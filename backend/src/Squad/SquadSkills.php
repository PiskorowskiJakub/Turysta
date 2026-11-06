<?php
declare(strict_types=1);

namespace Turysta\Squad;

use DateInterval;
use DateTimeImmutable;
use PDO;
use Throwable;
use Turysta\Game\Clock;
use Turysta\Game\Economy;
use Turysta\Game\Notifier;
use Turysta\Game\TrainingQueue;
use Turysta\Game\Wallet;
use Turysta\Http\ApiException;

/**
 * Umiejetnosci oddzialowe: gracz trenuje je w osobnym slocie (bez maksymalnego poziomu, za dukaty i czas), a korzysc trafia do oddzialu.
 * Efekt = maksimum x L / (L + polowa), gdzie L to srednia poziomow aktywnych czlonkow (posiadacz pasujacej roli wnosi o 10% wiecej).
 * Bonusy dzialaja tylko, gdy oddzial ma co najmniej 3 aktywnych czlonkow. Gracz bez oddzialu trenuje i zachowuje poziomy, ale bez efektu.
 */
final class SquadSkills
{
    public function __construct(private PDO $pdo)
    {
    }

    // ---------------------------------------------------------------- koszty i efekty

    public static function seconds(int $level): int
    {
        return SquadRules::SKILL_HOURS_PER_LEVEL * 3600 * max(1, $level);
    }

    /** Koszt poziomu w setnych dukata po zniżce (procent) ze Spizarni. */
    public static function costCents(int $level, float $discountPercent = 0.0): int
    {
        $base = SquadRules::SKILL_HOURS_PER_LEVEL * max(1, $level) * SquadRules::SKILL_CENTS_PER_HOUR;
        return (int) round($base * (1 - $discountPercent / 100));
    }

    /** Efekt umiejetnosci w procentach dla sredniego poziomu. */
    public static function effectPercent(string $skill, float $avgLevel): float
    {
        $max = SquadRules::SKILL_MAX_PERCENT[$skill] ?? 0.0;
        $avgLevel = max(0.0, $avgLevel);
        return $max * $avgLevel / ($avgLevel + SquadRules::SKILL_HALF);
    }

    /** Zniżka dukatowego kosztu szkolen (Spizarnia) w procentach. */
    public static function pantryDiscountPercent(int $pantryLevel): float
    {
        $l = max(0, $pantryLevel);
        return SquadRules::PANTRY_MAX_PERCENT * $l / ($l + SquadRules::PANTRY_HALF);
    }

    /** Skrocenie odnowienia tarczy przez Izbe chorych w procentach odnowienia. */
    public static function infirmaryCooldownPercent(int $level): float
    {
        $l = max(0, $level);
        return SquadRules::INFIRMARY_MAX_PERCENT * $l / ($l + SquadRules::INFIRMARY_HALF);
    }

    /**
     * Profil oddzialu: liczba aktywnych czlonkow, srednie poziomy i efekty (0, gdy aktywnych jest za malo).
     *
     * @return array{active:int,enough:bool,avg:array<string,float>,effect:array<string,float>}
     */
    public static function profile(PDO $pdo, int $squadId, ?string $today = null): array
    {
        $today ??= Clock::gameDay(Clock::now($pdo));
        $since = (new DateTimeImmutable($today))->modify('-' . SquadRules::ACTIVE_MEMBER_DAYS . ' days')->format('Y-m-d');
        $stmt = $pdo->prepare(
            'SELECT m.user_id, m.role, (SELECT COUNT(*) FROM user_active_days a WHERE a.user_id = m.user_id AND a.day > ? AND a.day <= ?) AS days
             FROM squad_members m WHERE m.squad_id = ?'
        );
        $stmt->execute([$since, $today, $squadId]);
        $members = [];
        foreach ($stmt->fetchAll() as $r) {
            if ((int) $r['days'] >= SquadRules::ACTIVE_MEMBER_MIN_DAYS) {
                $members[(int) $r['user_id']] = (string) $r['role'];
            }
        }
        $avg = array_fill_keys(SquadRules::SKILLS, 0.0);
        $effect = $avg;
        if (!$members) {
            return ['active' => 0, 'enough' => false, 'avg' => $avg, 'effect' => $effect];
        }
        $in = implode(',', array_fill(0, count($members), '?'));
        $lv = $pdo->prepare("SELECT user_id, skill, level FROM user_squad_skills WHERE user_id IN ($in)");
        $lv->execute(array_keys($members));
        $levels = [];
        foreach ($lv->fetchAll() as $r) {
            $levels[(int) $r['user_id']][(string) $r['skill']] = (int) $r['level'];
        }
        foreach (SquadRules::SKILLS as $skill) {
            $sum = 0.0;
            foreach ($members as $uid => $role) {
                $l = (float) ($levels[$uid][$skill] ?? 0);
                if (in_array($role, SquadRules::SKILL_ROLES[$skill] ?? [], true)) {
                    $l *= 1 + SquadRules::SKILL_ROLE_BONUS_PERCENT / 100;
                }
                $sum += $l;
            }
            $avg[$skill] = round($sum / count($members), 2);
        }
        $enough = count($members) >= SquadRules::SKILL_MIN_ACTIVE_MEMBERS;
        if ($enough) {
            foreach (SquadRules::SKILL_MAX_PERCENT as $skill => $_) {
                $effect[$skill] = round(self::effectPercent($skill, $avg[$skill]), 2);
            }
        }
        return ['active' => count($members), 'enough' => $enough, 'avg' => $avg, 'effect' => $effect];
    }

    /** Efekt jednej umiejetnosci oddzialu w procentach (0 przy braku oddzialu, bonusu lub bledzie, np. przed migracja). */
    public static function effectFor(PDO $pdo, int $squadId, string $skill, ?string $today = null): float
    {
        try {
            return self::profile($pdo, $squadId, $today)['effect'][$skill] ?? 0.0;
        } catch (Throwable $e) {
            error_log('SquadSkills::effectFor: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Tarcza ogniska: maksymalna liczba ladunkow i odnowienie w dniach.
     *
     * @param array{enough:bool,avg:array<string,float>} $profile
     * @return array{cap:int,cooldown_days:float}
     */
    public static function shield(array $profile, int $infirmaryLevel): array
    {
        if (!$profile['enough']) {
            return ['cap' => 0, 'cooldown_days' => (float) SquadRules::SHIELD_COOLDOWN_DAYS];
        }
        $cap = ($profile['avg']['first_aid'] >= 1 ? 1 : 0);
        foreach (SquadRules::INFIRMARY_CHARGE_LEVELS as $need) {
            if ($infirmaryLevel >= $need) {
                $cap++;
            }
        }
        $fa = $profile['avg']['first_aid'] / ($profile['avg']['first_aid'] + SquadRules::SKILL_HALF);
        $fi = $infirmaryLevel > 0 ? $infirmaryLevel / ($infirmaryLevel + SquadRules::INFIRMARY_HALF) : 0.0;
        $combined = 1 - (1 - $fa) * (1 - $fi);
        $days = max((float) SquadRules::SHIELD_COOLDOWN_MIN_DAYS, SquadRules::SHIELD_COOLDOWN_DAYS * (1 - 0.5 * $combined));
        return ['cap' => $cap, 'cooldown_days' => round($days, 1)];
    }

    /**
     * Trudnosci tras dostepne oddzialowi: trzy podstawowe od poczatku, kolejne po osiagnieciu sredniego poziomu Kartografii.
     *
     * @return list<string>
     */
    public static function unlockedDifficulties(PDO $pdo, int $squadId): array
    {
        $out = ['easy', 'medium', 'hard'];
        try {
            $avg = self::profile($pdo, $squadId)['avg']['cartography'];
        } catch (Throwable $e) {
            return $out;
        }
        foreach (SquadRules::TRAIL_UNLOCK_CARTOGRAPHY as $difficulty => $need) {
            if ($avg >= $need) {
                $out[] = $difficulty;
            }
        }
        return $out;
    }

    /** @return array<string,int> */
    public static function levels(PDO $pdo, int $userId): array
    {
        $levels = array_fill_keys(SquadRules::SKILLS, 0);
        $stmt = $pdo->prepare('SELECT skill, level FROM user_squad_skills WHERE user_id = ?');
        $stmt->execute([$userId]);
        foreach ($stmt->fetchAll() as $r) {
            if (isset($levels[(string) $r['skill']])) {
                $levels[(string) $r['skill']] = (int) $r['level'];
            }
        }
        return $levels;
    }

    // ---------------------------------------------------------------- stan gracza

    /** @return array<string,mixed> */
    public function state(int $userId): array
    {
        $now = Clock::now($this->pdo);
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE')->execute([$userId]);
            self::settle($this->pdo, $userId, $now);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        $levels = self::levels($this->pdo, $userId);
        $queued = array_fill_keys(SquadRules::SKILLS, 0);
        $active = null;
        $queue = [];
        $stmt = $this->pdo->prepare(
            "SELECT * FROM squad_skill_queue WHERE user_id = ? AND status IN ('active','queued') ORDER BY (status = 'active') DESC, position, id"
        );
        $stmt->execute([$userId]);
        foreach ($stmt->fetchAll() as $r) {
            $queued[(string) $r['skill']]++;
            $refund = $r['status'] === 'active'
                ? intdiv((int) $r['cost_cents'] * TrainingQueue::ACTIVE_REFUND_PERCENT, 100)
                : (int) $r['cost_cents'];
            $item = [
                'id' => (int) $r['id'],
                'skill' => (string) $r['skill'],
                'target_level' => (int) $r['target_level'],
                'cost_cents' => (int) $r['cost_cents'],
                'duration_seconds' => (int) $r['duration_seconds'],
                'started_at' => $r['started_at'] !== null ? Clock::iso((string) $r['started_at']) : null,
                'ends_at' => $r['ends_at'] !== null ? Clock::iso((string) $r['ends_at']) : null,
                'refund_cents' => $refund,
            ];
            if ($r['status'] === 'active') {
                $active = $item;
            } else {
                $queue[] = $item;
            }
        }

        $squad = $this->squadOf($userId);
        $discount = 0.0;
        $profile = null;
        if ($squad !== null) {
            $discount = self::pantryDiscountPercent(SquadShelter::levels($this->pdo, $squad)['pantry']);
            $profile = self::profile($this->pdo, $squad);
        }
        $skills = [];
        foreach (SquadRules::SKILLS as $skill) {
            $next = $levels[$skill] + $queued[$skill] + 1;
            $skills[] = [
                'code' => $skill,
                'level' => $levels[$skill],
                'in_queue' => $queued[$skill],
                'next_level' => $next,
                'next_cost_cents' => self::costCents($next, $discount),
                'next_duration_seconds' => self::seconds($next),
                'max_percent' => SquadRules::SKILL_MAX_PERCENT[$skill] ?? null,
                'squad_avg' => $profile ? $profile['avg'][$skill] : null,
                'squad_effect_percent' => $profile ? $profile['effect'][$skill] : null,
                'role_bonus' => SquadRules::SKILL_ROLES[$skill] ?? [],
            ];
        }
        return [
            'server_time' => gmdate('c'),
            'max_slots' => SquadRules::SKILL_QUEUE_SLOTS,
            'active' => $active,
            'queue' => $queue,
            'skills' => $skills,
            'squad' => $squad === null ? null : [
                'id' => $squad,
                'active_members' => $profile['active'],
                'enough' => $profile['enough'],
                'min_active' => SquadRules::SKILL_MIN_ACTIVE_MEMBERS,
                'discount_percent' => round($discount, 2),
            ],
            'half' => SquadRules::SKILL_HALF,
        ];
    }

    // ---------------------------------------------------------------- szkolenia

    /** @return array<string,mixed> */
    public function train(int $userId, string $skill): array
    {
        if (!in_array($skill, SquadRules::SKILLS, true)) {
            throw new ApiException(404, 'not_found', 'Nie ma takiej umiejętności oddziałowej.');
        }
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE')->execute([$userId]);
            $now = Clock::now($this->pdo);
            self::settle($this->pdo, $userId, $now);

            $count = $this->pdo->prepare("SELECT COUNT(*) FROM squad_skill_queue WHERE user_id = ? AND status IN ('active','queued')");
            $count->execute([$userId]);
            if ((int) $count->fetchColumn() >= SquadRules::SKILL_QUEUE_SLOTS) {
                throw new ApiException(409, 'queue_full', 'Kolejka szkoleń jest pełna.');
            }
            $this->pdo->prepare('INSERT IGNORE INTO user_squad_skills (user_id, skill, level) VALUES (?, ?, 0)')->execute([$userId, $skill]);
            $lv = $this->pdo->prepare('SELECT level FROM user_squad_skills WHERE user_id = ? AND skill = ? FOR UPDATE');
            $lv->execute([$userId, $skill]);
            $level = (int) $lv->fetchColumn();
            $inq = $this->pdo->prepare("SELECT COUNT(*) FROM squad_skill_queue WHERE user_id = ? AND skill = ? AND status IN ('active','queued')");
            $inq->execute([$userId, $skill]);
            $target = $level + (int) $inq->fetchColumn() + 1;

            $squad = $this->squadOf($userId);
            $discount = $squad !== null ? self::pantryDiscountPercent(SquadShelter::levels($this->pdo, $squad)['pantry']) : 0.0;
            $cost = self::costCents($target, $discount);
            $seconds = self::seconds($target);

            $hasActive = $this->pdo->prepare("SELECT COUNT(*) FROM squad_skill_queue WHERE user_id = ? AND status = 'active'");
            $hasActive->execute([$userId]);
            if ((int) $hasActive->fetchColumn() > 0) {
                $pos = $this->pdo->prepare("SELECT COALESCE(MAX(position), 0) + 1 FROM squad_skill_queue WHERE user_id = ? AND status = 'queued'");
                $pos->execute([$userId]);
                $this->insert($userId, $skill, $target, $cost, $seconds, 'queued', (int) $pos->fetchColumn(), null, null);
            } else {
                $ends = $now->add(new DateInterval('PT' . $seconds . 'S'));
                $this->insert($userId, $skill, $target, $cost, $seconds, 'active', 0, Clock::fmt($now), Clock::fmt($ends));
            }
            $id = (int) $this->pdo->lastInsertId();
            Wallet::apply($this->pdo, $userId, 'coins', -$cost, 'squad_skill_cost', 'squad_skill', $id);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
        return $this->state($userId);
    }

    /** @return array<string,mixed> */
    public function cancel(int $userId, int $trainingId): array
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE')->execute([$userId]);
            $now = Clock::now($this->pdo);
            self::settle($this->pdo, $userId, $now);

            $stmt = $this->pdo->prepare("SELECT * FROM squad_skill_queue WHERE id = ? AND user_id = ? AND status IN ('active','queued') FOR UPDATE");
            $stmt->execute([$trainingId, $userId]);
            $row = $stmt->fetch();
            if (!$row) {
                throw new ApiException(404, 'not_found', 'Nie znaleziono takiego szkolenia.');
            }
            $wasActive = $row['status'] === 'active';
            $refund = $wasActive ? intdiv((int) $row['cost_cents'] * TrainingQueue::ACTIVE_REFUND_PERCENT, 100) : (int) $row['cost_cents'];
            $this->pdo->prepare("UPDATE squad_skill_queue SET status = 'cancelled', finished_at = ? WHERE id = ?")->execute([Clock::fmt($now), $trainingId]);
            if ($refund > 0) {
                Wallet::apply($this->pdo, $userId, 'coins', $refund, 'squad_skill_refund', 'squad_skill', $trainingId);
            }
            // Kolejne poziomy tej samej umiejetnosci przesuwaja sie o jeden w dol (nizszy koszt i czas, roznica wraca do portfela).
            $squad = $this->squadOf($userId);
            $discount = $squad !== null ? self::pantryDiscountPercent(SquadShelter::levels($this->pdo, $squad)['pantry']) : 0.0;
            $later = $this->pdo->prepare(
                "SELECT id, target_level, cost_cents FROM squad_skill_queue
                 WHERE user_id = ? AND skill = ? AND status = 'queued' AND target_level > ? ORDER BY target_level FOR UPDATE"
            );
            $later->execute([$userId, $row['skill'], $row['target_level']]);
            foreach ($later->fetchAll() as $l) {
                $target = (int) $l['target_level'] - 1;
                $cost = self::costCents($target, $discount);
                $this->pdo->prepare('UPDATE squad_skill_queue SET target_level = ?, cost_cents = ?, duration_seconds = ? WHERE id = ?')
                    ->execute([$target, $cost, self::seconds($target), $l['id']]);
                $diff = (int) $l['cost_cents'] - $cost;
                if ($diff > 0) {
                    Wallet::apply($this->pdo, $userId, 'coins', $diff, 'squad_skill_refund', 'squad_skill', (int) $l['id']);
                }
            }
            if ($wasActive) {
                self::startNext($this->pdo, $userId, $now);
            }
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
        return $this->state($userId);
    }

    /**
     * Konczy szkolenia, ktorych czas minal, i uruchamia kolejne. Wywolywac w transakcji z zablokowanym wierszem gracza.
     */
    public static function settle(PDO $pdo, int $userId, DateTimeImmutable $now): void
    {
        while (true) {
            $stmt = $pdo->prepare("SELECT * FROM squad_skill_queue WHERE user_id = ? AND status = 'active' AND ends_at <= ? LIMIT 1 FOR UPDATE");
            $stmt->execute([$userId, Clock::fmt($now)]);
            $row = $stmt->fetch();
            if (!$row) {
                return;
            }
            $pdo->prepare("UPDATE squad_skill_queue SET status = 'done', finished_at = ends_at WHERE id = ?")->execute([$row['id']]);
            $pdo->prepare('INSERT INTO user_squad_skills (user_id, skill, level) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE level = level + 1')
                ->execute([$userId, $row['skill']]);
            // Punkty jak za zwykle szkolenie (1 za kazde rozpoczete 20 minut), dopiero po jego zakonczeniu; anulowane nie daje punktow.
            Wallet::apply($pdo, $userId, 'points', Economy::trainingPoints((int) $row['duration_seconds']), 'squad_skill_points', 'squad_skill', (int) $row['id']);
            Notifier::add($pdo, $userId, 'squad_skill_finished', ['skill' => (string) $row['skill'], 'level' => (int) $row['target_level']]);
            self::startNext($pdo, $userId, Clock::parse((string) $row['ends_at']));
        }
    }

    private static function startNext(PDO $pdo, int $userId, DateTimeImmutable $startAt): void
    {
        $stmt = $pdo->prepare("SELECT id, duration_seconds FROM squad_skill_queue WHERE user_id = ? AND status = 'queued' ORDER BY position, id LIMIT 1 FOR UPDATE");
        $stmt->execute([$userId]);
        $next = $stmt->fetch();
        if (!$next) {
            return;
        }
        $ends = $startAt->add(new DateInterval('PT' . max(1, (int) $next['duration_seconds']) . 'S'));
        $pdo->prepare("UPDATE squad_skill_queue SET status = 'active', started_at = ?, ends_at = ? WHERE id = ?")
            ->execute([Clock::fmt($startAt), Clock::fmt($ends), $next['id']]);
    }

    private function insert(int $userId, string $skill, int $target, int $cost, int $seconds, string $status, int $position, ?string $start, ?string $end): void
    {
        $this->pdo->prepare(
            'INSERT INTO squad_skill_queue (user_id, skill, target_level, cost_cents, duration_seconds, status, position, started_at, ends_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([$userId, $skill, $target, $cost, $seconds, $status, $position, $start, $end]);
    }

    private function squadOf(int $userId): ?int
    {
        $stmt = $this->pdo->prepare('SELECT m.squad_id FROM squad_members m JOIN squads s ON s.id = m.squad_id AND s.archived_at IS NULL WHERE m.user_id = ?');
        $stmt->execute([$userId]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }
}
