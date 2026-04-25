-- ============================================================================
-- GamingSpotHub — Migration: Session Extensions
-- Run this after schema.sql and migration_auth.sql
-- ============================================================================

USE gamingspothub;

-- ── New column on gaming_sessions ────────────────────────────────────────────
-- Tracks total minutes added via all approved extensions on this session.
ALTER TABLE gaming_sessions
    ADD COLUMN IF NOT EXISTS extended_minutes INT NOT NULL DEFAULT 0
        COMMENT 'Total minutes added via approved extensions';

-- ── New table: session_extensions ────────────────────────────────────────────
-- Tracks every time-extension request (customer-initiated or staff-direct).
--
-- Status lifecycle:
--   Customer request : pending → approved (staff approves) | denied (staff denies)
--   Staff direct     : inserted directly as 'approved' with no pending step

CREATE TABLE IF NOT EXISTS session_extensions (
    extension_id   INT AUTO_INCREMENT PRIMARY KEY,

    session_id     INT NOT NULL
                   COMMENT 'Active session being extended',

    requested_by   INT NOT NULL
                   COMMENT 'user_id who requested (customer or staff)',

    approved_by    INT DEFAULT NULL
                   COMMENT 'user_id of staff who approved (NULL if pending/denied)',

    extra_minutes  INT NOT NULL
                   COMMENT 'Minutes to add to the session',

    extra_cost     DECIMAL(10,2) NOT NULL DEFAULT 0.00
                   COMMENT 'Cost of the extension (0 for open_time/unlimited)',

    payment_method ENUM('cash','gcash','credit_card') DEFAULT NULL
                   COMMENT 'NULL until staff collects payment on approval',

    status         ENUM('pending','approved','denied') NOT NULL DEFAULT 'pending',

    note           VARCHAR(255) DEFAULT NULL
                   COMMENT 'Optional reason or remark',

    requested_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at    DATETIME DEFAULT NULL
                   COMMENT 'When staff approved or denied',

    INDEX idx_ext_session (session_id),
    INDEX idx_ext_status  (status),
    INDEX idx_ext_requested_by (requested_by),

    CONSTRAINT fk_ext_session
        FOREIGN KEY (session_id) REFERENCES gaming_sessions(session_id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_ext_requested_by
        FOREIGN KEY (requested_by) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_ext_approved_by
        FOREIGN KEY (approved_by) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Pricing rules (single source of truth for the bonus time logic) ──────────
-- All PHP and JS billing code reads THESE values.
-- To change the bonus policy, update only these rows — nothing else.
INSERT INTO system_settings (setting_key, setting_value, description)
VALUES
    ('bonus_paid_minutes', '120', 'Every X paid minutes earns free bonus time (e.g. 120 = every 2 hrs)'),
    ('bonus_free_minutes', '30',  'Free minutes awarded per bonus cycle (e.g. 30 = 30 min free)'),
    ('max_hourly_minutes', '240', 'Maximum bookable paid minutes for an hourly session')
ON DUPLICATE KEY UPDATE
    setting_value = VALUES(setting_value),
    description   = VALUES(description);
