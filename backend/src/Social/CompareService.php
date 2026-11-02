<?php
declare(strict_types=1);

namespace Turysta\Social;

use PDO;
use Turysta\Game\StatsService;
use Turysta\Http\ApiException;

/** Porownanie dwoch kont: statystyki i poziomy umiejetnosci gracza oraz jego znajomego. */
final class CompareService
{
    public function __construct(private PDO $pdo, private FriendService $friends)
    {
    }

    /** @return array<string,mixed> */
    public function compare(int $me, int $friendId): array
    {
        if ($this->friends->state($me, $friendId) !== 'friends') {
            throw new ApiException(403, 'not_friends', 'Porównać możesz tylko konta swoich znajomych.');
        }

        $mine = $this->person($me);
        $theirs = $this->person($friendId);
        if ($theirs === null || $mine === null) {
            throw new ApiException(404, 'player_not_found', 'Nie znaleziono gracza.');
        }

        // Lista prac z nazwami (w kolejnosci jak w profilu).
        $jobs = [];
        foreach ($this->pdo->query('SELECT code, name FROM job_definitions ORDER BY duration_seconds')->fetchAll() as $j) {
            $jobs[] = [
                'code' => (string) $j['code'],
                'name' => (string) $j['name'],
                'me' => $mine['jobs'][$j['code']] ?? 0,
                'friend' => $theirs['jobs'][$j['code']] ?? 0,
            ];
        }

        // Umiejetnosci podstawowe zawsze; swiatow tylko te, ktore gracz sam rozwija albo ktore znajomy rozwija w swiecie, do ktorego gracz juz dotarl (bez spoilerow).
        $skills = [];
        $stmt = $this->pdo->prepare(
            'SELECT s.code, s.name, s.world_ordinal, COALESCE(a.level, 0) AS me_level, COALESCE(b.level, 0) AS friend_level
             FROM skill_definitions s
             LEFT JOIN user_skills a ON a.skill_id = s.id AND a.user_id = ?
             LEFT JOIN user_skills b ON b.skill_id = s.id AND b.user_id = ?
             ORDER BY s.id'
        );
        $stmt->execute([$me, $friendId]);
        foreach ($stmt->fetchAll() as $s) {
            $world = $s['world_ordinal'] !== null ? (int) $s['world_ordinal'] : null;
            $meLevel = (int) $s['me_level'];
            $friendLevel = (int) $s['friend_level'];
            if ($world !== null && $meLevel === 0 && !($friendLevel > 0 && $mine['world'] >= $world)) {
                continue;
            }
            $skills[] = ['code' => (string) $s['code'], 'name' => (string) $s['name'], 'world' => $world, 'me' => $meLevel, 'friend' => $friendLevel];
        }

        unset($mine['jobs'], $theirs['jobs']);
        return ['me' => $mine, 'friend' => $theirs, 'jobs' => $jobs, 'skills' => $skills];
    }

    /** @return array<string,mixed>|null */
    private function person(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.id, u.character_name, av.image_url, COALESCE(w.coins_cents, 0) AS coins_cents, COALESCE(w.tickets, 0) AS tickets,
                    COALESCE(w.points, 0) AS points, wo.ordinal AS world, c.ordinal AS chapter,
                    (SELECT COALESCE(SUM(level), 0) FROM user_skills WHERE user_id = u.id) AS skill_levels,
                    (SELECT COUNT(*) FROM user_badges WHERE user_id = u.id) AS badges
             FROM users u
             LEFT JOIN avatars av ON av.id = u.avatar_id
             LEFT JOIN wallets w ON w.user_id = u.id
             LEFT JOIN player_progress p ON p.user_id = u.id
             LEFT JOIN chapters c ON c.id = p.chapter_id
             LEFT JOIN worlds wo ON wo.id = c.world_id
             WHERE u.id = ? AND u.status <> 'banned'"
        );
        $stmt->execute([$userId]);
        $r = $stmt->fetch();
        if (!$r) {
            return null;
        }
        $stats = (new StatsService($this->pdo))->forUser($userId);
        $jobs = [];
        foreach ($stats['earnings']['jobs'] as $j) {
            $jobs[(string) $j['code']] = (int) $j['cents'];
        }

        return [
            'id' => (int) $r['id'],
            'name' => (string) $r['character_name'],
            'avatar_url' => $r['image_url'],
            'rank' => (int) $stats['rank'],
            'points' => (int) $r['points'],
            'coins_cents' => (int) $r['coins_cents'],
            'tickets' => (int) $r['tickets'],
            'world' => (int) ($r['world'] ?? 0),
            'chapter' => (int) ($r['chapter'] ?? 0),
            'skill_levels' => (int) $r['skill_levels'],
            'badges' => (int) $r['badges'],
            'account_days' => (int) $stats['account_days'],
            'active_days' => (int) $stats['active_days'],
            'current_streak' => (int) $stats['current_streak'],
            'longest_streak' => (int) $stats['longest_streak'],
            'earned_cents' => (int) $stats['earnings']['total_cents'],
            'jobs' => $jobs,
        ];
    }
}
