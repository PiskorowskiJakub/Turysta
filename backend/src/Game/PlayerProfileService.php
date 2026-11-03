<?php
declare(strict_types=1);

namespace Turysta\Game;

use PDO;
use Turysta\Auth\RateLimiter;
use Turysta\Http\ApiException;
use Turysta\Squad\SquadService;
use Turysta\Squad\SquadView;

/** Publiczny profil gracza (to, co widzi inny zalogowany gracz): karta i ksiazeczka odznak, bez szczegolowych statystyk. */
final class PlayerProfileService
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<string,mixed> */
    public function forPlayer(int $viewerId, int $playerId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.id, u.character_name, u.gender, u.created_at, a.image_url, a.name AS avatar_name
             FROM users u LEFT JOIN avatars a ON a.id = u.avatar_id
             WHERE u.id = ? AND u.status = 'active'"
        );
        $stmt->execute([$playerId]);
        $user = $stmt->fetch();
        if (!$user) {
            throw new ApiException(404, 'player_not_found', 'Nie znaleziono gracza.');
        }

        $stmt = $this->pdo->prepare(
            'SELECT w.ordinal AS wo, w.name AS wn, c.ordinal AS co, c.name AS cn
             FROM player_progress p JOIN chapters c ON c.id = p.chapter_id JOIN worlds w ON w.id = c.world_id
             WHERE p.user_id = ?'
        );
        $stmt->execute([$playerId]);
        $progress = $stmt->fetch() ?: null;

        // Nazwy swiata i rozdzialu pokazujemy tylko wtedy, gdy ogladajacy sam dotarl do tego swiata (bez spoilerow).
        $stmt = $this->pdo->prepare(
            'SELECT w.ordinal FROM player_progress p JOIN chapters c ON c.id = p.chapter_id JOIN worlds w ON w.id = c.world_id
             WHERE p.user_id = ?'
        );
        $stmt->execute([$viewerId]);
        $viewerWorld = (int) ($stmt->fetchColumn() ?: 0);
        $namesVisible = $progress !== null && ($viewerId === $playerId || $viewerWorld >= (int) $progress['wo']);

        $stmt = $this->pdo->prepare('SELECT points FROM wallets WHERE user_id = ?');
        $stmt->execute([$playerId]);
        $points = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare('SELECT longest_streak FROM user_stats WHERE user_id = ?');
        $stmt->execute([$playerId]);
        $longest = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(level), 0) FROM user_skills WHERE user_id = ?');
        $stmt->execute([$playerId]);
        $skillLevels = (int) $stmt->fetchColumn();

        return [
            'id' => (int) $user['id'],
            'name' => (string) $user['character_name'],
            'gender' => $user['gender'] !== null ? (string) $user['gender'] : null,
            'avatar_name' => $user['avatar_name'] !== null ? (string) $user['avatar_name'] : null,
            'avatar_url' => $user['image_url'] !== null ? (string) $user['image_url'] : null,
            'world' => $progress ? ['ordinal' => (int) $progress['wo'], 'name' => $namesVisible ? (string) $progress['wn'] : null] : null,
            'chapter' => $progress ? ['ordinal' => (int) $progress['co'], 'name' => $namesVisible ? (string) $progress['cn'] : null] : null,
            'rank' => (new RankingService($this->pdo))->rankOf($playerId),
            'points' => $points,
            'registered_at' => Clock::iso((string) $user['created_at']),
            'longest_streak' => $longest,
            'skill_levels' => $skillLevels,
            'badges' => BadgeService::earned($this->pdo, $playerId),
            'featured' => BadgeService::featured($this->pdo, $playerId),
            'squad' => (new SquadView($this->pdo, new SquadService($this->pdo, new RateLimiter($this->pdo))))->badgeFor($playerId),
        ];
    }
}
