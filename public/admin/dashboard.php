<?php
/**
 * public/admin/dashboard.php
 * Admin dashboard — Member 5.
 *
 * Shows the summary metrics from ReportService and the two review queues.
 * Every number comes from the service; this page only arranges the data.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Core/Auth.php';
require_once __DIR__ . '/../../app/Core/Csrf.php';
require_once __DIR__ . '/../../app/Core/helpers.php';
require_once __DIR__ . '/../../app/Repositories/ReportRepository.php';
require_once __DIR__ . '/../../app/Repositories/AdminReviewRepository.php';
require_once __DIR__ . '/../../app/Services/ReportService.php';
require_once __DIR__ . '/../../app/Services/AdminReviewService.php';

Auth::startSession();
Auth::requireAdmin();

$currentUser = Auth::user();
$basePath    = defined('BASE_PATH') ? BASE_PATH : '';

$reportService = new ReportService(new ReportRepository($pdo));
$reviewService = new AdminReviewService(new AdminReviewRepository($pdo), $pdo);

/* ── Data ────────────────────────────────────────────────────── */
$summary = $reportService->getDashboardSummary();
$metrics = $summary['data'] ?? [];

$pendingReservations  = $reviewService->getPendingReservations(['limit' => 5])['data']['rows'] ?? [];
$pendingModifications = $reviewService->getPendingModifications(['limit' => 5])['data']['rows'] ?? [];

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

/* ── Page ────────────────────────────────────────────────────── */
$pageTitle    = 'Admin Dashboard';
$pageSubtitle = 'Reservations waiting for a decision, modification requests, and room activity at a glance.';
$pageStyles   = '<link rel="stylesheet" href="' . esc($basePath) . '/assets/css/admin-pages.css">';
$pageScripts  = '<script src="' . esc($basePath) . '/assets/js/report-filters.js" defer></script>';

ob_start();
require __DIR__ . '/../../app/Modules/Admin/Views/dashboard-view.php';
$content = ob_get_clean();

require __DIR__ . '/../../app/Views/layouts/app-layout.php';
