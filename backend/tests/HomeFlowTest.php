<?php
declare(strict_types=1);

namespace Turysta\Tests;

use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;
use Turysta\Auth\AuthService;
use Turysta\Auth\RateLimiter;
use Turysta\Database;
use Turysta\Game\AnnouncementService;
use Turysta\Game\Clock;
use Turysta\Game\DailyRewardService;
use Turysta\Game\Economy;
use Turysta\Game\RankingService;
use Turysta\Game\ReferralService;
use Turysta\Http\ApiException;
use Turysta\Mailer;

/** Test integracyjny na prawdziwym MySQL: codzienna nagroda, ogloszenia i czolowka rankingu. */
final class HomeFlowTest extends TestCase
{
    private PDO $pdo;
    private DailyRewardService $daily;
    private int $userId;

    protected function setUp(): void
    {
        try {
            $this->pdo = Database::pdo();
            $this->pdo->query('SELECT streak_day FROM daily_rewards LIMIT 1');
        } catch (Throwable $e) {
            $this->markTestSkipped('Brak bazy z migracjami: ' . $e->getMessage());
        }
        $mailer = new class extends Mailer {
            public function send(string $to, string $subject, string $text, ?string $html = null): bool
            {
                return true;
            }
        };
        $suffix = bin2hex(random_bytes(4));
        $user = (new AuthService($this->pdo, $mailer, new RateLimiter($this->pdo)))->register([
            'character_name' => 'Home' . $suffix,
            'email' => "home{$suffix}@example.com",
            'password' => 'bardzo-dlugie-haslo',
            'password_confirm' => 'bardzo-dlugie-haslo',
        ], '10.1.0.' . random_int(1, 250));
        $this->userId = (int) $user['id'];
        $this->daily = new DailyRewardService($this->pdo);
    }

    private function setLast(int $streakDay, int $daysAgo): void
    {
        $day = (new DateTimeImmutable(Clock::gameDay(Clock::now($this->pdo))))->modify("-$daysAgo day")->format('Y-m-d');
        $this->pdo->prepare(
            'INSERT INTO daily_rewards (user_id, streak_day, last_claim_day, claims_total) VALUES (?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE streak_day = VALUES(streak_day), last_claim_day = VALUES(last_claim_day)'
        )->execute([$this->userId, $streakDay, $day]);
    }

    /** @return array{0:int,1:int} dukaty (setne) i punkty gracza */
    private function wallet(): array
    {
        $stmt = $this->pdo->prepare('SELECT coins_cents, points FROM wallets WHERE user_id = ?');
        $stmt->execute([$this->userId]);
        $w = $stmt->fetch();
        return [(int) $w['coins_cents'], (int) $w['points']];
    }

    public function testFirstClaimGivesDayOneOnlyOncePerDay(): void
    {
        [$coins0, $points0] = $this->wallet();
        $this->assertSame(1, $this->daily->state($this->userId)['next_day']);

        $res = $this->daily->claim($this->userId);
        $this->assertSame(1, $res['reward']['day']);
        [$coins1, $points1] = $this->wallet();
        $this->assertSame(Economy::dailyCoins(1, 200), $coins1 - $coins0);
        $this->assertSame(Economy::DAILY_REWARDS[1][1], $points1 - $points0);
        $this->assertTrue($res['daily']['claimed_today']);
        $this->assertSame(1, $res['daily']['claimed_through']);

        try {
            $this->daily->claim($this->userId);
            $this->fail('Druga nagroda tego samego dnia powinna byc zablokowana.');
        } catch (ApiException $e) {
            $this->assertSame('already_claimed', $e->errorCode);
        }
        $this->assertSame([$coins1, $points1], $this->wallet());
    }

    public function testStreakContinuesAndRewardsGrow(): void
    {
        $this->setLast(3, 1);
        $state = $this->daily->state($this->userId);
        $this->assertSame(3, $state['claimed_through']);
        $this->assertSame(4, $state['next_day']);

        $this->assertSame(4, $this->daily->claim($this->userId)['reward']['day']);
        $this->assertGreaterThan(Economy::DAILY_REWARDS[1][0], Economy::DAILY_REWARDS[4][0]);
    }

    public function testAfterSeventhDayCycleStartsOver(): void
    {
        $this->setLast(7, 1);
        $state = $this->daily->state($this->userId);
        $this->assertSame(0, $state['claimed_through']);
        $this->assertSame(1, $this->daily->claim($this->userId)['reward']['day']);
    }

    public function testMissedDayResetsStreak(): void
    {
        $this->setLast(5, 2);
        $this->assertSame(0, $this->daily->state($this->userId)['claimed_through']);
        $this->assertSame(1, $this->daily->claim($this->userId)['reward']['day']);
    }

    public function testCoinRewardGrowsWithPlayerProgress(): void
    {
        $startDay1 = $this->daily->state($this->userId)['days'][0]['coins_cents'];

        $row = $this->pdo->query(
            'SELECT c.id, c.cost_cents FROM chapters c JOIN worlds w ON w.id = c.world_id ORDER BY w.ordinal DESC, c.ordinal DESC LIMIT 1'
        )->fetch();
        $this->pdo->prepare('UPDATE player_progress SET chapter_id = ? WHERE user_id = ?')->execute([$row['id'], $this->userId]);

        $days = $this->daily->state($this->userId)['days'];
        $this->assertGreaterThan($startDay1, $days[0]['coins_cents']);
        $this->assertSame(Economy::dailyCoins(7, (int) $row['cost_cents']), $days[6]['coins_cents']);

        [$coins0] = $this->wallet();
        $res = $this->daily->claim($this->userId);
        [$coins1] = $this->wallet();
        $this->assertSame($res['reward']['coins_cents'], $coins1 - $coins0);
        $this->assertSame($days[0]['coins_cents'], $res['reward']['coins_cents']);
    }

    public function testRewardsAreStrictlyIncreasingOverSevenDays(): void
    {
        $this->assertCount(7, Economy::DAILY_REWARDS);
        for ($d = 2; $d <= 7; $d++) {
            $this->assertGreaterThan(Economy::DAILY_REWARDS[$d - 1][0], Economy::DAILY_REWARDS[$d][0]);
            $this->assertGreaterThanOrEqual(Economy::DAILY_REWARDS[$d - 1][1], Economy::DAILY_REWARDS[$d][1]);
        }
    }

    public function testReferralCountersCountOnlyVerifiedAccountsAndTheirActivity(): void
    {
        $referral = new ReferralService($this->pdo);
        $this->assertSame(['registered' => 0, 'active' => 0, 'active_days_required' => 7], $referral->stats($this->userId));

        $ids = [];
        foreach ([0, 1, 2] as $i) {
            $suffix = bin2hex(random_bytes(4));
            $this->pdo->prepare("INSERT INTO users (email, password_hash, character_name) VALUES (?, 'x', ?)")
                ->execute(["ref{$suffix}@example.com", 'Ref' . $suffix]);
            $id = (int) $this->pdo->lastInsertId();
            $ids[] = $id;
            $this->pdo->prepare('INSERT INTO referrals (referrer_id, referred_id) VALUES (?, ?)')->execute([$this->userId, $id]);
        }
        // Konto liczy sie po potwierdzeniu e-maila: drugi i trzeci. Tylko trzeci ma 7 dni aktywnosci (pierwszy ma 7 dni, ale bez e-maila - nie liczy sie).
        $this->pdo->prepare('UPDATE users SET email_verified_at = NOW() WHERE id IN (?, ?)')->execute([$ids[1], $ids[2]]);
        foreach ([[$ids[0], 7], [$ids[1], 6], [$ids[2], 7]] as [$id, $days]) {
            $this->pdo->prepare(
                'INSERT INTO user_stats (user_id, active_days) VALUES (?, ?) ON DUPLICATE KEY UPDATE active_days = VALUES(active_days)'
            )->execute([$id, $days]);
        }

        $this->assertSame(['registered' => 2, 'active' => 1, 'active_days_required' => 7], $referral->stats($this->userId));
    }

    public function testAnnouncementsLifecycleAndVisibility(): void
    {
        $svc = new AnnouncementService($this->pdo);
        $marker = 'Test ' . bin2hex(random_bytes(3));

        $a = $svc->create($this->userId, ['title' => $marker, 'body' => 'Treść', 'published' => true, 'pinned' => false]);
        $draft = $svc->create($this->userId, ['title' => $marker . ' szkic', 'body' => 'Treść', 'published' => false]);

        $publicTitles = array_column($svc->published(50), 'title');
        $this->assertContains($marker, $publicTitles);
        $this->assertNotContains($marker . ' szkic', $publicTitles);
        $this->assertContains($marker . ' szkic', array_column($svc->all(), 'title'));

        $updated = $svc->update($draft['id'], ['title' => $marker . ' szkic', 'body' => 'Nowa treść', 'published' => true]);
        $this->assertTrue($updated['published']);
        $this->assertContains($marker . ' szkic', array_column($svc->published(50), 'title'));

        try {
            $svc->create($this->userId, ['title' => '', 'body' => 'x']);
            $this->fail('Pusty tytul powinien byc odrzucony.');
        } catch (ApiException $e) {
            $this->assertArrayHasKey('title', $e->fields);
        }

        $svc->delete($a['id']);
        $svc->delete($draft['id']);
        $this->assertNotContains($marker, array_column($svc->all(), 'title'));
    }

    public function testTopRankingReturnsOrderedRows(): void
    {
        $top = (new RankingService($this->pdo))->top(5, $this->userId);
        $this->assertLessThanOrEqual(5, count($top));
        $this->assertNotEmpty($top);
        for ($i = 1; $i < count($top); $i++) {
            $this->assertGreaterThanOrEqual($top[$i]['points'], $top[$i - 1]['points']);
        }
    }
}
