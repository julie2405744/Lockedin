<?php
/**
 * public/admin/dashboard.php
 * Admin dashboard stub — to be completed by Member 5.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Core/Auth.php';
require_once __DIR__ . '/../../app/Core/Csrf.php';
require_once __DIR__ . '/../../app/Core/helpers.php';

Auth::startSession();
Auth::requireAdmin();

$currentUser  = Auth::user();
$pageTitle    = 'Admin Dashboard';
$pageSubtitle = 'Overview and pending actions.';

ob_start();
?>
<div class="empty-state" style="padding:var(--space-12) 0;">
    <div class="empty-state__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m14.7 6.3 3-3a4.2 4.2 0 0 1-5.3 5.3L5 16a2.1 2.1 0 1 1-3-3l7.4-7.4a4.2 4.2 0 0 1 5.3-5.3l-3 3z"/></svg></div>
    <h2 class="empty-state__title">Admin dashboard</h2>
    <p class="empty-state__description">
        Member 5 will build this page. Check back once the admin dashboard module is complete.
    </p>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../app/Views/layouts/app-layout.php';
