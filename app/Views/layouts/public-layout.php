<?php
/**
 * public-layout.php
 * Shared layout for unauthenticated pages (login, register, landing).
 * Usage: set $pageTitle before including this file.
 * Usage for content: use output buffering — see usage pattern in login.php
 */

$pageTitle = $pageTitle ?? 'InFocus';
$basePath = defined('BASE_PATH') ? BASE_PATH : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="InFocus — Study Room Booking System. Find and reserve study rooms at your university.">
    <title><?= htmlspecialchars($pageTitle) ?> — InFocus</title>

    <!-- Design system CSS -->
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/variables.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/base.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/layout.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/components.css">
</head>
<body>

<a class="skip-link" href="#main-content">Skip to main content</a>

<main id="main-content" tabindex="-1">
    <?= $content ?? '' ?>
</main>

<script src="<?= htmlspecialchars($basePath) ?>/assets/js/navigation.js" defer></script>
</body>
</html>
