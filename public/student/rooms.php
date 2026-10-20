<?php
/**
 * public/student/rooms.php
 * "Find a Study Room" — room discovery — Member 4 (frontend F4).
 *
 * FRONTEND-BUILD MODE:
 * The page currently filters the shared sample dataset
 * (app/Modules/Student/_sample-data.php) so the student experience can be
 * built and reviewed before the backend services land. It performs NO
 * database queries and contains NO booking rules.
 *
 * Future integration point (agreed contract):
 *   $rooms = (new RoomService($pdo, $roomRepo))->searchAvailableRooms($filters);
 * Everything below the filter collection stays unchanged.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Core/Auth.php';
require_once __DIR__ . '/../../app/Core/Csrf.php';
require_once __DIR__ . '/../../app/Core/helpers.php';

Auth::startSession();
Auth::requireStudent();

$currentUser = Auth::user();
$basePath = defined('BASE_PATH') ? BASE_PATH : '';
$pageStyles  = '<link rel="stylesheet" href="' . esc($basePath) . '/assets/css/student-pages.css">';
$pageScripts = '<script src="' . esc($basePath) . '/assets/js/room-filters.js" defer></script>';

$sample = require __DIR__ . '/../../app/Modules/Student/_sample-data.php';
$allRooms = $sample['rooms'];
$totalCount = count($allRooms);

/* ── Read filters (GET) ─────────────────────────────────────────── */
$filters = [
    'date'         => trim((string) ($_GET['date'] ?? '')),
    'start_time'   => trim((string) ($_GET['start_time'] ?? '')),
    'end_time'     => trim((string) ($_GET['end_time'] ?? '')),
    'category'     => (string) ($_GET['category'] ?? ''),
    'min_capacity' => (string) ($_GET['min_capacity'] ?? ''),
    'facilities'   => isset($_GET['facilities']) && is_array($_GET['facilities'])
                        ? array_map('intval', $_GET['facilities'])
                        : [],
];

// Category must be one of the exact agreed values.
if (!in_array($filters['category'], ['individual_study', 'group_study', 'lounge'], true)) {
    $filters['category'] = '';
}

$minCapacity = ctype_digit($filters['min_capacity']) ? (int) $filters['min_capacity'] : 0;

/* ── Facility catalog with stable ids (sample-mode stand-in) ───── */
$facilityCatalog = [];
foreach ($allRooms as $room) {
    foreach ($room['facilities'] as $facilityName) {
        if (!isset($facilityCatalog[$facilityName])) {
            $facilityCatalog[$facilityName] = count($facilityCatalog) + 1;
        }
    }
}
$facilities = [];
foreach ($facilityCatalog as $name => $id) {
    $facilities[] = ['id' => $id, 'name' => $name];
}
$selectedFacilityIds = $filters['facilities'];

/* ── Simulated availability bookkeeping (sample-mode only) ─────── */
// A couple of demo "already booked / closed" slots so the availability
// chip is exercised in the UI. The real engine replaces this entirely.
$simulatedBusy = [
    4 => ['2026-11-02', '2026-11-04'], // Group Pod 2B busy on these dates
    7 => ['2026-11-03'],
];

$timePresented = hasTimeWindowValid($filters['date'], $filters['start_time'], $filters['end_time']);

$criteria = ['category' => $filters['category'], 'min_capacity' => $minCapacity, 'facility_ids' => $selectedFacilityIds];

$rooms = [];
foreach ($allRooms as $room) {
    if ($criteria['category'] !== ''
        && $room['category'] !== $criteria['category']) {
        continue;
    }
    if ($criteria['min_capacity'] > 0 && (int) $room['capacity'] < $criteria['min_capacity']) {
        continue;
    }
    if ($criteria['facility_ids']) {
        $roomFacilityIds = [];
        foreach ($room['facilities'] as $facilityName) {
            $roomFacilityIds[] = $facilityCatalog[$facilityName];
        }
        if (count(array_intersect($criteria['facility_ids'], $roomFacilityIds))
                !== count($criteria['facility_ids'])) {
            continue;
        }
    }

    if ($timePresented) {
        // Whole window must fit inside one operating interval on that weekday.
        $weekday = (int) DateTimeImmutable::createFromFormat('Y-m-d', $filters['date'], new DateTimeZone('Africa/Cairo'))->format('w');
        $fitsHours = false;
        foreach ($room['hours'] as $interval) {
            if ((int) $interval['day_of_week'] === $weekday
                && $interval['opens_at'] <= $filters['start_time']
                && $interval['closes_at'] >= $filters['end_time']) {
                $fitsHours = true;
                break;
            }
        }
        if (!$fitsHours) {
            continue;
        }
        // Simulated: room already busy on that date?
        $busy = in_array($filters['date'], $simulatedBusy[(int) $room['id']] ?? [], true);
        $room['availability_summary'] = $busy
            ? 'Busy at your selected time'
            : 'Available for your selected time';
    }

    // Show static summaries only while no time window searched.
    if (!$timePresented && $room['availability_summary'] !== null) {
        $room['availability_summary'] = null;
    }

    $rooms[] = $room;
}

/* ── Active filter chips with remove-one links ──────────────────── */
$chipBase = array_filter([
    'date'         => $filters['date'],
    'start_time'   => $filters['start_time'],
    'end_time'     => $filters['end_time'],
    'category'     => $filters['category'],
    'min_capacity' => $minCapacity > 0 ? (string) $minCapacity : '',
], static fn($v) => $v !== null && $v !== '');

$activeFilters = [];
$buildRemoveUrl = static function (string $withoutKey) use ($chipBase, $basePath): string {
    $query = array_filter([
        'date'         => $chipBase['date'] ?? '',
        'start_time'   => $chipBase['start_time'] ?? '',
        'end_time'     => $chipBase['end_time'] ?? '',
        'category'     => $chipBase['category'] ?? '',
        'min_capacity' => $chipBase['min_capacity'] ?? '',
    ], static fn($v) => $v !== '');
    unset($query[$withoutKey]);
    $qs = http_build_query($query);
    return $basePath . '/student/rooms.php' . ($qs !== '' ? '?' . $qs : '');
};

$removeAllUrl = $buildRemoveUrl('__none__');
if (isset($chipBase['date'])) {
    $label = 'Date: ' . formatLocalTime($chipBase['date'] . ' 00:00:00', 'D, d M Y');
    $activeFilters[] = ['label' => $label, 'removeUrl' => $buildRemoveUrl('date')];
}
if (isset($chipBase['start_time']) || isset($chipBase['end_time'])) {
    $from = isset($chipBase['start_time']) ? $chipBase['start_time'] : '—';
    $to   = isset($chipBase['end_time']) ? $chipBase['end_time'] : '—';
    $label = (isset($chipBase['start_time']) ? 'Time: ' : '') . $from . '–' . $to;
    $activeFilters[] = [
        'label' => $label,
        'removeUrl' => isset($chipBase['start_time']) ? $buildRemoveUrl('start_time') : $buildRemoveUrl('end_time'),
    ];
}
if (isset($chipBase['category'])) {
    $activeFilters[] = [
        'label' => 'Category: ' . roomCategoryLabel($chipBase['category']),
        'removeUrl' => $buildRemoveUrl('category'),
    ];
}
if (isset($chipBase['min_capacity'])) {
    $activeFilters[] = [
        'label' => 'Min capacity: ' . $chipBase['min_capacity'],
        'removeUrl' => $buildRemoveUrl('min_capacity'),
    ];
}
foreach ($selectedFacilityIds as $facilityId) {
    $remove = array_values(array_diff($selectedFacilityIds, [$facilityId]));
    $query = array_filter([
        'date'         => $chipBase['date'] ?? '',
        'start_time'   => $chipBase['start_time'] ?? '',
        'end_time'     => $chipBase['end_time'] ?? '',
        'category'     => $chipBase['category'] ?? '',
        'min_capacity' => $chipBase['min_capacity'] ?? '',
        'facilities'   => $remove,
    ], static fn($v) => $v !== '' && $v !== []);
    $activeFilters[] = [
        'label' => 'Facility: '
            . (array_search((string) $facilityId, array_map('strval', $facilityCatalog), true) ?: ''),
        'removeUrl' => $basePath . '/student/rooms.php' . ($query ? '?' . http_build_query($query) : ''),
    ];
}

/* ── Render ─────────────────────────────────────────────────────── */
$resultCount = count($rooms);
$pageTitle    = 'Find a Study Room';
$pageSubtitle = 'Discover rooms based on your preferred time, capacity, category, and facilities.';

ob_start();
require __DIR__ . '/../../app/Modules/Student/Views/room-list-view.php';
$content = ob_get_clean();

require __DIR__ . '/../../app/Views/layouts/app-layout.php';

/* ═══════════ these are ONLY sample-mode helpers ═════════════════ */

function hasTimeWindowValid(string $date, string $start, string $end): bool
{
    if ($start === '' || $end === '' || $start >= $end) {
        return false;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }
    try {
        new DateTimeImmutable($date, new DateTimeZone('Africa/Cairo'));
    } catch (Exception $e) {
        return false;
    }
    return true;
}
