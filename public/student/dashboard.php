<?php
/**
 * public/student/dashboard.php
 * Student dashboard stub — to be completed by Member 4.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Core/Auth.php';
require_once __DIR__ . '/../../app/Core/Csrf.php';
require_once __DIR__ . '/../../app/Core/helpers.php';

Auth::startSession();
Auth::requireStudent();

$currentUser  = Auth::user();
$pageTitle    = 'Dashboard';
$pageSubtitle = 'Welcome back, ' . esc($currentUser['name']) . '.';

ob_start();
?>
<div class="empty-state" style="padding:var(--space-12) 0;">
    <div class="empty-state__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V2h6v2M9 10h6M9 14h6M9 18h4"/></svg></div>
    <h2 class="empty-state__title">Student dashboard</h2>
    <p class="empty-state__description">
        Member 4 will build this page. Check back once the student experience module is complete.
    </p>
    <a href="<?= htmlspecialchars(BASE_PATH) ?>/student/rooms.php" class="btn btn--primary">Find a room</a>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../app/Views/layouts/app-layout.php';
