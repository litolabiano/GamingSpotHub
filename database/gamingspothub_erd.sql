-- ============================================================
--  GamingSpotHub — ERD Schema for LucidChart Import
--  Generated: 2026-05-05
--  Usage: LucidChart → Insert → Import Data → Database Import
--         → paste this file content → Generate
-- ============================================================

-- ─────────────────────────────────────────────
--  USERS
-- ─────────────────────────────────────────────
CREATE TABLE `users` (
  `user_id`                   INT(11)       NOT NULL AUTO_INCREMENT,
  `email`                     VARCHAR(100)  NOT NULL,
  `password_hash`             VARCHAR(255)  NOT NULL,
  `full_name`                 VARCHAR(100)  NOT NULL,
  `phone`                     VARCHAR(20)   DEFAULT NULL,
  `role`                      ENUM('customer','shopkeeper','owner','walkin') NOT NULL DEFAULT 'customer',
  `status`                    ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `email_verified`            TINYINT(1)    NOT NULL DEFAULT 0,
  `verification_token`        VARCHAR(64)   DEFAULT NULL,
  `verification_expires`      DATETIME      DEFAULT NULL,
  `reset_token`               VARCHAR(64)   DEFAULT NULL,
  `reset_expires`             DATETIME      DEFAULT NULL,
  `consecutive_cancellations` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
  `reservation_banned_until`  DATETIME      DEFAULT NULL,
  `created_at`                DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
--  CONSOLES
-- ─────────────────────────────────────────────
CREATE TABLE `consoles` (
  `console_id`                  INT(11)      NOT NULL AUTO_INCREMENT,
  `console_name`                VARCHAR(50)  NOT NULL,
  `console_type`                ENUM('PS5','PS4','Xbox Series X') NOT NULL,
  `unit_number`                 VARCHAR(10)  NOT NULL,
  `status`                      ENUM('available','in_use','maintenance','archived') NOT NULL DEFAULT 'available',
  `hourly_rate`                 DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `compatible_controller_type`  VARCHAR(50)  DEFAULT NULL COMMENT 'Maps console to its compatible controller type',
  `created_at`                  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`console_id`),
  UNIQUE KEY `uq_consoles_unit` (`unit_number`)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
--  CONTROLLERS
-- ─────────────────────────────────────────────
CREATE TABLE `controllers` (
  `controller_id`   INT(11)      NOT NULL AUTO_INCREMENT,
  `controller_name` VARCHAR(100) NOT NULL,
  `controller_type` ENUM('DualSense','DualShock 4','Xbox Controller','Other') NOT NULL,
  `unit_number`     VARCHAR(20)  NOT NULL,
  `status`          ENUM('available','in_use','maintenance','archived') NOT NULL DEFAULT 'available',
  `notes`           TEXT         DEFAULT NULL,
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`controller_id`),
  UNIQUE KEY `uq_controllers_unit` (`unit_number`)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
--  GAMING SESSIONS
-- ─────────────────────────────────────────────
CREATE TABLE `gaming_sessions` (
  `session_id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`               INT(11)      NOT NULL,
  `console_id`            INT(11)      NOT NULL,
  `rental_mode`           ENUM('hourly','open_time','unlimited') NOT NULL,
  `planned_minutes`       INT(11)      DEFAULT NULL,
  `extended_minutes`      INT(11)      NOT NULL DEFAULT 0,
  `start_time`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `end_time`              DATETIME     DEFAULT NULL,
  `duration_minutes`      INT(11)      DEFAULT NULL,
  `hourly_rate`           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_cost`            DECIMAL(10,2) DEFAULT NULL,
  `status`                ENUM('active','completed','cancelled') NOT NULL DEFAULT 'active',
  `payment_status`        ENUM('paid','unpaid','partial') DEFAULT NULL,
  `source_reservation_id` INT(11)      DEFAULT NULL,
  `created_by`            INT(11)      NOT NULL,
  `created_at`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`session_id`),
  CONSTRAINT `fk_gs_user`       FOREIGN KEY (`user_id`)    REFERENCES `users`    (`user_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_gs_console`    FOREIGN KEY (`console_id`) REFERENCES `consoles` (`console_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_gs_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`    (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
--  SESSION EXTENSIONS
-- ─────────────────────────────────────────────
CREATE TABLE `session_extensions` (
  `extension_id`   INT(11)      NOT NULL AUTO_INCREMENT,
  `session_id`     INT(11)      NOT NULL,
  `requested_by`   INT(11)      NOT NULL,
  `approved_by`    INT(11)      DEFAULT NULL,
  `extra_minutes`  INT(11)      NOT NULL,
  `extra_cost`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` ENUM('cash','gcash','credit_card') DEFAULT NULL,
  `status`         ENUM('pending','approved','denied') NOT NULL DEFAULT 'pending',
  `note`           VARCHAR(255) DEFAULT NULL,
  `requested_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at`    DATETIME     DEFAULT NULL,
  PRIMARY KEY (`extension_id`),
  CONSTRAINT `fk_ext_session`     FOREIGN KEY (`session_id`)   REFERENCES `gaming_sessions` (`session_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ext_req_by`      FOREIGN KEY (`requested_by`) REFERENCES `users`           (`user_id`)    ON UPDATE CASCADE,
  CONSTRAINT `fk_ext_approved_by` FOREIGN KEY (`approved_by`)  REFERENCES `users`           (`user_id`)    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
--  ADDITIONAL REQUESTS  (controller rental, extras)
-- ─────────────────────────────────────────────
CREATE TABLE `additional_requests` (
  `request_id`   INT(11)      NOT NULL AUTO_INCREMENT,
  `session_id`   INT(11)      NOT NULL,
  `request_type` ENUM('extra_hours','controller_rental','other') NOT NULL,
  `description`  TEXT         DEFAULT NULL,
  `extra_cost`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status`       ENUM('pending','approved','denied') NOT NULL DEFAULT 'pending',
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`request_id`),
  CONSTRAINT `fk_ar_session` FOREIGN KEY (`session_id`) REFERENCES `gaming_sessions` (`session_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
--  TRANSACTIONS
-- ─────────────────────────────────────────────
CREATE TABLE `transactions` (
  `transaction_id`  INT(11)      NOT NULL AUTO_INCREMENT,
  `session_id`      INT(11)      DEFAULT NULL,
  `user_id`         INT(11)      NOT NULL,
  `amount`          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tendered_amount` DECIMAL(10,2) DEFAULT NULL,
  `shortfall_amount`DECIMAL(10,2) DEFAULT NULL,
  `payment_note`    VARCHAR(255) DEFAULT NULL,
  `payment_method`  ENUM('cash','gcash','credit_card') NOT NULL DEFAULT 'cash',
  `payment_status`  ENUM('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `transaction_date`DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_by`    INT(11)      NOT NULL,
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`transaction_id`),
  CONSTRAINT `fk_tx_session`      FOREIGN KEY (`session_id`)  REFERENCES `gaming_sessions` (`session_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tx_user`         FOREIGN KEY (`user_id`)     REFERENCES `users`           (`user_id`)    ON UPDATE CASCADE,
  CONSTRAINT `fk_tx_processed_by` FOREIGN KEY (`processed_by`) REFERENCES `users`          (`user_id`)    ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
--  RESERVATIONS
-- ─────────────────────────────────────────────
CREATE TABLE `reservations` (
  `reservation_id`       INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`              INT(11)      NOT NULL,
  `console_id`           INT(11)      DEFAULT NULL,
  `console_type`         ENUM('PS5','Xbox Series X','PS4') NOT NULL,
  `rental_mode`          ENUM('hourly','open_time','unlimited') NOT NULL DEFAULT 'hourly',
  `planned_minutes`      INT(11)      DEFAULT NULL,
  `reserved_date`        DATE         NOT NULL,
  `reserved_time`        TIME         NOT NULL,
  `notes`                TEXT         DEFAULT NULL,
  `downpayment_amount`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `downpayment_method`   ENUM('cash','gcash','credit_card') DEFAULT NULL,
  `downpayment_paid`     TINYINT(1)   NOT NULL DEFAULT 0,
  `payment_proof`        VARCHAR(255) DEFAULT NULL,
  `payment_proof_status` ENUM('pending','verified','rejected') DEFAULT NULL,
  `paymongo_source_id`   VARCHAR(60)  DEFAULT NULL,
  `paymongo_payment_id`  VARCHAR(60)  DEFAULT NULL,
  `paymongo_status`      VARCHAR(30)  DEFAULT NULL,
  `status`               ENUM('pending','confirmed','converted','cancelled','no_show') NOT NULL DEFAULT 'pending',
  `cancelled_by`         ENUM('user','admin') DEFAULT NULL,
  `cancellation_reason`  VARCHAR(300) DEFAULT NULL,
  `refund_issued`        TINYINT(1)   NOT NULL DEFAULT 0,
  `cancel_reason_type`   ENUM('schedule_change','found_alternative','budget_issue','technical_issue','emergency','other','admin_decision') DEFAULT NULL,
  `cancel_reason_detail` TEXT         DEFAULT NULL,
  `created_by`           INT(11)      NOT NULL,
  `created_at`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`reservation_id`),
  CONSTRAINT `fk_res_user`       FOREIGN KEY (`user_id`)    REFERENCES `users`    (`user_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_res_console`    FOREIGN KEY (`console_id`) REFERENCES `consoles` (`console_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_res_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`    (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
--  RESERVATION CANCELLATIONS
-- ─────────────────────────────────────────────
CREATE TABLE `reservation_cancellations` (
  `cancel_id`           INT(11)      NOT NULL AUTO_INCREMENT,
  `reservation_id`      INT(11)      NOT NULL,
  `user_id`             INT(11)      NOT NULL,
  `cancelled_by`        ENUM('user','admin') NOT NULL DEFAULT 'user',
  `cancel_reason_type`  ENUM('schedule_change','found_alternative','budget_issue','technical_issue','emergency','other','admin_decision') NOT NULL DEFAULT 'other',
  `cancel_reason_detail`TEXT         DEFAULT NULL,
  `console_type`        ENUM('PS5','PS4','Xbox Series X') NOT NULL,
  `rental_mode`         ENUM('hourly','open_time','unlimited') NOT NULL DEFAULT 'hourly',
  `reserved_date`       DATE         NOT NULL,
  `downpayment_amount`  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `refund_issued`       TINYINT(1)   NOT NULL DEFAULT 0,
  `cancelled_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cancel_id`),
  CONSTRAINT `fk_rc_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rc_user`        FOREIGN KEY (`user_id`)        REFERENCES `users`         (`user_id`)       ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
--  RESERVATION RESCHEDULES
-- ─────────────────────────────────────────────
CREATE TABLE `reservation_reschedules` (
  `reschedule_id`  INT(11)      NOT NULL AUTO_INCREMENT,
  `reservation_id` INT(11)      NOT NULL,
  `user_id`        INT(11)      NOT NULL,
  `old_date`       DATE         NOT NULL,
  `old_time`       TIME         NOT NULL,
  `new_date`       DATE         NOT NULL,
  `new_time`       TIME         NOT NULL,
  `reason`         ENUM('typhoon','power_outage','emergency','maintenance','other') NOT NULL DEFAULT 'other',
  `reason_detail`  VARCHAR(300) DEFAULT NULL,
  `rescheduled_by` INT(11)      NOT NULL,
  `seen_by_user`   TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`reschedule_id`),
  CONSTRAINT `fk_rr_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rr_user`        FOREIGN KEY (`user_id`)        REFERENCES `users`         (`user_id`)       ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
--  TOURNAMENTS
-- ─────────────────────────────────────────────
CREATE TABLE `tournaments` (
  `tournament_id`    INT(11)      NOT NULL AUTO_INCREMENT,
  `tournament_name`  VARCHAR(150) NOT NULL,
  `game_name`        VARCHAR(150) NOT NULL DEFAULT '',
  `console_type`     ENUM('PS5','Xbox Series X','PS4','PC','Multi') NOT NULL,
  `start_date`       DATETIME     NOT NULL,
  `end_date`         DATETIME     NOT NULL,
  `entry_fee`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `prize_pool`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `max_participants` INT(11)      NOT NULL DEFAULT 16,
  `status`           ENUM('upcoming','scheduled','ongoing','completed','cancelled') NOT NULL DEFAULT 'upcoming',
  `announcement`     TEXT         DEFAULT NULL,
  `created_by`       INT(11)      DEFAULT NULL,
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tournament_id`),
  CONSTRAINT `fk_tourn_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
--  TOURNAMENT PARTICIPANTS
-- ─────────────────────────────────────────────
CREATE TABLE `tournament_participants` (
  `participant_id`    INT(11)      NOT NULL AUTO_INCREMENT,
  `tournament_id`     INT(11)      NOT NULL,
  `user_id`           INT(11)      NOT NULL,
  `payment_status`    ENUM('pending','paid') NOT NULL DEFAULT 'pending',
  `placement`         INT(11)      DEFAULT NULL,
  `prize_amount`      DECIMAL(10,2) DEFAULT NULL,
  `ign`               VARCHAR(100) DEFAULT NULL,
  `contact_number`    VARCHAR(20)  DEFAULT NULL,
  `gcash_proof`       VARCHAR(255) DEFAULT NULL,
  `registered_by`     INT(11)      DEFAULT NULL,
  `walkin_name`       VARCHAR(100) DEFAULT NULL,
  `notes`             VARCHAR(255) DEFAULT NULL,
  `paymongo_source_id`VARCHAR(80)  DEFAULT NULL,
  `paymongo_payment_id`VARCHAR(80) DEFAULT NULL,
  `paymongo_status`   VARCHAR(30)  DEFAULT NULL,
  `registration_date` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`participant_id`),
  CONSTRAINT `fk_tp_tournament`    FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`tournament_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tp_user`          FOREIGN KEY (`user_id`)       REFERENCES `users`        (`user_id`)      ON UPDATE CASCADE,
  CONSTRAINT `fk_tp_registered_by` FOREIGN KEY (`registered_by`) REFERENCES `users`        (`user_id`)      ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
--  SYSTEM SETTINGS  (key-value store)
-- ─────────────────────────────────────────────
CREATE TABLE `system_settings` (
  `setting_id`    INT(11)      NOT NULL AUTO_INCREMENT,
  `setting_key`   VARCHAR(50)  NOT NULL,
  `setting_value` TEXT         NOT NULL,
  `description`   VARCHAR(255) DEFAULT NULL,
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `uq_settings_key` (`setting_key`)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
--  REPORTS
-- ─────────────────────────────────────────────
CREATE TABLE `reports` (
  `report_id`   INT(11)      NOT NULL AUTO_INCREMENT,
  `report_type` ENUM('daily_sales','rental_records','console_usage','tournament') NOT NULL,
  `generated_by`INT(11)      NOT NULL,
  `date_from`   DATE         NOT NULL,
  `date_to`     DATE         NOT NULL,
  `file_path`   VARCHAR(255) DEFAULT NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`report_id`),
  CONSTRAINT `fk_rep_generated_by` FOREIGN KEY (`generated_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB;
