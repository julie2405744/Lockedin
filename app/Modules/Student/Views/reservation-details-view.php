<?php
/**
 * app/Modules/Student/Views/reservation-details-view.php
 * Member 4 — one reservation's detail screen (student view).
 *
 * Composes Member 3's shared components (comparison, actions). Display
 * only — ownership, cutoffs and statuses come from the handler, which
 * later reads them through ReservationService.
 *
 * Expects:
 *   $basePath      (string)
 *   $reservation   (array)  id, room_id, room_name, floor_name, category,
 *                           starts_at (UTC), ends_at (UTC), status, reason?
 *   $modification  (array|null) pending/historic proposal for this
 *                           reservation: original[], proposed[], status,
 *                           requested_at, reason?
 *   $actions       (array)  canCancel, canModify (booleans)
 */

$basePath     = $basePath ?? '';
$reservation  = $reservation ?? [];
$modification = $modification ?? null;
$actions      = $actions ?? [];

$detailsId      = (int) ($reservation['id'] ?? 0);
$cancelUrl      = $basePath . '/student/reservation-cancel.php?id=' . $detailsId;
$modifyUrl      = $basePath . '/student/reservation-modify.php?id=' . $detailsId;
$listUrl        = $basePath . '/student/reservations.php';
$roomDetailsUrl = $basePath . '/student/room-details.php?id=' . (int) ($reservation['room_id'] ?? 0);

$isTerminal = in_array($reservation['status'] ?? '', ['rejected', 'cancelled'], true);
?>
<div class="reservation-stack">

    <div class="panel">
        <div class="panel__header">
            <h1 class="panel__title">Reservation #<?= $detailsId ?></h1>
            <span class="badge <?= esc(reservationStatusBadge((string) ($reservation['status'] ?? ''))) ?>">
                <?= esc(reservationStatusLabel((string) ($reservation['status'] ?? ''))) ?>
            </span>
        </div>
        <div class="panel__body">
            <div class="reservation-facts" style="margin-bottom:var(--space-4);">
                <div>
                    <p class="reservation-fact__label">Room</p>
                    <p class="reservation-fact__value">
                        <a href="<?= esc($roomDetailsUrl) ?>" style="color:inherit;text-decoration:none;"><?= esc((string) ($reservation['room_name'] ?? '—')) ?></a>
                    </p>
                </div>
                <div>
                    <p class="reservation-fact__label">Floor</p>
                    <p class="reservation-fact__value"><?= esc((string) ($reservation['floor_name'] ?? '—')) ?></p>
                </div>
                <div>
                    <p class="reservation-fact__label">Category</p>
                    <p class="reservation-fact__value"><?= esc(roomCategoryLabel((string) ($reservation['category'] ?? ''))) ?></p>
                </div>
                <div>
                    <p class="reservation-fact__label">Date</p>
                    <p class="reservation-fact__value"><?= esc(isset($reservation['starts_at']) ? formatLocalTime((string) $reservation['starts_at'], 'D, d M Y') : '—') ?></p>
                </div>
                <div>
                    <p class="reservation-fact__label">Starts</p>
                    <p class="reservation-fact__value"><?= esc(isset($reservation['starts_at']) ? formatLocalTime((string) $reservation['starts_at'], 'H:i') : '—') ?> (Africa/Cairo)</p>
                </div>
                <div>
                    <p class="reservation-fact__label">Ends</p>
                    <p class="reservation-fact__value"><?= esc(isset($reservation['ends_at']) ? formatLocalTime((string) $reservation['ends_at'], 'H:i') : '—') ?> (Africa/Cairo)</p>
                </div>
            </div>

            <?php if (!empty($reservation['reason']) && ($reservation['status'] ?? '') === 'rejected'): ?>
                <div class="reservation-reason reservation-reason--rejected">
                    <strong>Administrator note:</strong> <?= esc((string) $reservation['reason']) ?>
                </div>
            <?php endif; ?>

            <div style="display:flex;flex-wrap:wrap;gap:var(--space-2);margin-top:var(--space-4);">
                <?php
                $canCancel  = (bool) ($actions['canCancel'] ?? false);
                $canModify  = (bool) ($actions['canModify'] ?? false);
                $detailsUrl = null; // the page IS the detail — no ghost link needed
                $cancelUrl  = $canCancel ? $cancelUrl : null;
                $modifyUrl  = $canModify ? $modifyUrl : null;
                $size       = ''; // full-size buttons on the detail page
                require __DIR__ . '/../../../Modules/Reservations/Views/reservation-actions-view.php';
                ?>
                <a class="btn btn--secondary" href="<?= esc($listUrl) ?>">Back to all reservations</a>
            </div>
        </div>
    </div>

    <?php
    $isFuture = (string) ($reservation['starts_at'] ?? '0') > gmdate('Y-m-d H:i:s');
    ?>
    <?php if (is_array($modification)): ?>
        <div class="panel">
            <div class="panel__header">
                <h2 class="panel__title">Change request</h2>
            </div>
            <div class="panel__body">
                <?php
                $title       = 'Request #' . (string) ($modification['id'] ?? '');
                $original    = $modification['original'] ?? [];
                $proposed    = $modification['proposed'] ?? [];
                require __DIR__ . '/../../../Modules/Reservations/Views/modification-comparison-view.php';
                ?>
            </div>
        </div>
    <?php elseif (!$isTerminal && ($reservation['status'] ?? '') === 'confirmed' && $isFuture): ?>
        <div class="panel">
            <div class="panel__body">
                <p class="form-hint">
                    Confirmed reservations can be changed by submitting a request.
                    Submit at least two hours before the start — an administrator reviews
                    every proposed change.
                </p>
            </div>
        </div>
    <?php endif; ?>

    <p class="form-hint">
        Times above are stored as UTC and shown in Africa/Cairo local time.
    </p>

</div>
