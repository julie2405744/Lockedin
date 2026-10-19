<?php
/**
 * public/profile.php
 * Basic profile page — Member 1.
 * Students may update their name. Password update is also available.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Core/Auth.php';
require_once __DIR__ . '/../app/Core/Csrf.php';
require_once __DIR__ . '/../app/Services/AuthService.php';

Auth::startSession();
Auth::requireLogin();

$currentUser = Auth::user();
$authService = new AuthService($pdo);
$errors      = [];
$success     = '';
$old         = ['name' => $currentUser['name']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Session expired. Please refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_name') {
            $name = trim($_POST['name'] ?? '');
            $old  = ['name' => $name];

            if ($name === '') {
                $errors['name'] = 'Name is required.';
            } elseif (mb_strlen($name) > 120) {
                $errors['name'] = 'Name must be 120 characters or fewer.';
            } else {
                // Direct repo update for name — no sensitive logic
                require_once __DIR__ . '/../app/Repositories/UserRepository.php';
                $repo = new UserRepository($pdo);
                if ($repo->updateName(Auth::id(), $name)) {
                    // Refresh session
                    Auth::setUser(array_merge($currentUser, ['name' => $name]));
                    $currentUser = Auth::user();
                    $old         = ['name' => $name];
                    $success     = 'Your name has been updated.';
                } else {
                    $errors['general'] = 'Update failed. Please try again.';
                }
            }

        } elseif ($action === 'change_password') {
            $current  = $_POST['current_password']  ?? '';
            $new      = $_POST['new_password']       ?? '';
            $confirm  = $_POST['confirm_password']   ?? '';

            if ($current === '') {
                $errors['current_password'] = 'Current password is required.';
            }
            if ($new === '') {
                $errors['new_password'] = 'New password is required.';
            } elseif (strlen($new) < 8) {
                $errors['new_password'] = 'Password must be at least 8 characters.';
            }
            if ($confirm === '') {
                $errors['confirm_password'] = 'Please confirm your new password.';
            } elseif ($new !== $confirm) {
                $errors['confirm_password'] = 'Passwords do not match.';
            }

            if (empty($errors)) {
                require_once __DIR__ . '/../app/Repositories/UserRepository.php';
                $repo = new UserRepository($pdo);
                $user = $repo->findByEmail($currentUser['email']);

                if (!$user || !password_verify($current, $user['password_hash'])) {
                    $errors['current_password'] = 'Current password is incorrect.';
                } else {
                    $hash = password_hash($new, PASSWORD_BCRYPT);
                    if ($repo->updatePassword(Auth::id(), $hash)) {
                        $success = 'Your password has been changed.';
                    } else {
                        $errors['general'] = 'Password update failed. Please try again.';
                    }
                }
            }
        }
    }
}

$csrfField = Csrf::field();
$pageTitle = 'My Profile';
$basePath = defined('BASE_PATH') ? BASE_PATH : '';

ob_start();
?>

<?php if ($success): ?>
    <div class="alert alert--success" role="alert" data-auto-dismiss="5000">
        <svg class="alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
        <div class="alert__body"><p><?= htmlspecialchars($success) ?></p></div>
    </div>
<?php endif; ?>

<?php if (!empty($errors['general'])): ?>
    <div class="alert alert--danger" role="alert">
        <div class="alert__body"><p><?= htmlspecialchars($errors['general']) ?></p></div>
    </div>
<?php endif; ?>

<div class="profile-grid">

    <!-- Update name -->
    <div class="panel">
        <div class="panel__header">
            <h2 class="panel__title">Personal information</h2>
        </div>
        <div class="panel__body">
            <form method="POST" action="<?= htmlspecialchars($basePath) ?>/profile.php" novalidate>
                <?= $csrfField ?>
                <input type="hidden" name="action" value="update_name">
                <div class="form-stack">
                    <div class="form-group">
                        <label class="form-label form-label--required" for="name">Full name</label>
                        <input class="form-control<?= isset($errors['name']) ? ' form-control--error' : '' ?>"
                               type="text" id="name" name="name"
                               value="<?= htmlspecialchars($old['name'] ?? $currentUser['name']) ?>"
                               required maxlength="120">
                        <?php if (isset($errors['name'])): ?>
                            <span class="form-error"><?= htmlspecialchars($errors['name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email address</label>
                        <input class="form-control" type="email"
                               value="<?= htmlspecialchars($currentUser['email']) ?>" disabled>
                        <span class="form-hint">Email address cannot be changed.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <div>
                            <span class="badge badge--<?= $currentUser['role'] === 'admin' ? 'admin' : 'student' ?>">
                                <?= ucfirst(htmlspecialchars($currentUser['role'])) ?>
                            </span>
                        </div>
                    </div>
                    <div>
                        <button class="btn btn--primary" type="submit">Save changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Change password -->
    <div class="panel">
        <div class="panel__header">
            <h2 class="panel__title">Change password</h2>
        </div>
        <div class="panel__body">
            <form method="POST" action="<?= htmlspecialchars($basePath) ?>/profile.php" novalidate>
                <?= $csrfField ?>
                <input type="hidden" name="action" value="change_password">
                <div class="form-stack">
                    <div class="form-group">
                        <label class="form-label form-label--required" for="current_password">Current password</label>
                        <input class="form-control<?= isset($errors['current_password']) ? ' form-control--error' : '' ?>"
                               type="password" id="current_password" name="current_password"
                               autocomplete="current-password" required>
                        <?php if (isset($errors['current_password'])): ?>
                            <span class="form-error"><?= htmlspecialchars($errors['current_password']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label form-label--required" for="new_password">New password</label>
                        <input class="form-control<?= isset($errors['new_password']) ? ' form-control--error' : '' ?>"
                               type="password" id="new_password" name="new_password"
                               autocomplete="new-password" required minlength="8">
                        <span class="form-hint">Minimum 8 characters.</span>
                        <?php if (isset($errors['new_password'])): ?>
                            <span class="form-error"><?= htmlspecialchars($errors['new_password']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label form-label--required" for="confirm_password">Confirm new password</label>
                        <input class="form-control<?= isset($errors['confirm_password']) ? ' form-control--error' : '' ?>"
                               type="password" id="confirm_password" name="confirm_password"
                               autocomplete="new-password" required>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <span class="form-error"><?= htmlspecialchars($errors['confirm_password']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <button class="btn btn--secondary" type="submit">Change password</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../app/Views/layouts/app-layout.php';
