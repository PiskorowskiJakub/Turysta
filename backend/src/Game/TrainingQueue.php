<?php
declare(strict_types=1);

namespace Turysta\Game;

use DateInterval;
use DateTimeImmutable;
use PDO;

final class TrainingQueue
{
    /** 1 aktywne + 2 oczekujace. */
    public const MAX_SLOTS = 3;
    public const ACTIVE_REFUND_PERCENT = 50;

    /** Uruchamia pierwsze oczekujace szkolenie gracza od podanej chwili. */
    public static function startNext(PDO $pdo, int $userId, DateTimeImmutable $startAt): void
    {
        $stmt = $pdo->prepare(
            "SELECT id, duration_seconds FROM training_queue
             WHERE user_id = ? AND status = 'queued' ORDER BY position, id LIMIT 1 FOR UPDATE"
        );
        $stmt->execute([$userId]);
        $next = $stmt->fetch();
        if (!$next) {
            return;
        }
        $ends = $startAt->add(new DateInterval('PT' . max(1, (int) $next['duration_seconds']) . 'S'));
        $pdo->prepare("UPDATE training_queue SET status = 'active', started_at = ?, ends_at = ? WHERE id = ?")
            ->execute([Clock::fmt($startAt), Clock::fmt($ends), $next['id']]);
    }
}
