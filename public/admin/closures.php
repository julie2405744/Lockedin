<?php
/**
 * public/admin/closures.php
 * Closures / maintenance periods — Member 2 (frontend F5).
 *
 * GET only. Lists room_unavailability rows — the UTC DATETIME
 * periods during which a room cannot be booked. Display converts
 * every stored value to Africa/Cairo local time via the shared
 * formatLocalTime() helper.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Core/Auth.php';
require_once __DIR__ . '/../../app/Core/Csrf.php';
require_once __DIR__ . '/../../app/Core/helpers.php';
require_once __DIR__ . '/../../app/Repositories/RoomRepository.php';

Auth::startSession();
Auth::requireAdmin();

$currentUser = Auth::user();
$repo        = new RoomRepository($pdo);
$basePath    = defined('BASE_PATH') ? BASE_PATH : '';
$pageStyles  = '<link rel="stylesheet" href="' . esc($basePath) . '/assets/css/room-management.css">';
$pageScripts = '<script src="' . esc($basePath) . '/assets/js/room-management.js" defer></script>';

/* ── POST: remove a closure (POST-redirect-GET) ──────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
        $_SESSION['rooms_flash'] = ['type' => 'danger', 'message' => 'Your session expired. Please refresh and try again.'];
    } else {
        $id      = (int) ($_POST['closure_id'] ?? 0);
        $action  = (string) ($_POST['action'] ?? '');
        $row     = null;

        if ($id > 0) {
            foreach ($repo->getAllUnavailability() as $u) {
                if ((int) $u['id'] === $id) {
                    $row = $u;
                    break;
                }
            }
        }

        if ($action !== 'remove') {
            $_SESSION['rooms_flash'] = ['type' => 'danger', 'message' => 'Unknown action.'];
        } elseif ($row === null) {
            $_SESSION['rooms_flash'] = ['type' => 'danger', 'message' => 'Closure not found — it may already have been removed by another admin.'];
        } else {
            try {
                $repo->deleteUnavailability($id);
                $_SESSION['rooms_flash'] = ['type' => 'success', 'message' => 'Closure "' . $row['reason'] . '" removed. Students can book the room during that period again.'];
            } catch (PDOException $e) {
                error_log('Closure delete failed: ' . $e->getMessage());
                $_SESSION['rooms_flash'] = ['type' => 'danger', 'message' => 'The closure could not be removed. Please try again.'];
            }
        }
    }

    header('Location: ' . $basePath . '/admin/closures.php');
    exit;
}

/* ── GET: list ────────────────────────────────────────────────── */
$rooms      = $repo->findAllRooms();
$closures   = $repo->getAllUnavailability();
$flash      = $_SESSION['rooms_flash'] ?? null;
unset($_SESSION['rooms_flash']);

$pageTitle    = 'Closures & maintenance';
$pageSubtitle = 'Periods during which rooms cannot be booked. Dates are entered in Cairo local time and stored in UTC.';
$pageActions  = '<a href="' . esc($basePath) . '/admin/closure-form.php" class="btn btn--primary">'
    . '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>'
    . 'Add closure</a>';

ob_start();
require __DIR__ . '/../../app/Modules/Rooms/Views/closures-view.php';
$content = ob_get_clean();

require __DIR__ . '/../../app/Views/layouts/app-layout.php';
