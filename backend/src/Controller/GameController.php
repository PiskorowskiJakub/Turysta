<?php
declare(strict_types=1);

namespace Turysta\Controller;

use PDO;
use Turysta\Auth\Session;
use Turysta\Game\BadgeService;
use Turysta\Game\ChapterService;
use Turysta\Game\Clock;
use Turysta\Game\JobService;
use Turysta\Game\PlayerProfileService;
use Turysta\Game\RankingService;
use Turysta\Game\Settler;
use Turysta\Game\TrainingService;
use Turysta\Http\ApiException;
use Turysta\Http\Request;

final class GameController
{
    public function __construct(
        private PDO $pdo,
        private Settler $settler,
        private TrainingService $training,
        private JobService $jobs
    ) {
    }

    /** Ciekawostka dnia: ta sama dla wszystkich w danej dobie UTC. @return array{0:int,1:array<string,mixed>} */
    public function todayFact(Request $r): array
    {
        $ids = $this->pdo->query('SELECT id FROM daily_facts WHERE active = 1 ORDER BY id')->fetchAll(\PDO::FETCH_COLUMN);
        if (!$ids) {
            return [200, ['text' => null]];
        }
        $id = (int) $ids[intdiv(time(), 86400) % count($ids)];
        $stmt = $this->pdo->prepare('SELECT text FROM daily_facts WHERE id = ?');
        $stmt->execute([$id]);
        return [200, ['text' => (string) $stmt->fetchColumn()]];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function skills(Request $r): array
    {
        $uid = $this->uid($r);
        $this->settler->settleUser($uid);
        return [200, $this->training->skills($uid)];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function world(Request $r): array
    {
        $uid = $this->uid($r);
        $this->settler->settleUser($uid);
        return [200, (new ChapterService($this->pdo))->overview($uid)];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function advance(Request $r): array
    {
        return [201, $this->training->addChapter($this->uid($r))];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function training(Request $r): array
    {
        $uid = $this->uid($r);
        $this->settler->settleUser($uid);
        return [200, $this->training->state($uid)];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function addTraining(Request $r): array
    {
        $uid = $this->uid($r);
        $skillId = $r->body['skill_id'] ?? null;
        if (!is_int($skillId)) {
            throw new ApiException(422, 'validation_failed', 'Wybierz umiejętność.', ['skill_id' => 'Wybierz umiejętność.']);
        }
        return [201, $this->training->add($uid, $skillId)];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function removeTraining(Request $r, array $params = []): array
    {
        $uid = $this->uid($r);
        return [200, $this->training->remove($uid, (int) ($params['id'] ?? 0))];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function jobs(Request $r): array
    {
        $uid = $this->uid($r);
        $this->settler->settleUser($uid);
        return [200, $this->jobs->list($uid)];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function activeJob(Request $r): array
    {
        $uid = $this->uid($r);
        $this->settler->settleUser($uid);
        return [200, ['server_time' => gmdate('c'), 'active' => $this->jobs->active($uid)]];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function startJob(Request $r, array $params = []): array
    {
        $uid = $this->uid($r);
        return [201, $this->jobs->start($uid, (int) ($params['id'] ?? 0))];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function stopJob(Request $r): array
    {
        $uid = $this->uid($r);
        return [200, $this->jobs->stop($uid, ($r->body['confirm'] ?? false) === true)];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function notifications(Request $r): array
    {
        $uid = $this->uid($r);
        $this->settler->settleUser($uid);
        $stmt = $this->pdo->prepare(
            'SELECT id, type, payload, created_at, read_at FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 30'
        );
        $stmt->execute([$uid]);
        $items = array_map(static fn(array $n) => [
            'id' => (int) $n['id'],
            'type' => $n['type'],
            'payload' => $n['payload'] ? json_decode((string) $n['payload'], true) : new \stdClass(),
            'created_at' => str_replace(' ', 'T', (string) $n['created_at']) . 'Z',
            'read' => $n['read_at'] !== null,
        ], $stmt->fetchAll());
        return [200, ['notifications' => $items]];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function readNotifications(Request $r): array
    {
        $uid = $this->uid($r);
        $this->pdo->prepare('UPDATE notifications SET read_at = UTC_TIMESTAMP() WHERE user_id = ? AND read_at IS NULL')
            ->execute([$uid]);
        return [200, ['message' => 'Oznaczono jako przeczytane.']];
    }

    /** Zdobyte odznaki z data zdobycia. @return array{0:int,1:array<string,mixed>} */
    public function badges(Request $r): array
    {
        $uid = $this->uid($r);
        $this->settler->settleUser($uid);
        BadgeService::sync($this->pdo, $uid, Clock::now($this->pdo));
        return [200, ['badges' => BadgeService::earned($this->pdo, $uid), 'featured' => BadgeService::featured($this->pdo, $uid)]];
    }

    /** Wybor wyroznionych odznak (do 5): {"groups": ["fitness", "special_streak"]}. @return array{0:int,1:array<string,mixed>} */
    public function setFeaturedBadges(Request $r): array
    {
        $uid = $this->uid($r);
        return [200, ['featured' => BadgeService::setFeatured($this->pdo, $uid, $r->body['groups'] ?? null)]];
    }

    /** Publiczny profil gracza (karta i odznaki). @param array<string,string> $params @return array{0:int,1:array<string,mixed>} */
    public function player(Request $r, array $params = []): array
    {
        $uid = $this->uid($r);
        $this->settler->settleUser($uid);
        return [200, (new PlayerProfileService($this->pdo))->forPlayer($uid, (int) ($params['id'] ?? 0))];
    }

    /** Ranking graczy: ?page=&per_page=&q=&world= @return array{0:int,1:array<string,mixed>} */
    public function ranking(Request $r): array
    {
        $uid = $this->uid($r);
        $this->settler->settleUser($uid);
        $q = $r->query;
        return [200, (new RankingService($this->pdo))->page(
            $uid,
            (int) ($q['page'] ?? 1),
            (int) ($q['per_page'] ?? RankingService::DEFAULT_PER_PAGE),
            is_string($q['q'] ?? null) ? $q['q'] : '',
            (int) ($q['world'] ?? 0)
        )];
    }

    private function uid(Request $r): int
    {
        return (int) Session::requireUser($r)['id'];
    }
}
