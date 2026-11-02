<?php
declare(strict_types=1);

namespace Turysta\Game;

use DateTimeImmutable;
use PDO;
use Throwable;
use Turysta\Http\ApiException;

/** Codzienna nagroda za logowanie: seria 1-7 dni, kazdy dzien wart wiecej, po przerwie od nowa, po 7. dniu znow od 1. */
final class DailyRewardService
{
    public const CYCLE = 7;

    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<string,mixed> */
    public function state(int $userId, ?DateTimeImmutable $now = null): array
    {
        $now ??= Clock::now($this->pdo);
        $today = Clock::gameDay($now);

        $stmt = $this->pdo->prepare('SELECT streak_day, last_claim_day FROM daily_rewards WHERE user_id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        $claimedToday = false;
        $done = 0; // ile dni biezacego cyklu jest juz odebranych
        if ($row) {
            $yesterday = (new DateTimeImmutable($today))->modify('-1 day')->format('Y-m-d');
            if ($row['last_claim_day'] === $today) {
                $claimedToday = true;
                $done = (int) $row['streak_day'];
            } elseif ($row['last_claim_day'] === $yesterday) {
                $done = (int) $row['streak_day'] >= self::CYCLE ? 0 : (int) $row['streak_day'];
            }
        }

        $reference = $this->referenceCost($userId);
        $days = [];
        foreach (Economy::DAILY_REWARDS as $day => [, $points]) {
            $days[] = ['day' => $day, 'coins_cents' => Economy::dailyCoins($day, $reference), 'points' => $points];
        }

        return [
            'days' => $days,
            'claimed_through' => $done,
            'claimed_today' => $claimedToday,
            'next_day' => $claimedToday ? null : $done + 1,
            'can_claim' => !$claimedToday,
        ];
    }

    /**
     * Koszt zwyklego awansu w biezacym rozdziale gracza (skala jego postepu). W rozdziale 1 swiata (start gry albo wejscie do
     * nowego swiata, ktore kosztuje 20 x wiecej) bierzemy koszt rozdzialu 2 tego swiata.
     */
    public function referenceCost(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT c2.cost_cents FROM player_progress p
             JOIN chapters c ON c.id = p.chapter_id
             JOIN chapters c2 ON c2.world_id = c.world_id AND c2.ordinal = GREATEST(c.ordinal, 2)
             WHERE p.user_id = ?'
        );
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    /** @return array<string,mixed> stan po odebraniu + przyznana nagroda */
    public function claim(int $userId): array
    {
        $this->pdo->beginTransaction();
        try {
            // Blokada portfela jest muteksem: dwa rownolegle zadania nie odbiora tej samej nagrody.
            $this->pdo->prepare('SELECT user_id FROM wallets WHERE user_id = ? FOR UPDATE')->execute([$userId]);

            $now = Clock::now($this->pdo);
            $state = $this->state($userId, $now);
            if (!$state['can_claim']) {
                throw new ApiException(409, 'already_claimed', 'Dzisiejszą nagrodę już odebrano.');
            }

            $day = (int) $state['next_day'];
            $points = Economy::DAILY_REWARDS[$day][1];
            $cents = Economy::dailyCoins($day, $this->referenceCost($userId));
            $today = Clock::gameDay($now);

            Wallet::apply($this->pdo, $userId, 'coins', $cents, 'daily_reward', 'daily_reward', $day, "daily:coins:$today");
            Wallet::apply($this->pdo, $userId, 'points', $points, 'daily_reward', 'daily_reward', $day, "daily:points:$today");
            $this->pdo->prepare(
                'INSERT INTO daily_rewards (user_id, streak_day, last_claim_day, claims_total) VALUES (?, ?, ?, 1)
                 ON DUPLICATE KEY UPDATE streak_day = VALUES(streak_day), last_claim_day = VALUES(last_claim_day), claims_total = claims_total + 1'
            )->execute([$userId, $day, $today]);

            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        return ['daily' => $this->state($userId), 'reward' => ['day' => $day, 'coins_cents' => $cents, 'points' => $points]];
    }
}
