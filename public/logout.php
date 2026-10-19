<?php
/**
 * public/logout.php
 * Logs the authenticated user out and redirects to login — Member 1.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Core/Auth.php';
require_once __DIR__ . '/../app/Core/Csrf.php';

Auth::startSession();

// Only allow POST with a valid CSRF token to prevent logout CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Csrf::validate($_POST['csrf_token'] ?? '')) {
    Auth::logout();
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // GET logout is allowed for simplicity via direct link
    Auth::logout();
}

header('Location: ' . BASE_PATH . '/login.php');
exit;
