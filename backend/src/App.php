<?php
declare(strict_types=1);

namespace Turysta;

use Throwable;
use Turysta\Auth\AccountService;
use Turysta\Auth\AuthService;
use Turysta\Auth\RateLimiter;
use Turysta\Auth\Session;
use Turysta\Controller\AccountController;
use Turysta\Controller\AdminController;
use Turysta\Controller\AuthController;
use Turysta\Controller\GameController;
use Turysta\Controller\HomeController;
use Turysta\Controller\MeController;
use Turysta\Game\JobService;
use Turysta\Game\Settler;
use Turysta\Game\TrainingService;
use Turysta\Http\ApiException;
use Turysta\Http\Request;
use Turysta\Http\Router;

final class App
{
    public static function router(): Router
    {
        $pdo = Database::pdo();
        $auth = new AuthController(new AuthService($pdo, new Mailer(), new RateLimiter($pdo)));
        $settler = new Settler($pdo);
        $training = new TrainingService($pdo, $settler);
        $jobs = new JobService($pdo, $settler);
        $account = new AccountController(new AccountService($pdo, new Mailer(), new RateLimiter($pdo)), new MeController($pdo, $settler, $training, $jobs));
        $me = new MeController($pdo, $settler, $training, $jobs);
        $home = new HomeController($pdo);
        $admin = new AdminController($pdo);
        $game = new GameController($pdo, $settler, $training, $jobs);

        $router = new Router();
        $health = function (): array {
            try {
                Database::pdo()->query('SELECT 1');
                return [200, ['status' => 'ok', 'db' => 'ok', 'time' => gmdate('c')]];
            } catch (Throwable $e) {
                error_log($e->getMessage());
                return [503, ['status' => 'degraded', 'db' => 'error']];
            }
        };
        $router->get('/health', $health);
        $router->get('/v1/health', $health);

        $router->get('/v1/auth/csrf', [$auth, 'csrf']);
        $router->post('/v1/auth/register', [$auth, 'register']);
        $router->post('/v1/auth/login', [$auth, 'login']);
        $router->post('/v1/auth/logout', [$auth, 'logout']);
        $router->get('/v1/auth/verify-email', [$auth, 'verifyEmail']);

        $router->get('/v1/avatars', [$me, 'avatars']);
        $router->get('/v1/me', [$me, 'show']);
        $router->patch('/v1/me', [$me, 'update']);

        $router->patch('/v1/account/profile', [$account, 'updateProfile']);
        $router->put('/v1/account/email', [$account, 'changeEmail']);
        $router->put('/v1/account/password', [$account, 'changePassword']);

        $router->get('/v1/home', [$home, 'show']);
        $router->post('/v1/daily-reward/claim', [$home, 'claimDaily']);

        $router->get('/v1/admin/announcements', [$admin, 'announcements']);
        $router->post('/v1/admin/announcements', [$admin, 'createAnnouncement']);
        $router->put('/v1/admin/announcements/{id}', [$admin, 'updateAnnouncement']);
        $router->delete('/v1/admin/announcements/{id}', [$admin, 'deleteAnnouncement']);

        $router->get('/v1/facts/today', [$game, 'todayFact']);
        $router->get('/v1/skills', [$game, 'skills']);
        $router->get('/v1/world', [$game, 'world']);
        $router->post('/v1/world/advance', [$game, 'advance']);
        $router->get('/v1/training', [$game, 'training']);
        $router->post('/v1/training', [$game, 'addTraining']);
        $router->delete('/v1/training/{id}', [$game, 'removeTraining']);
        $router->get('/v1/jobs', [$game, 'jobs']);
        $router->get('/v1/jobs/active', [$game, 'activeJob']);
        $router->post('/v1/jobs/active/stop', [$game, 'stopJob']);
        $router->post('/v1/jobs/{id}/start', [$game, 'startJob']);
        $router->get('/v1/badges', [$game, 'badges']);
        $router->put('/v1/badges/featured', [$game, 'setFeaturedBadges']);
        $router->get('/v1/ranking', [$game, 'ranking']);
        $router->get('/v1/players/{id}', [$game, 'player']);
        $router->get('/v1/notifications', [$game, 'notifications']);
        $router->post('/v1/notifications/read', [$game, 'readNotifications']);

        return $router;
    }

    public static function run(): void
    {
        try {
            $request = Request::fromGlobals();
            if (!in_array($request->method, ['GET', 'HEAD', 'OPTIONS'], true)) {
                Session::verifyCsrf($request);
            }
            [$status, $body] = self::router()->dispatch($request->method, $request->path, $request);
        } catch (ApiException $e) {
            [$status, $body] = [$e->status, $e->toBody()];
        } catch (Throwable $e) {
            error_log((string) $e);
            [$status, $body] = [500, ['error' => ['code' => 'server_error', 'message' => 'Wystąpił błąd serwera.']]];
        }

        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($body, JSON_UNESCAPED_UNICODE);
    }
}
