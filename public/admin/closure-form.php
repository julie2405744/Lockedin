<?php
/**
 * public/admin/closure-form.php
 * Closure create / edit form — Member 2 (frontend F5).
 *
 * GET  : show the form (?id=NN edits an existing closure).
 * POST : validate input, convert local Africa/Cairo datetimes to
 *        UTC with the shared toUtc() helper, then create or update
 *        via RoomRepository. The database's starts_at < ends_at
 *        CHECK constraint is the final safety net.
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

$currentUser = Auth::user();
$errors      = [];
$old         = ['room_id' => '', 'starts_local' => '', 'ends_local' => '', 'reason' => ''];
$editing     = null;
$closureId   = 0;

/* Every room can receive a closure; inactive rooms are labeled. */
$rooms = $repo->findAllRooms();

/* ── Resolve the target closure (edit mode) ──────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $closureId = (int) ($_POST['closure_id'] ?? 0);
} else {
    $closureId = (int) ($_GET['id'] ?? 0);
}

if ($closureId > 0) {
    $editing = null;
    foreach ($repo->getAllUnavailability() as $u) {
        if ((int) $u['id'] === $closureId) {
            $editing = $u;
            break;
        }
    }

    if ($editing === null) {
        $_SESSION['rooms_flash'] = ['type' => 'danger', 'message' => 'That closure does not exist or was removed.'];
        header('Location: ' . $basePath . '/admin/closures.php');
        exit;
    }

    /* Stored UTC -> local Cairo for the datetime-local inputs. */
    $tryLocal = new DateTimeImmutable($editing['starts_at'], new DateTimeZone('UTC'));
    $old['room_id']      = (string) $editing['room_id'];
    $old['starts_local'] = $tryLocal->setTimezone(new DateTimeZone('Africa/Cairo'))->format('Y-m-d\TH:i');
    $old['ends_local']   = (new DateTimeImmutable($editing['ends_at'], new DateTimeZone('UTC')))
        ->setTimezone(new DateTimeZone('Africa/Cairo'))->format('Y-m-d\TH:i');
    $old['reason']       = (string) $editing['reason'];
}

/** 'YYYY-MM-DDTHH:MM(:SS)' -> 'Y-m-d H:i:s', or null. */
$normalizeLocal = function (string $value): ?string {
    $value = str_replace('T', ' ', trim($value));
    if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $value)) {
        return null;
    }
    return $value;
};

/* ── POST: validate and save ─────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Your session expired. Please refresh the page and try again.';
    } else {
        $old['room_id']      = (string) ($_POST['room_id'] ?? '');
        $old['starts_local'] = trim((string) ($_POST['starts_local'] ?? ''));
        $old['ends_local']   = trim((string) ($_POST['ends_local'] ?? ''));
        $old['reason']       = trim((string) ($_POST['reason'] ?? ''));

        $room = $old['room_id'] !== '' ? $repo->findRoomById((int) $old['room_id']) : null;
        if ($room === null) {
            $errors['room_id'] = 'Choose a valid room.';
        }

        $startsLocal = $old['starts_local'] !== '' ? $normalizeLocal($old['starts_local']) : null;
        $endsLocal   = $old['ends_local']   !== '' ? $normalizeLocal($old['ends_local'])   : null;

        if ($startsLocal === null) {
            $errors['starts_local'] = 'Enter a valid start date and time.';
        }
        if ($endsLocal === null) {
            $errors['ends_local'] = 'Enter a valid end date and time.';
        }

        if ($startsLocal !== null && $endsLocal !== null && $startsLocal >= $endsLocal) {
            $errors['ends_local'] = 'The end must come after the start.';
        }

        if ($old['reason'] === '') {
            $errors['reason'] = 'A reason is required (students see it).';
        } elseif (mb_strlen($old['reason']) > 255) {
            $errors['reason'] = 'The reason must be 255 characters or fewer.';
        }

        if ($errors === []) {
            try {
                if ($editing !== null) {
                    $repo->updateUnavailability($closureId, [
                        'starts_at' => toUtc($startsLocal),
                        'ends_at'   => toUtc($endsLocal),
                        'reason'    => $old['reason'],
                    ], (int) $currentUser['id']);
                    $_SESSION['rooms_flash'] = ['type' => 'success', 'message' => 'Closure updated for "' . $room['name'] . '".'];
                } else {
                    $repo->createUnavailability([
                        'room_id'    => (int) $old['room_id'],
                        'starts_at'  => toUtc($startsLocal),
                        'ends_at'    => toUtc($endsLocal),
                        'reason'     => $old['reason'],
                        'created_by' => (int) $currentUser['id'],
                    ]);
                    $_SESSION['rooms_flash'] = ['type' => 'success', 'message' => 'Closure added for "' . $room['name'] . '". Students are blocked from booking during that period.'];
                }

                header('Location: ' . $basePath . '/admin/closures.php');
                exit;
            } catch (PDOException $e) {
                error_log('Closure save failed: ' . $e->getMessage());

                if ($e->getCode() === '23000') {
                    $errors['general'] = 'The closure could not be saved — start and end must be different.';
                } else {
                    $errors['general'] = 'The closure could not be saved. Please try again, and contact the team lead if it keeps failing.';
                }
            }
        }
    }
}

$pageTitle    = $editing === null ? 'New closure' : 'Edit closure';
$pageSubtitle = $editing === null
    ? 'Block bookings for one room during a maintenance window or building closure.'
    : 'Change the period or reason. Surrounding history is preserved.';
$pageActions  = '<a href="' . esc($basePath) . '/admin/closures.php" class="btn btn--secondary">Back to closures</a>';

ob_start();
require __DIR__ . '/../../app/Modules/Rooms/Views/closure-form-view.php';
$content = ob_get_clean();

require __DIR__ . '/../../app/Views/layouts/app-layout.php';
