<?php
/**
 * public/reservation-preview.php
 * Member 3 — component preview harness (development / PR evidence only).
 *
 * Renders every reservation component with sample data so the booking,
 * cancellation, modification, comparison and feedback screens can be
 * reviewed and screenshotted before Member 4/5 wire them into their pages.
 *
 * It does NOT talk to the database and does NOT create, change or cancel
 * anything. It can be deleted once the components are integrated, or kept
 * as a living style guide.
 *
 * Real integration: Member 4 (student pages) and Member 5 (admin review)
 * require the same views from app/Modules/Reservations/Views/ and pass
 * data from ReservationService.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Core/Auth.php';
require_once __DIR__ . '/../app/Core/Csrf.php';
require_once __DIR__ . '/../app/Core/helpers.php';
require_once __DIR__ . '/../app/Modules/Reservations/Views/_reservation-helpers.php';

Auth::startSession();
Auth::requireLogin();

$basePath    = defined('BASE_PATH') ? BASE_PATH : '';
$currentUser = Auth::user();

$pageTitle    = 'Reservation components';
$pageSubtitle = 'Member 3 preview — booking, cancellation, modification, comparison and feedback.';
$pageStyles   = '<link rel="stylesheet" href="' . esc($basePath) . '/assets/css/reservation-forms.css">';
$pageScripts  = '<script src="' . esc($basePath) . '/assets/js/reservation-forms.js" defer></script>';

$viewsDir = __DIR__ . '/../app/Modules/Reservations/Views';

/**
 * Render a reservation view with a fresh set of variables and return the HTML.
 */
$render = static function (string $view, array $vars) use ($viewsDir): string {
    extract($vars, EXTR_SKIP);
    ob_start();
    require $viewsDir . '/' . $view;
    return (string) ob_get_clean();
};

$section = static function (string $title, string $hint, string $html): string {
    return '<section class="reservation-preview-section">'
        . '<h2 class="reservation-preview-section__title">' . esc($title) . '</h2>'
        . '<p class="reservation-preview-section__hint">' . esc($hint) . '</p>'
        . $html
        . '</section>';
};

/* ── Sample data ─────────────────────────────────────────────── */

$sampleRoom = [
    'id'         => 1,
    'name'       => 'Library Quiet Room 2A',
    'category'   => 'individual_study',
    'floor_name' => 'Floor 2',
    'capacity'   => 1,
    'facilities' => ['Power sockets', 'Desk lamp', 'Wi-Fi'],
];

$sampleRoomOptions = [
    ['id' => 1, 'name' => 'Library Quiet Room 2A', 'floor_name' => 'Floor 2', 'capacity' => 1,  'category' => 'individual_study'],
    ['id' => 2, 'name' => 'Group Pod 3B',         'floor_name' => 'Floor 3', 'capacity' => 6,  'category' => 'group_study'],
    ['id' => 3, 'name' => 'Rooftop Lounge',       'floor_name' => 'Floor 5', 'capacity' => 20, 'category' => 'lounge'],
];

// Stored values are UTC; the components convert to Africa/Cairo for display.
$sampleReservation = [
    'id'         => 101,
    'room_name'  => 'Library Quiet Room 2A',
    'floor_name' => 'Floor 2',
    'category'   => 'individual_study',
    'starts_at'  => '2026-11-02 09:00:00',
    'ends_at'    => '2026-11-02 11:00:00',
    'status'     => 'confirmed',
];

$minDateTime = '2026-10-20T00:00';
$maxDateTime = '2026-12-31T23:59';

$postNotice = null;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $postNotice = [
        'type'    => 'info',
        'title'   => 'Preview harness',
        'message' => 'This page is a component preview only. No reservation was created, changed or cancelled.',
    ];
}

/* ── Build the page ──────────────────────────────────────────── */

ob_start();
?>
<?php if ($postNotice) { require $viewsDir . '/reservation-feedback-view.php'; } ?>

<div class="reservation-stack">

    <?= $section(
        'Feedback / outcome alerts',
        'One shared alert component for success, validation, conflict and confirmation outcomes. '
            . 'The parent page passes the outcome returned by ReservationService.',
        $render('reservation-feedback-view.php', ['feedback' => ['type' => 'success', 'title' => 'Reservation confirmed', 'message' => 'Room 2A is booked from 11:00 to 13:00.']])
        . '<div style="height:var(--space-3)"></div>'
        . $render('reservation-feedback-view.php', ['feedback' => ['type' => 'danger', 'title' => 'That slot is not available', 'message' => 'Please choose another time.', 'details' => ['The room is closed on Sunday.', 'The end time must be after the start time.']]])
        . '<div style="height:var(--space-3)"></div>'
        . $render('reservation-feedback-view.php', ['feedback' => ['type' => 'warning', 'title' => 'Advance booking required', 'message' => 'Reservations must be made at least 2 hours in advance.']])
    ) ?>

    <?= $section(
        'Booking form — fixed room',
        'Used from a room-details page. All rules (duration, hours, closures, conflicts, cut-off) are enforced server-side.',
        $render('booking-form-view.php', [
            'basePath'      => $basePath,
            'formAction'    => $basePath . '/reservation-preview.php',
            'cancelUrl'     => $basePath . '/student/rooms.php',
            'room'          => $sampleRoom,
            'old'           => [],
            'errors'        => [],
            'feedback'      => null,
            'minDateTime'   => $minDateTime,
            'maxDateTime'   => $maxDateTime,
            'roomDetailsUrl'=> $basePath . '/student/rooms.php',
            'intro'         => 'Pick a start and end time. The room is reserved as one whole unit.',
        ])
    ) ?>

    <?= $section(
        'Booking form — repopulated after a rejected submit',
        'Field-level errors and a conflict summary are shown next to the values the student entered.',
        $render('booking-form-view.php', [
            'basePath'   => $basePath,
            'formAction' => $basePath . '/reservation-preview.php',
            'cancelUrl'  => $basePath . '/student/rooms.php',
            'room'       => $sampleRoom,
            'old'        => ['room_id' => '1', 'starts_at' => '2026-11-02T11:00', 'ends_at' => '2026-11-02T10:00'],
            'errors'     => ['starts_at' => 'Choose a start time within the room’s operating hours.', 'ends_at' => 'The end time must be after the start time.'],
            'feedback'   => ['type' => 'danger', 'title' => 'We could not create this reservation', 'message' => 'Fix the highlighted fields and try again.'],
            'minDateTime'=> $minDateTime,
            'maxDateTime'=> $maxDateTime,
        ])
    ) ?>

    <?= $section(
        'Booking form — room picker variant',
        'The same component with a room selector, for admin "book on behalf of a student" screens.',
        $render('booking-form-view.php', [
            'basePath'   => $basePath,
            'formAction' => $basePath . '/reservation-preview.php',
            'cancelUrl'  => $basePath . '/admin/dashboard.php',
            'room'       => null,
            'rooms'      => $sampleRoomOptions,
            'old'        => ['room_id' => '2', 'starts_at' => '2026-11-03T14:00', 'ends_at' => '2026-11-03T16:00'],
            'errors'     => [],
            'minDateTime'=> $minDateTime,
            'maxDateTime'=> $maxDateTime,
        ])
    ) ?>

    <?= $section(
        'Cancellation confirmation',
        'A deliberate second step that restates the reservation and asks for confirmation before the POST.',
        $render('cancellation-confirmation-view.php', [
            'basePath'    => $basePath,
            'formAction'  => $basePath . '/reservation-preview.php',
            'cancelUrl'   => $basePath . '/student/reservations.php',
            'reservation' => $sampleReservation,
            'old'         => [],
            'errors'      => [],
            'warning'     => 'Cancelling releases the room for other students.',
        ])
    ) ?>

    <?= $section(
        'Modification request form',
        'Original values stay visible; submitting only requests a change that an administrator reviews.',
        $render('modification-form-view.php', [
            'basePath'      => $basePath,
            'formAction'    => $basePath . '/reservation-preview.php',
            'cancelUrl'     => $basePath . '/student/reservations.php',
            'reservation'   => $sampleReservation,
            'rooms'         => $sampleRoomOptions,
            'old'           => ['proposed_room_id' => '2', 'proposed_starts_at' => '2026-11-02T14:00', 'proposed_ends_at' => '2026-11-02T16:00', 'reason' => 'Need a larger room for a group project.'],
            'errors'        => [],
            'minDateTime'   => $minDateTime,
            'maxDateTime'   => $maxDateTime,
            'hasPendingModification' => false,
        ])
    ) ?>

    <?= $section(
        'Modification comparison — student view',
        'Original vs proposed, with the changed values highlighted.',
        '<div class="panel"><div class="panel__body">'
        . $render('modification-comparison-view.php', [
            'basePath' => $basePath,
            'title'    => 'Requested change',
            'original' => ['room_name' => 'Library Quiet Room 2A', 'floor_name' => 'Floor 2', 'starts_at' => '2026-11-02 09:00:00', 'ends_at' => '2026-11-02 11:00:00'],
            'proposed' => ['room_name' => 'Group Pod 3B', 'floor_name' => 'Floor 3', 'starts_at' => '2026-11-02 12:00:00', 'ends_at' => '2026-11-02 14:00:00'],
            'modification' => ['id' => 55, 'status' => 'pending', 'requested_at' => '2026-10-19 18:30:00', 'reason' => 'Need a larger room for a group project.'],
        ])
        . '</div></div>'
    ) ?>

    <?= $section(
        'Modification comparison — admin review',
        'The same comparison with approve/reject controls for administrators.',
        '<div class="panel"><div class="panel__body">'
        . $render('modification-comparison-view.php', [
            'basePath'         => $basePath,
            'title'            => 'Review request #55',
            'original'         => ['room_name' => 'Library Quiet Room 2A', 'floor_name' => 'Floor 2', 'starts_at' => '2026-11-02 09:00:00', 'ends_at' => '2026-11-02 11:00:00'],
            'proposed'         => ['room_name' => 'Group Pod 3B', 'floor_name' => 'Floor 3', 'starts_at' => '2026-11-02 12:00:00', 'ends_at' => '2026-11-02 14:00:00'],
            'modification'     => ['id' => 55, 'status' => 'pending', 'requested_at' => '2026-10-19 18:30:00', 'reason' => 'Need a larger room for a group project.'],
            'showReviewActions'=> true,
            'reviewFormAction' => $basePath . '/reservation-preview.php',
        ])
        . '</div></div>'
    ) ?>

    <?= $section(
        'Modification comparison — invalidated',
        'A request that could no longer be applied, with the administrator note.',
        '<div class="panel"><div class="panel__body">'
        . $render('modification-comparison-view.php', [
            'basePath'     => $basePath,
            'title'        => 'Request #48',
            'original'     => ['room_name' => 'Group Pod 3B', 'floor_name' => 'Floor 3', 'starts_at' => '2026-11-04 10:00:00', 'ends_at' => '2026-11-04 12:00:00'],
            'proposed'     => ['room_name' => 'Group Pod 3B', 'floor_name' => 'Floor 3', 'starts_at' => '2026-11-04 13:00:00', 'ends_at' => '2026-11-04 15:00:00'],
            'modification' => ['id' => 48, 'status' => 'invalidated', 'requested_at' => '2026-10-18 09:15:00', 'invalidation_reason' => 'The original reservation was cancelled before this request was reviewed.'],
        ])
        . '</div></div>'
    ) ?>

    <?= $section(
        'Reservation action row',
        'Reusable action links for lists and detail pages. The caller passes only the actions it has authorised.',
        '<div class="panel"><div class="panel__body">'
        . $render('reservation-actions-view.php', [
            'reservation' => $sampleReservation,
            'canModify'   => true,
            'canCancel'   => true,
            'detailsUrl'  => $basePath . '/student/reservations.php',
            'modifyUrl'   => $basePath . '/student/reservations.php',
            'cancelUrl'   => $basePath . '/student/reservations.php',
        ])
        . '</div></div>'
    ) ?>

</div>
<?php
$content = (string) ob_get_clean();

require __DIR__ . '/../app/Views/layouts/app-layout.php';
