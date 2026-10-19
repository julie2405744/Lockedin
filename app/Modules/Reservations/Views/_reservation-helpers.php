<?php
/**
 * app/Modules/Reservations/Views/_reservation-helpers.php
 * Small presentation helpers shared by Member 3's reservation components.
 *
 * These are display-only helpers. They contain no booking rules and never
 * touch the database. All authoritative validation and status transitions
 * stay in ReservationService on the server.
 *
 * The `function_exists` guards keep the file safe to include more than once.
 */

if (!function_exists('reservationStatusLabel')) {
    /**
     * Readable label for a reservation status.
     * Values match database.sql: pending, confirmed, rejected, cancelled.
     */
    function reservationStatusLabel(string $status): string
    {
        $labels = [
            'pending'   => 'Pending',
            'confirmed' => 'Confirmed',
            'rejected'  => 'Rejected',
            'cancelled' => 'Cancelled',
        ];

        return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }
}

if (!function_exists('reservationFacilityNames')) {
    /**
     * Normalise a facilities list into an array of display strings.
     * Accepts either ['Wi-Fi', ...] or [['id' => 1, 'name' => 'Wi-Fi'], ...].
     *
     * @param  array $facilities
     * @return string[]
     */
    function reservationFacilityNames(array $facilities): array
    {
        $names = [];

        foreach ($facilities as $facility) {
            if (is_array($facility)) {
                $name = (string) ($facility['name'] ?? '');
            } else {
                $name = (string) $facility;
            }

            $name = trim($name);
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return $names;
    }
}

if (!function_exists('reservationDurationLabel')) {
    /**
     * Human duration between two local datetime strings. Accepts
     * 'Y-m-d\TH:i' (datetime-local) or 'Y-m-d H:i'. Returns '' when a value
     * is missing/invalid or the end is not after the start.
     *
     * Display only — the server is the authority on duration limits.
     */
    function reservationDurationLabel(string $start, string $end): string
    {
        $start = trim($start);
        $end   = trim($end);

        if ($start === '' || $end === '') {
            return '';
        }

        try {
            $from = new DateTimeImmutable(str_replace('T', ' ', $start));
            $to   = new DateTimeImmutable(str_replace('T', ' ', $end));
        } catch (Exception $e) {
            return '';
        }

        $seconds = $to->getTimestamp() - $from->getTimestamp();
        if ($seconds <= 0) {
            return '';
        }

        $hours   = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($hours > 0 && $minutes > 0) {
            return $hours . 'h ' . $minutes . 'm';
        }
        if ($hours > 0) {
            return $hours . 'h';
        }

        return $minutes . 'm';
    }
}

if (!function_exists('reservationDateTimeLocal')) {
    /**
     * Convert a stored UTC DATETIME to an Africa/Cairo value suitable for a
     * <input type="datetime-local"> control (format 'Y-m-d\TH:i').
     */
    function reservationDateTimeLocal(?string $utcDatetime): string
    {
        if ($utcDatetime === null || $utcDatetime === '') {
            return '';
        }

        try {
            $dt = new DateTimeImmutable($utcDatetime, new DateTimeZone('UTC'));

            return $dt->setTimezone(new DateTimeZone('Africa/Cairo'))->format('Y-m-d\TH:i');
        } catch (Exception $e) {
            return '';
        }
    }
}
