<?php
/**
 * app/Repositories/RoomRepository.php
 * Database access for floors, rooms, facilities, operating hours, and
 * room unavailability — Member 2.
 *
 * All queries use PDO prepared statements; no value is ever interpolated
 * into SQL text. Business rules (validation, overlap logic, status
 * transitions) live in RoomService — this class only reads and writes rows.
 *
 * === Agreed shared contract (docs/api-contracts.md § RoomRepository) ===
 *   findFloorById(int $floorId): ?array
 *   findRoomById(int $roomId): ?array
 *   findRoomForUpdate(int $roomId): ?array
 *   searchRooms(array $filters): array
 *   getRoomFacilities(int $roomId): array
 *   getOperatingHours(int $roomId): array
 *   getUnavailabilityOverlaps(int $roomId, string $startUtc, string $endUtc): array
 *
 * === Additional CRUD methods per the contract clause ===
 *   "Member 2 may add the small CRUD methods needed for floor, room,
 *    facilities, hours, and closure management."
 *
 * Transaction notice (for ReservationService / Member 3):
 *   - findRoomForUpdate() issues SELECT ... FOR UPDATE and MUST be called
 *     inside an already-started PDO transaction on this same connection.
 *   - This class never begins, commits, or rolls back transactions.
 */

class RoomRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /* ==========================================================
     * FLOORS
     * ========================================================== */

    /**
     * @param string $status Optional filter; 'active' or 'inactive'.
     * @return array         All floors ordered by floor_number ascending.
     */
    public function findAllFloors(?string $status = null): array
    {
        $sql = 'SELECT id, floor_number, name, description, status, created_at, updated_at
                FROM   floors';
        $params = [];

        if ($status !== null) {
            $sql .= ' WHERE status = :status';
            $params[':status'] = $status;
        }

        $sql .= ' ORDER BY floor_number ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array|null The floor row, or null if not found.
     */
    public function findFloorById(int $floorId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, floor_number, name, description, status, created_at, updated_at
             FROM   floors
             WHERE  id = :floor_id
             LIMIT  1'
        );
        $stmt->execute([':floor_id' => $floorId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    /**
     * Uniqueness check before inserting/updating a floor number.
     *
     * @return array|null
     */
    public function findFloorByNumber(int $floorNumber): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, floor_number, name, description, status
             FROM   floors
             WHERE  floor_number = :floor_number
             LIMIT  1'
        );
        $stmt->execute([':floor_number' => $floorNumber]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    /**
     * @param array $data Keys: floor_number, name, description, status
     * @return int        The new floor ID.
     * @throws PDOException on duplicate floor_number (service must pre-check).
     */
    public function createFloor(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO floors (floor_number, name, description, status)
             VALUES (:floor_number, :name, :description, :status)'
        );
        $stmt->execute([
            ':floor_number' => $data['floor_number'],
            ':name'         => $data['name'],
            ':description'  => $data['description'] ?? null,
            ':status'       => $data['status'] ?? 'active',
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array $data Keys: name, description  (floor_number changes are
     *                    rare; pass through this method if the team allows it)
     */
    public function updateFloor(int $floorId, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE floors
             SET    name        = :name,
                    description = :description,
                    floor_number = COALESCE(:floor_number, floor_number)
             WHERE  id = :floor_id'
        );
        return $stmt->execute([
            ':name'         => $data['name'] ?? null,
            ':description'  => $data['description'] ?? null,
            ':floor_number' => $data['floor_number'] ?? null,
            ':floor_id'     => $floorId,
        ]);
    }

    /**
     * Set 'active' or 'inactive'. Rooms are deactivated, never deleted,
     * when they hold history — same policy for floors.
     */
    public function setFloorStatus(int $floorId, string $status): bool
    {
        $stmt = $this->pdo->prepare('UPDATE floors SET status = :status WHERE id = :floor_id');
        return $stmt->execute([':status' => $status, ':floor_id' => $floorId]);
    }

    /* ==========================================================
     * ROOMS
     * ========================================================== */

    /**
     * Management listing: every room with its floor name/number.
     *
     * @param string|null $status    Optional 'active'/'inactive' filter.
     * @param int|null    $floorId   Optional filter to one floor.
     * @return array
     */
    public function findAllRooms(?string $status = null, ?int $floorId = null): array
    {
        $sql = 'SELECT r.id, r.floor_id, r.name, r.category, r.description,
                       r.capacity, r.status, r.created_at, r.updated_at,
                       f.name AS floor_name, f.floor_number
                FROM   rooms r
                JOIN   floors f ON f.id = r.floor_id';
        $conditions = [];
        $params     = [];

        if ($status !== null) {
            $conditions[] = 'r.status = :status';
            $params[':status'] = $status;
        }
        if ($floorId !== null) {
            $conditions[] = 'r.floor_id = :floor_id';
            $params[':floor_id'] = $floorId;
        }

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY f.floor_number ASC, r.name ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array|null The room row, or null if not found.
     */
    public function findRoomById(int $roomId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.id, r.floor_id, r.name, r.category, r.description,
                    r.capacity, r.status, r.created_at, r.updated_at
             FROM   rooms r
             WHERE  r.id = :room_id
             LIMIT  1'
        );
        $stmt->execute([':room_id' => $roomId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    /**
     * Room row plus its floor's status, locked for the remainder of the
     * enclosing transaction. Used by ReservationService (Member 3) right
     * before booking decisions, and by this module before availability-
     * changing administrative writes.
     *
     * IMPORTANT: must be called inside an already-started transaction.
     * FOR UPDATE locks the matching rooms rows (and the joined floors row),
     * so two transactions cannot re-validate the same room simultaneously.
     *
     * @return array|null Room row with extra key 'floor_status', or null.
     */
    public function findRoomForUpdate(int $roomId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.id, r.floor_id, r.name, r.category, r.description,
                    r.capacity, r.status, r.created_at, r.updated_at,
                    f.status AS floor_status
             FROM   rooms r
             JOIN   floors f ON f.id = r.floor_id
             WHERE  r.id = :room_id
             LIMIT  1
             FOR    UPDATE'
        );
        $stmt->execute([':room_id' => $roomId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    /**
     * @param array $data Keys: floor_id, name, category, description, capacity
     * @return int        The new room ID.
     * @throws PDOException on duplicate (floor_id, name) or invalid category.
     */
    public function createRoom(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO rooms (floor_id, name, category, description, capacity)
             VALUES (:floor_id, :name, :category, :description, :capacity)'
        );
        $stmt->execute([
            ':floor_id'    => $data['floor_id'],
            ':name'        => $data['name'],
            ':category'    => $data['category'],
            ':description' => $data['description'] ?? null,
            ':capacity'    => $data['capacity'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array $data Optional keys: name, category, description,
     *                    capacity, floor_id. Unset keys keep old values.
     */
    public function updateRoom(int $roomId, array $data): bool
    {
        $sql    = 'UPDATE rooms SET ';
        $set    = [];
        $params = [];

        if (array_key_exists('name', $data)) {
            $set[] = 'name = :name';
            $params[':name'] = $data['name'];
        }
        if (array_key_exists('category', $data)) {
            $set[] = 'category = :category';
            $params[':category'] = $data['category'];
        }
        if (array_key_exists('description', $data)) {
            $set[] = 'description = :description';
            $params[':description'] = $data['description'];
        }
        if (array_key_exists('capacity', $data)) {
            $set[] = 'capacity = :capacity';
            $params[':capacity'] = $data['capacity'];
        }
        if (array_key_exists('floor_id', $data)) {
            $set[] = 'floor_id = :floor_id';
            $params[':floor_id'] = $data['floor_id'];
        }

        if ($set === []) {
            return true; // nothing to change
        }

        $sql .= implode(', ', $set) . ' WHERE id = :room_id';
        $params[':room_id'] = $roomId;

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function setRoomStatus(int $roomId, string $status): bool
    {
        $stmt = $this->pdo->prepare('UPDATE rooms SET status = :status WHERE id = :room_id');
        return $stmt->execute([':status' => $status, ':room_id' => $roomId]);
    }

    /* ==========================================================
     * FACILITIES + ROOM_FACILITIES
     * ========================================================== */

    /**
     * @return array Every facility: id, name, description.
     */
    public function findAllFacilities(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, description, created_at
             FROM   facilities
             ORDER  BY name ASC'
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array|null
     */
    public function findFacilityById(int $facilityId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, description
             FROM   facilities
             WHERE  id = :facility_id
             LIMIT  1'
        );
        $stmt->execute([':facility_id' => $facilityId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    /**
     * Uniqueness check by facility name (candidate/option-list friendly).
     *
     * @return array|null
     */
    public function findFacilityByName(string $name): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, description
             FROM   facilities
             WHERE  name = :name
             LIMIT  1'
        );
        $stmt->execute([':name' => $name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    /**
     * @param array $data Keys: name, description
     */
    public function createFacility(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO facilities (name, description)
             VALUES (:name, :description)'
        );
        $stmt->execute([
            ':name'        => $data['name'],
            ':description' => $data['description'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @return array The facilities assigned to one room: id, name, description.
     */
    public function getRoomFacilities(int $roomId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT fc.id, fc.name, fc.description
             FROM   room_facilities rf
             JOIN   facilities fc ON fc.id = rf.facility_id
             WHERE  rf.room_id = :room_id
             ORDER  BY fc.name ASC'
        );
        $stmt->execute([':room_id' => $roomId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Link one facility to one room. Safe to call again — the INSERT is
     * ignored if the pair already exists (composite primary key).
     */
    public function assignRoomFacility(int $roomId, int $facilityId): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO room_facilities (room_id, facility_id)
             VALUES (:room_id, :facility_id)'
        );
        return $stmt->execute([':room_id' => $roomId, ':facility_id' => $facilityId]);
    }

    /**
     * Detach one facility from one room.
     */
    public function removeRoomFacility(int $roomId, int $facilityId): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM room_facilities
             WHERE  room_id    = :room_id
             AND    facility_id = :facility_id'
        );
        return $stmt->execute([':room_id' => $roomId, ':facility_id' => $facilityId]);
    }

    /**
     * Remove ALL facilities from one room (used inside RoomService's
     * setRoomFacilities() replace-in-transaction flow).
     */
    public function removeAllRoomFacilities(int $roomId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM room_facilities WHERE room_id = :room_id');
        return $stmt->execute([':room_id' => $roomId]);
    }

    /* ==========================================================
     * OPERATING HOURS (local Africa/Cairo TIME values)
     * ========================================================== */

    /**
     * @return array Every interval for the room: day_of_week (0-6),
     *               opens_at, closes_at. Ordered by day then opens_at.
     */
    public function getOperatingHours(int $roomId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, room_id, day_of_week, opens_at, closes_at
             FROM   room_operating_hours
             WHERE  room_id = :room_id
             ORDER  BY day_of_week ASC, opens_at ASC'
        );
        $stmt->execute([':room_id' => $roomId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * One opening interval for a specific room/day, if any matches the
     * requested local window entirely. The database already enforces
     * opens_at < closes_at via a CHECK constraint; containment (the whole
     * booking inside one interval) is evaluated here, explicitly.
     *
     * @param string $startLocal 'HH:MM:SS' requested opening end of booking.
     * @param string $endLocal   'HH:MM:SS' requested closing end of booking.
     * @return array|null        Matching interval row, or null.
     */
    public function findOperatingIntervalContaining(
        int $roomId,
        int $dayOfWeek,
        string $startLocal,
        string $endLocal
    ): ?array {
        $stmt = $this->pdo->prepare(
            'SELECT id, day_of_week, opens_at, closes_at
             FROM   room_operating_hours
             WHERE  room_id     = :room_id
             AND    day_of_week = :day_of_week
             AND    opens_at   <= :start_local
             AND    closes_at  >= :end_local
             LIMIT  1'
        );
        $stmt->execute([
            ':room_id'     => $roomId,
            ':day_of_week' => $dayOfWeek,
            ':start_local' => $startLocal,
            ':end_local'   => $endLocal,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    /**
     * @param array $data Keys: room_id, day_of_week, opens_at, closes_at
     * @return int        The new interval ID.
     * @throws PDOException when opens_at >= closes_at (DB CHECK constraint).
     */
    public function createOperatingInterval(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO room_operating_hours (room_id, day_of_week, opens_at, closes_at)
             VALUES (:room_id, :day_of_week, :opens_at, :closes_at)'
        );
        $stmt->execute([
            ':room_id'     => $data['room_id'],
            ':day_of_week' => $data['day_of_week'],
            ':opens_at'    => $data['opens_at'],
            ':closes_at'   => $data['closes_at'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Used by RoomService::replaceOperatingHours() inside a transaction:
     * clear the room's schedule, then insert the validated new intervals.
     */
    public function deleteOperatingHours(int $roomId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM room_operating_hours WHERE room_id = :room_id');
        return $stmt->execute([':room_id' => $roomId]);
    }

    public function deleteOperatingInterval(int $intervalId, int $roomId): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM room_operating_hours
             WHERE  id      = :interval_id
             AND    room_id = :room_id'
        );
        return $stmt->execute([':interval_id' => $intervalId, ':room_id' => $roomId]);
    }

    /* ==========================================================
     * UNAVAILABILITY (starts_at / ends_at stored in UTC)
     * ========================================================== */

    /**
     * Closure periods for every room (or one room) that INTERSECT the
     * given UTC window, using the half-open overlap rule:
     *
     *     existing.starts_at < requested_end_utc
     * AND existing.ends_at   > requested_start_utc
     *
     * (Adjacent periods — one ending exactly when the requested window
     * begins — do NOT overlap and are therefore excluded.)
     *
     * @param string     $startUtc   'Y-m-d H:i:s' UTC
     * @param string     $endUtc     'Y-m-d H:i:s' UTC
     * @param int|null   $roomId     null = all rooms
     * @param array|null $timeRange  Optional ['start' => UTC, 'end' => UTC]
     *                               filter for management listing.
     * @return array Closure rows overlapping the window.
     */
    public function getUnavailabilityOverlaps(
        int $roomId,
        string $startUtc,
        string $endUtc
    ): array {
        $stmt = $this->pdo->prepare(
            'SELECT id, room_id, starts_at, ends_at, reason, created_by, created_at
             FROM   room_unavailability
             WHERE  room_id   = :room_id
             AND    starts_at < :end_utc
             AND    ends_at   > :start_utc
             ORDER  BY starts_at ASC'
        );
        $stmt->execute([
            ':room_id'    => $roomId,
            ':start_utc'  => $startUtc,
            ':end_utc'    => $endUtc,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Management listing for the admin closure screens.
     *
     * @param int|null $roomId    null = all rooms (join room name).
     * @return array              Every closure: room_id, room_name, dates,
     *                            reason, created_by.
     */
    public function getAllUnavailability(?int $roomId = null): array
    {
        $sql = 'SELECT u.id, u.room_id, u.starts_at, u.ends_at, u.reason, u.created_by, u.created_at,
                       r.name AS room_name
                FROM   room_unavailability u
                JOIN   rooms r ON r.id = u.room_id';
        $params = [];

        if ($roomId !== null) {
            $sql .= ' WHERE u.room_id = :room_id';
            $params[':room_id'] = $roomId;
        }

        $sql .= ' ORDER BY starts_at ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param array $data Keys: room_id, starts_at, ends_at, reason, created_by
     * @return int        The new closure ID.
     * @throws PDOException when starts_at >= ends_at (DB CHECK constraint).
     */
    public function createUnavailability(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO room_unavailability (room_id, starts_at, ends_at, reason, created_by)
             VALUES (:room_id, :starts_at, :ends_at, :reason, :created_by)'
        );
        $stmt->execute([
            ':room_id'    => $data['room_id'],
            ':starts_at'  => $data['starts_at'],
            ':ends_at'    => $data['ends_at'],
            ':reason'     => $data['reason'],
            ':created_by' => $data['created_by'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateUnavailability(
        int $unavailabilityId,
        array $data,
        int $adminId
    ): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE room_unavailability
             SET    starts_at  = :starts_at,
                    ends_at    = :ends_at,
                    reason     = :reason,
                    created_by = :created_by
             WHERE  id = :unavailability_id'
        );
        return $stmt->execute([
            ':starts_at'          => $data['starts_at'],
            ':ends_at'            => $data['ends_at'],
            ':reason'             => $data['reason'],
            ':created_by'         => $adminId,
            ':unavailability_id'  => $unavailabilityId,
        ]);
    }

    public function deleteUnavailability(int $unavailabilityId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM room_unavailability WHERE id = :id');
        return $stmt->execute([':id' => $unavailabilityId]);
    }

    /* ==========================================================
     * SEARCH — room discovery for the student module
     * ========================================================== */

    /**
     * Approximate availability search. NOT authorization to book:
     * ReservationService revalidates inside a transaction before any write.
     *
     * Filters (all optional, combined with AND):
     *   category     (string|null)  'individual_study' | 'group_study' | 'lounge'
     *   min_capacity (int|null)
     *   facility_ids (int[]|null)   room must have ALL listed facilities
     *   start_utc    (string|null)  requested interval start 'Y-m-d H:i:s' UTC
     *   end_utc      (string|null)  requested interval end   'Y-m-d H:i:s' UTC
     *   day_of_week  (int|null)     0-6, from the requested LOCAL date
     *   start_local  (string|null)  'HH:MM'/'HH:MM:SS' requested local start
     *   end_local    (string|null)  'HH:MM'/'HH:MM:SS' requested local end
     *
     * Rules implemented:
     *   - room AND its floor must be active;
     *   - when a UTC interval is supplied, the room must not intersect:
     *       1) room_unavailability periods,
     *       2) reservations with status pending or confirmed,
     *       3) reservation_modifications with status pending
     *     (half-open overlap: existing < requested_end AND existing > requested_start,
     *     so adjacent intervals do not collide);
     *   - when day/time is supplied, at least one operating interval must
     *     CONTAIN the whole requested window (fits inside a single interval);
     *   - when facility_ids are supplied, every listed facility must be present.
     *
     * @return array Room rows + floor_name/floor_number.
     */
    public function searchRooms(array $filters): array
    {
        $hasInterval = isset($filters['start_utc'], $filters['end_utc']);
        $hasHours    = isset($filters['day_of_week'], $filters['start_local'], $filters['end_local']);

        $sql = 'SELECT DISTINCT r.id, r.floor_id, r.name, r.category, r.description,
                       r.capacity, r.status,
                       f.name AS floor_name, f.floor_number
                FROM   rooms r
                JOIN   floors f ON f.id = r.floor_id';
        $conditions = ['r.status = :room_status', 'f.status = :floor_status'];
        $params     = [
            ':room_status'  => 'active',
            ':floor_status' => 'active',
        ];

        if (isset($filters['category'])) {
            $conditions[] = 'r.category = :category';
            $params[':category'] = $filters['category'];
        }

        if (isset($filters['min_capacity'])) {
            $conditions[] = 'r.capacity >= :min_capacity';
            $params[':min_capacity'] = (int) $filters['min_capacity'];
        }

        if ($hasHours) {
            // Whole window must fit INSIDE one operating interval.
            $conditions[] = 'EXISTS (
                SELECT 1 FROM room_operating_hours oh
                WHERE  oh.room_id     = r.id
                AND    oh.day_of_week = :day_of_week
                AND    oh.opens_at   <= :start_local
                AND    oh.closes_at  >= :end_local
            )';
            $params[':day_of_week'] = (int) $filters['day_of_week'];
            $params[':start_local'] = $filters['start_local'];
            $params[':end_local']   = $filters['end_local'];
        }

        if ($hasInterval) {
            // 1) Closures (half-open overlap — adjacent does not collide)
            $conditions[] = 'NOT EXISTS (
                SELECT 1 FROM room_unavailability u
                WHERE  u.room_id   = r.id
                AND    u.starts_at < :closure_end_utc
                AND    u.ends_at   > :closure_start_utc
            )';

            // 2) Blocking reservations: pending OR confirmed
            $conditions[] = 'NOT EXISTS (
                SELECT 1 FROM reservations res
                WHERE  res.room_id   = r.id
                AND    res.status IN (:status_pending, :status_confirmed)
                AND    res.starts_at < :res_end_utc
                AND    res.ends_at   > :res_start_utc
            )';

            // 3) Pending modification proposals block their proposed interval
            $conditions[] = 'NOT EXISTS (
                SELECT 1 FROM reservation_modifications prop
                WHERE  prop.proposed_room_id      = r.id
                AND    prop.status                = :status_mod_pending
                AND    prop.proposed_starts_at    < :prop_end_utc
                AND    prop.proposed_ends_at      > :prop_start_utc
            )';

            $params[':closure_start_utc']  = $filters['start_utc'];
            $params[':closure_end_utc']    = $filters['end_utc'];
            $params[':res_start_utc']      = $filters['start_utc'];
            $params[':res_end_utc']        = $filters['end_utc'];
            $params[':prop_start_utc']     = $filters['start_utc'];
            $params[':prop_end_utc']       = $filters['end_utc'];
            $params[':status_pending']     = 'pending';
            $params[':status_confirmed']   = 'confirmed';
            $params[':status_mod_pending'] = 'pending';
        }

        if (!empty($filters['facility_ids']) && is_array($filters['facility_ids'])) {
            $facilityIds = array_values(array_map('intval', $filters['facility_ids']));
            $binds = [];
            foreach ($facilityIds as $i => $facilityId) {
                $key             = ":facility_id_{$i}";
                $binds[$key]     = $facilityId;
                $params[$key]    = $facilityId;
            }
            $inList = implode(', ', array_keys($binds));
            // COUNT(*) over a unique pair (room_id, facility_id) equals the
            // number of requested facilities the room already carries.
            $conditions[] = "(
                SELECT COUNT(*) FROM room_facilities rf
                WHERE  rf.room_id = r.id
                AND    rf.facility_id IN ({$inList})
            ) = :facility_count";
            $params[':facility_count'] = count($facilityIds);
        }

        $sql .= ' WHERE ' . implode(' AND ', $conditions);
        $sql .= ' ORDER BY r.capacity ASC, r.name ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
