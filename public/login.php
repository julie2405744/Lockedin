<?php
/**
 * public/login.php
 * Login page entry point — Member 1.
 *
 * GET  : show the login form
 * POST : validate credentials and create session via AuthService
 */

// Bootstrap
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Core/Auth.php';
require_once __DIR__ . '/../app/Core/Csrf.php';
require_once __DIR__ . '/../app/Services/AuthService.php';

Auth::startSession();

// Redirect already-authenticated users
if (Auth::isLoggedIn()) {
    $role = Auth::user()['role'] ?? 'student';
    header('Location: ' . BASE_PATH . '/' . ($role === 'admin' ? 'admin' : 'student') . '/dashboard.php');
    exit;
}

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Your session has expired. Please refresh the page and try again.';
    } else {
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');
        $old      = ['email' => $email];

        // Basic field presence check
        if ($email === '') {
            $errors[] = 'Email address is required.';
        }
        if ($password === '') {
            $errors[] = 'Password is required.';
        }

        if (empty($errors)) {
            $authService = new AuthService($pdo);
            $result      = $authService->login($email, $password);

            if ($result['success']) {
                // Session ID regenerated inside AuthService::login()
                $role = Auth::user()['role'] ?? 'student';
                header('Location: ' . BASE_PATH . '/' . ($role === 'admin' ? 'admin' : 'student') . '/dashboard.php');
                exit;
            } else {
                // Safe message — never reveal which field is wrong
                $errors[] = 'Incorrect email or password. Please try again.';
            }
        }
    }
}

// Render
$csrfField = Csrf::field();
$pageTitle = 'Sign in';
$basePath = defined('BASE_PATH') ? BASE_PATH : '';

ob_start();
require __DIR__ . '/../app/Modules/Auth/login-view.php';
$content = ob_get_clean();

require __DIR__ . '/../app/Views/layouts/public-layout.php';
