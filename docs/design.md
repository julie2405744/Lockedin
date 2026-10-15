# InFocus — UI Design System

**Project:** InFocus — Study Room Booking System
**Document:** `docs/design.md`

## 1. Design Direction

InFocus should follow the clean, professional workspace-directory style of the reference website.

The interface should emphasize:

- A clean, spacious layout.
- Prominent search and filtering.
- Image-based room cards.
- Clear typography and readable room information.
- Subtle borders and shadows.
- Consistent spacing and alignment.
- Simple, intuitive booking workflows.
- Responsive layouts for desktop, tablet, and mobile.

Adapt the reference to a university study-room booking platform. Replace coworking-specific features with room categories, floor information, capacity, facilities, operating hours, reservations, and availability.

Avoid excessive gradients, glassmorphism, decorative animations, unnecessary visual effects, emoji headings, and cluttered layouts.

## 2. Brand Identity

### Brand personality

InFocus should feel focused, dependable, organized, modern, and approachable.

### Color palette

Use the following palette consistently throughout the application.

| Token              | Hex       | Purpose                                          |
| ------------------ | --------- | ------------------------------------------------ |
| Primary            | `#2563EB` | Primary buttons, active links, selected controls |
| Primary Hover      | `#1D4ED8` | Hover state for primary actions                  |
| Primary Soft       | `#EFF6FF` | Selected filters and subtle highlights           |
| Heading            | `#172B4D` | Main headings and important text                 |
| Body Text          | `#475569` | Paragraphs and descriptions                      |
| Muted Text         | `#64748B` | Metadata and supporting information              |
| Page Background    | `#F8FAFC` | Main application background                      |
| Surface            | `#FFFFFF` | Cards, tables, forms, and panels                 |
| Border             | `#E2E8F0` | Borders and dividers                             |
| Success            | `#15803D` | Successful actions and confirmed reservations    |
| Success Background | `#F0FDF4` | Success alerts                                   |
| Warning            | `#B45309` | Pending reservations and warnings                |
| Warning Background | `#FFFBEB` | Warning alerts                                   |
| Danger             | `#B91C1C` | Errors and rejected actions                      |
| Danger Background  | `#FEF2F2` | Error alerts                                     |
| Neutral Background | `#F1F5F9` | Neutral badges and secondary surfaces            |

Use CSS custom properties for shared colors.

```css
:root {
    --color-primary: #2563EB;
    --color-primary-hover: #1D4ED8;
    --color-primary-soft: #EFF6FF;

    --color-heading: #172B4D;
    --color-text: #475569;
    --color-muted: #64748B;

    --color-background: #F8FAFC;
    --color-surface: #FFFFFF;
    --color-border: #E2E8F0;

    --color-success: #15803D;
    --color-success-bg: #F0FDF4;

    --color-warning: #B45309;
    --color-warning-bg: #FFFBEB;

    --color-danger: #B91C1C;
    --color-danger-bg: #FEF2F2;

    --color-neutral-bg: #F1F5F9;
}
```

Do not introduce a second palette for individual modules.

## 3. Typography

Use **Inter** if it is already available through the project's approved assets. Otherwise, use a system font stack.

```css
body {
    font-family: Inter, system-ui, -apple-system,
        BlinkMacSystemFont, "Segoe UI", sans-serif;
    color: var(--color-text);
    background: var(--color-background);
    font-size: 15px;
    line-height: 1.6;
}
```

Typography guidelines:

- Page titles: `28–32px` on desktop.
- Page titles on mobile: `24–28px`.
- Section headings: `18–22px`.
- Card titles: `16–18px`.
- Body text: `14–16px`.
- Supporting metadata: at least `12px`.
- Use medium or semibold weights for titles.
- Use sentence case for headings and buttons.

Maintain a clear hierarchy without using excessive font sizes or weights.

## 4. Layout and Spacing

Use a consistent spacing scale:

`4px, 8px, 12px, 16px, 24px, 32px, 40px, 48px`

Recommended layout rules:

- Maximum content width: approximately `1280px`.
- Desktop page padding: `32px`.
- Tablet page padding: `24px`.
- Mobile page padding: `16px`.
- Standard card radius: `12px`.
- Input radius: `8px`.
- Button radius: `8px`.
- Card grid gap: `20–24px`.

Cards and panels should use white backgrounds, subtle borders, and restrained shadows.

Use borders as the primary way to distinguish surfaces. Shadows should remain subtle.

## 5. Shared Application Layout

All pages must reuse the same application shell and shared UI components.

### Desktop navigation

The header should include:

- InFocus wordmark on the left.
- Navigation links relevant to the current user role.
- User profile or account menu on the right.

Keep the header compact and visually clean.

### Student navigation

- Dashboard
- Find a Room
- My Reservations
- Account, if implemented

### Admin navigation

- Dashboard
- Reservations
- Modifications
- Rooms and Floors
- Reports
- Account, if implemented

Navigation must reflect the authenticated user's role. Hiding links does not replace server-side authorization.

### Mobile navigation

- Keep the InFocus brand visible.
- Collapse navigation into an accessible menu.
- Avoid horizontal overflow.
- Stack page headings and action buttons when necessary.
- Ensure controls remain easy to tap.

## 6. Room Discovery Page

The room discovery page is the main page inspired by the reference website's workspace listing.

### Page heading

Use the title:

**Find a Study Room**

Include a short description explaining that students can discover rooms based on their preferred time, capacity, category, and facilities.

Place the search and filtering area directly below the heading.

### Search and filters

Use a clean filter panel with a white background, subtle border, and consistent internal spacing.

Supported filters may include:

- Date
- Start time
- End time
- Room category
- Minimum capacity
- Facilities

Provide a prominent **Search Rooms** button and a less prominent reset action.

On desktop, arrange filters in a compact horizontal layout or a sidebar, depending on the available space.

On mobile, stack filters vertically or place them inside a collapsible panel.

Display the number of matching rooms and any active filters near the results.

Important: room search results do not guarantee that a room will remain available. The reservation service must revalidate availability when a booking is submitted.

## 7. Room Listing Cards

Use a responsive grid of room cards inspired by the reference's listing design.

### Card contents

Each room card should display:

1. Room image or neutral placeholder.
2. Room name.
3. Room category.
4. Floor information.
5. Capacity.
6. Facilities summary.
7. Availability information when relevant.
8. A clear action such as **View Details**.

Do not display unrelated coworking information such as prices, ratings, business addresses, or walking distances.

### Card appearance

- White background.
- Subtle border.
- Rounded corners.
- Consistent image aspect ratio, preferably `16:9`.
- Content padding of approximately `16–20px`.
- Clear distinction between title and metadata.
- Subtle hover state.
- No unnecessary animation.

Room cards should have reasonably consistent heights within each grid row.

### Responsive grid

- Wide desktop: three columns.
- Tablet or smaller desktop: two columns.
- Mobile: one column.

The grid must adapt naturally to the available width.

### Room category labels

Display the database categories using readable labels:

| Database value     | Display label    |
| ------------------ | ---------------- |
| `individual_study` | Individual Study |
| `group_study`      | Group Study      |
| `lounge`           | Lounge           |

Do not change the underlying database values to match display labels.

## 8. Room Details Page

The room details page should help students understand the room before requesting a reservation.

Display:

- Room name and category.
- Room image.
- Floor.
- Capacity.
- Description.
- Facilities.
- Operating hours.
- Date and time selection.
- Booking action.
- Availability or validation feedback.

On desktop, use two columns when appropriate: room information on one side and booking controls on the other.

On mobile, stack the content vertically.

The booking action should be easy to locate without overwhelming the page.

## 9. Forms and Booking Feedback

### Form styling

- Every input must have a visible label.
- Use consistent input heights and padding.
- Group related fields.
- Display validation errors near the relevant field.
- Keep forms readable and avoid unnecessary fields.
- Provide visible focus states.
- Use consistent primary and secondary buttons.

### Reservation statuses

Use consistent text badges throughout the application.

| Status      | Label     | Treatment |
| ----------- | --------- | --------- |
| `pending`   | Pending   | Amber     |
| `confirmed` | Confirmed | Green     |
| `rejected`  | Rejected  | Red       |
| `cancelled` | Cancelled | Neutral   |

### Modification statuses

| Status        | Label               |
| ------------- | ------------------- |
| `pending`     | Pending Review      |
| `approved`    | Approved            |
| `rejected`    | Rejected            |
| `invalidated` | No Longer Available |

Badges must always contain readable text. Never rely on color or an icon alone to communicate a status.

### Feedback messages

Use consistent feedback components for:

- Successful actions.
- Validation errors.
- Booking conflicts.
- Permission errors.
- Unexpected failures.

Messages should explain the outcome clearly without exposing SQL errors, stack traces, or internal technical details.

## 10. My Reservations Page

Use a clean table on desktop and stacked cards on mobile.

Each reservation should display:

- Room.
- Date.
- Start and end times.
- Reservation status.
- Available actions.

Show cancellation or modification actions only when they are potentially applicable. The server remains responsible for enforcing all booking rules and cutoffs.

Students must only see their own reservations.

Past reservations should remain visible when required. Historical reservation records must never be deleted merely to simplify the interface.

## 11. Admin Dashboard

The admin dashboard should follow the same visual system as the student portal.

### Dashboard structure

Include:

- Page title and description.
- Summary cards.
- Pending reservation queue.
- Pending modification queue.
- Quick links to room management and reports.
- Charts when they provide useful information.

### Summary cards

Examples include:

- Pending Reservations
- Confirmed Reservations
- Active Rooms
- Pending Modifications

Cards should use a white background, subtle border, prominent metric, and short supporting label.

Avoid decorative icons that do not add meaning.

### Reservation review

Use a readable table on desktop. On mobile, use stacked records or a contained horizontally scrollable table when necessary.

Approval and rejection actions must call the shared reservation service and display the actual result.

### Room management

Use consistent forms and tables for floors, rooms, facilities, operating hours, and closures.

Use clear active/inactive badges. Preserve historical records when deactivating a room or floor.

## 12. Reports and Charts

Charts should be clean, restrained, and easy to understand.

Requirements:

- Clear chart titles.
- Readable labels and legends.
- Consistent styling.
- No 3D charts or decorative effects.
- No unnecessary animation.
- A readable summary or table for important information.
- Mobile-friendly layouts.

Reuse an existing approved chart library if available. Do not add a new dependency without team approval.

### Room utilization

Use the agreed formula:

**Utilization = Confirmed Reservation Hours ÷ Net Available Operating Hours × 100**

Net available operating hours must account for room closures without double-counting overlapping unavailable periods.

- Confirmed reservations count toward confirmed usage.
- Pending reservations may be displayed separately as demand.
- Rejected and cancelled reservations do not count as confirmed usage.
- Display `N/A` when net available operating hours are zero.

## 13. Shared UI Components

Reuse existing components before creating new ones.

### Buttons

- Primary: main action.
- Secondary: lower-priority action.
- Danger: cancellation or other potentially destructive action.
- Disabled: visibly unavailable action.

Buttons must have clear labels and keyboard-focus states.

### Inputs

- Consistent borders and dimensions.
- Visible labels.
- Clear focus states.
- Field-level validation messages.
- Mobile-friendly sizing.

### Tables

- Clear header row.
- Consistent alignment.
- Adequate cell padding.
- Subtle row dividers.
- Responsive behavior.

### Dialogs

Use dialogs only when a focused decision is necessary. Keep them concise and keyboard accessible.

### Loading and empty states

Every list or dashboard section must handle:

- No results.
- No reservations yet.
- Empty review queues.
- Loading, if applicable.
- Permission errors.
- Validation errors.
- Unexpected failures.

Do not leave blank sections that appear broken.

## 14. Responsive Design

Use mobile-first CSS and verify the following approximate breakpoints.

| Breakpoint   |         Width | Expected behavior                                     |
| ------------ | ------------: | ----------------------------------------------------- |
| Mobile       | Below `576px` | One-column cards, stacked forms, collapsed navigation |
| Tablet       |   `576–991px` | Two-column cards where space allows                   |
| Desktop      |  `992–1199px` | Full navigation and wider layouts                     |
| Wide desktop |     `1200px+` | Three-column room grid and wider content              |

Requirements:

- No page-level horizontal scrolling.
- No clipped buttons or inputs.
- No overlapping navigation.
- Tables must adapt or scroll within their own containers.
- Touch targets should generally be around `44 × 44px` where practical.
- Forms must remain usable on narrow screens.

Test intermediate widths, not just the breakpoints themselves.

## 15. Accessibility

- Use semantic HTML.
- Maintain a logical heading hierarchy.
- Associate form labels with their controls.
- Ensure keyboard navigation works.
- Provide visible focus states.
- Maintain sufficient text/background contrast.
- Never communicate status using color alone.
- Add useful alternative text to meaningful room images.
- Give icon-only buttons accessible names.
- Respect reduced-motion preferences if transitions are used.

## 16. Images and Assets

- Use room photos only when permitted.
- Keep room-card image ratios consistent.
- Provide neutral placeholders when images are missing.
- Avoid relying on unstable external image URLs.
- Optimize assets for loading performance.
- Ensure missing images do not break the layout.

## 17. Architecture and Implementation Constraints

This document defines the presentation system. It does not authorize changes to the project architecture.

- Keep the agreed PHP, MySQL/MariaDB, PDO, HTML, CSS, and JavaScript stack.
- Reuse the existing application layout and components.
- Keep database queries out of view templates.
- Keep reservation validation and status transitions inside `ReservationService`.
- Use the shared authentication and authorization helpers.
- Use prepared statements, server-side validation, CSRF protection, and output escaping.
- Store reservation and closure timestamps in UTC.
- Interpret operating hours and display local times using `Africa/Cairo`.
- Do not introduce unapproved frameworks, libraries, tables, or schema changes.
- Do not modify architecture or requirements documents while implementing UI changes.
- Reuse existing styles and components instead of building duplicate versions.

## 18. Design Acceptance Checklist

A page is considered aligned with InFocus when:

- It follows the shared color palette and typography.
- Spacing, borders, and shadows are consistent.
- The page title and primary action are clear.
- Room discovery uses readable, searchable room cards.
- Status badges follow the shared status system.
- Empty, error, and success states are implemented.
- Desktop, tablet, and mobile layouts are usable.
- Forms are accessible and responsive.
- No page-level horizontal scrolling occurs.
- Existing shared components are reused.
- No redundant design system or unapproved framework is introduced.
- The existing architecture and booking rules remain unchanged.

## 19. Visual Reference

Use the following page as inspiration for listing layouts, search and filter placement, card composition, spacing, and overall visual cleanliness:

[Spaces — All Spaces by Themesberg](https://demo.themesberg.com/spaces/html/all-spaces.html)

InFocus should adopt the reference's visual approach while retaining its own university-focused functionality and identity.
