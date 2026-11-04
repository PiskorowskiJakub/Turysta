<?php
declare(strict_types=1);

namespace Turysta\Auth;

use PDO;
use Turysta\Game\Clock;
use Turysta\Http\ApiException;
use Turysta\Mailer;
use Turysta\Squad\NameFilter;
use Turysta\MailTemplate;

/** Edycja konta przez zalogowanego gracza: plec i awatar, e-mail, haslo. Nazwe postaci mozna zmienic bardzo rzadko. */
final class AccountService
{
    public const GENDER_COOLDOWN_DAYS = 30;
    /** Nazwe postaci mozna zmienic raz na pol roku. */
    public const NAME_COOLDOWN_DAYS = 180;
    /** Przez tyle dni po zmianie nikt inny nie moze wziac porzuconej nazwy (ochrona przed podszywaniem sie). */
    public const NAME_RESERVE_DAYS = 90;

    public function __construct(
        private PDO $pdo,
        private Mailer $mailer,
        private RateLimiter $limiter
    ) {
    }

    /** Kiedy (UTC, 'Y-m-d H:i:s') plec mozna znowu zmienic; null = mozna teraz. */
    public static function genderChangeAvailableAt(?string $changedAt): ?string
    {
        if ($changedAt === null) {
            return null;
        }
        return Clock::parse($changedAt)->modify('+' . self::GENDER_COOLDOWN_DAYS . ' days')->format('Y-m-d H:i:s');
    }

    /** Kiedy (UTC, 'Y-m-d H:i:s') nazwe postaci mozna znowu zmienic; null = mozna teraz. */
    public static function nameChangeAvailableAt(?string $changedAt): ?string
    {
        if ($changedAt === null) {
            return null;
        }
        return Clock::parse($changedAt)->modify('+' . self::NAME_COOLDOWN_DAYS . ' days')->format('Y-m-d H:i:s');
    }

    /**
     * Zmiana nazwy postaci (wymaga hasla). Nowa nazwa musi byc wolna, nie moze byc zakazana ani niedawno porzucona przez kogos innego.
     *
     * @param array<string,mixed> $user wiersz users
     */
    public function changeName(array $user, string $currentPassword, string $newName, string $ip): void
    {
        $this->requirePassword($user, $currentPassword, $ip);
        $name = trim($newName);
        $errors = Validator::characterName($name);
        if (!$errors && $name === (string) $user['character_name']) {
            $errors['character_name'] = 'To jest już Twoja nazwa postaci.';
        }
        if ($errors) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', $errors);
        }

        $next = self::nameChangeAvailableAt($user['name_changed_at'] ?? null);
        if ($next !== null && Clock::parse($next) > Clock::now($this->pdo)) {
            $msg = 'Nazwę postaci możesz zmienić raz na ' . self::NAME_COOLDOWN_DAYS . ' dni. Następna zmiana będzie możliwa '
                . Clock::parse($next)->setTimezone(new \DateTimeZone(Clock::GAME_TZ))->format('d.m.Y') . '.';
            throw new ApiException(422, 'name_cooldown', $msg, ['character_name' => $msg]);
        }

        if (NameFilter::isBanned($this->pdo, $name)) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['character_name' => 'Ta nazwa jest niedozwolona.']);
        }
        $taken = $this->pdo->prepare(
            'SELECT 1 FROM users WHERE character_name = ? AND id <> ?
             UNION SELECT 1 FROM user_name_changes WHERE old_name = ? AND user_id <> ? AND changed_at > UTC_TIMESTAMP() - INTERVAL ' . self::NAME_RESERVE_DAYS . ' DAY'
        );
        $taken->execute([$name, $user['id'], $name, $user['id']]);
        if ($taken->fetchColumn()) {
            throw new ApiException(409, 'already_exists', 'Ta nazwa postaci jest już zajęta.', ['character_name' => 'Ta nazwa postaci jest już zajęta.']);
        }

        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('INSERT INTO user_name_changes (user_id, old_name, new_name) VALUES (?, ?, ?)')
                ->execute([$user['id'], $user['character_name'], $name]);
            $this->pdo->prepare('UPDATE users SET character_name = ?, name_changed_at = UTC_TIMESTAMP() WHERE id = ?')
                ->execute([$name, $user['id']]);
            $this->pdo->commit();
        } catch (\PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($e->getCode() === '23000') {
                throw new ApiException(409, 'already_exists', 'Ta nazwa postaci jest już zajęta.', ['character_name' => 'Ta nazwa postaci jest już zajęta.']);
            }
            throw $e;
        }
    }

    /**
     * Ustawienia prywatnosci: czy pokazywac aktywnosc znajomym (i ich aktywnosc u siebie) oraz czy pokazywac tytul roli w oddziale.
     * Kazde pole jest opcjonalne, ale trzeba podac przynajmniej jedno.
     *
     * @param array<string,mixed> $user wiersz users
     * @param array<string,mixed> $in share_activity i/lub show_squad_title
     */
    public function setPrivacy(array $user, array $in): void
    {
        $errors = [];
        $sets = [];
        $params = [];
        foreach (['share_activity', 'show_squad_title'] as $field) {
            if (!array_key_exists($field, $in)) {
                continue;
            }
            if (!is_bool($in[$field])) {
                $errors[$field] = 'Wybierz tak lub nie.';
                continue;
            }
            $sets[] = "$field = ?";
            $params[] = $in[$field] ? 1 : 0;
        }
        if (!$sets && !$errors) {
            $errors['share_activity'] = 'Wybierz tak lub nie.';
        }
        if ($errors) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', $errors);
        }
        $params[] = (int) $user['id'];
        $this->pdo->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);
    }

    /** @param array<string,mixed> $user wiersz users */
    public function updateProfile(array $user, mixed $gender, mixed $avatarId): void
    {
        $errors = [];
        if (!is_string($gender) || !in_array($gender, ['f', 'm', 'x'], true)) {
            $errors['gender'] = 'Wybierz płeć postaci.';
        }
        $avatarGender = null;
        if (is_int($avatarId)) {
            $stmt = $this->pdo->prepare('SELECT gender FROM avatars WHERE id = ?');
            $stmt->execute([$avatarId]);
            $found = $stmt->fetchColumn();
            $avatarGender = $found === false ? null : (string) $found;
        }
        if ($avatarGender === null) {
            $errors['avatar_id'] = 'Wybierz awatar z listy.';
        } elseif (is_string($gender) && !isset($errors['gender']) && $avatarGender !== $gender) {
            $errors['avatar_id'] = 'Ten awatar nie pasuje do wybranej płci.';
        }
        if ($errors) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', $errors);
        }

        $genderChanged = $user['gender'] !== null && $user['gender'] !== $gender;
        if ($genderChanged) {
            $next = self::genderChangeAvailableAt($user['gender_changed_at'] ?? null);
            if ($next !== null && Clock::parse($next) > Clock::now($this->pdo)) {
                throw new ApiException(
                    422,
                    'gender_cooldown',
                    'Płeć możesz zmienić raz na ' . self::GENDER_COOLDOWN_DAYS . ' dni.',
                    ['gender' => 'Płeć możesz zmienić raz na ' . self::GENDER_COOLDOWN_DAYS . ' dni. Następna zmiana będzie możliwa ' . Clock::parse($next)->setTimezone(new \DateTimeZone(Clock::GAME_TZ))->format('d.m.Y') . '.']
                );
            }
        }

        if ($genderChanged) {
            $this->pdo->prepare('UPDATE users SET gender = ?, gender_changed_at = UTC_TIMESTAMP(), avatar_id = ? WHERE id = ?')
                ->execute([$gender, $avatarId, $user['id']]);
        } else {
            $this->pdo->prepare('UPDATE users SET gender = ?, avatar_id = ? WHERE id = ?')
                ->execute([$gender, $avatarId, $user['id']]);
        }
    }

    /** @param array<string,mixed> $user wiersz users */
    public function changeEmail(array $user, string $currentPassword, string $newEmail, string $ip): void
    {
        $this->requirePassword($user, $currentPassword, $ip);
        $email = strtolower(trim($newEmail));
        $errors = Validator::email($email);
        if ($errors) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', $errors);
        }
        if ($email === strtolower((string) $user['email'])) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['email' => 'To jest już Twój adres e-mail.']);
        }
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE email = ? AND id <> ?');
        $stmt->execute([$email, $user['id']]);
        if ($stmt->fetchColumn()) {
            throw new ApiException(409, 'already_exists', 'Ten adres e-mail jest już zajęty.', ['email' => 'Ten adres e-mail jest już zajęty.']);
        }

        $token = bin2hex(random_bytes(32));
        $this->pdo->beginTransaction();
        $this->pdo->prepare('UPDATE users SET email = ?, email_verified_at = NULL, email_unverified_since = UTC_TIMESTAMP() WHERE id = ?')->execute([$email, $user['id']]);
        $this->pdo->prepare('DELETE FROM email_verifications WHERE user_id = ?')->execute([$user['id']]);
        $this->pdo->prepare(
            'INSERT INTO email_verifications (token_hash, user_id, expires_at) VALUES (?, ?, UTC_TIMESTAMP() + INTERVAL 2 DAY)'
        )->execute([hash('sha256', $token), $user['id']]);
        $this->pdo->commit();

        $base = rtrim(getenv('APP_URL') ?: 'http://localhost', '/');
        $mail = MailTemplate::verification((string) $user['character_name'], "$base/potwierdz-email?token=$token", true);
        $this->mailer->send($email, 'Potwierdź nowy adres e-mail w grze Dwa Dukaty', $mail['text'], $mail['html']);
    }

    /**
     * Zmienia haslo i konczy wszystkie pozostale sesje tego gracza (bez biezacej).
     *
     * @param array<string,mixed> $user wiersz users
     */
    public function changePassword(array $user, string $currentPassword, string $password, string $confirm, string $ip, ?string $keepSessionId): void
    {
        $this->requirePassword($user, $currentPassword, $ip);
        $errors = Validator::newPassword($password, $confirm);
        if ($errors) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', $errors);
        }
        if (hash_equals((string) $currentPassword, $password)) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['password' => 'Nowe hasło musi się różnić od obecnego.']);
        }

        $this->pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_ARGON2ID), $user['id']]);
        $this->pdo->prepare('DELETE FROM sessions WHERE user_id = ? AND id <> ?')
            ->execute([$user['id'], $keepSessionId ?? '']);
    }

    /**
     * Wysyla ponownie link potwierdzajacy adres e-mail (poprzednie linki przestaja dzialac).
     *
     * @param array<string,mixed> $user wiersz users
     */
    public function resendVerification(array $user, string $ip): void
    {
        if ($user['email_verified_at'] !== null) {
            throw new ApiException(409, 'already_verified', 'Ten adres e-mail jest już potwierdzony.');
        }
        if ($this->limiter->hit('resend:' . $user['id'], 3, 3600) || $this->limiter->hit('resend:ip:' . $ip, 10, 3600)) {
            throw new ApiException(429, 'rate_limited', 'Za dużo próśb o link. Spróbuj za godzinę.');
        }

        $token = bin2hex(random_bytes(32));
        $this->pdo->beginTransaction();
        $this->pdo->prepare('DELETE FROM email_verifications WHERE user_id = ?')->execute([$user['id']]);
        $this->pdo->prepare(
            'INSERT INTO email_verifications (token_hash, user_id, expires_at) VALUES (?, ?, UTC_TIMESTAMP() + INTERVAL 2 DAY)'
        )->execute([hash('sha256', $token), $user['id']]);
        $this->pdo->commit();

        $base = rtrim(getenv('APP_URL') ?: 'http://localhost', '/');
        $mail = MailTemplate::verification((string) $user['character_name'], "$base/potwierdz-email?token=$token", false);
        $sent = $this->mailer->send((string) $user['email'], 'Potwierdź adres e-mail w grze Dwa Dukaty', $mail['text'], $mail['html']);
        if (!$sent) {
            throw new ApiException(503, 'mail_failed', 'Nie udało się wysłać wiadomości. Spróbuj ponownie później.');
        }
    }

    /**
     * Usuwa konto gracza po podaniu hasla. Ostatniego administratora usunac nie mozna.
     *
     * @param array<string,mixed> $user wiersz users
     */
    public function deleteAccount(array $user, string $currentPassword, string $ip): void
    {
        $this->requirePassword($user, $currentPassword, $ip);
        if ($user['role'] === 'admin') {
            $admins = (int) $this->pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
            if ($admins <= 1) {
                throw new ApiException(409, 'last_admin', 'Nie można usunąć ostatniego konta administratora.');
            }
        }
        (new UserDeleter($this->pdo))->delete((int) $user['id']);
    }

    /** @param array<string,mixed> $user */
    private function requirePassword(array $user, string $password, string $ip): void
    {
        $key = 'account:' . $user['id'];
        if ($this->limiter->hit($key, 5, 900) || $this->limiter->hit('account:ip:' . $ip, 30, 900)) {
            throw new ApiException(429, 'rate_limited', 'Za dużo prób. Spróbuj za kilkanaście minut.');
        }
        if (!password_verify($password, (string) $user['password_hash'])) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['current_password' => 'Obecne hasło jest nieprawidłowe.']);
        }
        $this->limiter->clear($key);
    }
}
