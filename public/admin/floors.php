<?php
/**
 * public/admin/floors.php
 * Floors management list — Member 2 (frontend milestone F1).
 *
 * GET  : list floors with room counts and status badges.
 * POST : activate / deactivate a floor (admin only, CSRF protected).
 *
 * Deactivation NEVER deletes the floor: rooms on it and all
 * historical reservation records stay untouched.
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

/* ── POST: activate / deactivate (POST-redirect-GET) ─────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
        $_SESSION['rooms_flash'] = ['type' => 'danger', 'message' => 'Your session expired. Please refresh and try again.'];
    } else {
        $id     = (int) ($_POST['floor_id'] ?? 0);
        $action = (string) ($_POST['action'] ?? '');
        $floor  = $id > 0 ? $repo->findFloorById($id) : null;

        if ($floor === null) {
            $_SESSION['rooms_flash'] = ['type' => 'danger', 'message' => 'Floor not found — it may already have been changed by another admin.'];
        } elseif ($action === 'deactivate') {
            $repo->setFloorStatus($id, 'inactive');
            $_SESSION['rooms_flash'] = ['type' => 'success', 'message' => 'Floor "' . $floor['name'] . '" deactivated. Rooms and reservation history are preserved.'];
        } elseif ($action === 'activate') {
            $repo->setFloorStatus($id, 'active');
            $_SESSION['rooms_flash'] = ['type' => 'success', 'message' => 'Floor "' . $floor['name'] . '" activated.'];
        } else {
            $_SESSION['rooms_flash'] = ['type' => 'danger', 'message' => 'Unknown action.'];
        }
    }

    header('Location: ' . $basePath . '/admin/floors.php');
    exit;
}

/* ── GET: list ────────────────────────────────────────────────── */
$floors     = $repo->findAllFloors();
$roomCounts = $repo->findRoomCountsByFloor();
$flash      = $_SESSION['rooms_flash'] ?? null;
unset($_SESSION['rooms_flash']);

$pageTitle    = 'Floors';
$pageSubtitle = 'Maintain the building floors that rooms sit on. Deactivating a floor never deletes it — rooms and reservation history are preserved.';
$pageActions  = '<a href="' . esc($basePath) . '/admin/floor-form.php" class="btn btn--primary">'
    . '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>'
    . 'Add floor</a>';

ob_start();
require __DIR__ . '/../../app/Modules/Rooms/Views/floors-view.php';
$content = ob_get_clean();

require __DIR__ . '/../../app/Views/layouts/app-layout.php';
