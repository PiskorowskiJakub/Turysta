<?php
declare(strict_types=1);

namespace Turysta\Tests;

use PHPUnit\Framework\TestCase;
use Throwable;
use Turysta\Auth\AccountService;
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
            public function send(string $to, string $subject, string $text, ?string $html = null): bool
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

        $this->assertGreaterThan(0, (int) $pdo->query('SELECT COUNT(*) FROM skill_definitions')->fetchColumn());
        $this->assertSame(
            (int) $pdo->query('SELECT COUNT(*) FROM skill_definitions')->fetchColumn(),
            (int) $pdo->query("SELECT COUNT(*) FROM user_skills WHERE user_id = {$user['id']}")->fetchColumn()
        );
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

    /** @return array{0:AccountService,1:array<string,mixed>,2:array<string,string>} */
    private function accountFixture(): array
    {
        $pdo = Database::pdo();
        $d = $this->data();
        $created = $this->auth->register($d, '10.0.6.' . random_int(1, 250));
        $mailer = new class extends Mailer {
            public function send(string $to, string $subject, string $text, ?string $html = null): bool
            {
                return true;
            }
        };
        $pdo->prepare("UPDATE users SET gender = 'm', avatar_id = (SELECT id FROM avatars WHERE gender = 'm' LIMIT 1), tutorial_done_at = UTC_TIMESTAMP() WHERE id = ?")
            ->execute([$created['id']]);
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$created['id']]);
        return [new AccountService($pdo, $mailer, new RateLimiter($pdo)), $stmt->fetch(), $d];
    }

    private function userRow(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function testGenderCanBeChangedOnlyOncePer30Days(): void
    {
        [$account, $user] = $this->accountFixture();
        $pdo = Database::pdo();
        $female = (int) $pdo->query("SELECT id FROM avatars WHERE gender = 'f' LIMIT 1")->fetchColumn();
        $male2 = (int) $pdo->query("SELECT id FROM avatars WHERE gender = 'm' LIMIT 1")->fetchColumn();

        // Awatar tej samej plci mozna zmieniac bez ograniczen i nie uruchamia blokady plci.
        $account->updateProfile($user, 'm', $male2);
        $this->assertNull($this->userRow((int) $user['id'])['gender_changed_at']);

        // Pierwsza zmiana plci przechodzi, kolejna jest zablokowana na 30 dni.
        $account->updateProfile($user, 'f', $female);
        $after = $this->userRow((int) $user['id']);
        $this->assertSame('f', $after['gender']);
        $this->assertNotNull($after['gender_changed_at']);

        try {
            $account->updateProfile($after, 'm', $male2);
            $this->fail('Druga zmiana plci powinna byc zablokowana.');
        } catch (ApiException $e) {
            $this->assertSame('gender_cooldown', $e->errorCode);
        }

        // Po 30 dniach zmiana znowu jest mozliwa.
        $pdo->prepare('UPDATE users SET gender_changed_at = UTC_TIMESTAMP() - INTERVAL 31 DAY WHERE id = ?')->execute([$user['id']]);
        $account->updateProfile($this->userRow((int) $user['id']), 'm', $male2);
        $this->assertSame('m', $this->userRow((int) $user['id'])['gender']);
    }

    public function testAvatarMustMatchGender(): void
    {
        [$account, $user] = $this->accountFixture();
        $female = (int) Database::pdo()->query("SELECT id FROM avatars WHERE gender = 'f' LIMIT 1")->fetchColumn();

        $this->expectException(ApiException::class);
        $account->updateProfile($user, 'm', $female);
    }

    public function testChangeEmailRequiresPasswordAndResetsVerification(): void
    {
        [$account, $user, $d] = $this->accountFixture();
        $ip = '10.0.7.' . random_int(1, 250);
        $new = "nowy{$this->suffix}@example.com";

        try {
            $account->changeEmail($user, 'zle-haslo-zle-haslo', $new, $ip);
            $this->fail('Zle haslo powinno zablokowac zmiane.');
        } catch (ApiException $e) {
            $this->assertArrayHasKey('current_password', $e->fields);
        }

        $account->changeEmail($user, $d['password'], strtoupper($new), $ip);
        $row = $this->userRow((int) $user['id']);
        $this->assertSame($new, $row['email']);
        $this->assertNull($row['email_verified_at']);
    }

    public function testChangePasswordWorksAndEndsOtherSessions(): void
    {
        [$account, $user, $d] = $this->accountFixture();
        $pdo = Database::pdo();
        $ip = '10.0.8.' . random_int(1, 250);
        $pdo->prepare("INSERT INTO sessions (id, user_id, data) VALUES (?, ?, '')")->execute(['keep' . $this->suffix, $user['id']]);
        $pdo->prepare("INSERT INTO sessions (id, user_id, data) VALUES (?, ?, '')")->execute(['drop' . $this->suffix, $user['id']]);

        $account->changePassword($user, $d['password'], 'zupelnie-nowe-haslo', 'zupelnie-nowe-haslo', $ip, 'keep' . $this->suffix);

        $this->auth->login($d['email'], 'zupelnie-nowe-haslo', '10.0.9.' . random_int(1, 250));
        $this->assertSame(1, (int) $pdo->query("SELECT COUNT(*) FROM sessions WHERE id = 'keep{$this->suffix}'")->fetchColumn());
        $this->assertSame(0, (int) $pdo->query("SELECT COUNT(*) FROM sessions WHERE id = 'drop{$this->suffix}'")->fetchColumn());
    }

    public function testResendVerificationReplacesTokenAndIsLimited(): void
    {
        [$account, $user] = $this->accountFixture();
        $pdo = Database::pdo();
        $ip = '10.0.10.' . random_int(1, 250);
        $before = $pdo->query("SELECT token_hash FROM email_verifications WHERE user_id = {$user['id']}")->fetchColumn();

        $account->resendVerification($user, $ip);
        $after = $pdo->query("SELECT token_hash FROM email_verifications WHERE user_id = {$user['id']}")->fetchColumn();
        $this->assertNotSame($before, $after);
        $this->assertSame(1, (int) $pdo->query("SELECT COUNT(*) FROM email_verifications WHERE user_id = {$user['id']}")->fetchColumn());

        $account->resendVerification($user, $ip);
        $account->resendVerification($user, $ip);
        try {
            $account->resendVerification($user, $ip);
            $this->fail('Czwarta prosba w godzine powinna byc zablokowana.');
        } catch (ApiException $e) {
            $this->assertSame(429, $e->status);
        }
    }

    public function testResendVerificationRejectedWhenAlreadyVerified(): void
    {
        [$account, $user] = $this->accountFixture();
        Database::pdo()->prepare('UPDATE users SET email_verified_at = UTC_TIMESTAMP() WHERE id = ?')->execute([$user['id']]);
        $user = $this->userRow((int) $user['id']);

        try {
            $account->resendVerification($user, '10.0.11.' . random_int(1, 250));
            $this->fail('Potwierdzone konto nie potrzebuje linku.');
        } catch (ApiException $e) {
            $this->assertSame('already_verified', $e->errorCode);
        }
    }

    public function testDeleteAccountNeedsPasswordAndRemovesEverything(): void
    {
        [$account, $user, $d] = $this->accountFixture();
        $pdo = Database::pdo();
        $id = (int) $user['id'];
        $ip = '10.0.12.' . random_int(1, 250);

        try {
            $account->deleteAccount($user, 'zle-haslo-zle-haslo', $ip);
            $this->fail('Zle haslo powinno zablokowac usuniecie.');
        } catch (ApiException $e) {
            $this->assertArrayHasKey('current_password', $e->fields);
        }
        $this->assertSame(1, (int) $pdo->query("SELECT COUNT(*) FROM users WHERE id = $id")->fetchColumn());

        $account->deleteAccount($user, $d['password'], $ip);
        foreach (['users' => 'id', 'wallets' => 'user_id', 'user_skills' => 'user_id', 'referral_codes' => 'user_id', 'email_verifications' => 'user_id'] as $table => $col) {
            $this->assertSame(0, (int) $pdo->query("SELECT COUNT(*) FROM $table WHERE $col = $id")->fetchColumn(), $table);
        }

        // Ten sam adres mozna zarejestrowac ponownie.
        $again = $this->auth->register($d, '10.0.13.' . random_int(1, 250));
        $this->assertNotSame($id, (int) $again['id']);
    }

    public function testLastAdminCannotDeleteAccount(): void
    {
        [$account, $user, $d] = $this->accountFixture();
        $pdo = Database::pdo();
        $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([$user['id']]);
        $user = $this->userRow((int) $user['id']);
        $others = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND id <> {$user['id']}")->fetchColumn();
        if ($others > 0) {
            $this->markTestSkipped('W bazie sa inni administratorzy, wiec to konto nie jest ostatnim.');
        }

        try {
            $account->deleteAccount($user, $d['password'], '10.0.14.' . random_int(1, 250));
            $this->fail('Ostatni administrator nie moze usunac konta.');
        } catch (ApiException $e) {
            $this->assertSame('last_admin', $e->errorCode);
        }
    }
}
