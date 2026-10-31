<?php
declare(strict_types=1);

// Nadaje albo odbiera role uzytkownikowi: player | moderator | admin.
// Uzycie: php bin/set_role.php adres@email.pl admin
require __DIR__ . '/../vendor/autoload.php';

use Turysta\Database;

$email = $argv[1] ?? '';
$role = $argv[2] ?? '';
if ($email === '' || !in_array($role, ['player', 'moderator', 'admin'], true)) {
    fwrite(STDERR, "Uzycie: php bin/set_role.php adres@email.pl player|moderator|admin\n");
    exit(1);
}

$pdo = Database::pdo();
$stmt = $pdo->prepare('SELECT id, character_name FROM users WHERE email = ?');
$stmt->execute([strtolower(trim($email))]);
$user = $stmt->fetch();
if (!$user) {
    fwrite(STDERR, "Nie ma uzytkownika o adresie $email\n");
    exit(1);
}
$pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $user['id']]);
echo "Gracz {$user['character_name']} (id {$user['id']}) ma teraz role: $role.\n";
