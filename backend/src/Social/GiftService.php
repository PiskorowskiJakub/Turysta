<?php
declare(strict_types=1);

namespace Turysta\Social;

use PDO;
use Throwable;
use Turysta\Game\Clock;
use Turysta\Game\DailyRewardService;
use Turysta\Game\Economy;
use Turysta\Game\Notifier;
use Turysta\Game\ReferralService;
use Turysta\Game\Wallet;
use Turysta\Http\ApiException;

/**
 * Prezenty dla znajomych: raz dziennie dla danej osoby, do MAX_SENT dziennie lacznie, a odbiorca przyjmuje najwyzej
 * MAX_RECEIVED dziennie. Nadawca placi cala kwote, ale dostaje maly bonus (BONUS_PERCENT kwoty bazowej). Kwota bazowa rosnie z postepem nadawcy
 * (jak dzien 4 codziennej nagrody), a mnoznik rosnie z liczba aktywnych poleconych graczy (Economy::giftMultiplierPermille). Prezent mozna wyslac dopiero po MIN_FRIEND_HOURS od przyjecia zaproszenia (przeciw multikontom).
 */
final class GiftService
{
    public const MAX_SENT = 3;
    public const MAX_RECEIVED = 5;
    public const MIN_FRIEND_HOURS = 24;
    public const GIFT_DAY = 4;
    public const BONUS_PERCENT = 20;

    public function __construct(private PDO $pdo, private FriendService $friends)
    {
    }

    /** Kwota bazowa (bez mnoznika za polecenia): jak dzien 4 codziennej nagrody. */
    public function baseCents(int $senderId): int
    {
        $reference = (new DailyRewardService($this->pdo))->referenceCost($senderId);
        return Economy::dailyCoins(self::GIFT_DAY, $reference);
    }

    /** Kwota prezentu: baza razy mnoznik za aktywnych poleconych, zaokraglona do 0,10 dk. */
    public function giftCents(int $senderId): int
    {
        $multiplier = Economy::giftMultiplierPermille($this->activeReferrals($senderId));
        return intdiv(intdiv($this->baseCents($senderId) * $multiplier, 1000) + 5, 10) * 10;
    }

    private function activeReferrals(int $userId): int
    {
        return (new ReferralService($this->pdo))->stats($userId)['active'];
    }

    public static function bonusCents(int $giftCents): int
    {
        return max(10, intdiv(intdiv($giftCents * self::BONUS_PERCENT, 100) + 5, 10) * 10);
    }

    /**
     * Stan prezentow gracza: kwota, ile prezentow zostalo dzis i status dla kazdego znajomego
     * (ok / sent = juz dzis wyslano / early = za krotko w znajomych / limit = wyczerpany dzienny limit).
     *
     * @return array<string,mixed>
     */
    public function state(int $userId): array
    {
        $now = Clock::now($this->pdo);
        $day = Clock::gameDay($now);
        $active = $this->activeReferrals($userId);
        $base = $this->baseCents($userId);
        $multiplier = Economy::giftMultiplierPermille($active);
        $cents = intdiv(intdiv($base * $multiplier, 1000) + 5, 10) * 10;

        $stmt = $this->pdo->prepare('SELECT recipient_id FROM friend_gifts WHERE sender_id = ? AND game_day = ?');
        $stmt->execute([$userId, $day]);
        $sentTo = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        $left = max(0, self::MAX_SENT - count($sentTo));

        $stmt = $this->pdo->prepare(
            "SELECT IF(user_a = ?, user_b, user_a) AS fid, responded_at FROM friendships
             WHERE status = 'accepted' AND (user_a = ? OR user_b = ?)"
        );
        $stmt->execute([$userId, $userId, $userId]);
        $perFriend = [];
        foreach ($stmt->fetchAll() as $r) {
            $fid = (int) $r['fid'];
            $since = $r['responded_at'] !== null ? Clock::parse((string) $r['responded_at']) : null;
            $ready = $since?->modify('+' . self::MIN_FRIEND_HOURS . ' hours');
            if (in_array($fid, $sentTo, true)) {
                $status = 'sent';
            } elseif ($ready !== null && $ready > $now) {
                $status = 'early';
            } elseif ($left === 0) {
                $status = 'limit';
            } else {
                $status = 'ok';
            }
            $perFriend[$fid] = ['status' => $status, 'available_at' => $status === 'early' && $ready ? $ready->format(DATE_ATOM) : null];
        }

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS n, COALESCE(SUM(cents), 0) AS c FROM friend_gifts WHERE sender_id = ?'
        );
        $stmt->execute([$userId]);
        $sent = $stmt->fetch();
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS n, COALESCE(SUM(cents), 0) AS c FROM friend_gifts WHERE recipient_id = ?'
        );
        $stmt->execute([$userId]);
        $received = $stmt->fetch();

        return [
            'cents' => $cents,
            'base_cents' => $base,
            'multiplier_permille' => $multiplier,
            'active_referrals' => $active,
            'bonus_cents' => self::bonusCents($base),
            'totals' => [
                'sent_count' => (int) $sent['n'],
                'sent_cents' => (int) $sent['c'],
                'received_count' => (int) $received['n'],
                'received_cents' => (int) $received['c'],
            ],
            'sent_left' => $left,
            'max_per_day' => self::MAX_SENT,
            'min_friend_hours' => self::MIN_FRIEND_HOURS,
            'per_friend' => (object) $perFriend,
        ];
    }

    /**
     * @param array<string,mixed> $user wiersz users (nadawca)
     * @return array{cents:int,bonus_cents:int}
     */
    public function send(array $user, int $recipientId): array
    {
        $me = (int) $user['id'];
        if ($recipientId === $me || $this->friends->state($me, $recipientId) !== 'friends') {
            throw new ApiException(403, 'not_friends', 'Prezent możesz wysłać tylko znajomemu.');
        }

        $this->pdo->beginTransaction();
        try {
            // Portfele blokujemy zawsze w tej samej kolejnosci (rosnaco po id), zeby dwa wzajemne prezenty nie zakleszczyly sie.
            $ids = [$me, $recipientId];
            sort($ids);
            $this->pdo->prepare('SELECT user_id FROM wallets WHERE user_id IN (?, ?) ORDER BY user_id FOR UPDATE')->execute($ids);

            $now = Clock::now($this->pdo);
            $day = Clock::gameDay($now);

            $stmt = $this->pdo->prepare("SELECT responded_at FROM friendships WHERE user_a = ? AND user_b = ? AND status = 'accepted'");
            $stmt->execute([min($ids), max($ids)]);
            $since = $stmt->fetchColumn();
            if ($since !== false && $since !== null && Clock::parse((string) $since)->modify('+' . self::MIN_FRIEND_HOURS . ' hours') > $now) {
                throw new ApiException(409, 'gift_too_early', 'Prezenty można wysyłać dopiero po 24 godzinach od zostania znajomymi.');
            }

            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM friend_gifts WHERE sender_id = ? AND game_day = ?');
            $stmt->execute([$me, $day]);
            if ((int) $stmt->fetchColumn() >= self::MAX_SENT) {
                throw new ApiException(409, 'gift_limit', 'Wysłałeś dziś już maksymalną liczbę prezentów.');
            }
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM friend_gifts WHERE recipient_id = ? AND game_day = ?');
            $stmt->execute([$recipientId, $day]);
            if ((int) $stmt->fetchColumn() >= self::MAX_RECEIVED) {
                throw new ApiException(409, 'gift_recipient_limit', 'Ten gracz dostał dziś już wiele prezentów. Spróbuj jutro.');
            }
            $stmt = $this->pdo->prepare('SELECT 1 FROM friend_gifts WHERE sender_id = ? AND recipient_id = ? AND game_day = ?');
            $stmt->execute([$me, $recipientId, $day]);
            if ($stmt->fetchColumn()) {
                throw new ApiException(409, 'gift_already_sent', 'Temu znajomemu wysłałeś już dziś prezent.');
            }

            $cents = $this->giftCents($me);
            $bonus = self::bonusCents($this->baseCents($me));
            $this->pdo->prepare('INSERT INTO friend_gifts (sender_id, recipient_id, cents, bonus_cents, game_day) VALUES (?, ?, ?, ?, ?)')
                ->execute([$me, $recipientId, $cents, $bonus, $day]);
            $giftId = (int) $this->pdo->lastInsertId();

            Wallet::apply($this->pdo, $me, 'coins', -$cents, 'gift_sent', 'gift', $giftId);
            Wallet::apply($this->pdo, $recipientId, 'coins', $cents, 'gift_received', 'gift', $giftId);
            Wallet::apply($this->pdo, $me, 'coins', $bonus, 'gift_bonus', 'gift', $giftId);
            Notifier::add($this->pdo, $recipientId, 'friend_gift', ['name' => (string) $user['character_name'], 'cents' => $cents]);

            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        return ['cents' => $cents, 'bonus_cents' => $bonus];
    }
}
