<?php
/**
 * app/Modules/Rooms/Views/floors-view.php
 * Floors management list — Member 2.
 *
 * Expected variables (from public/admin/floors.php):
 *   $floors      (array)      findAllFloors() rows
 *   $roomCounts  (array)      findRoomCountsByFloor() map, keyed by floor id
 *   $flash       (array|null) ['type' => success|danger, 'message' => string]
 *   $basePath    (string)
 */

$basePath   = $basePath   ?? '';
$floors     = $floors     ?? [];
$roomCounts = $roomCounts ?? [];
$flash      = $flash      ?? null;
$active     = 'floors';
require __DIR__ . '/_rooms-nav.php';
?>

<?php if ($flash): ?>
    <div class="alert alert--<?= esc($flash['type']) ?>" role="status" aria-live="polite" style="margin-bottom: var(--space-6);">
        <svg class="alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <div class="alert__body">
            <p><?= esc($flash['message']) ?></p>
        </div>
    </div>
<?php endif; ?>

<?php if ($floors === []): ?>
    <div class="empty-state">
        <div class="empty-state__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <rect x="4" y="4" width="16" height="7" rx="1"/>
                <rect x="4" y="13" width="16" height="7" rx="1"/>
            </svg>
        </div>
        <h2 class="empty-state__title">No floors yet</h2>
        <p class="empty-state__description">
            Floors are the backbone of the room directory. Create the first floor,
            then add rooms to it.
        </p>
        <a href="<?= esc($basePath) ?>/admin/floor-form.php" class="btn btn--primary">Create the first floor</a>
    </div>
<?php else: ?>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Name</th>
                    <th scope="col">Rooms</th>
                    <th scope="col">Status</th>
                    <th scope="col">Active rooms</th>
                    <th scope="col"><span class="rooms-meta">Updated</span></th>
                    <th scope="col" style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($floors as $floor): ?>
                    <?php $counts = $roomCounts[(int) $floor['id']] ?? ['total' => 0, 'active' => 0]; ?>
                    <tr>
                        <td><?= (int) $floor['floor_number'] ?></td>
                        <td>
                            <strong><?= esc($floor['name']) ?></strong>
                            <?php if (trim((string) $floor['description']) !== ''): ?>
                                <div class="rooms-meta"><?= esc($floor['description']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= (int) $counts['total'] ?></td>
                        <td><span class="badge <?= $floor['status'] === 'active' ? 'badge--active' : 'badge--inactive'; ?>"><?= esc($floor['status']) ?></span></td>
                        <td><?= (int) $counts['active'] ?></td>
                        <td><span class="rooms-meta"><?= esc($floor['updated_at']) ?></span></td>
                        <td>
                            <div class="rooms-table__actions">
                                <a class="btn btn--secondary btn--sm"
                                   href="<?= esc($basePath) ?>/admin/floor-form.php?id=<?= (int) $floor['id'] ?>">
                                    Edit
                                </a>
                                <?php if ($floor['status'] === 'active'): ?>
                                    <form method="post" action="<?= esc($basePath) ?>/admin/floors.php"
                                          data-confirm="Deactivate floor &quot;<?= esc($floor['name']) ?>&quot;? Rooms on it stay in place, and all reservation history is preserved. Inactive floors are excluded from the student room search.">
                                        <input type="hidden" name="floor_id" value="<?= (int) $floor['id'] ?>">
                                        <input type="hidden" name="action" value="deactivate">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="btn btn--danger btn--sm">Deactivate</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="<?= esc($basePath) ?>/admin/floors.php">
                                        <input type="hidden" name="floor_id" value="<?= (int) $floor['id'] ?>">
                                        <input type="hidden" name="action" value="activate">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="btn btn--secondary btn--sm">Activate</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
