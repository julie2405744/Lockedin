<?php
/**
 * app/Services/ReportService.php
 * Admin dashboard metrics and report calculations — Member 5.
 *
 * Ownership: Member 5 (admin dashboard and reports). See docs/member-tasks.md §8
 * and docs/api-contracts.md §3.4.
 *
 * This service owns every report calculation so that no page or template has
 * to repeat one. It never writes to the database and it never changes a
 * reservation status — review decisions belong to ReservationService
 * (Member 3).
 *
 * Utilization follows the agreed definition:
 *
 *     Utilization = confirmed reservation hours / net available operating hours x 100
 *
 * The denominator is the scheduled operating time of the reporting window
 * minus the closure periods, with overlapping closures merged so that an
 * overlapping closure is never subtracted twice, and with a closure counted
 * only for the part of it that falls inside an opening interval. Pending
 * reservations are reported separately as demand and never counted as
 * confirmed usage. Rejected and cancelled reservations are never counted as
 * confirmed usage. When the net available hours are zero the result is `N/A`.
 *
 * Every method returns the shared result shape documented in
 * docs/api-contracts.md §2.4:
 *
 *     ['success' => bool, 'message' => string, 'data' => array|null]
 */

class ReportService
{
    /** Reporting window is interpreted in the campus timezone. */
    public const TIMEZONE = 'Africa/Cairo';

    /** Default window when the caller supplies no dates: the last 30 days. */
    private const DEFAULT_RANGE_DAYS = 30;

    /** Longest window a report may cover, to keep the calculation bounded. */
    private const MAX_RANGE_DAYS = 366;

    private ReportRepository $repo;

    public function __construct(ReportRepository $repo)
    {
        $this->repo = $repo;
    }

    /* ── Dashboard ───────────────────────────────────────────────── */

    /**
     * Summary metrics for the admin dashboard.
     *
     * @param array $filters Optional 'from' / 'to' dates ('Y-m-d').
     * @return array{success:bool,message:string,data:array|null}
     */
    public function getDashboardSummary(array $filters = []): array
    {
        try {
            $range = $this->resolveRange($filters);
        } catch (InvalidArgumentException $e) {
            return $this->failure($e->getMessage());
        }

        $reservationCounts  = $this->repo->countReservationsByStatus();
        $modificationCounts = $this->repo->countModificationsByStatus();

        $metrics = [
            'pending_reservations'   => $reservationCounts['pending'],
            'confirmed_reservations' => $reservationCounts['confirmed'],
            'rejected_reservations'  => $reservationCounts['rejected'],
            'cancelled_reservations' => $reservationCounts['cancelled'],
            'pending_modifications'  => $modificationCounts['pending'],
            'approved_modifications' => $modificationCounts['approved'],
            'active_rooms'           => $this->repo->countActiveRooms(),
            'active_floors'          => $this->repo->countActiveFloors(),
            'reservations_in_range'  => $this->repo->countReservationsBetween(
                $range['start_utc'],
                $range['end_utc']
            ),
            'range_from'             => $range['from'],
            'range_to'               => $range['to'],
        ];

        return $this->success($metrics);
    }

    /* ── Reservation report ──────────────────────────────────────── */

    /**
     * Reservation report for a date range, with the totals for that range.
     *
     * @param array $filters Optional 'from', 'to', 'status', 'room_id'.
     * @return array{success:bool,message:string,data:array|null}
     */
    public function getReservationReport(array $filters = []): array
    {
        try {
            $range = $this->resolveRange($filters);
        } catch (InvalidArgumentException $e) {
            return $this->failure($e->getMessage());
        }

        $status = $this->normalizeStatus($filters['status'] ?? null);
        if ($status === false) {
            return $this->failure('Choose a valid reservation status.');
        }

        $roomId = (int) ($filters['room_id'] ?? 0);
        $rows   = $this->repo->findReservationsBetween(
            $range['start_utc'],
            $range['end_utc'],
            ['status' => $status, 'room_id' => $roomId > 0 ? $roomId : null]
        );

        $totals = [
            'total'     => count($rows),
            'pending'   => 0,
            'confirmed' => 0,
            'rejected'  => 0,
            'cancelled' => 0,
            'hours'     => 0.0,
        ];

        foreach ($rows as $row) {
            $rowStatus = (string) $row['status'];
            if (isset($totals[$rowStatus])) {
                $totals[$rowStatus]++;
            }

            $totals['hours'] += $this->intervalHours(
                (string) $row['starts_at'],
                (string) $row['ends_at']
            );
        }

        $totals['hours'] = round($totals['hours'], 1);

        $data = [
            'filters' => [
                'from'    => $range['from'],
                'to'      => $range['to'],
                'status'  => $status,
                'room_id' => $roomId > 0 ? $roomId : null,
            ],
            'rows'   => $rows,
            'totals' => $totals,
        ];

        return $this->success($data);
    }

    /* ── Utilization report ──────────────────────────────────────── */

    /**
     * Room utilization report for a date range.
     *
     * @param array $filters Optional 'from', 'to', 'room_id'.
     * @return array{success:bool,message:string,data:array|null}
     */
    public function getRoomUtilizationReport(array $filters = []): array
    {
        try {
            $range = $this->resolveRange($filters);
        } catch (InvalidArgumentException $e) {
            return $this->failure($e->getMessage());
        }

        $roomIdFilter = (int) ($filters['room_id'] ?? 0);

        $rooms = $this->repo->findRoomsWithFloors();
        if ($roomIdFilter > 0) {
            $rooms = array_values(array_filter(
                $rooms,
                static fn (array $room): bool => (int) $room['id'] === $roomIdFilter
            ));
        }

        // Weekly schedule per room: room_id => day_of_week => intervals.
        $schedule = [];
        foreach ($this->repo->findOperatingHours() as $row) {
            $schedule[(int) $row['room_id']][(int) $row['day_of_week']][] = [
                'opens'  => substr((string) $row['opens_at'], 0, 5),
                'closes' => substr((string) $row['closes_at'], 0, 5),
            ];
        }

        // Closure periods per room (UTC).
        $closuresByRoom = [];
        foreach ($this->repo->findClosuresBetween($range['start_utc'], $range['end_utc']) as $row) {
            $closuresByRoom[(int) $row['room_id']][] = [
                $this->timestamp((string) $row['starts_at']),
                $this->timestamp((string) $row['ends_at']),
            ];
        }

        // Confirmed usage and pending demand per room (UTC).
        $confirmedByRoom = [];
        $pendingByRoom   = [];
        foreach ($this->repo->findReservationIntervals(
            $range['start_utc'],
            $range['end_utc'],
            ['confirmed', 'pending']
        ) as $row) {
            $roomId  = (int) $row['room_id'];
            $minutes = $this->clippedMinutes(
                (string) $row['starts_at'],
                (string) $row['ends_at'],
                $range['start_utc'],
                $range['end_utc']
            );

            if ((string) $row['status'] === 'confirmed') {
                $confirmedByRoom[$roomId] = ($confirmedByRoom[$roomId] ?? 0) + $minutes;
            } else {
                $pendingByRoom[$roomId] = ($pendingByRoom[$roomId] ?? 0) + $minutes;
            }
        }

        $rows = [];
        foreach ($rooms as $room) {
            $roomId = (int) $room['id'];

            // Every opening interval of the reporting window, in UTC.
            $openIntervals = $this->buildOpenIntervals(
                $schedule[$roomId] ?? [],
                $range['from_date'],
                $range['to_date']
            );

            $scheduledHours = $this->totalHours($openIntervals);

            // A closure only removes the part of it that falls inside an
            // opening interval; overlapping closures are merged first.
            $closedHours = $this->overlapHours(
                $openIntervals,
                $this->mergeIntervals($closuresByRoom[$roomId] ?? [])
            );

            $netAvailable = max(0.0, $scheduledHours - $closedHours);
            $confirmedH   = round(($confirmedByRoom[$roomId] ?? 0) / 60, 1);
            $pendingH     = round(($pendingByRoom[$roomId] ?? 0) / 60, 1);

            $utilization = null;
            if ($netAvailable > 0) {
                $utilization = round(($confirmedH / $netAvailable) * 100, 1);
            }

            $rows[] = [
                'room_id'             => $roomId,
                'room_name'           => (string) $room['name'],
                'category'            => (string) $room['category'],
                'capacity'            => (int) $room['capacity'],
                'status'              => (string) $room['status'],
                'floor_name'          => (string) $room['floor_name'],
                'floor_number'        => (int) $room['floor_number'],
                'scheduled_hours'     => round($scheduledHours, 1),
                'closure_hours'       => $closedHours,
                'net_available_hours' => round($netAvailable, 1),
                'confirmed_hours'     => $confirmedH,
                'pending_hours'       => $pendingH,
                'utilization'         => $utilization,
                'utilization_display' => $utilization === null ? 'N/A' : $utilization . '%',
            ];
        }

        $data = [
            'filters' => [
                'from'    => $range['from'],
                'to'      => $range['to'],
                'room_id' => $roomIdFilter > 0 ? $roomIdFilter : null,
            ],
            'rows' => $rows,
        ];

        return $this->success($data);
    }

    /* ── Internals ───────────────────────────────────────────────── */

    /**
     * Resolve and validate a reporting window.
     *
     * @return array{from:string,to:string,from_date:DateTimeImmutable,to_date:DateTimeImmutable,start_utc:string,end_utc:string}
     * @throws InvalidArgumentException when the dates are invalid.
     */
    private function resolveRange(array $filters): array
    {
        $timezone = new DateTimeZone(self::TIMEZONE);
        $today    = new DateTimeImmutable('today', $timezone);

        $fromInput = trim((string) ($filters['from'] ?? ''));
        $toInput   = trim((string) ($filters['to'] ?? ''));

        $from = $fromInput !== ''
            ? $fromInput
            : $today->modify('-' . (self::DEFAULT_RANGE_DAYS - 1) . ' days')->format('Y-m-d');

        $to = $toInput !== '' ? $toInput : $today->format('Y-m-d');

        $fromDate = $this->parseDate($from);
        $toDate   = $this->parseDate($to);

        if ($fromDate === null || $toDate === null) {
            throw new InvalidArgumentException('Use the YYYY-MM-DD format for the report dates.');
        }

        if ($fromDate > $toDate) {
            throw new InvalidArgumentException('The start date must not be after the end date.');
        }

        $days = (int) $fromDate->diff($toDate)->days + 1;
        if ($days > self::MAX_RANGE_DAYS) {
            throw new InvalidArgumentException('Choose a reporting window of one year or less.');
        }

        $startUtc = $fromDate->setTime(0, 0, 0)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $endUtc   = $toDate->setTime(23, 59, 59)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');

        return [
            'from'      => $from,
            'to'        => $to,
            'from_date' => $fromDate,
            'to_date'   => $toDate,
            'start_utc' => $startUtc,
            'end_utc'   => $endUtc,
        ];
    }

    /**
     * Parse a 'Y-m-d' date in the campus timezone.
     *
     * @return DateTimeImmutable|null null when the value is not a valid date.
     */
    private function parseDate(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $value,
            new DateTimeZone(self::TIMEZONE)
        );

        if ($date === false) {
            return null;
        }

        $errors = DateTimeImmutable::getLastErrors();
        if (is_array($errors) && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0)) {
            return null;
        }

        return $date;
    }

    /**
     * Normalise a reservation status filter.
     *
     * @return string|null|false null for "no filter", false for an invalid value.
     */
    private function normalizeStatus($status)
    {
        $status = trim((string) $status);
        if ($status === '') {
            return null;
        }

        $allowed = ['pending', 'confirmed', 'rejected', 'cancelled'];

        return in_array($status, $allowed, true) ? $status : false;
    }

    /**
     * Build the opening intervals of a room across the reporting window.
     *
     * Operating hours are local wall-clock times, so every interval is built
     * from a local date and then converted to UTC. Intervals are returned
     * merged and sorted.
     *
     * @param array $byDay day_of_week => list of ['opens' => 'HH:MM', 'closes' => 'HH:MM']
     * @return array<int, array{0:int,1:int}> unix timestamp pairs
     */
    private function buildOpenIntervals(array $byDay, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $timezone = new DateTimeZone(self::TIMEZONE);
        $utc      = new DateTimeZone('UTC');
        $intervals = [];

        $cursor = $from;
        while ($cursor <= $to) {
            $dayOfWeek = (int) $cursor->format('w');
            $dayStart  = new DateTimeImmutable($cursor->format('Y-m-d'), $timezone);

            foreach ($byDay[$dayOfWeek] ?? [] as $interval) {
                [$opensHour, $opensMinute]   = array_pad(explode(':', (string) $interval['opens']), 2, '0');
                [$closesHour, $closesMinute] = array_pad(explode(':', (string) $interval['closes']), 2, '0');

                $opensLocal  = $dayStart->setTime((int) $opensHour, (int) $opensMinute, 0);
                $closesLocal = $dayStart->setTime((int) $closesHour, (int) $closesMinute, 0);

                if ($closesLocal <= $opensLocal) {
                    continue; // Overnight intervals are not allowed by the schema.
                }

                $intervals[] = [$opensLocal->setTimezone($utc)->getTimestamp(), $closesLocal->setTimezone($utc)->getTimestamp()];
            }

            $cursor = $cursor->modify('+1 day');
        }

        return $this->mergeIntervals($intervals);
    }

    /**
     * Merge overlapping intervals into a sorted, disjoint list.
     *
     * @param array<int, array{0:int,1:int}> $intervals
     * @return array<int, array{0:int,1:int}>
     */
    private function mergeIntervals(array $intervals): array
    {
        $intervals = array_values(array_filter(
            $intervals,
            static fn (array $interval): bool => (int) $interval[1] > (int) $interval[0]
        ));

        if ($intervals === []) {
            return [];
        }

        usort(
            $intervals,
            static fn (array $a, array $b): int => [$a[0], $a[1]] <=> [$b[0], $b[1]]
        );

        $merged  = [];
        $current = $intervals[0];

        for ($i = 1, $count = count($intervals); $i < $count; $i++) {
            if ($intervals[$i][0] <= $current[1]) {
                $current[1] = max($current[1], $intervals[$i][1]);
                continue;
            }

            $merged[] = $current;
            $current  = $intervals[$i];
        }

        $merged[] = $current;

        return $merged;
    }

    /**
     * Total hours covered by a list of intervals.
     *
     * @param array<int, array{0:int,1:int}> $intervals
     */
    private function totalHours(array $intervals): float
    {
        $seconds = 0;

        foreach ($intervals as $interval) {
            $seconds += (int) $interval[1] - (int) $interval[0];
        }

        return round($seconds / 3600, 1);
    }

    /**
     * Hours covered by the overlap of two lists of disjoint intervals.
     *
     * @param array<int, array{0:int,1:int}> $a
     * @param array<int, array{0:int,1:int}> $b
     */
    private function overlapHours(array $a, array $b): float
    {
        if ($a === [] || $b === []) {
            return 0.0;
        }

        $seconds = 0;

        foreach ($a as $intervalA) {
            foreach ($b as $intervalB) {
                $start = max((int) $intervalA[0], (int) $intervalB[0]);
                $end   = min((int) $intervalA[1], (int) $intervalB[1]);

                if ($end > $start) {
                    $seconds += $end - $start;
                }
            }
        }

        return round($seconds / 3600, 1);
    }

    /**
     * Hours between two clock times ('H:i:s'), used for operating intervals.
     */
    private function intervalHours(string $from, string $to): float
    {
        $fromTs = strtotime($from . ' UTC');
        $toTs   = strtotime($to . ' UTC');

        if ($fromTs === false || $toTs === false || $toTs <= $fromTs) {
            return 0.0;
        }

        return ($toTs - $fromTs) / 3600;
    }

    /**
     * Unix timestamp of a stored UTC datetime.
     */
    private function timestamp(string $utcDatetime): int
    {
        $ts = strtotime($utcDatetime . ' UTC');

        return $ts === false ? 0 : $ts;
    }

    /**
     * Minutes of a reservation interval that fall inside the reporting window.
     */
    private function clippedMinutes(string $startUtc, string $endUtc, string $windowStartUtc, string $windowEndUtc): int
    {
        $start = $this->timestamp($startUtc);
        $end   = $this->timestamp($endUtc);
        $from  = $this->timestamp($windowStartUtc);
        $to    = $this->timestamp($windowEndUtc);

        $overlapStart = max($start, $from);
        $overlapEnd   = min($end, $to);

        if ($overlapEnd <= $overlapStart) {
            return 0;
        }

        return (int) round(($overlapEnd - $overlapStart) / 60);
    }

    /* ── Result helpers ──────────────────────────────────────────── */

    private function success(array $data): array
    {
        return ['success' => true, 'message' => '', 'data' => $data];
    }

    private function failure(string $message): array
    {
        return ['success' => false, 'message' => $message, 'data' => null];
    }
}
