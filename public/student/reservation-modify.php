<?php
/**
 * public/student/reservation-modify.php
 * Modification request form — Member 4 (frontend F4).
 *
 * Wraps Member 3's modification-form-view.php. During frontend build,
 * POSTs receive a clear preview notice; the real engine calls
 * ReservationService::requestModification() and shows its validation or
 * conflict answer while preserving the safe values the student typed.
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
$pageScripts = '<script src="' . esc($basePath) . '/assets/js/reservation-forms.js" defer></script>';

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

/* Only confirmed reservations may be modified — display only; the
   service re-checks everything. Others render a blocked notice.      */
try {
    $nowUtc = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
} catch (Exception $e) {
    $nowUtc = date('Y-m-d H:i:s');
}

$canRequestModification = $reservation['status'] === 'confirmed' && $reservation['starts_at'] > $nowUtc;

$modification = is_array($sample['modification'] ?? null)
    && (int) ($sample['modification']['reservation_id'] ?? 0) === $detailsId
    ? $sample['modification']
    : null;

$hasPending = is_array($modification) && ($modification['status'] ?? '') === 'pending';

if (!$canRequestModification) {
    $pageTitle    = 'Request a change';
    $pageSubtitle = '';
    ob_start(); ?>
    <div class="empty-state" style="padding:var(--space-10) 0;">
        <div class="empty-state__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
        </div>
        <h2 class="empty-state__title">This reservation cannot be modified</h2>
        <p class="empty-state__description">
            Only confirmed reservations starting later can be changed, and the request
            must be submitted at least two hours before the start.
        </p>
        <a href="<?= esc($basePath) ?>/student/reservation-details.php?id=<?= $detailsId ?>" class="btn btn--primary">Back to the reservation</a>
    </div>
    <?php $content = ob_get_clean();
    require __DIR__ . '/../../app/Views/layouts/app-layout.php';
    exit;
}

/* Room options for the "propose a different room" picklist */
$rooms = [];
foreach ($sample['rooms'] as $room) {
    $rooms[] = [
        'id'         => $room['id'],
        'name'       => $room['name'],
        'floor_name' => $room['floor_name'],
        'capacity'   => $room['capacity'],
        'category'   => $room['category'],
    ];
}

/* ── POST → preview-safe notice with safe repopulation ─────────── */
$feedback = null;
$old      = [];
$errors   = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'proposed_room_id'   => (string) ($_POST['proposed_room_id'] ?? ''),
        'proposed_starts_at' => (string) ($_POST['proposed_starts_at'] ?? ''),
        'proposed_ends_at'   => (string) ($_POST['proposed_ends_at'] ?? ''),
        'reason'             => (string) ($_POST['reason'] ?? ''),
    ];

    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
        $feedback = [
            'type'    => 'warning',
            'title'   => 'Session expired',
            'message' => 'Your form session expired. Refresh the page and try again.',
        ];
    } elseif ($hasPending) {
        $feedback = [
            'type'    => 'warning',
            'title'   => 'A change request is already open',
            'message' => 'Only one pending change per reservation is allowed. Wait for the review of your existing request.',
        ];
    } else {
        $feedback = [
            'type'    => 'info',
            'title'   => 'Frontend build only — nothing was submitted',
            'message' => 'Once the reservation engine is integrated, this form calls '
                . 'ReservationService::requestModification() which validates the proposed '
                . 'interval the same way as a new booking. Nothing was submitted now.',
        ];
    }
}

try {
    $nowCairo    = new DateTimeImmutable('now', new DateTimeZone('Africa/Cairo'));
    $minDateTime = $nowCairo->format('Y-m-d\TH:i');
    $maxDateTime = $nowCairo->modify('+7 days')->format('Y-m-d\TH:i');
} catch (Exception $e) {
    $minDateTime = null;
    $maxDateTime = null;
}

// Member 3's form component shows its "one change already open" alert when
// $hasPendingModification is true (real engine: query for status = pending).
$hasPendingModification = $hasPending;
$pendingModificationUrl = $hasPending
    ? $basePath . '/student/reservation-details.php?id=' . $detailsId
    : null;

$pageTitle    = 'Request a change — ' . $reservation['room_name'];
$pageSubtitle = 'Proposed changes are reviewed by an administrator. The reservation stays as it is until the change is approved.';

ob_start();
require __DIR__ . '/../../app/Modules/Reservations/Views/modification-form-view.php';
$content = ob_get_clean();

require __DIR__ . '/../../app/Views/layouts/app-layout.php';
