<?php
/**
 * app/Modules/Rooms/Views/floor-form-view.php
 * Floor create/edit form — Member 2.
 *
 * Expected variables (from public/admin/floor-form.php):
 *   $editing  (array|null) room row when editing, null when creating
 *   $errors   (array)      field errors + optional 'general'
 *   $old      (array)      repopulated field values
 *   $basePath (string)
 */

$basePath = $basePath ?? '';
$editing  = $editing  ?? null;
$errors   = $errors   ?? [];
$old      = $old      ?? [];
$active   = 'floors';
require __DIR__ . '/_rooms-nav.php';
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
        <div class="alert__body"><p>This floor is currently <strong>inactive</strong> — rooms on it are excluded from the student room search. Reactivating restores visibility.</p></div>
    </div>
<?php endif; ?>

<div class="panel" style="max-width: 720px;">
    <div class="panel__header">
        <h2 class="panel__title"><?= $editing === null ? 'Floor details' : 'Floor #' . (int) $editing['floor_number'] . ' — ' . esc($editing['name']) ?></h2>
    </div>
    <div class="panel__body">

        <form method="post" action="<?= esc($basePath) ?>/admin/floor-form.php" class="form-stack" novalidate>
            <?= Csrf::field() ?>
            <?php if ($editing !== null): ?>
                <input type="hidden" name="floor_id" value="<?= (int) $editing['id'] ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label form-label--required" for="floor_number">Floor number</label>
                    <input class="form-control<?= isset($errors['floor_number']) ? ' form-control--error' : '' ?>"
                           type="number" id="floor_number" name="floor_number"
                           min="1" max="999" step="1"
                           value="<?= esc($old['floor_number']) ?>"
                           required aria-required="true"
                           <?= isset($errors['floor_number']) ? 'aria-invalid="true" aria-describedby="floor_number-error"' : '' ?>>
                    <?php if (isset($errors['floor_number'])): ?>
                        <p class="form-error" id="floor_number-error"><?= esc($errors['floor_number']) ?></p>
                    <?php else: ?>
                        <p class="form-hint">Shown to students on room cards (1–999, unique).</p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label form-label--required" for="name">Floor name</label>
                    <input class="form-control<?= isset($errors['name']) ? ' form-control--error' : '' ?>"
                           type="text" id="name" name="name" maxlength="100"
                           value="<?= esc($old['name']) ?>"
                           placeholder="e.g. Ground Floor"
                           required aria-required="true"
                           <?= isset($errors['name']) ? 'aria-invalid="true" aria-describedby="name-error"' : '' ?>>
                    <?php if (isset($errors['name'])): ?>
                        <p class="form-error" id="name-error"><?= esc($errors['name']) ?></p>
                    <?php else: ?>
                        <p class="form-hint">Up to 100 characters.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Description <span class="rooms-meta">(optional)</span></label>
                <textarea class="form-control<?= isset($errors['description']) ? ' form-control--error' : '' ?>"
                          id="description" name="description" maxlength="1000"
                          rows="3" aria-describedby="description-hint"><?= esc($old['description']) ?></textarea>
                <p class="form-hint" id="description-hint">e.g. quiet floors near the atrium — up to 1000 characters.</p>
                <?php if (isset($errors['description'])): ?>
                    <p class="form-error"><?= esc($errors['description']) ?></p>
                <?php endif; ?>
            </div>

            <?php if ($editing !== null): ?>
                <p class="rooms-meta">Created <?= esc($editing['created_at']) ?> · Last updated <?= esc($editing['updated_at']) ?></p>
            <?php endif; ?>

            <div style="display: flex; gap: var(--space-3);">
                <button type="submit" class="btn btn--primary">
                    <?= $editing === null ? 'Create floor' : 'Save changes' ?>
                </button>
                <a href="<?= esc($basePath) ?>/admin/floors.php" class="btn btn--secondary">Cancel</a>
            </div>
        </form>

    </div>
</div>
