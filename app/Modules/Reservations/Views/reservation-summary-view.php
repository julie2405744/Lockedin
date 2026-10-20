<?php
/**
 * app/Modules/Reservations/Views/reservation-summary-view.php
 * Member 3 — reusable, read-only reservation summary card.
 *
 * Shows the room, the interval and the duration for a reservation in one
 * consistent place. The booking, cancellation and modification screens all
 * use it, so they can never disagree about what is being shown.
 *
 * Expects:
 *   $summary       (array)  room_name, starts_local, ends_local,
 *                           optional floor_name, category, status,
 *                           duration_label, status_label
 *   $summaryTitle  (string) default 'Reservation summary'
 *   $summaryNote   (string|null)
 *   $summaryLive   (bool)   default false — add JS hooks so the booking /
 *                           modification forms can update it live
 *   $summaryFormId (string|null) id of the form the live summary mirrors
 *   $summarySticky (bool)   default true
 */

require_once __DIR__ . '/_reservation-helpers.php';

$summary       = $summary ?? [];
$summaryTitle  = $summaryTitle ?? 'Reservation summary';
$summaryNote   = $summaryNote ?? null;
$summaryLive   = $summaryLive ?? false;
$summaryFormId = $summaryFormId ?? null;
$summarySticky = $summarySticky ?? true;

$roomName     = (string) ($summary['room_name'] ?? '');
$floorName    = (string) ($summary['floor_name'] ?? '');
$category     = (string) ($summary['category'] ?? '');
$startsLocal  = (string) ($summary['starts_local'] ?? '');
$endsLocal    = (string) ($summary['ends_local'] ?? '');
$status       = (string) ($summary['status'] ?? '');
$statusLabel  = (string) ($summary['status_label'] ?? ($status !== '' ? reservationStatusLabel($status) : ''));

$durationLabel = (string) ($summary['duration_label'] ?? '');
if ($durationLabel === '' && $startsLocal !== '' && $endsLocal !== '') {
    $durationLabel = reservationDurationLabel($startsLocal, $endsLocal);
}

// Friendly server-side label (used before JS reformats it, and as the
// no-JavaScript fallback). Accepts 'Y-m-d\TH:i' or 'Y-m-d H:i'.
$displayLocal = static function (string $value): string {
    if ($value === '') {
        return '—';
    }
    try {
        return (new DateTimeImmutable(str_replace('T', ' ', $value)))->format('D, d M Y H:i');
    } catch (Exception $e) {
        return $value;
    }
};
$startsDisplay = $displayLocal($startsLocal);
$endsDisplay   = $displayLocal($endsLocal);
?>
<aside class="reservation-summary"
       aria-label="<?= esc($summaryTitle) ?>"
       <?= $summaryLive ? 'data-booking-summary' : '' ?>
       <?= ($summaryLive && $summaryFormId) ? 'data-booking-summary-form="' . esc($summaryFormId) . '"' : '' ?>
       <?= $summarySticky ? '' : 'style="position:static;"' ?>>
    <div class="panel">
        <div class="panel__header">
            <h2 class="panel__title"><?= esc($summaryTitle) ?></h2>
            <?php if ($status !== ''): ?>
                <span class="badge <?= esc(reservationStatusBadge($status)) ?>"><?= esc($statusLabel) ?></span>
            <?php endif; ?>
        </div>
        <div class="panel__body">
            <dl class="reservation-summary__list">
                <div class="reservation-summary__row">
                    <dt class="reservation-summary__label">Room</dt>
                    <dd class="reservation-summary__value"
                        <?= $summaryLive ? 'data-summary-room' : '' ?>
                        data-room-name="<?= esc($roomName) ?>"><?= esc($roomName !== '' ? $roomName : 'Not selected') ?></dd>
                </div>

                <?php if ($floorName !== ''): ?>
                    <div class="reservation-summary__row">
                        <dt class="reservation-summary__label">Floor</dt>
                        <dd class="reservation-summary__value"><?= esc($floorName) ?></dd>
                    </div>
                <?php endif; ?>

                <?php if ($category !== ''): ?>
                    <div class="reservation-summary__row">
                        <dt class="reservation-summary__label">Type</dt>
                        <dd class="reservation-summary__value"><?= esc(roomCategoryLabel($category)) ?></dd>
                    </div>
                <?php endif; ?>

                <div class="reservation-summary__row">
                    <dt class="reservation-summary__label">Starts</dt>
                    <dd class="reservation-summary__value" <?= $summaryLive ? 'data-summary-start-out' : '' ?>>
                        <?= esc($startsDisplay) ?>
                    </dd>
                </div>

                <div class="reservation-summary__row">
                    <dt class="reservation-summary__label">Ends</dt>
                    <dd class="reservation-summary__value" <?= $summaryLive ? 'data-summary-end-out' : '' ?>>
                        <?= esc($endsDisplay) ?>
                    </dd>
                </div>

                <div class="reservation-summary__row reservation-summary__total">
                    <dt class="reservation-summary__label">Duration</dt>
                    <dd class="reservation-summary__value reservation-duration"
                        <?= $summaryLive ? 'data-summary-duration' : '' ?>><?= esc($durationLabel !== '' ? $durationLabel : '—') ?></dd>
                </div>
            </dl>
        </div>
        <?php if ($summaryNote !== null && trim((string) $summaryNote) !== ''): ?>
            <div class="panel__body" style="border-top:1px solid var(--color-border);">
                <p class="reservation-notice">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
                    </svg>
                    <span><?= esc((string) $summaryNote) ?></span>
                </p>
            </div>
        <?php endif; ?>
    </div>
</aside>
