<?php
/**
 * app/Modules/Rooms/Views/closure-form-view.php
 * Closure create/edit form — Member 2.
 *
 * Expected variables (from public/admin/closure-form.php):
 *   $editing (array|null) closure row when editing
 *   $errors  (array)      field errors + optional 'general'
 *   $old     (array)      repopulated values (LOCAL Cairo inputs)
 *   $rooms   (array)      findAllRooms() rows
 *   $basePath (string)
 */

$basePath = $basePath ?? '';
$editing  = $editing  ?? null;
$errors   = $errors   ?? [];
$old      = $old      ?? [];
$rooms    = $rooms    ?? [];
$active   = 'closures';
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
        <h2 class="panel__title"><?= $editing === null ? 'Closure details' : 'Editing closure' ?></h2>
    </div>
    <div class="panel__body">

        <form method="post" action="<?= esc($basePath) ?>/admin/closure-form.php" class="form-stack" novalidate>
            <?= Csrf::field() ?>
            <?php if ($editing !== null): ?>
                <input type="hidden" name="closure_id" value="<?= (int) $editing['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label form-label--required" for="room_id">Room</label>
                <select class="form-control<?= isset($errors['room_id']) ? ' form-control--error' : '' ?>"
                        id="room_id" name="room_id" required aria-required="true"
                        <?= isset($errors['room_id']) ? 'aria-invalid="true" aria-describedby="room_id-error"' : '' ?>>
                    <option value="">Select a room…</option>
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?= (int) $room['id'] ?>" <?= (int) $old['room_id'] === (int) $room['id'] ? 'selected' : '' ?>>
                            <?= esc($room['name']) ?> — Floor <?= (int) $room['floor_number'] ?><?= $room['status'] !== 'active' ? ' (inactive)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['room_id'])): ?>
                    <p class="form-error" id="room_id-error"><?= esc($errors['room_id']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label form-label--required" for="starts_local">Starts (Cairo local)</label>
                    <input class="form-control<?= isset($errors['starts_local']) ? ' form-control--error' : '' ?>"
                           type="datetime-local" id="starts_local" name="starts_local"
                           value="<?= esc($old['starts_local']) ?>"
                           required aria-required="true"
                           <?= isset($errors['starts_local']) ? 'aria-invalid="true" aria-describedby="starts_local-error"' : '' ?>>
                    <?php if (isset($errors['starts_local'])): ?>
                        <p class="form-error" id="starts_local-error"><?= esc($errors['starts_local']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label form-label--required" for="ends_local">Ends (Cairo local)</label>
                    <input class="form-control<?= isset($errors['ends_local']) ? ' form-control--error' : '' ?>"
                           type="datetime-local" id="ends_local" name="ends_local"
                           value="<?= esc($old['ends_local']) ?>"
                           required aria-required="true"
                           <?= isset($errors['ends_local']) ? 'aria-invalid="true" aria-describedby="ends_local-error"' : '' ?>>
                    <?php if (isset($errors['ends_local'])): ?>
                        <p class="form-error" id="ends_local-error"><?= esc($errors['ends_local']) ?></p>
                    <?php else: ?>
                        <p class="form-hint">The period may cross midnight — storage converts both moments to UTC.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label form-label--required" for="reason">Reason</label>
                <input class="form-control<?= isset($errors['reason']) ? ' form-control--error' : '' ?>"
                       type="text" id="reason" name="reason" maxlength="255"
                       value="<?= esc($old['reason']) ?>"
                       placeholder="e.g. HVAC maintenance / building closed"
                       required aria-required="true"
                       <?= isset($errors['reason']) ? 'aria-invalid="true" aria-describedby="reason-error"' : '' ?>>
                <?php if (isset($errors['reason'])): ?>
                    <p class="form-error" id="reason-error"><?= esc($errors['reason']) ?></p>
                <?php else: ?>
                    <p class="form-hint">Shown in the closures list — up to 255 characters.</p>
                <?php endif; ?>
            </div>

            <?php if ($editing !== null): ?>
                <p class="rooms-meta">Added <?= esc(formatLocalTime($editing['created_at'], 'D, d M Y H:i')) ?> (Cairo) · currently attributed to admin #<?= (int) $editing['created_by'] ?></p>
            <?php endif; ?>

            <div style="display: flex; gap: var(--space-3);">
                <button type="submit" class="btn btn--primary">
                    <?= $editing === null ? 'Add closure' : 'Save changes' ?>
                </button>
                <a href="<?= esc($basePath) ?>/admin/closures.php" class="btn btn--secondary">Cancel</a>
            </div>
        </form>

    </div>
</div>
