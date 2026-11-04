<?php
declare(strict_types=1);

namespace Turysta\Squad;

use DateTimeImmutable;
use PDO;
use Throwable;
use Turysta\Game\BadgeService;
use Turysta\Game\Clock;
use Turysta\Game\Wallet;
use Turysta\Http\ApiException;

/**
 * Szlaki oddzialu: jedna wyprawa tygodniowo (poniedzialek-niedziela, czas gry).
 * - Wpisowe (2 dk, znika z obiegu) wplaca sie od poniedzialku do srody; dolaczajacy dostaje swoj udzial w trasie od dnia dolaczenia.
 * - Postep: dla kazdego uczestnika 50% to dni aktywnosci w grze, a drugie 50% to codzienny, darmowy Marsz. Postep wyprawy to srednia z uczestnikow.
 *   Punkty gracza nigdy nie sa brane pod uwage.
 * - Po niedzieli wyprawa jest rozliczana: pieczatki trafiaja do puli oddzialu, a uczestnicy z min. 3 dniami aktywnosci dostaja nagrode i zaliczenie do odznaki.
 */
final class SquadTrail
{
    public function __construct(private PDO $pdo, private SquadService $squads)
    {
    }

    // ---------------------------------------------------------------- czas

    public static function weekStart(DateTimeImmutable $now): DateTimeImmutable
    {
        $d = new DateTimeImmutable(Clock::gameDay($now));
        return $d->modify('-' . ((int) $d->format('N') - 1) . ' days');
    }

    /** 0 = poniedzialek ... 6 = niedziela. */
    private static function dayIndex(DateTimeImmutable $now): int
    {
        return (int) (new DateTimeImmutable(Clock::gameDay($now)))->format('N') - 1;
    }

    // ---------------------------------------------------------------- widok i akcje

    /**
     * @param array<string,mixed> $user
     * @return array<string,mixed>
     */
    public function view(array $user, int $squadId, ?DateTimeImmutable $now = null): array
    {
        $this->requireMember($user, $squadId);
        $now ??= Clock::now($this->pdo);
        $trail = $this->ensureCurrent($squadId, $now);
        $today = Clock::gameDay($now);
        $uid = (int) $user['id'];

        $calc = self::compute($this->pdo, $trail, $today);
        $me = null;
        foreach ($calc['rows'] as $row) {
            if ($row['user_id'] === $uid) {
                $me = $row;
            }
        }
        $marchedToday = false;
        if ($me) {
            $stmt = $this->pdo->prepare('SELECT 1 FROM squad_trail_marches WHERE trail_id = ? AND user_id = ? AND day = ?');
            $stmt->execute([$trail['id'], $uid, $today]);
            $marchedToday = (bool) $stmt->fetchColumn();
        }
        $dow = self::dayIndex($now);
        $weekStart = new DateTimeImmutable((string) $trail['week_start']);
        $difficulty = (string) $trail['difficulty'];
        $eligibleNow = count(array_filter($calc['rows'], static fn (array $r): bool => $r['eligible']));
        $stamps = self::stampsFor($difficulty, $calc['percent'], $eligibleNow);
        $theme = SquadTrailThemes::get((string) $trail['theme']);

        return [
            'id' => (int) $trail['id'],
            'week_start' => $weekStart->format('Y-m-d'),
            'week_end' => $weekStart->modify('+6 days')->format('Y-m-d'),
            'join_until' => $weekStart->modify('+' . SquadRules::TRAIL_JOIN_LAST_DAY . ' days')->format('Y-m-d'),
            'plan_until' => $weekStart->modify('+' . SquadRules::TRAIL_PLAN_LAST_DAY . ' days')->format('Y-m-d'),
            'difficulty' => $difficulty,
            'theme' => $theme ?? ['code' => (string) $trail['theme'], 'name' => (string) $trail['theme'], 'description' => ''],
            'length_km' => SquadRules::TRAIL_LENGTH_KM[$difficulty],
            'percent' => round($calc['percent'], 1),
            'km' => round($calc['percent'] / 100 * SquadRules::TRAIL_LENGTH_KM[$difficulty], 1),
            'thresholds' => SquadRules::TRAIL_THRESHOLDS[$difficulty],
            'max_stamps' => SquadRules::TRAIL_MAX_STAMPS[$difficulty],
            'stamps_now' => $stamps,
            'min_eligible' => SquadRules::TRAIL_MIN_ELIGIBLE,
            'min_active_days' => SquadRules::TRAIL_MIN_ACTIVE_DAYS,
            'fee_cents' => SquadRules::TRAIL_FEE_CENTS,
            'reward_per_stamp_cents' => SquadRules::TRAIL_REWARD_PER_STAMP_CENTS,
            'joined' => $me !== null,
            'can_join' => $me === null && $dow <= SquadRules::TRAIL_JOIN_LAST_DAY,
            'can_march' => $me !== null && !$marchedToday,
            'marched_today' => $marchedToday,
            'can_plan' => $this->canPlan($uid, $squadId) && $dow <= SquadRules::TRAIL_PLAN_LAST_DAY,
            'participants' => array_map(static fn (array $r): array => [
                'user_id' => $r['user_id'],
                'name' => $r['name'],
                'joined_day' => $r['joined_day'],
                'active_days' => $r['active_days'],
                'march_days' => $r['march_days'],
                'km' => round($r['share'] * SquadRules::TRAIL_LENGTH_KM[$difficulty], 1),
                'eligible' => $r['eligible'],
                'is_me' => $r['user_id'] === $uid,
            ], $calc['rows']),
            'squad_stamps' => (int) $this->squads->squad($squadId)['stamps'],
        ];
    }

    /** @param array<string,mixed> $user */
    public function join(array $user, int $squadId, ?DateTimeImmutable $now = null): void
    {
        $this->requireMember($user, $squadId);
        $now ??= Clock::now($this->pdo);
        $trail = $this->ensureCurrent($squadId, $now);
        if (self::dayIndex($now) > SquadRules::TRAIL_JOIN_LAST_DAY) {
            throw new ApiException(409, 'trail_closed', 'Na tę wyprawę nie można już dołączyć.');
        }
        $uid = (int) $user['id'];
        $this->pdo->beginTransaction();
        try {
            $lock = $this->pdo->prepare('SELECT status FROM squad_trails WHERE id = ? FOR UPDATE');
            $lock->execute([$trail['id']]);
            if ($lock->fetchColumn() !== 'active') {
                throw new ApiException(409, 'trail_closed', 'Ta wyprawa jest już zamknięta.');
            }
            $dup = $this->pdo->prepare('SELECT 1 FROM squad_trail_members WHERE trail_id = ? AND user_id = ?');
            $dup->execute([$trail['id'], $uid]);
            if ($dup->fetchColumn()) {
                throw new ApiException(409, 'already_joined', 'Już bierzesz udział w tej wyprawie.');
            }
            Wallet::apply(
                $this->pdo,
                $uid,
                'coins',
                -SquadRules::TRAIL_FEE_CENTS,
                'squad_trail_fee',
                'squad_trail',
                (int) $trail['id'],
                'trailfee:' . $trail['id'] . ':' . $uid
            );
            $this->pdo->prepare(
                'INSERT INTO squad_trail_members (trail_id, user_id, joined_day, joined_at, paid_cents) VALUES (?, ?, ?, ?, ?)'
            )->execute([$trail['id'], $uid, Clock::gameDay($now), Clock::fmt($now), SquadRules::TRAIL_FEE_CENTS]);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /** @param array<string,mixed> $user */
    public function march(array $user, int $squadId, ?DateTimeImmutable $now = null): void
    {
        $this->requireMember($user, $squadId);
        $now ??= Clock::now($this->pdo);
        $trail = $this->ensureCurrent($squadId, $now);
        $uid = (int) $user['id'];
        $in = $this->pdo->prepare('SELECT 1 FROM squad_trail_members WHERE trail_id = ? AND user_id = ?');
        $in->execute([$trail['id'], $uid]);
        if (!$in->fetchColumn()) {
            throw new ApiException(409, 'not_participant', 'Najpierw dołącz do wyprawy.');
        }
        $ins = $this->pdo->prepare('INSERT IGNORE INTO squad_trail_marches (trail_id, user_id, day) VALUES (?, ?, ?)');
        $ins->execute([$trail['id'], $uid, Clock::gameDay($now)]);
        if ($ins->rowCount() === 0) {
            throw new ApiException(409, 'march_done', 'Dzisiejszy Marsz już wykonany.');
        }
    }

    /**
     * @param array<string,mixed> $actor
     * @param array<string,mixed> $in
     */
    public function setDifficulty(array $actor, int $squadId, array $in, ?DateTimeImmutable $now = null): void
    {
        $this->squads->requirePower($actor, $squadId, 'plan');
        $now ??= Clock::now($this->pdo);
        $difficulty = (string) ($in['difficulty'] ?? '');
        if (!in_array($difficulty, SquadRules::TRAIL_DIFFICULTIES, true)) {
            throw new ApiException(422, 'validation_failed', 'Wybierz poziom trudności.', ['difficulty' => 'invalid_difficulty']);
        }
        $trail = $this->ensureCurrent($squadId, $now);
        if (self::dayIndex($now) > SquadRules::TRAIL_PLAN_LAST_DAY) {
            throw new ApiException(409, 'difficulty_locked', 'Trudność trasy można zmieniać tylko w poniedziałek.');
        }
        $this->pdo->prepare('UPDATE squad_trails SET difficulty = ?, changed_by = ? WHERE id = ? AND status = ?')
            ->execute([$difficulty, (int) $actor['id'], $trail['id'], 'active']);
    }

    /**
     * @param array<string,mixed> $user
     * @return list<array<string,mixed>>
     */
    public function history(array $user, int $squadId): array
    {
        $this->requireMember($user, $squadId);
        $stmt = $this->pdo->prepare(
            "SELECT id, week_start, difficulty, theme, percent, stamps, participants, eligible
             FROM squad_trails WHERE squad_id = ? AND status = 'done' ORDER BY week_start DESC LIMIT " . SquadRules::TRAIL_HISTORY
        );
        $stmt->execute([$squadId]);
        $mine = $this->pdo->prepare('SELECT eligible, reward_cents FROM squad_trail_members WHERE trail_id = ? AND user_id = ?');
        $out = [];
        foreach ($stmt->fetchAll() as $t) {
            $mine->execute([$t['id'], (int) $user['id']]);
            $m = $mine->fetch() ?: null;
            $out[] = [
                'id' => (int) $t['id'],
                'week_start' => (string) $t['week_start'],
                'difficulty' => (string) $t['difficulty'],
                'theme' => SquadTrailThemes::get((string) $t['theme']) ?? ['code' => (string) $t['theme'], 'name' => (string) $t['theme'], 'description' => ''],
                'length_km' => SquadRules::TRAIL_LENGTH_KM[(string) $t['difficulty']] ?? 0,
                'percent' => (float) $t['percent'],
                'stamps' => (int) $t['stamps'],
                'participants' => (int) $t['participants'],
                'eligible' => (int) $t['eligible'],
                'joined' => $m !== null,
                'my_reward_cents' => $m ? (int) $m['reward_cents'] : 0,
                'my_eligible' => $m ? (bool) $m['eligible'] : false,
            ];
        }
        return $out;
    }

    // ---------------------------------------------------------------- tworzenie i rozliczanie

    /**
     * Zwraca wyprawe biezacego tygodnia (tworzy ja, jesli jej jeszcze nie ma). Wczesniej rozlicza zalegle wyprawy oddzialu.
     *
     * @return array<string,mixed>
     */
    public function ensureCurrent(int $squadId, DateTimeImmutable $now): array
    {
        $week = self::weekStart($now);
        self::finalizeDue($this->pdo, $now, $squadId);
        $sel = $this->pdo->prepare('SELECT * FROM squad_trails WHERE squad_id = ? AND week_start = ?');
        $sel->execute([$squadId, $week->format('Y-m-d')]);
        $row = $sel->fetch();
        if ($row) {
            return $row;
        }
        $squad = $this->squads->squad($squadId);
        $prev = $this->pdo->prepare('SELECT theme, difficulty FROM squad_trails WHERE squad_id = ? AND week_start < ? ORDER BY week_start DESC LIMIT 1');
        $prev->execute([$squadId, $week->format('Y-m-d')]);
        $p = $prev->fetch() ?: null;
        $theme = SquadTrailThemes::pick($squadId, (string) $squad['specialization'], $week, $p['theme'] ?? null);
        $this->pdo->prepare('INSERT IGNORE INTO squad_trails (squad_id, week_start, difficulty, theme) VALUES (?, ?, ?, ?)')
            ->execute([$squadId, $week->format('Y-m-d'), $p['difficulty'] ?? 'medium', $theme]);
        $sel->execute([$squadId, $week->format('Y-m-d')]);
        return $sel->fetch();
    }

    /** Rozlicza wyprawy z minionych tygodni (wszystkie oddzialy albo jeden). Zwraca liczbe rozliczonych. */
    public static function finalizeDue(PDO $pdo, DateTimeImmutable $now, ?int $squadId = null): int
    {
        $sql = "SELECT id FROM squad_trails WHERE status = 'active' AND week_start < ?";
        $args = [self::weekStart($now)->format('Y-m-d')];
        if ($squadId !== null) {
            $sql .= ' AND squad_id = ?';
            $args[] = $squadId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($args);
        $done = 0;
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            try {
                $done += self::finalize($pdo, (int) $id, $now) ? 1 : 0;
            } catch (Throwable $e) {
                error_log('SquadTrail::finalize ' . $id . ': ' . $e->getMessage());
            }
        }
        return $done;
    }

    public static function finalize(PDO $pdo, int $trailId, DateTimeImmutable $now): bool
    {
        $badgeUsers = [];
        $pdo->beginTransaction();
        try {
            $lock = $pdo->prepare('SELECT * FROM squad_trails WHERE id = ? FOR UPDATE');
            $lock->execute([$trailId]);
            $trail = $lock->fetch();
            if (!$trail || $trail['status'] !== 'active') {
                $pdo->rollBack();
                return false;
            }
            $weekEnd = (new DateTimeImmutable((string) $trail['week_start']))->modify('+6 days')->format('Y-m-d');
            $calc = self::compute($pdo, $trail, $weekEnd);
            $difficulty = (string) $trail['difficulty'];
            $eligible = array_values(array_filter($calc['rows'], static fn (array $r): bool => $r['eligible']));
            $stamps = self::stampsFor($difficulty, $calc['percent'], count($eligible));
            $reward = $stamps * SquadRules::TRAIL_REWARD_PER_STAMP_CENTS;

            $upd = $pdo->prepare(
                'UPDATE squad_trail_members SET eligible = ?, active_days = ?, march_days = ?, reward_cents = ? WHERE trail_id = ? AND user_id = ?'
            );
            foreach ($calc['rows'] as $r) {
                $pays = $r['eligible'] && $stamps > 0;
                $upd->execute([$r['eligible'] ? 1 : 0, $r['active_days'], $r['march_days'], $pays ? $reward : 0, $trailId, $r['user_id']]);
                if ($pays) {
                    Wallet::apply($pdo, $r['user_id'], 'coins', $reward, 'squad_trail_reward', 'squad_trail', $trailId, 'trailreward:' . $trailId . ':' . $r['user_id']);
                    $badgeUsers[] = $r['user_id'];
                }
            }
            if ($stamps > 0) {
                $pdo->prepare('UPDATE squads SET stamps = stamps + ?, trails_done = trails_done + 1 WHERE id = ?')
                    ->execute([$stamps, $trail['squad_id']]);
            }
            $pdo->prepare(
                "UPDATE squad_trails SET status = 'done', percent = ?, stamps = ?, participants = ?, eligible = ?, finalized_at = ? WHERE id = ?"
            )->execute([round($calc['percent'], 2), $stamps, count($calc['rows']), count($eligible), Clock::fmt($now), $trailId]);
            if (count($calc['rows']) > 0) {
                SquadChronicle::record($pdo, (int) $trail['squad_id'], 'trail_done', 'trail:' . $trailId, [
                    'theme' => (string) $trail['theme'],
                    'theme_name' => SquadTrailThemes::get((string) $trail['theme'])['name'] ?? (string) $trail['theme'],
                    'percent' => round($calc['percent'], 1),
                    'stamps' => $stamps,
                    'difficulty' => $difficulty,
                ]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        foreach ($badgeUsers as $uid) {
            BadgeService::sync($pdo, $uid, $now);
        }
        return true;
    }

    // ---------------------------------------------------------------- obliczenia

    /**
     * Liczba pieczatek dla wyniku. Brak uprawnionych uczestnikow (min. 2) = 0.
     */
    public static function stampsFor(string $difficulty, float $percent, int $eligible): int
    {
        if ($eligible < SquadRules::TRAIL_MIN_ELIGIBLE) {
            return 0;
        }
        $level = 0;
        foreach (SquadRules::TRAIL_THRESHOLDS[$difficulty] as $min) {
            if ($percent >= $min) {
                $level++;
            }
        }
        if ($difficulty === 'hard' && $level === 4) {
            $level = 5;
        }
        return min($level, SquadRules::TRAIL_MAX_STAMPS[$difficulty]);
    }

    /**
     * Postep wyprawy na dzien $upTo (Y-m-d, nie pozniej niz koniec tygodnia). Liczymy tylko biezacych czlonkow oddzialu.
     * Udzial uczestnika = 50% (dni aktywnosci / dni od dolaczenia do konca wyprawy) + 50% (dni Marszu / te same dni).
     *
     * @param array<string,mixed> $trail
     * @return array{percent:float,rows:list<array<string,mixed>>}
     */
    public static function compute(PDO $pdo, array $trail, string $upTo): array
    {
        $weekEnd = (new DateTimeImmutable((string) $trail['week_start']))->modify('+6 days');
        $upTo = min($upTo, $weekEnd->format('Y-m-d'));
        $stmt = $pdo->prepare(
            'SELECT m.user_id, u.character_name AS name, m.joined_day,
                    (SELECT COUNT(*) FROM user_active_days a WHERE a.user_id = m.user_id AND a.day BETWEEN m.joined_day AND ?) AS active_days,
                    (SELECT COUNT(*) FROM squad_trail_marches x WHERE x.trail_id = m.trail_id AND x.user_id = m.user_id) AS march_days
             FROM squad_trail_members m
             JOIN users u ON u.id = m.user_id
             JOIN squad_members sm ON sm.user_id = m.user_id AND sm.squad_id = ?
             WHERE m.trail_id = ? ORDER BY m.joined_at, m.user_id'
        );
        $stmt->execute([$upTo, $trail['squad_id'], $trail['id']]);
        $rows = [];
        $sum = 0.0;
        foreach ($stmt->fetchAll() as $r) {
            $joined = new DateTimeImmutable((string) $r['joined_day']);
            $span = max(1, (int) $joined->diff($weekEnd)->days + 1);
            $active = (int) $r['active_days'];
            $march = (int) $r['march_days'];
            $share = 0.5 * min(1.0, $active / $span) + 0.5 * min(1.0, $march / $span);
            $sum += $share;
            $rows[] = [
                'user_id' => (int) $r['user_id'],
                'name' => (string) $r['name'],
                'joined_day' => (string) $r['joined_day'],
                'active_days' => $active,
                'march_days' => $march,
                'share' => $share,
                'eligible' => $active >= SquadRules::TRAIL_MIN_ACTIVE_DAYS,
            ];
        }
        $percent = $rows ? min(100.0, $sum / count($rows) * 100) : 0.0;
        return ['percent' => $percent, 'rows' => $rows];
    }

    // ---------------------------------------------------------------- pomocnicze

    /** @param array<string,mixed> $user */
    private function requireMember(array $user, int $squadId): void
    {
        $m = $this->squads->memberRow((int) $user['id']);
        if (!$m || (int) $m['squad_id'] !== $squadId) {
            throw new ApiException(403, 'forbidden', 'Ta sekcja jest dostępna tylko dla członków oddziału.');
        }
        $this->squads->squad($squadId);
    }

    private function canPlan(int $userId, int $squadId): bool
    {
        $m = $this->squads->memberRow($userId);
        return $m && (int) $m['squad_id'] === $squadId && in_array((string) $m['role'], ['leader', 'deputy', 'guide'], true);
    }
}
