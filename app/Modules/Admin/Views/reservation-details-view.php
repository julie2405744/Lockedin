<?php
/**
 * app/Modules/Admin/Views/reservation-details-view.php
 * Admin reservation detail and review controls — Member 5.
 *
 * Expected variables (from public/admin/reservation-details.php):
 *   $reservation     (array)      the reservation row with room, floor and student
 *   $modification    (array|null) the latest modification request for it
 *   $flash           (array|null) feedback array for the shared alert component
 *   $basePath        (string)
 *   $reviewEngine    (bool)       whether ReservationService is connected
 *
 * The approve and reject forms post back to this page. They never change the
 * reservation themselves — the server calls ReservationService and shows the
 * result that the service returned.
 */

require_once __DIR__ . '/../../Reservations/Views/_reservation-helpers.php';

$basePath     = $basePath     ?? '';
$reservation  = $reservation  ?? [];
$modification = $modification ?? null;
$flash        = $flash        ?? null;
$reviewEngine = $reviewEngine ?? false;

$status          = (string) ($reservation['status'] ?? 'pending');
$isPending       = $status === 'pending';
$localStart      = formatLocalTime((string) ($reservation['starts_at'] ?? ''), 'D, d M Y');
$localStartTime  = formatLocalTime((string) ($reservation['starts_at'] ?? ''), 'H:i');
$localEndTime    = formatLocalTime((string) ($reservation['ends_at'] ?? ''), 'H:i');
$duration        = reservationDurationLabel(
    (string) ($reservation['starts_at'] ?? ''),
    (string) ($reservation['ends_at'] ?? '')
);
?>

<a class="admin-backlink" href="<?= esc($basePath) ?>/admin/reservations.php">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
    </svg>
    Back to the reservation queue
</a>

<?php if ($flash): ?>
    <?php $feedback = $flash; ?>
    <?php require __DIR__ . '/../../Reservations/Views/reservation-feedback-view.php'; ?>
<?php endif; ?>

<div class="admin-stack">
    <!-- ── Reservation facts ───────────────────────────────────── -->
    <section class="panel" aria-labelledby="reservation-details-title">
        <div class="panel__header">
            <h2 class="panel__title" id="reservation-details-title">
                <?= esc((string) ($reservation['room_name'] ?? 'Reservation')) ?>
            </h2>
            <span class="badge <?= esc(reservationStatusBadge($status)) ?>">
                <?= esc(reservationStatusLabel($status)) ?>
            </span>
        </div>
        <div class="panel__body">
            <dl class="admin-facts">
                <div class="admin-facts__item">
                    <dt class="admin-facts__label">Room</dt>
                    <dd class="admin-facts__value"><?= esc((string) ($reservation['room_name'] ?? '—')) ?></dd>
                </div>
                <div class="admin-facts__item">
                    <dt class="admin-facts__label">Floor</dt>
                    <dd class="admin-facts__value">
                        <?= esc((string) ($reservation['floor_name'] ?? '—')) ?>
                        <?php if (isset($reservation['floor_number'])): ?>
                            <span class="admin-facts__value--muted">(Floor <?= (int) $reservation['floor_number'] ?>)</span>
                        <?php endif; ?>
                    </dd>
                </div>
                <div class="admin-facts__item">
                    <dt class="admin-facts__label">Category</dt>
                    <dd class="admin-facts__value">
                        <?= esc(roomCategoryLabel((string) ($reservation['category'] ?? ''))) ?>
                    </dd>
                </div>
                <div class="admin-facts__item">
                    <dt class="admin-facts__label">Capacity</dt>
                    <dd class="admin-facts__value"><?= (int) ($reservation['capacity'] ?? 0) ?> people</dd>
                </div>
                <div class="admin-facts__item">
                    <dt class="admin-facts__label">Student</dt>
                    <dd class="admin-facts__value"><?= esc((string) ($reservation['student_name'] ?? '—')) ?></dd>
                </div>
                <div class="admin-facts__item">
                    <dt class="admin-facts__label">Student email</dt>
                    <dd class="admin-facts__value admin-facts__value--muted">
                        <?= esc((string) ($reservation['student_email'] ?? '—')) ?>
                    </dd>
                </div>
                <div class="admin-facts__item">
                    <dt class="admin-facts__label">Date</dt>
                    <dd class="admin-facts__value"><?= esc($localStart) ?></dd>
                </div>
                <div class="admin-facts__item">
                    <dt class="admin-facts__label">Time</dt>
                    <dd class="admin-facts__value">
                        <?= esc($localStartTime) ?> &ndash; <?= esc($localEndTime) ?>
                        <?php if ($duration !== ''): ?>
                            <span class="admin-facts__value--muted">(<?= esc($duration) ?>)</span>
                        <?php endif; ?>
                    </dd>
                </div>
                <div class="admin-facts__item">
                    <dt class="admin-facts__label">Requested</dt>
                    <dd class="admin-facts__value">
                        <?= esc(formatLocalTime((string) ($reservation['created_at'] ?? ''), 'D, d M Y H:i')) ?>
                    </dd>
                </div>
                <div class="admin-facts__item">
                    <dt class="admin-facts__label">Reviewed</dt>
                    <dd class="admin-facts__value<?= empty($reservation['reviewed_at']) ? ' admin-facts__value--muted' : '' ?>">
                        <?= empty($reservation['reviewed_at'])
                            ? 'Not reviewed yet'
                            : esc(formatLocalTime((string) $reservation['reviewed_at'], 'D, d M Y H:i')) ?>
                    </dd>
                </div>
                <div class="admin-facts__item">
                    <dt class="admin-facts__label">Reference</dt>
                    <dd class="admin-facts__value">#<?= (int) ($reservation['id'] ?? 0) ?></dd>
                </div>
            </dl>

            <?php if (!empty($reservation['cancellation_reason'])): ?>
                <div class="alert alert--info" style="margin-top:var(--space-6);" role="status">
                    <svg class="alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
                    </svg>
                    <div class="alert__body">
                        <p class="alert__title">Note on this reservation</p>
                        <p><?= esc((string) $reservation['cancellation_reason']) ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ── Review controls ─────────────────────────────────────── -->
    <?php if ($isPending): ?>
        <section class="panel" aria-labelledby="reservation-review-title">
            <div class="panel__header">
                <h2 class="panel__title" id="reservation-review-title">Review this request</h2>
            </div>
            <div class="panel__body">
                <div class="admin-review">
                    <form class="admin-review__form" method="post" action="<?= esc($basePath) ?>/admin/reservation-details.php"
                          data-confirm="Approve this reservation? The room is confirmed for the student.">
                        <input type="hidden" name="reservation_id" value="<?= (int) ($reservation['id'] ?? 0) ?>">
                        <input type="hidden" name="decision" value="approve">
                        <?= Csrf::field() ?>
                        <div class="admin-review__actions">
                            <button type="submit" class="btn btn--primary" data-submit-state="Approving…">
                                Approve reservation
                            </button>
                        </div>
                        <p class="admin-review__note">
                            Approving confirms the room for the student. The server re-checks the slot before confirming it.
                        </p>
                    </form>

                    <form class="admin-review__form" method="post" action="<?= esc($basePath) ?>/admin/reservation-details.php"
                          data-confirm="Reject this reservation? The student will not get the room.">
                        <input type="hidden" name="reservation_id" value="<?= (int) ($reservation['id'] ?? 0) ?>">
                        <input type="hidden" name="decision" value="reject">
                        <?= Csrf::field() ?>
                        <div class="form-group">
                            <label class="form-label" for="rejection-reason">Reason (optional)</label>
                            <textarea class="form-control" id="rejection-reason" name="reason" rows="2"
                                      maxlength="255" placeholder="e.g. the room was closed for maintenance"></textarea>
                            <p class="form-hint">The student sees this reason on their reservation.</p>
                        </div>
                        <div class="admin-review__actions">
                            <button type="submit" class="btn btn--danger" data-submit-state="Rejecting…">
                                Reject reservation
                            </button>
                        </div>
                    </form>

                    <?php if (!$reviewEngine): ?>
                        <p class="admin-review__note">
                            The reservation review engine is not connected yet, so a decision cannot change the
                            reservation until <code>ReservationService</code> is in place.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php else: ?>
        <section class="panel" aria-labelledby="reservation-decided-title">
            <div class="panel__header">
                <h2 class="panel__title" id="reservation-decided-title">Decision</h2>
            </div>
            <div class="panel__body">
                <p class="admin-filter-summary">
                    This request was already <?= esc(strtolower(reservationStatusLabel($status))) ?>.
                    Only pending requests can be approved or rejected.
                </p>
            </div>
        </section>
    <?php endif; ?>

    <!-- ── Linked modification request ─────────────────────────── -->
    <?php if ($modification !== null): ?>
        <section class="panel" aria-labelledby="reservation-modification-title">
            <div class="panel__header">
                <h2 class="panel__title" id="reservation-modification-title">Modification request</h2>
                <span class="badge <?= esc(modificationStatusBadge((string) $modification['status'])) ?>">
                    <?= esc(modificationStatusLabel((string) $modification['status'])) ?>
                </span>
            </div>
            <div class="panel__body">
                <p class="admin-filter-summary">
                    The student asked to move this reservation to
                    <strong><?= esc(formatLocalTime((string) $modification['proposed_starts_at'], 'D, d M Y H:i')) ?></strong>
                    &ndash;
                    <strong><?= esc(formatLocalTime((string) $modification['proposed_ends_at'], 'H:i')) ?></strong>.
                </p>
                <a class="btn btn--secondary"
                   href="<?= esc($basePath) ?>/admin/modification-details.php?id=<?= (int) $modification['id'] ?>">
                    Review the modification request
                </a>
            </div>
        </section>
    <?php endif; ?>
</div>
