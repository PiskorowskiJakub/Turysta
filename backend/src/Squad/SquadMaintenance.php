<?php
declare(strict_types=1);

namespace Turysta\Squad;

use DateTimeImmutable;
use PDO;
use Turysta\Auth\RateLimiter;
use Turysta\Game\Clock;

/**
 * Codzienne porzadki oddzialow (wywolywane z tick.php): rozliczenie ogniska, automatyczna zastepczosc i archiwizacja.
 * - Przywodca nieaktywny 14 dni: Zastepca zostaje Przywodca; gdy go nie ma, najaktywniejszy czlonek z ostatnich 30 dni.
 * - Oddzial bez zadnej aktywnosci przez 30 dni trafia do archiwum, a nazwa wraca do puli.
 * - Oddzial bez Przywodcy (np. po usunieciu konta) dostaje nowego wedlug tych samych zasad.
 */
final class SquadMaintenance
{
    public static function run(PDO $pdo): void
    {
        SquadFire::processPending($pdo);
        // Wyprawy z minionych tygodni rozliczamy zaraz po polnocy (rozliczone leniwie przy wejsciu gracza tez sa bezpieczne).
        SquadTrail::finalizeDue($pdo, Clock::now($pdo));

        // Zastepczosc i archiwizacja raz na dobe: o 00:11 czasu gry (po rozliczeniu ognisk).
        $now = Clock::now($pdo);
        if ($now->setTimezone(new \DateTimeZone(Clock::GAME_TZ))->format('G:i') !== '0:11') {
            return;
        }
        self::succession($pdo, $now);
        self::archiveInactive($pdo, $now);
    }

    public static function succession(PDO $pdo, DateTimeImmutable $now): void
    {
        $today = Clock::gameDay($now);
        $limit = (new DateTimeImmutable($today))->modify('-' . SquadRules::LEADER_INACTIVE_DAYS . ' days')->format('Y-m-d');
        $service = new SquadService($pdo, new RateLimiter($pdo));

        $stmt = $pdo->query('SELECT id FROM squads WHERE archived_at IS NULL');
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $squadId = (int) $id;
            $leader = $pdo->prepare("SELECT m.user_id, m.joined_at, st.last_active_day FROM squad_members m LEFT JOIN user_stats st ON st.user_id = m.user_id WHERE m.squad_id = ? AND m.role = 'leader'");
            $leader->execute([$squadId]);
            $l = $leader->fetch();

            $members = (int) $pdo->query("SELECT COUNT(*) FROM squad_members WHERE squad_id = $squadId")->fetchColumn();
            if ($members === 0) {
                $service->archive($squadId, false);
                continue;
            }
            if ($l) {
                $seen = $l['last_active_day'] ?? Clock::gameDay(Clock::parse((string) $l['joined_at']));
                if ((string) $seen >= $limit) {
                    continue;
                }
            }

            $new = self::pickSuccessor($pdo, $squadId, $l ? (int) $l['user_id'] : null, $today);
            if ($new !== null) {
                $service->promoteLeader($squadId, $new, $l ? (int) $l['user_id'] : null, 'succession');
            }
        }
    }

    /** Zastepca (jesli aktywny), a w przeciwnym razie najaktywniejszy czlonek z ostatnich 30 dni. */
    private static function pickSuccessor(PDO $pdo, int $squadId, ?int $exceptLeader, string $today): ?int
    {
        $from = (new DateTimeImmutable($today))->modify('-30 days')->format('Y-m-d');
        $stmt = $pdo->prepare(
            "SELECT m.user_id, m.role, COUNT(d.day) AS days
             FROM squad_members m LEFT JOIN user_active_days d ON d.user_id = m.user_id AND d.day >= ?
             WHERE m.squad_id = ? AND m.user_id <> ?
             GROUP BY m.user_id, m.role"
        );
        $stmt->execute([$from, $squadId, $exceptLeader ?? 0]);
        $best = null;
        $bestDays = 0;
        foreach ($stmt->fetchAll() as $r) {
            $days = (int) $r['days'];
            if ($r['role'] === 'deputy' && $days > 0) {
                return (int) $r['user_id'];
            }
            if ($days > $bestDays) {
                $best = (int) $r['user_id'];
                $bestDays = $days;
            }
        }
        return $best;
    }

    public static function archiveInactive(PDO $pdo, DateTimeImmutable $now): void
    {
        $today = Clock::gameDay($now);
        $from = (new DateTimeImmutable($today))->modify('-' . SquadRules::ARCHIVE_INACTIVE_DAYS . ' days')->format('Y-m-d');
        $service = new SquadService($pdo, new RateLimiter($pdo));
        $stmt = $pdo->prepare(
            'SELECT s.id FROM squads s
             WHERE s.archived_at IS NULL AND s.created_at < UTC_TIMESTAMP() - INTERVAL ' . SquadRules::ARCHIVE_INACTIVE_DAYS . ' DAY
               AND NOT EXISTS (SELECT 1 FROM squad_members m JOIN user_active_days d ON d.user_id = m.user_id AND d.day >= ? WHERE m.squad_id = s.id)'
        );
        $stmt->execute([$from]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $service->archive((int) $id, false);
        }
    }
}
