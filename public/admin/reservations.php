<?php
/**
 * public/admin/reservations.php
 * Admin reservation queue — Member 5.
 *
 * GET : list reservations filtered by status and room.
 * POST: not used here — approving and rejecting happens on the detail screen
 *       and always goes through ReservationService.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Core/Auth.php';
require_once __DIR__ . '/../../app/Core/Csrf.php';
require_once __DIR__ . '/../../app/Core/helpers.php';
require_once __DIR__ . '/../../app/Repositories/RoomRepository.php';
require_once __DIR__ . '/../../app/Repositories/AdminReviewRepository.php';
require_once __DIR__ . '/../../app/Services/AdminReviewService.php';

Auth::startSession();
Auth::requireAdmin();

$currentUser = Auth::user();
$basePath    = defined('BASE_PATH') ? BASE_PATH : '';

$reviewService = new AdminReviewService(new AdminReviewRepository($pdo), $pdo);
$roomRepo      = new RoomRepository($pdo);

/* ── Filters ─────────────────────────────────────────────────── */
$statuses   = ['pending', 'confirmed', 'rejected', 'cancelled'];
$status     = trim((string) ($_GET['status'] ?? 'pending'));
$roomId     = (int) ($_GET['room_id'] ?? 0);

if (!in_array($status, $statuses, true)) {
    $status = 'pending';
}

$filters = ['status' => $status, 'room_id' => $roomId > 0 ? $roomId : null];

/* ── Data ────────────────────────────────────────────────────── */
$queue        = $reviewService->getPendingReservations($filters);
$reservations = $queue['data']['rows'] ?? [];
$rooms        = $roomRepo->findAllRooms();

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

/* ── Page ────────────────────────────────────────────────────── */
$pageTitle    = 'Reservations';
$pageSubtitle = 'Review the reservation requests that students have submitted.';
$pageStyles   = '<link rel="stylesheet" href="' . esc($basePath) . '/assets/css/admin-pages.css">';
$pageScripts  = '<script src="' . esc($basePath) . '/assets/js/report-filters.js" defer></script>';

ob_start();
require __DIR__ . '/../../app/Modules/Admin/Views/reservations-view.php';
$content = ob_get_clean();

require __DIR__ . '/../../app/Views/layouts/app-layout.php';
