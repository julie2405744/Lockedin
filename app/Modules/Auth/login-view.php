<?php
/**
 * login-view.php
 * View template for the login page.
 *
 * Expected variables:
 *   $csrfField  (string) — rendered hidden CSRF input
 *   $errors     (array)  — validation/auth error messages
 *   $old        (array)  — old input values for repopulation ['email']
 */

$errors = $errors ?? [];
$old    = $old    ?? [];
$csrfField = $csrfField ?? '';
$basePath = $basePath ?? '';
?>
<div class="auth-shell">

    <!-- ── Left: brand panel ──────────────────────────────────── -->
    <div class="auth-shell__brand-panel" role="presentation">
        <div class="auth-shell__brand-content">
            <div class="auth-brand-wordmark">InFocus</div>
            <h2 class="auth-brand-tagline">Your university<br>study space, organised.</h2>
            <p class="auth-brand-sub">
                Browse available rooms, book a quiet spot, and manage your
                reservations — all in one place.
            </p>
            <div class="auth-brand-features">
                <div class="auth-brand-feature">
                    <svg class="auth-brand-feature__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="6"/><path d="m16 16 4 4"/></svg>
                    <span>Search rooms by date, time, and capacity</span>
                </div>
                <div class="auth-brand-feature">
                    <svg class="auth-brand-feature__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>
                    <span>Book up to seven days in advance</span>
                </div>
                <div class="auth-brand-feature">
                    <svg class="auth-brand-feature__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg>
                    <span>Track reservation status in real time</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Right: login form panel ───────────────────────────── -->
    <div class="auth-shell__panel">
        <div class="auth-card">

            <div class="auth-card__header">
                <p class="auth-card__home">
                    <a href="<?= htmlspecialchars($basePath) ?>/landing.php" class="auth-card__home-link">InFocus</a>
                </p>
                <h1 class="auth-card__title">Sign in to InFocus</h1>
                <p class="auth-card__subtitle">Enter your registered email and password to continue.</p>
            </div>

            <!-- Error alert -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert--danger" role="alert" aria-live="polite">
                    <svg class="alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <div class="alert__body">
                        <?php if (count($errors) === 1): ?>
                            <p><?= htmlspecialchars($errors[0]) ?></p>
                        <?php else: ?>
                            <p class="alert__title">Please fix the following issues:</p>
                            <ul class="alert__list">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Login form -->
            <form class="auth-card__form" method="POST" action="<?= htmlspecialchars($basePath) ?>/login.php" novalidate>
                <?= $csrfField ?>

                <div class="form-group">
                    <label class="form-label form-label--required" for="email">Email address</label>
                    <input
                        class="form-control<?= !empty($errors) ? ' form-control--error' : '' ?>"
                        type="email"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                        placeholder="you@university.edu"
                        required
                        autocomplete="email"
                        aria-required="true"
                        aria-describedby="email-hint"
                    >
                    <span id="email-hint" class="form-hint">The email address you registered with.</span>
                </div>

                <div class="form-group">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <label class="form-label form-label--required" for="password">Password</label>
                    </div>
                    <input
                        class="form-control<?= !empty($errors) ? ' form-control--error' : '' ?>"
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Your password"
                        required
                        autocomplete="current-password"
                        aria-required="true"
                    >
                </div>

                <button class="btn btn--primary btn--block btn--lg" type="submit" id="login-submit">
                    Sign in
                </button>
            </form>

            <div class="auth-card__footer">
                Don't have an account?
                <a href="<?= htmlspecialchars($basePath) ?>/register.php">Create one</a>
            </div>

        </div>
    </div>

</div><!-- .auth-shell -->
