<?php
/**
 * public/admin/facility-form.php
 * Facility create / edit form — Member 2 (frontend F3).
 *
 * GET  : show the form (?id=NN edits an existing facility).
 * POST : validate input, then create or update via RoomRepository.
 *        The unique facility name constraint is the final safety net;
 *        it is pre-checked here for a friendly error message.
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

$errors  = [];
$old     = ['name' => '', 'description' => ''];
$editing = null;

/* Resolve the target facility (edit mode). */
$requestedId = (int) ($_SERVER['REQUEST_METHOD'] === 'POST'
    ? ($_POST['facility_id'] ?? 0)
    : ($_GET['id'] ?? 0));

if ($requestedId > 0) {
    $editing = $repo->findFacilityById($requestedId);

    if ($editing === null) {
        $_SESSION['rooms_flash'] = ['type' => 'danger', 'message' => 'That facility does not exist or was removed.'];
        header('Location: ' . $basePath . '/admin/facilities.php');
        exit;
    }

    $old['name']        = (string) $editing['name'];
    $old['description'] = (string) $editing['description'];
}

/* ── POST: validate and save ─────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Your session expired. Please refresh the page and try again.';
    } else {
        $old['name']        = trim((string) ($_POST['name'] ?? ''));
        $old['description'] = trim((string) ($_POST['description'] ?? ''));

        if ($old['name'] === '') {
            $errors['name'] = 'Facility name is required.';
        } elseif (mb_strlen($old['name']) > 100) {
            $errors['name'] = 'Facility name must be 100 characters or fewer.';
        } else {
            $existing = $repo->findFacilityByName($old['name']);
            if ($existing !== null && (!$editing || (int) $existing['id'] !== (int) $editing['id'])) {
                $errors['name'] = 'A facility called "' . $old['name'] . '" already exists.';
            }
        }

        if (mb_strlen($old['description']) > 255) {
            $errors['description'] = 'Description must be 255 characters or fewer.';
        }

        if ($errors === []) {
            try {
                if ($editing === null) {
                    $repo->createFacility([
                        'name'        => $old['name'],
                        'description' => $old['description'] !== '' ? $old['description'] : null,
                    ]);
                    $_SESSION['rooms_flash'] = ['type' => 'success', 'message' => 'Facility "' . $old['name'] . '" created. It is now selectable on the room form.'];
                } else {
                    $repo->updateFacility((int) $editing['id'], [
                        'name'        => $old['name'],
                        'description' => $old['description'] !== '' ? $old['description'] : null,
                    ]);
                    $_SESSION['rooms_flash'] = ['type' => 'success', 'message' => 'Facility "' . $old['name'] . '" updated.'];
                }

                header('Location: ' . $basePath . '/admin/facilities.php');
                exit;
            } catch (PDOException $e) {
                error_log('Facility save failed: ' . $e->getMessage());

                if ($e->getCode() === '23000') {
                    $errors['name'] = 'A facility with this name already exists.';
                } else {
                    $errors['general'] = 'The facility could not be saved. Please try again, and contact the team lead if it keeps failing.';
                }
            }
        }
    }
}

$pageTitle    = $editing === null ? 'New facility' : 'Edit facility';
$pageSubtitle = $editing === null
    ? 'Add an amenity students can filter by, e.g. Wi-Fi, whiteboard, projector.'
    : 'Change the details of "' . $editing['name'] . '".';
$pageActions  = '<a href="' . esc($basePath) . '/admin/facilities.php" class="btn btn--secondary">Back to facilities</a>';

ob_start();
require __DIR__ . '/../../app/Modules/Rooms/Views/facility-form-view.php';
$content = ob_get_clean();

require __DIR__ . '/../../app/Views/layouts/app-layout.php';
