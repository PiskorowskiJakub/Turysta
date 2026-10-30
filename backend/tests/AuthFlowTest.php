<?php
declare(strict_types=1);

namespace Turysta\Tests;

use PHPUnit\Framework\TestCase;
use Throwable;
use Turysta\Auth\AuthService;
use Turysta\Auth\RateLimiter;
use Turysta\Database;
use Turysta\Http\ApiException;
use Turysta\Mailer;

/** Test integracyjny na prawdziwym MySQL (wymaga zmigrowanej bazy). */
final class AuthFlowTest extends TestCase
{
    private AuthService $auth;
    private string $suffix;

    protected function setUp(): void
    {
        try {
            $pdo = Database::pdo();
            $pdo->query('SELECT 1 FROM users LIMIT 1');
        } catch (Throwable $e) {
            $this->markTestSkipped('Brak bazy z migracjami: ' . $e->getMessage());
        }
        $mailer = new class extends Mailer {
            public function send(string $to, string $subject, string $text): bool
            {
                return true;
            }
        };
        $this->auth = new AuthService($pdo, $mailer, new RateLimiter($pdo));
        $this->suffix = bin2hex(random_bytes(4));
    }

    /** @return array<string,string> */
    private function data(): array
    {
        return [
            'character_name' => 'Test' . $this->suffix,
            'email' => "test{$this->suffix}@example.com",
            'password' => 'bardzo-dlugie-haslo',
            'password_confirm' => 'bardzo-dlugie-haslo',
        ];
    }

    public function testRegisterCreatesWalletSkillsAndReferralCode(): void
    {
        $user = $this->auth->register($this->data(), '10.0.0.' . random_int(1, 250));
        $pdo = Database::pdo();

        $this->assertSame(3, (int) $pdo->query("SELECT COUNT(*) FROM user_skills WHERE user_id = {$user['id']}")->fetchColumn());
        $this->assertSame(1, (int) $pdo->query("SELECT COUNT(*) FROM wallets WHERE user_id = {$user['id']}")->fetchColumn());
        $this->assertSame(1, (int) $pdo->query("SELECT COUNT(*) FROM referral_codes WHERE user_id = {$user['id']}")->fetchColumn());
        $this->assertStringStartsWith('$argon2id$', (string) $pdo->query("SELECT password_hash FROM users WHERE id = {$user['id']}")->fetchColumn());
    }

    public function testDuplicateEmailIsRejectedCaseInsensitively(): void
    {
        $this->auth->register($this->data(), '10.0.1.' . random_int(1, 250));
        $second = $this->data();
        $second['character_name'] = 'Inny' . $this->suffix;
        $second['email'] = strtoupper($second['email']);

        $this->expectException(ApiException::class);
        $this->auth->register($second, '10.0.2.' . random_int(1, 250));
    }

    public function testLoginSucceedsAndFailsWithWrongPassword(): void
    {
        $d = $this->data();
        $this->auth->register($d, '10.0.3.' . random_int(1, 250));

        $user = $this->auth->login($d['email'], $d['password'], '10.0.4.' . random_int(1, 250));
        $this->assertSame($d['character_name'], $user['character_name']);

        $this->expectException(ApiException::class);
        $this->auth->login($d['email'], 'zle-haslo-zle-haslo', '10.0.5.' . random_int(1, 250));
    }
}
