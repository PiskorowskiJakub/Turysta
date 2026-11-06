<?php
declare(strict_types=1);

namespace Turysta\Squad;

use PDO;
use PDOException;
use Turysta\Admin\Paging;
use Turysta\Game\Clock;
use Turysta\Social\LastSeen;
use Turysta\Game\Notifier;
use Turysta\Http\ApiException;

/** Moderacja oddzialow w panelu administratora: lista, podglad, wymuszenie nazwy, zgloszenia nazw i slowa zakazane. */
final class SquadAdminService
{
    private const SORTS = [
        'id' => 's.id',
        'name' => 's.name',
        'members' => 'members',
        'ember' => 's.ember',
        'created' => 's.created_at',
        'reports' => 'reports',
    ];

    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<string,mixed> */
    public function list(int $page, string $q, string $sort, string $dir, string $state): array
    {
        $where = [];
        $params = [];
        if ($q !== '') {
            $where[] = '(s.name LIKE ? OR s.id = ?)';
            $params[] = Paging::like($q);
            $params[] = ctype_digit($q) ? (int) $q : 0;
        }
        if ($state === 'active') {
            $where[] = 's.archived_at IS NULL';
        } elseif ($state === 'archived') {
            $where[] = 's.archived_at IS NOT NULL';
        }
        $w = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $col = self::SORTS[$sort] ?? 's.id';
        $direction = strtolower($dir) === 'asc' ? 'ASC' : 'DESC';

        return Paging::run(
            $this->pdo,
            "SELECT COUNT(*) FROM squads s $w",
            "SELECT s.*,
                    (SELECT COUNT(*) FROM squad_members m WHERE m.squad_id = s.id) AS members,
                    (SELECT COUNT(*) FROM squad_name_reports r WHERE r.squad_id = s.id AND r.status = 'new') AS reports
             FROM squads s $w ORDER BY $col $direction, s.id DESC",
            $params,
            $page,
            fn(array $r): array => SquadMemory::appearance($this->pdo, $r) + [
                'id' => (int) $r['id'],
                'name' => (string) $r['name'],
                'display_name' => SquadRules::displayName((string) $r['name'], (string) $r['specialization']),
                'specialization' => (string) $r['specialization'],
                'members' => (int) $r['members'],
                'ember' => (int) $r['ember'],
                'flame_level' => SquadFire::level((int) $r['flame_streak']),
                'reports' => (int) $r['reports'],
                'name_locked' => (int) $r['name_locked'] === 1,
                'name_forced' => (int) $r['name_forced'] === 1,
                'archived' => $r['archived_at'] !== null,
                'created_at' => Clock::iso((string) $r['created_at']),
            ]
        );
    }

    /** @return array<string,mixed> */
    public function show(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM squads WHERE id = ?');
        $stmt->execute([$id]);
        $s = $stmt->fetch();
        if (!$s) {
            throw new ApiException(404, 'squad_not_found', 'Nie znaleziono oddziału.');
        }
        $stmt = $this->pdo->prepare(
            'SELECT m.user_id, m.role, m.joined_at, u.character_name, ' . LastSeen::sql('u.id') . ' AS last_seen,
                    EXISTS(SELECT 1 FROM sessions s WHERE s.user_id = u.id AND s.last_activity > UTC_TIMESTAMP() - INTERVAL 5 MINUTE) AS online
             FROM squad_members m JOIN users u ON u.id = m.user_id WHERE m.squad_id = ? ORDER BY m.joined_at'
        );
        $stmt->execute([$id]);
        $members = array_map(static fn(array $m): array => [
            'id' => (int) $m['user_id'],
            'name' => (string) $m['character_name'],
            'role' => (string) $m['role'],
            'joined_at' => Clock::iso((string) $m['joined_at']),
            'online' => (bool) $m['online'],
            'last_seen' => $m['last_seen'] === null ? null : Clock::iso((string) $m['last_seen']),
        ], $stmt->fetchAll());

        $stmt = $this->pdo->prepare(
            'SELECT h.id, h.name, h.created_at, u.character_name AS by_name FROM squad_name_history h LEFT JOIN users u ON u.id = h.changed_by WHERE h.squad_id = ? ORDER BY h.id DESC LIMIT 30'
        );
        $stmt->execute([$id]);
        $history = array_map(static fn(array $h): array => [
            'id' => (int) $h['id'],
            'name' => (string) $h['name'],
            'by' => $h['by_name'],
            'created_at' => Clock::iso((string) $h['created_at']),
        ], $stmt->fetchAll());

        return SquadMemory::appearance($this->pdo, $s) + [
            'id' => (int) $s['id'],
            'name' => (string) $s['name'],
            'display_name' => SquadRules::displayName((string) $s['name'], (string) $s['specialization']),
            'specialization' => (string) $s['specialization'],
            'join_mode' => (string) $s['join_mode'],
            'ember' => (int) $s['ember'],
            'flame_streak' => (int) $s['flame_streak'],
            'name_locked' => (int) $s['name_locked'] === 1,
            'name_forced' => (int) $s['name_forced'] === 1,
            'archived' => $s['archived_at'] !== null,
            'created_at' => Clock::iso((string) $s['created_at']),
            'members' => $members,
            'name_history' => $history,
            'reports' => $this->reports(1, 'all', $id)['rows'],
        ];
    }

    /** Wymusza zmiane nazwy: do czasu zmiany przez oddzial obowiazuje tymczasowa nazwa "nr ID". */
    public function forceName(int $id): void
    {
        $stmt = $this->pdo->prepare('SELECT * FROM squads WHERE id = ? AND archived_at IS NULL');
        $stmt->execute([$id]);
        $s = $stmt->fetch();
        if (!$s) {
            throw new ApiException(404, 'squad_not_found', 'Nie znaleziono oddziału.');
        }
        $name = 'nr ' . $id;
        $this->pdo->prepare('UPDATE squads SET name = ?, name_key = ?, name_forced = 1, name_changed_at = NULL WHERE id = ?')
            ->execute([$name, NameFilter::uniqueKey($name), $id]);
        $this->pdo->prepare('INSERT INTO squad_name_history (squad_id, name, changed_by) VALUES (?, ?, NULL)')->execute([$id, $name]);
        $this->pdo->prepare("UPDATE squad_name_reports SET status = 'resolved' WHERE squad_id = ? AND status = 'new'")->execute([$id]);
        SquadChronicle::record($this->pdo, $id, 'name', 'name:forced:' . time(), ['name' => $name, 'forced' => true]);
        $stmt = $this->pdo->prepare("SELECT user_id FROM squad_members WHERE squad_id = ? AND role IN ('leader','deputy')");
        $stmt->execute([$id]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $uid) {
            Notifier::add($this->pdo, (int) $uid, 'squad_name_forced', ['name' => SquadRules::displayName($name, (string) $s['specialization'])]);
        }
    }

    /**
     * Reczna zmiana nazwy przez administratora. Sprawdza format, slowa zakazane i unikalnosc (jak przy zmianie przez dowodce),
     * nie rusza blokady zmiany nazwy oddzialu i nie uruchamia 30-dniowego odstepu dla dowodcy.
     */
    public function rename(int $id, mixed $raw, int $adminId): void
    {
        $stmt = $this->pdo->prepare('SELECT * FROM squads WHERE id = ? AND archived_at IS NULL');
        $stmt->execute([$id]);
        $s = $stmt->fetch();
        if (!$s) {
            throw new ApiException(404, 'squad_not_found', 'Nie znaleziono oddziału.');
        }
        $name = is_string($raw) ? trim((string) preg_replace('/\s+/u', ' ', $raw)) : '';
        $err = NameFilter::formatError($name);
        if ($err !== null) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['name' => $err]);
        }
        if ($name === $s['name']) {
            return;
        }
        if (NameFilter::isBanned($this->pdo, $name)) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['name' => 'Ta nazwa zawiera niedozwolone słowo.']);
        }
        $dup = $this->pdo->prepare('SELECT id FROM squads WHERE name_key = ? AND id <> ?');
        $dup->execute([NameFilter::uniqueKey($name), $id]);
        if ($dup->fetchColumn()) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['name' => 'Oddział o takiej nazwie już istnieje.']);
        }
        $this->pdo->prepare('UPDATE squads SET name = ?, name_key = ?, name_forced = 0, name_changed_at = NULL WHERE id = ?')
            ->execute([$name, NameFilter::uniqueKey($name), $id]);
        $this->pdo->prepare('INSERT INTO squad_name_history (squad_id, name, changed_by) VALUES (?, ?, ?)')->execute([$id, $name, $adminId]);
        $this->pdo->prepare("UPDATE squad_name_reports SET status = 'resolved' WHERE squad_id = ? AND status = 'new'")->execute([$id]);
        SquadChronicle::record($this->pdo, $id, 'name', 'name:admin:' . time(), ['name' => $name, 'admin' => true]);
        $m = $this->pdo->prepare("SELECT user_id FROM squad_members WHERE squad_id = ? AND role IN ('leader','deputy')");
        $m->execute([$id]);
        foreach ($m->fetchAll(PDO::FETCH_COLUMN) as $uid) {
            Notifier::add($this->pdo, (int) $uid, 'squad_name_admin', ['name' => SquadRules::displayName($name, (string) $s['specialization'])]);
        }
    }

    public function lockName(int $id, bool $locked): void
    {
        $stmt = $this->pdo->prepare('UPDATE squads SET name_locked = ? WHERE id = ?');
        $stmt->execute([$locked ? 1 : 0, $id]);
        $exists = $this->pdo->prepare('SELECT 1 FROM squads WHERE id = ?');
        $exists->execute([$id]);
        if (!$exists->fetchColumn()) {
            throw new ApiException(404, 'squad_not_found', 'Nie znaleziono oddziału.');
        }
    }

    /** @return array<string,mixed> */
    public function reports(int $page, string $status, ?int $squadId = null): array
    {
        $where = [];
        $params = [];
        if (in_array($status, ['new', 'resolved', 'dismissed'], true)) {
            $where[] = 'r.status = ?';
            $params[] = $status;
        }
        if ($squadId !== null) {
            $where[] = 'r.squad_id = ?';
            $params[] = $squadId;
        }
        $w = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        return Paging::run(
            $this->pdo,
            "SELECT COUNT(*) FROM squad_name_reports r $w",
            "SELECT r.*, s.name AS current_name, s.specialization, s.archived_at, s.emblem, s.frame, u.character_name AS reporter
             FROM squad_name_reports r JOIN squads s ON s.id = r.squad_id JOIN users u ON u.id = r.reporter_id
             $w ORDER BY (r.status = 'new') DESC, r.created_at DESC",
            $params,
            $page,
            fn(array $r): array => SquadMemory::appearance($this->pdo, ['id' => $r['squad_id'], 'emblem' => $r['emblem'], 'frame' => $r['frame']]) + [
                'id' => (int) $r['id'],
                'squad_id' => (int) $r['squad_id'],
                'reported_name' => (string) $r['reported_name'],
                'current_name' => (string) $r['current_name'],
                'display_name' => SquadRules::displayName((string) $r['current_name'], (string) $r['specialization']),
                'archived' => $r['archived_at'] !== null,
                'reason' => (string) $r['reason'],
                'reporter' => (string) $r['reporter'],
                'status' => (string) $r['status'],
                'created_at' => Clock::iso((string) $r['created_at']),
            ]
        );
    }

    public function setReportStatus(int $id, string $status): void
    {
        if (!in_array($status, ['resolved', 'dismissed', 'new'], true)) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['status' => 'Nieprawidłowy status.']);
        }
        $stmt = $this->pdo->prepare('UPDATE squad_name_reports SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
        $exists = $this->pdo->prepare('SELECT 1 FROM squad_name_reports WHERE id = ?');
        $exists->execute([$id]);
        if (!$exists->fetchColumn()) {
            throw new ApiException(404, 'not_found', 'Nie znaleziono zgłoszenia.');
        }
    }

    /** @return array{words:list<array<string,mixed>>} */
    public function bannedWords(): array
    {
        $rows = $this->pdo->query('SELECT id, word_key, created_at FROM banned_words ORDER BY word_key')->fetchAll();
        return ['words' => array_map(static fn(array $r): array => [
            'id' => (int) $r['id'],
            'word' => (string) $r['word_key'],
            'created_at' => Clock::iso((string) $r['created_at']),
        ], $rows)];
    }

    /** Dodaje slowo zakazane (przechowujemy znormalizowany klucz) i zwraca go. */
    public function addBannedWord(string $word, int $adminId): string
    {
        $key = NameFilter::wordKey($word);
        if (strlen($key) < 3 || strlen($key) > 40) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['word' => 'Słowo musi mieć od 3 do 40 liter.']);
        }
        try {
            $this->pdo->prepare('INSERT INTO banned_words (word_key, created_by) VALUES (?, ?)')->execute([$key, $adminId]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new ApiException(409, 'already_exists', 'To słowo jest już na liście.');
            }
            throw $e;
        }
        return $key;
    }

    public function deleteBannedWord(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM banned_words WHERE id = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) {
            throw new ApiException(404, 'not_found', 'Nie znaleziono słowa.');
        }
    }
}
