<?php
/**
 * app/Core/helpers.php
 * Shared utility functions — team leader ownership.
 * Changes must be coordinated with the team.
 */

/**
 * Safely echo an HTML-escaped string.
 */
function e(string $value): void
{
    echo htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Escape a string for HTML output and return it.
 */
function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Format a UTC DATETIME string for display in Africa/Cairo timezone.
 *
 * @param string $utcDatetime e.g. '2026-10-19 10:00:00'
 * @param string $format      PHP date format string
 * @return string
 */
function formatLocalTime(string $utcDatetime, string $format = 'D, d M Y H:i'): string
{
    try {
        $dt = new DateTimeImmutable($utcDatetime, new DateTimeZone('UTC'));
        $dt = $dt->setTimezone(new DateTimeZone('Africa/Cairo'));
        return $dt->format($format);
    } catch (Exception $e) {
        return $utcDatetime;
    }
}

/**
 * Convert a local Africa/Cairo datetime string to UTC for storage.
 *
 * @param string $localDatetime e.g. '2026-10-19 13:00:00'
 * @return string UTC datetime string
 */
function toUtc(string $localDatetime): string
{
    try {
        $dt = new DateTimeImmutable($localDatetime, new DateTimeZone('Africa/Cairo'));
        return $dt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return $localDatetime;
    }
}

/**
 * Map a room category DB value to a readable label.
 */
function roomCategoryLabel(string $category): string
{
    $map = [
        'individual_study' => 'Individual Study',
        'group_study'      => 'Group Study',
        'lounge'           => 'Lounge',
    ];
    return $map[$category] ?? ucfirst(str_replace('_', ' ', $category));
}

/**
 * Map a reservation status to a badge class.
 */
function reservationStatusBadge(string $status): string
{
    $map = [
        'pending'   => 'badge--pending',
        'confirmed' => 'badge--confirmed',
        'rejected'  => 'badge--rejected',
        'cancelled' => 'badge--cancelled',
    ];
    return $map[$status] ?? 'badge--cancelled';
}

/**
 * Map a modification status to a badge class.
 */
function modificationStatusBadge(string $status): string
{
    $map = [
        'pending'     => 'badge--pending-review',
        'approved'    => 'badge--approved',
        'rejected'    => 'badge--rejected',
        'invalidated' => 'badge--invalidated',
    ];
    return $map[$status] ?? 'badge--cancelled';
}

/**
 * Map a modification status to a readable label.
 */
function modificationStatusLabel(string $status): string
{
    $map = [
        'pending'     => 'Pending Review',
        'approved'    => 'Approved',
        'rejected'    => 'Rejected',
        'invalidated' => 'No Longer Available',
    ];
    return $map[$status] ?? ucfirst($status);
}
