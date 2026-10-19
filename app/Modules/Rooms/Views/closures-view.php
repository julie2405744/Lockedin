<?php
/**
 * app/Modules/Rooms/Views/closures-view.php
 * Closures / maintenance list — Member 2.
 *
 * Expected variables (from public/admin/closures.php):
 *   $rooms    (array)      findAllRooms() rows
 *   $closures (array)      getAllUnavailability() rows (UTC DATETIME)
 *   $flash    (array|null) ['type' => success|danger, 'message' => string]
 *   $basePath (string)
 */

$basePath = $basePath ?? '';
$rooms    = $rooms    ?? [];
$closures = $closures ?? [];
$flash    = $flash    ?? null;
$active   = 'closures';
require __DIR__ . '/_rooms-nav.php';

/** Period status relative to now, in Africa/Cairo time. */
$periodState = function (string $startsUtc, string $endsUtc): string {
    try {
        $now    = new DateTimeImmutable('now', new DateTimeZone('Africa/Cairo'));
        $starts = (new DateTimeImmutable($startsUtc, new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Africa/Cairo'));
        $ends   = (new DateTimeImmutable($endsUtc, new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Africa/Cairo'));
        if ($now < $starts) {
            return 'Upcoming';
        }
        if ($now <= $ends) {
            return 'Ongoing';
        }
        return 'Finished';
    } catch (Exception $e) {
        return 'Finished';
    }
};
?>

<?php if ($flash): ?>
    <div class="alert alert--<?= esc($flash['type']) ?>" role="status" aria-live="polite" style="margin-bottom: var(--space-6);">
        <svg class="alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <div class="alert__body">
            <p><?= esc($flash['message']) ?></p>
        </div>
    </div>
<?php endif; ?>

<?php if ($rooms === []): ?>
    <div class="empty-state">
        <div class="empty-state__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
        </div>
        <h2 class="empty-state__title">No rooms to close</h2>
        <p class="empty-state__description">Closures block bookings for one room at a time. Create a room first, then add its maintenance periods.</p>
        <a href="<?= esc($basePath) ?>/admin/rooms.php" class="btn btn--primary">Go to rooms</a>
    </div>
<?php elseif ($closures === []): ?>
    <div class="empty-state">
        <div class="empty-state__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
        </div>
        <h2 class="empty-state__title">No closures or maintenance periods</h2>
        <p class="empty-state__description">When a room needs repairs or the building closes, add the period here so students cannot book it.</p>
        <a href="<?= esc($basePath) ?>/admin/closure-form.php" class="btn btn--primary">Add a closure</a>
    </div>
<?php else: ?>
    <div class="alert alert--info" role="note" style="margin-bottom: var(--space-6);">
        <svg class="alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
        </svg>
        <div class="alert__body"><p>Date and time are shown in <strong>Africa/Cairo local time</strong>; the system stores the matching UTC values internally.</p></div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Room</th>
                    <th scope="col">Starts</th>
                    <th scope="col">Ends</th>
                    <th scope="col">Reason</th>
                    <th scope="col">Status</th>
                    <th scope="col" style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($closures as $closure): ?>
                    <?php $state = $periodState($closure['starts_at'], $closure['ends_at']); ?>
                    <tr>
                        <td><strong><?= esc($closure['room_name']) ?></strong></td>
                        <td><?= esc(formatLocalTime($closure['starts_at'], 'D, d M Y H:i')) ?></td>
                        <td><?= esc(formatLocalTime($closure['ends_at'], 'D, d M Y H:i')) ?></td>
                        <td><?= esc($closure['reason']) ?></td>
                        <td><span class="badge <?= $state === 'Finished' ? 'badge--inactive' : ($state === 'Ongoing' ? 'badge--pending' : 'badge--active') ?>"><?= esc($state) ?></span></td>
                        <td>
                            <div class="rooms-table__actions">
                                <a class="btn btn--secondary btn--sm"
                                   href="<?= esc($basePath) ?>/admin/closure-form.php?id=<?= (int) $closure['id'] ?>">
                                    Edit
                                </a>
                                <form method="post" action="<?= esc($basePath) ?>/admin/closures.php"
                                      data-confirm="Remove closure &quot;<?= esc($closure['reason']) ?>&quot;? Students will be able to request bookings for this room during that period again.">
                                    <input type="hidden" name="closure_id" value="<?= (int) $closure['id'] ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="btn btn--danger btn--sm">Remove</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
