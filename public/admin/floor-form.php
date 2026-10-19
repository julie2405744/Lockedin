<?php
/**
 * public/admin/floor-form.php
 * Floor create / edit form — Member 2 (frontend milestone F1).
 *
 * GET  : show the form (?id=NN for editing an existing floor).
 * POST : validate input, then create or update via RoomRepository.
 *        The database's unique floor_number constraint is the final
 *        safety net; uniqueness is pre-checked for friendly errors.
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
$old     = ['floor_number' => '', 'name' => '', 'description' => ''];
$editing = null;

/* Resolve the target floor (edit mode) from GET or POST. */
$requestedId = (int) ($_SERVER['REQUEST_METHOD'] === 'POST'
    ? ($_POST['floor_id'] ?? 0)
    : ($_GET['id'] ?? 0));

if ($requestedId > 0) {
    $editing = $repo->findFloorById($requestedId);

    if ($editing === null) {
        $_SESSION['rooms_flash'] = ['type' => 'danger', 'message' => 'That floor does not exist or was removed.'];
        header('Location: ' . $basePath . '/admin/floors.php');
        exit;
    }

    $old['floor_number'] = (string) $editing['floor_number'];
    $old['name']         = (string) $editing['name'];
    $old['description']  = (string) $editing['description'];
}

/* ── POST: validate and save ─────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Your session expired. Please refresh the page and try again.';
    } else {
        $old['floor_number'] = trim((string) ($_POST['floor_number'] ?? ''));
        $old['name']         = trim((string) ($_POST['name'] ?? ''));
        $old['description']  = trim((string) ($_POST['description'] ?? ''));

        if ($old['floor_number'] === '') {
            $errors['floor_number'] = 'Floor number is required.';
        } elseif (!ctype_digit($old['floor_number']) || (int) $old['floor_number'] < 1 || (int) $old['floor_number'] > 999) {
            $errors['floor_number'] = 'Enter a whole number between 1 and 999.';
        } else {
            $existing = $repo->findFloorByNumber((int) $old['floor_number']);
            if ($existing !== null && (!$editing || (int) $existing['id'] !== (int) $editing['id'])) {
                $errors['floor_number'] = 'Floor number ' . $old['floor_number'] . ' is already in use by "' . $existing['name'] . '".';
            }
        }

        if ($old['name'] === '') {
            $errors['name'] = 'Floor name is required.';
        } elseif (mb_strlen($old['name']) > 100) {
            $errors['name'] = 'Floor name must be 100 characters or fewer.';
        }

        if (mb_strlen($old['description']) > 1000) {
            $errors['description'] = 'Description must be 1000 characters or fewer.';
        }

        if ($errors === []) {
            try {
                if ($editing === null) {
                    $repo->createFloor([
                        'floor_number' => (int) $old['floor_number'],
                        'name'         => $old['name'],
                        'description'  => $old['description'] !== '' ? $old['description'] : null,
                        'status'       => 'active',
                    ]);
                    $_SESSION['rooms_flash'] = ['type' => 'success', 'message' => 'Floor "' . $old['name'] . '" created and active.'];
                } else {
                    $repo->updateFloor((int) $editing['id'], [
                        'floor_number' => (int) $old['floor_number'],
                        'name'         => $old['name'],
                        'description'  => $old['description'] !== '' ? $old['description'] : null,
                    ]);
                    $_SESSION['rooms_flash'] = ['type' => 'success', 'message' => 'Floor "' . $old['name'] . '" updated.'];
                }

                header('Location: ' . $basePath . '/admin/floors.php');
                exit;
            } catch (PDOException $e) {
                error_log('Floor save failed: ' . $e->getMessage());
                $errors['general'] = 'The floor could not be saved. If you changed the floor number, another floor may already use it.';
            }
        }
    }
}

$pageTitle    = $editing === null ? 'New floor' : 'Edit floor';
$pageSubtitle = $editing === null
    ? 'Add a level of the building. Rooms belong to exactly one floor.'
    : 'Change the details of ' . $editing['name'] . '. Renumbering is allowed while the new number is not in use.';

ob_start();
require __DIR__ . '/../../app/Modules/Rooms/Views/floor-form-view.php';
$content = ob_get_clean();

require __DIR__ . '/../../app/Views/layouts/app-layout.php';
