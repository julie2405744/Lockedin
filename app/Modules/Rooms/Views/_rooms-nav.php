<?php
/**
 * app/Modules/Rooms/Views/_rooms-nav.php
 * Sub-navigation shared by all room-management screens — Member 2.
 *
 * Expects:
 *   $active  (string) one of the section keys below
 *   $basePath (string)
 *
 * Only implemented sections are listed; later milestones add theirs.
 */

$roomsNavSections = [
    'floors'     => ['label' => 'Floors',          'href' => $basePath . '/admin/floors.php'],
    'rooms'      => ['label' => 'Rooms',           'href' => $basePath . '/admin/rooms.php'],
    'facilities' => ['label' => 'Facilities',      'href' => $basePath . '/admin/facilities.php'],
    'hours'      => ['label' => 'Operating hours', 'href' => $basePath . '/admin/operating-hours.php'],
    'closures'   => ['label' => 'Closures',        'href' => $basePath . '/admin/closures.php'],
];
?>
<nav class="rooms-nav" aria-label="Room management sections">
    <?php foreach ($roomsNavSections as $key => $section): ?>
        <a href="<?= esc($section['href']) ?>"
           class="rooms-nav__link<?= $active === $key ? ' rooms-nav__link--active' : '' ?>"
           <?= $active === $key ? 'aria-current="page"' : '' ?>>
            <?= esc($section['label']) ?>
        </a>
    <?php endforeach; ?>
</nav>
