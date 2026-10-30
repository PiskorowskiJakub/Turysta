<?php
declare(strict_types=1);

use Turysta\Http\Router;
use Turysta\Database;

require __DIR__ . '/../vendor/autoload.php';

$router = new Router();

$router->get('/health', function (): array {
    try {
        Database::pdo()->query('SELECT 1');
        return [200, ['status' => 'ok', 'db' => 'ok', 'time' => gmdate('c')]];
    } catch (Throwable $e) {
        error_log($e->getMessage());
        return [503, ['status' => 'degraded', 'db' => 'error']];
    }
});

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = preg_replace('#^/api#', '', $path) ?: '/';

[$status, $body] = $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $path);

http_response_code($status);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($body, JSON_UNESCAPED_UNICODE);
