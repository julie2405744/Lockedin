<?php
/**
 * app/Modules/Student/Views/room-details-view.php
 * Member 4 — room details presentation.
 *
 * Renders the room information block (image, facts, description,
 * facilities, operating hours). The booking controls themselves come
 * from Member 3's booking-form-view.php, required by the page handler
 * — never duplicated here.
 *
 * Expects:
 *   $basePath   (string)
 *   $room       (array)  id, name, category, floor_name, capacity,
 *                        description, facilities[], optional image_path,
 *                        optional availability_summary, hours[]
 *   $notice     (string|null) one-line note shown under the image,
 *                        e.g. the availability caveat
 */

$basePath = $basePath ?? '';
$room     = $room ?? [];
$notice   = $notice ?? null;

$facilityNames = array_map('strval', $room['facilities'] ?? []);

// Group the room's operating hours by weekday (0 = Sunday … 6 = Saturday).
$daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$hoursByDay = [];
foreach ($room['hours'] ?? [] as $interval) {
    $hoursByDay[(int) $interval['day_of_week']][] =
        $interval['opens_at'] . '–' . $interval['closes_at'];
}
?>
<div class="room-top">
    <div>
        <div class="room-details__img">
            <?php if (!empty($room['image_path'])): ?>
                <img src="<?= esc((string) $room['image_path']) ?>"
                     alt="Photo of <?= esc((string) $room['name']) ?>">
            <?php else: ?>
                <div class="room-details__img-placeholder">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M15 9h.01M9 13h.01M15 13h.01M9 17h.01M15 17h.01"/>
                    </svg>
                </div>
            <?php endif; ?>
        </div>
        <p class="room-details__image-note">Room photos are added by the faculty. All rooms are reserved as one whole unit.</p>

        <?php if ($notice): ?>
            <div class="alert alert--info" role="status" style="margin-top:var(--space-4);">
                <svg class="alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
                </svg>
                <div class="alert__body"><p><?= esc($notice) ?></p></div>
            </div>
        <?php endif; ?>
    </div>

    <div>
        <span class="badge badge--active"><?= esc(roomCategoryLabel((string) $room['category'])) ?></span>
        <h2 style="font-size:var(--font-size-2xl);color:var(--color-heading);margin:var(--space-2) 0;">
            <?= esc((string) $room['name']) ?>
        </h2>

        <div class="room-facts">
            <div class="room-fact">
                <p class="room-fact__label">Floor</p>
                <p class="room-fact__value"><?= esc((string) $room['floor_name']) ?></p>
            </div>
            <div class="room-fact">
                <p class="room-fact__label">Capacity</p>
                <p class="room-fact__value"><?= esc((string) $room['capacity']) ?> seats</p>
            </div>
            <div class="room-fact">
                <p class="room-fact__label">Status</p>
                <p class="room-fact__value">Active</p>
            </div>
        </div>

        <p style="color:var(--color-text);margin-bottom:var(--space-4);">
            <?= esc((string) ($room['description'] ?? '')) ?>
        </p>

        <?php if ($facilityNames): ?>
            <p class="room-card__facilities-label">Facilities</p>
            <div class="room-card__chips" style="margin-bottom:var(--space-4);">
                <?php foreach ($facilityNames as $facilityName): ?>
                    <span class="room-chip"><?= esc($facilityName) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="panel" style="margin-bottom:var(--space-6);">
    <div class="panel__header">
        <h2 class="panel__title">Weekly operating hours</h2>
        <p class="form-hint">Shown in Africa/Cairo local time. A booking must fit inside one interval on the day you choose.</p>
    </div>
    <div class="panel__body">
        <?php $shownClosedDays = false; ?>
        <table class="hours-table">
            <thead>
                <tr><th scope="col">Day</th><th scope="col">Open</th></tr>
            </thead>
            <tbody>
                <?php foreach ($daysOfWeek as $index => $dayName): ?>
                    <tr>
                        <td class="hours-table__day"><?= esc($dayName) ?></td>
                        <?php if (isset($hoursByDay[$index])): ?>
                            <td><?= esc(implode(', ', $hoursByDay[$index])) ?></td>
                        <?php else: ?>
                            <td class="hours-table__closed">Closed</td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (empty($room['hours'])): ?>
    <div class="empty-state">
        <div class="empty-state__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
        </div>
        <h2 class="empty-state__title">No operating hours published yet</h2>
        <p class="empty-state__description">
            This room has not been scheduled. Booking opens automatically once the
            faculty publishes its operating hours.
        </p>
        <a href="<?= esc($basePath) ?>/student/rooms.php" class="btn btn--primary">Browse other rooms</a>
    </div>
<?php endif; ?>
