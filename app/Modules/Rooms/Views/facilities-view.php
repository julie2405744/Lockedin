<?php
/**
 * app/Modules/Rooms/Views/facilities-view.php
 * Facilities management list — Member 2.
 *
 * Expected variables (from public/admin/facilities.php):
 *   $facilities (array)      findAllFacilities() rows
 *   $roomCounts (array)      findRoomCountsByFacility() map
 *   $flash      (array|null) ['type' => success|danger, 'message' => string]
 *   $basePath   (string)
 */

$basePath   = $basePath   ?? '';
$facilities = $facilities ?? [];
$roomCounts = $roomCounts ?? [];
$flash      = $flash      ?? null;
$active     = 'facilities';
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

<?php if ($facilities === []): ?>
    <div class="empty-state">
        <div class="empty-state__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M6 3v7a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V3"/>
                <path d="M12 12v9"/>
                <circle cx="12" cy="3" r="1"/>
            </svg>
        </div>
        <h2 class="empty-state__title">No facilities yet</h2>
        <p class="empty-state__description">
            Facilities (Wi-Fi, whiteboards, projectors…) appear as filter options
            in the student room search and as checkboxes on the room form.
        </p>
        <a href="<?= esc($basePath) ?>/admin/facility-form.php" class="btn btn--primary">Create the first facility</a>
    </div>
<?php else: ?>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Facility</th>
                    <th scope="col">Rooms using it</th>
                    <th scope="col"><span class="rooms-meta">Created</span></th>
                    <th scope="col" style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($facilities as $facility): ?>
                    <?php $roomsUsing = $roomCounts[(int) $facility['id']] ?? 0; ?>
                    <tr>
                        <td>
                            <strong><?= esc($facility['name']) ?></strong>
                            <?php if (trim((string) $facility['description']) !== ''): ?>
                                <div class="rooms-meta"><?= esc($facility['description']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= (int) $roomsUsing ?>
                            <?php if ($roomsUsing > 0): ?>
                                <div class="rooms-meta">Remove it from those rooms first</div>
                            <?php endif; ?>
                        </td>
                        <td><span class="rooms-meta"><?= esc($facility['created_at']) ?></span></td>
                        <td>
                            <div class="rooms-table__actions">
                                <a class="btn btn--secondary btn--sm"
                                   href="<?= esc($basePath) ?>/admin/facility-form.php?id=<?= (int) $facility['id'] ?>">
                                    Edit
                                </a>
                                <?php if ($roomsUsing === 0): ?>
                                    <form method="post" action="<?= esc($basePath) ?>/admin/facilities.php"
                                          data-confirm="Remove facility &quot;<?= esc($facility['name']) ?>&quot;? This permanently deletes it — rooms will no longer offer it as an amenity.">
                                        <input type="hidden" name="facility_id" value="<?= (int) $facility['id'] ?>">
                                        <input type="hidden" name="action" value="remove">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="btn btn--danger btn--sm">Remove</button>
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
