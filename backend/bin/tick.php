<?php
declare(strict_types=1);

// Wywolywane co minute przez kontener scheduler: rozlicza zakonczone szkolenia i prace.
require __DIR__ . '/../vendor/autoload.php';

use Turysta\Database;
use Turysta\Game\Settler;

$pdo = Database::pdo();
$settler = new Settler($pdo);

$ids = $pdo->query(
    "SELECT user_id FROM training_queue WHERE status = 'active' AND ends_at <= UTC_TIMESTAMP()
     UNION
     SELECT user_id FROM job_runs WHERE status = 'active' AND ends_at <= UTC_TIMESTAMP()"
)->fetchAll(PDO::FETCH_COLUMN);

$done = 0;
foreach ($ids as $id) {
    try {
        $settler->settleUser((int) $id);
        $done++;
    } catch (Throwable $e) {
        error_log("tick: user $id: " . $e->getMessage());
    }
}

// Oddzialy: rozliczenie ognisk po polnocy oraz (raz na dobe) zastepczosc przywodcow i archiwizacja nieaktywnych.
try {
    \Turysta\Squad\SquadMaintenance::run($pdo);
} catch (Throwable $e) {
    error_log('tick: squads: ' . $e->getMessage());
}

// Porzadki: stare limity zadan i wygasle sesje.
$pdo->exec('DELETE FROM rate_limits WHERE window_start < UTC_TIMESTAMP() - INTERVAL 1 DAY');
$pdo->exec('DELETE FROM sessions WHERE last_activity < UTC_TIMESTAMP() - INTERVAL 14 DAY');

// Raz na godzine: konta, ktorych adres e-mail nie zostal potwierdzony w ciagu 30 dni.
if ((int) gmdate('i') === 7) {
    $removed = (new \Turysta\Auth\UnverifiedCleaner($pdo))->run();
    if ($removed > 0) {
        echo gmdate('c'), " tick: usunieto niepotwierdzone konta: $removed\n";
    }
}

echo gmdate('c'), " tick: rozliczono $done\n";
