# API Contracts — Study Room Booking System

**Project:** Workspace  
**Stack:** PHP, MySQL/MariaDB, HTML5, CSS3, JavaScript, XAMPP  
**Architecture:** Server-rendered PHP with shared services and repositories  
**Database:** `workspace_db`

## 1. Purpose and architecture rules

This document defines how the five team members' modules communicate. It is the shared contract for implementation.

The application does not use React, Node.js, Laravel, microservices, or a REST API. Forms submit to PHP pages/controllers, which call shared services. Services use repositories to access the database.

### Request flow

```text
PHP page / form handler
        |
        v
Authentication and authorization checks
        |
        v
Shared service
        |
        v
Repository (PDO queries)
        |
        v
MySQL / MariaDB
```

### Responsibilities

- **Pages/controllers:** Read requests, validate basic input format, call services, and render results.
- **Auth / AuthService:** Manage login, registration, sessions, and role checks.
- **RoomService:** Apply room, floor, operating-hours, facilities, and closure-management rules.
- **ReservationService:** Centralize every reservation and modification rule, including transactions and conflict checks.
- **ReportService:** Calculate admin reports using repository queries.
- **Repositories:** Run prepared SQL queries. They must not make independent booking-policy decisions.
- **Views:** Display data and validation messages. They must not execute SQL or implement business rules.

Do not create a separate service class for every page or operation.

## 2. Shared conventions

### 2.1 Database and naming

Use the table and column names in `database.sql` exactly:

- `users`
- `floors`
- `rooms`
- `facilities`
- `room_facilities`
- `room_operating_hours`
- `room_unavailability`
- `reservations`
- `reservation_modifications`

Use `id` as the primary key in entity tables. All related IDs are unsigned integers.

Room categories:

- `individual_study`
- `group_study`
- `lounge`

Reservation statuses:

- `pending`
- `confirmed`
- `rejected`
- `cancelled`

Modification statuses:

- `pending`
- `approved`
- `rejected`
- `invalidated`

Floor and room statuses:

- `active`
- `inactive`

Do not introduce alternative spellings, extra categories, or additional status values without agreement from the whole team and an update to all shared documents.

### 2.2 Time handling

- Store reservation, modification proposal, and unavailability timestamps in UTC.
- Use `Africa/Cairo` when interpreting operating hours and displaying local dates/times.
- Convert local form input to UTC before saving.
- Convert UTC timestamps to `Africa/Cairo` before displaying them.
- Do not hardcode the Egypt UTC offset.
- Set the PDO database session timezone to UTC.
- `room_operating_hours.day_of_week` uses `0 = Sunday` through `6 = Saturday`.

### 2.3 Database access

- Use the shared PDO connection from `config/database.php`.
- Use prepared statements for user-supplied values.
- Do not create another PDO connection inside individual modules.
- Do not concatenate user input into SQL.
- Do not expose database errors, stack traces, or SQL statements to students.

### 2.4 Return and error conventions

Services should return data for successful read operations and a consistent result for operations that can fail.

Recommended operation result:

```php
[
    'success' => true,  // or false
    'message' => 'Human-readable result',
    'data' => null      // optional result data
]
```

Use the same convention across all modules. Do not mix unrelated return formats for similar operations.

Expected failures include:

- `validation_error`
- `unauthenticated`
- `forbidden`
- `not_found`
- `conflict`
- `invalid_status`
- `database_error`

These may be represented through a shared result structure or a small agreed exception hierarchy. Do not create a large custom exception system for this project.

A validation or conflict failure must not leave partial database changes.

## 3. Shared service contracts

These are the logical method contracts. Method names can be adjusted once by the team, but their responsibilities and rules must remain the same.

### 3.1 AuthService

**Owner:** Member 1

Responsibilities:

- Register students.
- Authenticate users.
- Retrieve the current authenticated user.
- Validate role permissions where appropriate.
- Update a user's own permitted profile fields if profile editing is implemented.

Logical methods:

```php
registerStudent(array $input): array
login(string $email, string $password): array
logout(): void
getCurrentUser(): ?array
requireLogin(): void
requireRole(string $role): void
```

Rules:

- Public registration always creates a `student`.
- Never accept `role = admin` from a registration form.
- Store passwords with `password_hash()`.
- Verify passwords with `password_verify()`.
- Regenerate the session ID after successful login.
- Protect state-changing forms with CSRF tokens.
- Logout must invalidate the session.
- Admin authorization must be checked on the server, not merely by hiding admin links.

`Auth.php` may provide shared session/authentication helpers. Avoid duplicating login logic between `Auth.php` and `AuthService.php`.

### 3.2 RoomService

**Owner:** Member 2

Responsibilities:

- Create and update floors.
- Activate/deactivate floors.
- Create and update rooms.
- Activate/deactivate rooms.
- Manage facilities assigned to rooms.
- Manage room operating hours.
- Manage room unavailability periods.

Logical methods:

```php
createFloor(array $input): array
updateFloor(int $floorId, array $input): array
setFloorStatus(int $floorId, string $status): array

createRoom(array $input): array
updateRoom(int $roomId, array $input): array
setRoomStatus(int $roomId, string $status): array

setRoomFacilities(int $roomId, array $facilityIds): array
replaceOperatingHours(int $roomId, array $intervals): array

createUnavailability(int $roomId, array $input, int $adminId): array
updateUnavailability(int $id, array $input, int $adminId): array
removeUnavailability(int $id, int $adminId): array

getRoomDetails(int $roomId): ?array
searchAvailableRooms(array $filters): array
```

Rules:

- Only admins can perform management operations.
- A room belongs to one floor.
- A room has one of the three agreed categories.
- Capacity must be a positive integer.
- Floor and room deletion should not destroy booking history. Use inactive status.
- Every operating interval must have `opens_at < closes_at`.
- Overnight intervals are not allowed.
- Multiple intervals per room/day are allowed, but they must not overlap.
- Availability searches must account for active room/floor status, operating hours, closures, pending reservations, confirmed reservations, and pending modification proposals.
- Availability search is for discovery only. `ReservationService` must revalidate before writing.
- Schedule, closure, or active-status changes must not silently delete or invalidate historical reservations.
- Operations changing room availability must coordinate with `ReservationService` through the agreed transaction and room-locking approach.

### 3.3 ReservationService

**Owner:** Member 3

This is the authoritative booking-policy service. Every module that creates, reviews, changes, or cancels a reservation must use it.

Logical methods:

```php
createReservation(int $studentId, array $input): array

getStudentReservations(int $studentId, array $filters = []): array
getReservationDetails(int $reservationId, int $actorId): ?array

approveReservation(int $reservationId, int $adminId): array
rejectReservation(int $reservationId, int $adminId, ?string $reason = null): array

cancelReservation(int $reservationId, int $studentId, ?string $reason = null): array

requestModification(int $reservationId, int $studentId, array $input): array
approveModification(int $modificationId, int $adminId): array
rejectModification(int $modificationId, int $adminId, ?string $reason = null): array

getPendingReservations(array $filters = []): array
getPendingModifications(array $filters = []): array
```

These methods are logical contracts, not permission to duplicate the rules in controllers or views.

#### Reservation creation

`createReservation()` must:

1. Verify that the actor is a student.
2. Validate the requested room, start, end, and duration.
3. Convert submitted local date/time values to UTC.
4. Start a transaction and lock the relevant room row using `SELECT ... FOR UPDATE`.
5. Re-read and validate the room and floor status, operating hours, and unavailability.
6. Check for overlapping blocking reservations and pending modification proposals.
7. Insert a reservation with status `pending` only if all checks pass.
8. Commit the transaction; otherwise roll back.

Policy:

- Duration is at least one hour and at most twelve hours.
- Start time cannot be in the past.
- Start time may be up to seven days ahead, inclusive.
- The entire reservation must fit inside one operating interval.
- A pending or confirmed reservation blocks overlapping intervals.
- Rejected and cancelled reservations do not block.
- A pending modification proposal blocks its proposed interval.
- Adjacent bookings are allowed.
- Overlap condition:

```sql
existing.starts_at < requested.ends_at
AND existing.ends_at > requested.starts_at
```

#### Approval and rejection

- Only admins can approve or reject pending reservations.
- Only a `pending` reservation can be approved or rejected.
- Approval must revalidate availability inside a transaction.
- Approval changes the status to `confirmed`.
- Rejection changes the status to `rejected`.
- Record the reviewing admin and review time.
- A failed validation must not leave the reservation partially changed.

#### Cancellation

- A student may cancel only their own reservation.
- Cancellation must occur at least two hours before the reservation's original start.
- A `pending` or `confirmed` reservation can be cancelled.
- `rejected` and already `cancelled` reservations are terminal.
- Cancellation updates the status; it does not delete the row.
- If a pending modification exists, cancellation and invalidation of that modification must happen atomically.
- Admin actions must follow the same agreed status-transition rules. Do not introduce additional cancellation behavior without team agreement.

#### Modification request

- Only the owner of the reservation can request a modification.
- The request must be submitted at least two hours before the original reservation start.
- Only a `confirmed` reservation may be modified.
- Only one pending modification may exist for a reservation.
- Proposed room and time must pass the same duration, advance-booking, operating-hours, room/floor status, closure, and conflict checks as a new booking.
- The original confirmed reservation remains unchanged and continues blocking its original interval.
- The proposal blocks its proposed interval.
- Conflict checking excludes the original reservation itself, so a proposal may overlap its own original interval.
- A pending proposal for another reservation still blocks the proposed interval.

#### Modification approval

Within a transaction:

1. Lock affected room rows in ascending room ID order.
2. Re-read the reservation and pending modification.
3. Revalidate the proposed interval against current room/floor status, hours, closures, reservations, and other pending proposals.
4. If valid, update the original reservation's room and time and mark the modification `approved`.
5. If no longer valid, mark the modification `invalidated`, record a reason, and leave the original reservation unchanged.
6. If any write fails, roll back all changes.

Do not automatically move a proposal to another time.

#### Modification rejection

- Only admins may reject a pending modification.
- Mark it `rejected`, record reviewer/time and optional reason.
- The original reservation remains unchanged.

#### Concurrency

Any operation that changes availability must use transactions and lock affected room rows, including booking, modification request/approval/rejection, cancellation, schedule edits, closure edits, and room/floor deactivation.

When two rooms are involved, lock them in ascending ID order to reduce deadlocks. Keep transactions short. Do not render views or perform external work while locks are held.

### 3.4 ReportService

**Owner:** Member 5

Responsibilities:

- Provide summary statistics for the admin dashboard.
- Provide reservation reports and date filters.
- Provide room utilization reports.
- Keep reporting calculations separate from page rendering.

Logical methods:

```php
getDashboardSummary(array $filters = []): array
getReservationReport(array $filters = []): array
getRoomUtilizationReport(array $filters = []): array
```

Utilization definition:

`confirmed reservation hours / net available operating hours × 100`

The denominator is scheduled operating time minus unavailable periods. Do not double-subtract overlapping closures. Exclude inactive periods according to the agreed reporting interval. Pending reservations may be shown separately as demand, but they do not count as confirmed usage. Rejected and cancelled reservations do not count. If net available hours are zero, return `N/A`, not division by zero.

Reports use the current room schedule; historical schedule versioning is out of scope.

## 4. Repository contracts

Repositories provide data access. They use PDO and prepared statements, and they do not enforce business policy independently.

### UserRepository

**Used by:** Member 1; other modules only when required through an agreed method.

```php
findById(int $id): ?array
findByEmail(string $email): ?array
createStudent(array $data): int
updateProfile(int $id, array $data): bool
```

Never return password hashes to views.

### RoomRepository

**Used by:** Members 2 and 3, with agreed shared methods.

```php
findFloorById(int $floorId): ?array
findRoomById(int $roomId): ?array
findRoomForUpdate(int $roomId): ?array
searchRooms(array $filters): array
getRoomFacilities(int $roomId): array
getOperatingHours(int $roomId): array
getUnavailabilityOverlaps(int $roomId, string $startUtc, string $endUtc): array
```

Member 2 may add the small CRUD methods needed for floor, room, facilities, hours, and closure management. Agree on method signatures before Member 3 depends on them.

### ReservationRepository

**Used by:** Member 3, and indirectly by Members 4 and 5 through services.

```php
findById(int $reservationId): ?array
findByIdForUpdate(int $reservationId): ?array
findBlockingOverlaps(int $roomId, string $startUtc, string $endUtc, array $statuses, ?int $excludeReservationId = null): array
findPendingModificationOverlaps(int $roomId, string $startUtc, string $endUtc, ?int $excludeModificationId = null): array
createReservation(array $data): int
updateReservationStatus(int $reservationId, string $status, array $reviewData = []): bool
updateReservationSchedule(int $reservationId, int $roomId, string $startUtc, string $endUtc): bool
createModification(array $data): int
findModificationById(int $modificationId): ?array
findModificationByIdForUpdate(int $modificationId): ?array
updateModificationStatus(int $modificationId, string $status, array $reviewData = []): bool
```

These signatures may be extended for pagination, student history, admin queues, or reporting. Keep the number of methods reasonable and avoid duplicate queries with different names.

Repository methods that participate in a transaction must use the same PDO connection and must not commit or roll back transactions independently.

## 5. Module integration contracts

### Member 1 — Authentication

- Owns registration, login, logout, session handling, and role checks.
- Other members call shared authentication helpers; they must not implement their own session or role logic.
- Registration creates students only.

### Member 2 — Room management

- Owns floor, room, facility, operating-hour, and closure administration.
- Member 3 relies on `RoomRepository` to read room availability data.
- Agree on repository method signatures before parallel work begins.

### Member 3 — Reservation engine

- Owns all reservation and modification business rules.
- Is the only member allowed to implement reservation state transitions and booking-conflict decisions.
- Exposes operations through `ReservationService`.

### Member 4 — Student experience

- Owns student-facing browsing, filtering, room details, booking forms, reservation history, modification requests, and cancellation interfaces.
- Calls `RoomService` for room discovery and `ReservationService` for all reservation operations.
- Must not write directly to reservation tables.

### Member 5 — Admin dashboard and reports

- Owns admin dashboard, reservation/modification review interfaces, and reports.
- Calls `ReservationService` for approvals/rejections and `ReportService` for metrics.
- Calls `RoomService` for room-management screens only if this responsibility is explicitly shared with Member 2.
- Must not implement alternate reservation approval logic.

## 6. Required tests before integration

- Public registration cannot create an admin.
- Duplicate email is rejected.
- Unauthenticated users cannot access protected pages.
- Students cannot access admin actions.
- Booking duration below one hour or above twelve hours is rejected.
- Past starts and starts beyond the seven-day window are rejected.
- Inactive room/floor, closures, and outside-hours requests are rejected.
- Overlapping pending/confirmed bookings are rejected.
- Adjacent bookings are allowed.
- A pending modification blocks its proposed interval.
- Two concurrent conflicting requests cannot both succeed.
- Only the reservation owner can cancel or request modifications.
- Cancellation and modification cutoff is two hours.
- Only one pending modification is allowed per reservation.
- Modification approval revalidates and either updates atomically or invalidates the proposal while preserving the original booking.
- Rejected/cancelled reservations no longer block availability.
- Report utilization excludes pending/rejected/cancelled reservations and handles a zero denominator.

## 7. Change-control rule

If a member discovers that a required feature cannot fit these contracts, they must raise it before changing shared tables, statuses, service behavior, or method signatures. Update `requirements.md`, `architecture.md`, `database.sql`, and this document together when a real change is approved.
