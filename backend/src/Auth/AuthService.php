<?php
declare(strict_types=1);

namespace Turysta\Auth;

use PDO;
use PDOException;
use Turysta\Http\ApiException;
use Turysta\Mailer;

final class AuthService
{
    private const STARTING_COINS_CENTS = 200;

    private const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function __construct(
        private PDO $pdo,
        private Mailer $mailer,
        private RateLimiter $limiter
    ) {
    }

    /**
     * @param array<string,mixed> $in
     * @return array<string,mixed> utworzony uzytkownik (id, email, character_name)
     */
    public function register(array $in, string $ip): array
    {
        if ($this->limiter->hit('register:ip:' . $ip, 10, 3600)) {
            throw new ApiException(429, 'rate_limited', 'Za dużo prób rejestracji. Spróbuj później.');
        }

        $name = trim((string) ($in['character_name'] ?? ''));
        $email = strtolower(trim((string) ($in['email'] ?? '')));
        $password = (string) ($in['password'] ?? '');
        $confirm = (string) ($in['password_confirm'] ?? '');
        $referral = strtoupper(trim((string) ($in['referral_code'] ?? '')));

        $errors = Validator::registration($name, $email, $password, $confirm);
        if ($errors) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', $errors);
        }

        $referrerId = null;
        if ($referral !== '') {
            $stmt = $this->pdo->prepare('SELECT user_id FROM referral_codes WHERE code = ?');
            $stmt->execute([$referral]);
            $referrerId = $stmt->fetchColumn();
            if ($referrerId === false) {
                throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['referral_code' => 'Nieprawidłowy kod polecający.']);
            }
        }

        $taken = [];
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetchColumn()) {
            $taken['email'] = 'Ten adres e-mail jest już zajęty.';
        }
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE character_name = ?');
        $stmt->execute([$name]);
        if ($stmt->fetchColumn()) {
            $taken['character_name'] = 'Ta nazwa postaci jest już zajęta.';
        }
        if ($taken) {
            throw new ApiException(409, 'already_exists', 'Konto o takich danych już istnieje.', $taken);
        }

        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $token = bin2hex(random_bytes(32));

        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('INSERT INTO users (email, password_hash, character_name) VALUES (?, ?, ?)')
                ->execute([$email, $hash, $name]);
            $userId = (int) $this->pdo->lastInsertId();

            $this->pdo->prepare('INSERT INTO wallets (user_id) VALUES (?)')->execute([$userId]);
            // Startowe 2 monety wystarcza na pierwsze kilkadziesiat poziomow, zanim gracz wezmie prace.
            \Turysta\Game\Wallet::apply($this->pdo, $userId, 'coins', self::STARTING_COINS_CENTS, 'starting_bonus', 'user', $userId);
            $this->pdo->prepare(
                'INSERT INTO user_skills (user_id, skill_id, level) SELECT ?, id, 0 FROM skill_definitions'
            )->execute([$userId]);
            $this->pdo->prepare(
                'INSERT INTO player_progress (user_id, world_id, chapter_id)
                 SELECT ?, w.id, c.id FROM worlds w JOIN chapters c ON c.world_id = w.id
                 ORDER BY w.ordinal, c.ordinal LIMIT 1'
            )->execute([$userId]);

            $this->createReferralCode($userId);
            if ($referrerId !== null) {
                $this->pdo->prepare('INSERT INTO referrals (referrer_id, referred_id) VALUES (?, ?)')
                    ->execute([(int) $referrerId, $userId]);
            }

            $this->pdo->prepare(
                'INSERT INTO email_verifications (token_hash, user_id, expires_at)
                 VALUES (?, ?, UTC_TIMESTAMP() + INTERVAL 2 DAY)'
            )->execute([hash('sha256', $token), $userId]);

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            if ($e->getCode() === '23000') {
                throw new ApiException(409, 'already_exists', 'Konto o takich danych już istnieje.');
            }
            throw $e;
        }

        $base = rtrim(getenv('APP_URL') ?: 'http://localhost', '/');
        $this->mailer->send(
            $email,
            'Potwierdź adres e-mail w grze Turysta',
            "Cześć $name!\n\nPotwierdź swój adres e-mail, klikając link:\n$base/api/v1/auth/verify-email?token=$token\n\nLink działa przez 2 dni."
        );

        return ['id' => $userId, 'email' => $email, 'character_name' => $name];
    }

    /** @return array<string,mixed> wiersz uzytkownika */
    public function login(string $email, string $password, string $ip): array
    {
        $email = strtolower(trim($email));
        $accountKey = 'login:acct:' . $email;
        if ($this->limiter->hit('login:ip:' . $ip, 30, 900) || $this->isBlocked($accountKey, 5, 900)) {
            throw new ApiException(429, 'rate_limited', 'Za dużo prób logowania. Spróbuj za kilkanaście minut.');
        }

        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Staly koszt czasowy: dla nieistniejacego konta tez liczymy skrot.
        $hash = $user['password_hash'] ?? password_hash('dummy-password', PASSWORD_ARGON2ID);
        $ok = password_verify($password, $hash) && $user !== false;

        if (!$ok) {
            $this->limiter->hit($accountKey, 5, 900);
            throw new ApiException(401, 'invalid_credentials', 'Nieprawidłowy e-mail lub hasło.');
        }
        if ($user['status'] === 'banned') {
            throw new ApiException(403, 'banned', 'To konto zostało zablokowane.');
        }

        $this->limiter->clear($accountKey);
        if (password_needs_rehash($user['password_hash'], PASSWORD_ARGON2ID)) {
            $this->pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_ARGON2ID), $user['id']]);
        }
        $this->pdo->prepare('UPDATE users SET last_login_at = UTC_TIMESTAMP() WHERE id = ?')->execute([$user['id']]);
        return $user;
    }

    public function recordLogin(int $userId, string $ip, string $userAgent): int
    {
        $this->pdo->prepare('INSERT INTO login_events (user_id, ip, user_agent) VALUES (?, ?, ?)')
            ->execute([$userId, $ip, $userAgent]);
        return (int) $this->pdo->lastInsertId();
    }

    public function recordLogout(int $loginEventId): void
    {
        $this->pdo->prepare('UPDATE login_events SET logged_out_at = UTC_TIMESTAMP() WHERE id = ? AND logged_out_at IS NULL')
            ->execute([$loginEventId]);
    }

    public function verifyEmail(string $token): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT user_id FROM email_verifications WHERE token_hash = ? AND expires_at > UTC_TIMESTAMP()'
        );
        $stmt->execute([hash('sha256', $token)]);
        $userId = $stmt->fetchColumn();
        if ($userId === false) {
            throw new ApiException(400, 'invalid_token', 'Link jest nieprawidłowy lub wygasł.');
        }
        $this->pdo->beginTransaction();
        $this->pdo->prepare('UPDATE users SET email_verified_at = COALESCE(email_verified_at, UTC_TIMESTAMP()) WHERE id = ?')
            ->execute([(int) $userId]);
        $this->pdo->prepare('DELETE FROM email_verifications WHERE user_id = ?')->execute([(int) $userId]);
        $this->pdo->commit();
    }

    private function isBlocked(string $key, int $max, int $window): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT hits FROM rate_limits WHERE bucket_key = ? AND window_start >= UTC_TIMESTAMP() - INTERVAL ? SECOND'
        );
        $stmt->execute([hash('sha256', $key), $window]);
        return (int) $stmt->fetchColumn() >= $max;
    }

    private function createReferralCode(int $userId): void
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }
            $stmt = $this->pdo->prepare('INSERT IGNORE INTO referral_codes (user_id, code) VALUES (?, ?)');
            $stmt->execute([$userId, $code]);
            if ($stmt->rowCount() === 1) {
                return;
            }
        }
        throw new \RuntimeException('Nie udalo sie wygenerowac kodu polecajacego.');
    }
}
