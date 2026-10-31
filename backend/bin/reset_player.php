<?php
declare(strict_types=1);

// Cofa postep gracza do stanu nowego konta: poziomy, kolejka, prace, dukaty, awanse, statystyki.
// Zostaje konto (e-mail, haslo, nazwa postaci, awatar, plec, samouczek).
// Uzycie: php bin/reset_player.php adres@email.pl [--yes]
require __DIR__ . '/../vendor/autoload.php';

use Turysta\Database;

const STARTING_COINS_CENTS = 200; // jak w AuthService

$email = $argv[1] ?? '';
$confirmed = in_array('--yes', $argv, true);
if ($email === '' || str_starts_with($email, '--')) {
    fwrite(STDERR, "Uzycie: php bin/reset_player.php adres@email.pl [--yes]\n");
    exit(1);
}

$pdo = Database::pdo();
$stmt = $pdo->prepare('SELECT id, character_name FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();
if (!$user) {
    fwrite(STDERR, "Nie ma uzytkownika o adresie $email\n");
    exit(1);
}
$uid = (int) $user['id'];

if (!$confirmed) {
    echo "Gracz: {$user['character_name']} (id $uid). To usunie caly jego postep. Dodaj --yes, aby wykonac.\n";
    exit(0);
}

$pdo->beginTransaction();
try {
    foreach (['training_queue', 'job_runs', 'chapter_log', 'notifications', 'user_earnings', 'user_items', 'user_badges', 'user_featured_badges', 'ledger_entries', 'user_stats'] as $table) {
        $pdo->prepare("DELETE FROM $table WHERE user_id = ?")->execute([$uid]);
    }
    $pdo->prepare('UPDATE user_skills SET level = 0 WHERE user_id = ?')->execute([$uid]);
    $pdo->prepare('INSERT IGNORE INTO user_skills (user_id, skill_id, level) SELECT ?, id, 0 FROM skill_definitions')->execute([$uid]);
    $pdo->prepare('UPDATE wallets SET coins_cents = ?, tickets = 0, points = 0 WHERE user_id = ?')->execute([STARTING_COINS_CENTS, $uid]);
    $pdo->prepare(
        "INSERT INTO ledger_entries (user_id, currency, amount, balance_after, reason, ref_type, ref_id)
         VALUES (?, 'coins', ?, ?, 'starting_bonus', 'user', ?)"
    )->execute([$uid, STARTING_COINS_CENTS, STARTING_COINS_CENTS, $uid]);
    $pdo->prepare(
        'UPDATE player_progress SET world_id = (SELECT w.id FROM worlds w ORDER BY w.ordinal LIMIT 1),
            chapter_id = (SELECT c.id FROM worlds w JOIN chapters c ON c.world_id = w.id ORDER BY w.ordinal, c.ordinal LIMIT 1)
         WHERE user_id = ?'
    )->execute([$uid]);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Blad, nic nie zmieniono: ' . $e->getMessage() . "\n");
    exit(1);
}
echo "Postep gracza {$user['character_name']} cofniety do ustawien nowego gracza.\n";
