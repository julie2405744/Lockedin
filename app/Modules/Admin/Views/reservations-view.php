<?php
/**
 * app/Modules/Admin/Views/reservations-view.php
 * Admin reservation queue — Member 5.
 *
 * Expected variables (from public/admin/reservations.php):
 *   $reservations (array)      reservation rows for the queue
 *   $filters      (array)      ['status' => string, 'room_id' => int]
 *   $rooms        (array)      rooms for the filter dropdown
 *   $flash        (array|null) feedback array for the shared alert component
 *   $basePath     (string)
 *
 * The queue is a read-only view. Approving or rejecting happens on the
 * reservation detail screen and always goes through ReservationService.
 */

require_once __DIR__ . '/../../Reservations/Views/_reservation-helpers.php';

$basePath     = $basePath     ?? '';
$reservations = $reservations ?? [];
$filters      = $filters      ?? [];
$rooms        = $rooms        ?? [];
$flash        = $flash        ?? null;

$statusFilter = (string) ($filters['status'] ?? 'pending');
$roomFilter   = (int) ($filters['room_id'] ?? 0);

$statuses = [
    'pending'   => 'Pending',
    'confirmed' => 'Confirmed',
    'rejected'  => 'Rejected',
    'cancelled' => 'Cancelled',
];
?>

<?php if ($flash): ?>
    <?php $feedback = $flash; ?>
    <?php require __DIR__ . '/../../Reservations/Views/reservation-feedback-view.php'; ?>
<?php endif; ?>

<!-- ── Filters ───────────────────────────────────────────────── -->
<form class="filter-bar" method="get" action="<?= esc($basePath) ?>/admin/reservations.php">
    <div class="filter-bar__inner">
        <div class="filter-bar__field">
            <label class="form-label" for="filter-status">Status</label>
            <select class="form-control" id="filter-status" name="status">
                <?php foreach ($statuses as $value => $label): ?>
                    <option value="<?= esc($value) ?>"<?= $statusFilter === $value ? ' selected' : '' ?>>
                        <?= esc($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-bar__field">
            <label class="form-label" for="filter-room">Room</label>
            <select class="form-control" id="filter-room" name="room_id">
                <option value="0">All rooms</option>
                <?php foreach ($rooms as $room): ?>
                    <option value="<?= (int) $room['id'] ?>"<?= $roomFilter === (int) $room['id'] ? ' selected' : '' ?>>
                        <?= esc((string) $room['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-bar__actions">
            <button type="submit" class="btn btn--primary">Apply filters</button>
            <a class="btn btn--ghost" href="<?= esc($basePath) ?>/admin/reservations.php">Reset</a>
        </div>
    </div>
</form>

<p class="admin-filter-summary">
    <strong><?= count($reservations) ?></strong>
    <?= count($reservations) === 1 ? 'reservation' : 'reservations' ?>
    <?= $statusFilter !== '' ? 'with status “' . esc($statuses[$statusFilter] ?? $statusFilter) . '”' : 'in total' ?>.
</p>

<!-- ── Queue ─────────────────────────────────────────────────── -->
<?php if ($reservations === []): ?>
    <div class="empty-state">
        <div class="empty-state__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <path d="M16 2v4M8 2v4M3 10h18"/>
                <path d="m9 16 2 2 4-4"/>
            </svg>
        </div>
        <h2 class="empty-state__title">Nothing to review here</h2>
        <p class="empty-state__description">
            <?php if ($statusFilter === 'pending'): ?>
                There are no reservation requests waiting for a decision.
            <?php else: ?>
                No reservations match the selected filters.
            <?php endif; ?>
        </p>
        <a class="btn btn--secondary" href="<?= esc($basePath) ?>/admin/reservations.php">Show pending requests</a>
    </div>
<?php else: ?>
    <div class="table-container">
        <table class="table admin-table">
            <thead>
                <tr>
                    <th scope="col">Room</th>
                    <th scope="col">Student</th>
                    <th scope="col">Date</th>
                    <th scope="col">Time</th>
                    <th scope="col">Status</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservations as $row): ?>
                    <tr>
                        <td data-label="Room">
                            <strong><?= esc((string) $row['room_name']) ?></strong>
                            <span class="admin-panel-table__meta">
                                <?= esc((string) $row['floor_name']) ?>
                                &middot;
                                <?= esc(roomCategoryLabel((string) $row['category'])) ?>
                            </span>
                        </td>
                        <td data-label="Student">
                            <?= esc((string) $row['student_name']) ?>
                            <span class="admin-panel-table__meta"><?= esc((string) $row['student_email']) ?></span>
                        </td>
                        <td data-label="Date"><?= esc(formatLocalTime((string) $row['starts_at'], 'D, d M Y')) ?></td>
                        <td data-label="Time">
                            <?= esc(formatLocalTime((string) $row['starts_at'], 'H:i')) ?>
                            &ndash;
                            <?= esc(formatLocalTime((string) $row['ends_at'], 'H:i')) ?>
                        </td>
                        <td data-label="Status">
                            <span class="badge <?= esc(reservationStatusBadge((string) $row['status'])) ?>">
                                <?= esc(reservationStatusLabel((string) $row['status'])) ?>
                            </span>
                        </td>
                        <td data-label="Actions">
                            <div class="admin-panel-table__actions">
                                <a class="btn btn--secondary btn--sm"
                                   href="<?= esc($basePath) ?>/admin/reservation-details.php?id=<?= (int) $row['id'] ?>">
                                    Review
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
