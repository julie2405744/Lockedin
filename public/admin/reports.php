<?php
/**
 * public/admin/reports.php
 * Reservation and utilization reports — Member 5.
 *
 * The filters are applied to both reports. Every figure is calculated by
 * ReportService; this page never repeats a calculation.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Core/Auth.php';
require_once __DIR__ . '/../../app/Core/Csrf.php';
require_once __DIR__ . '/../../app/Core/helpers.php';
require_once __DIR__ . '/../../app/Repositories/RoomRepository.php';
require_once __DIR__ . '/../../app/Repositories/ReportRepository.php';
require_once __DIR__ . '/../../app/Services/ReportService.php';

Auth::startSession();
Auth::requireAdmin();

$currentUser = Auth::user();
$basePath    = defined('BASE_PATH') ? BASE_PATH : '';

$reportService = new ReportService(new ReportRepository($pdo));
$roomRepo      = new RoomRepository($pdo);

/* ── Filters ─────────────────────────────────────────────────── */
$from   = trim((string) ($_GET['from'] ?? ''));
$to     = trim((string) ($_GET['to'] ?? ''));
$roomId = (int) ($_GET['room_id'] ?? 0);

$filters = [
    'from'    => $from,
    'to'      => $to,
    'room_id' => $roomId > 0 ? $roomId : null,
];

/* ── Data ────────────────────────────────────────────────────── */
$reservationResult = $reportService->getReservationReport($filters);
$utilizationResult = $reportService->getRoomUtilizationReport($filters);

$reservationReport = $reservationResult['data'] ?? ['filters' => [], 'rows' => [], 'totals' => []];
$utilizationReport = $utilizationResult['data'] ?? ['filters' => [], 'rows' => []];

/* Keep the chosen window visible even when the service had to correct it. */
$filters = [
    'from'    => (string) ($reservationReport['filters']['from'] ?? $from),
    'to'      => (string) ($reservationReport['filters']['to'] ?? $to),
    'room_id' => $roomId > 0 ? $roomId : null,
];

$rooms = $roomRepo->findAllRooms();

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

/* Report a rejected date range instead of silently showing an empty report. */
if (!$reservationResult['success'] || !$utilizationResult['success']) {
    $message = $reservationResult['success'] ? $utilizationResult['message'] : $reservationResult['message'];

    $flash = [
        'type'    => 'danger',
        'title'   => 'The report filters could not be applied',
        'message' => $message,
    ];
}

/* ── Page ────────────────────────────────────────────────────── */
$pageTitle    = 'Reports';
$pageSubtitle = 'Reservation activity and room utilization for a chosen window.';
$pageStyles   = '<link rel="stylesheet" href="' . esc($basePath) . '/assets/css/admin-pages.css">';
$pageScripts  = '<script src="' . esc($basePath) . '/assets/js/report-filters.js" defer></script>';

ob_start();
require __DIR__ . '/../../app/Modules/Admin/Views/reports-view.php';
$content = ob_get_clean();

require __DIR__ . '/../../app/Views/layouts/app-layout.php';
