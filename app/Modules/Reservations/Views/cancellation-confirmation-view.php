<?php

require_once __DIR__ . '/_reservation-helpers.php';

$basePath      = $basePath ?? '';
$formAction    = $formAction ?? '';
$cancelUrl     = $cancelUrl ?? ($basePath . '/student/reservations.php');
$reservation   = $reservation ?? [];
$old           = $old ?? [];
$errors        = $errors ?? [];
$feedback      = $feedback ?? null;
$warning       = $warning ?? null;
$canCancel     = $canCancel ?? true;
$blockedReason = $blockedReason ?? null;
$formId        = $formId ?? 'cancellation-form';

$reservationId = (string) ($reservation['id'] ?? '');
$reason        = (string) ($old['reason'] ?? '');

$startsDisplay = isset($reservation['starts_at']) && $reservation['starts_at'] !== null
    ? formatLocalTime((string) $reservation['starts_at'], 'D, d M Y H:i')
    : '—';
$endsDisplay = isset($reservation['ends_at']) && $reservation['ends_at'] !== null
    ? formatLocalTime((string) $reservation['ends_at'], 'D, d M Y H:i')
    : '—';

$feedbackView = __DIR__ . '/reservation-feedback-view.php';
?>
<div class="reservation-layout">
    <div class="reservation-layout__main">

        <?php if ($feedback) { require $feedbackView; } ?>

        <?php if ($warning): ?>
            <div class="alert alert--warning" role="status">
                <svg class="alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <div class="alert__body"><p><?= esc($warning) ?></p></div>
            </div>
        <?php endif; ?>

        <?php if (!$canCancel && $blockedReason): ?>
            <div class="alert alert--danger" role="alert">
                <svg class="alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
                <div class="alert__body"><p><?= esc($blockedReason) ?></p></div>
            </div>
        <?php endif; ?>

        <div class="panel">
            <div class="panel__header">
                <h1 class="panel__title">Cancel this reservation?</h1>
                <span class="badge <?= esc(reservationStatusBadge((string) ($reservation['status'] ?? 'pending'))) ?>">
                    <?= esc(reservationStatusLabel((string) ($reservation['status'] ?? 'pending'))) ?>
                </span>
            </div>
            <div class="panel__body">
                <p class="form-hint" style="margin-bottom:var(--space-4);">
                    Cancelling is permanent. The room and time slot will be released for other students.
                </p>

                <form id="<?= esc($formId) ?>" class="reservation-form" method="post"
                      action="<?= esc($formAction) ?>"
                      data-confirm="Cancel this reservation? This cannot be undone.">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="reservation_id" value="<?= esc($reservationId) ?>">

                    <dl class="reservation-summary__list">
                        <div class="reservation-summary__row">
                            <dt class="reservation-summary__label">Room</dt>
                            <dd class="reservation-summary__value"><?= esc((string) ($reservation['room_name'] ?? '—')) ?></dd>
                        </div>
                        <?php if (!empty($reservation['floor_name'])): ?>
                            <div class="reservation-summary__row">
                                <dt class="reservation-summary__label">Floor</dt>
                                <dd class="reservation-summary__value"><?= esc((string) $reservation['floor_name']) ?></dd>
                            </div>
                        <?php endif; ?>
                        <div class="reservation-summary__row">
                            <dt class="reservation-summary__label">Starts</dt>
                            <dd class="reservation-summary__value"><?= esc($startsDisplay) ?></dd>
                        </div>
                        <div class="reservation-summary__row">
                            <dt class="reservation-summary__label">Ends</dt>
                            <dd class="reservation-summary__value"><?= esc($endsDisplay) ?></dd>
                        </div>
                    </dl>

                    <div class="form-group">
                        <label class="form-label" for="<?= esc($formId) ?>-reason">Reason (optional)</label>
                        <textarea id="<?= esc($formId) ?>-reason" name="reason" class="form-control"
                                  maxlength="255" rows="3"
                                  placeholder="Let the administrator know why you are cancelling"><?= esc($reason) ?></textarea>
                        <?php if (!empty($errors['reason'])): ?>
                            <p class="form-error"><?= esc((string) $errors['reason']) ?></p>
                        <?php endif; ?>
                        <p class="form-hint">Stored with the reservation for the audit trail.</p>
                    </div>

                    <?php if (!empty($errors['general'])): ?>
                        <p class="form-error" role="alert"><?= esc((string) $errors['general']) ?></p>
                    <?php endif; ?>

                    <div class="reservation-actions">
                        <button type="submit" class="btn btn--danger"
                                data-submit-state="Cancelling…"
                                <?= $canCancel ? '' : 'disabled' ?>>Cancel reservation</button>
                        <a class="btn btn--secondary" href="<?= esc($cancelUrl) ?>">Keep reservation</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="reservation-layout__aside">
        <?php
        $summary = [
            'room_name'    => (string) ($reservation['room_name'] ?? ''),
            'floor_name'   => (string) ($reservation['floor_name'] ?? ''),
            'category'     => (string) ($reservation['category'] ?? ''),
            'starts_local' => $startsDisplay,
            'ends_local'   => $endsDisplay,
            'status'       => (string) ($reservation['status'] ?? ''),
        ];
        $summaryTitle  = 'Reservation';
        $summarySticky = true;
        require __DIR__ . '/reservation-summary-view.php';
        ?>
    </div>
</div>
