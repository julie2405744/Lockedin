# Frontend Member Task Specifications — InFocus Study Room Booking System

**Project:** InFocus — Study Room Booking System  
**Team size:** 5 members  
**Frontend:** PHP templates, HTML5, CSS3, vanilla JavaScript  
**Backend:** PHP + PDO + MySQL/MariaDB  
**Integration branch:** `test`  
**Stable branch:** `main`  
**Design reference:** https://demo.themesberg.com/spaces/html/all-spaces.html

---

## 1. Rules for every member

### Technology restrictions

- Use HTML5, CSS3, vanilla JavaScript, and PHP templates.
- Do not introduce React, Vue, Angular, Node.js, Laravel, or unapproved frameworks.
- Follow the existing project structure and PHP routing conventions.
- Use the shared design system defined in `design.md`.
- Do not create separate colors, typography, spacing, or component styles for your module.
- Do not put SQL queries or authoritative business rules in views or JavaScript.
- Keep validation, permissions, and reservation decisions on the server.
- Escape dynamic PHP output and use semantic, accessible HTML.
- Every form must display appropriate validation and server-response messages.

### Shared design rules

Follow the existing InFocus design system:

| Element | Value |
|---|---|
| Primary | `#13285E` |
| Primary hover | `#0D1C45` |
| Soft primary | `#EEF1F8` |
| Accent | `#E64D69` |
| Accent hover | `#C93D58` |
| Soft accent | `#FFF0F3` |
| Headings | `#172B4D` |
| Body text | `#475569` |
| Muted text | `#64748B` |
| Page background | `#F8FAFC` |
| Surface | `#FFFFFF` |
| Border | `#E2E8F0` |
| Success | `#15803D` |
| Warning | `#B45309` |
| Danger | `#B91C1C` |

- Use Inter if already available; otherwise use the system font stack.
- Follow the shared spacing scale: 4, 8, 12, 16, 24, 32, 40, and 48 px.
- Use 12 px card corners and 8 px form-control and button corners.
- Room cards should use a consistent 16:9 image area.
- Follow the reference's deep-navy hero, white elevated search panel, compact navigation, and spacious directory-listing rhythm, while retaining InFocus content and routes.
- Use coral only for prominent promotional actions. Use the navy primary color for normal application actions and navigation.
- Use inline SVGs for UI and placeholder icons; do not use emoji or add an icon library for small icon needs.
- Use brief entrance and hover transitions only when they clarify hierarchy or feedback. Respect `prefers-reduced-motion` and avoid continuous, decorative motion.
- Avoid glassmorphism, excessive gradients, and clutter.
- Reuse shared buttons, badges, alerts, forms, layouts, and navigation.

### Git workflow

1. Pull the latest `test` branch.
2. Create or use your assigned feature branch.
3. Implement and test your assigned frontend scope.
4. Commit meaningful changes.
5. Push your branch and open a pull request targeting `test`.
6. Include a summary, screenshots, and test results.
7. Wait for review before merging.
8. Never push directly to `main`.

Suggested branch names:

- Member 1: `feature/frontend-auth`
- Member 2: `feature/frontend-room-management`
- Member 3: `feature/frontend-reservation-views`
- Member 4: `feature/frontend-student-experience`
- Member 5: `feature/frontend-admin-dashboard`

If your team already uses one branch per full-stack member, continue using that convention instead of creating duplicate branches.

### Definition of done

A frontend feature is complete only when:

- It follows `design.md`, `requirements.md`, `architecture.md`, and the agreed backend contracts.
- It uses shared layouts and components.
- Its success, validation, empty, conflict, and error states work.
- Its forms use the agreed field names and CSRF mechanism.
- It has no unfinished placeholder buttons or links.
- It works on mobile, tablet, and desktop.
- Its labels, keyboard navigation, and focus indicators are usable.
- Any icon-only interactive control has an accessible name, and nonessential motion respects the user's reduced-motion preference.
- It does not duplicate backend rules or access the database directly from views.
- Screenshots and a short testing checklist are included in the pull request.

---

## 2. Member 1 — Shared UI Foundation and Authentication Pages

### Objective

Build the shared frontend foundation used by the entire application and create the login and registration pages.

This member's work affects all other members, so shared components and their interfaces must be agreed upon early.

### Owned scope

- Shared public layout.
- Shared authenticated application layout.
- Header and InFocus branding.
- Navigation and mobile navigation.
- Shared page containers and page headers.
- Reusable buttons, form controls, alerts, badges, cards, tables, and empty states.
- Login page.
- Student registration page.
- Basic profile interface, only if included in the agreed requirements.
- Global CSS variables and base styles.

### Suggested files

```text
public/
└── assets/
    ├── css/
    │   ├── variables.css
    │   ├── base.css
    │   ├── layout.css
    │   └── components.css
    └── js/
        └── navigation.js

app/
└── Views/
    ├── layouts/
    │   ├── public-layout.php
    │   └── app-layout.php
    └── components/
        ├── header.php
        ├── navigation.php
        ├── button.php
        ├── alert.php
        ├── badge.php
        ├── form-field.php
        ├── empty-state.php
        └── confirmation-dialog.php
```

Authentication views should follow the existing project structure rather than creating duplicate entry points.

### Implementation requirements

1. Establish the shared page shell and consistent content width.
2. Implement desktop navigation and accessible mobile navigation.
3. Display navigation links appropriate to the signed-in user's role.
4. Build login and registration forms using shared styles.
5. Include labels, appropriate input types, validation-message areas, and submit states.
6. Display authentication errors returned by the backend without exposing technical details.
7. Create reusable badges for reservation and modification statuses.
8. Provide reusable alerts, empty states, and confirmation patterns.
9. Keep global colors, typography, and spacing in one place.
10. Use the shared SVG icon treatment and retain the visible skip link and focus states in shared layouts.
11. Coordinate changes to shared files before other members depend on them.

### Acceptance criteria

- Login and registration pages follow the design system.
- Navigation works on desktop and mobile.
- Student and admin navigation states are handled.
- Forms display understandable validation and authentication messages.
- Other members can reuse shared components without copying their CSS.
- Keyboard navigation and visible focus states work.
- Icons are SVG-based, and any motion is subtle and reduced when requested by the operating system.

### Tests to submit

- Login and registration at desktop and mobile widths.
- Incorrect-credentials feedback.
- Missing or invalid field feedback.
- Mobile navigation open/close behavior.
- Examples of reusable buttons, badges, alerts, and form fields.
- Student and admin navigation states.

### Must not implement

- Backend authentication or session logic beyond the agreed contract.
- Room-management screens.
- Reservation rules or database queries.
- Student dashboard content or admin reports.

**Dependencies:** Existing routing, authentication contracts, and `design.md`.

**Deliverable:** Shared UI foundation, reusable components, and polished login/registration pages.

---

## 3. Member 2 — Floor and Room Management Interface

### Objective

Build the admin-facing screens for managing floors, rooms, facilities, operating hours, and room closures.

This member owns the management interface, not the authoritative booking-availability logic.

### Owned scope

- Floor list and create/edit forms.
- Room list and create/edit forms.
- Room category, capacity, description, floor, and status controls.
- Facility management and room-facility selection.
- Operating-hours editor.
- Room unavailability and closure forms.
- Success, warning, validation, and error states for management pages.

### Suggested files

```text
app/
└── Modules/
    └── Rooms/
        └── Views/
            ├── floors-view.php
            ├── floor-form-view.php
            ├── rooms-view.php
            ├── room-form-view.php
            ├── facilities-view.php
            ├── operating-hours-view.php
            └── unavailability-view.php

public/assets/css/
└── room-management.css
```

Adapt the filenames to the actual project structure.

### Implementation requirements

1. Reuse the shared admin layout and components.
2. Build clear tables or cards for floors and rooms.
3. Display the three allowed room categories with readable labels:

   - `individual_study` → Individual Study
   - `group_study` → Group Study
   - `lounge` → Lounge

4. Preserve the exact category values expected by the backend.
5. Build forms for floor and room details, capacity, facilities, and active/inactive status.
6. Support multiple operating intervals per room per day.
7. Allow users to add and remove interval rows using vanilla JavaScript where appropriate.
8. Build closure forms with start/end date and time, reason, and confirmation feedback.
9. Display backend validation errors near the relevant fields where possible.
10. Warn users before deactivating a floor or room or making a schedule change that could affect bookings.
11. Do not claim that a change succeeded until the backend confirms it.
12. Make it clear that deactivation preserves historical records.

### Acceptance criteria

- Admins can create and edit floors and rooms through the existing handlers.
- All three room categories are represented correctly.
- Facilities can be selected and displayed.
- Multiple operating intervals can be entered clearly.
- Closure periods and reasons are easy to review.
- Active/inactive states are obvious.
- Validation errors are understandable.
- The pages use the shared admin layout and work on mobile.

### Tests to submit

- Floor list and form.
- Room list and form.
- All three room categories.
- Facility assignment interface.
- Adding and removing operating intervals.
- Displaying backend validation errors.
- Closure creation feedback.
- Deactivation confirmation.
- Mobile and tablet layouts.

### Must not implement

- Reservation approval or rejection screens.
- Authoritative booking-conflict logic.
- Student room-discovery pages.
- Utilization calculations.
- Direct SQL queries in templates.

**Dependencies:** Member 1's shared layout/components and the room-management backend contract. Coordinate with Member 3 on changes that could affect booking availability.

**Deliverable:** Responsive admin interfaces for floor, room, facility, operating-hours, and closure management.

---

## 4. Member 3 — Reservation Forms and Reservation Action Components

### Objective

Build reusable reservation-related UI components that connect to the central `ReservationService`.

This member owns the booking, cancellation, and modification form components. Member 4 and Member 5 should reuse these components rather than rebuilding them.

### Owned scope

- Booking form.
- Reservation review/summary.
- Cancellation confirmation.
- Modification-request form.
- Original-versus-proposed reservation comparison.
- Reservation conflict and validation feedback.
- Reusable reservation action components.

### Suggested files

```text
app/
└── Modules/
    └── Reservations/
        └── Views/
            ├── booking-form-view.php
            ├── cancellation-confirmation-view.php
            ├── modification-form-view.php
            ├── modification-comparison-view.php
            └── reservation-feedback-view.php

public/assets/
├── css/
│   └── reservation-forms.css
└── js/
    └── reservation-forms.js
```

### Implementation requirements

1. Build booking controls for date, start time, and end time.
2. Display the selected room and requested interval clearly.
3. Provide a concise booking summary before submission where appropriate.
4. Build a cancellation confirmation that identifies the reservation being cancelled.
5. Show the original reservation and proposed room/time separately in modification forms.
6. Clearly identify modification requests as awaiting admin review until approved.
7. Display service-returned messages for conflicts, cutoff violations, inactive rooms, and other validation failures.
8. Use shared status badges.
9. Use the exact field names agreed upon with the backend owner.
10. Use JavaScript only for usability, such as updating summaries and displaying input hints.
11. Include CSRF fields through the shared security/form mechanism.
12. Never claim a reservation was successfully changed before the backend confirms it.

### Acceptance criteria

- Booking forms submit the expected field names and values.
- Users can review the selected room and time before submitting.
- Cancellation requires a clear confirmation.
- Modification screens distinguish original details from proposed details.
- Pending modifications are clearly labelled as awaiting review.
- Service errors are understandable and preserve safe form values when appropriate.
- Components can be reused by both student and admin modules.
- No view or JavaScript directly changes reservation status.

### Tests to submit

- Booking form required fields and input hints.
- Room/time summary.
- Conflict-message display.
- Cancellation confirmation and failure feedback.
- Modification form and original/proposed comparison.
- Pending, approved, rejected, and invalidated status display.
- Keyboard navigation and responsive form layout.

### Must not implement

- Separate booking rules in the frontend.
- The student dashboard or complete reservation-history page.
- Admin review queues or report calculations.
- Direct database updates from views.

**Dependencies:** Agreed reservation service contract, Member 1's shared components, and coordination with Members 4 and 5.

**Deliverable:** Reusable booking, cancellation, modification, and reservation-feedback components.

---

## 5. Member 4 — Student Dashboard and Room Discovery

### Objective

Build the main student-facing experience, from discovering rooms to viewing personal reservations.

This member assembles the student pages using the shared layout from Member 1 and reservation components from Member 3.

### Owned scope

- Student dashboard.
- Find a Room page.
- Search and filter interface.
- Responsive room-card grid.
- Room details page.
- My Reservations page.
- Reservation details page.
- Integration of booking, cancellation, and modification components.

### Suggested files

```text
app/
└── Modules/
    └── Student/
        └── Views/
            ├── dashboard-view.php
            ├── room-list-view.php
            ├── room-details-view.php
            ├── reservations-view.php
            └── reservation-details-view.php

public/assets/
├── css/
│   └── student-pages.css
└── js/
    └── room-filters.js
```

### Implementation requirements

1. Build a dashboard with clear next steps and relevant reservation summaries.
2. Make the Find a Study Room page centered on prominent search and filters.
3. Support the agreed filters: date, start time, end time, category, minimum capacity, and facilities.
4. Use responsive room cards with a 16:9 image or neutral placeholder.
5. Show room name, category, floor, capacity, facilities, and availability information when supplied.
6. Build room details with operating information and booking controls.
7. Build My Reservations as a desktop table and, where practical, stacked cards on mobile.
8. Display room, date, time, status, and applicable actions.
9. Include Member 3's booking, cancellation, and modification components rather than recreating them.
10. Use backend-provided room and reservation data.
11. Explain that search availability may change before the booking is submitted.
12. Provide intentional empty states for no matching rooms and no reservations.
13. Never trust a student ID supplied through a URL or form to determine whose reservations to show.

### Acceptance criteria

- Students can browse rooms and use the agreed filters.
- Room cards and detail pages follow the design system.
- Search results adapt to desktop, tablet, and mobile.
- Only the authenticated student's reservations are shown.
- Actual backend reservation statuses are displayed.
- Booking, cancellation, and modification use shared components.
- Conflict and validation responses are understandable.
- Empty states look intentional.
- No page-level horizontal scrolling occurs at the agreed viewport sizes.

### Tests to submit

- Student dashboard.
- Room discovery with no filters and multiple filters.
- No matching rooms state.
- Room details page.
- Booking form integration.
- Reservation history with multiple statuses.
- Empty reservation history.
- Conflict feedback.
- Cancellation and modification integration.
- Mobile, tablet, and desktop screenshots.

### Must not implement

- Backend search SQL or reservation SQL.
- Authoritative availability checks.
- Independent booking, cancellation, or modification rules.
- Admin room-management screens or reports.
- Duplicate reservation form components.

**Dependencies:** Shared layout/components, Member 2's room data contract, Member 3's reservation components/service contract, and the agreed student-page data contract.

**Deliverable:** Complete responsive student experience for room discovery and personal reservation management.

---

## 6. Member 5 — Admin Dashboard, Review Queues, and Reports

### Objective

Build the admin dashboard and interfaces for reviewing reservation requests, reviewing modification requests, and inspecting reports.

Room and floor CRUD remain the responsibility of Member 2.

### Owned scope

- Admin dashboard.
- Summary cards.
- Pending reservation list and detail view.
- Reservation approval/rejection controls.
- Pending modification list and detail view.
- Original-versus-proposed modification comparison.
- Reservation reports and date filters.
- Room utilization report presentation.
- Tables, charts, and report empty states.

### Suggested files

```text
app/
└── Modules/
    └── Admin/
        └── Views/
            ├── dashboard-view.php
            ├── reservations-view.php
            ├── reservation-details-view.php
            ├── modifications-view.php
            ├── modification-details-view.php
            └── reports-view.php

public/assets/
├── css/
│   └── admin-pages.css
└── js/
    └── report-filters.js
```

### Implementation requirements

1. Reuse the shared admin layout and navigation.
2. Display summary cards using backend-provided metrics.
3. Build a readable pending-reservation queue with room, student, date/time, and status.
4. Provide approve/reject actions with confirmation where appropriate.
5. Show the original reservation beside the proposed modification.
6. Display rejection and invalidation reasons when provided by the backend.
7. Build report filters using the agreed date range and supported filter values.
8. Present backend-calculated utilization and chart data.
9. Display `N/A` when the backend reports zero net available operating hours.
10. Use a chart library only if it is already available or the team explicitly approves it.
11. Include empty states for queues and reports.
12. Display the actual backend result after every review action.
13. Link to Member 2's room-management pages instead of creating duplicate CRUD screens.

### Acceptance criteria

- Dashboard cards display backend-provided values.
- Reservation and modification queues are easy to review.
- Review actions submit the correct IDs and display actual outcomes.
- Original and proposed modification details are easy to compare.
- Invalidated proposals show reasons when available.
- Report filters preserve selected values.
- Utilization is displayed exactly as calculated by the backend, including `N/A`.
- Empty queues and reports are handled.
- Pages are responsive and follow the shared design system.

### Tests to submit

- Dashboard with returned data.
- Empty dashboard queues.
- Reservation review list.
- Approve/reject confirmation and result feedback.
- Modification comparison.
- Invalidated modification display.
- Date-filtered reports.
- Utilization display, including `N/A`.
- Empty report state.
- Responsive cards and tables.
- Verify student navigation does not display admin links. Remember that server-side authorization must still protect every admin route and action.

### Must not implement

- Room/floor/facility CRUD.
- Reservation business logic or direct status updates outside the shared service.
- Report calculations in templates or JavaScript.
- Authentication/session handling.
- Duplicate layouts or shared components.

**Dependencies:** Shared components, Member 3's reservation-review contract, Member 2's room-management links, and the report data contract.

**Deliverable:** Responsive admin dashboard, reservation/modification review UI, and reports presentation.

---

## 7. Shared component ownership

To prevent merge conflicts and inconsistent styling, agree on component ownership before implementation.

| Shared item | Owner | Other members' responsibility |
|---|---|---|
| CSS variables, base styles, typography | Member 1 | Use shared tokens |
| Public and authenticated layouts | Member 1 | Include the correct layout |
| Header and navigation | Member 1 | Request changes instead of duplicating |
| Buttons, inputs, alerts, badges, empty states | Member 1 | Reuse components |
| Login and registration views | Member 1 | Do not duplicate |
| Room/floor management views | Member 2 | Link to these screens when needed |
| Booking/cancellation/modification components | Member 3 | Include shared components |
| Student dashboard, discovery, history | Member 4 | Use shared layouts and reservation components |
| Admin dashboard, review queues, reports | Member 5 | Use shared layouts and service results |
| Global routing and integration decisions | Team leader | Coordinate changes |

If the backend/full-stack plan already assigns a file to a different member, do not create a second file for the same purpose. Keep one owner and agree on the interface.

---

## 8. Frontend integration order

1. **Team leader:** Confirm page conventions, `design.md`, component names, and data passed to views.
2. **Member 1:** Build the shared layout, design tokens, components, and authentication views.
3. **Member 2:** Build room-management views using the shared admin layout.
4. **Member 3:** Build booking, cancellation, modification, and feedback components.
5. **Member 4:** Assemble the student experience using the shared reservation components.
6. **Member 5:** Assemble the admin dashboard, review queues, and reports UI.
7. **All members:** Integrate through reviewed pull requests into `test` and run end-to-end tests.
8. **Team leader:** Verify the complete UI and promote tested milestones to `main`.

This order describes dependencies, not a requirement to wait idle. Members can build their screens using agreed sample data while backend handlers are being completed.

---

## 9. Shared view contracts to agree on first

Before everyone builds pages in parallel, agree on the data each view receives. These are presentation examples, not instructions to change the database schema.

- **Current user:** `id`, `name`, `email`, `role`.
- **Room card:** `id`, `name`, `category`, `floor_name`, `capacity`, `facilities`, optional `image_path`, optional `availability_summary`.
- **Reservation row:** `id`, `room_name`, `starts_at`, `ends_at`, `status`.
- **Modification row:** modification ID, original reservation details, proposed room/time, status, and optional reason.
- **Dashboard summary:** named values returned by the relevant service.
- **Report result:** selected filters, report rows or chart series, and utilization value or `N/A`.

Match the final implementation to the actual backend contract. Do not silently invent fields the backend does not provide.

Agree on form field names with the backend owner. Examples include `room_id`, `starts_at`, `ends_at`, `proposed_room_id`, and the shared CSRF field.

---

## 10. Frontend pull request checklist

Every frontend pull request must confirm:

- [ ] Targets `test`, not `main`.
- [ ] Changes stay within the assigned scope or were coordinated.
- [ ] Existing shared components are reused.
- [ ] No second design system or unapproved dependency is introduced.
- [ ] No SQL or authoritative business rules are added to views or JavaScript.
- [ ] Forms use the agreed field names and CSRF mechanism.
- [ ] Dynamic PHP output is escaped.
- [ ] Server-side validation and authorization remain in place.
- [ ] Success, error, empty, and permission states are handled.
- [ ] Mobile, tablet, and desktop layouts have been tested.
- [ ] Keyboard focus and form labels are usable.
- [ ] UI icons are SVG-based rather than emoji, and icon-only controls have accessible names.
- [ ] Motion is subtle and supports `prefers-reduced-motion`.
- [ ] Screenshots and test results are included.
- [ ] No debug output, secrets, unrelated files, or placeholder actions remain.

---

## 11. Final frontend acceptance flows

The frontend milestone is ready when these end-to-end flows work:

1. A visitor can use the registration and login screens.
2. The shared layout displays role-appropriate navigation.
3. An admin can manage floors and rooms through Member 2's pages.
4. An admin can configure facilities, operating hours, and closures.
5. A student can search/filter rooms and open room details.
6. A student submits a booking request and sees the backend's actual result.
7. Conflicts and validation failures are shown clearly without pretending a booking succeeded.
8. A student can view personal reservation history and accurate statuses.
9. A student can submit cancellation or modification requests when permitted by the backend.
10. An admin can review reservations and modifications and see the actual results.
11. Admin reports display backend-calculated figures and handle empty/`N/A` states.
12. All pages share consistent styling and work on mobile, tablet, and desktop.
13. Direct URLs and form submissions remain protected by server-side authorization and validation.

The team leader reviews frontend pull requests, resolves shared-file conflicts, verifies the complete flow on `test`, and promotes tested milestones to `main`.

---

**Team rule:** Own your assigned screens, reuse shared components, and coordinate before changing shared files. A consistent, integrated project is more important than five individually styled modules.
