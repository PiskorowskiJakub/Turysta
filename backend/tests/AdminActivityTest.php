<?php
declare(strict_types=1);

namespace Turysta\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;
use Turysta\Admin\AdminStatsService;
use Turysta\Auth\AuthService;
use Turysta\Auth\RateLimiter;
use Turysta\Database;
use Turysta\Game\ActivityTracker;
use Turysta\Mailer;

/** Wykresy aktywnosci w panelu admina: godzinowy i dzienny szereg aktywnych graczy (prawdziwy MySQL). */
final class AdminActivityTest extends TestCase
{
    private PDO $pdo;
    private AuthService $auth;

    protected function setUp(): void
    {
        try {
            $this->pdo = Database::pdo();
            $this->pdo->query('SELECT 1 FROM user_active_hours LIMIT 1');
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
    }

    private function userId(string $prefix): int
    {
        $suffix = bin2hex(random_bytes(3));
        $created = $this->auth->register([
            'character_name' => $prefix . $suffix,
            'email' => strtolower($prefix) . "$suffix@example.com",
            'password' => 'bardzo-dlugie-haslo',
            'password_confirm' => 'bardzo-dlugie-haslo',
            'accept_terms' => true,
        ], '10.8.' . random_int(1, 250) . '.' . random_int(1, 250));
        return (int) $created['id'];
    }

    public function testHourlyAndDailySeries(): void
    {
        $a = $this->userId('AktA');
        $b = $this->userId('AktB');
        ActivityTracker::touch($this->pdo, $a);
        ActivityTracker::touch($this->pdo, $a); // druga aktywnosc w tej samej godzinie niczego nie dubluje
        ActivityTracker::touch($this->pdo, $b);

        $svc = new AdminStatsService($this->pdo);
        $hours = $svc->activity('hours', 24);
        $this->assertSame('hours', $hours['view']);
        $this->assertCount(24, $hours['points']);
        $last = $hours['points'][23];
        $this->assertGreaterThanOrEqual(2, $last['active']);
        $this->assertGreaterThanOrEqual(2, $last['registrations']);
        $this->assertNotNull($hours['tracking_since']);

        $rows = (int) $this->pdo->query("SELECT COUNT(*) FROM user_active_hours WHERE user_id = $a")->fetchColumn();
        $this->assertSame(1, $rows);

        $days = $svc->activity('days', 30);
        $this->assertCount(30, $days['points']);
        $this->assertGreaterThanOrEqual(2, $days['points'][29]['active']);

        // Niedozwolony zakres wraca do domyslnego.
        $this->assertCount(24, $svc->activity('hours', 5)['points']);
        $this->assertCount(30, $svc->activity('days', 5)['points']);
    }
}
