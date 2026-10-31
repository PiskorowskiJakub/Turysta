<?php
declare(strict_types=1);

namespace Turysta\Game;

use DateTimeImmutable;
use DateTimeZone;
use PDO;

final class Clock
{
    /** Czas z bazy (UTC): jedno zrodlo prawdy dla wszystkich kontenerow. */
    public static function now(PDO $pdo): DateTimeImmutable
    {
        return new DateTimeImmutable((string) $pdo->query('SELECT UTC_TIMESTAMP()')->fetchColumn(), new DateTimeZone('UTC'));
    }

    public static function fmt(DateTimeImmutable $d): string
    {
        return $d->format('Y-m-d H:i:s');
    }

    public static function parse(string $db): DateTimeImmutable
    {
        return new DateTimeImmutable($db, new DateTimeZone('UTC'));
    }

    public static function iso(?string $db): ?string
    {
        return $db === null ? null : str_replace(' ', 'T', $db) . 'Z';
    }
}
