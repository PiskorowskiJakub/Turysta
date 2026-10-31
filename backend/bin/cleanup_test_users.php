<?php
declare(strict_types=1);

// Usuwa konta zostawione przez testy integracyjne (adresy @example.com) razem z ich danymi.
// Konta administratorow nigdy nie sa ruszane. Domyslnie tylko pokazuje, co zostanie usuniete.
// Uzycie: php bin/cleanup_test_users.php [--yes]
require __DIR__ . '/../vendor/autoload.php';

use Turysta\Database;

$confirmed = in_array('--yes', $argv, true);
$pdo = Database::pdo();

$ids = array_map('intval', $pdo->query(
    "SELECT id FROM users WHERE email LIKE '%@example.com' AND role <> 'admin' ORDER BY id"
)->fetchAll(PDO::FETCH_COLUMN));

$kept = $pdo->query("SELECT id, email FROM users WHERE id NOT IN (" . ($ids ? implode(',', $ids) : '0') . ') ORDER BY id')->fetchAll();
echo 'Do usuniecia: ' . count($ids) . " kont testowych.\n";
echo 'Zostaje: ' . count($kept) . " kont";
echo $kept ? ' (' . implode(', ', array_map(static fn(array $u): string => "{$u['id']}:{$u['email']}", $kept)) . ")\n" : "\n";

if (!$ids) {
    exit(0);
}
if (!$confirmed) {
    echo "Dodaj --yes, aby usunac.\n";
    exit(0);
}

// Tabele i kolumny z kluczami obcymi do users.id; CASCADE i SET NULL zalatwia baza.
$fks = $pdo->query(
    "SELECT k.TABLE_NAME AS t, k.COLUMN_NAME AS c
     FROM information_schema.KEY_COLUMN_USAGE k
     JOIN information_schema.REFERENTIAL_CONSTRAINTS r
       ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME AND r.TABLE_NAME = k.TABLE_NAME
     WHERE k.TABLE_SCHEMA = DATABASE() AND k.REFERENCED_TABLE_NAME = 'users' AND k.REFERENCED_COLUMN_NAME = 'id'
       AND r.DELETE_RULE NOT IN ('CASCADE', 'SET NULL')"
)->fetchAll();

$in = implode(',', $ids);
$pdo->beginTransaction();
try {
    foreach ($fks as $fk) {
        $n = $pdo->exec("DELETE FROM `{$fk['t']}` WHERE `{$fk['c']}` IN ($in)");
        if ($n) {
            echo "  {$fk['t']}.{$fk['c']}: $n\n";
        }
    }
    $pdo->exec("DELETE FROM sessions WHERE user_id IN ($in)");
    $deleted = $pdo->exec("DELETE FROM users WHERE id IN ($in)");
    $pdo->commit();
    echo "Usunieto kont: $deleted.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Blad, nic nie usunieto: ' . $e->getMessage() . "\n");
    exit(1);
}
