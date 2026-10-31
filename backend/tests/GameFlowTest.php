<?php
declare(strict_types=1);

namespace Turysta\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;
use Turysta\Auth\AuthService;
use Turysta\Auth\RateLimiter;
use Turysta\Database;
use Turysta\Game\JobService;
use Turysta\Game\Settler;
use Turysta\Game\TrainingService;
use Turysta\Game\Wallet;
use Turysta\Http\ApiException;
use Turysta\Mailer;

/** Test integracyjny na prawdziwym MySQL (wymaga zmigrowanej bazy). */
final class GameFlowTest extends TestCase
{
    private PDO $pdo;
    private Settler $settler;
    private TrainingService $training;
    private JobService $jobs;
    private int $userId;

    protected function setUp(): void
    {
        try {
            $this->pdo = Database::pdo();
            $this->pdo->query('SELECT duration_seconds FROM training_queue LIMIT 1');
        } catch (Throwable $e) {
            $this->markTestSkipped('Brak bazy z migracjami: ' . $e->getMessage());
        }
        $this->settler = new Settler($this->pdo);
        $this->training = new TrainingService($this->pdo, $this->settler);
        $this->jobs = new JobService($this->pdo, $this->settler);

        $mailer = new class extends Mailer {
            public function send(string $to, string $subject, string $text): bool
            {
                return true;
            }
        };
        $auth = new AuthService($this->pdo, $mailer, new RateLimiter($this->pdo));
        $suffix = bin2hex(random_bytes(4));
        $user = $auth->register([
            'character_name' => 'Gra' . $suffix,
            'email' => "gra{$suffix}@example.com",
            'password' => 'bardzo-dlugie-haslo',
            'password_confirm' => 'bardzo-dlugie-haslo',
        ], '10.1.' . random_int(1, 250) . '.' . random_int(1, 250));
        $this->userId = (int) $user['id'];
    }

    private function skillId(string $code = 'fitness'): int
    {
        return (int) $this->pdo->query("SELECT id FROM skill_definitions WHERE code = '$code'")->fetchColumn();
    }

    private function jobId(string $code): int
    {
        return (int) $this->pdo->query("SELECT id FROM job_definitions WHERE code = '$code'")->fetchColumn();
    }

    private function coins(): int
    {
        return (int) $this->pdo->query("SELECT coins_cents FROM wallets WHERE user_id = {$this->userId}")->fetchColumn();
    }

    private function fund(int $cents): void
    {
        $this->pdo->beginTransaction();
        Wallet::apply($this->pdo, $this->userId, 'coins', $cents, 'test_fund');
        $this->pdo->commit();
    }

    public function testQueueHasThreeSlotsAndRefundsQueuedInFull(): void
    {
        $this->fund(1000000);
        $this->training->add($this->userId, $this->skillId());
        $this->training->add($this->userId, $this->skillId());
        $state = $this->training->add($this->userId, $this->skillId());

        $this->assertNotNull($state['active']);
        $this->assertCount(2, $state['queue']);
        $this->assertSame([2, 3], array_map(fn($q) => $q['target_level'], $state['queue']));

        try {
            $this->training->add($this->userId, $this->skillId('trade'));
            $this->fail('Czwarte szkolenie powinno byc odrzucone');
        } catch (ApiException $e) {
            $this->assertSame('queue_full', $e->errorCode);
        }

        // Usuniecie srodkowego poziomu: pelny zwrot, a kolejny poziom przesuwa sie w dol
        // (nizszy koszt i czas, roznica wraca do portfela).
        $before = $this->coins();
        $middle = $state['queue'][0];
        $last = $state['queue'][1];
        $after = $this->training->remove($this->userId, $middle['id']);
        $this->assertSame($before + $last['cost_cents'], $this->coins());
        $this->assertCount(1, $after['queue']);
        $this->assertSame(2, $after['queue'][0]['target_level']);
        $this->assertSame($middle['cost_cents'], $after['queue'][0]['cost_cents']);

        $before = $this->coins();
        $rest = $after['queue'][0];
        $this->training->remove($this->userId, $rest['id']);
        $this->assertSame($before + $rest['cost_cents'], $this->coins());
    }

    public function testCancellingActiveRefundsHalfAndStartsNext(): void
    {
        $this->fund(1000000);
        $this->training->add($this->userId, $this->skillId());
        $state = $this->training->add($this->userId, $this->skillId('trade'));
        $active = $state['active'];

        $before = $this->coins();
        $after = $this->training->remove($this->userId, $active['id']);

        $this->assertSame($before + intdiv($active['cost_cents'], 2), $this->coins());
        $this->assertSame('trade', $after['active']['skill']['code']);
        $this->assertCount(0, $after['queue']);
    }

    public function testSettlementCompletesTrainingAndChainsNextFromEndTime(): void
    {
        $this->fund(1000000);
        $this->training->add($this->userId, $this->skillId());
        $this->training->add($this->userId, $this->skillId());

        $this->pdo->exec(
            "UPDATE training_queue SET ends_at = UTC_TIMESTAMP() - INTERVAL 1 MINUTE
             WHERE user_id = {$this->userId} AND status = 'active'"
        );
        $endedAt = (string) $this->pdo->query(
            "SELECT ends_at FROM training_queue WHERE user_id = {$this->userId} AND status = 'active'"
        )->fetchColumn();

        $this->settler->settleUser($this->userId);
        $this->settler->settleUser($this->userId); // idempotentnie

        $level = (int) $this->pdo->query(
            "SELECT level FROM user_skills WHERE user_id = {$this->userId} AND skill_id = {$this->skillId()}"
        )->fetchColumn();
        $this->assertSame(1, $level);
        $this->assertSame(10, (int) $this->pdo->query("SELECT points FROM wallets WHERE user_id = {$this->userId}")->fetchColumn());

        $next = $this->pdo->query(
            "SELECT started_at, status FROM training_queue WHERE user_id = {$this->userId} AND target_level = 2"
        )->fetch();
        $this->assertSame('active', $next['status']);
        $this->assertSame($endedAt, $next['started_at']);
    }

    public function testProportionalJobPaysForWorkedTime(): void
    {
        $this->jobs->start($this->userId, $this->jobId('market'));
        $this->pdo->exec(
            "UPDATE job_runs SET started_at = UTC_TIMESTAMP() - INTERVAL 4 HOUR, ends_at = UTC_TIMESTAMP() + INTERVAL 4 HOUR
             WHERE user_id = {$this->userId} AND status = 'active'"
        );

        $result = $this->jobs->stop($this->userId, false);

        $this->assertEqualsWithDelta(12350, $result['payout_cents'], 20);
        $this->assertSame($result['payout_cents'], $this->coins());
        $this->assertNull($result['active']);

        // Przerwanie pracy proporcjonalnej nie uruchamia przerwy - mozna zaczac od razu.
        $again = $this->jobs->start($this->userId, $this->jobId('market'));
        $this->assertNotNull($again['active']);
    }

    public function testLongJobNeedsConfirmationAndPaysNothing(): void
    {
        $this->jobs->start($this->userId, $this->jobId('hostel'));

        try {
            $this->jobs->stop($this->userId, false);
            $this->fail('Przerwanie dlugiej pracy wymaga potwierdzenia');
        } catch (ApiException $e) {
            $this->assertSame('job_cancel_forfeit', $e->errorCode);
        }

        $result = $this->jobs->stop($this->userId, true);
        $this->assertSame(0, $result['payout_cents']);
        $this->assertSame(0, $this->coins());
    }

    public function testFinishedJobIsPaidInFullOnRead(): void
    {
        $this->jobs->start($this->userId, $this->jobId('market'));
        $this->pdo->exec(
            "UPDATE job_runs SET started_at = UTC_TIMESTAMP() - INTERVAL 9 HOUR, ends_at = UTC_TIMESTAMP() - INTERVAL 1 HOUR
             WHERE user_id = {$this->userId} AND status = 'active'"
        );

        $this->settler->settleUser($this->userId);

        $this->assertSame(24700, $this->coins());
        $this->assertSame(24, (int) $this->pdo->query("SELECT points FROM wallets WHERE user_id = {$this->userId}")->fetchColumn());
    }
}
