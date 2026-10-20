<?php
/**
 * public/admin/modifications.php
 * Admin modification queue — Member 5.
 *
 * GET : list modification requests filtered by status.
 * POST: not used here — the decision happens on the detail screen and always
 *       goes through ReservationService.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Core/Auth.php';
require_once __DIR__ . '/../../app/Core/Csrf.php';
require_once __DIR__ . '/../../app/Core/helpers.php';
require_once __DIR__ . '/../../app/Repositories/AdminReviewRepository.php';
require_once __DIR__ . '/../../app/Services/AdminReviewService.php';

Auth::startSession();
Auth::requireAdmin();

$currentUser = Auth::user();
$basePath    = defined('BASE_PATH') ? BASE_PATH : '';

$reviewService = new AdminReviewService(new AdminReviewRepository($pdo), $pdo);

/* ── Filters ─────────────────────────────────────────────────── */
$statuses = ['pending', 'approved', 'rejected', 'invalidated'];
$status   = trim((string) ($_GET['status'] ?? 'pending'));

if (!in_array($status, $statuses, true)) {
    $status = 'pending';
}

$filters = ['status' => $status];

/* ── Data ────────────────────────────────────────────────────── */
$queue         = $reviewService->getPendingModifications($filters);
$modifications = $queue['data']['rows'] ?? [];

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

/* ── Page ────────────────────────────────────────────────────── */
$pageTitle    = 'Modifications';
$pageSubtitle = 'Review the changes students have asked for on their confirmed reservations.';
$pageStyles   = '<link rel="stylesheet" href="' . esc($basePath) . '/assets/css/admin-pages.css">';
$pageScripts  = '<script src="' . esc($basePath) . '/assets/js/report-filters.js" defer></script>';

ob_start();
require __DIR__ . '/../../app/Modules/Admin/Views/modifications-view.php';
$content = ob_get_clean();

require __DIR__ . '/../../app/Views/layouts/app-layout.php';
