# `database.sql` — Study Room Booking System

```sql
-- ============================================================
-- Study Room Booking System (Workspace)
-- Database: MySQL / MariaDB on XAMPP
--
-- TIMEZONE CONVENTION:
-- Reservation, modification, and unavailability DATETIME values
-- are stored in UTC.
-- Operating hours use local Africa/Cairo TIME values.
--
-- SETUP:
-- 1. Open phpMyAdmin.
-- 2. Select the SQL tab.
-- 3. Run this script.
-- 4. Configure your PHP PDO connection to use workspace_db.
-- 5. Set the database session timezone to UTC in PHP.
-- 6. Create the first administrator using the instructions below.
--
-- IMPORTANT:
-- Complex booking rules must be validated in PHP and protected
-- by transactions. See the notes after the SQL script.
-- This script does not delete existing data.
-- ============================================================

CREATE DATABASE IF NOT EXISTS workspace_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE workspace_db;


-- ============================================================
-- 1. USERS
-- Public registration must always create student accounts.
-- Administrator accounts must be created through a controlled
-- setup process, never through public registration.
-- ============================================================

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 2. FLOORS
-- Floors are deactivated rather than deleted when they have
-- related rooms or historical reservations.
-- ============================================================

CREATE TABLE IF NOT EXISTS floors (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    floor_number INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_floors_floor_number (floor_number),
    KEY idx_floors_status (status)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 3. ROOMS
-- Only these three room categories are permitted:
-- individual_study, group_study, lounge.
-- ============================================================

CREATE TABLE IF NOT EXISTS rooms (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    floor_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    category ENUM(
        'individual_study',
        'group_study',
        'lounge'
    ) NOT NULL,
    description TEXT NULL,
    capacity INT UNSIGNED NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_rooms_floor_name (floor_id, name),
    KEY idx_rooms_floor_status (floor_id, status),
    KEY idx_rooms_category_status (category, status),

    CONSTRAINT fk_rooms_floor
        FOREIGN KEY (floor_id)
        REFERENCES floors(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT chk_rooms_capacity
        CHECK (capacity > 0)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 4. FACILITIES
-- Examples: Wi-Fi, Whiteboard, Projector, Power Outlets.
-- ============================================================

CREATE TABLE IF NOT EXISTS facilities (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_facilities_name (name)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 5. ROOM_FACILITIES
-- Many-to-many relationship:
-- one room can have many facilities, and one facility can
-- belong to many rooms.
-- ============================================================

CREATE TABLE IF NOT EXISTS room_facilities (
    room_id INT UNSIGNED NOT NULL,
    facility_id INT UNSIGNED NOT NULL,

    PRIMARY KEY (room_id, facility_id),
    KEY idx_room_facilities_facility (facility_id),

    CONSTRAINT fk_room_facilities_room
        FOREIGN KEY (room_id)
        REFERENCES rooms(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_room_facilities_facility
        FOREIGN KEY (facility_id)
        REFERENCES facilities(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 6. ROOM_OPERATING_HOURS
-- day_of_week:
-- 0 = Sunday, 1 = Monday, ..., 6 = Saturday.
--
-- Multiple intervals per room/day are allowed.
-- Overnight intervals are not allowed.
-- PHP must prevent overlapping operating-hour intervals.
-- TIME values represent local Africa/Cairo time.
-- ============================================================

CREATE TABLE IF NOT EXISTS room_operating_hours (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    room_id INT UNSIGNED NOT NULL,
    day_of_week TINYINT UNSIGNED NOT NULL,
    opens_at TIME NOT NULL,
    closes_at TIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_operating_hours_room_day (room_id, day_of_week),

    CONSTRAINT fk_operating_hours_room
        FOREIGN KEY (room_id)
        REFERENCES rooms(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT chk_operating_hours_day
        CHECK (day_of_week BETWEEN 0 AND 6),

    CONSTRAINT chk_operating_hours_interval
        CHECK (opens_at < closes_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 7. ROOM_UNAVAILABILITY
-- Used for maintenance, closures, and other unavailable periods.
-- starts_at and ends_at must be stored in UTC.
-- ============================================================

CREATE TABLE IF NOT EXISTS room_unavailability (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    room_id INT UNSIGNED NOT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    reason VARCHAR(255) NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_unavailability_room_interval
        (room_id, starts_at, ends_at),
    KEY idx_unavailability_creator (created_by),

    CONSTRAINT fk_unavailability_room
        FOREIGN KEY (room_id)
        REFERENCES rooms(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_unavailability_creator
        FOREIGN KEY (created_by)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT chk_unavailability_interval
        CHECK (starts_at < ends_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 8. RESERVATIONS
-- One student owns each reservation.
-- No participants table is needed.
--
-- Statuses:
-- pending, confirmed, rejected, cancelled.
--
-- Pending and confirmed reservations block room availability.
-- Rejected and cancelled reservations do not.
-- All reservation timestamps are stored in UTC.
-- ============================================================

CREATE TABLE IF NOT EXISTS reservations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id INT UNSIGNED NOT NULL,
    room_id INT UNSIGNED NOT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    status ENUM(
        'pending',
        'confirmed',
        'rejected',
        'cancelled'
    ) NOT NULL DEFAULT 'pending',
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    cancellation_reason VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    KEY idx_reservations_room_status_interval
        (room_id, status, starts_at, ends_at),

    KEY idx_reservations_student_start
        (student_id, starts_at),

    KEY idx_reservations_status_start
        (status, starts_at),

    KEY idx_reservations_reviewer (reviewed_by),

    CONSTRAINT fk_reservations_student
        FOREIGN KEY (student_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_reservations_room
        FOREIGN KEY (room_id)
        REFERENCES rooms(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_reservations_reviewer
        FOREIGN KEY (reviewed_by)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT chk_reservations_interval
        CHECK (starts_at < ends_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 9. RESERVATION_MODIFICATIONS
-- Students request changes; they do not directly modify an
-- existing reservation.
--
-- The original reservation stays unchanged while the request
-- is pending. On approval, PHP updates the original reservation
-- and the modification status within one transaction.
--
-- Statuses:
-- pending, approved, rejected, invalidated.
--
-- To keep this schema compatible with a wider range of
-- MySQL/MariaDB versions, the one-pending-modification rule
-- is enforced by ReservationService in a transaction rather
-- than relying on a generated-column index.
-- ============================================================

CREATE TABLE IF NOT EXISTS reservation_modifications (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    reservation_id INT UNSIGNED NOT NULL,
    proposed_room_id INT UNSIGNED NOT NULL,
    proposed_starts_at DATETIME NOT NULL,
    proposed_ends_at DATETIME NOT NULL,
    status ENUM(
        'pending',
        'approved',
        'rejected',
        'invalidated'
    ) NOT NULL DEFAULT 'pending',
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    invalidation_reason VARCHAR(255) NULL,
    rejection_reason VARCHAR(255) NULL,

    PRIMARY KEY (id),

    KEY idx_modifications_reservation_status
        (reservation_id, status),

    KEY idx_modifications_proposed_room_interval
        (proposed_room_id, status, proposed_starts_at, proposed_ends_at),

    KEY idx_modifications_reviewer (reviewed_by),

    CONSTRAINT fk_modifications_reservation
        FOREIGN KEY (reservation_id)
        REFERENCES reservations(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_modifications_proposed_room
        FOREIGN KEY (proposed_room_id)
        REFERENCES rooms(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_modifications_reviewer
        FOREIGN KEY (reviewed_by)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT chk_modifications_interval
        CHECK (proposed_starts_at < proposed_ends_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- OPTIONAL STARTER FACILITIES
-- Remove the comment markers if you want these facilities
-- available when you first set up the application.
-- ============================================================

-- INSERT IGNORE INTO facilities (name, description) VALUES
-- ('Wi-Fi', 'Wireless internet access'),
-- ('Whiteboard', 'Whiteboard available in the room'),
-- ('Projector', 'Projector for presentations'),
-- ('Power Outlets', 'Accessible electrical outlets'),
-- ('Air Conditioning', 'Air-conditioned room');


-- ============================================================
-- FIRST ADMINISTRATOR SETUP
--
-- Do not store a plaintext password in the database.
--
-- Generate a password hash using PHP from your terminal:
--
-- php -r "echo password_hash('YOUR_STRONG_PASSWORD',
-- PASSWORD_DEFAULT), PHP_EOL;"
--
-- Copy the generated hash and run this statement with your
-- administrator details:
--
-- INSERT INTO users (name, email, password_hash, role)
-- VALUES (
--     'System Administrator',
--     'admin@example.com',
--     'PASTE_GENERATED_HASH_HERE',
--     'admin'
-- );
--
-- Do this only for the initial administrator or through a
-- controlled administrative setup process. Public registration
-- must always assign the student role on the server.
-- ============================================================
```

