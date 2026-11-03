<?php
declare(strict_types=1);

namespace Turysta\Squad;

use PDO;

/** Normalizacja i moderacja nazw oddzialow: klucz unikalnosci oraz filtr slow zakazanych. */
final class NameFilter
{
    private const DIACRITICS = [
        'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z',
    ];
    /** Typowe podmiany znakow w obchodzeniu filtra. */
    private const LEET = ['0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '5' => 's', '7' => 't', '@' => 'a', '$' => 's'];

    /** Male litery, bez polskich znakow; zostawia litery, cyfry i spacje. */
    public static function fold(string $s): string
    {
        $s = mb_strtolower($s);
        return strtr($s, self::DIACRITICS);
    }

    /** Klucz unikalnosci nazwy: bez wielkosci liter, polskich znakow, spacji i interpunkcji. */
    public static function uniqueKey(string $name): string
    {
        return (string) preg_replace('/[^a-z0-9]/', '', self::fold($name));
    }

    /** Postac do sprawdzania slow zakazanych: jak klucz, ale z odwroceniem typowych podmian (0 na o, 3 na e...). */
    public static function screenKey(string $name): string
    {
        $folded = strtr(self::fold($name), self::LEET);
        return (string) preg_replace('/[^a-z]/', '', $folded);
    }

    /** Klucz slowa dodawanego do listy zakazanych. */
    public static function wordKey(string $word): string
    {
        return self::screenKey($word);
    }

    /** True, gdy nazwa zawiera slowo zakazane. */
    public static function isBanned(PDO $pdo, string $name): bool
    {
        $screen = self::screenKey($name);
        if ($screen === '') {
            return false;
        }
        foreach ($pdo->query('SELECT word_key FROM banned_words')->fetchAll(PDO::FETCH_COLUMN) as $word) {
            if ($word !== '' && str_contains($screen, (string) $word)) {
                return true;
            }
        }
        return false;
    }

    /** @return string|null komunikat bledu albo null, gdy nazwa ma poprawny format */
    public static function formatError(string $name): ?string
    {
        $len = mb_strlen($name);
        if ($len < SquadRules::NAME_MIN || $len > SquadRules::NAME_MAX) {
            return 'Nazwa oddziału musi mieć od ' . SquadRules::NAME_MIN . ' do ' . SquadRules::NAME_MAX . ' znaków.';
        }
        if (!preg_match('/^[\p{L}\p{N} .,\'\-!?()&]+$/u', $name) || str_contains($name, '  ') || self::uniqueKey($name) === '') {
            return 'Dozwolone są litery, cyfry, spacje i podstawowa interpunkcja (. , \' - ! ? ( ) &).';
        }
        if (preg_match('/pttk|polskie\s+towarzystwo\s+turystyczno/iu', $name) || str_contains(self::uniqueKey($name), 'pttk')) {
            return 'Nazwa nie może sugerować, że oddział jest oficjalną jednostką istniejącej organizacji.';
        }
        return null;
    }
}
