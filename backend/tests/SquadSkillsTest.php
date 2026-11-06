<?php
declare(strict_types=1);

namespace Turysta\Tests;

use DateInterval;
use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;
use Turysta\Auth\AuthService;
use Turysta\Auth\RateLimiter;
use Turysta\Database;
use Turysta\Game\Clock;
use Turysta\Http\ApiException;
use Turysta\Mailer;
use Turysta\Squad\SquadFire;
use Turysta\Squad\SquadRules;
use Turysta\Squad\SquadService;
use Turysta\Squad\SquadSkills;

/** Umiejetnosci oddzialowe (Etap 4): koszty, osobny slot, srednia aktywnych, tarcza i zniżki (prawdziwy MySQL). */
final class SquadSkillsTest extends TestCase
{
    private PDO $pdo;
    private AuthService $auth;
    private SquadService $squads;
    private SquadSkills $skills;
    private string $suffix;

    protected function setUp(): void
    {
        try {
            $this->pdo = Database::pdo();
            $this->pdo->query('SELECT 1 FROM squad_skill_queue LIMIT 1');
        } catch (Throwable $e) {
            $this->markTestSkipped('Brak bazy z migracjami: ' . $e->getMessage());
        }
        $mailer = new class extends Mailer {
            public function send(string $to, string $subject, string $text, ?string $html = null): bool
            {
                return true;
            }
        };
        $this->auth = new AuthService($this->pdo, $mailer, new RateLimiter($this->pdo));
        $this->squads = new SquadService($this->pdo, new RateLimiter($this->pdo));
        $this->skills = new SquadSkills($this->pdo);
        $this->suffix = bin2hex(random_bytes(3));
    }

    /** @return array<string,mixed> */
    private function user(string $prefix): array
    {
        $created = $this->auth->register([
            'character_name' => $prefix . $this->suffix,
            'email' => strtolower($prefix) . "{$this->suffix}@example.com",
            'password' => 'bardzo-dlugie-haslo',
            'password_confirm' => 'bardzo-dlugie-haslo',
            'accept_terms' => true,
        ], '10.9.' . random_int(1, 250) . '.' . random_int(1, 250));
        $id = (int) $created['id'];
        $this->pdo->prepare('UPDATE users SET email_verified_at = UTC_TIMESTAMP(), created_at = UTC_TIMESTAMP() - INTERVAL 10 DAY WHERE id = ?')->execute([$id]);
        $this->pdo->prepare('UPDATE wallets SET coins_cents = 10000000 WHERE user_id = ?')->execute([$id]);
        return $this->pdo->query("SELECT * FROM users WHERE id = $id")->fetch();
    }

    private function coins(int $userId): int
    {
        return (int) $this->pdo->query("SELECT coins_cents FROM wallets WHERE user_id = $userId")->fetchColumn();
    }

    private function setLevel(int $userId, string $skill, int $level): void
    {
        $this->pdo->prepare('INSERT INTO user_squad_skills (user_id, skill, level) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE level = VALUES(level)')
            ->execute([$userId, $skill, $level]);
    }

    private function activate(int $userId, int $days = 5): void
    {
        $today = Clock::gameDay(Clock::now($this->pdo));
        for ($i = 0; $i < $days; $i++) {
            $day = (new \DateTimeImmutable($today))->sub(new DateInterval('P' . $i . 'D'))->format('Y-m-d');
            $this->pdo->prepare('INSERT IGNORE INTO user_active_days (user_id, day) VALUES (?, ?)')->execute([$userId, $day]);
        }
    }

    /** @return array{0:list<array<string,mixed>>,1:int} trzech czlonkow (pierwszy to przywodca) i id oddzialu */
    private function squad(): array
    {
        $a = $this->user('SkA');
        $b = $this->user('SkB');
        $c = $this->user('SkC');
        $id = $this->squads->create($a, ['name' => "Umiej {$this->suffix}", 'specialization' => 'gorska', 'join_mode' => 'open'], '10.9.1.1')['id'];
        $this->squads->join($b, $id);
        $this->squads->join($c, $id);
        return [[$a, $b, $c], $id];
    }

    public function testCostsDurationAndEffectCurve(): void
    {
        $this->assertSame(2 * 3600, SquadSkills::seconds(1));
        $this->assertSame(20 * 3600, SquadSkills::seconds(10));
        $this->assertSame(648, SquadSkills::costCents(1));
        $this->assertSame(6480, SquadSkills::costCents(10));
        $this->assertSame(5508, SquadSkills::costCents(10, 15.0));
        // L = polowa daje polowe maksimum, kolejne poziomy dalej cos daja.
        $this->assertEqualsWithDelta(15.0, SquadSkills::effectPercent('campfire', 40), 0.001);
        $this->assertGreaterThan(SquadSkills::effectPercent('campfire', 100), SquadSkills::effectPercent('campfire', 200));
        $this->assertLessThan(30.0, SquadSkills::effectPercent('campfire', 100000));
        $this->assertEqualsWithDelta(0.0, SquadSkills::effectPercent('campfire', 0), 0.001);
    }

    public function testSeparateSlotTrainingFlowRefundAndCompletion(): void
    {
        $u = $this->user('SkSolo');
        $uid = (int) $u['id'];
        $before = $this->coins($uid);

        $state = $this->skills->train($uid, 'campfire');
        $this->assertNotNull($state['active']);
        $this->assertSame(1, $state['active']['target_level']);
        $this->assertSame($before - 648, $this->coins($uid));
        $this->skills->train($uid, 'campfire');
        $this->skills->train($uid, 'guidance');
        try {
            $this->skills->train($uid, 'supplies');
            $this->fail('Kolejka powinna byc pelna.');
        } catch (ApiException $e) {
            $this->assertSame('queue_full', $e->errorCode);
        }

        // Anulowanie szkolenia w kolejce: pelny zwrot; kolejny poziom tej samej umiejetnosci przesuwa sie w dol.
        $state = $this->skills->state($uid);
        $queuedCampfire = $state['queue'][0];
        $this->assertSame(2, $queuedCampfire['target_level']);
        $state = $this->skills->cancel($uid, $queuedCampfire['id']);
        $this->assertCount(1, $state['queue']);

        // Koniec szkolenia: poziom rosnie, startuje nastepne, jest powiadomienie.
        $this->pdo->prepare("UPDATE squad_skill_queue SET ends_at = UTC_TIMESTAMP() - INTERVAL 1 MINUTE WHERE user_id = ? AND status = 'active'")->execute([$uid]);
        $state = $this->skills->state($uid);
        $this->assertSame(1, SquadSkills::levels($this->pdo, $uid)['campfire']);
        $this->assertSame('guidance', $state['active']['skill']);
        $n = $this->pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND type = 'squad_skill_finished'");
        $n->execute([$uid]);
        $this->assertSame(1, (int) $n->fetchColumn());

        // Anulowanie aktywnego: zwrot 50%.
        $coinsBefore = $this->coins($uid);
        $this->skills->cancel($uid, $state['active']['id']);
        $this->assertSame($coinsBefore + intdiv(648, 2), $this->coins($uid));
    }

    public function testProfileAveragesActiveMembersWithRoleBonusAndMinimum(): void
    {
        [$members, $id] = $this->squad();
        // Trzech czlonkow, ale tylko dwoch aktywnych: brak bonusu.
        $this->activate((int) $members[0]['id']);
        $this->activate((int) $members[1]['id']);
        $this->setLevel((int) $members[0]['id'], 'campfire', 40);
        $this->setLevel((int) $members[1]['id'], 'campfire', 40);
        $this->setLevel((int) $members[2]['id'], 'campfire', 400);
        $p = SquadSkills::profile($this->pdo, $id);
        $this->assertSame(2, $p['active']);
        $this->assertFalse($p['enough']);
        $this->assertSame(0.0, $p['effect']['campfire']);

        // Trzeci tez aktywny: srednia z trzech; przywodca ma +10% do poziomu w Rozpalaniu.
        $this->activate((int) $members[2]['id']);
        $this->setLevel((int) $members[2]['id'], 'campfire', 40);
        $p = SquadSkills::profile($this->pdo, $id);
        $this->assertTrue($p['enough']);
        $this->assertEqualsWithDelta((44 + 40 + 40) / 3, $p['avg']['campfire'], 0.01);
        $this->assertGreaterThan(14.9, $p['effect']['campfire']);

        // Kartografia odblokowuje kolejne trudnosci tras.
        $this->assertSame(['easy', 'medium', 'hard'], SquadSkills::unlockedDifficulties($this->pdo, $id));
        foreach ($members as $m) {
            $this->setLevel((int) $m['id'], 'cartography', 30);
        }
        $this->assertContains('expert', SquadSkills::unlockedDifficulties($this->pdo, $id));
        $this->assertNotContains('legend', SquadSkills::unlockedDifficulties($this->pdo, $id));
    }

    public function testEmberBonusAndShieldRules(): void
    {
        // 4 z 10 aktywnych, seria 1: baza 10; +30% z Rozpalania = 13.
        $this->assertSame(10, SquadFire::ember(4, 10, 1));
        $this->assertSame(13, SquadFire::ember(4, 10, 1, 30.0));

        $none = ['active' => 5, 'enough' => true, 'avg' => array_fill_keys(SquadRules::SKILLS, 0.0), 'effect' => array_fill_keys(SquadRules::SKILLS, 0.0)];
        $this->assertSame(['cap' => 0, 'cooldown_days' => 14.0], SquadSkills::shield($none, 0));
        $this->assertSame(1, SquadSkills::shield($none, 3)['cap']);
        $this->assertSame(2, SquadSkills::shield($none, 15)['cap']);
        $aid = $none;
        $aid['avg']['first_aid'] = 10.0;
        $this->assertSame(1, SquadSkills::shield($aid, 0)['cap']);
        $this->assertSame(3, SquadSkills::shield($aid, 15)['cap']);
        $this->assertLessThan(14.0, SquadSkills::shield($aid, 0)['cooldown_days']);
        $this->assertGreaterThanOrEqual(7.0, SquadSkills::shield($aid, 100000)['cooldown_days']);
        $weak = $aid;
        $weak['enough'] = false;
        $this->assertSame(0, SquadSkills::shield($weak, 15)['cap']);
    }

    public function testPantryDiscountLowersTrainingCost(): void
    {
        [$members, $id] = $this->squad();
        $uid = (int) $members[1]['id'];
        $this->pdo->prepare("INSERT INTO squad_rooms (squad_id, room, level) VALUES (?, 'pantry', 10) ON DUPLICATE KEY UPDATE level = 10")->execute([$id]);
        $before = $this->coins($uid);
        $this->skills->train($uid, 'supplies');
        $expected = SquadSkills::costCents(1, SquadSkills::pantryDiscountPercent(10));
        $this->assertSame(599, $expected);
        $this->assertSame($before - $expected, $this->coins($uid));
    }
}
