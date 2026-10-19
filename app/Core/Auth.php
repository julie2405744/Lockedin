<?php
/**
 * app/Core/Auth.php
 * Session management and authentication helpers — Member 1.
 *
 * All members must use these methods to check login state and roles.
 * Do not start sessions manually elsewhere in the application.
 */

class Auth
{
    private const SESSION_USER_KEY = 'infocus_user';

    /**
     * Start the PHP session with secure defaults.
     * Call this at the top of every public entry point.
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    /**
     * Store the authenticated user in the session.
     * Called by AuthService after successful credential verification.
     *
     * @param array $user Associative array with keys: id, name, email, role
     */
    public static function setUser(array $user): void
    {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        $_SESSION[self::SESSION_USER_KEY] = [
            'id'    => (int) $user['id'],
            'name'  => (string) $user['name'],
            'email' => (string) $user['email'],
            'role'  => (string) $user['role'],
        ];
    }

    /**
     * Return the current authenticated user array, or null if not logged in.
     *
     * @return array|null ['id', 'name', 'email', 'role'] or null
     */
    public static function user(): ?array
    {
        return $_SESSION[self::SESSION_USER_KEY] ?? null;
    }

    /**
     * Return true if a user is currently logged in.
     */
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION[self::SESSION_USER_KEY]);
    }

    /**
     * Return true if the current user has the 'admin' role.
     */
    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? '') === 'admin';
    }

    /**
     * Return true if the current user has the 'student' role.
     */
    public static function isStudent(): bool
    {
        return (self::user()['role'] ?? '') === 'student';
    }

    /**
     * Require the user to be logged in.
     * Redirects to /login.php if not authenticated.
     */
    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            $basePath = defined('BASE_PATH') ? BASE_PATH : '';
            header('Location: ' . $basePath . '/login.php');
            exit;
        }
    }

    /**
     * Require the user to be an admin.
     * Redirects to /login.php if not authenticated, or 403 if not admin.
     */
    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            require __DIR__ . '/../../app/Views/errors/403.php';
            exit;
        }
    }

    /**
     * Require the user to be a student.
     */
    public static function requireStudent(): void
    {
        self::requireLogin();
        if (!self::isStudent()) {
            http_response_code(403);
            require __DIR__ . '/../../app/Views/errors/403.php';
            exit;
        }
    }

    /**
     * Return the authenticated user's ID.
     * Always derive the student identity from the session, never from form input.
     *
     * @throws RuntimeException if not logged in
     */
    public static function id(): int
    {
        $user = self::user();
        if ($user === null) {
            throw new RuntimeException('Auth::id() called without an authenticated user.');
        }
        return $user['id'];
    }

    /**
     * Destroy the current session (logout).
     */
    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }
}
