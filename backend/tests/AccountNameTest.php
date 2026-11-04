<?php
declare(strict_types=1);

namespace Turysta\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;
use Turysta\Auth\AccountService;
use Turysta\Auth\AuthService;
use Turysta\Auth\RateLimiter;
use Turysta\Database;
use Turysta\Http\ApiException;
use Turysta\Mailer;

/** Zmiana nazwy postaci: haslo, limit raz na pol roku, zajete i zarezerwowane nazwy (prawdziwy MySQL). */
final class AccountNameTest extends TestCase
{
    private const PASSWORD = 'bardzo-dlugie-haslo';

    private PDO $pdo;
    private AuthService $auth;
    private AccountService $account;
    private string $suffix;

    protected function setUp(): void
    {
        try {
            $this->pdo = Database::pdo();
            $this->pdo->query('SELECT 1 FROM user_name_changes LIMIT 1');
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
        $this->account = new AccountService($this->pdo, $mailer, new RateLimiter($this->pdo));
        $this->suffix = bin2hex(random_bytes(3));
    }

    /** @return array<string,mixed> */
    private function user(string $prefix): array
    {
        $created = $this->auth->register([
            'character_name' => $prefix . $this->suffix,
            'email' => strtolower($prefix) . "{$this->suffix}@example.com",
            'password' => self::PASSWORD,
            'password_confirm' => self::PASSWORD,
            'accept_terms' => true,
        ], '10.5.' . random_int(1, 250) . '.' . random_int(1, 250));
        return $this->fresh((int) $created['id']);
    }

    /** @return array<string,mixed> */
    private function fresh(int $id): array
    {
        return $this->pdo->query("SELECT * FROM users WHERE id = $id")->fetch();
    }

    private function expectError(string $code, callable $action): ApiException
    {
        try {
            $action();
        } catch (ApiException $e) {
            $this->assertSame($code, $e->errorCode);
            return $e;
        }
        $this->fail("Oczekiwano bledu $code.");
    }

    public function testChangeNameFlow(): void
    {
        $a = $this->user('NazA');
        $b = $this->user('NazB');
        $oldA = (string) $a['character_name'];
        $ip = '10.5.1.1';

        // Haslo, format i ta sama nazwa.
        $this->expectError('validation_failed', fn () => $this->account->changeName($a, 'zle-haslo', 'NowaNazwa' . $this->suffix, $ip));
        $this->expectError('validation_failed', fn () => $this->account->changeName($a, self::PASSWORD, 'ab', $ip));
        $this->expectError('validation_failed', fn () => $this->account->changeName($a, self::PASSWORD, $oldA, $ip));
        // Nazwa zajeta przez innego gracza.
        $this->expectError('already_exists', fn () => $this->account->changeName($a, self::PASSWORD, (string) $b['character_name'], $ip));

        // Udana zmiana zapisuje date i historie.
        $new = 'Nowy' . $this->suffix;
        $this->account->changeName($a, self::PASSWORD, $new, $ip);
        $a = $this->fresh((int) $a['id']);
        $this->assertSame($new, $a['character_name']);
        $this->assertNotNull($a['name_changed_at']);
        $this->assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM user_name_changes WHERE user_id = {$a['id']} AND old_name = " . $this->pdo->quote($oldA))->fetchColumn());

        // Kolejna zmiana jest zablokowana do konca limitu.
        $this->expectError('name_cooldown', fn () => $this->account->changeName($a, self::PASSWORD, 'Inny' . $this->suffix, $ip));

        // Porzucona nazwa jest zarezerwowana dla innych, ale nie dla jej poprzedniego wlasciciela.
        $this->expectError('already_exists', fn () => $this->account->changeName($b, self::PASSWORD, $oldA, $ip));
        $this->pdo->exec("UPDATE user_name_changes SET changed_at = UTC_TIMESTAMP() - INTERVAL 100 DAY WHERE user_id = {$a['id']}");
        $this->account->changeName($b, self::PASSWORD, $oldA, $ip);
        $this->assertSame($oldA, $this->fresh((int) $b['id'])['character_name']);

        // Po limicie pol roku mozna zmienic ponownie.
        $this->pdo->exec("UPDATE users SET name_changed_at = UTC_TIMESTAMP() - INTERVAL 181 DAY WHERE id = {$a['id']}");
        $this->account->changeName($this->fresh((int) $a['id']), self::PASSWORD, 'Trzeci' . $this->suffix, $ip);
        $this->assertSame('Trzeci' . $this->suffix, $this->fresh((int) $a['id'])['character_name']);
    }
}
