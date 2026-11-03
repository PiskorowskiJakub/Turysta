<?php
declare(strict_types=1);

namespace Turysta\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;
use Turysta\Auth\AuthService;
use Turysta\Auth\RateLimiter;
use Turysta\Database;
use Turysta\Game\ActivityTracker;
use Turysta\Game\BadgeService;
use Turysta\Game\Clock;
use Turysta\Game\Economy;
use Turysta\Game\JobService;
use Turysta\Game\PlayerProfileService;
use Turysta\Game\RankingService;
use Turysta\Game\Settler;
use Turysta\Game\StatsService;
use Turysta\Game\TrainingService;
use Turysta\Game\Wallet;
use Turysta\Http\ApiException;
use Turysta\Mailer;

/** Test integracyjny na prawdziwym MySQL (wymaga zmigrowanej bazy). */
final class GameFlowTest extends TestCase
{
    private PDO $pdo;
    private Settler $settler;
    private TrainingService $training;
    private JobService $jobs;
    private int $userId;

    /** Bonus startowy z rejestracji (AuthService::STARTING_COINS_CENTS), obecny w portfelu kazdego nowego gracza. */
    private const START_CENTS = 200;

    protected function setUp(): void
    {
        try {
            $this->pdo = Database::pdo();
            $this->pdo->query('SELECT duration_seconds FROM training_queue LIMIT 1');
        } catch (Throwable $e) {
            $this->markTestSkipped('Brak bazy z migracjami: ' . $e->getMessage());
        }
        $this->settler = new Settler($this->pdo);
        $this->training = new TrainingService($this->pdo, $this->settler);
        $this->jobs = new JobService($this->pdo, $this->settler);

        $mailer = new class extends Mailer {
            public function send(string $to, string $subject, string $text, ?string $html = null): bool
            {
                return true;
            }
        };
        $auth = new AuthService($this->pdo, $mailer, new RateLimiter($this->pdo));
        $suffix = bin2hex(random_bytes(4));
        $user = $auth->register([
            'character_name' => 'Gra' . $suffix,
            'email' => "gra{$suffix}@example.com",
            'password' => 'bardzo-dlugie-haslo',
            'password_confirm' => 'bardzo-dlugie-haslo',
            'accept_terms' => true,
        ], '10.1.' . random_int(1, 250) . '.' . random_int(1, 250));
        $this->userId = (int) $user['id'];
    }

    private function skillId(string $code = 'fitness'): int
    {
        return (int) $this->pdo->query("SELECT id FROM skill_definitions WHERE code = '$code'")->fetchColumn();
    }

    private function jobId(string $code): int
    {
        return (int) $this->pdo->query("SELECT id FROM job_definitions WHERE code = '$code'")->fetchColumn();
    }

    private function coins(): int
    {
        return (int) $this->pdo->query("SELECT coins_cents FROM wallets WHERE user_id = {$this->userId}")->fetchColumn();
    }

    private function fund(int $cents): void
    {
        $this->pdo->beginTransaction();
        Wallet::apply($this->pdo, $this->userId, 'coins', $cents, 'test_fund');
        $this->pdo->commit();
    }

    public function testQueueHasFourSlotsAndRefundsQueuedInFull(): void
    {
        $this->fund(1000000);
        $this->training->add($this->userId, $this->skillId());
        $this->training->add($this->userId, $this->skillId());
        $this->training->add($this->userId, $this->skillId());
        $state = $this->training->add($this->userId, $this->skillId());

        $this->assertNotNull($state['active']);
        $this->assertCount(3, $state['queue']);
        $this->assertSame([2, 3, 4], array_map(fn($q) => $q['target_level'], $state['queue']));

        try {
            $this->training->add($this->userId, $this->skillId('trade'));
            $this->fail('Piate szkolenie powinno byc odrzucone');
        } catch (ApiException $e) {
            $this->assertSame('queue_full', $e->errorCode);
        }

        // Usuniecie pierwszego oczekujacego poziomu: pelny zwrot, a kolejne poziomy przesuwaja sie w dol
        // (nizszy koszt i czas, roznica wraca do portfela).
        $before = $this->coins();
        $first = $state['queue'][0];
        $last = $state['queue'][2];
        $after = $this->training->remove($this->userId, $first['id']);
        $this->assertSame($before + $last['cost_cents'], $this->coins());
        $this->assertCount(2, $after['queue']);
        $this->assertSame([2, 3], array_map(fn($q) => $q['target_level'], $after['queue']));
        $this->assertSame($first['cost_cents'], $after['queue'][0]['cost_cents']);

        // Usuniecie ostatniego poziomu: nic sie nie przesuwa, wraca jego pelny koszt.
        $before = $this->coins();
        $rest = $after['queue'][1];
        $this->training->remove($this->userId, $rest['id']);
        $this->assertSame($before + $rest['cost_cents'], $this->coins());
    }

    public function testChapterAdvanceNeedsRequirementsAndSharesQueue(): void
    {
        $this->fund(10000000);
        try {
            $this->training->addChapter($this->userId);
            $this->fail('Awans bez wymaganych poziomow powinien byc odrzucony');
        } catch (ApiException $e) {
            $this->assertSame('requirements_not_met', $e->errorCode);
        }

        $this->pdo->exec("UPDATE user_skills SET level = 50 WHERE user_id = {$this->userId}");
        $state = $this->training->addChapter($this->userId);
        $this->assertSame('chapter', $state['active']['kind']);

        $state = $this->training->add($this->userId, $this->skillId());
        $this->assertCount(1, $state['queue']);
        $this->assertSame('skill', $state['queue'][0]['kind']);

        try {
            $this->training->addChapter($this->userId);
            $this->fail('Drugi awans naraz powinien byc odrzucony');
        } catch (ApiException $e) {
            $this->assertSame('chapter_in_queue', $e->errorCode);
        }
    }

    public function testCancellingActiveRefundsHalfAndStartsNext(): void
    {
        $this->fund(1000000);
        $this->training->add($this->userId, $this->skillId());
        $state = $this->training->add($this->userId, $this->skillId('trade'));
        $active = $state['active'];

        $before = $this->coins();
        $after = $this->training->remove($this->userId, $active['id']);

        $this->assertSame($before + intdiv($active['cost_cents'], 2), $this->coins());
        $this->assertSame('trade', $after['active']['skill']['code']);
        $this->assertCount(0, $after['queue']);
    }

    public function testSettlementCompletesTrainingAndChainsNextFromEndTime(): void
    {
        $this->fund(1000000);
        $this->training->add($this->userId, $this->skillId());
        $this->training->add($this->userId, $this->skillId());

        $this->pdo->exec(
            "UPDATE training_queue SET ends_at = UTC_TIMESTAMP() - INTERVAL 1 SECOND
             WHERE user_id = {$this->userId} AND status = 'active'"
        );
        $endedAt = (string) $this->pdo->query(
            "SELECT ends_at FROM training_queue WHERE user_id = {$this->userId} AND status = 'active'"
        )->fetchColumn();

        $duration = (int) $this->pdo->query(
            "SELECT duration_seconds FROM training_queue WHERE user_id = {$this->userId} AND target_level = 1 AND skill_id = {$this->skillId()}"
        )->fetchColumn();

        // Ledwie skonczone (1 s temu), zeby krotki 2. poziom nie zdazyl sie skonczyc w tym samym rozliczeniu.
        $this->settler->settleUser($this->userId);
        $this->settler->settleUser($this->userId); // idempotentnie

        $level = (int) $this->pdo->query(
            "SELECT level FROM user_skills WHERE user_id = {$this->userId} AND skill_id = {$this->skillId()}"
        )->fetchColumn();
        $this->assertSame(1, $level);
        $this->assertSame(Economy::trainingPoints($duration), (int) $this->pdo->query("SELECT points FROM wallets WHERE user_id = {$this->userId}")->fetchColumn());

        $next = $this->pdo->query(
            "SELECT started_at, status FROM training_queue WHERE user_id = {$this->userId} AND target_level = 2"
        )->fetch();
        $this->assertSame('active', $next['status']);
        $this->assertSame($endedAt, $next['started_at']);
    }

    public function testProportionalJobPaysForWorkedTime(): void
    {
        $this->jobs->start($this->userId, $this->jobId('market'));
        $this->pdo->exec(
            "UPDATE job_runs SET started_at = UTC_TIMESTAMP() - INTERVAL 4 HOUR, ends_at = UTC_TIMESTAMP() + INTERVAL 4 HOUR
             WHERE user_id = {$this->userId} AND status = 'active'"
        );

        $result = $this->jobs->stop($this->userId, false);

        $this->assertEqualsWithDelta(12350, $result['payout_cents'], 20);
        $this->assertSame(self::START_CENTS + $result['payout_cents'], $this->coins());
        $this->assertNull($result['active']);

        // Przerwanie pracy proporcjonalnej nie uruchamia przerwy - mozna zaczac od razu.
        $again = $this->jobs->start($this->userId, $this->jobId('market'));
        $this->assertNotNull($again['active']);
    }

    public function testTradeLevelRaisesJobRewardAndIsFrozenAtStart(): void
    {
        $this->pdo->prepare('INSERT INTO user_skills (user_id, skill_id, level) VALUES (?, ?, 120)
                             ON DUPLICATE KEY UPDATE level = 120')
            ->execute([$this->userId, $this->skillId('trade')]);

        $list = $this->jobs->list($this->userId);
        $this->assertSame(600, $list['trade']['bonus_permille']);

        $this->jobs->start($this->userId, $this->jobId('market'));
        // Zmiana poziomu po starcie nie wplywa na wyplate trwajacej pracy.
        $this->pdo->exec("UPDATE user_skills SET level = 200 WHERE user_id = {$this->userId}");
        $this->pdo->exec(
            "UPDATE job_runs SET started_at = UTC_TIMESTAMP() - INTERVAL 9 HOUR, ends_at = UTC_TIMESTAMP() - INTERVAL 1 HOUR
             WHERE user_id = {$this->userId} AND status = 'active'"
        );

        $this->settler->settleUser($this->userId);

        $this->assertSame(self::START_CENTS + 39520, $this->coins());
    }

    public function testEarningsAreRecordedPerJobAndInTotal(): void
    {
        // Przerwany targ: polowa wyplaty.
        $this->jobs->start($this->userId, $this->jobId('market'));
        $this->pdo->exec(
            "UPDATE job_runs SET started_at = UTC_TIMESTAMP() - INTERVAL 4 HOUR, ends_at = UTC_TIMESTAMP() + INTERVAL 4 HOUR
             WHERE user_id = {$this->userId} AND status = 'active'"
        );
        $half = $this->jobs->stop($this->userId, false)['payout_cents'];

        // Ukonczony hostel (rozliczony przez Settler).
        $this->jobs->start($this->userId, $this->jobId('hostel'));
        $this->pdo->exec(
            "UPDATE job_runs SET started_at = UTC_TIMESTAMP() - INTERVAL 13 HOUR, ends_at = UTC_TIMESTAMP() - INTERVAL 1 HOUR
             WHERE user_id = {$this->userId} AND status = 'active'"
        );
        $this->settler->settleUser($this->userId);

        $stats = (new StatsService($this->pdo))->forUser($this->userId);
        $byCode = array_column($stats['earnings']['jobs'], 'cents', 'code');
        $this->assertSame($half, $byCode['market']);
        $this->assertSame(42000, $byCode['hostel']);
        $this->assertSame(0, $byCode['expedition']);
        $this->assertSame($half + 42000, $stats['earnings']['total_cents']);
        $this->assertSame(0, $stats['earnings']['other_cents']);
        $this->assertGreaterThanOrEqual(1, $stats['rank']);

        // Przyszle zrodlo zarobku trafia do sumy i do "inne".
        $this->pdo->beginTransaction();
        Wallet::earn($this->pdo, $this->userId, 'shop_sale', 500, 'test_sale');
        $this->pdo->commit();
        $stats = (new StatsService($this->pdo))->forUser($this->userId);
        $this->assertSame(500, $stats['earnings']['other_cents']);
        $this->assertSame($half + 42000 + 500, $stats['earnings']['total_cents']);
    }

    public function testActivityDaysAndStreaks(): void
    {
        $stats = new StatsService($this->pdo);
        ActivityTracker::touch($this->pdo, $this->userId, '2026-01-10');
        ActivityTracker::touch($this->pdo, $this->userId, '2026-01-10'); // ta sama doba: bez zmian
        ActivityTracker::touch($this->pdo, $this->userId, '2026-01-11');
        ActivityTracker::touch($this->pdo, $this->userId, '2026-01-12');
        $s = $stats->forUser($this->userId);
        $this->assertSame(3, $s['active_days']);
        $this->assertSame(3, $s['current_streak']);
        $this->assertSame(3, $s['longest_streak']);

        // Przerwa: seria wraca do 1, rekord zostaje.
        ActivityTracker::touch($this->pdo, $this->userId, '2026-01-15');
        $s = $stats->forUser($this->userId);
        $this->assertSame(4, $s['active_days']);
        $this->assertSame(1, $s['current_streak']);
        $this->assertSame(3, $s['longest_streak']);
        $this->assertSame(1, $s['account_days']);
    }

    public function testWorldSkillsAreLockedUntilTheWorldIsReached(): void
    {
        // Gracz zaczyna w swiecie 1: umiejetnosc swiata 1 dziala, swiata 2 jest zablokowana.
        $list = $this->training->skills($this->userId);
        $byCode = array_column($list['skills'], null, 'code');
        $this->assertFalse($byCode['w1_packing']['locked']);
        $this->assertTrue($byCode['w2_mushrooms']['locked']);
        $this->assertSame(1, $list['current_world']);
        $this->assertCount(5, $list['worlds']);

        $state = $this->training->add($this->userId, $byCode['w1_packing']['id']);
        $this->assertSame('w1_packing', $state['active']['skill']['code']);

        try {
            $this->training->add($this->userId, $byCode['w2_mushrooms']['id']);
            $this->fail('Umiejetnosc swiata 2 powinna byc zablokowana');
        } catch (ApiException $e) {
            $this->assertSame('skill_locked', $e->errorCode);
        }

        // Po przejsciu do swiata 2 odblokowuje sie.
        $this->pdo->prepare(
            "UPDATE player_progress SET chapter_id = (SELECT c.id FROM chapters c JOIN worlds w ON w.id = c.world_id
             WHERE w.ordinal = 2 AND c.ordinal = 1) WHERE user_id = ?"
        )->execute([$this->userId]);
        $this->assertFalse(array_column($this->training->skills($this->userId)['skills'], null, 'code')['w2_mushrooms']['locked']);
    }

    public function testPromotionNeedsWorldSkillsGraduallyAndAllForNewWorld(): void
    {
        $this->fund(100000000);
        $this->pdo->exec("UPDATE user_skills us JOIN skill_definitions s ON s.id = us.skill_id SET us.level = 300 WHERE us.user_id = {$this->userId} AND s.world_ordinal IS NULL");
        $chapters = new \Turysta\Game\ChapterService($this->pdo);

        // Pierwszy awans: 4 poziomy kazdej umiejetnosci swiata 1 (ceil(33 * 1 / 10)).
        $next = $chapters->next($this->userId);
        $req = $chapters->worldRequirement($this->userId, $next);
        $this->assertSame(4, $req['required']);
        $this->assertSame(6, $req['total']);
        $this->assertFalse($req['met']);
        try {
            $this->training->addChapter($this->userId);
            $this->fail('Bez umiejetnosci swiata awans powinien byc odrzucony');
        } catch (ApiException $e) {
            $this->assertSame('requirements_not_met', $e->errorCode);
        }
        $this->pdo->exec(
            "UPDATE user_skills us JOIN skill_definitions s ON s.id = us.skill_id SET us.level = 4
             WHERE us.user_id = {$this->userId} AND s.world_ordinal = 1"
        );
        $this->assertSame('chapter', $this->training->addChapter($this->userId)['active']['kind']);

        // Przejscie z rozdzialu 10 do nowego swiata: wszystkie umiejetnosci swiata 1 na maksimum, a awans jest wydarzeniem.
        $this->pdo->exec("DELETE FROM training_queue WHERE user_id = {$this->userId}");
        $this->pdo->prepare(
            "UPDATE player_progress SET chapter_id = (SELECT c.id FROM chapters c JOIN worlds w ON w.id = c.world_id
             WHERE w.ordinal = 1 AND c.ordinal = 10) WHERE user_id = ?"
        )->execute([$this->userId]);
        $this->pdo->exec(
            "UPDATE user_skills us JOIN skill_definitions s ON s.id = us.skill_id SET us.level = s.max_level - 1
             WHERE us.user_id = {$this->userId} AND s.world_ordinal = 1"
        );
        $next = $chapters->next($this->userId);
        $req = $chapters->worldRequirement($this->userId, $next);
        $this->assertTrue($req['complete']);
        $this->assertFalse($req['met']);
        $this->assertSame(0, $req['done']);
        $this->assertSame(6, $req['total']);

        $this->pdo->exec(
            "UPDATE user_skills us JOIN skill_definitions s ON s.id = us.skill_id SET us.level = s.max_level
             WHERE us.user_id = {$this->userId} AND s.world_ordinal = 1"
        );
        $this->assertTrue($chapters->worldRequirement($this->userId, $next)['met']);
        $normal = (int) $this->pdo->query(
            "SELECT c.cost_cents FROM chapters c JOIN worlds w ON w.id = c.world_id WHERE w.ordinal = 1 AND c.ordinal = 10"
        )->fetchColumn();
        $this->assertGreaterThan(5 * $normal, (int) $next['cost_cents']);
        $this->assertSame('chapter', $this->training->addChapter($this->userId)['active']['kind']);
    }

    public function testLongJobNeedsConfirmationAndPaysNothing(): void
    {
        $this->jobs->start($this->userId, $this->jobId('hostel'));

        try {
            $this->jobs->stop($this->userId, false);
            $this->fail('Przerwanie dlugiej pracy wymaga potwierdzenia');
        } catch (ApiException $e) {
            $this->assertSame('job_cancel_forfeit', $e->errorCode);
        }

        $result = $this->jobs->stop($this->userId, true);
        $this->assertSame(0, $result['payout_cents']);
        $this->assertSame(self::START_CENTS, $this->coins());
    }

    public function testCancelledLongJobLocksOnlyThatJob(): void
    {
        $this->jobs->start($this->userId, $this->jobId('hostel'));
        $this->jobs->stop($this->userId, true);

        $list = $this->jobs->list($this->userId);
        $this->assertNull($list['available_at']);
        $byCode = [];
        foreach ($list['jobs'] as $j) {
            $byCode[$j['code']] = $j;
        }
        $this->assertNotNull($byCode['hostel']['locked_until']);
        $this->assertNull($byCode['expedition']['locked_until']);

        try {
            $this->jobs->start($this->userId, $this->jobId('hostel'));
            $this->fail('Przerwana praca powinna byc zablokowana');
        } catch (ApiException $e) {
            $this->assertSame('job_locked', $e->errorCode);
        }

        $this->jobs->start($this->userId, $this->jobId('expedition'));
        $this->assertNotNull($this->jobs->active($this->userId));
    }

    public function testFinishedJobIsPaidInFullOnRead(): void
    {
        $this->jobs->start($this->userId, $this->jobId('market'));
        $this->pdo->exec(
            "UPDATE job_runs SET started_at = UTC_TIMESTAMP() - INTERVAL 9 HOUR, ends_at = UTC_TIMESTAMP() - INTERVAL 1 HOUR
             WHERE user_id = {$this->userId} AND status = 'active'"
        );

        $this->settler->settleUser($this->userId);

        $this->assertSame(self::START_CENTS + 24700, $this->coins());
        $this->assertSame(4, (int) $this->pdo->query("SELECT points FROM wallets WHERE user_id = {$this->userId}")->fetchColumn());
    }

    public function testRankingSearchPaginationAndMyPlace(): void
    {
        // Punkty o 1 wyzsze niz u kazdego innego gracza, wiec test dziala tez na bazie z danymi po poprzednich uruchomieniach.
        $this->pdo->exec(
            "UPDATE wallets SET points = (SELECT m FROM (SELECT COALESCE(MAX(points), 0) + 1 AS m FROM wallets WHERE user_id <> {$this->userId}) x)
             WHERE user_id = {$this->userId}"
        );
        $name = (string) $this->pdo->query("SELECT character_name FROM users WHERE id = {$this->userId}")->fetchColumn();
        $ranking = new RankingService($this->pdo);

        $all = $ranking->page($this->userId, 1, 50, '', 0);
        $this->assertSame(1, $all['rows'][0]['rank']);
        $this->assertTrue($all['rows'][0]['me']);
        $this->assertSame(1, $all['me']['rank']);
        $this->assertSame(1, $all['me']['page']);
        $this->assertSame(50, $all['per_page']);

        // Nieprawidlowa liczba wierszy na strone wraca do domyslnej, strona poza zakresem jest przycinana.
        $odd = $ranking->page($this->userId, 9999, 7, '', 0);
        $this->assertSame(50, $odd['per_page']);
        $this->assertSame($odd['pages'], $odd['page']);

        // Wyszukiwanie po fragmencie nazwy nie zmienia miejsca w rankingu.
        $found = $ranking->page($this->userId, 1, 50, mb_substr($name, 1, 6), 0);
        $this->assertGreaterThanOrEqual(1, $found['total']);
        $mine = array_values(array_filter($found['rows'], fn($r) => $r['me']));
        $this->assertCount(1, $mine);
        $this->assertSame(1, $mine[0]['rank']);

        // Znaki specjalne LIKE sa traktowane doslownie.
        $this->assertSame(0, $ranking->page($this->userId, 1, 50, '%%%', 0)['total']);
        $this->assertSame(0, $ranking->page($this->userId, 1, 50, 'zzzz-nie-ma-takiego-gracza', 0)['total']);
    }

    public function testBadgesAreAwardedOncePerTierWithEarnDate(): void
    {
        $this->pdo->exec("UPDATE user_skills SET level = 100 WHERE user_id = {$this->userId} AND skill_id = {$this->skillId()}");
        $this->pdo->exec("INSERT IGNORE INTO user_stats (user_id) VALUES ({$this->userId})");
        $this->pdo->exec("UPDATE user_stats SET longest_streak = 60, active_days = 100 WHERE user_id = {$this->userId}");

        $at = Clock::parse('2026-03-05 10:00:00');
        BadgeService::sync($this->pdo, $this->userId, $at);
        // Powtorne wywolanie niczego nie zmienia ani nie przesuwa daty.
        BadgeService::sync($this->pdo, $this->userId, Clock::parse('2026-09-09 10:00:00'));

        $byKey = [];
        foreach (BadgeService::earned($this->pdo, $this->userId) as $b) {
            $byKey[$b['group'] . '.' . $b['tier']] = $b;
        }
        $this->assertArrayHasKey('special_streak.diamond', $byKey);
        $this->assertArrayNotHasKey('special_streak.ruby', $byKey);
        $this->assertArrayHasKey('special_activity.silver', $byKey);
        $this->assertArrayNotHasKey('special_activity.gold', $byKey);
        $this->assertSame(60, $byKey['special_streak.diamond']['min']);
        $this->assertStringStartsWith('2026-03-05T10:00:00', $byKey['special_streak.diamond']['earned_at']);

        $skill = (string) $this->pdo->query("SELECT code FROM skill_definitions WHERE id = {$this->skillId()}")->fetchColumn();
        $this->assertArrayHasKey($skill . '.silver', $byKey);
        $this->assertArrayNotHasKey($skill . '.gold', $byKey);
    }

    public function testFeaturedBadgesOnlyFromEarnedMaxFiveUnique(): void
    {
        $this->pdo->exec("INSERT IGNORE INTO user_stats (user_id) VALUES ({$this->userId})");
        $this->pdo->exec("UPDATE user_stats SET longest_streak = 14, active_days = 30 WHERE user_id = {$this->userId}");
        BadgeService::sync($this->pdo, $this->userId, Clock::parse('2026-03-05 10:00:00'));

        $this->assertSame(['special_activity', 'special_streak'], BadgeService::setFeatured($this->pdo, $this->userId, ['special_activity', 'special_streak']));
        $this->assertSame(['special_activity', 'special_streak'], BadgeService::featured($this->pdo, $this->userId));
        // Ponowny zapis zastepuje poprzedni wybor, kolejnosc jest zachowana.
        BadgeService::setFeatured($this->pdo, $this->userId, ['special_streak']);
        $this->assertSame(['special_streak'], BadgeService::featured($this->pdo, $this->userId));

        foreach ([['fitness'], ['special_streak', 'special_streak'], ['a', 'b', 'c', 'd', 'e', 'f'], 'special_streak'] as $bad) {
            try {
                BadgeService::setFeatured($this->pdo, $this->userId, $bad);
                $this->fail('Nieprawidlowy wybor powinien byc odrzucony');
            } catch (ApiException $e) {
                $this->assertSame('validation_failed', $e->errorCode);
            }
        }
        $this->assertSame(['special_streak'], BadgeService::featured($this->pdo, $this->userId));
    }

    public function testPublicPlayerProfileHidesWorldNamesFromPlayersWhoHaventReachedThem(): void
    {
        $service = new PlayerProfileService($this->pdo);

        $own = $service->forPlayer($this->userId, $this->userId);
        $this->assertSame($this->userId, $own['id']);
        $this->assertNotNull($own['world']['name']);
        $this->assertIsArray($own['badges']);
        $this->assertArrayNotHasKey('email', $own);

        // Ogladajacy bez postepu (tu: nieistniejacy gracz 0) nie widzi nazw swiata i rozdzialu.
        $stranger = $service->forPlayer(0, $this->userId);
        $this->assertSame($own['world']['ordinal'], $stranger['world']['ordinal']);
        $this->assertNull($stranger['world']['name']);
        $this->assertNull($stranger['chapter']['name']);

        try {
            $service->forPlayer($this->userId, 999999999);
            $this->fail('Nieistniejacy gracz powinien dac 404');
        } catch (ApiException $e) {
            $this->assertSame('player_not_found', $e->errorCode);
        }
    }
}
