<?php
declare(strict_types=1);

namespace Turysta\Squad;

use PDO;
use Turysta\Http\ApiException;

/**
 * Izba pamieci: kosmetyka i prestiz oddzialu. Poziom pomieszczenia odblokowuje emblematy (im wyzszy poziom, tym bardziej
 * szczegolowe), ramki, miejsca w gablocie wyroznionych odznak i kolejne rozdzialy Ksiegi wypraw. Nie wplywa na ekonomie gry.
 */
final class SquadMemory
{
    /** Emblemat => najnizszy poziom Izby pamieci, na ktorym jest dostepny. */
    public const EMBLEMS = [
        'compass' => 1, 'flame' => 1, 'peak' => 1,
        'tent' => 3, 'pine' => 3,
        'lantern' => 6, 'boot' => 6,
        'eagle' => 10, 'scroll' => 10,
        'stag' => 15, 'tower' => 15,
        'phoenix' => 20,
    ];

    /** Ramka => najnizszy poziom Izby pamieci (stopnie jak w odznakach). */
    public const FRAMES = ['bronze' => 1, 'silver' => 4, 'gold' => 8, 'diamond' => 14, 'ruby' => 20];

    /** Poziomy Izby pamieci, od ktorych gablota mieści kolejno 1, 2, 3 i 4 odznaki. */
    public const SHOWCASE_SLOTS = [1 => 1, 3 => 2, 6 => 3, 10 => 4];

    /** Rozdzial Ksiegi => poziom, od ktorego jest dostepny. */
    public const BOOK = ['records' => 1, 'recent' => 2, 'milestones' => 4, 'walkers' => 7, 'full' => 10];

    public const BOOK_RECENT = 5;
    public const BOOK_FULL = 52;
    public const BOOK_WALKERS = 5;

    public static function level(PDO $pdo, int $squadId): int
    {
        return SquadShelter::levels($pdo, $squadId)['memory'];
    }

    public static function slots(int $level): int
    {
        $slots = 0;
        foreach (self::SHOWCASE_SLOTS as $min => $n) {
            if ($level >= $min) {
                $slots = $n;
            }
        }
        return $slots;
    }

    /** @return list<string> kody odznak z gabloty (tylko zdobyte i miesczace sie w limicie) */
    public static function showcase(?string $stored, int $slots, array $earned): array
    {
        $out = [];
        foreach (array_filter(explode(',', (string) $stored)) as $code) {
            if (count($out) < $slots && in_array($code, $earned, true) && !in_array($code, $out, true)) {
                $out[] = $code;
            }
        }
        return $out;
    }

    /**
     * Stan Izby pamieci widoczny w profilu oddzialu (publiczny: poziom jest jawny tak samo jak poziomy schroniska).
     *
     * @param array<string,mixed> $squad wiersz squads
     * @return array<string,mixed>
     */
    public static function view(PDO $pdo, array $squad, int $level): array
    {
        $emblems = [];
        foreach (self::EMBLEMS as $code => $min) {
            $emblems[] = ['code' => $code, 'min_level' => $min, 'unlocked' => $level >= $min];
        }
        $frames = [];
        foreach (self::FRAMES as $code => $min) {
            $frames[] = ['code' => $code, 'min_level' => $min, 'unlocked' => $level >= $min];
        }
        return [
            'level' => $level,
            'emblem' => self::emblemOf($squad, $level),
            'frame' => self::frameOf($squad, $level),
            'slots' => self::slots($level),
            'next_slot_level' => self::nextSlotLevel($level),
            'emblems' => $emblems,
            'frames' => $frames,
            'book' => self::bookChapters($level),
        ];
    }

    /**
     * Wyglad oddzialu do pokazania przy jego nazwie (katalog, znajomi, profile graczy).
     *
     * @param array<string,mixed> $squad wiersz squads (id, emblem, frame)
     * @return array{emblem:?string,frame:?string}
     */
    public static function appearance(PDO $pdo, array $squad): array
    {
        if (empty($squad['emblem']) && empty($squad['frame'])) {
            return ['emblem' => null, 'frame' => null];
        }
        $level = self::level($pdo, (int) $squad['id']);
        return ['emblem' => self::emblemOf($squad, $level), 'frame' => self::frameOf($squad, $level)];
    }

    /**
     * Oddzialy wielu graczy naraz (rankingi): user_id => {id, display_name, emblem, frame}; gracze bez oddzialu sa pominieci.
     *
     * @param list<int> $userIds
     * @return array<int,array{id:int,display_name:string,emblem:?string,frame:?string}>
     */
    public static function forUsers(PDO $pdo, array $userIds): array
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if (!$userIds) {
            return [];
        }
        $in = implode(',', array_fill(0, count($userIds), '?'));
        $stmt = $pdo->prepare(
            "SELECT m.user_id, s.id, s.name, s.specialization, s.emblem, s.frame, COALESCE(r.level, 0) AS memory_level
             FROM squad_members m
             JOIN squads s ON s.id = m.squad_id AND s.archived_at IS NULL
             LEFT JOIN squad_rooms r ON r.squad_id = s.id AND r.room = 'memory'
             WHERE m.user_id IN ($in)"
        );
        $stmt->execute($userIds);
        $out = [];
        foreach ($stmt->fetchAll() as $r) {
            $level = (int) $r['memory_level'];
            $out[(int) $r['user_id']] = [
                'id' => (int) $r['id'],
                'display_name' => SquadRules::displayName((string) $r['name'], (string) $r['specialization']),
                'emblem' => self::emblemOf($r, $level),
                'frame' => self::frameOf($r, $level),
            ];
        }
        return $out;
    }

    /** Emblemat i ramka, o ile oddzial nadal ma poziom, ktory je odblokowuje. */
    public static function emblemOf(array $squad, int $level): ?string
    {
        $code = $squad['emblem'] ?? null;
        return is_string($code) && isset(self::EMBLEMS[$code]) && $level >= self::EMBLEMS[$code] ? $code : null;
    }

    public static function frameOf(array $squad, int $level): ?string
    {
        $code = $squad['frame'] ?? null;
        return is_string($code) && isset(self::FRAMES[$code]) && $level >= self::FRAMES[$code] ? $code : null;
    }

    private static function nextSlotLevel(int $level): ?int
    {
        foreach (self::SHOWCASE_SLOTS as $min => $_n) {
            if ($min > $level) {
                return $min;
            }
        }
        return null;
    }

    /** @return list<array{code:string,min_level:int,unlocked:bool}> */
    private static function bookChapters(int $level): array
    {
        $out = [];
        foreach (self::BOOK as $code => $min) {
            $out[] = ['code' => $code, 'min_level' => $min, 'unlocked' => $level >= $min];
        }
        return $out;
    }

    /**
     * Zmiany z ustawien oddzialu: waliduje emblemat, ramke i gablote wedlug poziomu Izby pamieci.
     *
     * @param array<string,mixed> $squad wiersz squads
     * @param array<string,mixed> $in tresc zadania
     * @return array{0:list<string>,1:list<mixed>} fragmenty SET i parametry
     */
    public static function changes(PDO $pdo, array $squad, array $in): array
    {
        $touched = array_key_exists('emblem', $in) || array_key_exists('frame', $in) || array_key_exists('showcase', $in);
        if (!$touched) {
            return [[], []];
        }
        $level = self::level($pdo, (int) $squad['id']);
        $sets = [];
        $params = [];
        if (array_key_exists('emblem', $in) && $in['emblem'] !== ($squad['emblem'] ?? null)) {
            $code = $in['emblem'];
            if ($code !== null && (!is_string($code) || !isset(self::EMBLEMS[$code]) || $level < self::EMBLEMS[$code])) {
                throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['emblem' => 'Ten emblemat nie jest jeszcze odblokowany.']);
            }
            $sets[] = 'emblem = ?';
            $params[] = $code;
        }
        if (array_key_exists('frame', $in) && $in['frame'] !== ($squad['frame'] ?? null)) {
            $code = $in['frame'];
            if ($code !== null && (!is_string($code) || !isset(self::FRAMES[$code]) || $level < self::FRAMES[$code])) {
                throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['frame' => 'Ta ramka nie jest jeszcze odblokowana.']);
            }
            $sets[] = 'frame = ?';
            $params[] = $code;
        }
        if (array_key_exists('showcase', $in)) {
            $codes = $in['showcase'];
            if (!is_array($codes)) {
                throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['showcase' => 'Nieprawidłowa lista odznak.']);
            }
            $earned = self::earnedCodes($pdo, (int) $squad['id']);
            $slots = self::slots($level);
            $clean = [];
            foreach ($codes as $c) {
                if (!is_string($c) || !in_array($c, $earned, true) || in_array($c, $clean, true)) {
                    throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['showcase' => 'Wyróżnić można tylko zdobyte odznaki, każdą raz.']);
                }
                $clean[] = $c;
            }
            if (count($clean) > $slots) {
                throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['showcase' => 'Gablota mieści teraz odznak: ' . $slots . '.']);
            }
            $sets[] = 'showcase = ?';
            $params[] = $clean ? implode(',', $clean) : null;
        }
        return [$sets, $params];
    }

    /** @return list<string> */
    public static function earnedCodes(PDO $pdo, int $squadId): array
    {
        $stmt = $pdo->prepare('SELECT code FROM squad_badges WHERE squad_id = ?');
        $stmt->execute([$squadId]);
        return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Ksiega wypraw rosnie razem z Izba pamieci: kolejne rozdzialy odblokowuja sie z poziomem.
     *
     * @param array<string,mixed> $squad wiersz squads
     * @return array<string,mixed>
     */
    public static function book(PDO $pdo, array $squad): array
    {
        $squadId = (int) $squad['id'];
        $level = self::level($pdo, $squadId);
        $out = ['level' => $level, 'chapters' => self::bookChapters($level)];

        $stats = $pdo->prepare("SELECT COUNT(*) AS trails, COALESCE(SUM(stamps), 0) AS stamps FROM squad_trails WHERE squad_id = ? AND status = 'done'");
        $stats->execute([$squadId]);
        $s = $stats->fetch() ?: ['trails' => 0, 'stamps' => 0];
        if ($level >= self::BOOK['records']) {
            $best = $pdo->prepare("SELECT week_start, difficulty, theme, percent, stamps FROM squad_trails WHERE squad_id = ? AND status = 'done' ORDER BY stamps DESC, percent DESC, week_start DESC LIMIT 1");
            $best->execute([$squadId]);
            $b = $best->fetch() ?: null;
            $out['records'] = [
                'founded' => (string) $squad['created_at'],
                'best_streak' => (int) $squad['best_streak'],
                'lit_days' => (int) $squad['lit_days'],
                'trails_done' => (int) $s['trails'],
                'stamps_total' => (int) $s['stamps'],
                'best_trail' => $b ? self::trailRow($b) : null,
            ];
        }
        if ($level >= self::BOOK['recent']) {
            $limit = $level >= self::BOOK['full'] ? self::BOOK_FULL : self::BOOK_RECENT;
            $stmt = $pdo->prepare("SELECT week_start, difficulty, theme, percent, stamps, participants FROM squad_trails WHERE squad_id = ? AND status = 'done' ORDER BY week_start DESC LIMIT " . $limit);
            $stmt->execute([$squadId]);
            $out['trails'] = array_map(fn(array $r): array => self::trailRow($r), $stmt->fetchAll());
        }
        if ($level >= self::BOOK['milestones']) {
            $out['milestones'] = self::milestones($pdo, $squad);
        }
        if ($level >= self::BOOK['walkers']) {
            $stmt = $pdo->prepare(
                "SELECT u.id, u.name, COUNT(*) AS trails, COALESCE(SUM(m.march_days), 0) AS march_days
                 FROM squad_trail_members m
                 JOIN squad_trails t ON t.id = m.trail_id AND t.squad_id = ? AND t.status = 'done'
                 JOIN users u ON u.id = m.user_id
                 WHERE m.eligible = 1
                 GROUP BY u.id, u.name ORDER BY trails DESC, march_days DESC, u.id ASC LIMIT " . self::BOOK_WALKERS
            );
            $stmt->execute([$squadId]);
            $out['walkers'] = array_map(static fn(array $r): array => [
                'id' => (int) $r['id'], 'name' => (string) $r['name'], 'trails' => (int) $r['trails'], 'march_days' => (int) $r['march_days'],
            ], $stmt->fetchAll());
        }
        return $out;
    }

    /** @param array<string,mixed> $r @return array<string,mixed> */
    private static function trailRow(array $r): array
    {
        $theme = SquadTrailThemes::get((string) $r['theme']);
        return [
            'week_start' => (string) $r['week_start'],
            'difficulty' => (string) $r['difficulty'],
            'theme' => $theme['name'] ?? (string) $r['theme'],
            'percent' => (float) $r['percent'],
            'stamps' => (int) $r['stamps'],
            'participants' => isset($r['participants']) ? (int) $r['participants'] : null,
        ];
    }

    /** Kamienie milowe: zalozenie, pierwsza wyprawa i pierwsze odznaki. @param array<string,mixed> $squad @return list<array<string,mixed>> */
    private static function milestones(PDO $pdo, array $squad): array
    {
        $squadId = (int) $squad['id'];
        $out = [['kind' => 'founded', 'at' => (string) $squad['created_at'], 'ref' => null]];
        $first = $pdo->prepare("SELECT finalized_at FROM squad_trails WHERE squad_id = ? AND status = 'done' AND stamps > 0 ORDER BY week_start ASC LIMIT 1");
        $first->execute([$squadId]);
        $at = $first->fetchColumn();
        if (is_string($at)) {
            $out[] = ['kind' => 'first_trail', 'at' => $at, 'ref' => null];
        }
        $stmt = $pdo->prepare('SELECT code, tier, earned_at FROM squad_badges WHERE squad_id = ? ORDER BY earned_at ASC, code ASC');
        $stmt->execute([$squadId]);
        foreach ($stmt->fetchAll() as $b) {
            $out[] = ['kind' => 'badge', 'at' => (string) $b['earned_at'], 'ref' => (string) $b['code'], 'tier' => (int) $b['tier']];
        }
        usort($out, static fn(array $a, array $b): int => strcmp((string) $a['at'], (string) $b['at']));
        return $out;
    }
}
