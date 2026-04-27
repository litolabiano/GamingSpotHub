-- ============================================================
-- migration_cancellations_log.sql
-- Creates reservation_cancellations — a dedicated audit/stats
-- log for every reservation cancellation event.
-- Run once in phpMyAdmin or via CLI.
-- ============================================================

-- 1. Add admin_decision to the reservations.cancel_reason_type ENUM
--    (admin.php already sends 'admin_decision' but it wasn't in the ENUM)
ALTER TABLE `reservations`
    MODIFY COLUMN `cancel_reason_type`
        ENUM('schedule_change','found_alternative','budget_issue',
             'technical_issue','emergency','other','admin_decision')
        DEFAULT NULL;

-- 2. Create the dedicated cancellations log table
CREATE TABLE IF NOT EXISTS `reservation_cancellations` (
    `cancel_id`           INT(11)       NOT NULL AUTO_INCREMENT,
    `reservation_id`      INT(11)       NOT NULL,
    `user_id`             INT(11)       NOT NULL,
    `cancelled_by`        ENUM('user','admin') NOT NULL DEFAULT 'user',
    `cancel_reason_type`  ENUM('schedule_change','found_alternative','budget_issue',
                               'technical_issue','emergency','other','admin_decision')
                          NOT NULL DEFAULT 'other',
    `cancel_reason_detail` TEXT          DEFAULT NULL,
    `console_type`        ENUM('PS5','PS4','Xbox Series X') NOT NULL,
    `rental_mode`         ENUM('hourly','open_time','unlimited') NOT NULL DEFAULT 'hourly',
    `reserved_date`       DATE          NOT NULL,
    `downpayment_amount`  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `refund_issued`       TINYINT(1)    NOT NULL DEFAULT 0,
    `cancelled_at`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`cancel_id`),
    KEY `idx_rc_reservation` (`reservation_id`),
    KEY `idx_rc_user`        (`user_id`),
    KEY `idx_rc_cancelled_by`(`cancelled_by`),
    KEY `idx_rc_reason_type` (`cancel_reason_type`),
    KEY `idx_rc_date`        (`cancelled_at`),
    KEY `idx_rc_console`     (`console_type`),
    CONSTRAINT `fk_rc_reservation` FOREIGN KEY (`reservation_id`)
        REFERENCES `reservations` (`reservation_id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_rc_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Backfill existing cancelled reservations
INSERT IGNORE INTO `reservation_cancellations`
    (`reservation_id`, `user_id`, `cancelled_by`, `cancel_reason_type`,
     `cancel_reason_detail`, `console_type`, `rental_mode`,
     `reserved_date`, `downpayment_amount`, `refund_issued`, `cancelled_at`)
SELECT
    r.reservation_id,
    r.user_id,
    COALESCE(r.cancelled_by, 'user'),
    COALESCE(r.cancel_reason_type, 'other'),
    r.cancel_reason_detail,
    r.console_type,
    r.rental_mode,
    r.reserved_date,
    r.downpayment_amount,
    r.refund_issued,
    r.updated_at   -- updated_at reflects the moment of cancellation
FROM `reservations` r
WHERE r.status = 'cancelled';
