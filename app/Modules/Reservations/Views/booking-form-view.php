<?php
/**
 * app/Modules/Reservations/Views/booking-form-view.php
 * Member 3 — booking request form component.
 *
 * Server-rendered booking form reused by the student room-details page and,
 * with a room picker, by admin "book on behalf of a student" screens. All
 * validation (duration limits, operating hours, closures, conflicts,
 * advance-booking window and cut-off) lives in ReservationService; this
 * view only collects input and repopulates it after a rejected submit.
 *
 * Expects:
 *   $basePath       (string)
 *   $formAction     (string)  POST target (page that owns the service call)
 *   $cancelUrl      (string|null)
 *   $room           (array|null) fixed room being booked: id, name, category,
 *                               floor_name, capacity, facilities[],
 *                               optional status, image_path
 *   $rooms          (array)   room options when $room is null:
 *                               each ['id', 'name', 'floor_name', 'capacity', 'category']
 *   $old            (array)   room_id, starts_at, ends_at (datetime-local values)
 *   $errors         (array)   'room_id' | 'starts_at' | 'ends_at' | 'general'
 *   $feedback       (array|null)  see reservation-feedback-view.php
 *   $minDateTime    (string|null) 'Y-m-d\TH:i' earliest selectable start
 *   $maxDateTime    (string|null) 'Y-m-d\TH:i' latest selectable start
 *   $canBook        (bool)    default true
 *   $blockedReason  (string|null) message shown when $canBook is false
 *   $intro          (string|null)
 *   $roomDetailsUrl (string|null)
 *   $formId         (string)  default 'booking-form'
 */

require_once __DIR__ . '/_reservation-helpers.php';

$basePath       = $basePath ?? '';
$formAction     = $formAction ?? '';
$cancelUrl      = $cancelUrl ?? null;
$room           = $room ?? null;
$rooms          = $rooms ?? [];
$old            = $old ?? [];
$errors         = $errors ?? [];
$feedback       = $feedback ?? null;
$minDateTime    = $minDateTime ?? null;
$maxDateTime    = $maxDateTime ?? null;
$canBook        = $canBook ?? true;
$blockedReason  = $blockedReason ?? null;
$intro          = $intro ?? null;
$roomDetailsUrl = $roomDetailsUrl ?? null;
$formId         = $formId ?? 'booking-form';

$startValue     = (string) ($old['starts_at'] ?? '');
$endValue       = (string) ($old['ends_at'] ?? '');
$selectedRoomId = (string) ($old['room_id'] ?? (is_array($room) ? ($room['id'] ?? '') : ''));

$hasFixedRoom = is_array($room) && !empty($room['id']);
$facilityNames = $hasFixedRoom ? reservationFacilityNames($room['facilities'] ?? []) : [];

// Live-summary room name (fixed room, or the room currently selected).
$summaryRoomName = $hasFixedRoom ? (string) ($room['name'] ?? '') : '';
foreach ($rooms as $option) {
    if ($selectedRoomId !== '' && (string) ($option['id'] ?? '') === $selectedRoomId) {
        $summaryRoomName = (string) ($option['name'] ?? $summaryRoomName);
    }
}

$fieldError = static function (string $field) use ($errors): string {
    return esc((string) ($errors[$field] ?? ''));
};
?>
<div class="reservation-layout">
    <div class="reservation-layout__main">

        <?php
        $feedbackView = __DIR__ . '/reservation-feedback-view.php';
        if ($feedback) {
            require $feedbackView;
        }
        ?>

        <?php if (!$canBook && $blockedReason): ?>
            <div class="alert alert--warning" role="status">
                <svg class="alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <div class="alert__body"><p><?= esc($blockedReason) ?></p></div>
            </div>
        <?php endif; ?>

        <div class="panel">
            <div class="panel__header">
                <h1 class="panel__title">Request a reservation</h1>
                <?php if ($hasFixedRoom && $roomDetailsUrl): ?>
                    <a class="btn btn--ghost btn--sm" href="<?= esc($roomDetailsUrl) ?>">Back to room</a>
                <?php endif; ?>
            </div>
            <div class="panel__body">
                <?php if ($intro): ?>
                    <p class="form-hint" style="margin-bottom:var(--space-4);"><?= esc($intro) ?></p>
                <?php endif; ?>

                <form id="<?= esc($formId) ?>" class="reservation-form" method="post"
                      action="<?= esc($formAction) ?>" novalidate>
                    <?= Csrf::field() ?>

                    <?php if (isset($errors['general']) && $errors['general'] !== ''): ?>
                        <p class="form-error" role="alert"><?= esc((string) $errors['general']) ?></p>
                    <?php endif; ?>

                    <?php if ($hasFixedRoom): ?>
                        <input type="hidden" name="room_id" value="<?= esc((string) $room['id']) ?>">
                        <section class="reservation-context" aria-label="Selected room">
                            <div class="reservation-context__top">
                                <div>
                                    <p class="reservation-context__name"><?= esc((string) ($room['name'] ?? '')) ?></p>
                                    <p class="form-hint"><?= esc(roomCategoryLabel((string) ($room['category'] ?? ''))) ?></p>
                                </div>
                                <div class="reservation-context__badges">
                                    <span class="badge badge--active"><?= esc(roomCategoryLabel((string) ($room['category'] ?? ''))) ?></span>
                                </div>
                            </div>
                            <div class="reservation-context__meta">
                                <?php if (!empty($room['floor_name'])): ?>
                                    <span class="reservation-meta">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M15 9h.01M9 13h.01M15 13h.01M9 17h.01M15 17h.01"/>
                                        </svg>
                                        <?= esc((string) $room['floor_name']) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (isset($room['capacity']) && $room['capacity'] !== null && $room['capacity'] !== ''): ?>
                                    <span class="reservation-meta">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                                            <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                                        </svg>
                                        Seats <?= esc((string) $room['capacity']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($facilityNames): ?>
                                <div class="reservation-facilities">
                                    <?php foreach ($facilityNames as $facilityName): ?>
                                        <span class="reservation-chip"><?= esc($facilityName) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </section>
                    <?php else: ?>
                        <div class="form-group">
                            <label class="form-label form-label--required" for="room_id">Room</label>
                            <select id="room_id" name="room_id"
                                    class="form-control<?= $fieldError('room_id') !== '' ? ' form-control--error' : '' ?>"
                                    required data-summary-room-select>
                                <option value="">Select a room</option>
                                <?php foreach ($rooms as $option): ?>
                                    <?php $optionId = (string) ($option['id'] ?? ''); ?>
                                    <option value="<?= esc($optionId) ?>"
                                            data-room-name="<?= esc((string) ($option['name'] ?? '')) ?>"
                                            <?= $optionId === $selectedRoomId ? 'selected' : '' ?>>
                                        <?= esc((string) ($option['name'] ?? '')) ?><?php
                                            if (!empty($option['floor_name'])) {
                                                echo ' — ' . esc((string) $option['floor_name']);
                                            }
                                            if (isset($option['capacity']) && $option['capacity'] !== '' && $option['capacity'] !== null) {
                                                echo ' (' . esc((string) $option['capacity']) . ' seats)';
                                            }
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($fieldError('room_id') !== ''): ?>
                                <p class="form-error"><?= $fieldError('room_id') ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <fieldset class="reservation-fieldset">
                        <legend>Date and time</legend>
                        <div class="reservation-interval">
                            <div class="form-group">
                                <label class="form-label form-label--required" for="<?= esc($formId) ?>-starts">Start</label>
                                <input type="datetime-local"
                                       id="<?= esc($formId) ?>-starts"
                                       name="starts_at"
                                       class="form-control<?= $fieldError('starts_at') !== '' ? ' form-control--error' : '' ?>"
                                       value="<?= esc($startValue) ?>"
                                       <?= $minDateTime ? 'min="' . esc($minDateTime) . '"' : '' ?>
                                       <?= $maxDateTime ? 'max="' . esc($maxDateTime) . '"' : '' ?>
                                       required data-summary-start>
                                <?php if ($fieldError('starts_at') !== ''): ?>
                                    <p class="form-error"><?= $fieldError('starts_at') ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label class="form-label form-label--required" for="<?= esc($formId) ?>-ends">End</label>
                                <input type="datetime-local"
                                       id="<?= esc($formId) ?>-ends"
                                       name="ends_at"
                                       class="form-control<?= $fieldError('ends_at') !== '' ? ' form-control--error' : '' ?>"
                                       value="<?= esc($endValue) ?>"
                                       <?= $minDateTime ? 'min="' . esc($minDateTime) . '"' : '' ?>
                                       required data-summary-end>
                                <?php if ($fieldError('ends_at') !== ''): ?>
                                    <p class="form-error"><?= $fieldError('ends_at') ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <p class="form-error" data-duration-hint hidden></p>
                        <p class="form-hint">
                            Times are in Africa/Cairo (local) time. Availability, duration limits
                            and the booking cut-off are checked by the server when you submit.
                        </p>
                    </fieldset>

                    <div class="reservation-actions">
                        <button type="submit" class="btn btn--primary"
                                data-submit-state="Submitting…"
                                <?= $canBook ? '' : 'disabled' ?>>Request reservation</button>
                        <?php if ($cancelUrl): ?>
                            <a class="btn btn--secondary" href="<?= esc($cancelUrl) ?>">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="reservation-layout__aside">
        <?php
        $summary = [
            'room_name'    => $summaryRoomName,
            'floor_name'   => $hasFixedRoom ? (string) ($room['floor_name'] ?? '') : '',
            'category'     => $hasFixedRoom ? (string) ($room['category'] ?? '') : '',
            'starts_local' => $startValue,
            'ends_local'   => $endValue,
        ];
        $summaryTitle  = 'Your request';
        $summaryLive   = true;
        $summaryFormId = $formId;
        $summaryNote   = 'New requests start as Pending and are reviewed by an administrator before they are confirmed.';
        require __DIR__ . '/reservation-summary-view.php';
        ?>
    </div>
</div>
