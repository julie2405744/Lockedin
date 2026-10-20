<?php
/**
 * app/Repositories/ReportRepository.php
 * Read-only data access for the admin dashboard and the report screens — Member 5.
 *
 * Ownership: Member 5 (admin dashboard and reports). See docs/member-tasks.md §8.
 *
 * This repository only SELECTs. It contains no booking rules, no status
 * transitions and no writes of any kind — every reservation decision stays in
 * ReservationService (Member 3), and every report calculation stays in
 * ReportService (Member 5).
 *
 * All timestamps are stored in UTC. Operating hours are local (Africa/Cairo)
 * weekly intervals; the conversion between the two is done in ReportService,
 * never in SQL.
 */

class ReportRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /* ── Dashboard counters ──────────────────────────────────────── */

    /**
     * Count reservations grouped by status.
     *
     * @return array{pending:int,confirmed:int,rejected:int,cancelled:int}
     */
    public function countReservationsByStatus(): array
    {
        $counts = ['pending' => 0, 'confirmed' => 0, 'rejected' => 0, 'cancelled' => 0];

        $rows = $this->pdo->query(
            'SELECT status, COUNT(*) AS total
               FROM reservations
              GROUP BY status'
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $status = (string) $row['status'];
            if (array_key_exists($status, $counts)) {
                $counts[$status] = (int) $row['total'];
            }
        }

        return $counts;
    }

    /**
     * Count modification requests grouped by status.
     *
     * @return array{pending:int,approved:int,rejected:int,invalidated:int}
     */
    public function countModificationsByStatus(): array
    {
        $counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'invalidated' => 0];

        $rows = $this->pdo->query(
            'SELECT status, COUNT(*) AS total
               FROM reservation_modifications
              GROUP BY status'
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $status = (string) $row['status'];
            if (array_key_exists($status, $counts)) {
                $counts[$status] = (int) $row['total'];
            }
        }

        return $counts;
    }

    /** Number of active rooms. */
    public function countActiveRooms(): int
    {
        return (int) $this->pdo->query(
            "SELECT COUNT(*) FROM rooms WHERE status = 'active'"
        )->fetchColumn();
    }

    /** Number of active floors. */
    public function countActiveFloors(): int
    {
        return (int) $this->pdo->query(
            "SELECT COUNT(*) FROM floors WHERE status = 'active'"
        )->fetchColumn();
    }

    /**
     * Count reservations that overlap a UTC interval, optionally per status.
     *
     * @param string      $startUtc  Inclusive range start, 'Y-m-d H:i:s' UTC.
     * @param string      $endUtc    Inclusive range end,   'Y-m-d H:i:s' UTC.
     * @param string|null $status    Optional single status filter.
     */
    public function countReservationsBetween(string $startUtc, string $endUtc, ?string $status = null): int
    {
        $sql = 'SELECT COUNT(*)
                  FROM reservations
                 WHERE starts_at <= :end_utc
                   AND ends_at   >= :start_utc';
        $params = [':start_utc' => $startUtc, ':end_utc' => $endUtc];

        if ($status !== null) {
            $sql .= ' AND status = :status';
            $params[':status'] = $status;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /* ── Report rows ─────────────────────────────────────────────── */

    /**
     * Reservations that overlap a UTC interval, joined for display.
     *
     * Filters (all optional):
     *   status  (string|null) reservation status
     *   room_id (int|null)    limit to one room
     *
     * @return array<int, array<string, mixed>>
     */
    public function findReservationsBetween(string $startUtc, string $endUtc, array $filters = []): array
    {
        $sql = 'SELECT res.id,
                       res.student_id,
                       res.room_id,
                       res.starts_at,
                       res.ends_at,
                       res.status,
                       res.reviewed_at,
                       res.cancellation_reason,
                       res.created_at,
                       u.name  AS student_name,
                       u.email AS student_email,
                       r.name  AS room_name,
                       r.category,
                       f.name  AS floor_name,
                       f.floor_number
                  FROM reservations res
                  JOIN users u ON u.id = res.student_id
                  JOIN rooms r ON r.id = res.room_id
                  JOIN floors f ON f.id = r.floor_id
                 WHERE res.starts_at <= :end_utc
                   AND res.ends_at   >= :start_utc';

        $params = [':start_utc' => $startUtc, ':end_utc' => $endUtc];

        if (!empty($filters['status'])) {
            $sql .= ' AND res.status = :status';
            $params[':status'] = (string) $filters['status'];
        }

        if (!empty($filters['room_id'])) {
            $sql .= ' AND res.room_id = :room_id';
            $params[':room_id'] = (int) $filters['room_id'];
        }

        $sql .= ' ORDER BY res.starts_at ASC, res.id ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Raw reservation intervals for report calculations.
     *
     * @param string[] $statuses statuses to include
     * @return array<int, array{room_id:int,starts_at:string,ends_at:string,status:string}>
     */
    public function findReservationIntervals(
        string $startUtc,
        string $endUtc,
        array $statuses,
        ?int $roomId = null
    ): array {
        $placeholders = [];
        $params      = [':start_utc' => $startUtc, ':end_utc' => $endUtc];

        foreach (array_values($statuses) as $index => $status) {
            $key                      = ':status_' . $index;
            $placeholders[]           = $key;
            $params[$key]             = (string) $status;
        }

        $sql = 'SELECT room_id, starts_at, ends_at, status
                  FROM reservations
                 WHERE starts_at <= :end_utc
                   AND ends_at   >= :start_utc
                   AND status IN (' . implode(', ', $placeholders) . ')';

        if ($roomId !== null) {
            $sql .= ' AND room_id = :room_id';
            $params[':room_id'] = $roomId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Every room with its floor, ordered for display.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findRoomsWithFloors(): array
    {
        $sql = 'SELECT r.id,
                       r.name,
                       r.category,
                       r.capacity,
                       r.status,
                       f.name        AS floor_name,
                       f.floor_number,
                       f.status      AS floor_status
                  FROM rooms r
                  JOIN floors f ON f.id = r.floor_id
                 ORDER BY f.floor_number ASC, r.name ASC';

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Weekly operating hours for every room.
     *
     * @return array<int, array{room_id:int,day_of_week:int,opens_at:string,closes_at:string}>
     */
    public function findOperatingHours(): array
    {
        $sql = 'SELECT room_id, day_of_week, opens_at, closes_at
                  FROM room_operating_hours
                 ORDER BY room_id ASC, day_of_week ASC, opens_at ASC';

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Closure periods that overlap a UTC interval.
     *
     * @return array<int, array{room_id:int,starts_at:string,ends_at:string,reason:?string}>
     */
    public function findClosuresBetween(string $startUtc, string $endUtc): array
    {
        $sql = 'SELECT room_id, starts_at, ends_at, reason
                  FROM room_unavailability
                 WHERE starts_at <= :end_utc
                   AND ends_at   >= :start_utc
                 ORDER BY room_id ASC, starts_at ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':start_utc' => $startUtc, ':end_utc' => $endUtc]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
