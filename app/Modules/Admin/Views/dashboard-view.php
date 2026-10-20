<?php
/**
 * app/Modules/Admin/Views/dashboard-view.php
 * Admin dashboard — Member 5.
 *
 * Expected variables (from public/admin/dashboard.php):
 *   $metrics             (array)      named metrics from ReportService
 *   $pendingReservations (array)      rows waiting for a reservation decision
 *   $pendingModifications (array)     rows waiting for a modification decision
 *   $flash               (array|null) ['type' => ..., 'title' => ..., 'message' => ...]
 *   $basePath            (string)
 *
 * The view only presents data. Every number comes from ReportService and
 * every decision is made on the server.
 */

require_once __DIR__ . '/../../Reservations/Views/_reservation-helpers.php';

$basePath             = $basePath             ?? '';
$metrics              = $metrics              ?? [];
$pendingReservations  = $pendingReservations  ?? [];
$pendingModifications = $pendingModifications ?? [];
$flash                = $flash                ?? null;

$pendingReservationCount  = (int) ($metrics['pending_reservations'] ?? count($pendingReservations));
$confirmedReservationCount = (int) ($metrics['confirmed_reservations'] ?? 0);
$pendingModificationCount = (int) ($metrics['pending_modifications'] ?? count($pendingModifications));
$activeRoomCount          = (int) ($metrics['active_rooms'] ?? 0);
$rangeFrom                = (string) ($metrics['range_from'] ?? '');
$rangeTo                  = (string) ($metrics['range_to'] ?? '');
?>

<?php if ($flash): ?>
    <?php $feedback = $flash; ?>
    <?php require __DIR__ . '/../../Reservations/Views/reservation-feedback-view.php'; ?>
<?php endif; ?>

<!-- ── Summary cards ─────────────────────────────────────────── -->
<div class="admin-summary">
    <a class="admin-summary__card<?= $pendingReservationCount > 0 ? ' admin-summary__card--attention' : '' ?>"
       href="<?= esc($basePath) ?>/admin/reservations.php?status=pending">
        <p class="admin-summary__label">Pending Reservations</p>
        <p class="admin-summary__value"><?= $pendingReservationCount ?></p>
        <p class="admin-summary__sub">Waiting for a decision</p>
    </a>

    <a class="admin-summary__card" href="<?= esc($basePath) ?>/admin/reports.php">
        <p class="admin-summary__label">Confirmed Reservations</p>
        <p class="admin-summary__value"><?= $confirmedReservationCount ?></p>
        <p class="admin-summary__sub">All time</p>
    </a>

    <a class="admin-summary__card" href="<?= esc($basePath) ?>/admin/rooms.php">
        <p class="admin-summary__label">Active Rooms</p>
        <p class="admin-summary__value"><?= $activeRoomCount ?></p>
        <p class="admin-summary__sub"><?= (int) ($metrics['active_floors'] ?? 0) ?> active floors</p>
    </a>

    <a class="admin-summary__card<?= $pendingModificationCount > 0 ? ' admin-summary__card--attention' : '' ?>"
       href="<?= esc($basePath) ?>/admin/modifications.php">
        <p class="admin-summary__label">Pending Modifications</p>
        <p class="admin-summary__value"><?= $pendingModificationCount ?></p>
        <p class="admin-summary__sub">Awaiting review</p>
    </a>
</div>

<!-- ── Queues ────────────────────────────────────────────────── -->
<div class="admin-columns">

    <section class="panel" aria-labelledby="dashboard-reservations-title">
        <div class="panel__header">
            <h2 class="panel__title" id="dashboard-reservations-title">Pending reservation requests</h2>
            <a class="btn btn--secondary btn--sm" href="<?= esc($basePath) ?>/admin/reservations.php?status=pending">
                Review all
            </a>
        </div>
        <div class="panel__body">
            <?php if ($pendingReservations === []): ?>
                <div class="admin-empty-inline">
                    <p><strong>Nothing is waiting.</strong></p>
                    <p>Every reservation request has been reviewed.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="table admin-table">
                        <thead>
                            <tr>
                                <th scope="col">Room</th>
                                <th scope="col">Student</th>
                                <th scope="col">Starts</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($pendingReservations, 0, 5) as $row): ?>
                                <tr>
                                    <td data-label="Room">
                                        <strong><?= esc((string) $row['room_name']) ?></strong>
                                        <span class="admin-panel-table__meta">
                                            <?= esc((string) $row['floor_name']) ?>
                                        </span>
                                    </td>
                                    <td data-label="Student"><?= esc((string) $row['student_name']) ?></td>
                                    <td data-label="Starts">
                                        <?= esc(formatLocalTime((string) $row['starts_at'], 'D, d M H:i')) ?>
                                    </td>
                                    <td data-label="Status">
                                        <span class="badge <?= esc(reservationStatusBadge((string) $row['status'])) ?>">
                                            <?= esc(reservationStatusLabel((string) $row['status'])) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="panel" aria-labelledby="dashboard-modifications-title">
        <div class="panel__header">
            <h2 class="panel__title" id="dashboard-modifications-title">Pending modification requests</h2>
            <a class="btn btn--secondary btn--sm" href="<?= esc($basePath) ?>/admin/modifications.php">
                Review all
            </a>
        </div>
        <div class="panel__body">
            <?php if ($pendingModifications === []): ?>
                <div class="admin-empty-inline">
                    <p><strong>Nothing is waiting.</strong></p>
                    <p>No student has asked to change a confirmed reservation.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="table admin-table">
                        <thead>
                            <tr>
                                <th scope="col">Reservation</th>
                                <th scope="col">Change</th>
                                <th scope="col">Requested</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($pendingModifications, 0, 5) as $row): ?>
                                <tr>
                                    <td data-label="Reservation">
                                        <strong><?= esc((string) $row['original_room_name']) ?></strong>
                                        <span class="admin-panel-table__meta">
                                            <?= esc((string) $row['student_name']) ?>
                                        </span>
                                    </td>
                                    <td data-label="Change">
                                        <?= esc((string) $row['proposed_room_name']) ?>
                                        <span class="admin-panel-table__meta">
                                            <?= esc(formatLocalTime((string) $row['proposed_starts_at'], 'D, d M H:i')) ?>
                                            &ndash;
                                            <?= esc(formatLocalTime((string) $row['proposed_ends_at'], 'H:i')) ?>
                                        </span>
                                    </td>
                                    <td data-label="Requested">
                                        <?= esc(formatLocalTime((string) $row['requested_at'], 'D, d M H:i')) ?>
                                    </td>
                                    <td data-label="Status">
                                        <span class="badge <?= esc(modificationStatusBadge((string) $row['status'])) ?>">
                                            <?= esc(modificationStatusLabel((string) $row['status'])) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<!-- ── Quick links ───────────────────────────────────────────── -->
<section class="panel" aria-labelledby="dashboard-shortcuts-title">
    <div class="panel__header">
        <h2 class="panel__title" id="dashboard-shortcuts-title">Shortcuts</h2>
        <?php if ($rangeFrom !== '' && $rangeTo !== ''): ?>
            <span class="admin-panel-table__meta">
                Reporting window <?= esc($rangeFrom) ?> &ndash; <?= esc($rangeTo) ?>
            </span>
        <?php endif; ?>
    </div>
    <div class="panel__body">
        <div class="admin-quicklinks">
            <a class="admin-quicklink" href="<?= esc($basePath) ?>/admin/reservations.php?status=pending">
                <span class="admin-quicklink__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>
                    </svg>
                </span>
                Reservation queue
            </a>

            <a class="admin-quicklink" href="<?= esc($basePath) ?>/admin/modifications.php">
                <span class="admin-quicklink__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                    </svg>
                </span>
                Modification queue
            </a>

            <a class="admin-quicklink" href="<?= esc($basePath) ?>/admin/reports.php">
                <span class="admin-quicklink__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3v18h18"/><path d="M7 15v3M12 9v9M17 12v6"/>
                    </svg>
                </span>
                Reports
            </a>

            <a class="admin-quicklink" href="<?= esc($basePath) ?>/admin/rooms.php">
                <span class="admin-quicklink__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 21V8l9-5 9 5v13"/><path d="M9 21v-6h6v6"/>
                    </svg>
                </span>
                Rooms &amp; floors
            </a>
        </div>
    </div>
</section>
