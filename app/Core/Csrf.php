<?php
/**
 * app/Core/Csrf.php
 * CSRF token generation and validation — Member 1.
 *
 * Usage in a form:
 *     echo Csrf::field();
 *
 * Validation in a POST handler:
 *     if (!Csrf::validate($_POST['csrf_token'] ?? '')) { ... }
 */

class Csrf
{
    private const TOKEN_KEY    = 'csrf_token';
    private const FIELD_NAME   = 'csrf_token';
    private const TOKEN_LENGTH = 32; // bytes

    /**
     * Generate a secure token and store it in the session.
     * Returns the token string.
     */
    public static function generate(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(self::TOKEN_LENGTH));
        }

        return $_SESSION[self::TOKEN_KEY];
    }

    /**
     * Return an HTML hidden input containing the CSRF token.
     */
    public static function field(): string
    {
        $token = self::generate();
        return '<input type="hidden" name="' . self::FIELD_NAME . '" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Validate a submitted token against the session token.
     * Uses hash_equals() to prevent timing attacks.
     *
     * @param string $submittedToken The value from the POST request.
     * @return bool
     */
    public static function validate(string $submittedToken): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            return false;
        }

        $stored = $_SESSION[self::TOKEN_KEY] ?? '';

        if ($stored === '' || $submittedToken === '') {
            return false;
        }

        return hash_equals($stored, $submittedToken);
    }

    /**
     * Rotate the CSRF token (call after any validation, optional).
     * This prevents token reuse on subsequent requests.
     */
    public static function rotate(): void
    {
        unset($_SESSION[self::TOKEN_KEY]);
        self::generate();
    }
}
