<?php $basePath = defined('BASE_PATH') ? BASE_PATH : ''; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>403 — Access Denied — InFocus</title>
<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/variables.css">
<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/base.css">
<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/layout.css">
<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/components.css">
</head>
<body>
<div style="min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:var(--space-8);">
    <div style="text-align:center;max-width:440px;">
        <div style="font-size:64px;font-weight:700;color:var(--color-primary);margin-bottom:var(--space-4);">403</div>
        <h1 style="font-size:var(--font-size-xl);margin-bottom:var(--space-2);">Access denied</h1>
        <p style="color:var(--color-muted);margin-bottom:var(--space-8);">
            You do not have permission to view this page.
        </p>
        <a href="<?= htmlspecialchars($basePath) ?>/login.php" class="btn btn--primary">Go to sign in</a>
    </div>
</div>
</body>
</html>
