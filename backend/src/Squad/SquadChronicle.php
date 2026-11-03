<?php
declare(strict_types=1);

namespace Turysta\Squad;

use PDO;
use Throwable;
use Turysta\Game\Clock;

/**
 * Kronika oddzialu: wylacznie automatyczne wpisy. Zapisujemy identyfikatory graczy, a nazwy doczytujemy przy wyswietlaniu,
 * dzieki czemu gracz z wylaczonym share_activity jest w kronice anonimowy ("ktos z oddzialu").
 */
final class SquadChronicle
{
    /** @param array<string,mixed> $payload */
    public static function record(PDO $pdo, int $squadId, string $type, string $refKey, array $payload): void
    {
        try {
            $pdo->prepare('INSERT IGNORE INTO squad_events (squad_id, type, ref_key, payload) VALUES (?, ?, ?, ?)')
                ->execute([$squadId, $type, $refKey, json_encode($payload, JSON_UNESCAPED_UNICODE)]);
        } catch (Throwable $e) {
            error_log('SquadChronicle: ' . $e->getMessage());
        }
    }

    /**
     * @return list<array<string,mixed>>
     */
    public static function list(PDO $pdo, int $squadId, int $viewerId, int $limit = SquadRules::CHRONICLE_LIMIT): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = $pdo->prepare(
            'SELECT id, type, payload, created_at FROM squad_events
             WHERE squad_id = ? AND created_at > UTC_TIMESTAMP() - INTERVAL ' . SquadRules::CHRONICLE_DAYS . " DAY
             ORDER BY created_at DESC, id DESC LIMIT $limit"
        );
        $stmt->execute([$squadId]);
        $rows = $stmt->fetchAll();

        $ids = [];
        foreach ($rows as $i => $r) {
            $p = json_decode((string) $r['payload'], true);
            $rows[$i]['p'] = is_array($p) ? $p : [];
            foreach (['user', 'target'] as $k) {
                if (isset($rows[$i]['p'][$k])) {
                    $ids[(int) $rows[$i]['p'][$k]] = true;
                }
            }
        }
        $names = [];
        if ($ids) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT id, character_name, share_activity FROM users WHERE id IN ($in)");
            $stmt->execute(array_keys($ids));
            foreach ($stmt->fetchAll() as $u) {
                $visible = (int) $u['share_activity'] === 1 || (int) $u['id'] === $viewerId;
                $names[(int) $u['id']] = $visible ? (string) $u['character_name'] : null;
            }
        }

        $out = [];
        foreach ($rows as $r) {
            $p = $r['p'];
            $entry = ['id' => (int) $r['id'], 'type' => (string) $r['type'], 'created_at' => Clock::iso((string) $r['created_at'])];
            foreach (['user', 'target'] as $k) {
                if (isset($p[$k])) {
                    $entry[$k] = ['id' => $names[(int) $p[$k]] === null ? null : (int) $p[$k], 'name' => $names[(int) $p[$k]] ?? null];
                    unset($p[$k]);
                }
            }
            $entry['payload'] = (object) $p;
            $out[] = $entry;
        }
        return $out;
    }
}
