<?php
/**
 * app/Modules/Admin/Views/reports-view.php
 * Reservation and utilization reports — Member 5.
 *
 * Expected variables (from public/admin/reports.php):
 *   $reservationReport (array)      ['filters' => ..., 'rows' => ..., 'totals' => ...]
 *   $utilizationReport (array)      ['filters' => ..., 'rows' => ...]
 *   $filters           (array)      ['from' => ..., 'to' => ..., 'room_id' => int]
 *   $rooms             (array)      rooms for the filter dropdown
 *   $flash             (array|null) feedback array for the shared alert component
 *   $basePath          (string)
 *
 * Every figure on this screen is calculated by ReportService. The view only
 * formats what it is given — there is no report arithmetic here.
 */

require_once __DIR__ . '/../../Reservations/Views/_reservation-helpers.php';

$basePath          = $basePath          ?? '';
$reservationReport = $reservationReport ?? ['filters' => [], 'rows' => [], 'totals' => []];
$utilizationReport = $utilizationReport ?? ['filters' => [], 'rows' => []];
$filters           = $filters           ?? [];
$rooms             = $rooms             ?? [];
$flash             = $flash             ?? null;

$rows         = $reservationReport['rows'] ?? [];
$totals       = $reservationReport['totals'] ?? [];
$utilization  = $utilizationReport['rows'] ?? [];
$from         = (string) ($filters['from'] ?? '');
$to           = (string) ($filters['to'] ?? '');
$roomFilter   = (int) ($filters['room_id'] ?? 0);

$hasUtilizationData = false;
foreach ($utilization as $row) {
    if ((float) ($row['net_available_hours'] ?? 0) > 0) {
        $hasUtilizationData = true;
        break;
    }
}
?>

<?php if ($flash): ?>
    <?php $feedback = $flash; ?>
    <?php require __DIR__ . '/../../Reservations/Views/reservation-feedback-view.php'; ?>
<?php endif; ?>

<!-- ── Filters ───────────────────────────────────────────────── -->
<form class="filter-bar" method="get" action="<?= esc($basePath) ?>/admin/reports.php"
      data-range-form="report-filters">
    <div class="filter-bar__inner">
        <div class="filter-bar__field">
            <label class="form-label" for="report-from">From</label>
            <input class="form-control" type="date" id="report-from" name="from"
                   value="<?= esc($from) ?>" max="<?= esc($to !== '' ? $to : date('Y-m-d')) ?>">
        </div>

        <div class="filter-bar__field">
            <label class="form-label" for="report-to">To</label>
            <input class="form-control" type="date" id="report-to" name="to"
                   value="<?= esc($to) ?>" min="<?= esc($from) ?>">
        </div>

        <div class="filter-bar__field">
            <label class="form-label" for="report-room">Room</label>
            <select class="form-control" id="report-room" name="room_id">
                <option value="0">All rooms</option>
                <?php foreach ($rooms as $room): ?>
                    <option value="<?= (int) $room['id'] ?>"<?= $roomFilter === (int) $room['id'] ? ' selected' : '' ?>>
                        <?= esc((string) $room['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-bar__actions">
            <button type="submit" class="btn btn--primary">Apply filters</button>
            <a class="btn btn--ghost" href="<?= esc($basePath) ?>/admin/reports.php">Reset</a>
        </div>
    </div>

    <div class="admin-review__actions" style="margin-top:var(--space-4);">
        <button type="button" class="btn btn--secondary btn--sm" data-range-days="7"
                data-range-scope="report-filters" aria-pressed="false">Last 7 days</button>
        <button type="button" class="btn btn--secondary btn--sm" data-range-days="30"
                data-range-scope="report-filters" aria-pressed="false">Last 30 days</button>
        <button type="button" class="btn btn--secondary btn--sm" data-range-days="90"
                data-range-scope="report-filters" aria-pressed="false">Last 90 days</button>
    </div>
</form>

<p class="admin-filter-summary">
    Reporting window <strong><?= esc($from !== '' ? $from : '—') ?></strong>
    &ndash;
    <strong><?= esc($to !== '' ? $to : '—') ?></strong>.
    Times are shown in the campus timezone (Africa/Cairo).
</p>

<div class="admin-stack">
    <!-- ── Reservation report ──────────────────────────────────── -->
    <section class="panel" aria-labelledby="reservation-report-title">
        <div class="panel__header">
            <h2 class="panel__title" id="reservation-report-title">Reservation report</h2>
            <span class="admin-panel-table__meta">
                <?= (int) ($totals['total'] ?? 0) ?>
                <?= ((int) ($totals['total'] ?? 0)) === 1 ? 'reservation' : 'reservations' ?>
            </span>
        </div>

        <?php if ($rows === []): ?>
            <div class="panel__body">
                <div class="admin-empty-inline">
                    <p><strong>No reservations in this window.</strong></p>
                    <p>Pick a wider date range to see more activity.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th scope="col">Room</th>
                            <th scope="col">Student</th>
                            <th scope="col">Date</th>
                            <th scope="col">Time</th>
                            <th scope="col">Hours</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td data-label="Room">
                                    <?= esc((string) $row['room_name']) ?>
                                    <span class="admin-panel-table__meta"><?= esc((string) $row['floor_name']) ?></span>
                                </td>
                                <td data-label="Student"><?= esc((string) $row['student_name']) ?></td>
                                <td data-label="Date"><?= esc(formatLocalTime((string) $row['starts_at'], 'D, d M Y')) ?></td>
                                <td data-label="Time">
                                    <?= esc(formatLocalTime((string) $row['starts_at'], 'H:i')) ?>
                                    &ndash;
                                    <?= esc(formatLocalTime((string) $row['ends_at'], 'H:i')) ?>
                                </td>
                                <td data-label="Hours">
                                    <?php
                                    $rowDuration = reservationDurationLabel(
                                        (string) $row['starts_at'],
                                        (string) $row['ends_at']
                                    );
                                    ?>
                                    <?= esc($rowDuration !== '' ? $rowDuration : '—') ?>
                                </td>
                                <td data-label="Status">
                                    <span class="badge <?= esc(reservationStatusBadge((string) $row['status'])) ?>">
                                        <?= esc(reservationStatusLabel((string) $row['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="admin-totals">
                <div>
                    <p class="admin-total__label">Total</p>
                    <p class="admin-total__value"><?= (int) ($totals['total'] ?? 0) ?></p>
                </div>
                <div>
                    <p class="admin-total__label">Confirmed</p>
                    <p class="admin-total__value"><?= (int) ($totals['confirmed'] ?? 0) ?></p>
                </div>
                <div>
                    <p class="admin-total__label">Pending</p>
                    <p class="admin-total__value"><?= (int) ($totals['pending'] ?? 0) ?></p>
                </div>
                <div>
                    <p class="admin-total__label">Booked hours</p>
                    <p class="admin-total__value"><?= esc(rtrim(rtrim(number_format((float) ($totals['hours'] ?? 0), 1), '0'), '.')) ?></p>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- ── Utilization report ───────────────────────────────────── -->
    <section class="panel" aria-labelledby="utilization-report-title">
        <div class="panel__header">
            <h2 class="panel__title" id="utilization-report-title">Room utilization</h2>
            <span class="admin-panel-table__meta">
                Confirmed hours &divide; net available operating hours
            </span>
        </div>

        <?php if ($utilization === []): ?>
            <div class="panel__body">
                <div class="admin-empty-inline">
                    <p><strong>No rooms to report on.</strong></p>
                    <p>Rooms appear here once they have been created.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th scope="col">Room</th>
                            <th scope="col">Net available hours</th>
                            <th scope="col">Confirmed hours</th>
                            <th scope="col">Pending demand</th>
                            <th scope="col">Utilization</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($utilization as $row): ?>
                            <?php
                            $utilizationValue = $row['utilization'];
                            $barWidth         = $utilizationValue === null ? 0 : max(0, min(100, (float) $utilizationValue));
                            $netAvailable     = (float) $row['net_available_hours'];
                            ?>
                            <tr>
                                <td data-label="Room">
                                    <strong class="admin-nowrap"><?= esc((string) $row['room_name']) ?></strong>
                                    <span class="admin-panel-table__meta">
                                        <?= esc((string) $row['floor_name']) ?>
                                        &middot;
                                        <?= esc(roomCategoryLabel((string) $row['category'])) ?>
                                        <?php if (($row['status'] ?? '') !== 'active'): ?>
                                            &middot;
                                            <span class="badge badge--inactive">inactive</span>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td data-label="Net available hours">
                                    <?= esc(rtrim(rtrim(number_format($netAvailable, 1), '0'), '.')) ?>
                                    <span class="admin-panel-table__meta">
                                        <?= esc(rtrim(rtrim(number_format((float) $row['scheduled_hours'], 1), '0'), '.')) ?>
                                        scheduled
                                        <?php if ((float) $row['closure_hours'] > 0): ?>
                                            &minus;
                                            <?= esc(rtrim(rtrim(number_format((float) $row['closure_hours'], 1), '0'), '.')) ?>
                                            closed
                                        <?php else: ?>
                                            &middot; no closures
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td data-label="Confirmed hours"><?= esc(rtrim(rtrim(number_format((float) $row['confirmed_hours'], 1), '0'), '.')) ?></td>
                                <td data-label="Pending demand"><?= esc(rtrim(rtrim(number_format((float) $row['pending_hours'], 1), '0'), '.')) ?></td>
                                <td data-label="Utilization">
                                    <div class="admin-util">
                                        <span class="admin-util__track" aria-hidden="true">
                                            <span class="admin-util__fill" style="width:<?= esc((string) $barWidth) ?>%"></span>
                                        </span>
                                        <?php if ($utilizationValue === null): ?>
                                            <span class="admin-util__value admin-util__value--na">N/A</span>
                                        <?php else: ?>
                                            <span class="admin-util__value"><?= esc((string) $row['utilization_display']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="panel__body">
                <p class="admin-filter-summary">
                    <?php if ($hasUtilizationData): ?>
                        Utilization is shown exactly as the server calculated it. A room with no net available
                        operating hours shows <strong>N/A</strong> instead of a percentage.
                    <?php else: ?>
                        No room has net available operating hours in this window, so utilization is
                        <strong>N/A</strong>.
                    <?php endif; ?>
                    Pending reservations are reported as demand only and are never counted as confirmed usage.
                </p>
            </div>
        <?php endif; ?>
    </section>
</div>
