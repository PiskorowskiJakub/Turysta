<?php
declare(strict_types=1);

namespace Turysta\Game;

use PDO;

/** Licznik zaproszen: ile osob zalozylo konto z linku gracza, ile potwierdzilo e-mail i ile z nich ma co najmniej 7 dni aktywnosci. */
final class ReferralService
{
    public const ACTIVE_DAYS = 7;

    public function __construct(private PDO $pdo)
    {
    }

    /** @return array{registered:int,verified:int,active:int,active_days_required:int} */
    public function stats(int $referrerId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS registered,
                    COALESCE(SUM(u.email_verified_at IS NOT NULL), 0) AS verified,
                    COALESCE(SUM(u.email_verified_at IS NOT NULL AND COALESCE(st.active_days, 0) >= ?), 0) AS active
             FROM referrals r
             JOIN users u ON u.id = r.referred_id
             LEFT JOIN user_stats st ON st.user_id = u.id
             WHERE r.referrer_id = ?'
        );
        $stmt->execute([self::ACTIVE_DAYS, $referrerId]);
        $row = $stmt->fetch();
        return [
            'registered' => (int) $row['registered'],
            'verified' => (int) $row['verified'],
            'active' => (int) $row['active'],
            'active_days_required' => self::ACTIVE_DAYS,
        ];
    }
}
