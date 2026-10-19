<?php
/**
 * public/register.php
 * Student registration entry point — Member 1.
 *
 * GET  : show the registration form
 * POST : validate input and create student account via AuthService
 */

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
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Your session has expired. Please refresh the page and try again.';
    } else {
        $name            = trim($_POST['name']             ?? '');
        $email           = trim($_POST['email']            ?? '');
        $password        = $_POST['password']              ?? '';
        $passwordConfirm = $_POST['password_confirm']      ?? '';
        $old             = ['name' => $name, 'email' => $email];

        // Field validation
        if ($name === '') {
            $errors['name'] = 'Full name is required.';
        } elseif (mb_strlen($name) > 120) {
            $errors['name'] = 'Name must be 120 characters or fewer.';
        }

        if ($email === '') {
            $errors['email'] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        } elseif (mb_strlen($email) > 190) {
            $errors['email'] = 'Email address is too long.';
        }

        if ($password === '') {
            $errors['password'] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }

        if ($passwordConfirm === '') {
            $errors['password_confirm'] = 'Please confirm your password.';
        } elseif ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            $authService = new AuthService($pdo);
            $result      = $authService->register($name, $email, $password);

            if ($result['success']) {
                // Log the new student in immediately
                $authService->login($email, $password);
                header('Location: ' . BASE_PATH . '/student/dashboard.php');
                exit;
            } else {
                // Map service-level errors back to fields
                if ($result['error'] === 'email_taken') {
                    $errors['email'] = 'This email address is already registered. Try signing in instead.';
                } else {
                    $errors['general'] = 'Registration could not be completed. Please try again.';
                }
            }
        }
    }
}

$csrfField = Csrf::field();
$pageTitle = 'Create account';
$basePath = defined('BASE_PATH') ? BASE_PATH : '';

ob_start();
require __DIR__ . '/../app/Modules/Auth/register-view.php';
$content = ob_get_clean();

require __DIR__ . '/../app/Views/layouts/public-layout.php';
