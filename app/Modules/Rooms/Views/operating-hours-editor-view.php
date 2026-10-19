<?php
/**
 * app/Modules/Rooms/Views/operating-hours-editor-view.php
 * Weekly schedule editor for one room — Member 2.
 *
 * Expected variables (from public/admin/operating-hours-editor.php):
 *   $room        (array)     findRoomById() row (+ floor_name/floor_number)
 *   $rowsByDay  (array)  [day => ['opens','closes'] rows]
 *   $dayErrors   (array)     [day => first error message]
 *   $errors      (array)     optional 'general'
 *   $flash       (array|null)['type' => .., 'message' => ..]
 *   $dayNames    (array)     [0..6 => name]
 *   $basePath    (string)
 */

$basePath   = $basePath   ?? '';
$room       = $room       ?? [];
$rowsByDay  = $rowsByDay  ?? [];
$dayErrors  = $dayErrors  ?? [];
$errors     = $errors     ?? [];
$flash      = $flash      ?? null;
$dayNames   = $dayNames   ?? [0 => 'Sunday'];
$active     = 'hours';
require __DIR__ . '/_rooms-nav.php';
?>

<?php if ($flash): ?>
    <div class="alert alert--<?= esc($flash['type']) ?>" role="status" aria-live="polite" style="margin-bottom: var(--space-6);">
        <svg class="alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <div class="alert__body"><p><?= esc($flash['message']) ?></p></div>
    </div>
<?php endif; ?>

<?php if (!empty($errors['general'])): ?>
    <div class="alert alert--danger" role="alert" aria-live="polite" style="margin-bottom: var(--space-6);">
        <svg class="alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <div class="alert__body"><p><?= esc($errors['general']) ?></p></div>
    </div>
<?php endif; ?>

<?php if ($room['status'] !== 'active'): ?>
    <div class="alert alert--warning" role="status" style="margin-bottom: var(--space-6);">
        <svg class="alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
        <div class="alert__body"><p>This room is <strong>inactive</strong> — operating hours are saved but students cannot see the room until it is reactivated from the rooms list.</p></div>
    </div>
<?php endif; ?>

<div class="panel" style="max-width: 820px;">
    <div class="panel__header">
        <h2 class="panel__title">Weekly schedule — <?= esc($room['name']) ?></h2>
        <p class="rooms-meta"><?= esc($room['floor_name']) ?> (Floor <?= (int) $room['floor_number'] ?>) · <?= esc(roomCategoryLabel($room['category'])) ?> · capacity <?= (int) $room['capacity'] ?></p>
    </div>
    <div class="panel__body">

        <div class="alert alert--info" role="note" style="margin-bottom: var(--space-6);">
            <svg class="alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
            </svg>
            <div class="alert__body"><p>Times are <strong>Africa/Cairo local time</strong>, 24-hour format. Intervals may be back-to-back but must not overlap, and none may cross midnight. Save applies to the whole week at once.</p></div>
        </div>

        <form method="post" action="<?= esc($basePath) ?>/admin/operating-hours-editor.php" novalidate>
    <?= Csrf::field() ?>
    <input type="hidden" name="room_id" value="<?= (int) $room['id'] ?>">

            <?php foreach ($dayNames as $dayNumber => $dayName): ?>
                <?php $dayRows = $rowsByDay[$dayNumber] ?? []; ?>
                <fieldset class="oh-day">
                    <legend class="form-label"><?= esc($dayName) ?></legend>

                    <div class="oh-list" data-interval-list="<?= $dayNumber ?>" data-testid="list-day-<?= $dayNumber ?>"><?php foreach ($dayRows as $interval): ?><div class="oh-row" data-interval-row>
                                    <input class="form-control" type="time" name="intervals[<?= $dayNumber ?>][opens_at][]" value="<?= esc($interval['opens']) ?>" aria-label="<?= esc($dayName) ?> open time">
                                    <span class="oh-sep" aria-hidden="true">–</span>
                                    <input class="form-control" type="time" name="intervals[<?= $dayNumber ?>][closes_at][]" value="<?= esc($interval['closes']) ?>" aria-label="<?= esc($dayName) ?> close time">
                                    <button type="button" class="btn btn--ghost btn--sm" data-remove-interval>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        Remove
                                    </button>
                                </div><?php endforeach; ?></div>

                    <?php if (isset($dayErrors[$dayNumber])): ?>
                        <p class="form-error" role="alert"><?= esc($dayErrors[$dayNumber]) ?></p>
                    <?php endif; ?>

                    <div class="oh-day__actions">
                        <button type="button" class="btn btn--secondary btn--sm" data-add-interval="<?= $dayNumber ?>">
                            + Add interval
                        </button>
                    </div>

                    <template data-interval-template="<?= $dayNumber ?>"><div class="oh-row" data-interval-row>
                                    <input class="form-control" type="time" name="intervals[<?= $dayNumber ?>][opens_at][]" value="" required aria-label="<?= esc($dayName) ?> open time">
                                    <span class="oh-sep" aria-hidden="true">–</span>
                                    <input class="form-control" type="time" name="intervals[<?= $dayNumber ?>][closes_at][]" value="" aria-label="<?= esc($dayName) ?> close time">
                                    <button type="button" class="btn btn--ghost btn--sm" data-remove-interval>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        Remove
                                    </button>
                                </div></template>
                </fieldset>
            <?php endforeach; ?>

            <div style="display: flex; gap: var(--space-3); margin-top: var(--space-6);">
                <button type="submit" class="btn btn--primary" data-confirm-empty="Saving now would DELETE the entire weekly schedule for this room, and students will not be able to book it. Continue?">
                    Save schedule
                </button>
                <a href="<?= esc($basePath) ?>/admin/operating-hours.php" class="btn btn--secondary">Cancel</a>
            </div>
        </form>

    </div>
</div>
