<?php
/**
 * public/student/dashboard.php
 * Student dashboard — Member 4 (frontend F4).
 *
 * FRONTEND-BUILD MODE:
 * Statistics and the upcoming list are derived from the shared sample
 * dataset while Member 3's ReservationService is not integrated yet.
 * No database access and no booking rules live here.
 *
 * Future integration point:
 *   $reservations = (new ReservationService(...))->getStudentReservations(Auth::id());
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Core/Auth.php';
require_once __DIR__ . '/../../app/Core/Csrf.php';
require_once __DIR__ . '/../../app/Core/helpers.php';
require_once __DIR__ . '/../../app/Modules/Reservations/Views/_reservation-helpers.php';

Auth::startSession();
Auth::requireStudent();

$basePath = defined('BASE_PATH') ? BASE_PATH : '';
$pageStyles = '<link rel="stylesheet" href="' . esc($basePath) . '/assets/css/student-pages.css">';

$currentUser = Auth::user();
$sample      = require __DIR__ . '/../../app/Modules/Student/_sample-data.php';

/* ── Segment the sample reservations (compiled lines replace this) ── */
try {
    $nowUtc = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
} catch (Exception $e) {
    $nowUtc = date('Y-m-d H:i:s');
}

$upcoming = [];
$pastCount = 0;
$pendingCount = 0;

foreach ($sample['reservations'] as $reservation) {
    $isUpcoming = ($reservation['starts_at'] ?? '') > $nowUtc
        && in_array($reservation['status'] ?? '', ['pending', 'confirmed'], true);

    if ($isUpcoming) {
        $upcoming[] = $reservation;
        if (($reservation['status'] ?? '') === 'pending') {
            $pendingCount++;
        }
    } else {
        $pastCount++;
    }
}

// Order upcoming by — not booked yet — chronological closest first.
usort($upcoming, static fn(array $a, array $b): int => strcmp((string) $a['starts_at'], (string) $b['starts_at']));

$modification             = $sample['modification'] ?? null;
$hasPendingModification   = is_array($modification) && ($modification['status'] ?? '') === 'pending';

$stats = [
    'upcoming' => count($upcoming),
    'pending'  => $pendingCount,
    'past'     => $pastCount,
];

/* ── Render ─────────────────────────────────────────────────────── */
$pageTitle    = 'Dashboard';
$pageSubtitle = 'Welcome back, ' . $currentUser['name'] . '. Here is where your study space planning stands.';

ob_start();
require __DIR__ . '/../../app/Modules/Student/Views/dashboard-view.php';
$content = ob_get_clean();

require __DIR__ . '/../../app/Views/layouts/app-layout.php';
