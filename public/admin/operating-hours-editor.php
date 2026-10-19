<?php
/**
 * public/admin/operating-hours-editor.php
 * Operating hours editor for ONE room — Member 2 (frontend F4).
 *
 * GET  (?room_id=NN): show the weekly editor, stored intervals loaded.
 * POST: validate the submitted intervals (time format, opens < closes,
 *       no overlaps inside a day, adjacent intervals allowed), then
 *       replace the room's whole schedule in ONE transaction.
 *       Invalid submissions re-render with values and day errors kept.
 *
 * Conventions (database.sql): day_of_week 0 = Sunday .. 6 = Saturday;
 * TIME values are local Africa/Cairo time; no overnight intervals.
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
$pageScripts = '<script src="' . esc($basePath) . '/assets/js/room-management.js" defer></script>';

$dayNames = [0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];

/** 'H:MM' / 'HH:MM(:SS)' → 'HH:MM:00' or null. */
$normalizeTime = function (string $value): ?string {
    $value = trim($value);
    if (!preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $value, $m)) {
        return null;
    }
    $minutes = ($m[1] * 60) + $m[2];
    if ($minutes === 0) {
        return '00:00:00';
    }
    if ($minutes < 0 || $minutes > (23 * 60) + 59) {
        return null;
    }
    return str_pad($m[1], 2, '0', STR_PAD_LEFT) . ':' . $m[2] . ':00';
};

/* Resolve the room. */
$roomId = (int) ($_SERVER['REQUEST_METHOD'] === 'POST'
    ? ($_POST['room_id'] ?? 0)
    : ($_GET['room_id'] ?? 0));

if ($roomId <= 0) {
    $_SESSION['rooms_flash'] = ['type' => 'danger', 'message' => 'Choose a room first, then edit its schedule.'];
    header('Location: ' . $basePath . '/admin/operating-hours.php');
    exit;
}

$room = $repo->findRoomById($roomId);
if ($room === null) {
    $_SESSION['rooms_flash'] = ['type' => 'danger', 'message' => 'That room does not exist or was removed.'];
    header('Location: ' . $basePath . '/admin/operating-hours.php');
    exit;
}

/* findRoomById() does not join floors — fetch floor info for the header. */
$floorInfo            = $repo->findFloorById((int) $room['floor_id']);
$room['floor_name']   = $floorInfo !== null ? (string) $floorInfo['name'] : '';
$room['floor_number'] = $floorInfo !== null ? (int) $floorInfo['floor_number'] : 0;

/**
 * $rowsByDay[day] = [ ['opens' => 'HH:MM', 'closes' => 'HH:MM'], ... ]
 * Gets rebuilt from storage (GET) or from the submission (POST errors).
 */
$rowsByDay  = [];
$dayErrors  = [];
$errors     = [];
$savedCount = null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    foreach ($repo->getOperatingHours($roomId) as $interval) {
        $rowsByDay[(int) $interval['day_of_week']][] = [
            'opens'  => substr($interval['opens_at'], 0, 5),
            'closes' => substr($interval['closes_at'], 0, 5),
        ];
    }
}

/* ── POST: validate and save (replace-in-transaction) ────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Your session expired. Please refresh the page and try again.';
    }

    if (!isset($errors['general'])) {
        $posted  = $_POST['intervals'] ?? [];
        $isDay   = static fn ($day) => array_key_exists($day, $dayNames);

        /* 1. Shape + pair up the submitted rows. */
        if (!is_array($posted) || $posted === []) {
            /* No days submitted — the whole schedule becomes empty. Valid,
               but confirm it is on purpose (UI confirm covers this). */
            $rowsByDay = [];
        } else {
            foreach ($posted as $day => $dayData) {
                if (!$isDay($day) || !is_array($dayData)) {
                    $errors['general'] = 'A submitted schedule is not a valid weekday — reload the page and try again.';
                    continue;
                }
                $opensArr  = is_array($dayData['opens_at'] ?? null) ? $dayData['opens_at'] : [];
                $closesArr = is_array($dayData['closes_at'] ?? null) ? $dayData['closes_at'] : [];

                if (count($opensArr) !== count($closesArr)) {
                    $dayErrors[$day] = 'A row is missing its start or end time — check all rows on this day.';
                }

                $rows = [];
                $count = min(count($opensArr), count($closesArr));
                for ($i = 0; $i < $count; $i++) {
                    $opens  = $normalizeTime((string) $opensArr[$i]);
                    $closes = $normalizeTime((string) $closesArr[$i]);

                    if ($opens === null || $closes === null) {
                        $dayErrors[$day] = 'One of the times on this day is not a valid 24-hour time (e.g. 08:30).';
                        $rows[] = ['opens' => trim((string) $opensArr[$i]), 'closes' => trim((string) $closesArr[$i])];
                        continue;
                    }
                    if ($opens >= $closes) {
                        $dayErrors[$day] = 'Every interval\'s end time must be after its start time on ' . $dayNames[(int) $day] . '.';
                        $rows[] = ['opens' => substr($opens, 0, 5), 'closes' => substr($closes, 0, 5)];
                        continue;
                    }
                    $rows[] = ['opens' => substr($opens, 0, 5), 'closes' => substr($closes, 0, 5)];
                }

                /* 2. Overlap check inside the day (touching edges allowed). */
                if (!isset($dayErrors[$day]) && $rows !== []) {
                    $sorted = $rows;
                    usort($sorted, static fn ($a, $b) => strcmp($a['opens'], $b['opens']));
                    for ($i = 1; $i < count($sorted); $i++) {
                        if ($sorted[$i]['opens'] < $sorted[$i - 1]['closes']) {
                            $dayErrors[$day] = 'Intervals overlap on ' . $dayNames[(int) $day] . ': '
                                . $sorted[$i - 1]['opens'] . '–' . $sorted[$i - 1]['closes']
                                . ' and ' . $sorted[$i]['opens'] . '–' . $sorted[$i]['closes'] . '.';
                            break;
                        }
                    }
                }

                $rowsByDay[$day] = $rows;
            }
        }

        /* 3. Save when clean. */
        if ($dayErrors === [] && !isset($errors['general'])) {
            try {
                $pdo->beginTransaction();
                $repo->deleteOperatingHours($roomId);

                $count = 0;
                foreach ($rowsByDay as $day => $rows) {
                    $day = (int) $day;
                    foreach ($rows as $row) {
                        $repo->createOperatingInterval([
                            'room_id'    => $roomId,
                            'day_of_week' => $day,
                            'opens_at'    => $row['opens'] . ':00',
                            'closes_at'   => $row['closes'] . ':00',
                        ]);
                        $count++;
                    }
                }

                $pdo->commit();

                $_SESSION['rooms_flash'] = $count > 0
                    ? ['type' => 'success', 'message' => 'Schedule for "' . $room['name'] . '" saved — ' . $count . ' weekly ' . ($count === 1 ? 'interval' : 'intervals') . ' established.']
                    : ['type' => 'warning', 'message' => 'Schedule for "' . $room['name'] . '" saved, but it is now empty — students cannot book this room until intervals are added.'];

                header('Location: ' . $basePath . '/admin/operating-hours-editor.php?room_id=' . $roomId);
                exit;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Operating hours save failed: ' . $e->getMessage());
                $errors['general'] = 'The schedule could not be saved. Nothing was changed — please try again.';
            }
        }
    }
}

$pageTitle    = 'Operating hours';
$pageSubtitle = 'Set the weekly bookable schedule for "' . $room['name'] . '".';
$pageActions  = '<a href="' . esc($basePath) . '/admin/operating-hours.php" class="btn btn--secondary">Back to overview</a>';

/* Session flash (set by the save-redirect or by earlier pages). */
$flash = $_SESSION['rooms_flash'] ?? null;
unset($_SESSION['rooms_flash']);

ob_start();
require __DIR__ . '/../../app/Modules/Rooms/Views/operating-hours-editor-view.php';
$content = ob_get_clean();

require __DIR__ . '/../../app/Views/layouts/app-layout.php';
