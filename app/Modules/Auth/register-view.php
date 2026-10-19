<?php
/**
 * register-view.php
 * View template for the student registration page.
 *
 * Expected variables:
 *   $csrfField  (string) — rendered hidden CSRF input
 *   $errors     (array)  — keyed or flat validation error messages
 *   $old        (array)  — old input values ['name', 'email']
 */

$errors = $errors ?? [];
$old    = $old    ?? [];
$csrfField = $csrfField ?? '';
$basePath = $basePath ?? '';

$fieldErrorClass = static fn(string $field): string => isset($errors[$field]) ? ' form-control--error' : '';
$fieldErrorMessage = static function (string $field) use ($errors): string {
    return isset($errors[$field])
        ? '<span class="form-error" id="' . $field . '-error">' . htmlspecialchars($errors[$field]) . '</span>'
        : '';
};
?>
<div class="auth-shell">

    <!-- ── Left: brand panel ──────────────────────────────────── -->
    <div class="auth-shell__brand-panel" role="presentation">
        <div class="auth-shell__brand-content">
            <div class="auth-brand-wordmark">InFocus</div>
            <h2 class="auth-brand-tagline">Join thousands of<br>students studying smarter.</h2>
            <p class="auth-brand-sub">
                Create your free account to start booking study rooms,
                track your reservations, and stay organised throughout the semester.
            </p>
            <div class="auth-brand-features">
                <div class="auth-brand-feature">
                    <svg class="auth-brand-feature__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 21h18M5 21V10l7-5 7 5v11M9 21v-5h6v5M8 12h.01M16 12h.01"/></svg>
                    <span>Individual, group, and lounge rooms available</span>
                </div>
                <div class="auth-brand-feature">
                    <svg class="auth-brand-feature__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m13 2-9 12h7l-1 8 10-13h-7z"/></svg>
                    <span>Instant booking requests, admin-approved</span>
                </div>
                <div class="auth-brand-feature">
                    <svg class="auth-brand-feature__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V2h6v2M9 10h6M9 14h6M9 18h4"/></svg>
                    <span>Full reservation history and status tracking</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Right: registration form panel ────────────────────── -->
    <div class="auth-shell__panel">
        <div class="auth-card">

            <div class="auth-card__header">
                <p class="auth-card__home">
                    <a href="<?= htmlspecialchars($basePath) ?>/landing.php" class="auth-card__home-link">InFocus</a>
                </p>
                <h1 class="auth-card__title">Create your account</h1>
                <p class="auth-card__subtitle">Student accounts only. Admin access is granted separately.</p>
            </div>

            <!-- General error alert (non-field errors) -->
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert--danger" role="alert" aria-live="polite">
                    <svg class="alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <div class="alert__body">
                        <p><?= htmlspecialchars($errors['general']) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Registration form -->
            <form class="auth-card__form" method="POST" action="<?= htmlspecialchars($basePath) ?>/register.php" novalidate>
                <?= $csrfField ?>

                <!-- Full name -->
                <div class="form-group">
                    <label class="form-label form-label--required" for="name">Full name</label>
                    <input
                        class="form-control<?= $fieldErrorClass('name') ?>"
                        type="text"
                        id="name"
                        name="name"
                        value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                        placeholder="First and last name"
                        required
                        autocomplete="name"
                        aria-required="true"
                        aria-describedby="name-error"
                        maxlength="120"
                    >
                    <?= $fieldErrorMessage('name') ?>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label class="form-label form-label--required" for="email">University email address</label>
                    <input
                        class="form-control<?= $fieldErrorClass('email') ?>"
                        type="email"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                        placeholder="you@university.edu"
                        required
                        autocomplete="email"
                        aria-required="true"
                        aria-describedby="email-error"
                        maxlength="190"
                    >
                    <?= $fieldErrorMessage('email') ?>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label class="form-label form-label--required" for="password">Password</label>
                    <input
                        class="form-control<?= $fieldErrorClass('password') ?>"
                        type="password"
                        id="password"
                        name="password"
                        placeholder="At least 8 characters"
                        required
                        autocomplete="new-password"
                        aria-required="true"
                        aria-describedby="password-hint password-error"
                        minlength="8"
                    >
                    <span id="password-hint" class="form-hint">Minimum 8 characters.</span>
                    <?= $fieldErrorMessage('password') ?>
                </div>

                <!-- Confirm password -->
                <div class="form-group">
                    <label class="form-label form-label--required" for="password_confirm">Confirm password</label>
                    <input
                        class="form-control<?= $fieldErrorClass('password_confirm') ?>"
                        type="password"
                        id="password_confirm"
                        name="password_confirm"
                        placeholder="Repeat your password"
                        required
                        autocomplete="new-password"
                        aria-required="true"
                        aria-describedby="password_confirm-error"
                    >
                    <?= $fieldErrorMessage('password_confirm') ?>
                </div>

                <!-- Security: role must not be submitted by students -->
                <!-- No role field — backend always assigns 'student' on public registration -->

                <button class="btn btn--primary btn--block btn--lg" type="submit" id="register-submit">
                    Create account
                </button>
            </form>

            <div class="auth-card__footer">
                Already have an account?
                <a href="<?= htmlspecialchars($basePath) ?>/login.php">Sign in</a>
            </div>

        </div>
    </div>

</div><!-- .auth-shell -->
