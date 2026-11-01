<?php
declare(strict_types=1);

namespace Turysta\Auth;

use PDO;
use Turysta\Http\ApiException;
use Turysta\Mailer;
use Turysta\MailTemplate;

/** Reset hasla: link z jednorazowym tokenem wysylany na adres konta, wazny godzine. */
final class PasswordResetService
{
    public const TOKEN_TTL_HOURS = 1;

    public function __construct(
        private PDO $pdo,
        private Mailer $mailer,
        private RateLimiter $limiter
    ) {
    }

    /**
     * Zawsze konczy sie tak samo, niezaleznie od tego, czy konto istnieje (brak wyciekania, kto ma konto).
     * Bledem konczy sie tylko niepoprawny format adresu i przekroczony limit na adres IP.
     */
    public function request(string $email, string $ip): void
    {
        $email = strtolower(trim($email));
        $errors = Validator::email($email);
        if ($errors) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', $errors);
        }
        if ($this->limiter->hit('forgot:ip:' . $ip, 10, 3600)) {
            throw new ApiException(429, 'rate_limited', 'Za dużo prób. Spróbuj ponownie za godzinę.');
        }
        // Limit na adres e-mail: nadmiarowe prosby ciche, zeby nie zalewac cudzej skrzynki.
        if ($this->limiter->hit('forgot:acct:' . $email, 3, 3600)) {
            return;
        }

        $stmt = $this->pdo->prepare('SELECT id, character_name FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $this->pdo->beginTransaction();
        $this->pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$user['id']]);
        $this->pdo->prepare(
            'INSERT INTO password_resets (token_hash, user_id, expires_at) VALUES (?, ?, UTC_TIMESTAMP() + INTERVAL ' . self::TOKEN_TTL_HOURS . ' HOUR)'
        )->execute([hash('sha256', $token), $user['id']]);
        $this->pdo->commit();

        $base = rtrim(getenv('APP_URL') ?: 'http://localhost', '/');
        $mail = MailTemplate::passwordReset((string) $user['character_name'], "$base/nowe-haslo?token=$token");
        $this->mailer->send($email, 'Ustaw nowe hasło w grze Dwa Dukaty', $mail['text'], $mail['html']);
    }

    /**
     * Ustawia nowe haslo po tokenie z maila. Konczy wszystkie sesje gracza, a skoro mail dotarl, potwierdza tez adres.
     */
    public function reset(string $token, string $password, string $confirm, string $ip): void
    {
        if ($this->limiter->hit('reset:ip:' . $ip, 20, 900)) {
            throw new ApiException(429, 'rate_limited', 'Za dużo prób. Spróbuj za kilkanaście minut.');
        }
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            throw new ApiException(400, 'invalid_token', 'Link jest nieprawidłowy lub wygasł.');
        }
        // Haslo sprawdzamy przed zuzyciem tokenu, zeby po literowce mozna bylo sprobowac jeszcze raz.
        $errors = Validator::newPassword($password, $confirm);
        if ($errors) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', $errors);
        }

        $stmt = $this->pdo->prepare(
            'SELECT r.user_id, u.email FROM password_resets r JOIN users u ON u.id = r.user_id
             WHERE r.token_hash = ? AND r.expires_at > UTC_TIMESTAMP()'
        );
        $stmt->execute([hash('sha256', $token)]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new ApiException(400, 'invalid_token', 'Link jest nieprawidłowy lub wygasł.');
        }

        $this->pdo->beginTransaction();
        $this->pdo->prepare('UPDATE users SET password_hash = ?, email_verified_at = COALESCE(email_verified_at, UTC_TIMESTAMP()) WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_ARGON2ID), $row['user_id']]);
        $this->pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$row['user_id']]);
        $this->pdo->prepare('DELETE FROM sessions WHERE user_id = ?')->execute([$row['user_id']]);
        $this->pdo->commit();

        // Po resecie gracz nie powinien zostac zablokowany limitem nieudanych logowan.
        $this->limiter->clear('login:acct:' . strtolower((string) $row['email']));
    }
}
