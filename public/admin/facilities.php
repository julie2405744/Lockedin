<?php
/**
 * public/admin/facilities.php
 * Facilities management list — Member 2 (frontend F3).
 *
 * GET  : list facilities with room-usage counts.
 * POST : remove a facility (admin only, CSRF protected). The server
 *        refuses while any room still uses the facility, and the
 *        UI hides the Remove button for used facilities anyway.
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

/* ── POST: remove a facility (POST-redirect-GET) ─────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
        $_SESSION['rooms_flash'] = ['type' => 'danger', 'message' => 'Your session expired. Please refresh and try again.'];
    } else {
        $id       = (int) ($_POST['facility_id'] ?? 0);
        $action   = (string) ($_POST['action'] ?? '');
        $facility = $id > 0 ? $repo->findFacilityById($id) : null;

        if ($facility === null) {
            $_SESSION['rooms_flash'] = ['type' => 'danger', 'message' => 'Facility not found — it may already have been removed by another admin.'];
        } elseif ($action !== 'remove') {
            $_SESSION['rooms_flash'] = ['type' => 'danger', 'message' => 'Unknown action.'];
        } else {
            $roomsUsing = $repo->findRoomCountsByFacility()[$id] ?? 0;

            if ($roomsUsing > 0) {
                $_SESSION['rooms_flash'] = ['type' => 'danger', 'message' => 'Facility "' . $facility['name'] . '" is still attached to ' . $roomsUsing . ' ' . ($roomsUsing === 1 ? 'room' : 'rooms') . '. Remove it from those rooms first.'];
            } else {
                try {
                    $repo->deleteFacility($id);
                    $_SESSION['rooms_flash'] = ['type' => 'success', 'message' => 'Facility "' . $facility['name'] . '" removed.'];
                } catch (PDOException $e) {
                    error_log('Facility delete failed: ' . $e->getMessage());
                    $_SESSION['rooms_flash'] = ['type' => 'danger', 'message' => 'Facility "' . $facility['name'] . '" could not be removed — it is in use by a room.'];
                }
            }
        }
    }

    header('Location: ' . $basePath . '/admin/facilities.php');
    exit;
}

/* ── GET: list ────────────────────────────────────────────────── */
$facilities = $repo->findAllFacilities();
$roomCounts = $repo->findRoomCountsByFacility();
$flash      = $_SESSION['rooms_flash'] ?? null;
unset($_SESSION['rooms_flash']);

$pageTitle    = 'Facilities';
$pageSubtitle = 'The amenities students can filter rooms by. Facilities still attached to rooms cannot be removed.';
$pageActions  = '<a href="' . esc($basePath) . '/admin/facility-form.php" class="btn btn--primary">'
    . '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>'
    . 'Add facility</a>';

ob_start();
require __DIR__ . '/../../app/Modules/Rooms/Views/facilities-view.php';
$content = ob_get_clean();

require __DIR__ . '/../../app/Views/layouts/app-layout.php';
