<?php

$reservation   = $reservation ?? [];
$canCancel     = $canCancel ?? false;
$canModify     = $canModify ?? false;
$cancelUrl     = $cancelUrl ?? null;
$modifyUrl     = $modifyUrl ?? null;
$detailsUrl    = $detailsUrl ?? null;
$cancelConfirm = $cancelConfirm ?? 'Cancel this reservation? This cannot be undone.';
$size          = $size ?? 'sm';

$buttonClass = 'btn btn--secondary' . ($size === 'sm' ? ' btn--sm' : '');
$dangerClass = 'btn btn--danger' . ($size === 'sm' ? ' btn--sm' : '');
$ghostClass  = 'btn btn--ghost' . ($size === 'sm' ? ' btn--sm' : '');

$hasAction = ($detailsUrl && $reservation) || ($canModify && $modifyUrl) || ($canCancel && $cancelUrl);

if (!$hasAction) {
    return;
}
?>
<div class="reservation-actions">
    <?php if ($detailsUrl): ?>
        <a class="<?= esc($ghostClass) ?>" href="<?= esc($detailsUrl) ?>">View details</a>
    <?php endif; ?>

    <?php if ($canModify && $modifyUrl): ?>
        <a class="<?= esc($buttonClass) ?>" href="<?= esc($modifyUrl) ?>">Request change</a>
    <?php endif; ?>

    <?php if ($canCancel && $cancelUrl): ?>
        <a class="<?= esc($dangerClass) ?>" href="<?= esc($cancelUrl) ?>"
           aria-label="Cancel reservation" title="<?= esc($cancelConfirm) ?>">Cancel</a>
    <?php endif; ?>
</div>
