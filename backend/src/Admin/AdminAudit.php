<?php
declare(strict_types=1);

namespace Turysta\Admin;

use PDO;

/** Dziennik dzialan administratorow: kazda zmiana w panelu zostawia wpis (kto, co, na kim, szczegoly, IP). */
final class AdminAudit
{
    /** @param array<string,mixed> $details */
    public static function log(PDO $pdo, int $adminId, string $action, ?int $targetUserId = null, array $details = [], string $ip = ''): void
    {
        $pdo->prepare('INSERT INTO admin_audit (admin_id, action, target_user_id, details, ip) VALUES (?, ?, ?, ?, ?)')
            ->execute([
                $adminId,
                $action,
                $targetUserId,
                $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
                $ip !== '' ? $ip : null,
            ]);
    }
}
