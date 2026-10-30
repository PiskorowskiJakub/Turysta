<?php
declare(strict_types=1);

namespace Turysta;

use Throwable;
use Turysta\Auth\AuthService;
use Turysta\Auth\RateLimiter;
use Turysta\Auth\Session;
use Turysta\Controller\AuthController;
use Turysta\Controller\MeController;
use Turysta\Http\ApiException;
use Turysta\Http\Request;
use Turysta\Http\Router;

final class App
{
    public static function router(): Router
    {
        $pdo = Database::pdo();
        $auth = new AuthController(new AuthService($pdo, new Mailer(), new RateLimiter($pdo)));
        $me = new MeController($pdo);

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
