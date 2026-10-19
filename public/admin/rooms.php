<?php
/**
 * public/admin/rooms.php
 * Rooms management list / "Rooms & Floors" hub — Member 2 (frontend F2).
 *
 * GET  : list rooms with floor, category, capacity, facility counts, status.
 * POST : activate / deactivate a room (admin only, CSRF protected).
 *
 * Deactivation NEVER deletes the room or its floor assignment;
 * historical reservations remain intact.
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
        $id     = (int) ($_POST['room_id'] ?? 0);
        $action = (string) ($_POST['action'] ?? '');
        $room   = $id > 0 ? $repo->findRoomById($id) : null;

        if ($room === null) {
            $_SESSION['rooms_flash'] = ['type' => 'danger', 'message' => 'Room not found — it may already have been changed by another admin.'];
        } elseif ($action === 'deactivate') {
            $repo->setRoomStatus($id, 'inactive');
            $_SESSION['rooms_flash'] = ['type' => 'success', 'message' => 'Room "' . $room['name'] . '" deactivated. It is hidden from the student search, and all reservation history is preserved.'];
        } elseif ($action === 'activate') {
            $repo->setRoomStatus($id, 'active');
            $_SESSION['rooms_flash'] = ['type' => 'success', 'message' => 'Room "' . $room['name'] . '" activated.'];
        } else {
            $_SESSION['rooms_flash'] = ['type' => 'danger', 'message' => 'Unknown action.'];
        }
    }

    header('Location: ' . $basePath . '/admin/rooms.php');
    exit;
}

/* ── GET: list ────────────────────────────────────────────────── */
$rooms           = $repo->findAllRooms();
$facilityCounts  = $repo->findFacilityCountsByRoom();
$flash           = $_SESSION['rooms_flash'] ?? null;
unset($_SESSION['rooms_flash']);

$pageTitle    = 'Rooms & Floors';
$pageSubtitle = 'Create and maintain rooms, their categories and facilities. Deactivating a room never deletes it — reservation history is preserved.';
$pageActions  = '<a href="' . esc($basePath) . '/admin/room-form.php" class="btn btn--primary">'
    . '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>'
    . 'Add room</a>';

ob_start();
require __DIR__ . '/../../app/Modules/Rooms/Views/rooms-view.php';
$content = ob_get_clean();

require __DIR__ . '/../../app/Views/layouts/app-layout.php';
