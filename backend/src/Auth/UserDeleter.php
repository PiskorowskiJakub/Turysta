<?php
declare(strict_types=1);

namespace Turysta\Auth;

use PDO;
use Throwable;

/** Usuwa konto razem ze wszystkimi danymi gracza (klucze obce bez kaskady czyszczone recznie, w jednej transakcji). */
final class UserDeleter
{
    public function __construct(private PDO $pdo)
    {
    }

    public function delete(int $userId): void
    {
        // Tabele i kolumny z kluczami obcymi do users.id; CASCADE i SET NULL zalatwia baza.
        $fks = $this->pdo->query(
            "SELECT k.TABLE_NAME AS t, k.COLUMN_NAME AS c
             FROM information_schema.KEY_COLUMN_USAGE k
             JOIN information_schema.REFERENTIAL_CONSTRAINTS r
               ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME AND r.TABLE_NAME = k.TABLE_NAME
             WHERE k.TABLE_SCHEMA = DATABASE() AND k.REFERENCED_TABLE_NAME = 'users' AND k.REFERENCED_COLUMN_NAME = 'id'
               AND r.DELETE_RULE NOT IN ('CASCADE', 'SET NULL')"
        )->fetchAll();

        $this->pdo->beginTransaction();
        try {
            foreach ($fks as $fk) {
                $this->pdo->prepare("DELETE FROM `{$fk['t']}` WHERE `{$fk['c']}` = ?")->execute([$userId]);
            }
            $this->pdo->prepare('DELETE FROM sessions WHERE user_id = ?')->execute([$userId]);
            $this->pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
