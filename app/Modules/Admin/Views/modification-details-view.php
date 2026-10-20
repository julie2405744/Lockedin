<?php
/**
 * app/Modules/Admin/Views/modification-details-view.php
 * Admin modification detail and review controls — Member 5.
 *
 * Expected variables (from public/admin/modification-details.php):
 *   $modification  (array)      the modification row with the original and the proposal
 *   $flash         (array|null) feedback array for the shared alert component
 *   $basePath      (string)
 *   $reviewEngine  (bool)       whether ReservationService is connected
 *
 * The original-versus-proposed comparison and the review buttons are the
 * shared reservation components from Member 3, so that the student screen and
 * the admin screen always show the same thing.
 */

require_once __DIR__ . '/../../Reservations/Views/_reservation-helpers.php';

$basePath     = $basePath     ?? '';
$modification  = $modification ?? [];
$flash        = $flash        ?? null;
$reviewEngine = $reviewEngine ?? false;

$status   = (string) ($modification['status'] ?? 'pending');
$isPending = $status === 'pending';

$original = [
    'room_name'  => (string) ($modification['original_room_name'] ?? '—'),
    'floor_name' => trim(sprintf(
        '%s (Floor %d)',
        (string) ($modification['original_floor_name'] ?? ''),
        (int) ($modification['original_floor_number'] ?? 0)
    ), ' (Floor 0)'),
    'starts_at'  => (string) ($modification['original_starts_at'] ?? ''),
    'ends_at'    => (string) ($modification['original_ends_at'] ?? ''),
];

$proposed = [
    'room_name'  => (string) ($modification['proposed_room_name'] ?? '—'),
    'floor_name' => trim(sprintf(
        '%s (Floor %d)',
        (string) ($modification['proposed_floor_name'] ?? ''),
        (int) ($modification['proposed_floor_number'] ?? 0)
    ), ' (Floor 0)'),
    'starts_at'  => (string) ($modification['proposed_starts_at'] ?? ''),
    'ends_at'    => (string) ($modification['proposed_ends_at'] ?? ''),
];
?>

<a class="admin-backlink" href="<?= esc($basePath) ?>/admin/modifications.php">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
    </svg>
    Back to the modification queue
</a>

<?php if ($flash): ?>
    <?php $feedback = $flash; ?>
    <?php require __DIR__ . '/../../Reservations/Views/reservation-feedback-view.php'; ?>
<?php endif; ?>

<div class="admin-stack">
    <!-- ── Request facts ───────────────────────────────────────── -->
    <section class="panel" aria-labelledby="modification-facts-title">
        <div class="panel__header">
            <h2 class="panel__title" id="modification-facts-title">
                Request #<?= (int) ($modification['id'] ?? 0) ?>
            </h2>
            <span class="badge <?= esc(modificationStatusBadge($status)) ?>">
                <?= esc(modificationStatusLabel($status)) ?>
            </span>
        </div>
        <div class="panel__body">
            <dl class="admin-facts">
                <div class="admin-facts__item">
                    <dt class="admin-facts__label">Student</dt>
                    <dd class="admin-facts__value"><?= esc((string) ($modification['student_name'] ?? '—')) ?></dd>
                </div>
                <div class="admin-facts__item">
                    <dt class="admin-facts__label">Student email</dt>
                    <dd class="admin-facts__value admin-facts__value--muted">
                        <?= esc((string) ($modification['student_email'] ?? '—')) ?>
                    </dd>
                </div>
                <div class="admin-facts__item">
                    <dt class="admin-facts__label">Reservation</dt>
                    <dd class="admin-facts__value">
                        <a href="<?= esc($basePath) ?>/admin/reservation-details.php?id=<?= (int) ($modification['reservation_id'] ?? 0) ?>">
                            #<?= (int) ($modification['reservation_id'] ?? 0) ?>
                        </a>
                    </dd>
                </div>
                <div class="admin-facts__item">
                    <dt class="admin-facts__label">Reservation status</dt>
                    <dd class="admin-facts__value">
                        <span class="badge <?= esc(reservationStatusBadge((string) ($modification['reservation_status'] ?? ''))) ?>">
                            <?= esc(reservationStatusLabel((string) ($modification['reservation_status'] ?? ''))) ?>
                        </span>
                    </dd>
                </div>
                <div class="admin-facts__item">
                    <dt class="admin-facts__label">Requested</dt>
                    <dd class="admin-facts__value">
                        <?= esc(formatLocalTime((string) ($modification['requested_at'] ?? ''), 'D, d M Y H:i')) ?>
                    </dd>
                </div>
                <div class="admin-facts__item">
                    <dt class="admin-facts__label">Reviewed</dt>
                    <dd class="admin-facts__value<?= empty($modification['reviewed_at']) ? ' admin-facts__value--muted' : '' ?>">
                        <?= empty($modification['reviewed_at'])
                            ? 'Not reviewed yet'
                            : esc(formatLocalTime((string) $modification['reviewed_at'], 'D, d M Y H:i')) ?>
                    </dd>
                </div>
            </dl>

            <?php if (!$isPending && !$reviewEngine): ?>
                <p class="admin-filter-summary" style="margin-top:var(--space-4);">
                    This request was already reviewed. Only pending requests can be approved or rejected.
                </p>
            <?php endif; ?>
        </div>
    </section>

    <!-- ── Shared comparison and review component (Member 3) ────── -->
    <section class="panel" aria-labelledby="modification-comparison-title">
        <div class="panel__header">
            <h2 class="panel__title" id="modification-comparison-title">Original versus proposed</h2>
        </div>
        <div class="panel__body">
            <?php
            /* The shared component owns the comparison layout and the review
               buttons; the admin page only says where a decision is posted. */
            $showReviewActions = $isPending;
            $reviewFormAction  = $basePath . '/admin/modification-details.php';
            /* The administrator may always try a decision; the server answers
               with the real outcome, including "the review engine is not
               connected yet" while ReservationService is still missing. */
            $canReview         = true;

            require __DIR__ . '/../../Reservations/Views/modification-comparison-view.php';
            ?>
        </div>
    </section>

    <?php if ($isPending && !$reviewEngine): ?>
        <p class="admin-review__note">
            The reservation review engine is not connected yet, so a decision cannot change this request
            until <code>ReservationService</code> is in place.
        </p>
    <?php endif; ?>
</div>
