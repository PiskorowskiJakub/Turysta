<?php
declare(strict_types=1);

// Usuwa konta graczy, ktorzy nie potwierdzili adresu e-mail w ciagu 30 dni (to samo robi co godzine scheduler).
// Domyslnie tylko pokazuje, co zostanie usuniete.
// Uzycie: php bin/cleanup_unverified.php [--yes]
require __DIR__ . '/../vendor/autoload.php';

use Turysta\Auth\UnverifiedCleaner;
use Turysta\Database;

$pdo = Database::pdo();
$cleaner = new UnverifiedCleaner($pdo);
$ids = $cleaner->candidates();

if (!$ids) {
    echo "Brak niepotwierdzonych kont po terminie.\n";
    exit(0);
}

$in = implode(',', array_map('intval', $ids));
foreach ($pdo->query("SELECT id, email, character_name, created_at FROM users WHERE id IN ($in) ORDER BY id")->fetchAll() as $u) {
    echo "#{$u['id']} {$u['email']} ({$u['character_name']}), zalozone {$u['created_at']}\n";
}

if (!in_array('--yes', $argv, true)) {
    echo count($ids) . " kont do usuniecia. Dodaj --yes, aby usunac.\n";
    exit(0);
}
echo 'Usunieto kont: ' . $cleaner->purge($ids) . "\n";
