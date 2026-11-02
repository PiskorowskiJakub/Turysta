<?php
declare(strict_types=1);

namespace Turysta\Admin;

use DateTimeZone;
use PDO;
use Throwable;
use Turysta\Auth\Validator;
use Turysta\Game\Clock;
use Turysta\Game\Wallet;
use Turysta\Http\ApiException;

/** Zarzadzanie graczami przez administratora: lista, podglad, edycja, blokady, zawieszenia i korekty portfela. */
final class AdminUserService
{
    public const ROLES = ['player', 'moderator', 'admin'];
    public const STATUSES = ['active', 'suspended', 'banned', 'pending'];
    public const MAX_SUSPEND_DAYS = 365;

    /** Efektywny status konta: banned > suspended (do daty) > pending > active. */
    private const EFF_STATUS = "CASE WHEN u.status = 'banned' THEN 'banned'
        WHEN u.suspended_until IS NOT NULL AND u.suspended_until > UTC_TIMESTAMP() THEN 'suspended'
        WHEN u.status = 'pending' THEN 'pending' ELSE 'active' END";

    private const SORTS = [
        'new' => 'id DESC',
        'old' => 'id ASC',
        'points' => 'points DESC, id ASC',
        'login' => 'last_login_at IS NULL, last_login_at DESC, id DESC',
        // Sortowanie po kliknieciu w kolumne: <kolumna>_asc / <kolumna>_desc (puste wartosci zawsze na koncu).
        'id_asc' => 'id ASC',
        'id_desc' => 'id DESC',
        'name_asc' => 'name ASC, id ASC',
        'name_desc' => 'name DESC, id ASC',
        'email_asc' => 'email ASC, id ASC',
        'email_desc' => 'email DESC, id ASC',
        'role_asc' => 'role ASC, id ASC',
        'role_desc' => 'role DESC, id ASC',
        'status_asc' => 'eff_status ASC, id ASC',
        'status_desc' => 'eff_status DESC, id ASC',
        'world_asc' => 'world IS NULL, world ASC, chapter ASC, id ASC',
        'world_desc' => 'world IS NULL, world DESC, chapter DESC, id ASC',
        'points_asc' => 'points ASC, id ASC',
        'points_desc' => 'points DESC, id ASC',
        'coins_asc' => 'coins ASC, id ASC',
        'coins_desc' => 'coins DESC, id ASC',
        'registered_asc' => 'created_at ASC, id ASC',
        'registered_desc' => 'created_at DESC, id DESC',
        'login_asc' => 'last_login_at IS NULL, last_login_at ASC, id ASC',
        'login_desc' => 'last_login_at IS NULL, last_login_at DESC, id DESC',
    ];

    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<string,mixed> */
    public function list(int $page, string $q, string $status, string $role, string $sort): array
    {
        $eff = self::EFF_STATUS;
        $base = "SELECT u.id, u.character_name AS name, u.email, u.role, u.created_at, u.last_login_at,
                    u.email_verified_at IS NOT NULL AS verified, $eff AS eff_status,
                    COALESCE(w.points, 0) AS points, COALESCE(w.coins_cents, 0) AS coins,
                    wo.ordinal AS world, c.ordinal AS chapter
                 FROM users u
                 LEFT JOIN wallets w ON w.user_id = u.id
                 LEFT JOIN player_progress p ON p.user_id = u.id
                 LEFT JOIN chapters c ON c.id = p.chapter_id
                 LEFT JOIN worlds wo ON wo.id = c.world_id";

        $where = [];
        $params = [];
        $q = trim(mb_substr($q, 0, 60));
        if ($q !== '') {
            $where[] = '(name LIKE ? OR email LIKE ? OR id = ?)';
            $like = Paging::like($q);
            array_push($params, $like, $like, ctype_digit($q) ? (int) $q : 0);
        }
        if (in_array($status, self::STATUSES, true)) {
            $where[] = 'eff_status = ?';
            $params[] = $status;
        }
        if (in_array($role, self::ROLES, true)) {
            $where[] = 'role = ?';
            $params[] = $role;
        }
        $filter = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $order = self::SORTS[$sort] ?? self::SORTS['new'];

        return Paging::run(
            $this->pdo,
            "SELECT COUNT(*) FROM ($base) r$filter",
            "SELECT * FROM ($base) r$filter ORDER BY $order",
            $params,
            $page,
            static fn(array $r): array => [
                'id' => (int) $r['id'],
                'name' => (string) $r['name'],
                'email' => (string) $r['email'],
                'role' => (string) $r['role'],
                'status' => (string) $r['eff_status'],
                'email_verified' => (bool) $r['verified'],
                'registered_at' => Clock::iso((string) $r['created_at']),
                'last_login_at' => Clock::iso($r['last_login_at']),
                'points' => (int) $r['points'],
                'coins_cents' => (int) $r['coins'],
                'world' => $r['world'] !== null ? (int) $r['world'] : null,
                'chapter' => $r['chapter'] !== null ? (int) $r['chapter'] : null,
            ]
        );
    }

    /** @return array<string,mixed> */
    public function detail(int $id): array
    {
        $user = $this->user($id);

        $one = function (string $sql, array $params = []) {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        };

        $stmt = $this->pdo->prepare('SELECT coins_cents, tickets, points FROM wallets WHERE user_id = ?');
        $stmt->execute([$id]);
        $wallet = $stmt->fetch() ?: ['coins_cents' => 0, 'tickets' => 0, 'points' => 0];

        $stmt = $this->pdo->prepare('SELECT last_active_day, active_days, current_streak, longest_streak FROM user_stats WHERE user_id = ?');
        $stmt->execute([$id]);
        $stats = $stmt->fetch() ?: ['last_active_day' => null, 'active_days' => 0, 'current_streak' => 0, 'longest_streak' => 0];

        $stmt = $this->pdo->prepare(
            'SELECT w.ordinal AS wo, w.name AS wn, c.ordinal AS co, c.name AS cn
             FROM player_progress p JOIN chapters c ON c.id = p.chapter_id JOIN worlds w ON w.id = c.world_id WHERE p.user_id = ?'
        );
        $stmt->execute([$id]);
        $progress = $stmt->fetch() ?: null;

        $stmt = $this->pdo->prepare('SELECT name, gender FROM avatars WHERE id = ?');
        $stmt->execute([(int) $user['avatar_id']]);
        $avatar = $stmt->fetch() ?: null;

        $stmt = $this->pdo->prepare('SELECT u.id, u.character_name FROM referrals r JOIN users u ON u.id = r.referrer_id WHERE r.referred_id = ?');
        $stmt->execute([$id]);
        $referrer = $stmt->fetch() ?: null;

        return [
            'id' => (int) $user['id'],
            'name' => (string) $user['character_name'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
            'status' => $this->effectiveStatus($user),
            'status_reason' => $user['status_reason'],
            'suspended_until' => Clock::iso($user['suspended_until']),
            'email_verified' => $user['email_verified_at'] !== null,
            'tutorial_done' => $user['tutorial_done_at'] !== null,
            'gender' => $user['gender'],
            'gender_changed_at' => Clock::iso($user['gender_changed_at'] ?? null),
            'avatar_id' => $user['avatar_id'] !== null ? (int) $user['avatar_id'] : null,
            'avatar_name' => $avatar['name'] ?? null,
            'registered_at' => Clock::iso((string) $user['created_at']),
            'last_login_at' => Clock::iso($user['last_login_at']),
            'wallet' => [
                'coins_cents' => (int) $wallet['coins_cents'],
                'tickets' => (int) $wallet['tickets'],
                'points' => (int) $wallet['points'],
            ],
            'stats' => [
                'last_active_day' => $stats['last_active_day'],
                'active_days' => (int) $stats['active_days'],
                'current_streak' => (int) $stats['current_streak'],
                'longest_streak' => (int) $stats['longest_streak'],
            ],
            'progress' => $progress ? [
                'world_ordinal' => (int) $progress['wo'],
                'world_name' => (string) $progress['wn'],
                'chapter_ordinal' => (int) $progress['co'],
                'chapter_name' => (string) $progress['cn'],
            ] : null,
            'counts' => [
                'logins' => (int) $one('SELECT COUNT(*) FROM login_events WHERE user_id = ?', [$id]),
                'jobs_done' => (int) $one("SELECT COUNT(*) FROM job_runs WHERE user_id = ? AND status = 'done'", [$id]),
                'trainings_done' => (int) $one("SELECT COUNT(*) FROM training_queue WHERE user_id = ? AND status = 'done'", [$id]),
                'badges' => (int) $one('SELECT COUNT(*) FROM user_badges WHERE user_id = ?', [$id]),
                'referrals' => (int) $one('SELECT COUNT(*) FROM referrals WHERE referrer_id = ?', [$id]),
                'sessions' => (int) $one('SELECT COUNT(*) FROM sessions WHERE user_id = ?', [$id]),
            ],
            'referral_code' => $one('SELECT code FROM referral_codes WHERE user_id = ?', [$id]) ?: null,
            'referred_by' => $referrer ? ['id' => (int) $referrer['id'], 'name' => (string) $referrer['character_name']] : null,
        ];
    }

    /**
     * Edycja danych konta. Zwraca zmiany (stara -> nowa wartosc) do dziennika.
     *
     * @param array<string,mixed> $in
     * @return array<string,array{from:mixed,to:mixed}>
     */
    public function update(int $adminId, int $id, array $in): array
    {
        $user = $this->user($id);
        $errors = [];
        $set = [];
        $changes = [];

        $change = static function (string $field, mixed $from, mixed $to) use (&$set, &$changes): void {
            if ($from !== $to) {
                $set[$field] = $to;
                $changes[$field] = ['from' => $from, 'to' => $to];
            }
        };

        if (array_key_exists('character_name', $in)) {
            $name = trim((string) $in['character_name']);
            $nameErrors = Validator::characterName($name);
            $errors += $nameErrors;
            if (!$nameErrors && $name !== $user['character_name']) {
                $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE character_name = ? AND id <> ?');
                $stmt->execute([$name, $id]);
                if ($stmt->fetchColumn()) {
                    $errors['character_name'] = 'Ta nazwa postaci jest już zajęta.';
                }
            }
            $change('character_name', $user['character_name'], $name);
        }

        if (array_key_exists('email', $in)) {
            $email = strtolower(trim((string) $in['email']));
            $emailErrors = Validator::email($email);
            $errors += $emailErrors;
            if (!$emailErrors && $email !== strtolower((string) $user['email'])) {
                $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE email = ? AND id <> ?');
                $stmt->execute([$email, $id]);
                if ($stmt->fetchColumn()) {
                    $errors['email'] = 'Ten adres e-mail jest już zajęty.';
                }
            }
            $change('email', $user['email'], $email);
        }

        if (array_key_exists('role', $in)) {
            $role = (string) $in['role'];
            if (!in_array($role, self::ROLES, true)) {
                $errors['role'] = 'Nieprawidłowa rola.';
            } elseif ($role !== $user['role'] && $adminId === $id) {
                $errors['role'] = 'Nie możesz zmienić własnej roli.';
            }
            $change('role', $user['role'], $role);
        }

        if (array_key_exists('gender', $in) || array_key_exists('avatar_id', $in)) {
            $gender = array_key_exists('gender', $in) ? $in['gender'] : $user['gender'];
            $avatarId = array_key_exists('avatar_id', $in) ? $in['avatar_id'] : $user['avatar_id'];
            if ($gender !== null && !in_array($gender, ['f', 'm', 'x'], true)) {
                $errors['gender'] = 'Nieprawidłowa płeć.';
            }
            if ($avatarId !== null) {
                $stmt = $this->pdo->prepare('SELECT gender FROM avatars WHERE id = ?');
                $stmt->execute([(int) $avatarId]);
                $found = $stmt->fetchColumn();
                if ($found === false) {
                    $errors['avatar_id'] = 'Nie ma takiego awatara.';
                } elseif ($gender !== null && $found !== $gender) {
                    $errors['avatar_id'] = 'Awatar nie pasuje do płci.';
                }
                $avatarId = (int) $avatarId;
            }
            $change('gender', $user['gender'], $gender);
            $change('avatar_id', $user['avatar_id'] !== null ? (int) $user['avatar_id'] : null, $avatarId);
        }

        if (array_key_exists('email_verified', $in)) {
            $verified = (bool) $in['email_verified'];
            if ($verified !== ($user['email_verified_at'] !== null)) {
                $set['email_verified_at'] = $verified ? Clock::fmt(Clock::now($this->pdo)) : null;
                $changes['email_verified'] = ['from' => $user['email_verified_at'] !== null, 'to' => $verified];
            }
        }

        if ($errors) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', $errors);
        }
        if ($set) {
            $cols = implode(', ', array_map(static fn(string $c): string => "$c = ?", array_keys($set)));
            $this->pdo->prepare("UPDATE users SET $cols WHERE id = ?")->execute([...array_values($set), $id]);
        }
        return $changes;
    }

    /**
     * Blokada, zawieszenie i ich zdjecie. $action: ban | unban | suspend | unsuspend.
     *
     * @return array<string,mixed> szczegoly do dziennika
     */
    public function setStatus(int $adminId, int $id, string $action, string $reason, int $days): array
    {
        $user = $this->user($id);
        if ($adminId === $id) {
            throw new ApiException(422, 'validation_failed', 'Nie możesz zmienić statusu własnego konta.');
        }
        if (in_array($action, ['ban', 'suspend'], true) && $user['role'] === 'admin') {
            throw new ApiException(422, 'validation_failed', 'Najpierw odbierz rolę administratora, potem zablokuj lub zawieś konto.');
        }
        $reason = trim(mb_substr($reason, 0, 255));

        switch ($action) {
            case 'ban':
                if ($reason === '') {
                    throw new ApiException(422, 'validation_failed', 'Podaj powód.', ['reason' => 'Podaj powód blokady.']);
                }
                $this->pdo->prepare("UPDATE users SET status = 'banned', suspended_until = NULL, status_reason = ? WHERE id = ?")->execute([$reason, $id]);
                $this->endSessions($id);
                return ['reason' => $reason];
            case 'unban':
                $this->pdo->prepare("UPDATE users SET status = 'active', status_reason = NULL WHERE id = ? AND status = 'banned'")->execute([$id]);
                return [];
            case 'suspend':
                if ($reason === '') {
                    throw new ApiException(422, 'validation_failed', 'Podaj powód.', ['reason' => 'Podaj powód zawieszenia.']);
                }
                if ($days < 1 || $days > self::MAX_SUSPEND_DAYS) {
                    throw new ApiException(422, 'validation_failed', 'Podaj liczbę dni.', ['days' => 'Zawieszenie może trwać od 1 do ' . self::MAX_SUSPEND_DAYS . ' dni.']);
                }
                $until = Clock::now($this->pdo)->modify("+$days days");
                $this->pdo->prepare('UPDATE users SET suspended_until = ?, status_reason = ? WHERE id = ?')->execute([Clock::fmt($until), $reason, $id]);
                $this->endSessions($id);
                return ['reason' => $reason, 'days' => $days, 'until' => Clock::fmt($until)];
            case 'unsuspend':
                $this->pdo->prepare("UPDATE users SET suspended_until = NULL, status_reason = NULL WHERE id = ? AND status <> 'banned'")->execute([$id]);
                return [];
        }
        throw new ApiException(422, 'validation_failed', 'Nieznana akcja.');
    }

    /**
     * Korekta portfela (nagroda, rekompensata, kara). Kazda zmiana zostaje w ksiedze gracza z powodem "admin_adjust".
     *
     * @return array<string,mixed> szczegoly do dziennika
     */
    public function adjustWallet(int $adminId, int $id, string $currency, int $amount, string $reason): array
    {
        $this->user($id);
        $errors = [];
        if (!in_array($currency, ['coins', 'tickets', 'points'], true)) {
            $errors['currency'] = 'Wybierz walutę.';
        }
        if ($amount === 0 || abs($amount) > 1_000_000_000) {
            $errors['amount'] = 'Podaj niezerową kwotę.';
        }
        $reason = trim(mb_substr($reason, 0, 255));
        if ($reason === '') {
            $errors['reason'] = 'Podaj powód korekty.';
        }
        if ($errors) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', $errors);
        }

        $this->pdo->beginTransaction();
        try {
            $balance = Wallet::apply($this->pdo, $id, $currency, $amount, 'admin_adjust', 'admin', $adminId);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($e instanceof ApiException && $e->errorCode === 'insufficient_funds') {
                throw new ApiException(422, 'validation_failed', 'Saldo nie może być ujemne.', ['amount' => 'Saldo nie może spaść poniżej zera.']);
            }
            throw $e;
        }
        return ['currency' => $currency, 'amount' => $amount, 'balance_after' => $balance, 'reason' => $reason];
    }

    /** Konczy wszystkie sesje gracza (wylogowanie ze wszystkich urzadzen). Zwraca liczbe zamknietych sesji. */
    public function endSessions(int $id): int
    {
        $stmt = $this->pdo->prepare('DELETE FROM sessions WHERE user_id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }

    /** @return array<string,mixed> wiersz users */
    public function user(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new ApiException(404, 'player_not_found', 'Nie znaleziono gracza.');
        }
        return $row;
    }

    /** @param array<string,mixed> $user */
    private function effectiveStatus(array $user): string
    {
        if ($user['status'] === 'banned') {
            return 'banned';
        }
        if ($user['suspended_until'] !== null && Clock::parse((string) $user['suspended_until']) > Clock::now($this->pdo)) {
            return 'suspended';
        }
        return $user['status'] === 'pending' ? 'pending' : 'active';
    }

    /** Czytelna data do komunikatow (czas polski). */
    public static function pl(string $utc): string
    {
        return Clock::parse($utc)->setTimezone(new DateTimeZone(Clock::GAME_TZ))->format('d.m.Y H:i');
    }
}
