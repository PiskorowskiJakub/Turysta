<?php
declare(strict_types=1);

namespace Turysta;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=3306;dbname=%s;charset=utf8mb4',
                getenv('DB_HOST') ?: 'db',
                getenv('DB_NAME') ?: 'turysta'
            );
            self::$pdo = new PDO($dsn, getenv('DB_USER') ?: 'turysta', getenv('DB_PASSWORD') ?: '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            self::$pdo->exec("SET time_zone = '+00:00'");
        }
        return self::$pdo;
    }
}
