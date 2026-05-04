-- ============================================================================
-- GamingSpotHub — Migration: Tournament System Unification
-- Run after schema.sql, migration_auth.sql, and migration_extensions.sql
-- Safe to re-run (IF NOT EXISTS guards on all column additions)
-- ============================================================================

USE gamingspothub;

-- ── 1. Formally add 'scheduled' to tournaments.status ENUM ──────────────────
-- The live code already uses 'scheduled' as the "open for registration" state
-- but the original schema docs only defined upcoming/ongoing/completed/cancelled.
ALTER TABLE tournaments
    MODIFY COLUMN status
        ENUM('upcoming','scheduled','ongoing','completed','cancelled')
        NOT NULL DEFAULT 'upcoming';

-- ── 2. Extend tournament_participants with self-registration fields ───────────
-- These columns support the unified public + admin registration flow.
-- Public self-registrations populate ign, contact_number, gcash_proof.
-- Admin-added entries leave those NULL and populate registered_by instead.
ALTER TABLE tournament_participants
    ADD COLUMN IF NOT EXISTS ign             VARCHAR(100) DEFAULT NULL
        COMMENT 'In-game name / alias provided at registration',
    ADD COLUMN IF NOT EXISTS contact_number  VARCHAR(20)  DEFAULT NULL
        COMMENT 'Contact number provided at registration',
    ADD COLUMN IF NOT EXISTS gcash_proof     VARCHAR(255) DEFAULT NULL
        COMMENT 'Uploaded GCash receipt filename (self-registrations only)',
    ADD COLUMN IF NOT EXISTS registered_by   INT          DEFAULT NULL
        COMMENT 'Staff user_id if admin-added; NULL for self-registration',
    ADD COLUMN IF NOT EXISTS walkin_name     VARCHAR(100) DEFAULT NULL
        COMMENT 'Display name for walk-in participants (user_id = 0)',
    ADD COLUMN IF NOT EXISTS notes           VARCHAR(255) DEFAULT NULL
        COMMENT 'Optional note from registrant or staff';

-- ── 3. Drop uk_tp_entry unique key ───────────────────────────────────────────
-- The unique key prevents multiple walk-in entries (all share user_id = 0).
-- Admin can add walk-ins by name without a registered account.
-- Duplicate detection for registered users is handled at the application level.
SET @uk_exists = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA  = 'gamingspothub'
      AND TABLE_NAME    = 'tournament_participants'
      AND INDEX_NAME    = 'uk_tp_entry'
);

SET @sql2 = IF(
    @uk_exists > 0,
    'ALTER TABLE tournament_participants DROP INDEX uk_tp_entry',
    'SELECT 1 -- uk_tp_entry already removed, skip'
);

PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- ── 4. FK for registered_by ──────────────────────────────────────────────────
-- Only add the constraint if it does not already exist.
-- ON DELETE SET NULL: if the staff account is removed, we keep the participant
-- record but lose the "who added them" audit trail (acceptable).
SET @fk_exists = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'gamingspothub'
      AND TABLE_NAME         = 'tournament_participants'
      AND CONSTRAINT_NAME    = 'fk_tp_registered_by'
);

SET @sql = IF(
    @fk_exists = 0,
    'ALTER TABLE tournament_participants
         ADD CONSTRAINT fk_tp_registered_by
             FOREIGN KEY (registered_by) REFERENCES users(user_id)
             ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1 -- FK already exists, skip'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
