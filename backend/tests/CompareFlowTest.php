<?php
declare(strict_types=1);

namespace Turysta\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;
use Turysta\Auth\AuthService;
use Turysta\Auth\RateLimiter;
use Turysta\Database;
use Turysta\Http\ApiException;
use Turysta\Mailer;
use Turysta\Social\CompareService;
use Turysta\Social\FriendService;

/** Porownanie kont znajomych (prawdziwy MySQL). */
final class CompareFlowTest extends TestCase
{
    private PDO $pdo;
    private AuthService $auth;
    private FriendService $friends;
    private CompareService $compare;
    private string $suffix;

    protected function setUp(): void
    {
        try {
            $this->pdo = Database::pdo();
            $this->pdo->query('SELECT 1 FROM friendships LIMIT 1');
        } catch (Throwable $e) {
            $this->markTestSkipped('Brak bazy z migracjami: ' . $e->getMessage());
        }
        $mailer = new class extends Mailer {
            public function send(string $to, string $subject, string $text, ?string $html = null): bool
            {
                return true;
            }
        };
        $limiter = new RateLimiter($this->pdo);
        $this->auth = new AuthService($this->pdo, $mailer, $limiter);
        $this->friends = new FriendService($this->pdo, $limiter);
        $this->compare = new CompareService($this->pdo, $this->friends);
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
        ], '10.6.' . random_int(1, 250) . '.' . random_int(1, 250));
        return $this->pdo->query("SELECT * FROM users WHERE id = {$created['id']}")->fetch();
    }

    private function setLevel(int $userId, string $code, int $level): void
    {
        $this->pdo->prepare('UPDATE user_skills SET level = ? WHERE user_id = ? AND skill_id = (SELECT id FROM skill_definitions WHERE code = ?)')
            ->execute([$level, $userId, $code]);
    }

    public function testOnlyFriendsCanBeCompared(): void
    {
        $ala = $this->user('Ala');
        $bob = $this->user('Bob');

        try {
            $this->compare->compare((int) $ala['id'], (int) $bob['id']);
            $this->fail('Obcego gracza nie wolno porownywac.');
        } catch (ApiException $e) {
            $this->assertSame('not_friends', $e->errorCode);
        }

        $this->friends->request($ala, ['user_id' => (int) $bob['id']], '10.6.0.1');
        try {
            $this->compare->compare((int) $ala['id'], (int) $bob['id']);
            $this->fail('Samo zaproszenie nie wystarcza.');
        } catch (ApiException $e) {
            $this->assertSame('not_friends', $e->errorCode);
        }
    }

    public function testComparisonShowsBothSidesAndHidesLockedWorldSkills(): void
    {
        $ala = $this->user('Ola');
        $bob = $this->user('Rob');
        $this->friends->request($ala, ['user_id' => (int) $bob['id']], '10.6.0.2');
        $this->friends->accept($bob, (int) $ala['id']);

        $this->setLevel((int) $ala['id'], 'fitness', 2);
        $this->setLevel((int) $bob['id'], 'fitness', 5);
        $this->pdo->exec("UPDATE wallets SET points = 40 WHERE user_id = {$ala['id']}");
        $this->pdo->exec("UPDATE wallets SET points = 90 WHERE user_id = {$bob['id']}");
        $worldSkill = (string) $this->pdo->query('SELECT code FROM skill_definitions WHERE world_ordinal = 2 LIMIT 1')->fetchColumn();
        if ($worldSkill !== '') {
            $this->setLevel((int) $bob['id'], $worldSkill, 3);
        }

        $r = $this->compare->compare((int) $ala['id'], (int) $bob['id']);
        $this->assertSame((int) $ala['id'], $r['me']['id']);
        $this->assertSame((int) $bob['id'], $r['friend']['id']);
        $this->assertSame(40, $r['me']['points']);
        $this->assertSame(90, $r['friend']['points']);
        $this->assertArrayNotHasKey('jobs', $r['me']);
        $this->assertNotEmpty($r['jobs']);

        $byCode = [];
        foreach ($r['skills'] as $s) {
            $byCode[$s['code']] = $s;
        }
        $this->assertSame(2, $byCode['fitness']['me']);
        $this->assertSame(5, $byCode['fitness']['friend']);
        // Umiejetnosc swiata 2 znajomego jest ukryta, dopoki gracz sam nie dotrze do tego swiata.
        if ($worldSkill !== '') {
            $this->assertArrayNotHasKey($worldSkill, $byCode);
        }
    }
}
