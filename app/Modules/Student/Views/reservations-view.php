<?php
/**
 * app/Modules/Student/Views/reservations-view.php
 * Member 4 — "My Reservations" presentation.
 *
 * Desktop: table. Mobile (≤575px): stacked cards (CSS-driven swap).
 * Rows carry Member 3's reservation-actions-view component where any
 * action is applicable. Display only — cutoffs and permissions are
 * enforced by ReservationService on the server; the handler decides
 * which actions to enable.
 *
 * Expects:
 *   $basePath       (string)
 *   $reservations   (array) filtered rows: id, room_id, room_name,
 *                           floor_name, starts_at (UTC), ends_at (UTC),
 *                           status, reason?
 *   $allCount       (int)
 *   $statusFilter   (string) '' | pending | confirmed | rejected | cancelled
 *   $statusCounts   (array)  map status => count for tabs
 *   $actions        (array)  map reservation_id => ['canModify' => bool, 'canCancel' => bool]
 */

$basePath     = $basePath ?? '';
$reservations = $reservations ?? [];
$allCount     = (int) ($allCount ?? 0);
$statusFilter = (string) ($statusFilter ?? '');
$statusCounts = $statusCounts ?? [];
$actions      = $actions ?? [];

$statuses = ['pending', 'confirmed', 'rejected', 'cancelled'];
$tabs = ['' => 'All'];
foreach ($statuses as $status) {
    $tabs[$status] = reservationStatusLabel($status);
}

$tabsUrl = static function (string $status) use ($basePath): string {
    return $basePath . '/student/reservations.php'
        . ($status !== '' ? '?status=' . $status : '');
};
?>
<div class="res-tabs" role="navigation" aria-label="Filter reservations by status">
    <?php foreach ($tabs as $status => $label): ?>
        <a href="<?= esc($tabsUrl($status)) ?>"
           class="res-tabs__tab<?= $statusFilter === $status ? ' res-tabs__tab--active' : '' ?>"
           <?= $statusFilter === $status ? 'aria-current="page"' : '' ?>>
            <?= esc($label) ?>
            <span class="res-tabs__count"><?= (int) ($status === '' ? $allCount : ($statusCounts[$status] ?? 0)) ?></span>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($reservations): ?>
    <!-- Desktop table -->
    <div class="table-container res-desktop">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Room</th>
                    <th scope="col">Date</th>
                    <th scope="col">Time</th>
                    <th scope="col">Status</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservations as $reservation): ?>
                    <?php $reservationActions = $actions[(int) $reservation['id']] ?? []; ?>
                    <tr>
                        <td>
                            <a href="<?= esc($basePath) ?>/student/reservation-details.php?id=<?= (int) $reservation['id'] ?>"
                               style="color:var(--color-primary);font-weight:600;text-decoration:none;">
                                <?= esc((string) $reservation['room_name']) ?>
                            </a>
                            <p style="font-size:var(--font-size-xs);color:var(--color-muted);"><?= esc((string) $reservation['floor_name']) ?></p>
                        </td>
                        <td><?= esc(formatLocalTime((string) $reservation['starts_at'], 'D, d M Y')) ?></td>
                        <td><?= esc(formatLocalTime((string) $reservation['starts_at'], 'H:i')) ?> – <?= esc(formatLocalTime((string) $reservation['ends_at'], 'H:i')) ?></td>
                        <td>
                            <span class="badge <?= esc(reservationStatusBadge((string) $reservation['status'])) ?>">
                                <?= esc(reservationStatusLabel((string) $reservation['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $detailsUrl = $basePath . '/student/reservation-details.php?id=' . (int) $reservation['id'];
                            $canCancel  = (bool) ($reservationActions['canCancel'] ?? false);
                            $canModify  = (bool) ($reservationActions['canModify'] ?? false);
                            $cancelUrl  = $canCancel ? $basePath . '/student/reservation-cancel.php?id=' . (int) $reservation['id'] : null;
                            $modifyUrl  = $canModify ? $basePath . '/student/reservation-modify.php?id=' . (int) $reservation['id'] : null;
                            require __DIR__ . '/../../../../app/Modules/Reservations/Views/reservation-actions-view.php';
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Mobile stacked cards -->
    <div class="res-mobile">
        <?php foreach ($reservations as $reservation): ?>
            <?php $reservationActions = $actions[(int) $reservation['id']] ?? []; ?>
            <article class="res-card">
                <?php if (!empty($reservation['reason'])
                          && in_array($reservation['status'], ['rejected'], true)): ?>
                    <p class="res-card__reason" role="note">
                        <strong>Why:</strong> <?= esc((string) $reservation['reason']) ?>
                    </p>
                <?php endif; ?>
                <div class="res-card__top">
                    <a class="res-card__room" href="<?= esc($basePath) ?>/student/reservation-details.php?id=<?= (int) $reservation['id'] ?>">
                        <?= esc((string) $reservation['room_name']) ?>
                    </a>
                    <span class="badge <?= esc(reservationStatusBadge((string) $reservation['status'])) ?>">
                        <?= esc(reservationStatusLabel((string) $reservation['status'])) ?>
                    </span>
                </div>
                <div class="res-card__meta">
                    <span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align:-2px;">
                            <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
                        </svg>
                        <?= esc(formatLocalTime((string) $reservation['starts_at'], 'D, d M Y')) ?>
                    </span>
                    <span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align:-2px;">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <?= esc(formatLocalTime((string) $reservation['starts_at'], 'H:i')) ?> – <?= esc(formatLocalTime((string) $reservation['ends_at'], 'H:i')) ?>
                    </span>
                    <span><?= esc((string) $reservation['floor_name']) ?></span>
                </div>
                <div class="res-card__actions">
                    <?php
                    $detailsUrl = $basePath . '/student/reservation-details.php?id=' . (int) $reservation['id'];
                    $canCancel  = (bool) ($reservationActions['canCancel'] ?? false);
                    $canModify  = (bool) ($reservationActions['canModify'] ?? false);
                    $cancelUrl  = $canCancel ? $basePath . '/student/reservation-cancel.php?id=' . (int) $reservation['id'] : null;
                    $modifyUrl  = $canModify ? $basePath . '/student/reservation-modify.php?id=' . (int) $reservation['id'] : null;
                    require __DIR__ . '/../../../../app/Modules/Reservations/Views/reservation-actions-view.php';
                    ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty-state" style="padding:var(--space-8) 0;">
        <div class="empty-state__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V2h6v2M9 10h6M9 14h6M9 18h4"/>
            </svg>
        </div>
        <?php if ($statusFilter !== ''): ?>
            <h2 class="empty-state__title">No <?= esc(strtolower(reservationStatusLabel($statusFilter))) ?> reservations</h2>
            <p class="empty-state__description">Nothing with this status right now. Try the All tab to see your complete history.</p>
            <a href="<?= esc($tabsUrl('')) ?>" class="btn btn--primary">Show all reservations</a>
        <?php else: ?>
            <h2 class="empty-state__title">No reservations yet</h2>
            <p class="empty-state__description">
                You haven't requested a room yet. Find a study space and submit your first booking —
                requests are reviewed by an administrator before they are confirmed.
            </p>
            <a href="<?= esc($basePath) ?>/student/rooms.php" class="btn btn--primary">Find a room</a>
        <?php endif; ?>
    </div>
<?php endif; ?>
