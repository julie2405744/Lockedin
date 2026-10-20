<?php
/**
 * public/student/room-details.php
 * Room details + booking controls — Member 4 (frontend F4).
 *
 * FRONTEND-BUILD MODE:
 * Room information comes from the shared sample dataset. The booking form
 * is Member 3's shared component (app/Modules/Reservations/Views/
 * booking-form-view.php); POSTs are accepted and safely rejected with a
 * clear preview notice, because ReservationService (Member 3) is not
 * integrated yet. No database access, no booking rules here.
 *
 * Future integration point:
 *   $room = (new RoomService($pdo, $roomRepo))->getRoomDetails($roomId);
 *   on POST: $result = (new ReservationService(...))->createReservation(Auth::id(), $_POST);
 *   and $feedback/errors/min-max follow the returned result.
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
$pageStyles  = '<link rel="stylesheet" href="' . esc($basePath) . '/assets/css/student-pages.css">'
             . '<link rel="stylesheet" href="' . esc($basePath) . '/assets/css/reservation-forms.css">';
$pageScripts = '<script src="' . esc($basePath) . '/assets/js/reservation-forms.js" defer></script>';

$sample = require __DIR__ . '/../../app/Modules/Student/_sample-data.php';

/* ── Resolve the room ──────────────────────────────────────────── */
$roomId = isset($_GET['id']) && ctype_digit((string) $_GET['id']) ? (int) $_GET['id'] : 0;
$room   = studentSampleRoomById($sample['rooms'], $roomId);

if ($room === null) {
    $pageTitle    = 'Room not found';
    $pageSubtitle = '';
    ob_start(); ?>
    <div class="empty-state" style="padding:var(--space-10) 0;">
        <div class="empty-state__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
        </div>
        <h2 class="empty-state__title">We couldn't find that room</h2>
        <p class="empty-state__description">
            The room may have been deactivated, or the link is out of date.
        </p>
        <a href="<?= esc($basePath) ?>/student/rooms.php" class="btn btn--primary">Browse all rooms</a>
    </div>
    <?php $content = ob_get_clean();
    require __DIR__ . '/../../app/Views/layouts/app-layout.php';
    exit;
}

/* ── Booking-window bounds (matches the real policy window) ───── */
try {
    $nowCairo  = new DateTimeImmutable('now', new DateTimeZone('Africa/Cairo'));
    $minDateTime = $nowCairo->format('Y-m-d\TH:i');
    $maxDateTime = $nowCairo->modify('+7 days')->format('Y-m-d\TH:i');
} catch (Exception $e) {
    $minDateTime = null;
    $maxDateTime = null;
}

/* ── POST: preview-safe rejection (service not integrated yet) ── */
$feedback = null;
$old      = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'starts_at' => (string) ($_POST['starts_at'] ?? ''),
        'ends_at'   => (string) ($_POST['ends_at'] ?? ''),
    ];

    $feedback = Csrf::validate($_POST['csrf_token'] ?? '')
        ? [
            'type'    => 'info',
            'title'   => 'Frontend build only — nothing was submitted',
            'message' => 'This page is being built before the reservation engine is ready. '
                . 'Once ReservationService is integrated, this form will create a real '
                . 'pending reservation for an administrator to review. Nothing was created now.',
        ]
        : [
            'type'    => 'warning',
            'title'   => 'Session expired',
            'message' => 'Your form session expired. Refresh the page and try again.',
        ];
}

/* ── Render ─────────────────────────────────────────────────────── */
$pageTitle    = $room['name'];
$pageSubtitle = roomCategoryLabel((string) $room['category']) . ' · ' . $room['floor_name'];
$pageActions  = '<a class="btn btn--ghost" href="' . esc($basePath) . '/student/rooms.php">'
    . 'Back to all rooms</a>';

ob_start();
require __DIR__ . '/../../app/Modules/Student/Views/room-details-view.php';

// Booking controls — shared component from Member 3 (fixed-room variant).
$roomForBooking = [
    'id'         => $room['id'],
    'name'       => $room['name'],
    'category'   => $room['category'],
    'floor_name' => $room['floor_name'],
    'capacity'   => $room['capacity'],
    'facilities' => $room['facilities'],
    'status'     => 'active',
];
bookingFormForRoomDetails(
    $basePath, $roomForBooking, $old, $feedback, $minDateTime, $maxDateTime
);

$content = ob_get_clean();
require __DIR__ . '/../../app/Views/layouts/app-layout.php';

/* ═══ sample-mode helper: shared form with page-local defaults ═══ */
function bookingFormForRoomDetails(
    string $basePath,
    array $room,
    array $old,
    ?array $feedback,
    ?string $minDateTime,
    ?string $maxDateTime
): void {
    $formAction = $basePath . '/student/room-details.php?id=' . (int) $room['id'];
    require __DIR__ . '/../../app/Modules/Reservations/Views/booking-form-view.php';
}
