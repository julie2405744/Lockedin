<?php
/**
 * app/Modules/Admin/Views/modifications-view.php
 * Admin modification queue — Member 5.
 *
 * Expected variables (from public/admin/modifications.php):
 *   $modifications (array)      modification rows with the original and the proposal
 *   $filters      (array)      ['status' => string]
 *   $flash        (array|null) feedback array for the shared alert component
 *   $basePath     (string)
 *
 * The queue is a read-only view; the decision happens on the detail screen and
 * always goes through ReservationService.
 */

require_once __DIR__ . '/../../Reservations/Views/_reservation-helpers.php';

$basePath     = $basePath     ?? '';
$modifications = $modifications ?? [];
$filters      = $filters      ?? [];
$flash        = $flash        ?? null;

$statusFilter = (string) ($filters['status'] ?? 'pending');

$statuses = [
    'pending'     => 'Pending Review',
    'approved'    => 'Approved',
    'rejected'    => 'Rejected',
    'invalidated' => 'No Longer Available',
];
?>

<?php if ($flash): ?>
    <?php $feedback = $flash; ?>
    <?php require __DIR__ . '/../../Reservations/Views/reservation-feedback-view.php'; ?>
<?php endif; ?>

<!-- ── Filters ───────────────────────────────────────────────── -->
<form class="filter-bar" method="get" action="<?= esc($basePath) ?>/admin/modifications.php">
    <div class="filter-bar__inner">
        <div class="filter-bar__field">
            <label class="form-label" for="filter-mod-status">Status</label>
            <select class="form-control" id="filter-mod-status" name="status">
                <?php foreach ($statuses as $value => $label): ?>
                    <option value="<?= esc($value) ?>"<?= $statusFilter === $value ? ' selected' : '' ?>>
                        <?= esc($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-bar__actions">
            <button type="submit" class="btn btn--primary">Apply filters</button>
            <a class="btn btn--ghost" href="<?= esc($basePath) ?>/admin/modifications.php">Reset</a>
        </div>
    </div>
</form>

<p class="admin-filter-summary">
    <strong><?= count($modifications) ?></strong>
    <?= count($modifications) === 1 ? 'request' : 'requests' ?>
    with status “<?= esc($statuses[$statusFilter] ?? $statusFilter) ?>”.
</p>

<!-- ── Queue ─────────────────────────────────────────────────── -->
<?php if ($modifications === []): ?>
    <div class="empty-state">
        <div class="empty-state__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>
            </svg>
        </div>
        <h2 class="empty-state__title">No modification requests</h2>
        <p class="empty-state__description">
            <?php if ($statusFilter === 'pending'): ?>
                No student has asked to change a confirmed reservation yet.
            <?php else: ?>
                No modification request matches the selected status.
            <?php endif; ?>
        </p>
        <a class="btn btn--secondary" href="<?= esc($basePath) ?>/admin/modifications.php">Show pending requests</a>
    </div>
<?php else: ?>
    <div class="table-container">
        <table class="table admin-table">
            <thead>
                <tr>
                    <th scope="col">Request</th>
                    <th scope="col">Current booking</th>
                    <th scope="col">Proposed change</th>
                    <th scope="col">Requested</th>
                    <th scope="col">Status</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($modifications as $row): ?>
                    <tr>
                        <td data-label="Request">
                            <strong>#<?= (int) $row['id'] ?></strong>
                            <span class="admin-panel-table__meta"><?= esc((string) $row['student_name']) ?></span>
                        </td>
                        <td data-label="Current booking">
                            <?= esc((string) $row['original_room_name']) ?>
                            <span class="admin-panel-table__meta">
                                <?= esc(formatLocalTime((string) $row['original_starts_at'], 'D, d M H:i')) ?>
                                &ndash;
                                <?= esc(formatLocalTime((string) $row['original_ends_at'], 'H:i')) ?>
                            </span>
                        </td>
                        <td data-label="Proposed change">
                            <?= esc((string) $row['proposed_room_name']) ?>
                            <span class="admin-panel-table__meta">
                                <?= esc(formatLocalTime((string) $row['proposed_starts_at'], 'D, d M H:i')) ?>
                                &ndash;
                                <?= esc(formatLocalTime((string) $row['proposed_ends_at'], 'H:i')) ?>
                            </span>
                        </td>
                        <td data-label="Requested"><?= esc(formatLocalTime((string) $row['requested_at'], 'D, d M H:i')) ?></td>
                        <td data-label="Status">
                            <span class="badge <?= esc(modificationStatusBadge((string) $row['status'])) ?>">
                                <?= esc(modificationStatusLabel((string) $row['status'])) ?>
                            </span>
                        </td>
                        <td data-label="Actions">
                            <div class="admin-panel-table__actions">
                                <a class="btn btn--secondary btn--sm"
                                   href="<?= esc($basePath) ?>/admin/modification-details.php?id=<?= (int) $row['id'] ?>">
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
