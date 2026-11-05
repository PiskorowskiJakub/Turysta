<?php
declare(strict_types=1);

namespace Turysta\Squad;

use PDO;
use Throwable;
use Turysta\Game\Notifier;

/** Powiadomienia (dzwonek) dla wszystkich czlonkow oddzialu. Blad powiadomienia nigdy nie psuje wlasciwej operacji. */
final class SquadNotifier
{
    /** @param array<string,mixed> $payload dopisujemy do niego nazwe oddzialu ("squad") */
    public static function toMembers(PDO $pdo, int $squadId, string $type, array $payload = []): void
    {
        try {
            $stmt = $pdo->prepare('SELECT name, specialization FROM squads WHERE id = ?');
            $stmt->execute([$squadId]);
            $squad = $stmt->fetch();
            if (!$squad) {
                return;
            }
            $payload['squad'] = SquadRules::displayName((string) $squad['name'], (string) $squad['specialization']);
            $members = $pdo->prepare('SELECT user_id FROM squad_members WHERE squad_id = ?');
            $members->execute([$squadId]);
            foreach ($members->fetchAll(PDO::FETCH_COLUMN) as $userId) {
                Notifier::add($pdo, (int) $userId, $type, $payload);
            }
        } catch (Throwable $e) {
            error_log('SquadNotifier: ' . $e->getMessage());
        }
    }
}
