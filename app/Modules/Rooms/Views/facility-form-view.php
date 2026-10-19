<?php
/**
 * app/Modules/Rooms/Views/facility-form-view.php
 * Facility create/edit form — Member 2.
 *
 * Expected variables (from public/admin/facility-form.php):
 *   $editing  (array|null) facility row when editing, null when creating
 *   $errors   (array)      field errors + optional 'general'
 *   $old      (array)      repopulated field values
 *   $basePath (string)
 */

$basePath = $basePath ?? '';
$editing  = $editing  ?? null;
$errors   = $errors   ?? [];
$old      = $old      ?? [];
$active   = 'facilities';
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

<div class="panel" style="max-width: 720px;">
    <div class="panel__header">
        <h2 class="panel__title"><?= $editing === null ? 'Facility details' : 'Editing ' . esc($editing['name']) ?></h2>
    </div>
    <div class="panel__body">

        <form method="post" action="<?= esc($basePath) ?>/admin/facility-form.php" class="form-stack" novalidate>
            <?= Csrf::field() ?>
            <?php if ($editing !== null): ?>
                <input type="hidden" name="facility_id" value="<?= (int) $editing['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label form-label--required" for="name">Facility name</label>
                <input class="form-control<?= isset($errors['name']) ? ' form-control--error' : '' ?>"
                       type="text" id="name" name="name" maxlength="100"
                       value="<?= esc($old['name']) ?>"
                       placeholder="e.g. Projector"
                       required aria-required="true"
                       <?= isset($errors['name']) ? 'aria-invalid="true" aria-describedby="name-error"' : '' ?>>
                <?php if (isset($errors['name'])): ?>
                    <p class="form-error" id="name-error"><?= esc($errors['name']) ?></p>
                <?php else: ?>
                    <p class="form-hint">Must be unique — up to 100 characters. This is what students see as a filter option.</p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Description <span class="rooms-meta">(optional)</span></label>
                <textarea class="form-control<?= isset($errors['description']) ? ' form-control--error' : '' ?>"
                          id="description" name="description" maxlength="255"
                          rows="2" aria-describedby="description-hint"><?= esc($old['description']) ?></textarea>
                <p class="form-hint" id="description-hint">A short clarification shown to admins — up to 255 characters.</p>
                <?php if (isset($errors['description'])): ?>
                    <p class="form-error"><?= esc($errors['description']) ?></p>
                <?php endif; ?>
            </div>

            <?php if ($editing !== null): ?>
                <p class="rooms-meta">Created <?= esc($editing['created_at']) ?></p>
            <?php endif; ?>

            <div style="display: flex; gap: var(--space-3);">
                <button type="submit" class="btn btn--primary">
                    <?= $editing === null ? 'Create facility' : 'Save changes' ?>
                </button>
                <a href="<?= esc($basePath) ?>/admin/facilities.php" class="btn btn--secondary">Cancel</a>
            </div>
        </form>

    </div>
</div>
