<?php
/**
 * public/frontend-preview.php
 * Frontend preview up to Member 3. For local testing only.
 * Shows Member 1 (auth), Member 2 (room management), Member 3 (reservations).
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Core/Csrf.php';
require_once __DIR__ . '/../app/Core/helpers.php';
require_once __DIR__ . '/../app/Modules/Reservations/Views/_reservation-helpers.php';

$basePath = defined('BASE_PATH') ? BASE_PATH : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frontend Preview — Up to Member 3</title>
    <link rel="stylesheet" href="<?= esc($basePath) ?>/assets/css/variables.css">
    <link rel="stylesheet" href="<?= esc($basePath) ?>/assets/css/base.css">
    <link rel="stylesheet" href="<?= esc($basePath) ?>/assets/css/layout.css">
    <link rel="stylesheet" href="<?= esc($basePath) ?>/assets/css/components.css">
    <link rel="stylesheet" href="<?= esc($basePath) ?>/assets/css/room-management.css">
    <link rel="stylesheet" href="<?= esc($basePath) ?>/assets/css/reservation-forms.css">
</head>
<body style="background:var(--color-background);">
    <div class="page-shell">
        <header class="site-header" role="banner">
            <div class="site-header__inner">
                <span class="brand-wordmark">In<span>Focus</span></span>
                <span style="color:var(--color-muted);font-size:var(--font-size-sm);">Frontend Preview (up to Member 3)</span>
            </div>
        </header>
        <main class="page-main">
            <div class="page-container">
                <div class="page-header">
                    <div>
                        <h1 class="page-header__title">Complete Frontend Preview</h1>
                        <p class="page-header__subtitle">Test all pages built by Members 1, 2, and 3</p>
                    </div>
                </div>

                <div class="panel" style="margin-bottom:var(--space-6);">
                    <div class="panel__header"><h2 class="panel__title">Member 1 — Authentication</h2></div>
                    <div class="panel__body" style="display:flex;flex-wrap:wrap;gap:var(--space-3);">
                        <a class="btn btn--primary" href="<?= esc($basePath) ?>/landing.php">Landing</a>
                        <a class="btn btn--secondary" href="<?= esc($basePath) ?>/login.php">Login</a>
                        <a class="btn btn--secondary" href="<?= esc($basePath) ?>/register.php">Register</a>
                        <a class="btn btn--secondary" href="<?= esc($basePath) ?>/profile.php">Profile</a>
                        <a class="btn btn--ghost" href="<?= esc($basePath) ?>/logout.php">Logout</a>
                    </div>
                </div>

                <div class="panel" style="margin-bottom:var(--space-6);">
                    <div class="panel__header"><h2 class="panel__title">Member 2 — Room Management (Admin)</h2></div>
                    <div class="panel__body" style="display:flex;flex-wrap:wrap;gap:var(--space-3);">
                        <a class="btn btn--primary" href="<?= esc($basePath) ?>/admin/dashboard.php">Admin Dashboard</a>
                        <a class="btn btn--secondary" href="<?= esc($basePath) ?>/admin/floors.php">Floors</a>
                        <a class="btn btn--secondary" href="<?= esc($basePath) ?>/admin/rooms.php">Rooms</a>
                        <a class="btn btn--secondary" href="<?= esc($basePath) ?>/admin/facilities.php">Facilities</a>
                        <a class="btn btn--secondary" href="<?= esc($basePath) ?>/admin/operating-hours.php">Operating Hours</a>
                        <a class="btn btn--secondary" href="<?= esc($basePath) ?>/admin/closures.php">Closures</a>
                    </div>
                    <div class="panel__body" style="border-top:1px solid var(--color-border);">
                        <p class="form-hint">Note: Some admin pages require login as admin. If you get redirected to login, just sign in.</p>
                    </div>
                </div>

                <div class="panel" style="margin-bottom:var(--space-6);">
                    <div class="panel__header"><h2 class="panel__title">Member 4 — Student Experience</h2></div>
                    <div class="panel__body" style="display:flex;flex-wrap:wrap;gap:var(--space-3);">
                        <a class="btn btn--primary" href="<?= esc($basePath) ?>/student/dashboard.php">Student Dashboard</a>
                        <a class="btn btn--secondary" href="<?= esc($basePath) ?>/student/rooms.php">Find a Study Room</a>
                        <a class="btn btn--secondary" href="<?= esc($basePath) ?>/student/room-details.php?id=3">Room Details</a>
                        <a class="btn btn--secondary" href="<?= esc($basePath) ?>/student/reservations.php">My Reservations</a>
                        <a class="btn btn--secondary" href="<?= esc($basePath) ?>/student/reservation-details.php?id=103">Reservation Details</a>
                        <a class="btn btn--secondary" href="<?= esc($basePath) ?>/student/reservation-cancel.php?id=103">Cancel Flow</a>
                        <a class="btn btn--secondary" href="<?= esc($basePath) ?>/student/reservation-modify.php?id=103">Modify Flow</a>
                    </div>
                    <div class="panel__body" style="border-top:1px solid var(--color-border);">
                        <p class="form-hint">Student pages require a signed-in student account (frontend build: sample data; room discovery filters it live; reservation actions show the preview notice until the engine lands).</p>
                    </div>
                </div>

                <div class="panel" style="margin-bottom:var(--space-6);">
                    <div class="panel__header"><h2 class="panel__title">Member 3 — Reservation Components</h2></div>
                    <div class="panel__body" style="display:flex;flex-wrap:wrap;gap:var(--space-3);">
                        <a class="btn btn--primary" href="<?= esc($basePath) ?>/reservation-preview.php">Full Component Preview (requires login)</a>
                    </div>
                    <div class="panel__body" style="border-top:1px solid var(--color-border);">
                        <p class="form-hint">The preview shows booking form, cancellation, modification form, comparison, actions, and feedback alerts.</p>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel__header"><h2 class="panel__title">Quick Start</h2></div>
                    <div class="panel__body">
                        <ol style="display:flex;flex-direction:column;gap:var(--space-2);margin-left:var(--space-6);">
                            <li>Make sure Apache and MySQL are running in XAMPP</li>
                            <li>Click <strong>Login</strong> or <strong>Register</strong> to create an account</li>
                            <li>After logging in as student, open <strong>Full Component Preview</strong> to see all Member 3 components</li>
                            <li>Try the admin pages - you'll need an admin account, or just see the UI structure</li>
                        </ol>
                    </div>
                </div>
            </div>
        </main>
        <footer class="site-footer">
            <p>&copy; <?= date('Y') ?> InFocus — Study Room Booking System</p>
        </footer>
    </div>
</body>
</html>
