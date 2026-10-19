<?php

require_once __DIR__ . '/_reservation-helpers.php';

$basePath          = $basePath ?? '';
$original          = $original ?? [];
$proposed          = $proposed ?? [];
$modification      = $modification ?? [];
$showReviewActions = $showReviewActions ?? false;
$reviewFormAction  = $reviewFormAction ?? null;
$reviewIdField     = $reviewIdField ?? 'modification_id';
$canReview         = $canReview ?? true;
$title             = $title ?? null;

$originalRoom = (string) ($original['room_name'] ?? '—');
$proposedRoom = (string) ($proposed['room_name'] ?? '—');

$originalStart = isset($original['starts_at']) && $original['starts_at'] !== null
    ? formatLocalTime((string) $original['starts_at'], 'D, d M Y H:i')
    : '—';
$originalEnd = isset($original['ends_at']) && $original['ends_at'] !== null
    ? formatLocalTime((string) $original['ends_at'], 'D, d M Y H:i')
    : '—';
$proposedStart = isset($proposed['starts_at']) && $proposed['starts_at'] !== null
    ? formatLocalTime((string) $proposed['starts_at'], 'D, d M Y H:i')
    : '—';
$proposedEnd = isset($proposed['ends_at']) && $proposed['ends_at'] !== null
    ? formatLocalTime((string) $proposed['ends_at'], 'D, d M Y H:i')
    : '—';

$roomChanged  = $originalRoom !== $proposedRoom;
$startChanged = $originalStart !== $proposedStart;
$endChanged   = $originalEnd !== $proposedEnd;
$anyChange    = $roomChanged || $startChanged || $endChanged;

$status         = (string) ($modification['status'] ?? 'pending');
$decisionReason = (string) ($modification['decision_reason']
    ?? $modification['rejection_reason']
    ?? $modification['invalidation_reason']
    ?? '');
$requestReason  = (string) ($modification['reason'] ?? '');

$requestedAt = isset($modification['requested_at']) && $modification['requested_at'] !== null
    ? formatLocalTime((string) $modification['requested_at'], 'D, d M Y H:i')
    : '';

$valueClass = static function (bool $changed): string {
    return $changed ? 'reservation-compare__value reservation-compare__value--changed' : 'reservation-compare__value';
};
?>
<section class="reservation-compare-section" aria-label="Modification comparison">
    <?php if ($title !== null && trim((string) $title) !== ''): ?>
        <div class="reservation-context__top" style="margin-bottom:var(--space-4);">
            <h2 class="panel__title"><?= esc((string) $title) ?></h2>
            <span class="badge <?= esc(modificationStatusBadge($status)) ?>"><?= esc(modificationStatusLabel($status)) ?></span>
        </div>
    <?php endif; ?>

    <div class="reservation-compare">
        <div class="reservation-compare__card">
            <p class="reservation-compare__eyebrow">Current reservation</p>
            <p class="reservation-compare__room"><?= esc($originalRoom) ?></p>
            <?php if (!empty($original['floor_name'])): ?>
                <p class="form-hint"><?= esc((string) $original['floor_name']) ?></p>
            <?php endif; ?>
            <div class="reservation-compare__row">
                <span class="reservation-compare__label">Starts</span>
                <span class="<?= esc($valueClass($startChanged)) ?>"><?= esc($originalStart) ?></span>
            </div>
            <div class="reservation-compare__row">
                <span class="reservation-compare__label">Ends</span>
                <span class="<?= esc($valueClass($endChanged)) ?>"><?= esc($originalEnd) ?></span>
            </div>
        </div>

        <div class="reservation-compare__card reservation-compare__card--proposed">
            <p class="reservation-compare__eyebrow">Proposed change</p>
            <p class="reservation-compare__room"><?= esc($proposedRoom) ?></p>
            <?php if (!empty($proposed['floor_name'])): ?>
                <p class="form-hint"><?= esc((string) $proposed['floor_name']) ?></p>
            <?php endif; ?>
            <div class="reservation-compare__row">
                <span class="reservation-compare__label">Starts</span>
                <span class="<?= esc($valueClass($startChanged)) ?>"><?= esc($proposedStart) ?></span>
            </div>
            <div class="reservation-compare__row">
                <span class="reservation-compare__label">Ends</span>
                <span class="<?= esc($valueClass($endChanged)) ?>"><?= esc($proposedEnd) ?></span>
            </div>
        </div>
    </div>

    <?php if (!$anyChange): ?>
        <p class="form-hint" style="margin-top:var(--space-3);">
            No differences were recorded for this request.
        </p>
    <?php endif; ?>

    <div class="reservation-facts" style="margin-top:var(--space-4);">
        <?php if ($requestedAt !== ''): ?>
            <div>
                <p class="reservation-fact__label">Requested</p>
                <p class="reservation-fact__value"><?= esc($requestedAt) ?></p>
            </div>
        <?php endif; ?>
        <div>
            <p class="reservation-fact__label">Status</p>
            <p class="reservation-fact__value"><?= esc(modificationStatusLabel($status)) ?></p>
        </div>
        <?php if (isset($modification['id'])): ?>
            <div>
                <p class="reservation-fact__label">Request</p>
                <p class="reservation-fact__value">#<?= esc((string) $modification['id']) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($requestReason !== ''): ?>
        <div class="reservation-reason" style="margin-top:var(--space-4);">
            <strong>Student's reason:</strong> <?= esc($requestReason) ?>
        </div>
    <?php endif; ?>

    <?php if ($decisionReason !== ''): ?>
        <div class="reservation-reason <?= $status === 'rejected' ? 'reservation-reason--rejected' : ($status === 'invalidated' ? 'reservation-reason--invalid' : '') ?>"
             style="margin-top:var(--space-4);">
            <strong>Administrator note:</strong> <?= esc($decisionReason) ?>
        </div>
    <?php endif; ?>

    <?php if ($showReviewActions && $reviewFormAction && $status === 'pending'): ?>
        <div class="reservation-actions" style="margin-top:var(--space-6);">
            <form class="reservation-actions__form" method="post" action="<?= esc($reviewFormAction) ?>"
                  data-confirm="Approve this change? The reservation will be updated.">
                <?= Csrf::field() ?>
                <input type="hidden" name="<?= esc($reviewIdField) ?>" value="<?= esc((string) ($modification['id'] ?? '')) ?>">
                <input type="hidden" name="decision" value="approve">
                <button type="submit" class="btn btn--primary" data-submit-state="Approving…"
                        <?= $canReview ? '' : 'disabled' ?>>Approve change</button>
            </form>
            <form class="reservation-actions__form" method="post" action="<?= esc($reviewFormAction) ?>"
                  data-confirm="Reject this change? The reservation stays as it is.">
                <?= Csrf::field() ?>
                <input type="hidden" name="<?= esc($reviewIdField) ?>" value="<?= esc((string) ($modification['id'] ?? '')) ?>">
                <input type="hidden" name="decision" value="reject">
                <button type="submit" class="btn btn--danger" data-submit-state="Rejecting…"
                        <?= $canReview ? '' : 'disabled' ?>>Reject change</button>
            </form>
        </div>
    <?php endif; ?>
</section>
