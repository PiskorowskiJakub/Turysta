<?php
declare(strict_types=1);

namespace Turysta\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;
use Turysta\Auth\AuthService;
use Turysta\Auth\RateLimiter;
use Turysta\Auth\UnverifiedCleaner;
use Turysta\Database;
use Turysta\Http\ApiException;
use Turysta\Mailer;
use Turysta\Support\BugReportService;

/** Zgoda na regulamin, usuwanie niepotwierdzonych kont i zgloszenia bledow (prawdziwy MySQL). */
final class SupportFlowTest extends TestCase
{
    private PDO $pdo;
    private AuthService $auth;
    private string $suffix;

    protected function setUp(): void
    {
        try {
            $this->pdo = Database::pdo();
            $this->pdo->query('SELECT 1 FROM bug_reports LIMIT 1');
        } catch (Throwable $e) {
            $this->markTestSkipped('Brak bazy z migracjami: ' . $e->getMessage());
        }
        $mailer = new class extends Mailer {
            public function send(string $to, string $subject, string $text, ?string $html = null): bool
            {
                return true;
            }
        };
        $this->auth = new AuthService($this->pdo, $mailer, new RateLimiter($this->pdo));
        $this->suffix = bin2hex(random_bytes(3));
    }

    /** @return array<string,mixed> */
    private function register(string $prefix, bool $accept = true): array
    {
        return $this->auth->register([
            'character_name' => $prefix . $this->suffix,
            'email' => strtolower($prefix) . "{$this->suffix}@example.com",
            'password' => 'bardzo-dlugie-haslo',
            'password_confirm' => 'bardzo-dlugie-haslo',
            'accept_terms' => $accept,
        ], '10.9.' . random_int(1, 250) . '.' . random_int(1, 250));
    }

    private function exists(int $id): bool
    {
        return (bool) $this->pdo->query("SELECT 1 FROM users WHERE id = $id")->fetchColumn();
    }

    public function testRegistrationRequiresTermsAndStoresAcceptance(): void
    {
        try {
            $this->register('Bez', false);
            $this->fail('Rejestracja bez zgody powinna byc odrzucona.');
        } catch (ApiException $e) {
            $this->assertArrayHasKey('accept_terms', $e->fields);
        }

        $user = $this->register('Zgoda');
        $row = $this->pdo->query("SELECT terms_accepted_at, email_unverified_since FROM users WHERE id = {$user['id']}")->fetch();
        $this->assertNotNull($row['terms_accepted_at']);
        $this->assertNotNull($row['email_unverified_since']);
    }

    public function testCleanerRemovesOnlyExpiredUnverifiedPlayers(): void
    {
        $old = $this->register('Stary');
        $oldVerified = $this->register('Potwierdzony');
        $fresh = $this->register('Swiezy');
        $changed = $this->register('Zmieniony');
        $admin = $this->register('Admin');

        $ago = 'UTC_TIMESTAMP() - INTERVAL 31 DAY';
        foreach ([$old, $oldVerified, $changed, $admin] as $u) {
            $this->pdo->exec("UPDATE users SET created_at = $ago, email_unverified_since = $ago WHERE id = {$u['id']}");
        }
        $this->pdo->exec("UPDATE users SET email_verified_at = UTC_TIMESTAMP() WHERE id = {$oldVerified['id']}");
        $this->pdo->exec("UPDATE users SET email_unverified_since = UTC_TIMESTAMP() - INTERVAL 2 DAY WHERE id = {$changed['id']}");
        $this->pdo->exec("UPDATE users SET role = 'admin' WHERE id = {$admin['id']}");

        $cleaner = new UnverifiedCleaner($this->pdo);
        $candidates = $cleaner->candidates();
        $this->assertContains((int) $old['id'], $candidates);
        foreach ([$oldVerified, $fresh, $changed, $admin] as $keep) {
            $this->assertNotContains((int) $keep['id'], $candidates);
        }

        // Usuwamy tylko wlasne konto testowe, zeby nie ruszac innych danych w bazie deweloperskiej.
        $this->assertSame(1, $cleaner->purge([(int) $old['id']]));
        $this->assertFalse($this->exists((int) $old['id']));
        foreach ([$oldVerified, $fresh, $changed, $admin] as $keep) {
            $this->assertTrue($this->exists((int) $keep['id']));
        }
    }

    public function testDeadlineIsThirtyDaysFromRegistrationOrEmailChange(): void
    {
        $user = $this->register('Termin');
        $row = $this->pdo->query("SELECT * FROM users WHERE id = {$user['id']}")->fetch();
        $deadline = new \DateTimeImmutable(UnverifiedCleaner::deadline($row), new \DateTimeZone('UTC'));
        $days = (int) round(($deadline->getTimestamp() - time()) / 86400);
        $this->assertSame(UnverifiedCleaner::RETENTION_DAYS, $days);
    }

    public function testBugReportIsStoredListedAndManaged(): void
    {
        $user = $this->register('Zglaszajacy');
        $service = new BugReportService($this->pdo, new RateLimiter($this->pdo));

        try {
            $service->create($user, ['message' => 'krotko'], '10.8.0.1', 'UA');
            $this->fail('Za krotki opis powinien byc odrzucony.');
        } catch (ApiException $e) {
            $this->assertArrayHasKey('message', $e->fields);
        }

        $id = $service->create($user, ['message' => 'Przycisk nie dziala po kliknieciu.', 'page' => '/praca?x=1'], '10.8.0.2', 'TestAgent');
        $list = $service->list(1, 'new');
        $mine = array_values(array_filter($list['rows'], static fn(array $r): bool => $r['id'] === $id));
        $this->assertCount(1, $mine);
        $this->assertSame('/praca', $mine[0]['page']);
        $this->assertSame('TestAgent', $mine[0]['user_agent']);
        $this->assertSame((int) $user['id'], $mine[0]['user']['id']);

        $service->setStatus($id, 'resolved');
        $this->assertSame('resolved', $this->pdo->query("SELECT status FROM bug_reports WHERE id = $id")->fetchColumn());

        try {
            $service->setStatus($id, 'cokolwiek');
            $this->fail('Nieznany status powinien byc odrzucony.');
        } catch (ApiException $e) {
            $this->assertArrayHasKey('status', $e->fields);
        }

        $service->delete($id);
        $this->assertFalse($this->pdo->query("SELECT 1 FROM bug_reports WHERE id = $id")->fetchColumn());
    }

    public function testBugReportsAreRemovedWithTheAccount(): void
    {
        $user = $this->register('Usuwany');
        $service = new BugReportService($this->pdo, new RateLimiter($this->pdo));
        $id = $service->create($user, ['message' => 'Zgloszenie przed usunieciem konta.'], '10.8.0.3', null);

        (new \Turysta\Auth\UserDeleter($this->pdo))->delete((int) $user['id']);
        $this->assertFalse($this->pdo->query("SELECT 1 FROM bug_reports WHERE id = $id")->fetchColumn());
    }
}
