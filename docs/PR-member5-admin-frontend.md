# Member 5 — Admin dashboard, review queues and reports

**Branch:** `feature/frontend-adminDashboard`
**Target:** `test` (never `main`)
**Owner:** Member 5 — admin dashboard, review queues, reports

---

## 1. What this pull request delivers

The admin-facing part of InFocus: a dashboard with the summary metrics and both
review queues, the reservation and modification review screens, and the
reservation and utilization reports.

Everything is built on the shared design system (`docs/design.md`) and the
shared layouts and components. No new colours, fonts, spacing scale or
component styles were introduced, and no framework or dependency was added.

### Pages

| Page | Route |
| --- | --- |
| Admin dashboard | `/admin/dashboard.php` |
| Reservation queue | `/admin/reservations.php` |
| Reservation detail and review | `/admin/reservation-details.php?id=NN` |
| Modification queue | `/admin/modifications.php` |
| Modification detail and review | `/admin/modification-details.php?id=NN` |
| Reports (reservations + utilization) | `/admin/reports.php` |

All of them are linked from the shared admin navigation, which already pointed
at these routes.

---

## 2. Phases

The work was committed in four phases so that it can be reviewed in pieces.

| Phase | Commit | Content |
| --- | --- | --- |
| 1 | `feat(admin): add report and admin-review data layer` | `ReportRepository`, `AdminReviewRepository`, `ReportService`, `AdminReviewService` |
| 2 | `feat(admin): add shared admin module styles and scripts` | `public/assets/css/admin-pages.css`, `public/assets/js/report-filters.js` |
| 3 | `feat(admin): add admin dashboard, review and report views` | `app/Modules/Admin/Views/*` (6 views) |
| 4 | `feat(admin): add admin dashboard, review and report pages` | `public/admin/*.php` (6 pages, dashboard stub replaced) |

### Phase 1 — data layer

* `app/Repositories/ReportRepository.php` — read-only queries for the dashboard
  counters, the reservation report and the utilization report.
* `app/Repositories/AdminReviewRepository.php` — read-only queries for the two
  review queues and their detail screens.
* `app/Services/ReportService.php` — **owned by Member 5**
  (`docs/member-tasks.md` §8). It owns every report calculation:
  `getDashboardSummary()`, `getReservationReport()` and
  `getRoomUtilizationReport()`, all returning the shared result shape from
  `docs/api-contracts.md` §2.4.
* `app/Services/AdminReviewService.php` — reads for the queues and the review
  decisions. Every decision is **delegated to `ReservationService`**; this
  service never writes a reservation status and never repeats a booking rule.

### Phase 2 — module assets

* `public/assets/css/admin-pages.css` — summary cards, two column panels,
  responsive tables, detail facts, review actions, utilization bars, quick
  links. It only uses the shared tokens from `variables.css`.
* `public/assets/js/report-filters.js` — confirmation before a review action,
  a visible working state on the pressed button, and quick reporting ranges.

### Phase 3 — views

`app/Modules/Admin/Views/` — `dashboard-view.php`, `reservations-view.php`,
`reservation-details-view.php`, `modifications-view.php`,
`modification-details-view.php`, `reports-view.php`.

The views contain no SQL and no report arithmetic. Feedback messages reuse
Member 3's `reservation-feedback-view.php`, and the modification screen reuses
Member 3's `modification-comparison-view.php` with its admin review mode, so
the student screen and the admin screen always show the same comparison.

### Phase 4 — pages

`public/admin/dashboard.php` (replaces the stub), `reservations.php`,
`reservation-details.php`, `modifications.php`, `modification-details.php`,
`reports.php`.

Every page calls `Auth::requireAdmin()`, every form carries the shared CSRF
field, and every state change uses POST-redirect-GET.

---

## 3. Utilization calculation

`ReportService::getRoomUtilizationReport()` follows the agreed definition:

```
utilization = confirmed reservation hours / net available operating hours x 100
```

* The denominator is the scheduled operating time of the window minus the
  closure periods.
* Overlapping closures are merged, so a closure is never subtracted twice.
* A closure is only counted for the part of it that falls inside an opening
  interval.
* Pending reservations are reported separately as **demand** and never counted
  as confirmed usage.
* Rejected and cancelled reservations are never counted as confirmed usage.
* When the net available hours are zero the result is `N/A`, never a division
  by zero.

The service is the only place that calculates any of this — no page, template
or script repeats the formula.

---

## 4. Integration note — `ReservationService`

The approve/reject actions on the reservation and modification screens call
`ReservationService` (Member 3, `docs/api-contracts.md` §3.3) through
`AdminReviewService`.

`ReservationService` does not exist in the repository yet, so until it lands
the review actions answer with the honest result:

> The reservation review engine is not connected yet, so nothing was changed.

Nothing is written and no success is claimed. As soon as Member 3's service is
merged, the same buttons call it and display the result it returns — no change
to this pull request is needed, because `AdminReviewService` looks the service
up at call time and normalises whatever result shape it returns.

**Coordination request for Member 3:** the shared
`modification-comparison-view.php` review buttons do not include a rejection
reason field. `AdminReviewService::rejectModification()` already accepts an
optional reason and the reservation rejection form already captures one. Adding
the reason field to the shared component would need a change in Member 3's
file, so it was not done here.

---

## 5. Test checklist and results

| Test | Result |
| --- | --- |
| Dashboard with returned data | Pass — four summary cards, both queues, shortcuts |
| Empty dashboard queues | Pass — "Nothing is waiting" state in both panels |
| Reservation queue with pending, confirmed, rejected and cancelled filters | Pass |
| Reservation queue empty state | Pass — "No reservations match the selected filters" |
| Reservation detail with all facts | Pass — room, floor, category, capacity, student, date, time, duration, status |
| Approve / reject action posts the right IDs | Pass — `reservation_id`, `decision`, `reason` + CSRF |
| Review action shows the real backend result | Pass — the failure result is shown, nothing is changed |
| Student tries to open an admin page | Pass — HTTP 403 "Access denied" |
| Modification queue | Pass |
| Modification comparison (original vs proposed) | Pass — shared Member 3 component, admin review mode |
| Invalidated proposal shows its reason | Pass — badge "No Longer Available" and the administrator note |
| Only pending requests can be reviewed | Pass — no review buttons on a decided request |
| Date-filtered reservation report | Pass — filters are applied by `ReportService` and preserved in the form |
| Report filters keep their values | Pass — invalid ranges are reported and the inputs keep what was typed |
| Utilization with confirmed bookings | Pass — 3 confirmed hours / 350 net available hours = 0.9% |
| Utilization with pending / rejected / cancelled bookings | Pass — pending is shown as demand only, rejected and cancelled are excluded |
| Zero available operating hours | Pass — `N/A` is displayed instead of a percentage |
| Empty report result | Pass — "No reservations in this window" |
| Mobile, tablet and desktop layouts | Pass — checked at 390px, 768px and 800px+, no horizontal overflow |
| Keyboard navigation and focus states | Pass — shared layout focus states kept, every control has a label |
| Console errors | None on every page |

Accessibility audit (Lighthouse, admin dashboard): accessibility 96,
best practices 83, SEO 75. The remaining contrast warning is in the shared
header wordmark, which belongs to Member 1.

---

## 6. Screenshots

Screenshots of the dashboard, both queues, both detail screens and the reports
are attached to the pull request, at mobile (390px), tablet (768px) and
desktop widths.

---

## 7. Files

### Added

```text
app/Modules/Admin/Views/dashboard-view.php
app/Modules/Admin/Views/reservations-view.php
app/Modules/Admin/Views/reservation-details-view.php
app/Modules/Admin/Views/modifications-view.php
app/Modules/Admin/Views/modification-details-view.php
app/Modules/Admin/Views/reports-view.php
app/Repositories/ReportRepository.php
app/Repositories/AdminReviewRepository.php
app/Services/ReportService.php
app/Services/AdminReviewService.php
public/admin/reservations.php
public/admin/reservation-details.php
public/admin/modifications.php
public/admin/modification-details.php
public/admin/reports.php
public/assets/css/admin-pages.css
public/assets/js/report-filters.js
```

### Changed

```text
public/admin/dashboard.php   (the "Member 5 will build this page" stub replaced)
```

### Not touched

Shared files owned by other members (`app/Views/layouts/*`, `app/Core/*`,
`app/Modules/Reservations/Views/*`, `public/assets/css/{variables,base,layout,components}.css`,
the room management pages) were not modified.
