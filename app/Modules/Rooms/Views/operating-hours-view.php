<?php
/**
 * app/Modules/Rooms/Views/operating-hours-view.php
 * Operating hours overview (room picker) — Member 2.
 *
 * Expected variables (from public/admin/operating-hours.php):
 *   $rooms    (array)      findAllRooms() rows
 *   $hours    (array)      getAllOperatingHours() rows (all rooms)
 *   $flash    (array|null) ['type' => success|danger, 'message' => string]
 *   $basePath (string)
 */

$basePath = $basePath ?? '';
$rooms    = $rooms    ?? [];
$allHours = $hours    ?? [];
$flash    = $flash    ?? null;
$active   = 'hours';
require __DIR__ . '/_rooms-nav.php';

/* Group all intervals by room for compact per-room summaries. */
$hoursByRoom = [];
foreach ($allHours as $hoursRow) {
    $hoursByRoom[(int) $hoursRow['room_id']][] = $hoursRow;
}

$dayNames = [0 => 'Sun', 1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat'];

/** Compact schedule summary for one room: "Sun 08–22, Wed 10–12". */
$summarize = function (array $intervals, array $dayNames): string {
    $byDay = [];
    foreach ($intervals as $interval) {
        $byDay[(int) $interval['day_of_week']][] = $interval;
    }

    ksort($byDay);
    $parts = [];
    foreach ($byDay as $day => $dayIntervals) {
        $spans = [];
        foreach ($dayIntervals as $interval) {
            $spans[] = substr($interval['opens_at'], 0, 5) . '–' . substr($interval['closes_at'], 0, 5);
        }
        $parts[] = $dayNames[$day] . ' ' . implode(', ', $spans);
    }
    return implode(' · ', $parts);
};
?>

<?php if ($flash): ?>
    <div class="alert alert--<?= esc($flash['type']) ?>" role="status" aria-live="polite" style="margin-bottom: var(--space-6);">
        <svg class="alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <div class="alert__body"><p><?= esc($flash['message']) ?></p></div>
    </div>
<?php endif; ?>

<?php if ($rooms === []): ?>
    <div class="empty-state">
        <div class="empty-state__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
        </div>
        <h2 class="empty-state__title">No rooms to schedule</h2>
        <p class="empty-state__description">Operating hours belong to rooms. Create a room first, then open its schedule here.</p>
        <a href="<?= esc($basePath) ?>/admin/rooms.php" class="btn btn--primary">Go to rooms</a>
    </div>
<?php else: ?>
    <div class="alert alert--info" role="note" style="margin-bottom: var(--space-6);">
        <svg class="alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
        </svg>
        <div class="alert__body"><p>All times on this page are <strong>Africa/Cairo local time</strong> — the same clock students see. Save changes on the room's schedule page.</p></div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Room</th>
                    <th scope="col">Weekly schedule</th>
                    <th scope="col">Intervals</th>
                    <th scope="col" style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rooms as $room): ?>
                    <?php $roomIntervals = $hoursByRoom[(int) $room['id']] ?? []; ?>
                    <tr>
                        <td>
                            <strong><?= esc($room['name']) ?></strong>
                            <div class="rooms-meta"><?= esc($room['floor_name']) ?> (Floor <?= (int) $room['floor_number'] ?>) · <?= esc(roomCategoryLabel($room['category'])) ?></div>
                        </td>
                        <td>
                            <?php if ($roomIntervals !== []): ?>
                                <span data-testid="schedule-<?= (int) $room['id'] ?>"><?= esc($summarize($roomIntervals, $dayNames)) ?></span>
                            <?php else: ?>
                                <span class="badge badge--inactive">No schedule yet</span>
                            <?php endif; ?>
                        </td>
                        <td><?= count($roomIntervals) ?></td>
                        <td>
                            <div class="rooms-table__actions">
                                <a class="btn btn--primary btn--sm"
                                   href="<?= esc($basePath) ?>/admin/operating-hours-editor.php?room_id=<?= (int) $room['id'] ?>">
                                    Edit schedule
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
