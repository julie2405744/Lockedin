<?php
/**
 * app/Repositories/AdminReviewRepository.php
 * Read-only queries for the admin review queues — Member 5.
 *
 * Ownership: Member 5 (admin dashboard and reviews). See docs/member-tasks.md §8.
 *
 * These queries only read. They exist because the admin screens need to show
 * reservations and modification requests, and because docs/api-contracts.md §4
 * explicitly allows the repository layer to be extended for "admin queues".
 *
 * No status transition happens here. Approving, rejecting, confirming or
 * cancelling a reservation always goes through ReservationService (Member 3)
 * so that there is exactly one implementation of the booking rules.
 */

class AdminReviewRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Reservations waiting for an administrator decision.
     *
     * Filters (all optional):
     *   status   (string|null) defaults to 'pending'
     *   room_id  (int|null)
     *   limit    (int|null)
     *
     * @return array<int, array<string, mixed>>
     */
    public function findReservationsForReview(array $filters = []): array
    {
        $status = trim((string) ($filters['status'] ?? 'pending'));
        if ($status === '') {
            $status = 'pending';
        }

        $sql = 'SELECT res.id,
                       res.student_id,
                       res.room_id,
                       res.starts_at,
                       res.ends_at,
                       res.status,
                       res.reviewed_at,
                       res.reviewed_by,
                       res.cancellation_reason,
                       res.created_at,
                       u.name  AS student_name,
                       u.email AS student_email,
                       r.name  AS room_name,
                       r.category,
                       r.capacity,
                       f.name  AS floor_name,
                       f.floor_number
                  FROM reservations res
                  JOIN users u ON u.id = res.student_id
                  JOIN rooms r ON r.id = res.room_id
                  JOIN floors f ON f.id = r.floor_id
                 WHERE res.status = :status';

        $params = [':status' => $status];

        if (!empty($filters['room_id'])) {
            $sql .= ' AND res.room_id = :room_id';
            $params[':room_id'] = (int) $filters['room_id'];
        }

        $sql .= ' ORDER BY res.starts_at ASC, res.id ASC';

        if (!empty($filters['limit'])) {
            $sql .= ' LIMIT ' . (int) $filters['limit'];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * One reservation with the room, floor and student it belongs to.
     *
     * @return array<string, mixed>|null
     */
    public function findReservationById(int $reservationId): ?array
    {
        $sql = 'SELECT res.id,
                       res.student_id,
                       res.room_id,
                       res.starts_at,
                       res.ends_at,
                       res.status,
                       res.reviewed_by,
                       res.reviewed_at,
                       res.cancellation_reason,
                       res.created_at,
                       res.updated_at,
                       u.name  AS student_name,
                       u.email AS student_email,
                       r.name  AS room_name,
                       r.category,
                       r.capacity,
                       f.name  AS floor_name,
                       f.floor_number
                  FROM reservations res
                  JOIN users u ON u.id = res.student_id
                  JOIN rooms r ON r.id = res.room_id
                  JOIN floors f ON f.id = r.floor_id
                 WHERE res.id = :id
                 LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $reservationId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /**
     * Modification requests waiting for an administrator decision.
     *
     * Each row carries the original reservation and the proposed room/time so
     * that the queue can show both without a second query.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findModificationsForReview(array $filters = []): array
    {
        $status = trim((string) ($filters['status'] ?? 'pending'));
        if ($status === '') {
            $status = 'pending';
        }

        $sql = 'SELECT m.id,
                       m.reservation_id,
                       m.proposed_room_id,
                       m.proposed_starts_at,
                       m.proposed_ends_at,
                       m.status,
                       m.requested_at,
                       m.reviewed_at,
                       m.reviewed_by,
                       m.rejection_reason,
                       m.invalidation_reason,
                       res.student_id,
                       res.starts_at     AS original_starts_at,
                       res.ends_at       AS original_ends_at,
                       res.status        AS reservation_status,
                       u.name            AS student_name,
                       u.email           AS student_email,
                       orig.name         AS original_room_name,
                       orig.category     AS original_category,
                       orig_floor.name   AS original_floor_name,
                       orig_floor.floor_number AS original_floor_number,
                       prop.name         AS proposed_room_name,
                       prop.category     AS proposed_category,
                       prop_floor.name   AS proposed_floor_name,
                       prop_floor.floor_number AS proposed_floor_number
                  FROM reservation_modifications m
                  JOIN reservations res ON res.id = m.reservation_id
                  JOIN users u         ON u.id  = res.student_id
                  JOIN rooms orig      ON orig.id = res.room_id
                  JOIN floors orig_floor ON orig_floor.id = orig.floor_id
                  JOIN rooms prop      ON prop.id = m.proposed_room_id
                  JOIN floors prop_floor ON prop_floor.id = prop.floor_id
                 WHERE m.status = :status';

        $params = [':status' => $status];

        $sql .= ' ORDER BY m.requested_at ASC, m.id ASC';

        if (!empty($filters['limit'])) {
            $sql .= ' LIMIT ' . (int) $filters['limit'];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * One modification request with the original reservation and the proposal.
     *
     * @return array<string, mixed>|null
     */
    public function findModificationById(int $modificationId): ?array
    {
        $sql = 'SELECT m.id,
                       m.reservation_id,
                       m.proposed_room_id,
                       m.proposed_starts_at,
                       m.proposed_ends_at,
                       m.status,
                       m.requested_at,
                       m.reviewed_at,
                       m.reviewed_by,
                       m.rejection_reason,
                       m.invalidation_reason,
                       res.student_id,
                       res.starts_at     AS original_starts_at,
                       res.ends_at       AS original_ends_at,
                       res.status        AS reservation_status,
                       u.name            AS student_name,
                       u.email           AS student_email,
                       orig.name         AS original_room_name,
                       orig.category     AS original_category,
                       orig_floor.name   AS original_floor_name,
                       orig_floor.floor_number AS original_floor_number,
                       prop.name         AS proposed_room_name,
                       prop.category     AS proposed_category,
                       prop_floor.name   AS proposed_floor_name,
                       prop_floor.floor_number AS proposed_floor_number
                  FROM reservation_modifications m
                  JOIN reservations res ON res.id = m.reservation_id
                  JOIN users u         ON u.id  = res.student_id
                  JOIN rooms orig      ON orig.id = res.room_id
                  JOIN floors orig_floor ON orig_floor.id = orig.floor_id
                  JOIN rooms prop      ON prop.id = m.proposed_room_id
                  JOIN floors prop_floor ON prop_floor.id = prop.floor_id
                 WHERE m.id = :id
                 LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $modificationId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /**
     * The most recent modification request attached to a reservation.
     *
     * @return array<string, mixed>|null
     */
    public function findLatestModificationForReservation(int $reservationId): ?array
    {
        $sql = 'SELECT id, reservation_id, proposed_room_id, proposed_starts_at,
                       proposed_ends_at, status, requested_at, reviewed_at,
                       rejection_reason, invalidation_reason
                  FROM reservation_modifications
                 WHERE reservation_id = :reservation_id
                 ORDER BY (status = \'pending\') DESC, requested_at DESC, id DESC
                 LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':reservation_id' => $reservationId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }
}
