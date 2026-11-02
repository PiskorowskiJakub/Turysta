<?php
declare(strict_types=1);

namespace Turysta\Controller;

use PDO;
use Turysta\Auth\AccountService;
use Turysta\Auth\Session;
use Turysta\Game\Clock;
use Turysta\Game\JobService;
use Turysta\Game\Settler;
use Turysta\Game\StatsService;
use Turysta\Game\TrainingService;
use Turysta\Http\ApiException;
use Turysta\Http\Request;

final class MeController
{
    public function __construct(
        private PDO $pdo,
        private Settler $settler,
        private TrainingService $training,
        private JobService $jobs
    ) {
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function show(Request $r): array
    {
        $user = Session::requireUser($r);
        $this->settler->settleUser((int) $user['id']);
        return [200, $this->profile($user)];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function update(Request $r): array
    {
        $user = Session::requireUser($r);
        if ($user['tutorial_done_at'] !== null) {
            // Po samouczku plec i awatar zmienia sie przez /v1/account/profile (z limitem zmiany plci).
            throw new ApiException(409, 'tutorial_done', 'Samouczek został już ukończony.');
        }
        $errors = [];

        $gender = $r->body['gender'] ?? null;
        if (!is_string($gender) || !in_array($gender, ['f', 'm', 'x'], true)) {
            $errors['gender'] = 'Wybierz płeć postaci.';
        }

        $avatarId = $r->body['avatar_id'] ?? null;
        $avatar = false;
        if (is_int($avatarId)) {
            $stmt = $this->pdo->prepare('SELECT id FROM avatars WHERE id = ?');
            $stmt->execute([$avatarId]);
            $avatar = $stmt->fetchColumn();
        }
        if ($avatar === false) {
            $errors['avatar_id'] = 'Wybierz awatar z listy.';
        }
        if ($errors) {
            throw new ApiException(422, 'validation_failed', 'Popraw zaznaczone pola.', $errors);
        }

        $this->pdo->prepare(
            'UPDATE users SET gender = ?, avatar_id = ?, tutorial_done_at = COALESCE(tutorial_done_at, UTC_TIMESTAMP()) WHERE id = ?'
        )->execute([$gender, $avatarId, $user['id']]);

        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        return [200, $this->profile($stmt->fetch())];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function avatars(Request $r): array
    {
        $rows = $this->pdo->query('SELECT id, name, gender, image_url FROM avatars ORDER BY id')->fetchAll();
        return [200, ['avatars' => $rows]];
    }

    /** @param array<string,mixed> $user */
    private function genderChangeAt(array $user): ?string
    {
        $next = AccountService::genderChangeAvailableAt($user['gender_changed_at'] ?? null);
        if ($next === null || Clock::parse($next) <= Clock::now($this->pdo)) {
            return null;
        }
        return Clock::iso($next);
    }

    /**
     * @param array<string,mixed> $user
     * @return array<string,mixed>
     */
    private function profile(array $user): array
    {
        $uid = (int) $user['id'];

        $stmt = $this->pdo->prepare('SELECT coins_cents, tickets, points FROM wallets WHERE user_id = ?');
        $stmt->execute([$uid]);
        $wallet = $stmt->fetch() ?: ['coins_cents' => 0, 'tickets' => 0, 'points' => 0];

        $stmt = $this->pdo->prepare(
            'SELECT s.code, s.name, us.level FROM user_skills us
             JOIN skill_definitions s ON s.id = us.skill_id
             WHERE us.user_id = ? AND (s.world_ordinal IS NULL OR us.level > 0) ORDER BY s.id'
        );
        $stmt->execute([$uid]);
        $skills = array_map(
            fn(array $s) => ['code' => $s['code'], 'name' => $s['name'], 'level' => (int) $s['level']],
            $stmt->fetchAll()
        );

        $stmt = $this->pdo->prepare(
            'SELECT c.world_id, p.chapter_id, w.ordinal AS world_ordinal, w.name AS world_name, c.ordinal AS chapter_ordinal, c.name AS chapter_name
             FROM player_progress p JOIN chapters c ON c.id = p.chapter_id JOIN worlds w ON w.id = c.world_id
             WHERE p.user_id = ?'
        );
        $stmt->execute([$uid]);
        $progress = $stmt->fetch() ?: null;

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL');
        $stmt->execute([$uid]);

        return [
            'server_time' => gmdate('c'),
            'user' => [
                'id' => $uid,
                'email' => $user['email'],
                'character_name' => $user['character_name'],
                'avatar_id' => $user['avatar_id'] !== null ? (int) $user['avatar_id'] : null,
                'gender' => $user['gender'],
                'gender_change_available_at' => $this->genderChangeAt($user),
                'role' => $user['role'],
                'email_verified' => $user['email_verified_at'] !== null,
                'email_verification_deadline' => $user['email_verified_at'] === null
                    ? \Turysta\Game\Clock::iso(\Turysta\Auth\UnverifiedCleaner::deadline($user))
                    : null,
                'tutorial_done' => $user['tutorial_done_at'] !== null,
            ],
            'wallet' => [
                'coins_cents' => (int) $wallet['coins_cents'],
                'tickets' => (int) $wallet['tickets'],
                'points' => (int) $wallet['points'],
            ],
            'skills' => $skills,
            'progress' => $progress ? [
                'world_id' => (int) $progress['world_id'],
                'chapter_id' => (int) $progress['chapter_id'],
                'world_ordinal' => (int) $progress['world_ordinal'],
                'world_name' => $progress['world_name'],
                'chapter_ordinal' => (int) $progress['chapter_ordinal'],
                'chapter_name' => $progress['chapter_name'],
            ] : null,
            'stats' => (new StatsService($this->pdo))->forUser($uid),
            'active' => ['training' => $this->training->state($uid)['active'], 'job' => $this->jobs->active($uid)],
            'notifications_unread' => (int) $stmt->fetchColumn(),
        ];
    }
}
