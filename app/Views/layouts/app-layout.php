<?php
/**
 * app-layout.php
 * Shared layout for all authenticated pages (student + admin).
 *
 * Variables expected before include:
 *   $pageTitle   (string)  — page title shown in <title> and page header
 *   $currentUser (array)   — ['id', 'name', 'email', 'role']
 *   $content     (string)  — rendered page HTML (output buffering pattern)
 *
 * Optional:
 *   $pageSubtitle (string) — subtitle shown below the page heading
 *   $pageActions  (string) — HTML for action buttons in the page header
 *   $pageStyles   (string) — optional extra <link> tags for module CSS
 *   $pageScripts  (string) — optional extra <script> tags
 *                            (coordinated additions, used by Member 2+)
 */

$pageTitle    = $pageTitle    ?? 'InFocus';
$pageSubtitle = $pageSubtitle ?? '';
$pageActions  = $pageActions  ?? '';
$pageStyles   = $pageStyles   ?? '';
$pageScripts  = $pageScripts  ?? '';
$currentUser  = $currentUser  ?? ['name' => '', 'email' => '', 'role' => 'student'];
$basePath     = defined('BASE_PATH') ? BASE_PATH : '';

$isAdmin   = ($currentUser['role'] ?? '') === 'admin';
$userName  = htmlspecialchars($currentUser['name']  ?? '');
$userEmail = htmlspecialchars($currentUser['email'] ?? '');
$userInitial = mb_strtoupper(mb_substr($currentUser['name'] ?? 'U', 0, 1));

// Compare paths without the optional application subdirectory.
$requestPath = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$currentPath = $basePath !== '' && strpos($requestPath, $basePath) === 0
    ? substr($requestPath, strlen($basePath))
    : $requestPath;
$isActivePath = static fn(string $path): bool => $path === $currentPath;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — InFocus</title>

    <link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/variables.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/base.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/layout.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/components.css">
    <?= $pageStyles ?>
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>
<div class="page-shell">

    <!-- ── Site header ───────────────────────────────────────── -->
    <header class="site-header" role="banner">
        <div class="site-header__inner">

            <!-- Brand -->
            <a href="<?= htmlspecialchars($basePath . ($isAdmin ? '/admin/dashboard.php' : '/student/dashboard.php')) ?>"
               class="site-header__brand"
               aria-label="InFocus home">
                <span class="brand-wordmark">In<span>Focus</span></span>
            </a>

            <!-- Desktop navigation -->
            <nav class="site-nav" aria-label="Main navigation">
                <?php if ($isAdmin): ?>
                    <a href="<?= htmlspecialchars($basePath) ?>/admin/dashboard.php"     class="site-nav__link<?= $isActivePath('/admin/dashboard.php') ? ' site-nav__link--active' : '' ?>"<?= $isActivePath('/admin/dashboard.php') ? ' aria-current="page"' : '' ?>>Dashboard</a>
                    <a href="<?= htmlspecialchars($basePath) ?>/admin/reservations.php"  class="site-nav__link<?= $isActivePath('/admin/reservations.php') ? ' site-nav__link--active' : '' ?>"<?= $isActivePath('/admin/reservations.php') ? ' aria-current="page"' : '' ?>>Reservations</a>
                    <a href="<?= htmlspecialchars($basePath) ?>/admin/modifications.php" class="site-nav__link<?= $isActivePath('/admin/modifications.php') ? ' site-nav__link--active' : '' ?>"<?= $isActivePath('/admin/modifications.php') ? ' aria-current="page"' : '' ?>>Modifications</a>
                    <a href="<?= htmlspecialchars($basePath) ?>/admin/rooms.php"         class="site-nav__link<?= $isActivePath('/admin/rooms.php') ? ' site-nav__link--active' : '' ?>"<?= $isActivePath('/admin/rooms.php') ? ' aria-current="page"' : '' ?>>Rooms &amp; Floors</a>
                    <a href="<?= htmlspecialchars($basePath) ?>/admin/reports.php"       class="site-nav__link<?= $isActivePath('/admin/reports.php') ? ' site-nav__link--active' : '' ?>"<?= $isActivePath('/admin/reports.php') ? ' aria-current="page"' : '' ?>>Reports</a>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($basePath) ?>/student/dashboard.php"   class="site-nav__link<?= $isActivePath('/student/dashboard.php') ? ' site-nav__link--active' : '' ?>"<?= $isActivePath('/student/dashboard.php') ? ' aria-current="page"' : '' ?>>Dashboard</a>
                    <a href="<?= htmlspecialchars($basePath) ?>/student/rooms.php"       class="site-nav__link<?= $isActivePath('/student/rooms.php') ? ' site-nav__link--active' : '' ?>"<?= $isActivePath('/student/rooms.php') ? ' aria-current="page"' : '' ?>>Find a Room</a>
                    <a href="<?= htmlspecialchars($basePath) ?>/student/reservations.php" class="site-nav__link<?= $isActivePath('/student/reservations.php') ? ' site-nav__link--active' : '' ?>"<?= $isActivePath('/student/reservations.php') ? ' aria-current="page"' : '' ?>>My Reservations</a>
                <?php endif; ?>
            </nav>

            <!-- Actions -->
            <div class="site-header__actions">
                <!-- User menu -->
                <div class="user-menu" role="navigation" aria-label="User account">
                    <button id="user-menu-toggle"
                            class="user-menu__toggle"
                            type="button"
                            aria-haspopup="true"
                            aria-controls="user-menu-dropdown"
                            aria-expanded="false">
                        <span class="user-menu__avatar" aria-hidden="true"><?= $userInitial ?></span>
                        <span><?= $userName ?></span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </button>
                    <div id="user-menu-dropdown" class="user-menu__dropdown" role="menu">
                        <div style="padding: var(--space-2) var(--space-3); border-bottom: 1px solid var(--color-border); margin-bottom: var(--space-2);">
                            <div style="font-size:var(--font-size-sm);font-weight:600;color:var(--color-heading)"><?= $userName ?></div>
                            <div style="font-size:var(--font-size-xs);color:var(--color-muted)"><?= $userEmail ?></div>
                        </div>
                        <a href="<?= htmlspecialchars($basePath) ?>/profile.php"    class="user-menu__dropdown-item" role="menuitem">My Profile</a>
                        <hr class="user-menu__dropdown-divider">
                        <a href="<?= htmlspecialchars($basePath) ?>/logout.php"     class="user-menu__dropdown-item user-menu__dropdown-item--danger" role="menuitem">Sign out</a>
                    </div>
                </div>

                <!-- Mobile toggle -->
                <button id="mobile-nav-toggle"
                        class="mobile-nav-toggle"
                        type="button"
                        aria-controls="mobile-nav"
                        aria-expanded="false"
                        aria-label="Open navigation menu">
                    <span class="icon-bar" aria-hidden="true"></span>
                    <span class="icon-bar" aria-hidden="true"></span>
                    <span class="icon-bar" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </header>

    <!-- ── Mobile navigation drawer ──────────────────────────── -->
    <div id="mobile-nav" class="mobile-nav" role="dialog" aria-modal="true" aria-label="Navigation menu">
        <div id="mobile-nav-backdrop" class="mobile-nav__backdrop" aria-hidden="true"></div>
        <div class="mobile-nav__drawer">
            <div class="mobile-nav__header">
                <span class="brand-wordmark">In<span>Focus</span></span>
                <button id="mobile-nav-close" class="mobile-nav__close"
                        type="button" aria-label="Close navigation menu">&times;</button>
            </div>
            <nav class="mobile-nav__body" aria-label="Mobile navigation">
                <?php if ($isAdmin): ?>
                    <a href="<?= htmlspecialchars($basePath) ?>/admin/dashboard.php"     class="mobile-nav__link<?= $isActivePath('/admin/dashboard.php') ? ' mobile-nav__link--active' : '' ?>"<?= $isActivePath('/admin/dashboard.php') ? ' aria-current="page"' : '' ?>>Dashboard</a>
                    <a href="<?= htmlspecialchars($basePath) ?>/admin/reservations.php"  class="mobile-nav__link<?= $isActivePath('/admin/reservations.php') ? ' mobile-nav__link--active' : '' ?>"<?= $isActivePath('/admin/reservations.php') ? ' aria-current="page"' : '' ?>>Reservations</a>
                    <a href="<?= htmlspecialchars($basePath) ?>/admin/modifications.php" class="mobile-nav__link<?= $isActivePath('/admin/modifications.php') ? ' mobile-nav__link--active' : '' ?>"<?= $isActivePath('/admin/modifications.php') ? ' aria-current="page"' : '' ?>>Modifications</a>
                    <a href="<?= htmlspecialchars($basePath) ?>/admin/rooms.php"         class="mobile-nav__link<?= $isActivePath('/admin/rooms.php') ? ' mobile-nav__link--active' : '' ?>"<?= $isActivePath('/admin/rooms.php') ? ' aria-current="page"' : '' ?>>Rooms &amp; Floors</a>
                    <a href="<?= htmlspecialchars($basePath) ?>/admin/reports.php"       class="mobile-nav__link<?= $isActivePath('/admin/reports.php') ? ' mobile-nav__link--active' : '' ?>"<?= $isActivePath('/admin/reports.php') ? ' aria-current="page"' : '' ?>>Reports</a>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($basePath) ?>/student/dashboard.php"   class="mobile-nav__link<?= $isActivePath('/student/dashboard.php') ? ' mobile-nav__link--active' : '' ?>"<?= $isActivePath('/student/dashboard.php') ? ' aria-current="page"' : '' ?>>Dashboard</a>
                    <a href="<?= htmlspecialchars($basePath) ?>/student/rooms.php"       class="mobile-nav__link<?= $isActivePath('/student/rooms.php') ? ' mobile-nav__link--active' : '' ?>"<?= $isActivePath('/student/rooms.php') ? ' aria-current="page"' : '' ?>>Find a Room</a>
                    <a href="<?= htmlspecialchars($basePath) ?>/student/reservations.php" class="mobile-nav__link<?= $isActivePath('/student/reservations.php') ? ' mobile-nav__link--active' : '' ?>"<?= $isActivePath('/student/reservations.php') ? ' aria-current="page"' : '' ?>>My Reservations</a>
                <?php endif; ?>
            </nav>
            <div class="mobile-nav__footer">
                <div class="mobile-nav__user-info">
                    <div class="mobile-nav__user-name"><?= $userName ?></div>
                    <div class="mobile-nav__user-email"><?= $userEmail ?></div>
                </div>
                <a href="<?= htmlspecialchars($basePath) ?>/logout.php" class="btn btn--danger btn--block btn--sm">Sign out</a>
            </div>
        </div>
    </div>

    <!-- ── Page content ──────────────────────────────────────── -->
    <main id="main-content" class="page-main" tabindex="-1">
        <div class="page-container">

            <?php if ($pageTitle || $pageSubtitle || $pageActions): ?>
                <div class="page-header<?= $pageActions ? ' page-header--with-actions' : '' ?>">
                    <div>
                        <?php if ($pageTitle): ?>
                            <h1 class="page-header__title"><?= htmlspecialchars($pageTitle) ?></h1>
                        <?php endif; ?>
                        <?php if ($pageSubtitle): ?>
                            <p class="page-header__subtitle"><?= htmlspecialchars($pageSubtitle) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if ($pageActions): ?>
                        <div class="page-header__actions"><?= $pageActions ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?= $content ?? '' ?>
        </div>
    </main>

    <!-- ── Site footer ───────────────────────────────────────── -->
    <footer class="site-footer" role="contentinfo">
        <p>&copy; <?= date('Y') ?> InFocus — Study Room Booking System</p>
    </footer>

</div><!-- .page-shell -->

<script src="<?= htmlspecialchars($basePath) ?>/assets/js/navigation.js" defer></script>
<?= $pageScripts ?>
</body>
</html>
