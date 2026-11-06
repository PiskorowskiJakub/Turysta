<?php
declare(strict_types=1);

namespace Turysta\Squad;

use PDO;
use Throwable;
use Turysta\Game\Clock;

/**
 * Odznaki oddzialu za wspolne cele, w tych samych stopniach co odznaki graczy (braz, srebro, zloto, diament, rubin).
 * Wartosc liczymy na biezaco, a zdobyty stopien zapisujemy (stopnie tylko rosna, wiec odejscie czlonkow ich nie odbiera).
 * Odznaki sa publiczne: widac je na profilu oddzialu w katalogu.
 */
final class SquadBadges
{
    /**
     * Nazwy stopni od najnizszego. Ostatnie trzy sa zarezerwowane: zadna odznaka ich jeszcze nie uzywa.
     * Nowy stopien odznaki to po prostu kolejny prog w BADGES (do liczby pozycji w TIERS).
     */
    public const TIERS = ['bronze', 'silver', 'gold', 'diamond', 'ruby', 'amethyst', 'obsidian', 'aurora'];

    /** Odznaka => progi kolejnych stopni. fire_streak: najlepsza seria ogniska (dni); trails: wyprawy z pieczatkami; shelter: suma poziomow pomieszczen; skills: suma poziomow umiejetnosci oddzialowych czlonkow. */
    public const BADGES = [
        'fire_streak' => [7, 30, 100, 250, 500],
        'trails' => [3, 10, 30, 75, 150],
        'shelter' => [5, 15, 40, 80, 150],
        'skills' => [100, 500, 2000, 6000, 15000],
    ];

    /** @return array<string,int> */
    public static function metrics(PDO $pdo, int $squadId): array
    {
        $best = $pdo->prepare('SELECT best_streak FROM squads WHERE id = ?');
        $best->execute([$squadId]);
        $trails = $pdo->prepare("SELECT COUNT(*) FROM squad_trails WHERE squad_id = ? AND status = 'done' AND stamps > 0");
        $trails->execute([$squadId]);
        $skills = $pdo->prepare('SELECT COALESCE(SUM(s.level), 0) FROM user_squad_skills s JOIN squad_members m ON m.user_id = s.user_id WHERE m.squad_id = ?');
        $skills->execute([$squadId]);
        return [
            'fire_streak' => (int) $best->fetchColumn(),
            'trails' => (int) $trails->fetchColumn(),
            'shelter' => array_sum(SquadShelter::levels($pdo, $squadId)),
            'skills' => (int) $skills->fetchColumn(),
        ];
    }

    /** Stopien (0 = brak, 1..liczba progow) dla wartosci. @param list<int> $tiers */
    public static function tierFor(array $tiers, int $value): int
    {
        $tier = 0;
        foreach ($tiers as $i => $min) {
            if ($value >= $min) {
                $tier = $i + 1;
            }
        }
        return $tier;
    }

    /** Zapisuje nowo zdobyte stopnie, wpis w kronice i powiadomienie. Blad nie moze zablokowac reszty (np. przed migracja). */
    public static function sync(PDO $pdo, int $squadId): void
    {
        try {
            $metrics = self::metrics($pdo, $squadId);
            $stored = $pdo->prepare('SELECT code, tier FROM squad_badges WHERE squad_id = ?');
            $stored->execute([$squadId]);
            $have = [];
            foreach ($stored->fetchAll() as $r) {
                $have[(string) $r['code']] = (int) $r['tier'];
            }
            foreach (self::BADGES as $code => $tiers) {
                $tier = self::tierFor($tiers, $metrics[$code]);
                $old = $have[$code] ?? 0;
                if ($tier <= $old) {
                    continue;
                }
                $pdo->prepare(
                    'INSERT INTO squad_badges (squad_id, code, tier, earned_at) VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE tier = VALUES(tier), earned_at = VALUES(earned_at)'
                )->execute([$squadId, $code, $tier, Clock::fmt(Clock::now($pdo))]);
                for ($t = $old + 1; $t <= $tier; $t++) {
                    SquadChronicle::record($pdo, $squadId, 'badge', 'badge:' . $code . ':' . $t, ['code' => $code, 'tier' => $t]);
                }
                SquadNotifier::toMembers($pdo, $squadId, 'squad_badge', ['code' => $code, 'tier' => $tier]);
            }
        } catch (Throwable $e) {
            error_log('SquadBadges::sync: ' . $e->getMessage());
        }
    }

    /** @return list<array<string,mixed>> */
    public static function view(PDO $pdo, int $squadId): array
    {
        self::sync($pdo, $squadId);
        $metrics = self::metrics($pdo, $squadId);
        $rows = [];
        try {
            $stmt = $pdo->prepare('SELECT code, tier, earned_at FROM squad_badges WHERE squad_id = ?');
            $stmt->execute([$squadId]);
            foreach ($stmt->fetchAll() as $r) {
                $rows[(string) $r['code']] = $r;
            }
        } catch (Throwable $e) {
            error_log('SquadBadges::view: ' . $e->getMessage());
        }
        $out = [];
        foreach (self::BADGES as $code => $tiers) {
            $tier = (int) ($rows[$code]['tier'] ?? 0);
            $out[] = [
                'code' => $code,
                'value' => $metrics[$code],
                'tier' => $tier,
                'tier_name' => $tier > 0 ? self::TIERS[$tier - 1] : null,
                'earned_at' => $tier > 0 ? Clock::iso((string) $rows[$code]['earned_at']) : null,
                'next_min' => $tiers[$tier] ?? null,
                'tiers' => $tiers,
            ];
        }
        return $out;
    }
}
