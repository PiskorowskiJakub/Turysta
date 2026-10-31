<?php
declare(strict_types=1);

namespace Turysta\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;
use Turysta\Admin\AdminAudit;
use Turysta\Admin\AdminLogService;
use Turysta\Admin\AdminStatsService;
use Turysta\Admin\AdminUserService;
use Turysta\Admin\FactService;
use Turysta\Auth\AuthService;
use Turysta\Auth\RateLimiter;
use Turysta\Database;
use Turysta\Http\ApiException;
use Turysta\Mailer;

/** Test integracyjny na prawdziwym MySQL: zarzadzanie graczami, blokady, korekty portfela, logi i statystyki. */
final class AdminFlowTest extends TestCase
{
    private PDO $pdo;
    private AuthService $auth;
    private AdminUserService $users;
    private string $suffix;

    protected function setUp(): void
    {
        try {
            $this->pdo = Database::pdo();
            $this->pdo->query('SELECT 1 FROM admin_audit LIMIT 1');
        } catch (Throwable $e) {
            $this->markTestSkipped('Brak bazy z migracjami: ' . $e->getMessage());
        }
        $mailer = new class extends Mailer {
            public function send(string $to, string $subject, string $text): bool
            {
                return true;
            }
        };
        $this->auth = new AuthService($this->pdo, $mailer, new RateLimiter($this->pdo));
        $this->users = new AdminUserService($this->pdo);
        $this->suffix = bin2hex(random_bytes(4));
    }

    /** @return array{0:int,1:string} id i haslo nowego gracza */
    private function player(string $tag = 'P'): array
    {
        $password = 'bardzo-dlugie-haslo';
        $user = $this->auth->register([
            'character_name' => $tag . $this->suffix,
            'email' => strtolower($tag) . "{$this->suffix}@example.com",
            'password' => $password,
            'password_confirm' => $password,
        ], '10.2.0.' . random_int(1, 250));
        return [(int) $user['id'], $password];
    }

    public function testSuspensionBlocksLoginUntilLifted(): void
    {
        [$admin] = $this->player('A');
        [$id, $password] = $this->player('S');
        $email = strtolower('S') . "{$this->suffix}@example.com";

        $details = $this->users->setStatus($admin, $id, 'suspend', 'Spam na czacie', 3);
        $this->assertSame(3, $details['days']);
        $this->assertSame('suspended', $this->users->detail($id)['status']);

        try {
            $this->auth->login($email, $password, '10.2.1.' . random_int(1, 250));
            $this->fail('Zawieszony gracz nie powinien sie zalogowac.');
        } catch (ApiException $e) {
            $this->assertSame('suspended', $e->errorCode);
            $this->assertStringContainsString('Spam na czacie', $e->getMessage());
        }

        $this->users->setStatus($admin, $id, 'unsuspend', '', 0);
        $this->assertSame('active', $this->users->detail($id)['status']);
        $this->assertSame($id, (int) $this->auth->login($email, $password, '10.2.2.' . random_int(1, 250))['id']);
    }

    public function testBanNeedsReasonAndCannotTargetSelfOrAdmins(): void
    {
        [$admin] = $this->player('A');
        [$id, $password] = $this->player('B');
        $email = "b{$this->suffix}@example.com";

        try {
            $this->users->setStatus($admin, $id, 'ban', '', 0);
            $this->fail('Blokada bez powodu powinna byc odrzucona.');
        } catch (ApiException $e) {
            $this->assertArrayHasKey('reason', $e->fields);
        }

        try {
            $this->users->setStatus($admin, $admin, 'ban', 'test', 0);
            $this->fail('Nie mozna blokowac siebie.');
        } catch (ApiException $e) {
            $this->assertSame(422, $e->status);
        }

        $this->pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([$id]);
        try {
            $this->users->setStatus($admin, $id, 'ban', 'test', 0);
            $this->fail('Nie mozna blokowac administratora.');
        } catch (ApiException $e) {
            $this->assertSame(422, $e->status);
        }
        $this->pdo->prepare("UPDATE users SET role = 'player' WHERE id = ?")->execute([$id]);

        $this->pdo->prepare("INSERT INTO sessions (id, user_id, data) VALUES (?, ?, '')")->execute(['ban' . $this->suffix, $id]);
        $this->users->setStatus($admin, $id, 'ban', 'Oszustwo', 0);
        $this->assertSame('banned', $this->users->detail($id)['status']);
        $this->assertSame(0, (int) $this->pdo->query("SELECT COUNT(*) FROM sessions WHERE id = 'ban{$this->suffix}'")->fetchColumn());

        try {
            $this->auth->login($email, $password, '10.2.3.' . random_int(1, 250));
            $this->fail('Zablokowany gracz nie powinien sie zalogowac.');
        } catch (ApiException $e) {
            $this->assertSame('banned', $e->errorCode);
        }

        $this->users->setStatus($admin, $id, 'unban', '', 0);
        $this->assertSame('active', $this->users->detail($id)['status']);
    }

    public function testWalletAdjustmentIsLoggedAndNeverGoesNegative(): void
    {
        [$admin] = $this->player('A');
        [$id] = $this->player('W');
        $before = $this->users->detail($id)['wallet'];

        $res = $this->users->adjustWallet($admin, $id, 'points', 500, 'Rekompensata za błąd');
        $this->assertSame($before['points'] + 500, $res['balance_after']);
        $this->assertSame($before['points'] + 500, $this->users->detail($id)['wallet']['points']);

        $entry = (new AdminLogService($this->pdo))->ledger($id, 1, 'points')['rows'][0];
        $this->assertSame('admin_adjust', $entry['reason']);
        $this->assertSame(500, $entry['amount']);

        try {
            $this->users->adjustWallet($admin, $id, 'points', -1_000_000, 'Za duża kara');
            $this->fail('Saldo nie moze spasc ponizej zera.');
        } catch (ApiException $e) {
            $this->assertArrayHasKey('amount', $e->fields);
        }
        $this->assertSame($before['points'] + 500, $this->users->detail($id)['wallet']['points']);

        try {
            $this->users->adjustWallet($admin, $id, 'coins', 100, '');
            $this->fail('Powod jest wymagany.');
        } catch (ApiException $e) {
            $this->assertArrayHasKey('reason', $e->fields);
        }
    }

    public function testUpdateValidatesNameEmailAndOwnRole(): void
    {
        [$admin] = $this->player('A');
        [$id] = $this->player('U');
        [$other] = $this->player('V');
        $otherName = 'V' . $this->suffix;

        try {
            $this->users->update($admin, $id, ['character_name' => $otherName]);
            $this->fail('Zajeta nazwa powinna byc odrzucona.');
        } catch (ApiException $e) {
            $this->assertArrayHasKey('character_name', $e->fields);
        }

        try {
            // Gracz z AuthService ma role player, wiec zmiana na inna rola jest probą zmiany wlasnej roli.
            $this->users->update($admin, $admin, ['role' => 'moderator']);
            $this->fail('Nie mozna zmienic wlasnej roli.');
        } catch (ApiException $e) {
            $this->assertArrayHasKey('role', $e->fields);
        }

        $changes = $this->users->update($admin, $id, ['character_name' => 'Nowa' . $this->suffix, 'role' => 'moderator', 'email_verified' => true]);
        $this->assertSame('moderator', $changes['role']['to']);
        $detail = $this->users->detail($id);
        $this->assertSame('Nowa' . $this->suffix, $detail['name']);
        $this->assertSame('moderator', $detail['role']);
        $this->assertTrue($detail['email_verified']);
        $this->assertSame([], $this->users->update($admin, $id, ['role' => 'moderator']));
        $this->assertNotSame($other, $id);
    }

    public function testListFiltersByStatusSearchAndRole(): void
    {
        [$admin] = $this->player('A');
        [$id] = $this->player('L');
        $this->users->setStatus($admin, $id, 'suspend', 'test', 2);

        $res = $this->users->list(1, 'L' . $this->suffix, 'suspended', '', 'new');
        $this->assertSame(1, $res['total']);
        $this->assertSame($id, $res['rows'][0]['id']);
        $this->assertSame('suspended', $res['rows'][0]['status']);

        $this->assertSame(0, $this->users->list(1, 'L' . $this->suffix, 'banned', '', 'new')['total']);
        $this->assertSame(0, $this->users->list(1, 'L' . $this->suffix, '', 'admin', 'new')['total']);
        // Szukanie po liczbie dopasowuje tez nazwy i e-maile zawierajace te cyfry, wiec na bazie z innymi graczami wynikow moze byc wiecej niz 1.
        $byId = $this->users->list(1, (string) $id, '', 'player', 'new');
        $this->assertContains($id, array_column($byId['rows'], 'id'));
    }

    public function testAuditLogAndLoginLogs(): void
    {
        [$admin] = $this->player('A');
        [$id, $password] = $this->player('G');
        $email = "g{$this->suffix}@example.com";

        AdminAudit::log($this->pdo, $admin, 'user.update', $id, ['role' => ['from' => 'player', 'to' => 'moderator']], '10.2.9.1');
        $audit = (new AdminLogService($this->pdo))->audit(1, $id, '');
        $this->assertSame(1, $audit['total']);
        $this->assertSame('user.update', $audit['rows'][0]['action']);
        $this->assertSame($admin, $audit['rows'][0]['admin']['id']);
        $this->assertSame('moderator', $audit['rows'][0]['details']['role']['to']);

        $user = $this->auth->login($email, $password, '10.2.9.2');
        $this->auth->recordLogin((int) $user['id'], '10.2.9.2', 'PHPUnit');
        $logins = (new AdminLogService($this->pdo))->logins(1, $id, '');
        $this->assertSame(1, $logins['total']);
        $this->assertSame('10.2.9.2', $logins['rows'][0]['ip']);
        $this->assertGreaterThanOrEqual(1, (new AdminLogService($this->pdo))->logins(1, null, '10.2.9.2')['total']);
    }

    public function testStatsOverviewHasExpectedShape(): void
    {
        $this->player('T');
        $s = (new AdminStatsService($this->pdo))->overview();
        $this->assertGreaterThanOrEqual(1, $s['accounts']['total']);
        $this->assertGreaterThanOrEqual(1, $s['accounts']['new_24h']);
        $this->assertCount(AdminStatsService::SERIES_DAYS, $s['registrations']);
        $this->assertCount(AdminStatsService::SERIES_DAYS, $s['logins']);
        $this->assertGreaterThanOrEqual(1, array_sum(array_column($s['registrations'], 'count')));
        $this->assertArrayHasKey('dau', $s['activity']);
        $this->assertArrayHasKey('coins_cents', $s['economy']);
    }

    public function testFactsCrud(): void
    {
        $svc = new FactService($this->pdo);
        $f = $svc->create(['text' => 'Ciekawostka testowa ' . $this->suffix, 'active' => true]);
        $this->assertTrue($f['active']);
        $u = $svc->update($f['id'], ['text' => 'Zmieniona ' . $this->suffix, 'active' => false]);
        $this->assertFalse($u['active']);
        $this->assertContains('Zmieniona ' . $this->suffix, array_column($svc->all(), 'text'));

        try {
            $svc->create(['text' => '']);
            $this->fail('Pusta tresc powinna byc odrzucona.');
        } catch (ApiException $e) {
            $this->assertArrayHasKey('text', $e->fields);
        }
        $svc->delete($f['id']);
        $this->assertNotContains('Zmieniona ' . $this->suffix, array_column($svc->all(), 'text'));
    }
}
