<?php
/**
 * public/index.php
 * Application root — redirect to the appropriate home page.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Core/Auth.php';

Auth::startSession();

if (Auth::isLoggedIn()) {
    $role = Auth::user()['role'] ?? 'student';
    header('Location: ' . BASE_PATH . '/' . ($role === 'admin' ? 'admin' : 'student') . '/dashboard.php');
} else {
    header('Location: ' . BASE_PATH . '/landing.php');
}
exit;
