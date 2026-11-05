<?php
declare(strict_types=1);

// Narzedzie deweloperskie: dosypuje oddzialowi zar, pieczatki i dukaty do magazynu schroniska, zeby wyprobowac rozbudowe bez tygodni zbierania.
// Uzycie: php bin/dev_grant_squad.php <id oddzialu> [--ember=N] [--stamps=N] [--coins=N]   (coins w pelnych dukatach)
// Odmawia dzialania na produkcji (APP_ENV=prod).
require __DIR__ . '/../vendor/autoload.php';

use Turysta\Database;

if ((getenv('APP_ENV') ?: 'dev') === 'prod') {
    fwrite(STDERR, "Odmowa: to narzedzie jest tylko dla srodowiska deweloperskiego.\n");
    exit(1);
}
$id = (int) ($argv[1] ?? 0);
if ($id <= 0) {
    fwrite(STDERR, "Podaj id oddzialu, np.: php bin/dev_grant_squad.php 1 --ember=5000 --stamps=50 --coins=20000\n");
    exit(1);
}
$opts = ['ember' => 0, 'stamps' => 0, 'coins' => 0];
foreach (array_slice($argv, 2) as $arg) {
    if (preg_match('/^--(ember|stamps|coins)=(\d+)$/', $arg, $m)) {
        $opts[$m[1]] = (int) $m[2];
    }
}
$pdo = Database::pdo();
$stmt = $pdo->prepare('UPDATE squads SET ember = ember + ?, stamps = stamps + ?, vault_cents = vault_cents + ? WHERE id = ? AND archived_at IS NULL');
$stmt->execute([$opts['ember'], $opts['stamps'], $opts['coins'] * 100, $id]);
if ($stmt->rowCount() === 0) {
    fwrite(STDERR, "Nie znaleziono aktywnego oddzialu o id $id.\n");
    exit(1);
}
echo "Oddzial $id: +{$opts['ember']} zaru, +{$opts['stamps']} pieczatek, +{$opts['coins']} dk w magazynie.\n";
