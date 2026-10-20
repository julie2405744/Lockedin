<?php
/**
 * public/student/reservations.php
 * "My Reservations" history — Member 4 (frontend F4).
 *
 * FRONTEND-BUILD MODE:
 * Rows come from the shared sample dataset and are always the current
 * student's own (the real engine scopes rows by the session user — a URL
 * or form student id is never trusted). No database access and no
 * reservation rules live here.
 *
 * Future integration point:
 *   $reservations = (new ReservationService(...))->getStudentReservations(Auth::id(), $filters);
 * Statuses come from the actual database rows.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Core/Auth.php';
require_once __DIR__ . '/../../app/Core/Csrf.php';
require_once __DIR__ . '/../../app/Core/helpers.php';
require_once __DIR__ . '/../../app/Modules/Reservations/Views/_reservation-helpers.php';

Auth::startSession();
Auth::requireStudent();

$currentUser = Auth::user();
$basePath = defined('BASE_PATH') ? BASE_PATH : '';
$pageStyles = '<link rel="stylesheet" href="' . esc($basePath) . '/assets/css/student-pages.css">';

$sample = require __DIR__ . '/../../app/Modules/Student/_sample-data.php';
$all    = $sample['reservations'];

/* ── Status filter + counts (GET `status`, agreed values only) ──── */
$statuses     = ['pending', 'confirmed', 'rejected', 'cancelled'];
$statusFilter = isset($_GET['status']) && in_array($_GET['status'], $statuses, true)
    ? (string) $_GET['status']
    : '';

$statusCounts = [];
foreach ($all as $row) {
    $statusCounts[$row['status']] = ($statusCounts[$row['status']] ?? 0) + 1;
}

try {
    $nowUtc = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
} catch (Exception $e) {
    $nowUtc = date('Y-m-d H:i:s');
}

$reservations = [];
foreach ($all as $row) {
    if ($statusFilter !== '' && $row['status'] !== $statusFilter) {
        continue;
    }
    $reservations[] = $row;
}

// Newest starts first for a history-style ordering.
usort($reservations, static fn(array $a, array $b): int => strcmp((string) $b['starts_at'], (string) $a['starts_at']));

/* ── Which actions are potentially applicable per row? ──────────
   Sample-mode heuristics; the real enforcer is ReservationService:
     cancel  → pending/confirmed and starts at least 2h ahead
     modify  → confirmed only, starts at least 2h ahead, and no
               existing pending modification                */
foreach ($reservations as &$row) {
    $isFuture = ($row['starts_at'] ?? '') > $nowUtc;
    $rowActions = ['canCancel' => false, 'canModify' => false];
    if ($isFuture && in_array($row['status'], ['pending', 'confirmed'], true)) {
        $rowActions['canCancel'] = true;
    }
    if ($isFuture && $row['status'] === 'confirmed') {
        $pendingForReservation = $sample['modification']['status'] === 'pending'
            && (int) $sample['modification']['reservation_id'] === (int) $row['id'];
        $rowActions['canModify'] = !$pendingForReservation;
    }
    $row['_actions'] = $rowActions;
}
unset($row);

$actions = [];
foreach ($reservations as $row) {
    $actions[(int) $row['id']] = $row['_actions'];
}

/* ── Render ─────────────────────────────────────────────────────── */
$allCount = count($all);
$pageTitle    = 'My Reservations';
$pageSubtitle = 'Every request you have made, with its actual status. Cancel and change actions appear only where the policy allows them.';

ob_start();
require __DIR__ . '/../../app/Modules/Student/Views/reservations-view.php';
$content = ob_get_clean();

require __DIR__ . '/../../app/Views/layouts/app-layout.php';
