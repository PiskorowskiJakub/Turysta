<?php
declare(strict_types=1);

namespace Turysta\Squad;

/** Stale zasad oddzialow (patrz docs w projekcie mechanik oddzialu). */
final class SquadRules
{
    public const MAX_MEMBERS = 30;
    /** Minimalny wiek konta (dni gry, wlacznie z dniem zalozenia) do zalozenia oddzialu i do dolaczenia. */
    public const MIN_ACCOUNT_DAYS = 3;
    /** Karencja na dolaczenie do (lub zalozenie) oddzialu po wyjsciu albo wyrzuceniu. */
    public const REJOIN_COOLDOWN_DAYS = 3;
    public const RECRUIT_DAYS = 7;
    public const NAME_MIN = 3;
    public const NAME_MAX = 40;
    public const NAME_CHANGE_DAYS = 30;
    public const SPEC_CHANGE_DAYS = 90;
    public const MAX_PENDING_REQUESTS_PER_USER = 5;
    public const MAX_PENDING_INVITES_PER_SQUAD = 20;
    public const CHRONICLE_LIMIT = 40;
    public const CHRONICLE_DAYS = 90;

    // Ognisko
    public const FIRE_THRESHOLD_PERCENT = 30;
    public const FIRE_MIN_ACTIVE = 2;
    public const FIRE_BASE_EMBER = 10;
    /** Dni serii potrzebne na kolejne poziomy plomienia: iskra, plomien, ognisko, watra, wielka watra. */
    public const FLAME_STREAKS = [0, 3, 7, 14, 30];
    /** Premia serii w procentach dla poziomow plomienia. */
    public const FLAME_BONUS_PERCENT = [0, 5, 10, 15, 20];

    // Szlaki (tygodniowe wyprawy)
    /** Wpisowe do wyprawy w setnych dukata (2 dk). Znika z obiegu. */
    public const TRAIL_FEE_CENTS = 200;
    /** Dolaczyc mozna od poniedzialku do srody wlacznie (indeks dnia tygodnia 0 = poniedzialek). */
    public const TRAIL_JOIN_LAST_DAY = 2;
    /** Poziom trudnosci mozna zmieniac do konca poniedzialku. */
    public const TRAIL_PLAN_LAST_DAY = 0;
    public const TRAIL_MIN_ACTIVE_DAYS = 3;
    /** Minimum uprawnionych uczestnikow, zeby oddzial dostal pieczatki. */
    public const TRAIL_MIN_ELIGIBLE = 2;
    /** Dlugosc trasy w km wg trudnosci. */
    public const TRAIL_LENGTH_KM = ['easy' => 30, 'medium' => 50, 'hard' => 80];
    /** Progi postepu (%) kolejnych pieczatek wg trudnosci. */
    public const TRAIL_THRESHOLDS = [
        'easy' => [40, 60, 75, 88],
        'medium' => [40, 65, 80, 92],
        'hard' => [40, 70, 88, 97],
    ];
    /** Maksimum pieczatek wg trudnosci (trudna trasa daje piata za ostatni prog). */
    public const TRAIL_MAX_STAMPS = ['easy' => 3, 'medium' => 4, 'hard' => 5];
    /** Nagroda gracza za kazda pieczatke oddzialu (w setnych dukata): 0,50 dk. */
    public const TRAIL_REWARD_PER_STAMP_CENTS = 50;
    public const TRAIL_DIFFICULTIES = ['easy', 'medium', 'hard'];
    public const TRAIL_HISTORY = 12;

    // Schronisko (magazyn i pomieszczenia bez maksymalnego poziomu)
    /** Pomieszczenia: kod => mnoznik ceny (Izba pamieci kosztuje 60%). */
    public const ROOMS = ['kitchen' => 1.0, 'pantry' => 1.0, 'infirmary' => 1.0, 'mapping' => 1.0, 'memory' => 0.6];
    /** Koszt poziomu 1 (zar, pieczatki, dukaty) i wykladniki wzrostu kosztu: koszt poziomu n = baza x n^wykladnik. */
    public const ROOM_BASE_EMBER = 240;
    public const ROOM_BASE_STAMPS = 6;
    public const ROOM_BASE_COINS = 600;
    public const ROOM_EXPONENT = 1.2;
    public const ROOM_STAMPS_EXPONENT = 1.1;
    /** Budowa poziomu n trwa n x 12 godzin. */
    public const ROOM_HOURS_PER_LEVEL = 12;
    /** Dzienny limit wplat na osobe = srednia cena dukatowa kolejnych poziomow / ten dzielnik (min. ROOM_DEPOSIT_MIN_COINS dk). */
    public const DEPOSIT_DIVISOR = 60;
    public const DEPOSIT_MIN_COINS = 5;
    /** Rekord wplat oddzialu trafia do kroniki dopiero od tej kwoty w ciagu dnia (w setnych dukata: 50 dk). */
    public const DEPOSIT_RECORD_MIN_CENTS = 5000;
    /** Efekty maleja asymptotycznie: maksimum x L / (L + polowa). Kuchnia: wyplaty z prac, Mapownia: pieczatki z wypraw. */
    public const KITCHEN_MAX_PERCENT = 5.0;
    public const KITCHEN_HALF = 7;
    public const MAPPING_MAX_PERCENT = 25.0;
    public const MAPPING_HALF = 10;

    // Zastepczosc
    public const ROLE_INACTIVE_DAYS = 7;
    public const LEADER_INACTIVE_DAYS = 14;
    public const ARCHIVE_INACTIVE_DAYS = 30;

    /** Role: kod => limit osob (null = bez limitu). */
    public const ROLE_LIMITS = [
        'leader' => 1,
        'deputy' => 1,
        'guide' => 2,
        'quartermaster' => 2,
        'chronicler' => 2,
        'recruiter' => 2,
        'member' => null,
    ];

    /** Role, ktore mozna nadac (wszystkie poza przywodca i zwyklym czlonkiem, ktore maja osobne akcje). */
    public const ASSIGNABLE_ROLES = ['deputy', 'guide', 'quartermaster', 'chronicler', 'recruiter', 'member'];

    public const KICK_REASONS = ['inactive', 'rules', 'other'];
    public const REPORT_REASONS = ['vulgar', 'offensive', 'impersonation', 'other'];

    /** Specjalizacje: kod => [grupa, nazwa]. Kolejnosc jest kolejnoscia na liscie wyboru. */
    public const SPECIALIZATIONS = [
        'piesza' => ['movement', 'turystyka piesza'],
        'gorska' => ['movement', 'turystyka górska'],
        'rowerowa' => ['movement', 'turystyka rowerowa'],
        'narciarska' => ['movement', 'turystyka narciarska i zimowa'],
        'jaskiniowa' => ['movement', 'turystyka jaskiniowa'],
        'kajakowa' => ['water', 'turystyka kajakowa'],
        'zeglarska' => ['water', 'turystyka żeglarska'],
        'kolejowa' => ['travel', 'turystyka kolejowa'],
        'podroznicza' => ['travel', 'turystyka podróżnicza'],
        'krajoznawstwo' => ['sightseeing', 'krajoznawstwo'],
        'historyczna' => ['sightseeing', 'turystyka historyczna'],
        'przyrodnicza' => ['sightseeing', 'turystyka przyrodnicza'],
        'kulturalna' => ['sightseeing', 'turystyka kulturalna'],
        'miejska' => ['sightseeing', 'turystyka miejska'],
        'fotograficzna' => ['other', 'turystyka fotograficzna'],
        'survival' => ['other', 'survival i bushcraft'],
        'kulinarna' => ['other', 'turystyka kulinarna'],
        'ogolna' => ['general', 'ogólna'],
    ];

    /** @return list<array{code:string,group:string,label:string}> */
    public static function specializations(): array
    {
        $out = [];
        foreach (self::SPECIALIZATIONS as $code => [$group, $label]) {
            $out[] = ['code' => $code, 'group' => $group, 'label' => $label];
        }
        return $out;
    }

    /** Pelna nazwa wyswietlana: "Oddzial <nazwa wlasna> - <specjalizacja>" (dla "ogolnej" bez przyrostka). */
    public static function displayName(string $name, string $spec): string
    {
        $label = self::SPECIALIZATIONS[$spec][1] ?? null;
        $base = 'Oddział ' . $name;
        return ($spec === 'ogolna' || $label === null) ? $base : $base . ' – ' . $label;
    }
}
