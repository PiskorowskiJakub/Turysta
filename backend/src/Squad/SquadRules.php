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
    public const FIRE_BASE_EMBER = 100;
    /** Dni serii potrzebne na kolejne poziomy plomienia: iskra, plomien, ognisko, watra, wielka watra. */
    public const FLAME_STREAKS = [0, 3, 7, 14, 30];
    /** Premia serii w procentach dla poziomow plomienia. */
    public const FLAME_BONUS_PERCENT = [0, 5, 10, 15, 20];

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
