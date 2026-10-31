<?php
declare(strict_types=1);

namespace Turysta\Game;

use PDO;

/** Licznik zaproszen: konto liczy sie dopiero po potwierdzeniu e-maila; osobno ile z tych kont ma co najmniej 7 dni aktywnosci. */
final class ReferralService
{
    public const ACTIVE_DAYS = 7;

    public function __construct(private PDO $pdo)
    {
    }

    /** @return array{registered:int,active:int,active_days_required:int} */
    public function stats(int $referrerId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS registered,
                    COALESCE(SUM(COALESCE(st.active_days, 0) >= ?), 0) AS active
             FROM referrals r
             JOIN users u ON u.id = r.referred_id AND u.email_verified_at IS NOT NULL
             LEFT JOIN user_stats st ON st.user_id = u.id
             WHERE r.referrer_id = ?'
        );
        $stmt->execute([self::ACTIVE_DAYS, $referrerId]);
        $row = $stmt->fetch();
        return [
            'registered' => (int) $row['registered'],
            'active' => (int) $row['active'],
            'active_days_required' => self::ACTIVE_DAYS,
        ];
    }
}
