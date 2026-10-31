<?php
declare(strict_types=1);

namespace Turysta\Auth;

use Turysta\Database;
use Turysta\Http\ApiException;
use Turysta\Http\Request;

final class Session
{
    private const LIFETIME = 1209600; // 14 dni

    public static function start(Request $request): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $secure = (getenv('APP_ENV') ?: 'dev') === 'prod';
        ini_set('session.gc_maxlifetime', (string) self::LIFETIME);
        session_name('turysta_sid');
        session_set_cookie_params([
            'lifetime' => self::LIFETIME,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        session_set_save_handler(new DbSessionHandler(Database::pdo(), $request->ip(), $request->userAgent()), true);
        session_start();
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION['csrf'];
    }

    public static function verifyCsrf(Request $request): void
    {
        self::start($request);
        $sent = $request->header('x-csrf-token') ?? '';
        if (empty($_SESSION['csrf']) || !hash_equals((string) $_SESSION['csrf'], $sent)) {
            throw new ApiException(403, 'csrf_invalid', 'Sesja wygasła lub żądanie jest nieprawidłowe. Odśwież stronę.');
        }
    }

    public static function login(int $userId, int $loginEventId): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['login_event_id'] = $loginEventId;
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $params['path'],
            'secure' => $params['secure'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /** @return array<string,mixed> wiersz uzytkownika */
    public static function requireUser(Request $request): array
    {
        self::start($request);
        $id = $_SESSION['user_id'] ?? null;
        if (!$id) {
            throw new ApiException(401, 'unauthorized', 'Musisz się zalogować.');
        }
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([(int) $id]);
        $user = $stmt->fetch();
        if (!$user || $user['status'] === 'banned') {
            self::destroy();
            throw new ApiException(401, 'unauthorized', 'Musisz się zalogować.');
        }
        \Turysta\Game\ActivityTracker::touch(Database::pdo(), (int) $user['id']);
        return $user;
    }
}
