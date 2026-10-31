<?php
declare(strict_types=1);

namespace Turysta\Controller;

use PDO;
use Turysta\Auth\Session;
use Turysta\Game\AnnouncementService;
use Turysta\Game\DailyRewardService;
use Turysta\Game\RankingService;
use Turysta\Http\Request;

/** Dane strony glownej ("Plecak"): ogloszenia, codzienna nagroda, czolowka rankingu, kod zaproszenia. */
final class HomeController
{
    public const TOP = 5;

    public function __construct(private PDO $pdo)
    {
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function show(Request $r): array
    {
        $uid = (int) Session::requireUser($r)['id'];

        $stmt = $this->pdo->prepare('SELECT code FROM referral_codes WHERE user_id = ?');
        $stmt->execute([$uid]);
        $code = $stmt->fetchColumn();

        return [200, [
            'announcements' => (new AnnouncementService($this->pdo))->published(),
            'daily' => (new DailyRewardService($this->pdo))->state($uid),
            'top' => (new RankingService($this->pdo))->top(self::TOP, $uid),
            'referral_code' => $code === false ? null : (string) $code,
        ]];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function claimDaily(Request $r): array
    {
        $uid = (int) Session::requireUser($r)['id'];
        return [200, (new DailyRewardService($this->pdo))->claim($uid)];
    }
}
