<?php
declare(strict_types=1);

namespace Turysta\Tests;

use PHPUnit\Framework\TestCase;
use Throwable;
use Turysta\Auth\AuthService;
use Turysta\Auth\PasswordResetService;
use Turysta\Auth\RateLimiter;
use Turysta\Database;
use Turysta\Http\ApiException;
use Turysta\Mailer;

/** Test integracyjny na prawdziwym MySQL (wymaga zmigrowanej bazy, w tym migracji 025). */
final class PasswordResetFlowTest extends TestCase
{
    private AuthService $auth;
    private PasswordResetService $reset;
    private object $mailer;
    private string $suffix;

    protected function setUp(): void
    {
        try {
            $pdo = Database::pdo();
            $pdo->query('SELECT 1 FROM password_resets LIMIT 1');
        } catch (Throwable $e) {
            $this->markTestSkipped('Brak bazy z migracjami: ' . $e->getMessage());
        }
        $this->mailer = new class extends Mailer {
            public int $sent = 0;
            public string $lastText = '';

            public function send(string $to, string $subject, string $text, ?string $html = null): bool
            {
                $this->sent++;
                $this->lastText = $text;
                return true;
            }
        };
        $limiter = new RateLimiter($pdo);
        $this->auth = new AuthService($pdo, $this->mailer, $limiter);
        $this->reset = new PasswordResetService($pdo, $this->mailer, $limiter);
        $this->suffix = bin2hex(random_bytes(4));
    }

    /** @return array{id:int,email:string,password:string} */
    private function account(): array
    {
        $d = [
            'character_name' => 'Reset' . $this->suffix,
            'email' => "reset{$this->suffix}@example.com",
            'password' => 'stare-dlugie-haslo',
            'password_confirm' => 'stare-dlugie-haslo',
        ];
        $user = $this->auth->register($d, '10.1.0.' . random_int(1, 250));
        return ['id' => (int) $user['id'], 'email' => $d['email'], 'password' => $d['password']];
    }

    private function tokenFromMail(): string
    {
        $this->assertSame(1, preg_match('/token=([a-f0-9]{64})/', $this->mailer->lastText, $m), 'Mail powinien zawierac link z tokenem.');
        return $m[1];
    }

    public function testRequestSendsLinkAndStoresOnlyHash(): void
    {
        $acc = $this->account();
        $before = $this->mailer->sent;

        $this->reset->request($acc['email'], '10.1.1.' . random_int(1, 250));

        $this->assertSame($before + 1, $this->mailer->sent);
        $token = $this->tokenFromMail();
        $this->assertStringContainsString('/nowe-haslo?token=', $this->mailer->lastText);
        $stored = Database::pdo()->query("SELECT token_hash FROM password_resets WHERE user_id = {$acc['id']}")->fetchColumn();
        $this->assertSame(hash('sha256', $token), $stored);
        $this->assertNotSame($token, $stored);
    }

    public function testUnknownEmailLooksTheSameAndSendsNothing(): void
    {
        $before = $this->mailer->sent;

        $this->reset->request("nikt{$this->suffix}@example.com", '10.1.2.' . random_int(1, 250));

        $this->assertSame($before, $this->mailer->sent);
    }

    public function testInvalidEmailFormatIsRejected(): void
    {
        $this->expectException(ApiException::class);
        $this->reset->request('to-nie-jest-mail', '10.1.3.' . random_int(1, 250));
    }

    public function testPerAccountLimitIsSilent(): void
    {
        $acc = $this->account();
        $ip = '10.1.4.' . random_int(1, 250);
        for ($i = 0; $i < 3; $i++) {
            $this->reset->request($acc['email'], $ip);
        }
        $sent = $this->mailer->sent;

        $this->reset->request($acc['email'], $ip);

        $this->assertSame($sent, $this->mailer->sent, 'Czwarta prosba w godzine nie wysyla maila, ale tez nie zwraca bledu.');
    }

    public function testResetSetsPasswordEndsSessionsAndTokenIsSingleUse(): void
    {
        $acc = $this->account();
        $pdo = Database::pdo();
        $pdo->prepare("INSERT INTO sessions (id, user_id, data) VALUES (?, ?, '')")->execute(['rs' . $this->suffix, $acc['id']]);
        $this->reset->request($acc['email'], '10.1.5.' . random_int(1, 250));
        $token = $this->tokenFromMail();
        $ip = '10.1.6.' . random_int(1, 250);

        $this->reset->reset($token, 'zupelnie-nowe-haslo', 'zupelnie-nowe-haslo', $ip);

        $this->auth->login($acc['email'], 'zupelnie-nowe-haslo', '10.1.7.' . random_int(1, 250));
        try {
            $this->auth->login($acc['email'], $acc['password'], '10.1.8.' . random_int(1, 250));
            $this->fail('Stare haslo nie powinno juz dzialac.');
        } catch (ApiException $e) {
            $this->assertSame('invalid_credentials', $e->errorCode);
        }
        $this->assertSame(0, (int) $pdo->query("SELECT COUNT(*) FROM sessions WHERE user_id = {$acc['id']}")->fetchColumn());
        $this->assertNotNull($pdo->query("SELECT email_verified_at FROM users WHERE id = {$acc['id']}")->fetchColumn());

        try {
            $this->reset->reset($token, 'jeszcze-inne-haslo', 'jeszcze-inne-haslo', $ip);
            $this->fail('Token mozna uzyc tylko raz.');
        } catch (ApiException $e) {
            $this->assertSame('invalid_token', $e->errorCode);
        }
    }

    public function testExpiredTokenIsRejected(): void
    {
        $acc = $this->account();
        $this->reset->request($acc['email'], '10.1.9.' . random_int(1, 250));
        $token = $this->tokenFromMail();
        Database::pdo()->prepare('UPDATE password_resets SET expires_at = UTC_TIMESTAMP() - INTERVAL 1 MINUTE WHERE user_id = ?')->execute([$acc['id']]);

        try {
            $this->reset->reset($token, 'zupelnie-nowe-haslo', 'zupelnie-nowe-haslo', '10.1.10.' . random_int(1, 250));
            $this->fail('Wygasly token powinien byc odrzucony.');
        } catch (ApiException $e) {
            $this->assertSame('invalid_token', $e->errorCode);
        }
    }

    public function testWeakPasswordDoesNotConsumeToken(): void
    {
        $acc = $this->account();
        $this->reset->request($acc['email'], '10.1.11.' . random_int(1, 250));
        $token = $this->tokenFromMail();
        $ip = '10.1.12.' . random_int(1, 250);

        try {
            $this->reset->reset($token, 'krotkie', 'krotkie', $ip);
            $this->fail('Za krotkie haslo powinno byc odrzucone.');
        } catch (ApiException $e) {
            $this->assertArrayHasKey('password', $e->fields);
        }

        $this->reset->reset($token, 'poprawne-dlugie-haslo', 'poprawne-dlugie-haslo', $ip);
        $this->auth->login($acc['email'], 'poprawne-dlugie-haslo', '10.1.13.' . random_int(1, 250));
        $this->assertTrue(true);
    }

    public function testMalformedTokenIsRejected(): void
    {
        try {
            $this->reset->reset('nie-token', 'zupelnie-nowe-haslo', 'zupelnie-nowe-haslo', '10.1.14.' . random_int(1, 250));
            $this->fail('Zly format tokenu powinien byc odrzucony.');
        } catch (ApiException $e) {
            $this->assertSame('invalid_token', $e->errorCode);
        }
    }
}
