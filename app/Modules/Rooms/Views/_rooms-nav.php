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
    'floors' => ['label' => 'Floors', 'href' => $basePath . '/admin/floors.php'],
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
