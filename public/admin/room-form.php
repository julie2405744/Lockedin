<?php
/**
 * public/admin/room-form.php
 * Room create / edit form — Member 2 (frontend F2).
 *
 * GET  : show the form (?id=NN edits an existing room; needs >=1 floor).
 * POST : validate input, then create/update the room and sync its
 *        facilities in one transaction (all-or-nothing).
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Core/Auth.php';
require_once __DIR__ . '/../../app/Core/Csrf.php';
require_once __DIR__ . '/../../app/Core/helpers.php';
require_once __DIR__ . '/../../app/Repositories/RoomRepository.php';

Auth::startSession();
Auth::requireAdmin();

$repo       = new RoomRepository($pdo);
$basePath   = defined('BASE_PATH') ? BASE_PATH : '';
$pageStyles = '<link rel="stylesheet" href="' . esc($basePath) . '/assets/css/room-management.css">';

const ROOM_CATEGORIES = ['individual_study', 'group_study', 'lounge'];

$errors  = [];
$old     = ['floor_id' => '', 'name' => '', 'category' => '', 'description' => '', 'capacity' => ''];
$editing = null;

/* Shared form data (needed in GET and on validation-round-trip). */
$floors     = $repo->findAllFloors();
$facilities = $repo->findAllFacilities();

/* Resolve the target room (edit mode). */
$requestedId = (int) ($_SERVER['REQUEST_METHOD'] === 'POST'
    ? ($_POST['room_id'] ?? 0)
    : ($_GET['id'] ?? 0));

if ($requestedId > 0) {
    $editing = $repo->findRoomById($requestedId);

    if ($editing === null) {
        $_SESSION['rooms_flash'] = ['type' => 'danger', 'message' => 'That room does not exist or was removed.'];
        header('Location: ' . $basePath . '/admin/rooms.php');
        exit;
    }

    $old['floor_id']    = (string) $editing['floor_id'];
    $old['name']        = (string) $editing['name'];
    $old['category']    = (string) $editing['category'];
    $old['description'] = (string) $editing['description'];
    $old['capacity']    = (string) $editing['capacity'];
}

/* Selected facilities — stored ones when editing, posted ones on re-render. */
$selectedFacilityIds = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $editing !== null) {
    foreach ($repo->getRoomFacilities((int) $editing['id']) as $facility) {
        $selectedFacilityIds[] = (int) $facility['id'];
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedIds = $_POST['facilities'] ?? [];
    if (is_array($postedIds)) {
        $selectedFacilityIds = array_map('intval', $postedIds);
    }
}

/* ── POST: validate and save ─────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Your session expired. Please refresh the page and try again.';
    } else {
        $old['floor_id']    = (string) ($_POST['floor_id'] ?? '');
        $old['name']        = trim((string) ($_POST['name'] ?? ''));
        $old['category']    = (string) ($_POST['category'] ?? '');
        $old['description'] = trim((string) ($_POST['description'] ?? ''));
        $old['capacity']    = trim((string) ($_POST['capacity'] ?? ''));

        $floor = $old['floor_id'] !== '' ? $repo->findFloorById((int) $old['floor_id']) : null;
        if ($floor === null) {
            $errors['floor_id'] = 'Choose a valid floor.';
        }

        if ($old['name'] === '') {
            $errors['name'] = 'Room name is required.';
        } elseif (mb_strlen($old['name']) > 120) {
            $errors['name'] = 'Room name must be 120 characters or fewer.';
        }

        if (!in_array($old['category'], ROOM_CATEGORIES, true)) {
            $errors['category'] = 'Choose one of the available room categories.';
        }

        if ($old['capacity'] === '') {
            $errors['capacity'] = 'Capacity is required.';
        } elseif (!ctype_digit($old['capacity']) || (int) $old['capacity'] < 1 || (int) $old['capacity'] > 9999) {
            $errors['capacity'] = 'Capacity must be a whole number between 1 and 9999.';
        }

        if (mb_strlen($old['description']) > 2000) {
            $errors['description'] = 'Description must be 2000 characters or fewer.';
        }

        /* Facility whitelist: every submitted id must be a real facility. */
        $knownFacilityIds = [];
        foreach ($facilities as $facility) {
            $knownFacilityIds[(int) $facility['id']] = true;
        }
        foreach ($selectedFacilityIds as $facilityId) {
            if (!isset($knownFacilityIds[$facilityId])) {
                $errors['facilities'] = 'An unknown facility was selected. Refresh the page and try again.';
                break;
            }
        }

        if ($errors === []) {
            $facilityIds = [];
            foreach ($selectedFacilityIds as $facilityId) {
                $facilityIds[$facilityId] = true; // de-duplicate, keep order stable enough
            }
            $facilityIds = array_keys($facilityIds);

            try {
                $pdo->beginTransaction();

                if ($editing !== null) {
                    $repo->updateRoom((int) $editing['id'], [
                        'floor_id'    => (int) $old['floor_id'],
                        'name'        => $old['name'],
                        'category'    => $old['category'],
                        'description' => $old['description'] !== '' ? $old['description'] : null,
                        'capacity'    => (int) $old['capacity'],
                    ]);
                    $roomId = (int) $editing['id'];
                } else {
                    $roomId = $repo->createRoom([
                        'floor_id'    => (int) $old['floor_id'],
                        'name'        => $old['name'],
                        'category'    => $old['category'],
                        'description' => $old['description'] !== '' ? $old['description'] : null,
                        'capacity'    => (int) $old['capacity'],
                    ]);
                }

                /* Replace the assignment set atomically. */
                $repo->removeAllRoomFacilities($roomId);
                foreach ($facilityIds as $facilityId) {
                    $repo->assignRoomFacility($roomId, $facilityId);
                }

                $pdo->commit();

                $_SESSION['rooms_flash'] = ['type' => 'success', 'message' => 'Room "' . $old['name'] . '" '
                    . ($editing !== null ? 'updated' : 'created and active') . ' with ' . count($facilityIds) . ' facilities.'];

                header('Location: ' . $basePath . '/admin/rooms.php');
                exit;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Room save failed: ' . $e->getMessage());

                if ($e->getCode() === '23000') {
                    $errors['name'] = 'A room with this name already exists on the selected floor.';
                } else {
                    $errors['general'] = 'The room could not be saved. Please try again, and contact the team lead if it keeps failing.';
                }
            }
        }
    }
}

$pageTitle    = $editing === null ? 'New room' : 'Edit room';
$pageSubtitle = $editing === null
    ? 'Add a bookable room. Pick its floor, one of the three categories, and its facilities.'
    : 'Change details, facilities, or capacity. Status is managed from the rooms list.';

ob_start();
require __DIR__ . '/../../app/Modules/Rooms/Views/room-form-view.php';
$content = ob_get_clean();

require __DIR__ . '/../../app/Views/layouts/app-layout.php';
