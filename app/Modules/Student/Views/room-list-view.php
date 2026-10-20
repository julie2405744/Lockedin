<?php
/**
 * app/Modules/Student/Views/room-list-view.php
 * Member 4 — "Find a Study Room" presentation.
 *
 * Renders the search/filter panel and the responsive room-card grid.
 * Pure presentation: reads $rooms exactly as prepared by the page handler
 * (later: RoomService::searchAvailableRooms). No availability rules or
 * SQL live in this file.
 *
 * Expects:
 *   $basePath      (string)
 *   $rooms         (array)  room cards after filtering:
 *                           id, name, category, floor_name, capacity,
 *                           facilities[], optional image_path,
 *                           optional availability_summary
 *   $facilities    (array)  catalog: ['id' => int, 'name' => string]
 *   $filters       (array)  current values: date, start_time, end_time,
 *                           category, min_capacity, facilities[] (ids)
 *   $activeFilters (array)  chips: ['label', 'removeUrl']
 *   $resultCount   (int)
 *   $totalCount    (int)
 */

$basePath      = $basePath ?? '';
$rooms         = $rooms ?? [];
$facilities    = $facilities ?? [];
$filters       = $filters ?? [];
$activeFilters = $activeFilters ?? [];
$resultCount   = (int) ($resultCount ?? 0);
$totalCount    = (int) ($totalCount ?? 0);
?>
<form id="room-filter-form" method="get" action="<?= esc($basePath) ?>/student/rooms.php"
      aria-label="Search and filter study rooms">
    <div class="filter-panel">
        <!-- Mobile toggle -->
        <div class="filter-panel__toggle-row">
            <span style="font-weight:600;color:var(--color-heading);font-size:var(--font-size-sm);">Filters</span>
            <button id="filter-toggle" type="button" class="btn btn--secondary btn--sm"
                    aria-expanded="true" aria-controls="filter-panel-body">
                Show / hide filters
            </button>
        </div>

        <div id="filter-panel-body" class="filter-panel__body" data-collapsed="false">
            <div class="filter-panel__grid">

                <div class="filter-panel__field">
                    <label class="form-label" for="filter-date">Date</label>
                    <input type="date" id="filter-date" name="date" class="form-control"
                           value="<?= esc((string) ($filters['date'] ?? '')) ?>">
                </div>

                <div class="filter-panel__field">
                    <label class="form-label" for="filter-start">Start time</label>
                    <input type="time" id="filter-start" name="start_time" class="form-control"
                           value="<?= esc((string) ($filters['start_time'] ?? '')) ?>">
                </div>

                <div class="filter-panel__field">
                    <label class="form-label" for="filter-end">End time</label>
                    <input type="time" id="filter-end" name="end_time" class="form-control"
                           value="<?= esc((string) ($filters['end_time'] ?? '')) ?>">
                </div>

                <div class="filter-panel__field">
                    <label class="form-label" for="filter-capacity">Minimum capacity</label>
                    <input type="number" id="filter-capacity" name="min_capacity" class="form-control"
                           min="1" value="<?= esc((string) ($filters['min_capacity'] ?? '')) ?>"
                           placeholder="Any">
                </div>

                <div class="filter-panel__field filter-panel__field--wide">
                    <label class="form-label" for="filter-category">Room category</label>
                    <select id="filter-category" name="category" class="form-control">
                        <option value="">All categories</option>
                        <option value="individual_study" <?= ($filters['category'] ?? '') === 'individual_study' ? 'selected' : '' ?>>Individual Study</option>
                        <option value="group_study"<?= ($filters['category'] ?? '') === 'group_study' ? ' selected' : '' ?>>Group Study</option>
                        <option value="lounge"<?= ($filters['category'] ?? '') === 'lounge' ? ' selected' : '' ?>>Lounge</option>
                    </select>
                </div>

                <fieldset class="filter-panel__field filter-panel__field--wide">
                    <legend class="form-label">Facilities</legend>
                    <div class="filter-panel__check-row">
                        <?php foreach ($facilities as $facility): ?>
                            <label class="filter-panel__check">
                                <input type="checkbox" name="facilities[]"
                                       value="<?= (int) $facility['id'] ?>"
                                       <?= in_array((int) $facility['id'], array_map('intval', $filters['facilities'] ?? []), true) ? 'checked' : '' ?>>
                                <?= esc($facility['name']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
            </div>

            <div class="filter-panel__actions">
                <button type="button" id="filter-reset" class="btn btn--ghost">Reset</button>
                <button type="submit" class="btn btn--primary"
                        data-submit-state="Searching…">Search Rooms</button>
            </div>
        </div>
    </div>
</form>

<!-- Active filters + result count -->
<div class="results-info" role="status">
    <span class="results-info__count">
        <?= (int) $resultCount ?> of <?= (int) $totalCount ?> rooms
        <?= $resultCount === 1 ? 'matches' : 'match' ?> your search
    </span>
    <?php if ($activeFilters): ?>
        <div class="results-info__chips">
            <?php foreach ($activeFilters as $chip): ?>
                <span class="filter-chip">
                    <?= esc($chip['label']) ?>
                    <a href="<?= esc($chip['removeUrl']) ?>" aria-label="Remove filter: <?= esc($chip['label']) ?>">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </a>
                </span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Availability caveat (required by the specification) -->
<div class="alert alert--info" role="status">
    <svg class="alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
    </svg>
    <div class="alert__body">
        <p>Search results show rooms that <strong>look available</strong> right now. Availability is
        checked again by the server when you submit a booking, so a listed room may still be
        busy by the time your request is reviewed.</p>
    </div>
</div>

<!-- Room cards -->
<?php if ($rooms): ?>
    <div class="room-grid">
        <?php foreach ($rooms as $room): ?>
            <?php
            $detailUrl = $basePath . '/student/room-details.php?id=' . (int) $room['id'];
            $facilityNames = array_map('strval', $room['facilities'] ?? []);
            $visibleChips  = array_slice($facilityNames, 0, 3);
            $extraCount    = count($facilityNames) - count($visibleChips);
            $availability  = $room['availability_summary'] ?? null;
            $isFree        = $availability !== null
                && stripos((string) $availability, 'available') !== false;
            ?>
            <article class="room-card">
                <a class="room-card__link" href="<?= esc($detailUrl) ?>"
                   aria-label="View details for <?= esc((string) $room['name']) ?>">
                    <div class="card__img-wrap">
                        <?php if (!empty($room['image_path'])): ?>
                            <img src="<?= esc((string) $room['image_path']) ?>">
                        <?php else: ?>
                            <div class="card__img-placeholder">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M15 9h.01M9 13h.01M15 13h.01M9 17h.01M15 17h.01"/>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
                <div class="card__body">
                    <span class="badge badge--active room-card__category">
                        <?= esc(roomCategoryLabel((string) $room['category'])) ?>
                    </span>
                    <a class="room-card__link" href="<?= esc($detailUrl) ?>">
                        <h3 class="card__title"><?= esc((string) $room['name']) ?></h3>
                    </a>
                    <div class="card__meta">
                        <span class="card__meta-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M15 9h.01M9 13h.01M15 13h.01M9 17h.01M15 17h.01"/>
                            </svg>
                            <?= esc((string) $room['floor_name']) ?>
                        </span>
                        <span class="card__meta-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                            Seats <?= esc((string) $room['capacity']) ?>
                        </span>
                    </div>
                    <?php if ($availability !== null): ?>
                        <p class="room-card__availability <?= $isFree ? 'room-card__availability--free' : 'room-card__availability--busy' ?>">
                            <?php if ($isFree): ?>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M20 6 9 17l-5-5"/>
                                </svg>
                            <?php else: ?>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                                </svg>
                            <?php endif; ?>
                            <?= esc((string) $availability) ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($visibleChips): ?>
                        <p class="room-card__facilities-label">Facilities</p>
                        <div class="room-card__chips">
                            <?php foreach ($visibleChips as $facilityName): ?>
                                <span class="room-chip"><?= esc($facilityName) ?></span>
                            <?php endforeach; ?>
                            <?php if ($extraCount > 0): ?>
                                <span class="room-chip room-chip--more">+<?= (int) $extraCount ?> more</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card__footer">
                    <a href="<?= esc($detailUrl) ?>" class="btn btn--primary btn--sm">View details</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-state__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
        </div>
        <h2 class="empty-state__title">No rooms match your search</h2>
        <p class="empty-state__description">
            Try removing some filters, lowering the minimum capacity, or searching a different
            date and time. New rooms appear here as soon as they are published.
        </p>
        <a href="<?= esc($basePath) ?>/student/rooms.php" class="btn btn--primary">Clear all filters</a>
    </div>
<?php endif; ?>
