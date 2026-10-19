<?php
/**
 * public/admin/operating-hours.php
 * Operating hours — room picker / schedule overview — Member 2 (frontend F4).
 *
 * GET only. Lists every room with its weekly schedule summary
 * (local Africa/Cairo TIME values, day 0 = Sunday). The editor
 * itself lives in operating-hours-editor.php?room_id=NN.
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

$rooms = $repo->findAllRooms();
$hours = $repo->getAllOperatingHours();
$flash = $_SESSION['rooms_flash'] ?? null;
unset($_SESSION['rooms_flash']);

$pageTitle    = 'Operating hours';
$pageSubtitle = "Each room's weekly opening schedule. Times are local (Africa/Cairo) — exactly what students see on room cards.";
$pageActions  = '';

ob_start();
require __DIR__ . '/../../app/Modules/Rooms/Views/operating-hours-view.php';
$content = ob_get_clean();

require __DIR__ . '/../../app/Views/layouts/app-layout.php';
