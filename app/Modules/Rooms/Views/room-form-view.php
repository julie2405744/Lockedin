<?php
/**
 * app/Modules/Rooms/Views/room-form-view.php
 * Room create/edit form — Member 2.
 *
 * Expected variables (from public/admin/room-form.php):
 *   $editing             (array|null) room row when editing
 *   $errors              (array)      field errors + optional 'general'
 *   $old                 (array)      repopulated values
 *   $floors              (array)      every floor (findAllFloors)
 *   $facilities          (array)      every facility (findAllFacilities)
 *   $selectedFacilityIds (int[])      checked facilities
 *   $basePath            (string)
 */

$basePath             = $basePath             ?? '';
$editing              = $editing              ?? null;
$errors               = $errors               ?? [];
$old                  = $old                  ?? [];
$floors               = $floors               ?? [];
$facilities           = $facilities           ?? [];
$selectedFacilityIds  = $selectedFacilityIds  ?? [];
$active               = 'rooms';
require __DIR__ . '/_rooms-nav.php';

$hasFloors = $floors !== [];
?>

<?php if (!empty($errors['general'])): ?>
    <div class="alert alert--danger" role="alert" aria-live="polite" style="margin-bottom: var(--space-6);">
        <svg class="alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <div class="alert__body"><p><?= esc($errors['general']) ?></p></div>
    </div>
<?php endif; ?>

<?php if ($editing !== null && $editing['status'] !== 'active'): ?>
    <div class="alert alert--warning" role="status" style="margin-bottom: var(--space-6);">
        <svg class="alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
        <div class="alert__body"><p>This room is currently <strong>inactive</strong> — students cannot see or book it. Reactivate it from the rooms list.</p></div>
    </div>
<?php endif; ?>

<?php if (!$hasFloors): ?>
    <div class="empty-state">
        <div class="empty-state__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <rect x="4" y="4" width="16" height="7" rx="1"/>
                <rect x="4" y="13" width="16" height="7" rx="1"/>
            </svg>
        </div>
        <h2 class="empty-state__title">Create a floor first</h2>
        <p class="empty-state__description">Every room belongs to exactly one floor, so at least one floor must exist before adding rooms.</p>
        <a href="<?= esc($basePath) ?>/admin/floor-form.php" class="btn btn--primary">Go to floors</a>
    </div>
<?php else: ?>

<div class="panel" style="max-width: 780px;">
    <div class="panel__header">
        <h2 class="panel__title"><?= $editing === null ? 'Room details' : 'Editing ' . esc($editing['name']) ?></h2>
    </div>
    <div class="panel__body">

        <form method="post" action="<?= esc($basePath) ?>/admin/room-form.php" class="form-stack" novalidate>
            <?= Csrf::field() ?>
            <?php if ($editing !== null): ?>
                <input type="hidden" name="room_id" value="<?= (int) $editing['id'] ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label form-label--required" for="floor_id">Floor</label>
                    <select class="form-control<?= isset($errors['floor_id']) ? ' form-control--error' : '' ?>"
                            id="floor_id" name="floor_id" required aria-required="true"
                            <?= isset($errors['floor_id']) ? 'aria-invalid="true" aria-describedby="floor_id-error"' : '' ?>>
                        <option value="">Select a floor…</option>
                        <?php foreach ($floors as $floor): ?>
                            <option value="<?= (int) $floor['id'] ?>" <?= (int) $old['floor_id'] === (int) $floor['id'] ? 'selected' : '' ?>>
                                Floor <?= (int) $floor['floor_number'] ?> — <?= esc($floor['name']) ?><?= $floor['status'] !== 'active' ? ' (inactive)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['floor_id'])): ?>
                        <p class="form-error" id="floor_id-error"><?= esc($errors['floor_id']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label form-label--required" for="category">Category</label>
                    <select class="form-control<?= isset($errors['category']) ? ' form-control--error' : '' ?>"
                            id="category" name="category" required aria-required="true"
                            <?= isset($errors['category']) ? 'aria-invalid="true" aria-describedby="category-error"' : '' ?>>
                        <option value="">Select a category…</option>
                        <?php foreach (['individual_study', 'group_study', 'lounge'] as $categoryValue): ?>
                            <option value="<?= esc($categoryValue) ?>" <?= $old['category'] === $categoryValue ? 'selected' : '' ?>>
                                <?= esc(roomCategoryLabel($categoryValue)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['category'])): ?>
                        <p class="form-error" id="category-error"><?= esc($errors['category']) ?></p>
                    <?php else: ?>
                        <p class="form-hint">Exactly three categories exist in the system.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label form-label--required" for="name">Room name</label>
                <input class="form-control<?= isset($errors['name']) ? ' form-control--error' : '' ?>"
                       type="text" id="name" name="name" maxlength="120"
                       value="<?= esc($old['name']) ?>"
                       placeholder="e.g. Quiet Room 3B"
                       required aria-required="true"
                       <?= isset($errors['name']) ? 'aria-invalid="true" aria-describedby="name-error"' : '' ?>>
                <?php if (isset($errors['name'])): ?>
                    <p class="form-error" id="name-error"><?= esc($errors['name']) ?></p>
                <?php else: ?>
                    <p class="form-hint">Must be unique on the selected floor.</p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label form-label--required" for="capacity">Capacity</label>
                <input class="form-control<?= isset($errors['capacity']) ? ' form-control--error' : '' ?>"
                       type="number" id="capacity" name="capacity"
                       min="1" max="9999" step="1"
                       value="<?= esc($old['capacity']) ?>"
                       required aria-required="true"
                       <?= isset($errors['capacity']) ? 'aria-invalid="true" aria-describedby="capacity-error"' : '' ?>>
                <?php if (isset($errors['capacity'])): ?>
                    <p class="form-error" id="capacity-error"><?= esc($errors['capacity']) ?></p>
                <?php else: ?>
                    <p class="form-hint">Maximum people, used by the student search filters.</p>
                <?php endif; ?>
            </div>

            <fieldset class="form-group" style="border:1px solid var(--color-border); border-radius: var(--radius-md); padding: var(--space-4);">
                <legend class="form-label">Facilities</legend>
                <?php if (isset($errors['facilities'])): ?>
                    <p class="form-error"><?= esc($errors['facilities']) ?></p>
                <?php endif; ?>
                <?php if ($facilities === []): ?>
                    <p class="rooms-meta">No facilities exist yet — rooms can be saved without any.</p>
                <?php endif; ?>
                <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: var(--space-2);">
                    <?php foreach ($facilities as $facility): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="facility_<?= (int) $facility['id'] ?>"
                                   name="facilities[]" value="<?= (int) $facility['id'] ?>"
                                   <?= in_array((int) $facility['id'], $selectedFacilityIds, false) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="facility_<?= (int) $facility['id'] ?>">
                                <?= esc($facility['name']) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <div class="form-group">
                <label class="form-label" for="description">Description <span class="rooms-meta">(optional)</span></label>
                <textarea class="form-control<?= isset($errors['description']) ? ' form-control--error' : '' ?>"
                          id="description" name="description" maxlength="2000"
                          rows="3" aria-describedby="description-hint"><?= esc($old['description']) ?></textarea>
                <p class="form-hint" id="description-hint">Shown to students on the room card — up to 2000 characters.</p>
                <?php if (isset($errors['description'])): ?>
                    <p class="form-error"><?= esc($errors['description']) ?></p>
                <?php endif; ?>
            </div>

            <?php if ($editing !== null): ?>
                <p class="rooms-meta">Created <?= esc($editing['created_at']) ?> · Last updated <?= esc($editing['updated_at']) ?></p>
            <?php endif; ?>

            <div style="display: flex; gap: var(--space-3);">
                <button type="submit" class="btn btn--primary">
                    <?= $editing === null ? 'Create room' : 'Save changes' ?>
                </button>
                <a href="<?= esc($basePath) ?>/admin/rooms.php" class="btn btn--secondary">Cancel</a>
            </div>
        </form>

    </div>
</div>

<?php endif; ?>
