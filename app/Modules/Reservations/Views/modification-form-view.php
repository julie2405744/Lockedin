<?php

require_once __DIR__ . '/_reservation-helpers.php';

$basePath               = $basePath ?? '';
$formAction             = $formAction ?? '';
$cancelUrl              = $cancelUrl ?? ($basePath . '/student/reservations.php');
$reservation            = $reservation ?? [];
$rooms                  = $rooms ?? [];
$old                    = $old ?? [];
$errors                 = $errors ?? [];
$feedback               = $feedback ?? null;
$minDateTime            = $minDateTime ?? null;
$maxDateTime            = $maxDateTime ?? null;
$hasPendingModification = $hasPendingModification ?? false;
$pendingModificationUrl = $pendingModificationUrl ?? null;
$formId                 = $formId ?? 'modification-form';

$proposedRoomId = (string) ($old['proposed_room_id'] ?? '');
$proposedStart  = (string) ($old['proposed_starts_at'] ?? '');
$proposedEnd    = (string) ($old['proposed_ends_at'] ?? '');
$reason         = (string) ($old['reason'] ?? '');

$originalStart = isset($reservation['starts_at']) && $reservation['starts_at'] !== null
    ? formatLocalTime((string) $reservation['starts_at'], 'D, d M Y H:i')
    : '—';
$originalEnd = isset($reservation['ends_at']) && $reservation['ends_at'] !== null
    ? formatLocalTime((string) $reservation['ends_at'], 'D, d M Y H:i')
    : '—';

$summaryRoomName = (string) ($reservation['room_name'] ?? '');
foreach ($rooms as $option) {
    if ($proposedRoomId !== '' && (string) ($option['id'] ?? '') === $proposedRoomId) {
        $summaryRoomName = (string) ($option['name'] ?? $summaryRoomName);
    }
}

$fieldError = static function (string $field) use ($errors): string {
    return esc((string) ($errors[$field] ?? ''));
};

$feedbackView = __DIR__ . '/reservation-feedback-view.php';
?>
<div class="reservation-layout">
    <div class="reservation-layout__main">

        <?php if ($feedback) { require $feedbackView; } ?>

        <?php if ($hasPendingModification): ?>
            <div class="alert alert--info" role="status">
                <svg class="alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
                </svg>
                <div class="alert__body">
                    <p class="alert__title">A change request is already open</p>
                    <p>Wait for an administrator to review your current request before submitting another one.</p>
                    <?php if ($pendingModificationUrl): ?>
                        <p style="margin-top:var(--space-2);">
                            <a href="<?= esc($pendingModificationUrl) ?>">View the pending request</a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="panel">
            <div class="panel__header">
                <h1 class="panel__title">Request a change</h1>
                <span class="badge badge--pending-review">Awaiting approval</span>
            </div>
            <div class="panel__body">
                <div class="reservation-facts" style="margin-bottom:var(--space-6);">
                    <div>
                        <p class="reservation-fact__label">Current room</p>
                        <p class="reservation-fact__value"><?= esc((string) ($reservation['room_name'] ?? '—')) ?></p>
                    </div>
                    <div>
                        <p class="reservation-fact__label">Current start</p>
                        <p class="reservation-fact__value"><?= esc($originalStart) ?></p>
                    </div>
                    <div>
                        <p class="reservation-fact__label">Current end</p>
                        <p class="reservation-fact__value"><?= esc($originalEnd) ?></p>
                    </div>
                </div>

                <form id="<?= esc($formId) ?>" class="reservation-form" method="post"
                      action="<?= esc($formAction) ?>" novalidate>
                    <?= Csrf::field() ?>
                    <input type="hidden" name="reservation_id" value="<?= esc((string) ($reservation['id'] ?? '')) ?>">

                    <fieldset class="reservation-fieldset">
                        <legend>Proposed change</legend>

                        <div class="form-group">
                            <label class="form-label form-label--required" for="<?= esc($formId) ?>-room">Room</label>
                            <select id="<?= esc($formId) ?>-room" name="proposed_room_id"
                                    class="form-control<?= $fieldError('proposed_room_id') !== '' ? ' form-control--error' : '' ?>"
                                    required data-summary-room-select>
                                <option value="">Keep the same room</option>
                                <?php foreach ($rooms as $option): ?>
                                    <?php $optionId = (string) ($option['id'] ?? ''); ?>
                                    <option value="<?= esc($optionId) ?>"
                                            data-room-name="<?= esc((string) ($option['name'] ?? '')) ?>"
                                            <?= $optionId === $proposedRoomId ? 'selected' : '' ?>>
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
                            <?php if ($fieldError('proposed_room_id') !== ''): ?>
                                <p class="form-error"><?= $fieldError('proposed_room_id') ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="reservation-interval">
                            <div class="form-group">
                                <label class="form-label form-label--required" for="<?= esc($formId) ?>-starts">New start</label>
                                <input type="datetime-local" id="<?= esc($formId) ?>-starts" name="proposed_starts_at"
                                       class="form-control<?= $fieldError('proposed_starts_at') !== '' ? ' form-control--error' : '' ?>"
                                       value="<?= esc($proposedStart) ?>"
                                       <?= $minDateTime ? 'min="' . esc($minDateTime) . '"' : '' ?>
                                       <?= $maxDateTime ? 'max="' . esc($maxDateTime) . '"' : '' ?>
                                       required data-summary-start>
                                <?php if ($fieldError('proposed_starts_at') !== ''): ?>
                                    <p class="form-error"><?= $fieldError('proposed_starts_at') ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label class="form-label form-label--required" for="<?= esc($formId) ?>-ends">New end</label>
                                <input type="datetime-local" id="<?= esc($formId) ?>-ends" name="proposed_ends_at"
                                       class="form-control<?= $fieldError('proposed_ends_at') !== '' ? ' form-control--error' : '' ?>"
                                       value="<?= esc($proposedEnd) ?>"
                                       <?= $minDateTime ? 'min="' . esc($minDateTime) . '"' : '' ?>
                                       required data-summary-end>
                                <?php if ($fieldError('proposed_ends_at') !== ''): ?>
                                    <p class="form-error"><?= $fieldError('proposed_ends_at') ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <p class="form-error" data-duration-hint hidden></p>
                    </fieldset>

                    <div class="form-group">
                        <label class="form-label" for="<?= esc($formId) ?>-reason">Reason (optional)</label>
                        <textarea id="<?= esc($formId) ?>-reason" name="reason" class="form-control"
                                  maxlength="255" rows="3"
                                  placeholder="Why do you need this change?"><?= esc($reason) ?></textarea>
                        <?php if ($fieldError('reason') !== ''): ?>
                            <p class="form-error"><?= $fieldError('reason') ?></p>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($errors['general'])): ?>
                        <p class="form-error" role="alert"><?= esc((string) $errors['general']) ?></p>
                    <?php endif; ?>

                    <div class="reservation-actions">
                        <button type="submit" class="btn btn--primary" data-submit-state="Submitting…"
                                <?= $hasPendingModification ? 'disabled' : '' ?>>Submit change request</button>
                        <a class="btn btn--secondary" href="<?= esc($cancelUrl) ?>">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="reservation-layout__aside">
        <?php
        $summary = [
            'room_name'    => $summaryRoomName,
            'floor_name'   => (string) ($reservation['floor_name'] ?? ''),
            'category'     => (string) ($reservation['category'] ?? ''),
            'starts_local' => $proposedStart,
            'ends_local'   => $proposedEnd,
        ];
        $summaryTitle  = 'Proposed change';
        $summaryLive   = true;
        $summaryFormId = $formId;
        $summaryNote   = 'The current reservation stays active until an administrator approves this change.';
        require __DIR__ . '/reservation-summary-view.php';
        ?>
    </div>
</div>
