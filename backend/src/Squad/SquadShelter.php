<?php
declare(strict_types=1);

namespace Turysta\Squad;

use DateInterval;
use DateTimeImmutable;
use PDO;
use Throwable;
use Turysta\Game\Clock;
use Turysta\Game\Wallet;
use Turysta\Http\ApiException;

/**
 * Schronisko oddzialu: magazyn bez limitu (zar z ogniska, pieczatki ze szlakow, dukaty z wplat czlonkow)
 * i pomieszczenia bez maksymalnego poziomu. Kazdy kolejny poziom kosztuje wiecej (baza x n^wykladnik) i dluzej sie buduje.
 * Skladniki schodza z magazynu w chwili rozpoczecia budowy, a poziom (i jego efekt) przybywa dopiero po jej zakonczeniu.
 */
final class SquadShelter
{
    public function __construct(private PDO $pdo, private SquadService $squads)
    {
    }

    // ---------------------------------------------------------------- koszty i efekty

    /** @return array{ember:int,stamps:int,cents:int} koszt wejscia na poziom $level ($level >= 1) */
    public static function cost(string $room, int $level): array
    {
        $factor = SquadRules::ROOMS[$room] ?? 1.0;
        $level = max(1, $level);
        return [
            'ember' => (int) round(SquadRules::ROOM_BASE_EMBER * ($level ** SquadRules::ROOM_EXPONENT) * $factor),
            'stamps' => max(1, (int) ceil(SquadRules::ROOM_BASE_STAMPS * ($level ** SquadRules::ROOM_STAMPS_EXPONENT) * $factor)),
            'cents' => (int) round(SquadRules::ROOM_BASE_COINS * ($level ** SquadRules::ROOM_EXPONENT) * $factor) * 100,
        ];
    }

    public static function buildHours(int $level): int
    {
        return SquadRules::ROOM_HOURS_PER_LEVEL * max(1, $level);
    }

    /** Efekt pomieszczenia w procentach dla poziomu albo null, gdy efekt jeszcze nie dziala ("wkrotce"). */
    public static function effectPercent(string $room, int $level): ?float
    {
        $level = max(0, $level);
        return match ($room) {
            'kitchen' => round(SquadRules::KITCHEN_MAX_PERCENT * $level / ($level + SquadRules::KITCHEN_HALF), 2),
            'mapping' => round(SquadRules::MAPPING_MAX_PERCENT * $level / ($level + SquadRules::MAPPING_HALF), 2),
            default => null,
        };
    }

    /**
     * Dzienny limit wplat na osobe w setnych dukata: srednia cena dukatowa kolejnych poziomow wszystkich pomieszczen / dzielnik,
     * ale co najmniej minimum. Rosnie wiec razem z cenami.
     *
     * @param array<string,int> $levels
     */
    public static function depositLimitCents(array $levels): int
    {
        $sum = 0;
        foreach (array_keys(SquadRules::ROOMS) as $room) {
            $sum += self::cost($room, ($levels[$room] ?? 0) + 1)['cents'];
        }
        $avgCoins = $sum / count(SquadRules::ROOMS) / 100;
        return max(SquadRules::DEPOSIT_MIN_COINS, (int) round($avgCoins / SquadRules::DEPOSIT_DIVISOR)) * 100;
    }

    /**
     * Pojemnosc magazynu na dukaty: najdrozsza kolejna rozbudowa. Wiecej nie ma sensu wplacac, wiec dukaty nie zalegaja.
     *
     * @param array<string,int> $levels
     */
    public static function vaultCapCents(array $levels): int
    {
        $max = 0;
        foreach (array_keys(SquadRules::ROOMS) as $room) {
            $max = max($max, self::cost($room, ($levels[$room] ?? 0) + 1)['cents']);
        }
        return $max;
    }

    /** @return array<string,int> */
    public static function levels(PDO $pdo, int $squadId): array
    {
        $levels = array_fill_keys(array_keys(SquadRules::ROOMS), 0);
        $stmt = $pdo->prepare('SELECT room, level FROM squad_rooms WHERE squad_id = ?');
        $stmt->execute([$squadId]);
        foreach ($stmt->fetchAll() as $r) {
            if (isset($levels[(string) $r['room']])) {
                $levels[(string) $r['room']] = (int) $r['level'];
            }
        }
        return $levels;
    }

    // ---------------------------------------------------------------- efekty w grze

    /** Premia Kuchni do wyplaty z pracy (w setnych dukata) dla gracza z oddzialu, ktorego Kuchnia ma poziom > 0. */
    public static function kitchenBonusCents(PDO $pdo, int $userId, int $payoutCents): int
    {
        if ($payoutCents <= 0) {
            return 0;
        }
        try {
            $stmt = $pdo->prepare(
                "SELECT r.level FROM squad_members m
                 JOIN squad_rooms r ON r.squad_id = m.squad_id AND r.room = 'kitchen'
                 JOIN squads s ON s.id = m.squad_id AND s.archived_at IS NULL
                 WHERE m.user_id = ?"
            );
            $stmt->execute([$userId]);
            $level = (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            // Premia nie moze zablokowac wyplaty z pracy (np. przed migracja).
            error_log('SquadShelter::kitchenBonusCents: ' . $e->getMessage());
            return 0;
        }
        if ($level <= 0) {
            return 0;
        }
        return (int) floor($payoutCents * (self::effectPercent('kitchen', $level) ?? 0.0) / 100);
    }

    /** Dolicza premie Kuchni do portfela gracza. Zwraca kwote premii. */
    public static function payKitchenBonus(PDO $pdo, int $userId, int $payoutCents, int $runId): int
    {
        $bonus = self::kitchenBonusCents($pdo, $userId, $payoutCents);
        if ($bonus > 0) {
            Wallet::earn($pdo, $userId, 'squad:kitchen', $bonus, 'squad_kitchen_bonus', 'job_run', $runId);
        }
        return $bonus;
    }

    /**
     * Premia Mapowni: dodatkowe pieczatki do puli oddzialu. Ulamki odkladamy w stamp_carry, zeby male premie sie sumowaly.
     * Wywolywac w transakcji rozliczenia wyprawy.
     */
    public static function mappingExtraStamps(PDO $pdo, int $squadId, int $stamps): int
    {
        if ($stamps <= 0) {
            return 0;
        }
        $level = self::levels($pdo, $squadId)['mapping'];
        if ($level <= 0) {
            return 0;
        }
        $stmt = $pdo->prepare('SELECT stamp_carry FROM squads WHERE id = ? FOR UPDATE');
        $stmt->execute([$squadId]);
        $exact = $stamps * (self::effectPercent('mapping', $level) ?? 0.0) / 100 + (float) $stmt->fetchColumn();
        $extra = (int) floor($exact + 1e-9);
        $pdo->prepare('UPDATE squads SET stamp_carry = ? WHERE id = ?')->execute([round(max(0.0, $exact - $extra), 4), $squadId]);
        return $extra;
    }

    // ---------------------------------------------------------------- widok

    /**
     * @param array<string,mixed> $user
     * @return array<string,mixed>
     */
    public function view(array $user, int $squadId, ?DateTimeImmutable $now = null): array
    {
        $this->requireMember($user, $squadId);
        $now ??= Clock::now($this->pdo);
        self::finalizeDue($this->pdo, $now, $squadId);
        $squad = $this->squads->squad($squadId);
        $uid = (int) $user['id'];
        $levels = self::levels($this->pdo, $squadId);
        $build = $this->activeBuild($squadId);

        $ember = max(0, (int) $squad['ember'] - (int) $squad['ember_spent']);
        $stamps = (int) $squad['stamps'];
        $cents = (int) $squad['vault_cents'];

        $rooms = [];
        foreach (array_keys(SquadRules::ROOMS) as $room) {
            $level = $levels[$room];
            $next = self::cost($room, $level + 1);
            $effect = self::effectPercent($room, $level);
            $rooms[] = [
                'code' => $room,
                'level' => $level,
                'effect_percent' => $effect,
                'next_effect_percent' => self::effectPercent($room, $level + 1),
                'effect_soon' => $effect === null,
                'next' => $next + ['hours' => self::buildHours($level + 1), 'level' => $level + 1],
                'building' => $build !== null && $build['room'] === $room,
                'affordable' => $ember >= $next['ember'] && $stamps >= $next['stamps'] && $cents >= $next['cents'],
            ];
        }

        $limit = self::depositLimitCents($levels);
        $used = $this->depositedToday($uid, Clock::gameDay($now));
        $cap = self::vaultCapCents($levels);
        $remaining = max(0, min($limit - $used, $cap - $cents));

        $hist = $this->pdo->prepare('SELECT room, level, started_at, finished_at FROM squad_builds WHERE squad_id = ? ORDER BY id DESC LIMIT 10');
        $hist->execute([$squadId]);

        return [
            'vault' => ['ember' => $ember, 'stamps' => $stamps, 'cents' => $cents, 'cap_cents' => $cap],
            'rooms' => $rooms,
            'build' => $build ? [
                'room' => (string) $build['room'],
                'level' => (int) $build['level'],
                'started_at' => Clock::iso((string) $build['started_at']),
                'finish_at' => Clock::iso((string) $build['finish_at']),
            ] : null,
            'deposit' => [
                'limit_cents' => $limit,
                'used_cents' => $used,
                'remaining_cents' => $remaining,
                'vault_full' => $cents >= $cap,
                'record_cents' => (int) $squad['deposit_record_cents'],
            ],
            'can_build' => $this->canBuild($uid, $squadId),
            'history' => array_map(static fn (array $h): array => [
                'room' => (string) $h['room'],
                'level' => (int) $h['level'],
                'started_at' => Clock::iso((string) $h['started_at']),
                'finished_at' => $h['finished_at'] !== null ? Clock::iso((string) $h['finished_at']) : null,
            ], $hist->fetchAll()),
        ];
    }

    // ---------------------------------------------------------------- wplata

    /** @param array<string,mixed> $user */
    public function deposit(array $user, int $squadId, int $coins, ?DateTimeImmutable $now = null): void
    {
        $this->requireMember($user, $squadId);
        if ($coins < 1) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['coins' => 'Podaj liczbę dukatów (co najmniej 1).']);
        }
        $now ??= Clock::now($this->pdo);
        $uid = (int) $user['id'];
        $cents = $coins * 100;
        $day = Clock::gameDay($now);

        $this->pdo->beginTransaction();
        try {
            $lock = $this->pdo->prepare('SELECT vault_cents, deposit_record_cents FROM squads WHERE id = ? FOR UPDATE');
            $lock->execute([$squadId]);
            $row = $lock->fetch();
            $levels = self::levels($this->pdo, $squadId);
            $limit = self::depositLimitCents($levels);
            $used = $this->depositedToday($uid, $day);
            if ($used + $cents > $limit) {
                throw new ApiException(409, 'deposit_limit', 'Przekroczysz dzienny limit wpłat do magazynu.');
            }
            if ((int) $row['vault_cents'] + $cents > self::vaultCapCents($levels)) {
                throw new ApiException(409, 'vault_full', 'Magazyn ma już dość dukatów na kolejną rozbudowę.');
            }
            Wallet::apply($this->pdo, $uid, 'coins', -$cents, 'squad_deposit', 'squad', $squadId, 'sqdep:' . $squadId . ':' . $uid . ':' . bin2hex(random_bytes(6)));
            $this->pdo->prepare('INSERT INTO squad_deposits (squad_id, user_id, day, cents) VALUES (?, ?, ?, ?)')->execute([$squadId, $uid, $day, $cents]);
            $this->pdo->prepare('UPDATE squads SET vault_cents = vault_cents + ? WHERE id = ?')->execute([$cents, $squadId]);

            $sum = $this->pdo->prepare('SELECT COALESCE(SUM(cents), 0) FROM squad_deposits WHERE squad_id = ? AND day = ?');
            $sum->execute([$squadId, $day]);
            $total = (int) $sum->fetchColumn();
            if ($total > (int) $row['deposit_record_cents'] && $total >= SquadRules::DEPOSIT_RECORD_MIN_CENTS) {
                $this->pdo->prepare('UPDATE squads SET deposit_record_cents = ? WHERE id = ?')->execute([$total, $squadId]);
                // Jeden wpis na dzien; kolejne rekordy tego samego dnia aktualizuja kwote.
                $this->pdo->prepare(
                    'INSERT INTO squad_events (squad_id, type, ref_key, payload) VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE payload = VALUES(payload)'
                )->execute([$squadId, 'deposit_record', 'deposit_record:' . $day, json_encode(['cents' => $total, 'day' => $day], JSON_UNESCAPED_UNICODE)]);
            }
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    // ---------------------------------------------------------------- rozbudowa

    /** @param array<string,mixed> $user */
    public function upgrade(array $user, int $squadId, string $room, ?DateTimeImmutable $now = null): void
    {
        $this->squads->requirePower($user, $squadId, 'build');
        if (!isset(SquadRules::ROOMS[$room])) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['room' => 'Nieznane pomieszczenie.']);
        }
        $now ??= Clock::now($this->pdo);
        self::finalizeDue($this->pdo, $now, $squadId);
        $uid = (int) $user['id'];

        $this->pdo->beginTransaction();
        try {
            $lock = $this->pdo->prepare('SELECT ember, ember_spent, stamps, vault_cents FROM squads WHERE id = ? AND archived_at IS NULL FOR UPDATE');
            $lock->execute([$squadId]);
            $s = $lock->fetch();
            if (!$s) {
                throw new ApiException(404, 'not_found', 'Nie znaleziono oddziału.');
            }
            if ($this->activeBuild($squadId) !== null) {
                throw new ApiException(409, 'build_in_progress', 'Oddział już coś buduje. Poczekaj na zakończenie rozbudowy.');
            }
            $level = self::levels($this->pdo, $squadId)[$room] + 1;
            $cost = self::cost($room, $level);
            $ember = (int) $s['ember'] - (int) $s['ember_spent'];
            if ($ember < $cost['ember'] || (int) $s['stamps'] < $cost['stamps'] || (int) $s['vault_cents'] < $cost['cents']) {
                throw new ApiException(409, 'not_enough_resources', 'W magazynie brakuje składników na tę rozbudowę.');
            }
            $finish = $now->add(new DateInterval('PT' . self::buildHours($level) . 'H'));
            $this->pdo->prepare(
                'UPDATE squads SET ember_spent = ember_spent + ?, stamps = stamps - ?, vault_cents = vault_cents - ? WHERE id = ?'
            )->execute([$cost['ember'], $cost['stamps'], $cost['cents'], $squadId]);
            $this->pdo->prepare(
                'INSERT INTO squad_builds (squad_id, room, level, ember, stamps, cents, started_by, started_at, finish_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([$squadId, $room, $level, $cost['ember'], $cost['stamps'], $cost['cents'], $uid, Clock::fmt($now), Clock::fmt($finish)]);
            SquadChronicle::record($this->pdo, $squadId, 'shelter_start', 'build_start:' . $room . ':' . $level, [
                'room' => $room, 'level' => $level, 'user' => $uid, 'finish_at' => Clock::iso(Clock::fmt($finish)),
            ]);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /** Konczy budowy, ktorych czas minal (wszystkie oddzialy albo jeden). Zwraca liczbe zakonczonych. */
    public static function finalizeDue(PDO $pdo, DateTimeImmutable $now, ?int $squadId = null): int
    {
        $sql = 'SELECT id FROM squad_builds WHERE active = 1 AND finish_at <= ?';
        $args = [Clock::fmt($now)];
        if ($squadId !== null) {
            $sql .= ' AND squad_id = ?';
            $args[] = $squadId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($args);
        $done = 0;
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            try {
                $done += self::finalizeBuild($pdo, (int) $id, $now) ? 1 : 0;
            } catch (Throwable $e) {
                error_log('SquadShelter::finalizeBuild ' . $id . ': ' . $e->getMessage());
            }
        }
        return $done;
    }

    private static function finalizeBuild(PDO $pdo, int $buildId, DateTimeImmutable $now): bool
    {
        $pdo->beginTransaction();
        try {
            $lock = $pdo->prepare('SELECT * FROM squad_builds WHERE id = ? FOR UPDATE');
            $lock->execute([$buildId]);
            $b = $lock->fetch();
            if (!$b || (int) $b['active'] !== 1) {
                $pdo->rollBack();
                return false;
            }
            $pdo->prepare('INSERT INTO squad_rooms (squad_id, room, level) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE level = VALUES(level)')
                ->execute([$b['squad_id'], $b['room'], $b['level']]);
            $pdo->prepare('UPDATE squad_builds SET active = NULL, finished_at = ? WHERE id = ?')->execute([$b['finish_at'], $buildId]);
            SquadChronicle::record($pdo, (int) $b['squad_id'], 'shelter_done', 'build_done:' . $b['room'] . ':' . $b['level'], [
                'room' => (string) $b['room'], 'level' => (int) $b['level'],
            ]);
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    // ---------------------------------------------------------------- pomocnicze

    /** @return array<string,mixed>|null */
    private function activeBuild(int $squadId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM squad_builds WHERE squad_id = ? AND active = 1');
        $stmt->execute([$squadId]);
        return $stmt->fetch() ?: null;
    }

    private function depositedToday(int $userId, string $day): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(cents), 0) FROM squad_deposits WHERE user_id = ? AND day = ?');
        $stmt->execute([$userId, $day]);
        return (int) $stmt->fetchColumn();
    }

    private function canBuild(int $userId, int $squadId): bool
    {
        $m = $this->squads->memberRow($userId);
        return $m && (int) $m['squad_id'] === $squadId && in_array((string) $m['role'], ['leader', 'deputy', 'quartermaster'], true);
    }

    /** @param array<string,mixed> $user */
    private function requireMember(array $user, int $squadId): void
    {
        $m = $this->squads->memberRow((int) $user['id']);
        if (!$m || (int) $m['squad_id'] !== $squadId) {
            throw new ApiException(403, 'forbidden', 'Ta sekcja jest dostępna tylko dla członków oddziału.');
        }
        $this->squads->squad($squadId);
    }
}
