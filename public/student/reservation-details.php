<?php
/**
 * public/student/reservation-details.php
 * One reservation's detail page — Member 4 (frontend F4).
 *
 * FRONTEND-BUILD MODE: rows come from the sample dataset; in the real
 * engine the handler verifies the reservation belongs to the session
 * user before rendering its private details (`getReservationDetails`
 * receives Auth::id() and refuses foreign rows). Not found, foreign, or
 * deleted records render the same neutral "not available" state.
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
        <p class="empty-state__description">
            This reservation doesn't exist, doesn't belong to your account, or the link is out of date.
        </p>
        <a href="<?= esc($basePath) ?>/student/reservations.php" class="btn btn--primary">Go to My Reservations</a>
    </div>
    <?php $content = ob_get_clean();
    require __DIR__ . '/../../app/Views/layouts/app-layout.php';
    exit;
}

try {
    $nowUtc = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
} catch (Exception $e) {
    $nowUtc = date('Y-m-d H:i:s');
}

$isFuture = ($reservation['starts_at'] ?? '') > $nowUtc;
$actions  = ['canCancel' => false, 'canModify' => false];

if ($isFuture && in_array($reservation['status'], ['pending', 'confirmed'], true)) {
    $actions['canCancel'] = true;
}

$modification = null;
if (is_array($sample['modification'] ?? null)
    && (int) ($sample['modification']['reservation_id'] ?? 0) === $detailsId) {
    $modification = $sample['modification'];
    // modifier allowed only for confirmed without an open/past request cycle
    if ($isFuture && $reservation['status'] === 'confirmed'
        && ($modification['status'] ?? '') === 'pending') {
        $actions['canModify'] = false;
    } elseif ($isFuture && $reservation['status'] === 'confirmed') {
        $actions['canModify'] = true;
    }
}

$pageTitle    = 'Reservation #' . $detailsId;
$pageSubtitle = 'All times shown in Africa/Cairo local time.';

ob_start();
require __DIR__ . '/../../app/Modules/Student/Views/reservation-details-view.php';
$content = ob_get_clean();

require __DIR__ . '/../../app/Views/layouts/app-layout.php';
