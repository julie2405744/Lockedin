<?php
/**
 * app/Modules/Student/_sample-data.php
 * Member 4 — TEMPORARY sample data for the student experience pages.
 *
 * ══════════════════════════════════════════════════════════════════
 *  THIS FILE IS A FRONTEND STAND-IN, NOT A DATA SOURCE.
 *  Delete it as soon as Member 2's RoomService and Member 3's
 *  ReservationService are integrated; the page handlers then obtain
 *  the same-shaped arrays from the shared services:
 *
 *    Room card          id, name, category, floor_name, capacity,
 *                       facilities[], optional image_path,
 *                       optional availability_summary
 *    Reservation row    id, room_id, room_name, floor_name, starts_at
 *                       (UTC), ends_at (UTC), status, optional reason
 *    Modification row   id, original reservation, proposed room/time,
 *                       status, optional reason
 * ══════════════════════════════════════════════════════════════════
 *
 * Nothing in here contains booking rules — only data the views render.
 * Reservation, modification, and unavailability timestamps are UTC;
 * operating hours use Africa/Cairo local time values, exactly like
 * the real database columns.
 */

/* ── Rooms ──────────────────────────────────────────────────────── */

$studentSampleRooms = [
    [
        'id'                   => 1,
        'name'                 => 'Quiet Hall A',
        'category'             => 'individual_study',
        'floor_name'           => 'Floor 1',
        'capacity'             => 1,
        'description'          => 'A silent single-seat room on the ground floor, two minutes from the entrance. Best for focused solo revision.',
        'facilities'           => ['Power sockets', 'Desk lamp', 'Wi-Fi'],
        'image_path'           => null,
        'availability_summary' => 'Available for your selected time',
        'hours'                => [
            ['day_of_week' => 0, 'opens_at' => '08:00', 'closes_at' => '22:00'],
            ['day_of_week' => 1, 'opens_at' => '08:00', 'closes_at' => '22:00'],
            ['day_of_week' => 2, 'opens_at' => '08:00', 'closes_at' => '22:00'],
            ['day_of_week' => 3, 'opens_at' => '08:00', 'closes_at' => '22:00'],
            ['day_of_week' => 4, 'opens_at' => '08:00', 'closes_at' => '22:00'],
            ['day_of_week' => 5, 'opens_at' => '08:00', 'closes_at' => '18:00'],
            ['day_of_week' => 6, 'opens_at' => '10:00', 'closes_at' => '18:00'],
        ],
    ],
    [
        'id'                   => 2,
        'name'                 => 'Quiet Hall B',
        'category'             => 'individual_study',
        'floor_name'           => 'Floor 1',
        'capacity'             => 1,
        'description'          => 'Silent individual study room beside Quiet Hall A. Noise rules are strictly enforced.',
        'facilities'           => ['Power sockets', 'Wi-Fi'],
        'image_path'           => null,
        'availability_summary' => null,
        'hours'                => [
            ['day_of_week' => 0, 'opens_at' => '09:00', 'closes_at' => '21:00'],
            ['day_of_week' => 1, 'opens_at' => '09:00', 'closes_at' => '21:00'],
            ['day_of_week' => 2, 'opens_at' => '09:00', 'closes_at' => '21:00'],
            ['day_of_week' => 3, 'opens_at' => '09:00', 'closes_at' => '21:00'],
            ['day_of_week' => 4, 'opens_at' => '09:00', 'closes_at' => '21:00'],
            ['day_of_week' => 5, 'opens_at' => '09:00', 'closes_at' => '17:00'],
        ],
    ],
    [
        'id'                   => 3,
        'name'                 => 'Group Pod 2A',
        'category'             => 'group_study',
        'floor_name'           => 'Floor 2',
        'capacity'              => 6,
        'description'          => 'Six-seat group room with a wall-mounted whiteboard and a shared display. Windows face the quiet courtyard.',
        'facilities'           => ['Power sockets', 'Wi-Fi', 'Whiteboard', 'Projector'],
        'image_path'           => null,
        'availability_summary' => 'Available for your selected time',
        'hours'                => [
            ['day_of_week' => 0, 'opens_at' => '09:00', 'closes_at' => '21:00'],
            ['day_of_week' => 1, 'opens_at' => '09:00', 'closes_at' => '21:00'],
            ['day_of_week' => 2, 'opens_at' => '09:00', 'closes_at' => '21:00'],
            ['day_of_week' => 3, 'opens_at' => '09:00', 'closes_at' => '21:00'],
            ['day_of_week' => 4, 'opens_at' => '09:00', 'closes_at' => '21:00'],
            ['day_of_week' => 5, 'opens_at' => '09:00', 'closes_at' => '17:00'],
        ],
    ],
    [
        'id'                   => 4,
        'name'                 => 'Group Pod 2B',
        'category'             => 'group_study',
        'floor_name'           => 'Floor 2',
        'capacity'             => 8,
        'description'          => 'Eight-seat group room next to Group Pod 2A. Book both for larger teams.',
        'facilities'           => ['Power sockets', 'Wi-Fi', 'Whiteboard'],
        'image_path'           => null,
        'availability_summary' => 'Busy at your selected time',
        'hours'                => [
            ['day_of_week' => 0, 'opens_at' => '08:30', 'closes_at' => '20:30'],
            ['day_of_week' => 1, 'opens_at' => '08:30', 'closes_at' => '20:30'],
            ['day_of_week' => 2, 'opens_at' => '08:30', 'closes_at' => '20:30'],
            ['day_of_week' => 3, 'opens_at' => '08:30', 'closes_at' => '20:30'],
            ['day_of_week' => 4, 'opens_at' => '08:30', 'closes_at' => '20:30'],
            ['day_of_week' => 5, 'opens_at' => '09:00', 'closes_at' => '16:00'],
        ],
    ],
    [
        'id'                   => 5,
        'name'                 => 'Collaboration Suite 3',
        'category'             => 'group_study',
        'floor_name'           => 'Floor 3',
        'capacity'             => 12,
        'description'          => 'Large collaboration suite for project teams, with a projector and reconfigurable tables.',
        'facilities'           => ['Power sockets', 'Wi-Fi', 'Projector', 'Whiteboard', 'Air conditioning'],
        'image_path'           => null,
        'availability_summary' => null,
        'hours'                => [
            ['day_of_week' => 0, 'opens_at' => '10:00', 'closes_at' => '20:00'],
            ['day_of_week' => 1, 'opens_at' => '10:00', 'closes_at' => '20:00'],
            ['day_of_week' => 2, 'opens_at' => '10:00', 'closes_at' => '20:00'],
            ['day_of_week' => 3, 'opens_at' => '10:00', 'closes_at' => '20:00'],
            ['day_of_week' => 4, 'opens_at' => '10:00', 'closes_at' => '20:00'],
            ['day_of_week' => 5, 'opens_at' => '10:00', 'closes_at' => '15:00'],
        ],
    ],
    [
        'id'                   => 6,
        'name'                 => 'Sky Lounge',
        'category'             => 'lounge',
        'floor_name'           => 'Floor 4',
        'capacity'             => 20,
        'description'          => 'Open-plan rooftop-style lounge with soft seating, a coffee area, and views over the campus.',
        'facilities'           => ['Wi-Fi', 'Coffee area', 'Air conditioning'],
        'image_path'           => null,
        'availability_summary' => 'Available for your selected time',
        'hours'                => [
            ['day_of_week' => 0, 'opens_at' => '08:00', 'closes_at' => '22:00'],
            ['day_of_week' => 1, 'opens_at' => '08:00', 'closes_at' => '22:00'],
            ['day_of_week' => 2, 'opens_at' => '08:00', 'closes_at' => '22:00'],
            ['day_of_week' => 3, 'opens_at' => '08:00', 'closes_at' => '22:00'],
            ['day_of_week' => 4, 'opens_at' => '08:00', 'closes_at' => '22:00'],
            ['day_of_week' => 5, 'opens_at' => '10:00', 'closes_at' => '22:00'],
            ['day_of_week' => 6, 'opens_at' => '12:00', 'closes_at' => '20:00'],
        ],
    ],
    [
        'id'                   => 7,
        'name'                 => 'Reading Lounge',
        'category'             => 'lounge',
        'floor_name'           => 'Floor 3',
        'capacity'             => 10,
        'description'          => 'Quiet lounge with reading tables and armchairs. Conversations are kept to a whisper.',
        'facilities'           => ['Wi-Fi', 'Power sockets', 'Air conditioning'],
        'image_path'           => null,
        'availability_summary' => null,
        'hours'                => [
            ['day_of_week' => 0, 'opens_at' => '08:30', 'closes_at' => '21:00'],
            ['day_of_week' => 1, 'opens_at' => '08:30', 'closes_at' => '21:00'],
            ['day_of_week' => 2, 'opens_at' => '08:30', 'closes_at' => '21:00'],
            ['day_of_week' => 3, 'opens_at' => '08:30', 'closes_at' => '21:00'],
            ['day_of_week' => 4, 'opens_at' => '08:30', 'closes_at' => '21:00'],
            ['day_of_week' => 5, 'opens_at' => '08:30', 'closes_at' => '18:00'],
        ],
    ],
];

/**
 * Lightweight lookup for room-details / booking pages.
 * @return array|null
 */
function studentSampleRoomById(array $rooms, int $id): ?array
{
    foreach ($rooms as $room) {
        if ((int) $room['id'] === $id) {
            return $room;
        }
    }
    return null;
}

/* ── Reservations (starts_at / ends_at stored as UTC like the DB) ── */

$studentSampleReservations = [
    [
        'id'         => 101,
        'room_id'    => 3,
        'room_name'  => 'Group Pod 2A',
        'floor_name' => 'Floor 2',
        'category'   => 'group_study',
        'starts_at'  => '2026-11-02 09:00:00',
        'ends_at'    => '2026-11-02 11:00:00',
        'status'     => 'confirmed',
        'reason'     => null,
    ],
    [
        'id'         => 102,
        'room_id'    => 1,
        'room_name'  => 'Quiet Hall A',
        'floor_name' => 'Floor 1',
        'category'   => 'individual_study',
        'starts_at'  => '2026-11-03 13:00:00',
        'ends_at'    => '2026-11-03 15:00:00',
        'status'     => 'pending',
        'reason'     => null,
    ],
    [
        'id'         => 103,
        'room_id'    => 6,
        'room_name'  => 'Sky Lounge',
        'floor_name' => 'Floor 4',
        'category'   => 'lounge',
        'starts_at'  => '2026-11-05 10:00:00',
        'ends_at'    => '2026-11-05 13:00:00',
        'status'     => 'confirmed',
        'reason'     => null,
    ],
    [
        'id'         => 104,
        'room_id'    => 2,
        'room_name'  => 'Quiet Hall B',
        'floor_name' => 'Floor 1',
        'category'   => 'individual_study',
        'starts_at'  => '2026-10-26 08:00:00',
        'ends_at'    => '2026-10-26 10:00:00',
        'status'     => 'rejected',
        'reason'     => 'Another student had already booked this slot when your request was reviewed.',
    ],
    [
        'id'         => 105,
        'room_id'    => 4,
        'room_name'  => 'Group Pod 2B',
        'floor_name' => 'Floor 2',
        'category'   => 'group_study',
        'starts_at'  => '2026-10-22 14:00:00',
        'ends_at'    => '2026-10-22 16:00:00',
        'status'     => 'cancelled',
        'reason'     => null,
    ],
    [
        'id'         => 106,
        'room_id'    => 5,
        'room_name'  => 'Collaboration Suite 3',
        'floor_name' => 'Floor 3',
        'category'   => 'group_study',
        'starts_at'  => '2026-10-15 12:00:00',
        'ends_at'    => '2026-10-15 17:00:00',
        'status'     => 'confirmed',
        'reason'     => null,
    ],
];

/* ── One pending modification proposal (belongs to reservation 103) ─ */

$studentSampleModification = [
    'id'                  => 55,
    'reservation_id'      => 103,
    'original'            => [
        'room_name'  => 'Sky Lounge',
        'floor_name' => 'Floor 4',
        'starts_at'  => '2026-11-05 10:00:00',
        'ends_at'    => '2026-11-05 13:00:00',
    ],
    'proposed'            => [
        'room_name'  => 'Group Pod 2B',
        'floor_name' => 'Floor 2',
        'starts_at'  => '2026-11-05 14:00:00',
        'ends_at'    => '2026-11-05 17:00:00',
    ],
    'status'              => 'pending',
    'requested_at'        => '2026-10-19 18:30:00',
    'reason'              => 'The team wants an indoor room with a whiteboard for the presentation rehearsal.',
    'invalidation_reason' => null,
];

return [
    'rooms'        => $studentSampleRooms,
    'reservations' => $studentSampleReservations,
    'modification' => $studentSampleModification,
];
