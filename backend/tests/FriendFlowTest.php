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
use Turysta\Social\FriendService;

/** Znajomi: zaproszenia, akceptacja, widocznosc statusu online, dukatow i punktow (prawdziwy MySQL). */
final class FriendFlowTest extends TestCase
{
    private PDO $pdo;
    private AuthService $auth;
    private FriendService $friends;
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
        $this->auth = new AuthService($this->pdo, $mailer, new RateLimiter($this->pdo));
        $this->friends = new FriendService($this->pdo, new RateLimiter($this->pdo));
        $this->suffix = bin2hex(random_bytes(3));
    }

    /** @return array<string,mixed> wiersz users */
    private function user(string $prefix): array
    {
        $created = $this->auth->register([
            'character_name' => $prefix . $this->suffix,
            'email' => strtolower($prefix) . "{$this->suffix}@example.com",
            'password' => 'bardzo-dlugie-haslo',
            'password_confirm' => 'bardzo-dlugie-haslo',
            'accept_terms' => true,
        ], '10.7.' . random_int(1, 250) . '.' . random_int(1, 250));
        return $this->pdo->query("SELECT * FROM users WHERE id = {$created['id']}")->fetch();
    }

    private function ip(): string
    {
        return '10.1.' . random_int(1, 250) . '.' . random_int(1, 250);
    }

    /** @param list<array<string,mixed>> $rows */
    private function names(array $rows): array
    {
        return array_column($rows, 'name');
    }

    public function testRequestAcceptAndVisibility(): void
    {
        $ala = $this->user('Ala');
        $bob = $this->user('Bob');

        $sent = $this->friends->request($ala, ['name' => strtolower($bob['character_name'])], $this->ip());
        $this->assertSame('pending', $sent['status']);
        $this->assertSame('outgoing', $this->friends->state((int) $ala['id'], (int) $bob['id']));
        $this->assertSame('incoming', $this->friends->state((int) $bob['id'], (int) $ala['id']));

        // Zaproszony widzi je, ale jeszcze nie widzi dukatow ani punktow zapraszajacego.
        $bobView = $this->friends->overview((int) $bob['id']);
        $this->assertSame([$ala['character_name']], $this->names($bobView['incoming']));
        $this->assertSame([], $bobView['friends']);
        $this->assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id = {$bob['id']} AND type = 'friend_request'")->fetchColumn());

        // Zaprosic samemu siebie, zaprosic dwa razy lub nieistniejacego gracza sie nie da.
        foreach ([[$ala, ['name' => $ala['character_name']]], [$ala, ['name' => 'Nikt' . $this->suffix]]] as [$u, $in]) {
            try {
                $this->friends->request($u, $in, $this->ip());
                $this->fail('Zaproszenie powinno byc odrzucone.');
            } catch (ApiException $e) {
                $this->assertArrayHasKey('name', $e->fields);
            }
        }
        try {
            $this->friends->request($ala, ['user_id' => (int) $bob['id']], $this->ip());
            $this->fail('Drugie zaproszenie powinno byc odrzucone.');
        } catch (ApiException $e) {
            $this->assertSame('request_pending', $e->errorCode);
        }

        // Zapraszajacy nie moze sam siebie zaakceptowac.
        try {
            $this->friends->accept($ala, (int) $bob['id']);
            $this->fail('Akceptacja wlasnego zaproszenia powinna byc odrzucona.');
        } catch (ApiException $e) {
            $this->assertSame('not_found', $e->errorCode);
        }

        $this->friends->accept($bob, (int) $ala['id']);
        $this->assertSame('friends', $this->friends->state((int) $ala['id'], (int) $bob['id']));
        $this->assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id = {$ala['id']} AND type = 'friend_accepted'")->fetchColumn());

        $this->pdo->exec("UPDATE wallets SET coins_cents = 12345, points = 77 WHERE user_id = {$bob['id']}");
        $list = $this->friends->overview((int) $ala['id'])['friends'];
        $this->assertSame([$bob['character_name']], $this->names($list));
        $this->assertSame(12345, $list[0]['coins_cents']);
        $this->assertSame(77, $list[0]['points']);
        $this->assertFalse($list[0]['online']);

        // Aktywna sesja w ostatnich minutach oznacza "online".
        $sid = 'test' . bin2hex(random_bytes(8));
        $this->pdo->prepare("INSERT INTO sessions (id, user_id, data) VALUES (?, ?, '')")->execute([$sid, $bob['id']]);
        $this->assertTrue($this->friends->overview((int) $ala['id'])['friends'][0]['online']);
        $this->pdo->exec("UPDATE sessions SET last_activity = UTC_TIMESTAMP() - INTERVAL 10 MINUTE WHERE id = '$sid'");
        $this->assertFalse($this->friends->overview((int) $ala['id'])['friends'][0]['online']);
        $this->pdo->exec("DELETE FROM sessions WHERE id = '$sid'");

        $this->friends->remove((int) $bob['id'], (int) $ala['id']);
        $this->assertSame('none', $this->friends->state((int) $ala['id'], (int) $bob['id']));
        $this->assertSame([], $this->friends->overview((int) $ala['id'])['friends']);
    }

    public function testMutualRequestsBecomeFriendsAndDeclineDeletes(): void
    {
        $ala = $this->user('Ola');
        $bob = $this->user('Rob');
        $cez = $this->user('Cez');

        $this->friends->request($ala, ['user_id' => (int) $bob['id']], $this->ip());
        $this->assertSame('accepted', $this->friends->request($bob, ['user_id' => (int) $ala['id']], $this->ip())['status']);
        $this->assertSame('friends', $this->friends->state((int) $ala['id'], (int) $bob['id']));

        $this->friends->request($cez, ['name' => $ala['character_name']], $this->ip());
        $this->friends->remove((int) $ala['id'], (int) $cez['id']);
        $this->assertSame('none', $this->friends->state((int) $cez['id'], (int) $ala['id']));
    }

    public function testFriendshipsDisappearWithAccount(): void
    {
        $ala = $this->user('Usu');
        $bob = $this->user('Zos');
        $this->friends->request($ala, ['user_id' => (int) $bob['id']], $this->ip());
        $this->friends->accept($bob, (int) $ala['id']);

        (new \Turysta\Auth\UserDeleter($this->pdo))->delete((int) $ala['id']);
        $this->assertSame([], $this->friends->overview((int) $bob['id'])['friends']);
    }
}
