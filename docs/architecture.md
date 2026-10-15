# Study Room Booking System (Workspace)
## System Architecture

**Project Type:** University Software Development Project  
**Architecture:** Modular PHP Application  
**Technology Stack:** PHP, MySQL, HTML5, CSS3, JavaScript  
**Development Environment:** XAMPP  
**Database Engine:** MySQL with InnoDB  
**Timezone:** Africa/Cairo

---

## 1. Project Overview

Workspace is a web application that allows students to discover available study rooms, submit reservation requests, and manage their bookings. Administrators manage floors, rooms, facilities, operating hours, unavailability periods, reservations, and reports.

The system manages one building with multiple floors. All bookable units are complete rooms, not individual seats.

The system supports exactly three room categories:

1. Individual Study Room (`individual_study`)
2. Group Study Room (`group_study`)
3. Lounge (`lounge`)

Each room has its own capacity, facilities, operating hours, and availability.

### User Roles

**Student**
- Register and log in.
- Browse and filter rooms.
- View room details, capacity, facilities, and availability.
- Submit reservation requests.
- View reservation history and current statuses.
- Request changes to eligible reservations.
- Cancel eligible reservations.

**Administrator**
- Manage floors and rooms.
- Manage facilities and room operating hours.
- Manage room unavailability periods.
- Approve or reject reservation requests.
- Review, approve, or reject modification requests.
- View dashboard statistics and room usage reports.

---

## 2. Technology Stack

| Technology | Purpose |
|---|---|
| PHP | Server-side logic and page rendering |
| MySQL | Persistent data storage |
| PDO | Database access and prepared statements |
| HTML5 | Page structure |
| CSS3 | Styling and responsive layouts |
| JavaScript | Client-side interactions and validation |
| XAMPP | Local Apache and MySQL development environment |
| Git and GitHub | Version control and collaboration |

### Technical Restrictions

- Do not use React, Node.js, Laravel, or another PHP framework.
- Do not build a REST API or introduce a separate backend server.
- Render pages using PHP templates.
- Use PDO prepared statements for database queries.
- Use PHP sessions for authentication.
- Use `password_hash()` and `password_verify()` for passwords.
- Use MySQL InnoDB transactions for operations that affect room availability.
- Keep the implementation understandable and appropriate for a university project.

---

## 3. Application Architecture

The application uses a simple modular architecture.

A browser requests a PHP page. The page checks authentication and permissions, invokes the relevant service logic, retrieves or updates database records, and renders the resulting HTML.

```text
Browser
   |
   v
Public PHP Pages
   |
   v
Authentication and Authorization
   |
   v
Module / Service Logic
   |
   v
Repositories (database queries)
   |
   v
MySQL Database
```

PHP views render the interface using data provided by the application logic.

### Architectural Rules

1. Each member owns a defined module.
2. Shared authentication, database access, and common UI components must not be independently reimplemented.
3. Reservation availability and conflict rules must be centralized in the reservation logic.
4. Database queries should be placed in existing repositories where practical.
5. Do not create a separate class for every small operation.
6. Validate important rules on the server, even when JavaScript also validates the input.
7. Never trust room availability calculated only in the browser.
8. Keep database credentials and other configuration outside publicly accessible pages.

---

## 4. Folder Structure

Use the following project structure:

```text
workspace/
│
├── config/
│   └── database.php
│
├── app/
│   ├── Core/
│   │   ├── Auth.php
│   │   ├── Csrf.php
│   │   └── helpers.php
│   │
│   ├── Services/
│   │   ├── AuthService.php
│   │   ├── RoomService.php
│   │   ├── ReservationService.php
│   │   └── ReportService.php
│   │
│   ├── Repositories/
│   │   ├── UserRepository.php
│   │   ├── RoomRepository.php
│   │   └── ReservationRepository.php
│   │
│   ├── Modules/
│   │   ├── Auth/
│   │   ├── Rooms/
│   │   ├── Reservations/
│   │   ├── Student/
│   │   └── Admin/
│   │
│   └── Views/
│       ├── layouts/
│       ├── components/
│       ├── student/
│       └── admin/
│
├── public/
│   ├── index.php
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   │
│   ├── student/
│   ├── admin/
│   │
│   └── assets/
│       ├── css/
│       ├── js/
│       └── images/
│
├── database.sql
│
├── docs/
│   ├── requirements.md
│   ├── architecture.md
│   ├── api-contracts.md
│   └── design.md
│
└── README.md
```

### Folder Responsibilities

- `config/`: Database connection and configuration.
- `app/Core/`: Shared authentication, CSRF protection, and helper functions.
- `app/Services/`: Business rules and operations.
- `app/Repositories/`: Reusable database queries.
- `app/Modules/`: Feature-specific implementation.
- `app/Views/`: Reusable PHP layouts, components, and page templates.
- `public/`: Browser-accessible entry points and static assets.
- `database.sql`: Database schema and any agreed initial data.
- `docs/`: Project documentation.

**Important:** Only `public/` should be directly accessible through Apache. The `app/` and `config/` directories must not be exposed as public web directories. During local development, configure Apache's document root to the project's `public/` directory, or use an equivalent setup that prevents direct access to private application files.

The structure is a guide, not a reason to create empty files prematurely. Create files when their functionality is needed.

---

## 5. Main Application Components

### 5.1 Core

**Auth.php**
- Starts and manages authenticated sessions.
- Provides the current logged-in user.
- Provides login-required checks.

**Csrf.php**
- Generates and validates CSRF tokens for state-changing forms.

**helpers.php**
- Contains genuinely shared helper functions.
- Avoid placing module-specific business rules here.

### 5.2 Services

**AuthService.php**
- Registration, login, logout, and profile updates.
- Ensures public registration always creates a student account.

**RoomService.php**
- Floor, room, facility, operating-hours, and unavailability management.
- Room searching and availability checks.
- Validation of room and schedule data.

**ReservationService.php**
- Reservation creation, conflict checks, approval, rejection, and cancellation.
- Reservation modification requests and their review.
- Central enforcement of reservation policies and concurrency rules.

**ReportService.php**
- Dashboard statistics.
- Reservation summaries.
- Room usage and utilization reports.

Modification operations may be implemented in a separate class later if needed, but the initial implementation should avoid unnecessary extra layers. All modification operations must use the same centralized reservation rules.

### 5.3 Repositories

**UserRepository.php**
- User retrieval and persistence.

**RoomRepository.php**
- Queries involving floors, rooms, facilities, operating hours, and unavailability periods.

**ReservationRepository.php**
- Reservation and modification queries, conflict checks, status updates, and related database operations.

Add another repository only when a clear need arises. Do not duplicate the same query logic across multiple modules.

---

## 6. Database Design

The database contains the following main tables:

| Table | Purpose |
|---|---|
| `users` | Student and administrator accounts |
| `floors` | Floors within the building |
| `rooms` | Bookable study rooms |
| `facilities` | Available room facilities |
| `room_facilities` | Many-to-many relationship between rooms and facilities |
| `room_operating_hours` | Weekly operating intervals for each room |
| `room_unavailability` | Periods when a room cannot be booked |
| `reservations` | Original reservation records |
| `reservation_modifications` | Proposed changes to existing reservations |

### Relationship Summary

- One floor contains multiple rooms.
- Each room belongs to one floor.
- Rooms can have multiple facilities.
- A facility can belong to multiple rooms.
- One student can have multiple reservations.
- Each reservation belongs to one student and one room at a time.
- One reservation can have multiple historical modification requests, but only one may be pending at once.
- An administrator can review reservations and modification requests.

### Data Integrity

- Use foreign keys for relationships.
- Use unique constraints for user email, floor number, and other appropriate unique fields.
- Use transactions for operations that must succeed or fail as one unit.
- Retain reservation history.
- Deactivate floors and rooms instead of deleting records that have booking history.
- Validate complex scheduling rules in PHP services; database constraints alone cannot enforce every business rule.
- Use consistent status values and UTC storage for reservation-related timestamps.

The authoritative schema is `database.sql`. Update this document if the actual schema changes.

---

## 7. Reservation Business Rules

These rules are shared by all five members and must be implemented consistently.

### 7.1 Creating Reservations

- Every new reservation starts with `pending` status.
- An administrator must approve or reject the request.
- Pending and confirmed reservations block their requested intervals.
- Rejected and cancelled reservations do not block availability.
- A student may have multiple upcoming reservations.
- There is no maximum number of reservations per student.
- Reservation duration must be at least 1 hour and at most 12 hours.
- The start time cannot be in the past.
- Reservations may be created up to 7 days in advance, inclusive of the exact seven-day boundary.
- The entire reservation must fit inside one operating-hours interval.
- The room must be active, its floor must be active, and the room must not be unavailable during the requested interval.

### 7.2 Detecting Conflicts

Use half-open intervals so that adjacent reservations are allowed.

Two intervals conflict when:

```text
existing.starts_at < requested.ends_at
AND
existing.ends_at > requested.starts_at
```

For example, a reservation ending at 11:00 does not conflict with another reservation beginning at 11:00.

Availability checks must consider:

- Pending reservations.
- Confirmed reservations.
- Pending modification proposals from other reservations.
- Room unavailability periods.
- Operating hours and room/floor status.

### 7.3 Cancelling Reservations

- Students may cancel their reservations at least 2 hours before the original reservation start time.
- Administrators may manage reservations according to the application's authorization rules.
- Cancellation must update the reservation status instead of deleting the record.
- If a reservation with a pending modification is cancelled, the pending modification must be invalidated in the same transaction.

### 7.4 Modifying Reservations

- A student must submit a modification request instead of directly changing a confirmed reservation.
- The student must submit the request at least 2 hours before the original reservation starts.
- Proposed times must satisfy the same duration and advance-booking rules as new reservations.
- Only one modification request per reservation may be pending.
- The original confirmed reservation remains unchanged while a modification is pending.
- A pending proposal temporarily blocks its proposed interval.
- A proposal may overlap its own original reservation interval; conflict checks must exclude the original reservation itself.
- When approving a proposal, the system must revalidate room status, floor status, operating hours, closures, reservations, and other pending proposals.
- If the proposal is no longer valid, mark it `invalidated`, record the reason, and preserve the original reservation.
- If rejected, the original reservation remains unchanged.
- If approved, update the original reservation's room and time and mark the modification `approved` atomically.

### 7.5 Reservation Statuses

Reservation statuses:

- `pending`
- `confirmed`
- `rejected`
- `cancelled`

Allowed transitions:

```text
pending -> confirmed
pending -> rejected
pending -> cancelled
confirmed -> cancelled
```

A confirmed reservation's room or time may change only through an approved modification request. Rejected and cancelled reservations are terminal states.

Modification statuses:

- `pending`
- `approved`
- `rejected`
- `invalidated`

Allowed transitions:

```text
pending -> approved
pending -> rejected
pending -> invalidated
```

---

## 8. Operating Hours and Unavailability

- Each room can have multiple operating intervals on the same day.
- Intervals for the same room and day must not overlap.
- Overnight operating intervals are not supported.
- A reservation must fit entirely within a single operating interval.
- Administrators may create room unavailability periods.
- All intervals must have a valid start earlier than their end.
- Changes to operating hours, closures, or room status must not silently invalidate existing confirmed reservations.
- Availability must be checked again whenever an operation can affect room booking.

---

## 9. Timezone and Date Handling

The application uses the `Africa/Cairo` timezone for interpreting dates and times entered by users.

- Store reservation, modification, and unavailability timestamps in UTC using MySQL `DATETIME` columns.
- Configure the PHP application and database connection consistently to use UTC for stored timestamps.
- Interpret weekly operating-hours values in the `Africa/Cairo` timezone for the relevant date.
- Convert user-entered local times to UTC before persistence.
- Convert stored UTC values back to `Africa/Cairo` when displaying them.
- Use PHP `DateTimeImmutable` and `DateTimeZone`.
- Do not hardcode the UTC offset because it can change.
- Use server-side time as the authority for cutoff and advance-booking checks.

---

## 10. Concurrent Booking Protection

Two students may attempt to reserve the same room and time simultaneously. A normal availability check is not enough to prevent this.

Every operation that can change room availability must follow the same locking protocol:

1. Start a database transaction.
2. Lock the affected room row or rows using `SELECT ... FOR UPDATE`.
3. If multiple rooms are involved, lock them in ascending room ID order.
4. Re-read relevant records and validate availability while holding the locks.
5. Perform the operation.
6. Commit the transaction, or roll it back if validation fails.

This applies to:

- Creating reservations.
- Submitting modification requests.
- Approving or rejecting reservations.
- Approving or rejecting modification requests.
- Cancelling reservations.
- Changing operating hours.
- Creating or removing unavailability periods.
- Deactivating rooms or floors.

For an operation involving a room change, lock both the original and proposed room rows in ascending ID order.

Keep transactions short. Do not render pages, wait for user input, or make external calls while holding database locks.

Database indexes help performance but do not replace transaction locking and conflict validation.

---

## 11. Security Requirements

All members must follow these rules:

- Use PDO prepared statements for queries containing user input.
- Hash passwords with `password_hash()` and verify them with `password_verify()`.
- Regenerate the session ID after successful login.
- Protect state-changing forms with CSRF tokens.
- Escape untrusted output when rendering HTML.
- Validate input on the server.
- Check authentication and authorization on every protected operation.
- Never trust a submitted user ID to determine ownership; derive the student identity from the authenticated session.
- Only administrators may perform administrative operations.
- Public registration must not allow a user to select the administrator role.
- Do not expose database credentials, password hashes, or sensitive error details.
- Use POST for state-changing actions rather than changing data through GET requests.

---

## 12. Team Responsibilities

Each member owns a module and its implementation, tests, and documentation updates. Shared files and contracts must be agreed upon before parallel development.

### Member 1: Authentication and User Management

**Responsibilities**
- Registration, login, logout, sessions, and profile management.
- Student/admin role checks.
- Shared authentication helpers.

**Main areas**
- `app/Core/`
- `app/Services/AuthService.php`
- `app/Modules/Auth/`
- Relevant authentication views and public pages.

### Member 2: Floor and Room Management

**Responsibilities**
- Floor and room CRUD operations.
- Room categories, capacity, and facilities.
- Operating hours and unavailability periods.
- Active/inactive status management.

**Main areas**
- `app/Services/RoomService.php`
- `app/Repositories/RoomRepository.php`
- `app/Modules/Rooms/`
- Relevant administrative pages and views.

### Member 3: Reservation Engine

**Responsibilities**
- Creating reservations.
- Conflict detection and transaction locking.
- Approval, rejection, and cancellation logic.
- Modification requests and their review.
- Enforcement of duration, advance-booking, and cutoff rules.

**Main areas**
- `app/Services/ReservationService.php`
- `app/Repositories/ReservationRepository.php`
- `app/Modules/Reservations/`

This member owns the central reservation business rules. Other members must call the shared reservation logic rather than implement their own booking rules.

### Member 4: Student Experience

**Responsibilities**
- Room browsing and search.
- Filtering by capacity, facilities, category, and availability.
- Room details and availability display.
- Reservation forms, booking history, and status display.
- Student-facing modification and cancellation interfaces.

**Main areas**
- `app/Modules/Student/`
- Student views and public student pages.
- JavaScript and CSS for student-facing interactions.

This member integrates with the shared room and reservation services rather than duplicating their business logic.

### Member 5: Administration Dashboard and Reports

**Responsibilities**
- Administrative dashboard.
- Reservation review interface.
- Modification review interface.
- Reservation and room usage reports.
- Statistics and report presentation.

**Main areas**
- `app/Services/ReportService.php`
- `app/Modules/Admin/`
- Administrative views and pages.

This member calls the shared reservation service for administrative decisions and the report service for statistics.

### Shared Responsibilities

All members must:
- Follow `requirements.md`, `design.md`, and `api-contracts.md`.
- Respect the shared database schema.
- Use the common authentication, authorization, and validation rules.
- Write and run relevant tests.
- Keep changes within their assigned scope.
- Submit changes through pull requests.
- Avoid editing another member's module without coordination.

---

## 13. Git Workflow

Use three branch levels:

- `main`: Stable, tested versions only.
- `test`: Integration branch for the team's work.
- Feature branches: Individual development branches.

Suggested branches:

```text
main
└── test
    ├── feature/authentication
    ├── feature/room-management
    ├── feature/reservation-engine
    ├── feature/student-experience
    └── feature/admin-dashboard
```

### Rules

1. Never push directly to `main`.
2. Each member creates a feature branch from the latest `test`.
3. Commit focused changes with descriptive messages.
4. Push the feature branch to GitHub.
5. Open a pull request targeting `test`.
6. The team leader reviews the code, tests, and shared-file changes.
7. Fix review comments before merging.
8. Test the integrated application on `test`.
9. Merge tested milestones from `test` into `main`.

If a shared contract or database schema changes, notify the team and update the documentation before dependent implementation proceeds.

---

## 14. Testing Strategy

Testing should cover the main workflows and critical edge cases.

### Authentication
- Successful registration and login.
- Duplicate email rejection.
- Incorrect password rejection.
- Unauthorized access prevention.
- Student registration cannot create an administrator.

### Room Management
- Valid and invalid room data.
- Inactive room and floor behavior.
- Facility assignment.
- Overlapping operating-hours rejection.
- Unavailability periods.

### Reservations
- Valid reservation submission.
- Rejection of overlapping pending and confirmed bookings.
- Acceptance of adjacent time intervals.
- Duration and advance-booking boundaries.
- Reservations outside operating hours.
- Student modification and cancellation cutoff boundaries.
- Pending modification conflict behavior.
- Approval revalidation when availability changes.
- Concurrent booking attempts.
- Correct rollback when a transaction fails.

### Administration and Reports
- Only administrators can perform administrative actions.
- Reservation approval and rejection.
- Modification approval and rejection.
- Accurate reservation summaries.
- Correct utilization calculation and handling of zero available hours.

Test cases and their results should be documented according to the course requirements.

---

## 15. Room Utilization Calculation

Room utilization is calculated as:

```text
Utilization (%) =
Confirmed Reservation Hours
---------------------------- x 100
Net Available Operating Hours
```

The denominator is scheduled operating time minus room closures and unavailable periods. Exclude inactive periods. Rejected and cancelled reservations do not count as used time.

Calculate overlapping time ranges carefully so unavailable periods are not subtracted more than once. Pending reservations may be reported separately as demand, but they are not counted as confirmed usage.

If net available operating hours are zero, display `N/A`.

Reports use the current schedule and do not reconstruct historical operating-hour changes.

---

## 16. Implementation Order

The recommended order is:

1. Finalize the database schema and shared contracts.
2. Set up the project structure and database connection.
3. Implement shared authentication and authorization.
4. Implement floor, room, facility, and schedule management.
5. Implement the reservation engine and its conflict protection.
6. Integrate student browsing and reservation interfaces.
7. Integrate the administrative dashboard and reports.
8. Test modules individually and then test the integrated application.
9. Fix integration issues and prepare the final stable release.

Some work can proceed in parallel after the schema and contracts are agreed upon. Student and administrative interfaces should not invent their own reservation rules.

---

## 17. Definition of Done

A feature is complete only when:

- It meets the requirements and acceptance criteria.
- It follows the agreed folder structure and contracts.
- Authentication and authorization checks are applied where required.
- Server-side validation is implemented.
- Database operations are safe and use transactions when necessary.
- Relevant tests have been run and results recorded.
- No unrelated files or modules have been changed unnecessarily.
- The feature works with the latest integration branch.
- Its pull request has been reviewed and approved.

---

## 18. Final Architecture Principle

Keep the system modular but simple.

The project should have clear responsibilities, reusable shared logic, consistent database rules, and reliable booking behavior without unnecessary frameworks, APIs, classes, or infrastructure.

**Correctness, maintainability, and successful integration are more important than architectural complexity.**
