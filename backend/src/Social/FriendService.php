<?php
declare(strict_types=1);

namespace Turysta\Social;

use PDO;
use PDOException;
use Turysta\Auth\RateLimiter;
use Turysta\Game\BadgeService;
use Turysta\Game\Clock;
use Turysta\Game\Notifier;
use Turysta\Http\ApiException;
use Turysta\Squad\SquadMemory;
use Turysta\Squad\SquadRules;

/**
 * Znajomi: zaproszenia, akceptacja i lista. Dopiero zaakceptowany znajomy widzi status online,
 * dukaty i punkty drugiej osoby.
 */
final class FriendService
{
    public const MAX_FRIENDS = 100;
    public const MAX_PENDING_OUT = 20;
    /** Gracz jest "online", jesli jego sesja byla aktywna w ciagu tylu minut. */
    public const ONLINE_MINUTES = 5;

    public function __construct(private PDO $pdo, private RateLimiter $limiter)
    {
    }

    /** @return array<string,mixed> */
    public function overview(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT f.status, f.requested_by, u.id, u.character_name, av.image_url, w.coins_cents, w.points,
                    sq.id AS squad_id, sq.name AS squad_name, sq.specialization AS squad_spec, sq.emblem AS squad_emblem, sq.frame AS squad_frame,
                    EXISTS(SELECT 1 FROM sessions s WHERE s.user_id = u.id AND s.last_activity > UTC_TIMESTAMP() - INTERVAL ? MINUTE) AS online
             FROM friendships f
             JOIN users u ON u.id = IF(f.user_a = ?, f.user_b, f.user_a)
             LEFT JOIN avatars av ON av.id = u.avatar_id
             LEFT JOIN wallets w ON w.user_id = u.id
             LEFT JOIN squad_members sm ON sm.user_id = u.id
             LEFT JOIN squads sq ON sq.id = sm.squad_id AND sq.archived_at IS NULL
             WHERE (f.user_a = ? OR f.user_b = ?) AND u.status <> 'banned'"
        );
        $stmt->execute([self::ONLINE_MINUTES, $userId, $userId, $userId]);

        $friends = [];
        $incoming = [];
        $outgoing = [];
        foreach ($stmt->fetchAll() as $r) {
            $base = ['id' => (int) $r['id'], 'name' => (string) $r['character_name'], 'avatar_url' => $r['image_url']];
            if ($r['status'] === 'accepted') {
                $friends[] = $base + [
                    'online' => (bool) $r['online'],
                    'coins_cents' => (int) ($r['coins_cents'] ?? 0),
                    'points' => (int) ($r['points'] ?? 0),
                    'squad' => $r['squad_id'] === null ? null : [
                        'id' => (int) $r['squad_id'],
                        'display_name' => SquadRules::displayName((string) $r['squad_name'], (string) $r['squad_spec']),
                    ] + SquadMemory::appearance($this->pdo, ['id' => $r['squad_id'], 'emblem' => $r['squad_emblem'], 'frame' => $r['squad_frame']]),
                ];
            } elseif ((int) $r['requested_by'] === $userId) {
                $outgoing[] = $base;
            } else {
                $incoming[] = $base;
            }
        }
        $byName = static fn(array $a, array $b): int => strcasecmp($a['name'], $b['name']);
        usort($friends, static fn(array $a, array $b): int => ($b['online'] <=> $a['online']) ?: strcasecmp($a['name'], $b['name']));
        usort($incoming, $byName);
        usort($outgoing, $byName);

        return [
            'friends' => $friends,
            'incoming' => $incoming,
            'outgoing' => $outgoing,
            'limits' => ['max_friends' => self::MAX_FRIENDS, 'max_pending' => self::MAX_PENDING_OUT, 'online_minutes' => self::ONLINE_MINUTES],
        ];
    }

    /** Stan relacji z innym graczem: self, none, friends, outgoing (my zaprosilismy), incoming (zaprosil nas). */
    public function state(int $me, int $other): string
    {
        if ($me === $other) {
            return 'self';
        }
        $row = $this->row($me, $other);
        if (!$row) {
            return 'none';
        }
        if ($row['status'] === 'accepted') {
            return 'friends';
        }
        return (int) $row['requested_by'] === $me ? 'outgoing' : 'incoming';
    }

    /**
     * Wysyla zaproszenie (po nazwie postaci albo id gracza). Jesli ta osoba juz zaprosila nas, zaproszenie jest od razu przyjete.
     *
     * @param array<string,mixed> $user wiersz users
     * @param array<string,mixed> $in name albo user_id
     * @return array{status:string,name:string}
     */
    public function request(array $user, array $in, string $ip): array
    {
        $me = (int) $user['id'];
        if ($this->limiter->hit('friend:' . $me, 20, 3600) || $this->limiter->hit('friend:ip:' . $ip, 60, 3600)) {
            throw new ApiException(429, 'rate_limited', 'Za dużo zaproszeń. Spróbuj za godzinę.');
        }

        $target = $this->resolve($in);
        $other = (int) $target['id'];
        if ($other === $me) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['name' => 'Nie możesz zaprosić samego siebie.']);
        }

        $row = $this->row($me, $other);
        if ($row) {
            if ($row['status'] === 'accepted') {
                throw new ApiException(409, 'already_friends', 'Jesteście już znajomymi.');
            }
            if ((int) $row['requested_by'] === $me) {
                throw new ApiException(409, 'request_pending', 'Zaproszenie dla tego gracza już czeka na odpowiedź.');
            }
            $this->accept($user, $other);
            return ['status' => 'accepted', 'name' => (string) $target['character_name']];
        }

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM friendships WHERE requested_by = ? AND status = 'pending'");
        $stmt->execute([$me]);
        if ((int) $stmt->fetchColumn() >= self::MAX_PENDING_OUT) {
            throw new ApiException(409, 'friend_limit', 'Masz już ' . self::MAX_PENDING_OUT . ' oczekujących zaproszeń. Poczekaj na odpowiedzi albo anuluj część z nich.');
        }
        if ($this->countFriends($me) >= self::MAX_FRIENDS) {
            throw new ApiException(409, 'friend_limit', 'Osiągnąłeś limit ' . self::MAX_FRIENDS . ' znajomych.');
        }

        [$a, $b] = $this->pair($me, $other);
        try {
            $this->pdo->prepare('INSERT INTO friendships (user_a, user_b, requested_by) VALUES (?, ?, ?)')->execute([$a, $b, $me]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new ApiException(409, 'request_pending', 'Zaproszenie dla tego gracza już czeka na odpowiedź.');
            }
            throw $e;
        }
        Notifier::add($this->pdo, $other, 'friend_request', ['name' => (string) $user['character_name']]);
        return ['status' => 'pending', 'name' => (string) $target['character_name']];
    }

    /**
     * Przyjmuje zaproszenie od gracza $otherId.
     *
     * @param array<string,mixed> $user wiersz users
     */
    public function accept(array $user, int $otherId): void
    {
        $me = (int) $user['id'];
        $row = $this->row($me, $otherId);
        if (!$row || $row['status'] !== 'pending' || (int) $row['requested_by'] !== $otherId) {
            throw new ApiException(404, 'not_found', 'Nie ma takiego zaproszenia.');
        }
        if ($this->countFriends($me) >= self::MAX_FRIENDS) {
            throw new ApiException(409, 'friend_limit', 'Osiągnąłeś limit ' . self::MAX_FRIENDS . ' znajomych.');
        }
        if ($this->countFriends($otherId) >= self::MAX_FRIENDS) {
            throw new ApiException(409, 'friend_limit', 'Ten gracz osiągnął limit znajomych.');
        }

        [$a, $b] = $this->pair($me, $otherId);
        $this->pdo->prepare("UPDATE friendships SET status = 'accepted', responded_at = UTC_TIMESTAMP() WHERE user_a = ? AND user_b = ?")
            ->execute([$a, $b]);
        $now = Clock::now($this->pdo);
        BadgeService::sync($this->pdo, $me, $now);
        BadgeService::sync($this->pdo, $otherId, $now);
        Notifier::add($this->pdo, $otherId, 'friend_accepted', ['name' => (string) $user['character_name']]);
    }

    /** Usuwa znajomego albo odrzuca/anuluje zaproszenie: w kazdym przypadku kasuje relacje z danym graczem. */
    public function remove(int $me, int $otherId): void
    {
        [$a, $b] = $this->pair($me, $otherId);
        $stmt = $this->pdo->prepare('DELETE FROM friendships WHERE user_a = ? AND user_b = ?');
        $stmt->execute([$a, $b]);
        if ($stmt->rowCount() === 0) {
            throw new ApiException(404, 'not_found', 'Nie ma takiej relacji.');
        }
    }

    /** @return array<string,mixed>|null */
    private function row(int $x, int $y): ?array
    {
        [$a, $b] = $this->pair($x, $y);
        $stmt = $this->pdo->prepare('SELECT * FROM friendships WHERE user_a = ? AND user_b = ?');
        $stmt->execute([$a, $b]);
        return $stmt->fetch() ?: null;
    }

    /** @return array{0:int,1:int} */
    private function pair(int $x, int $y): array
    {
        return $x < $y ? [$x, $y] : [$y, $x];
    }

    private function countFriends(int $userId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM friendships WHERE status = 'accepted' AND (user_a = ? OR user_b = ?)");
        $stmt->execute([$userId, $userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * @param array<string,mixed> $in
     * @return array<string,mixed>
     */
    private function resolve(array $in): array
    {
        $id = isset($in['user_id']) ? (int) $in['user_id'] : 0;
        $name = is_string($in['name'] ?? null) ? trim($in['name']) : '';
        if ($id > 0) {
            $stmt = $this->pdo->prepare("SELECT id, character_name FROM users WHERE id = ? AND status <> 'banned'");
            $stmt->execute([$id]);
        } elseif ($name !== '') {
            $stmt = $this->pdo->prepare("SELECT id, character_name FROM users WHERE character_name = ? AND status <> 'banned'");
            $stmt->execute([$name]);
        } else {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['name' => 'Podaj nazwę postaci gracza.']);
        }
        $target = $stmt->fetch();
        if (!$target) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', ['name' => 'Nie ma gracza o takiej nazwie.']);
        }
        return $target;
    }
}
