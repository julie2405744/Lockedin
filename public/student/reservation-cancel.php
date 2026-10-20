<?php
/**
 * public/student/reservation-cancel.php
 * Cancellation confirmation — Member 4 (frontend F4).
 *
 * Wraps Member 3's cancellation-confirmation-view.php. In the real engine
 * the POST handler calls ReservationService::cancelReservation() and shows
 * its returned message. During frontend build, POSTs receive a clear
 * preview notice — nothing is changed, and no cancellation rules are
 * evaluated here.
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
$pageStyles = '<link rel="stylesheet" href="' . esc($basePath) . '/assets/css/student-pages.css">'
            . '<link rel="stylesheet" href="' . esc($basePath) . '/assets/css/reservation-forms.css">';

$sample = require __DIR__ . '/../../app/Modules/Student/_sample-data.php';

$detailsId = isset($_GET['id']) && ctype_digit((string) $_GET['id']) ? (int) $_GET['id'] : 0;

$reservation = null;
foreach ($sample['reservations'] as $row) {
    if ((int) $row['id'] === $detailsId) {
        $reservation = $row;
        break;
    }
}

if ($reservation === null) {
    $pageTitle    = 'Reservation not found';
    $pageSubtitle = '';
    ob_start(); ?>
    <div class="empty-state" style="padding:var(--space-10) 0;">
        <div class="empty-state__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V2h6v2M9 10h6M9 14h6M9 18h4"/>
            </svg>
        </div>
        <h2 class="empty-state__title">Reservation not available</h2>
        <p class="empty-state__description">This reservation doesn't exist or doesn't belong to your account.</p>
        <a href="<?= esc($basePath) ?>/student/reservations.php" class="btn btn--primary">Go to My Reservations</a>
    </div>
    <?php $content = ob_get_clean();
    require __DIR__ . '/../../app/Views/layouts/app-layout.php';
    exit;
}

/* ── Feasibility display only (real rules live in ReservationService) ── */
try {
    $nowUtc = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
} catch (Exception $e) {
    $nowUtc = date('Y-m-d H:i:s');
}

$canCancel     = in_array($reservation['status'], ['pending', 'confirmed'], true)
              && $reservation['starts_at'] > $nowUtc;
$blockedReason = !$canCancel ? 'This reservation can no longer be cancelled. Cancellation is possible until two hours before the start, and rejected or cancelled bookings are final.' : null;

/* ── POST → preview-safe notice ────────────────────────────────── */
$feedback = null;
$old      = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['reason'] = (string) ($_POST['reason'] ?? '');
    $feedback = Csrf::validate($_POST['csrf_token'] ?? '')
        ? [
            'type'    => 'info',
            'title'   => 'Frontend build only — nothing was cancelled',
            'message' => 'Once the reservation engine is integrated, this confirmation submits to '
                . 'ReservationService::cancelReservation() and the room is released for other students. '
                . 'A cancellation inside its two-hour cutoff would be refused with the service reason.',
        ]
        : [
            'type'    => 'warning',
            'title'   => 'Session expired',
            'message' => 'Your form session expired. Refresh the page and try again.',
        ];
}

$pageTitle    = 'Cancel reservation #' . $detailsId;
$pageSubtitle = 'Reveals the deliberate second step before any cancellation.';

ob_start();
require __DIR__ . '/../../app/Modules/Reservations/Views/cancellation-confirmation-view.php';
$content = ob_get_clean();

require __DIR__ . '/../../app/Views/layouts/app-layout.php';
