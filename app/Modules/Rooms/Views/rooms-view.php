<?php
/**
 * app/Modules/Rooms/Views/rooms-view.php
 * Rooms management list — Member 2.
 *
 * Expected variables (from public/admin/rooms.php):
 *   $rooms          (array)      findAllRooms() rows (joined floor info)
 *   $facilityCounts (array)      findFacilityCountsByRoom() map
 *   $flash          (array|null) ['type' => success|danger, 'message' => string]
 *   $basePath       (string)
 */

$basePath       = $basePath       ?? '';
$rooms          = $rooms          ?? [];
$facilityCounts = $facilityCounts ?? [];
$flash          = $flash          ?? null;
$active         = 'rooms';
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

<?php if ($rooms === []): ?>
    <div class="empty-state">
        <div class="empty-state__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M3 21V8l9-5 9 5v13"/>
                <path d="M9 21v-6h6v6"/>
            </svg>
        </div>
        <h2 class="empty-state__title">No rooms yet</h2>
        <p class="empty-state__description">
            Rooms are what students see and book. Create a floor first,
            then add rooms to it.
        </p>
        <a href="<?= esc($basePath) ?>/admin/room-form.php" class="btn btn--primary">Create the first room</a>
    </div>
<?php else: ?>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Room</th>
                    <th scope="col">Floor</th>
                    <th scope="col">Category</th>
                    <th scope="col">Capacity</th>
                    <th scope="col">Facilities</th>
                    <th scope="col">Status</th>
                    <th scope="col" style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rooms as $room): ?>
                    <tr>
                        <td>
                            <strong><?= esc($room['name']) ?></strong>
                            <?php if (trim((string) $room['description']) !== ''): ?>
                                <div class="rooms-meta"><?= esc($room['description']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= esc($room['floor_name']) ?>
                            <span class="rooms-meta">(Floor <?= (int) $room['floor_number'] ?>)</span>
                        </td>
                        <td><?= esc(roomCategoryLabel($room['category'])) ?></td>
                        <td><?= (int) $room['capacity'] ?></td>
                        <td><?= (int) ($facilityCounts[(int) $room['id']] ?? 0) ?></td>
                        <td><span class="badge <?= $room['status'] === 'active' ? 'badge--active' : 'badge--inactive'; ?>"><?= esc($room['status']) ?></span></td>
                        <td>
                            <div class="rooms-table__actions">
                                <a class="btn btn--secondary btn--sm"
                                   href="<?= esc($basePath) ?>/admin/room-form.php?id=<?= (int) $room['id'] ?>">
                                    Edit
                                </a>
                                <?php if ($room['status'] === 'active'): ?>
                                    <form method="post" action="<?= esc($basePath) ?>/admin/rooms.php"
                                          data-confirm="Deactivate room &quot;<?= esc($room['name']) ?>&quot;? It will disappear from the student room search, and all reservation history is preserved.">
                                        <input type="hidden" name="room_id" value="<?= (int) $room['id'] ?>">
                                        <input type="hidden" name="action" value="deactivate">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="btn btn--danger btn--sm">Deactivate</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="<?= esc($basePath) ?>/admin/rooms.php">
                                        <input type="hidden" name="room_id" value="<?= (int) $room['id'] ?>">
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
