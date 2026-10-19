# Member Task Specifications — Study Room Booking System

**Project:** Workspace  
**Team size:** 5  
**Architecture:** Traditional PHP + MySQL/MariaDB + HTML/CSS/JavaScript  
**Integration branch:** `test`  
**Stable branch:** `main`

## 1. Rules for every member

Every member must follow these rules.

### Technology restrictions

- Use PHP, PDO, MySQL/MariaDB, HTML5, CSS3, and JavaScript.
- Do not introduce React, Node.js, Laravel, a REST API, or microservices.
- Use the existing shared layout, components, database configuration, and design system.
- Use prepared SQL statements.
- Use the exact schema, table names, room categories, and statuses from `database.sql`.
- Do not create additional database tables or change shared contracts without approval.

### Git workflow

1. Pull the latest `test` branch.
2. Create a feature branch for the assigned work.
3. Implement and test the assigned module.
4. Commit meaningful changes.
5. Push the feature branch.
6. Open a pull request targeting `test`.
7. Include a summary, screenshots where useful, and test results.
8. Wait for review before merging.
9. Never push directly to `main`.

Suggested branch names:

- `feature/authentication`
- `feature/room-management`
- `feature/reservation-engine`
- `feature/student-experience`
- `feature/admin-dashboard`

### Code ownership

A member owns their assigned module, not every shared file that the module uses. Shared files must have one clear owner to prevent merge conflicts.

Do not edit another member's module directly. Request a review or coordinate a change through the team leader.

### Definition of done

A feature is complete only when:

- It follows `requirements.md`, `architecture.md`, `api-contracts.md`, `design.md`, and `database.sql`.
- Its success and failure states work.
- Inputs are validated on the server.
- Permissions are checked on the server.
- Database queries use prepared statements.
- It has been tested against the relevant acceptance criteria.
- It works with the shared project structure.
- No secrets, debug output, or unrelated changes are included.

---

## 2. Member 1 — Authentication and User Management

### Objective

Build secure registration, login, logout, session handling, and role protection used by the entire application.

### Owned scope

- Student registration.
- Login and logout.
- Session management.
- Authentication helpers.
- Role-based access checks.
- Basic profile display and permitted profile updates, if included in the agreed requirements.

### Main files

```text
app/
├── Core/
│   └── Auth.php
├── Services/
│   └── AuthService.php
├── Repositories/
│   └── UserRepository.php
└── Modules/
    └── Auth/
        ├── register.php
        ├── login.php
        └── logout.php

public/
├── register.php
├── login.php
└── logout.php
```

Use the project's existing routing and page conventions if they differ. Do not create duplicate public entry points if they already exist.

### Implementation requirements

1. Register users with name, email, and password.
2. Validate required fields, email format, and agreed password requirements.
3. Reject duplicate email addresses.
4. Hash passwords with `password_hash()`.
5. Verify passwords with `password_verify()`.
6. Create student accounts only through public registration.
7. Regenerate the session ID after login.
8. Implement logout and session invalidation.
9. Provide reusable helpers for checking login status and roles.
10. Prevent students from accessing admin pages and actions.
11. Protect state-changing forms with CSRF tokens using the shared security approach.
12. Use safe error messages and never reveal stored password hashes.

### Acceptance criteria

- A valid student can register and log in.
- An existing email cannot register a second time.
- Invalid credentials do not create an authenticated session.
- A student cannot create an admin by modifying form data.
- Unauthenticated users cannot access protected pages.
- Students cannot access admin actions by typing a URL directly.
- Logout prevents access to protected pages until the user logs in again.
- The current user can be retrieved consistently by other modules.

### Tests to submit

- Successful registration.
- Duplicate email.
- Invalid email and missing fields.
- Incorrect password.
- Successful login and logout.
- Direct access to a protected page while logged out.
- Student access attempt to an admin page.
- Attempt to submit `role=admin` during registration.

### Must not implement

- Room management.
- Reservation creation, cancellation, or modification rules.
- Admin dashboards or reports.
- A separate authentication mechanism for another module.

### Dependencies

Needs the database configuration and `users` table. Members 2–5 depend on the shared authentication helpers and current-user format.

**Deliverable:** A working authentication module with reusable authorization helpers and tests.

---

## 3. Member 2 — Floor, Room, Facility, and Availability Management

### Objective

Build the admin tools for managing floors and rooms, along with the data required to determine when rooms can be booked.

### Owned scope

- Floor creation, editing, activation, and deactivation.
- Room creation, editing, activation, and deactivation.
- Room categories, capacity, and descriptions.
- Facility management and room-facility assignments.
- Room operating hours.
- Room unavailability and closure periods.
- Shared room data-access methods.

### Main files

```text
app/
├── Services/
│   └── RoomService.php
├── Repositories/
│   └── RoomRepository.php
└── Modules/
    └── Rooms/
        ├── floors.php
        ├── rooms.php
        ├── room-form.php
        ├── facilities.php
        ├── operating-hours.php
        └── unavailability.php
```

Adapt filenames to the existing project conventions rather than creating duplicate screens.

### Implementation requirements

1. Manage floors and their active/inactive status.
2. Manage rooms assigned to floors.
3. Support exactly these categories:
   - `individual_study`
   - `group_study`
   - `lounge`
4. Validate that capacity is a positive integer.
5. Assign facilities to rooms through `room_facilities`.
6. Manage multiple operating intervals per room and day.
7. Reject intervals where opening time is not before closing time.
8. Reject overlapping operating intervals for the same room/day.
9. Prevent overnight operating intervals.
10. Create, edit, and remove appropriate room-closure records.
11. Record the admin who creates an unavailability period.
12. Use inactive statuses instead of deleting floors or rooms with history.
13. Provide room details and search methods for the student module.
14. Coordinate with Member 3 before changing any data-access methods used by booking validation.

### Availability integration

Room discovery must account for:

- Active room and active floor.
- Capacity, category, and facilities.
- Operating hours for the requested day.
- Room closures and maintenance.
- Pending and confirmed reservations.
- Pending modification proposals.

The room search can identify likely available rooms, but it must not be treated as final authorization to book. `ReservationService` performs the authoritative check during a transaction.

### Availability-change coordination

When changing operating hours, closures, or active status:

- Coordinate with Member 3's transaction and room-locking approach.
- Do not silently delete existing reservations.
- Do not automatically reschedule confirmed reservations.
- Warn or reject when a proposed administrative change conflicts with existing bookings, according to the agreed policy.
- Revalidate affected availability before committing the change.

### Acceptance criteria

- An admin can create and edit a floor.
- An admin can create a room on an existing floor.
- All three allowed room categories work.
- Invalid capacity is rejected.
- Facilities can be assigned and removed.
- Operating intervals are validated.
- Overlapping intervals for the same room/day are rejected.
- A room can be deactivated without deleting reservation history.
- A closure blocks room availability for its time interval.
- Students cannot access management actions.
- Search results expose enough room data for the student experience.

### Tests to submit

- Create/edit/deactivate a floor.
- Create/edit/deactivate a room.
- Invalid room category and capacity.
- Assign multiple facilities.
- Valid multiple intervals on one day.
- Overlapping intervals.
- Closure intersecting a requested booking interval.
- Inactive floor or room excluded from available rooms.
- Student attempt to access management actions.

### Must not implement

- Reservation approval or rejection.
- Booking conflict decisions independent of `ReservationService`.
- Student reservation history or modification workflows.
- Admin utilization calculations.

### Dependencies

Needs the database schema and authentication helpers. Member 3 needs the agreed `RoomRepository` methods before completing conflict validation.

**Deliverable:** Working room/floor management, operating hours, closures, and the shared room data-access contract.

---

## 4. Member 3 — Reservation and Booking Engine

### Objective

Implement the central reservation engine. This is the authoritative source of booking rules for the entire application.

### Owned scope

- Reservation creation.
- Reservation overlap checks.
- Admin approval and rejection.
- Student cancellation.
- Modification requests.
- Modification approval, rejection, and invalidation.
- Transaction handling and room locking.
- Reservation data-access methods.
- Booking-related tests.

### Main files

```text
app/
├── Services/
│   └── ReservationService.php
├── Repositories/
│   └── ReservationRepository.php
└── Modules/
    └── Reservations/
        ├── reservation-actions.php
        └── reservation-queries.php
```

These module files may be simple handlers. Keep the business logic in the service.

### Non-negotiable design rule

**Every reservation state change must go through `ReservationService`.**

Member 4 and Member 5 must call this service. They must not update `reservations` or `reservation_modifications` directly.

### Reservation creation requirements

- Create reservations with `pending` status.
- Require admin approval before a booking becomes confirmed.
- Minimum duration: one hour.
- Maximum duration: twelve hours.
- No past start time.
- Allow starts up to seven days ahead, inclusive.
- Require the entire interval to fit within one operating interval.
- Require the room and its floor to be active.
- Reject intervals that overlap a room closure.
- Reject intervals that overlap pending or confirmed reservations.
- Reject intervals that overlap pending modification proposals.
- Allow adjacent bookings where one ends exactly when the next begins.

Use the half-open interval overlap rule:

```text
existing start < requested end
AND existing end > requested start
```

### Cancellation requirements

- Students can cancel only their own reservations.
- Cancellation must occur at least two hours before the original start.
- A pending or confirmed reservation can be cancelled.
- Rejected and cancelled reservations are terminal.
- Cancellation changes status; it does not delete the row.
- If a pending modification exists, cancellation and invalidation must happen atomically.

### Modification requirements

- Only confirmed reservations may be modified.
- Only the reservation owner can request a modification.
- The request must be submitted at least two hours before the original start.
- Only one pending modification per reservation is allowed.
- Proposed intervals must follow the same booking rules as a new reservation.
- The original reservation stays unchanged while the proposal is pending.
- The original interval continues to block availability.
- The proposed interval also blocks availability.
- Conflict checks must exclude the proposal's own original reservation, allowing a proposal to overlap its own original interval.
- At approval, revalidate current availability.
- If valid, update the original reservation and approve the modification in the same transaction.
- If invalid, mark the modification `invalidated`, record a reason, and preserve the original reservation.
- Rejection leaves the original reservation unchanged.

### Concurrency requirements

Use short transactions for all operations that change availability.

1. Start the transaction.
2. Lock the affected room row or rows using `SELECT ... FOR UPDATE`.
3. Lock two room IDs in ascending order if a change involves two rooms.
4. Re-read current data and revalidate.
5. Perform all required writes.
6. Commit only after all writes succeed.
7. Roll back if any validation or write fails.

Apply this approach to booking, modification requests and reviews, cancellations/status changes, and coordinate with Member 2 for operating-hour, closure, and room/floor status changes.

Indexes do not replace conflict checks or transaction locking.

### Acceptance criteria

- Conflicting pending/confirmed reservations cannot both succeed.
- Two simultaneous requests for the same slot cannot both be accepted.
- A pending reservation blocks the slot until rejected or cancelled.
- A confirmed reservation blocks the slot until cancelled.
- Adjacent reservations are accepted.
- Booking rules are enforced on the server regardless of form input.
- Only admins can approve/reject reservations and modification proposals.
- Only reservation owners can cancel or request modifications.
- Cutoff rules use the original reservation start for cancellation and modification requests.
- A pending modification blocks its proposed interval.
- Modification approval updates both records atomically.
- A failed modification approval preserves the original reservation.
- Cancellation with a pending modification handles both records atomically.

### Tests to submit

At minimum, test:

1. Valid booking.
2. Booking too short and too long.
3. Past start and start beyond seven days.
4. Outside operating hours.
5. Inactive room/floor.
6. Closure conflict.
7. Overlapping pending reservation.
8. Overlapping confirmed reservation.
9. Adjacent bookings.
10. Pending proposal conflict.
11. Cancellation cutoff.
12. Modification cutoff.
13. Duplicate pending modification.
14. Successful modification approval.
15. Modification invalidated because availability changed.
16. Concurrent booking requests.
17. Non-owner cancellation/modification attempt.
18. Transaction rollback after a failed operation.

### Must not implement

- Room-management screens.
- Student-facing page layouts.
- Admin dashboard design or report calculations.
- Separate booking rules in controllers.

### Dependencies

Needs Member 2's room, operating-hours, and closure repository contract, plus the final database schema. Member 4 and Member 5 depend on the service methods being agreed early.

**Deliverable:** Tested `ReservationService` and `ReservationRepository` with reliable transactional booking behavior.

---

## 5. Member 4 — Student Experience

### Objective

Build the student-facing interface for finding rooms and managing personal reservations, using the shared services.

### Owned scope

- Student dashboard.
- Room browsing and search/filter interface.
- Room detail views.
- Booking form.
- Reservation history.
- Reservation status display.
- Cancellation interface.
- Modification-request interface.
- Student-facing success, validation, conflict, empty, and error states.

### Main files

```text
app/
└── Modules/
    └── Student/
        ├── dashboard.php
        ├── rooms.php
        ├── room-details.php
        ├── booking-form.php
        ├── reservations.php
        └── reservation-details.php

app/
└── Views/
    └── student/
        ├── dashboard-view.php
        ├── room-list-view.php
        ├── room-details-view.php
        └── reservation-list-view.php
```

Use the existing view conventions and shared components. The filenames are suggestions, not a requirement to create every file if a simpler structure works.

### Implementation requirements

1. Show relevant room information: category, floor, capacity, facilities, and operating information.
2. Allow filtering by the agreed room attributes and requested availability.
3. Display a clear booking form for room and time selection.
4. Submit booking requests through `ReservationService`.
5. Display the actual returned reservation status.
6. Show only the signed-in student's reservations.
7. Provide cancellation and modification forms where permitted.
8. Submit cancellation and modification requests through `ReservationService`.
9. Display validation and conflict messages returned by the service.
10. Disable or hide actions when clearly not permitted, but still rely on server-side checks.
11. Follow `design.md` for layout, colors, typography, components, and responsive behavior.
12. Include loading, empty, success, and failure states where relevant.

### Important restrictions

- Do not write SQL against `reservations` or `reservation_modifications` from student page handlers.
- Do not duplicate overlap checks, cutoff rules, or status-transition logic.
- Do not assume that a room is available merely because it appeared in search results.
- Never trust a student ID supplied by a form; obtain the authenticated user's ID from the shared authentication helper.
- Do not show another student's private reservation information.

### Acceptance criteria

- A student can browse and filter rooms.
- Room details show the agreed information.
- A valid booking request reaches `ReservationService`.
- A rejected request displays a clear reason.
- Students see their own reservation history and statuses.
- A student cannot view another student's private reservation details.
- Cancellation and modification forms submit the correct reservation IDs and values.
- Invalid or expired actions display the service's result without corrupting data.
- Pages match the shared design system and work at the agreed viewport sizes.

### Tests to submit

- Room search and filters.
- Room detail page.
- Valid booking form submission.
- Conflict/error display.
- Empty reservation history.
- Student reservation history.
- Attempt to view another student's reservation.
- Cancellation form.
- Modification request form.
- Responsive layout and form validation.

### Must not implement

- Reservation conflict logic.
- Direct reservation status updates.
- Admin review queues or reports.
- Separate database connection or authentication code.

### Dependencies

Needs Member 1's authentication helpers, Member 2's room data contract, Member 3's reservation service methods, and the shared design system.

**Deliverable:** Complete student-facing flow from room discovery through booking and personal reservation management.

---

## 6. Member 5 — Admin Dashboard, Reviews, and Reports

### Objective

Build the admin-facing dashboard and review workflows, plus reporting and utilization calculations.

### Owned scope

- Admin dashboard.
- Pending reservation review list.
- Reservation detail and review actions.
- Pending modification review list.
- Modification detail and review actions.
- Reservation reports and date filters.
- Room utilization reports.
- Dashboard summary cards and tables.

Room and floor CRUD remains owned by Member 2. Member 5 may link to those screens or integrate them into the admin navigation but should not create a competing implementation.

### Main files

```text
app/
├── Services/
│   └── ReportService.php
└── Modules/
    └── Admin/
        ├── dashboard.php
        ├── reservations.php
        ├── reservation-details.php
        ├── modifications.php
        └── reports.php
```

Use the agreed folder structure and shared components rather than creating duplicate layouts.

### Implementation requirements

1. Restrict every admin page and action to authenticated admins.
2. Display dashboard summaries using `ReportService`.
3. Show pending reservations and their relevant room/student/time details.
4. Allow admins to approve or reject pending reservations.
5. Show pending modification requests with original and proposed details.
6. Allow admins to approve or reject pending modifications.
7. Display invalidation and rejection reasons when available.
8. Provide reservation reports with agreed date filters.
9. Provide room utilization reports.
10. Use consistent tables, filters, empty states, and confirmation messages.
11. Call `ReservationService` for all reservation and modification decisions.
12. Use `ReportService` for reporting calculations rather than duplicating them in pages.

### Reporting definition

Utilization is:

`confirmed reservation hours / net available operating hours × 100`

The denominator is scheduled operating time minus unavailable periods. Overlapping closures must not be double-subtracted. Exclude inactive periods according to the agreed reporting interval. Pending reservations may be reported as demand separately. Rejected and cancelled reservations do not count as confirmed usage.

When net available hours equal zero, display `N/A`.

Reports use the current schedule. Historical schedule versioning is not part of this project.

### Acceptance criteria

- Students cannot access admin pages or actions.
- The dashboard displays data from shared services.
- Admins can view pending reservation requests.
- Approving/rejecting a reservation calls `ReservationService`.
- Admins can view and review pending modification requests.
- A stale proposal displays its invalidated status and reason.
- Date filters are applied consistently to reports.
- Utilization uses the agreed numerator and denominator.
- Zero available hours display `N/A`.
- No report counts rejected/cancelled reservations as confirmed usage.
- Admin screens follow the shared design system.

### Tests to submit

- Student access attempt to each protected admin workflow.
- Pending reservation list.
- Reservation approval and rejection.
- Pending modification list.
- Modification approval and rejection.
- Invalidated proposal display.
- Date-filtered reservation report.
- Utilization with confirmed bookings.
- Utilization with pending/rejected/cancelled bookings.
- Zero available operating hours.
- Empty report result.

### Must not implement

- Room CRUD owned by Member 2.
- Booking validation or transaction logic owned by Member 3.
- Custom admin authentication or session handling.
- Duplicate utilization calculations in individual pages.

### Dependencies

Needs Member 1's role protection, Member 3's reservation review methods, and the final report contract. Coordinate with Member 2 if the dashboard links to room management.

**Deliverable:** Working admin dashboard, review queues, reservation/modification actions, and tested reports.

---

## 7. Shared dependencies and integration order

Members can build their own views and isolated module code in parallel, but shared contracts must be agreed before integration.

### Recommended order

1. **Team leader:** Freeze `database.sql`, `architecture.md`, `api-contracts.md`, `requirements.md`, and `design.md`.
2. **Member 1:** Implement PDO-compatible authentication and session helpers.
3. **Member 2:** Implement `RoomRepository` and room-management operations.
4. **Member 3:** Implement reservation operations using the agreed room and reservation repository contracts.
5. **Member 4:** Connect student views to `RoomService` and `ReservationService`.
6. **Member 5:** Connect admin review screens and reports to shared services.
7. **All members:** Integrate through pull requests into `test`, run cross-module tests, and fix integration issues before release.

The sequence is about dependency readiness, not requiring one member to finish everything before others begin. Members 4 and 5 can build page layouts using agreed sample data while service methods are being implemented.

## 8. Shared files and ownership

| Shared file | Responsible owner |
|---|---|
| `config/database.php` | Team leader / designated integration owner |
| `app/Core/Auth.php` | Member 1 |
| `app/Core/Csrf.php` | Member 1 or designated security owner |
| `app/Core/helpers.php` | Team leader; changes coordinated |
| `app/Services/AuthService.php` | Member 1 |
| `app/Services/RoomService.php` | Member 2 |
| `app/Services/ReservationService.php` | Member 3 |
| `app/Services/ReportService.php` | Member 5 |
| `app/Repositories/UserRepository.php` | Member 1 |
| `app/Repositories/RoomRepository.php` | Member 2, with agreed methods for Member 3 |
| `app/Repositories/ReservationRepository.php` | Member 3 |
| Shared layouts and UI components | Designated owner or team leader |
| `database.sql` and architecture documents | Team leader; changes require team agreement |

Avoid having multiple members edit the same shared file in parallel. Agree on signatures first, then implement against those signatures.

## 9. Pull request checklist

Every pull request must confirm:

- [ ] Targets `test`, not `main`.
- [ ] Changes stay within the assigned module or were coordinated.
- [ ] Shared table names, statuses, and categories are unchanged.
- [ ] No duplicate business rules were added.
- [ ] Database access uses the shared PDO connection and prepared statements.
- [ ] Authorization is enforced server-side.
- [ ] Success and failure paths were tested.
- [ ] Relevant screenshots or test results are attached.
- [ ] No debug statements, secrets, or unrelated files are included.
- [ ] The module integrates with the current `test` branch.

## 10. Final integration acceptance

The system is ready for a milestone release only when these end-to-end flows work:

1. A student registers and logs in.
2. An admin creates a floor, room, facilities, operating hours, and a closure.
3. A student finds a room and submits a valid reservation.
4. A second overlapping request is rejected, including when the first request is still pending.
5. An admin approves or rejects a reservation.
6. The student sees the updated status.
7. The student cancels within the allowed policy window, or submits a valid modification request.
8. An admin approves/rejects the modification; approval revalidates availability.
9. Reports reflect the resulting reservation states.
10. Authorization, history preservation, and transaction behavior remain correct throughout.

The team leader reviews all pull requests and resolves contract or integration conflicts before promoting tested milestones from `test` to `main`.
