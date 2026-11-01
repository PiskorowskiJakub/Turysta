<?php
declare(strict_types=1);

// Usuwa jedno konto (wskazane adresem e-mail) razem z wszystkimi jego danymi, takze konto administratora.
// Domyslnie tylko pokazuje, co zostanie usuniete.
// Uzycie: php bin/delete_user.php adres@email [--yes]
require __DIR__ . '/../vendor/autoload.php';

use Turysta\Auth\UserDeleter;
use Turysta\Database;

$email = strtolower(trim((string) ($argv[1] ?? '')));
$confirmed = in_array('--yes', $argv, true);
if ($email === '' || str_starts_with($email, '--')) {
    fwrite(STDERR, "Uzycie: php bin/delete_user.php adres@email [--yes]\n");
    exit(1);
}

$pdo = Database::pdo();
$stmt = $pdo->prepare('SELECT id, email, character_name, role FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();
if (!$user) {
    echo "Nie ma konta o adresie $email.\n";
    exit(0);
}

echo "Konto do usuniecia: #{$user['id']} {$user['email']} ({$user['character_name']}, rola: {$user['role']})\n";
if (!$confirmed) {
    echo "Dodaj --yes, aby usunac.\n";
    exit(0);
}

try {
    (new UserDeleter($pdo))->delete((int) $user['id']);
    echo "Konto usuniete. Mozesz zalozyc je ponownie na ten sam adres.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Blad, nic nie usunieto: ' . $e->getMessage() . "\n");
    exit(1);
}
