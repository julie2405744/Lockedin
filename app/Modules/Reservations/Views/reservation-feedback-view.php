<?php


$feedback = $feedback ?? null;

if (!is_array($feedback)) {
    return;
}

$title   = trim((string) ($feedback['title'] ?? ''));
$message = trim((string) ($feedback['message'] ?? ''));
$rawDetails = is_array($feedback['details'] ?? null) ? $feedback['details'] : [];
$details = [];

foreach ($rawDetails as $detail) {
    $detail = trim((string) $detail);
    if ($detail !== '') {
        $details[] = $detail;
    }
}

if ($title === '' && $message === '' && !$details) {
    return;
}

$allowedTypes = ['success', 'warning', 'danger', 'info'];
$type = in_array($feedback['type'] ?? '', $allowedTypes, true) ? $feedback['type'] : 'info';

$icons = [
    'success' => '<path d="M20 6 9 17l-5-5"/>',
    'warning' => '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
    'danger'  => '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',
    'info'    => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
];

// Errors interrupt; anything else is a polite status update.
$role = $type === 'danger' ? 'alert' : 'status';
?>
<div class="alert alert--<?= esc($type) ?>" role="<?= esc($role) ?>" aria-live="polite">
    <svg class="alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <?= $icons[$type] ?>
    </svg>
    <div class="alert__body">
        <?php if ($title !== ''): ?>
            <p class="alert__title"><?= esc($title) ?></p>
        <?php endif; ?>
        <?php if ($message !== ''): ?>
            <p><?= esc($message) ?></p>
        <?php endif; ?>
        <?php if ($details): ?>
            <ul class="alert__list">
                <?php foreach ($details as $detail): ?>
                    <li><?= esc($detail) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
