<?php
/**
 * app/Modules/Student/Views/dashboard-view.php
 * Member 4 — student dashboard presentation.
 *
 * Expects:
 *   $basePath    (string)
 *   $currentUser (array)  id, name, email, role
 *   $upcoming    (array)  the student's upcoming reservations (sample data
 *                         now; ReservationService later). Each row:
 *                         id, room_id, room_name, floor_name, starts_at (UTC),
 *                         ends_at (UTC), status
 *   $stats       (array)  upcoming, confirmed, pending, past
 *   $hasPendingModification (bool) — a pending proposal exists for an
 *                         upcoming reservation
 *   $nextReservationUrl (string) — link to the first upcoming reservation
 */

$basePath        = $basePath ?? '';
$currentUser     = $currentUser ?? [];
$upcoming        = $upcoming ?? [];
$stats           = $stats ?? [];
$hasPendingModification = (bool) ($hasPendingModification ?? false);
?>
<div class="dashboard-grid" style="margin-bottom:var(--space-8);">
    <div class="stat-card">
        <p class="stat-card__label">Upcoming reservations</p>
        <p class="stat-card__value"><?= (int) ($stats['upcoming'] ?? 0) ?></p>
        <p class="stat-card__sub">Pending and confirmed bookings you have this week ahead</p>
    </div>
    <div class="stat-card">
        <p class="stat-card__label">Pending review</p>
        <p class="stat-card__value"><?= (int) ($stats['pending'] ?? 0) ?></p>
        <p class="stat-card__sub">Requests waiting for an administrator</p>
    </div>
    <div class="stat-card">
        <p class="stat-card__label">Past reservations</p>
        <p class="stat-card__value"><?= (int) ($stats['past'] ?? 0) ?></p>
        <p class="stat-card__sub">Fulfilled, rejected, and cancelled history</p>
    </div>
</div>

<div class="next-steps">
    <div class="next-step-card">
        <span class="next-step-card__icon" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
        </span>
        <h2 class="next-step-card__title">Find a study room</h2>
        <p class="next-step-card__text">Search by date, time, category, capacity, and facilities to see rooms that look available.</p>
        <a href="<?= esc($basePath) ?>/student/rooms.php" class="btn btn--primary btn--sm">Start a search</a>
    </div>
    <div class="next-step-card">
        <span class="next-step-card__icon" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
        </span>
        <h2 class="next-step-card__title">Plan for the week</h2>
        <p class="next-step-card__text">Reservations can be made up to seven days ahead. Requests are reviewed by an administrator.</p>
        <a href="<?= esc($basePath) ?>/student/reservations.php" class="btn btn--secondary btn--sm">My Reservations</a>
    </div>
    <div class="next-step-card">
        <span class="next-step-card__icon" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
            </svg>
        </span>
        <h2 class="next-step-card__title">Need a change?</h2>
        <p class="next-step-card__text">Confirmations can be changed or cancelled up to two hours before they start.</p>
        <a href="<?= esc($basePath) ?>/student/reservations.php" class="btn btn--secondary btn--sm">Manage bookings</a>
    </div>
</div>

<div class="panel">
    <div class="panel__header">
        <h2 class="panel__title">Your upcoming reservations</h2>
        <a class="btn btn--ghost btn--sm" href="<?= esc($basePath) ?>/student/reservations.php">View all</a>
    </div>
    <div class="panel__body">
        <?php if ($hasPendingModification): ?>
            <div class="alert alert--info" role="status" style="margin-bottom:var(--space-4);">
                <svg class="alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
                </svg>
                <div class="alert__body">
                    <p class="alert__title">A change request is awaiting review</p>
                    <p>One of your confirmed reservations has a modification pending. The current booking stays in force until an administrator decides.</p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($upcoming): ?>
            <div class="table-container res-desktop">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Room</th>
                            <th scope="col">Date</th>
                            <th scope="col">Time</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($upcoming, 0, 3) as $reservation): ?>
                            <tr>
                                <td>
                                    <a href="<?= esc($basePath) ?>/student/reservation-details.php?id=<?= (int) $reservation['id'] ?>"
                                       style="color:var(--color-primary);font-weight:600;text-decoration:none;">
                                        <?= esc((string) $reservation['room_name']) ?>
                                    </a>
                                </td>
                                <td><?= esc(formatLocalTime((string) $reservation['starts_at'], 'D, d M Y')) ?></td>
                                <td><?= esc(formatLocalTime((string) $reservation['starts_at'], 'H:i')) ?> – <?= esc(formatLocalTime((string) $reservation['ends_at'], 'H:i')) ?></td>
                                <td><span class="badge <?= esc(reservationStatusBadge((string) $reservation['status'])) ?>"><?= esc(reservationStatusLabel((string) $reservation['status'])) ?></span></td>
                                <td style="text-align:right;">
                                    <a class="btn btn--ghost btn--sm" href="<?= esc($basePath) ?>/student/reservation-details.php?id=<?= (int) $reservation['id'] ?>">Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state" style="padding:var(--space-6) 0;">
                <div class="empty-state__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>
                <h2 class="empty-state__title">No upcoming reservations</h2>
                <p class="empty-state__description">
                    You have no bookings ahead. Find a room and request one — new requests
                    appear here as Pending until an administrator reviews them.
                </p>
                <a href="<?= esc($basePath) ?>/student/rooms.php" class="btn btn--primary">Find a room</a>
            </div>
        <?php endif; ?>
    </div>
</div>
