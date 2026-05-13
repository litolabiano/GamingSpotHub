-- ============================================================
-- GamingSpotHub — Clean Production Schema v2.0
-- Generated: 2026-05-13
-- Fixes: missing columns, broken FKs, adds games + notifications
-- ============================================================
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;
SET NAMES utf8mb4;

-- -- 1. users -------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  user_id                   INT(11)       NOT NULL AUTO_INCREMENT,
  email                     VARCHAR(100)  NOT NULL,
  password_hash             VARCHAR(255)  NOT NULL,
  ull_name                 VARCHAR(100)  NOT NULL,
  phone                     VARCHAR(20)   DEFAULT NULL,
  
ole                      ENUM('customer','shopkeeper','owner','walkin') NOT NULL DEFAULT 'customer',
  status                    ENUM('active','inactive') NOT NULL DEFAULT 'active',
  email_verified            TINYINT(1)    NOT NULL DEFAULT 0,
  erification_token        VARCHAR(64)   DEFAULT NULL,
  erification_expires      DATETIME      DEFAULT NULL,
  
eset_token               VARCHAR(64)   DEFAULT NULL,
  
eset_expires             DATETIME      DEFAULT NULL,
  consecutive_cancellations TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
  
eservation_banned_until  DATETIME      DEFAULT NULL,
  created_at                DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_email (email),
  KEY idx_users_role (
ole),
  KEY idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -- 2. system_settings --------------------------------------
CREATE TABLE IF NOT EXISTS system_settings (
  setting_id    INT(11)       NOT NULL AUTO_INCREMENT,
  setting_key   VARCHAR(50)   NOT NULL,
  setting_value TEXT          NOT NULL,
  description   VARCHAR(255)  DEFAULT NULL,
  updated_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_id),
  UNIQUE KEY uq_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -- 3. console_types -----------------------------------------
CREATE TABLE IF NOT EXISTS console_types (
  	ype_id     INT(11)        NOT NULL AUTO_INCREMENT,
  	ype_name   VARCHAR(50)    NOT NULL,
  hourly_rate DECIMAL(10,2)  NOT NULL DEFAULT 80.00,
  category    ENUM('console') NOT NULL DEFAULT 'console',
  is_archived TINYINT(1)     NOT NULL DEFAULT 0,
  created_at  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (	ype_id),
  UNIQUE KEY uq_console_type_name (	ype_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -- 4. controller_types --------------------------------------
CREATE TABLE IF NOT EXISTS controller_types (
  	ype_id         INT(11)     NOT NULL AUTO_INCREMENT,
  	ype_name       VARCHAR(50) NOT NULL,
  console_type_id INT(11)     DEFAULT NULL COMMENT 'FK -> console_types.type_id',
  is_archived     TINYINT(1)  NOT NULL DEFAULT 0,
  created_at      TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (	ype_id),
  UNIQUE KEY uq_controller_type_name (	ype_name),
  KEY k_ctrlt_console_type (console_type_id),
  CONSTRAINT k_ctrlt_console_type FOREIGN KEY (console_type_id)
    REFERENCES console_types (	ype_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -- 5. games -------------------------------------------------
CREATE TABLE IF NOT EXISTS games (
  game_id          INT(11)      NOT NULL AUTO_INCREMENT,
  game_name        VARCHAR(150) NOT NULL,
  console_type_id  INT(11)      DEFAULT NULL COMMENT 'Primary console; NULL = multi-platform',
  genre            VARCHAR(50)  DEFAULT NULL,
  is_active        TINYINT(1)   NOT NULL DEFAULT 1,
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (game_id),
  UNIQUE KEY uq_game_name (game_name),
  KEY k_games_console_type (console_type_id),
  CONSTRAINT k_games_console_type FOREIGN KEY (console_type_id)
    REFERENCES console_types (	ype_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -- 6. pricing_tiers -----------------------------------------
CREATE TABLE IF NOT EXISTS pricing_tiers (
  	ier_id     INT(11)       NOT NULL AUTO_INCREMENT,
  min_minutes INT(11)       NOT NULL,
  max_minutes INT(11)       NOT NULL,
  charge      DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (	ier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -- 7. blocked_dates -----------------------------------------
CREATE TABLE IF NOT EXISTS locked_dates (
  id           INT(11)      NOT NULL AUTO_INCREMENT,
  locked_date DATE         NOT NULL,
  
eason       VARCHAR(255) DEFAULT NULL,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_blocked_date (locked_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -- 8. shop_hours_overrides ----------------------------------
CREATE TABLE IF NOT EXISTS shop_hours_overrides (
  override_id    INT(11)               NOT NULL AUTO_INCREMENT,
  ctivated_by   INT(11)               NOT NULL,
  ction         ENUM('enabled','disabled') NOT NULL,
  extended_until VARCHAR(5)            DEFAULT NULL COMMENT 'HH:MM or NULL',
  
ote           VARCHAR(255)          DEFAULT NULL,
  created_at     DATETIME              NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (override_id),
  KEY idx_sho_created (created_at),
  KEY k_sho_user (ctivated_by),
  CONSTRAINT k_sho_user FOREIGN KEY (ctivated_by)
    REFERENCES users (user_id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -- 9. consoles ----------------------------------------------
-- Includes missing hourly_rate and controller_count columns
CREATE TABLE IF NOT EXISTS consoles (
  console_id       INT(11)      NOT NULL AUTO_INCREMENT,
  console_name     VARCHAR(50)  NOT NULL,
  console_type_id  INT(11)      DEFAULT NULL,
  unit_number      VARCHAR(20)  NOT NULL,
  hourly_rate      DECIMAL(10,2) DEFAULT NULL COMMENT 'Override rate; NULL = use console_types.hourly_rate',
  controller_count INT(11)      NOT NULL DEFAULT 2,
  status           ENUM('available','in_use','maintenance','archived') NOT NULL DEFAULT 'available',
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (console_id),
  UNIQUE KEY uq_unit_number (unit_number),
  KEY idx_consoles_status (status),
  KEY idx_consoles_type (console_type_id),
  CONSTRAINT k_consoles_console_type FOREIGN KEY (console_type_id)
    REFERENCES console_types (	ype_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -- 10. controllers ------------------------------------------
-- FK console_type_id now points to console_types (was wrongly -> controller_types)
CREATE TABLE IF NOT EXISTS controllers (
  controller_id      INT(11)     NOT NULL AUTO_INCREMENT,
  controller_name    VARCHAR(100) NOT NULL,
  controller_type_id INT(11)     DEFAULT NULL,
  console_type_id    INT(11)     DEFAULT NULL COMMENT 'Which console type this belongs to',
  unit_number        VARCHAR(50) NOT NULL,
  status             ENUM('available','in_use','maintenance','archived') NOT NULL DEFAULT 'available',
  
otes              TEXT        DEFAULT NULL,
  created_at         TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (controller_id),
  UNIQUE KEY uq_ctrl_unit_number (unit_number),
  KEY k_ctrl_type (controller_type_id),
  KEY k_ctrl_console_type (console_type_id),
  CONSTRAINT k_ctrl_type FOREIGN KEY (controller_type_id)
    REFERENCES controller_types (	ype_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT k_ctrl_console_type FOREIGN KEY (console_type_id)
    REFERENCES console_types (	ype_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -- 11. gaming_sessions --------------------------------------
CREATE TABLE IF NOT EXISTS gaming_sessions (
  session_id            INT(11)      NOT NULL AUTO_INCREMENT,
  user_id               INT(11)      NOT NULL,
  console_id            INT(11)      NOT NULL,
  
ental_mode           ENUM('hourly','open_time','unlimited') NOT NULL,
  planned_minutes       INT(11)      DEFAULT NULL,
  start_time            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  end_time              DATETIME     DEFAULT NULL,
  duration_minutes      INT(11)      DEFAULT NULL,
  hourly_rate           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  	otal_cost            DECIMAL(10,2) DEFAULT NULL,
  extended_minutes      INT(11)      NOT NULL DEFAULT 0 COMMENT 'Total minutes added via approved extensions',
  status                ENUM('active','completed','cancelled') NOT NULL DEFAULT 'active',
  payment_status        ENUM('paid','unpaid','partial') DEFAULT NULL,
  
otes                 TEXT         DEFAULT NULL,
  source_reservation_id INT(11)      DEFAULT NULL,
  created_by            INT(11)      NOT NULL,
  created_at            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (session_id),
  KEY idx_gs_status (status),
  KEY idx_gs_user (user_id),
  KEY idx_gs_console (console_id),
  KEY idx_gs_start (start_time),
  KEY idx_gs_pay_status (payment_status),
  KEY idx_gs_source_res (source_reservation_id),
  KEY k_gs_created_by (created_by),
  CONSTRAINT k_gs_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON UPDATE CASCADE,
  CONSTRAINT k_gs_console FOREIGN KEY (console_id) REFERENCES consoles (console_id) ON UPDATE CASCADE,
  CONSTRAINT k_gs_created_by FOREIGN KEY (created_by) REFERENCES users (user_id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -- 12. session_extensions -----------------------------------
CREATE TABLE IF NOT EXISTS session_extensions (
  extension_id  INT(11)      NOT NULL AUTO_INCREMENT,
  session_id    INT(11)      NOT NULL,
  
equested_by  INT(11)      NOT NULL,
  pproved_by   INT(11)      DEFAULT NULL,
  extra_minutes INT(11)      NOT NULL,
  extra_cost    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  payment_method ENUM('cash','gcash','credit_card') DEFAULT NULL,
  status        ENUM('pending','approved','denied') NOT NULL DEFAULT 'pending',
  
ote          VARCHAR(255) DEFAULT NULL,
  
equested_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
esolved_at   DATETIME     DEFAULT NULL,
  PRIMARY KEY (extension_id),
  KEY idx_ext_session (session_id),
  KEY idx_ext_status (status),
  KEY idx_ext_requested_by (
equested_by),
  KEY k_ext_approved_by (pproved_by),
  CONSTRAINT k_ext_session FOREIGN KEY (session_id) REFERENCES gaming_sessions (session_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT k_ext_requested_by FOREIGN KEY (
equested_by) REFERENCES users (user_id) ON UPDATE CASCADE,
  CONSTRAINT k_ext_approved_by FOREIGN KEY (pproved_by) REFERENCES users (user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -- 13. additional_requests ----------------------------------
CREATE TABLE IF NOT EXISTS dditional_requests (
  
equest_id   INT(11)      NOT NULL AUTO_INCREMENT,
  session_id   INT(11)      NOT NULL,
  
equest_type ENUM('extra_hours','controller_rental','other') NOT NULL,
  description  TEXT         DEFAULT NULL,
  extra_cost   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status       ENUM('pending','approved','denied') NOT NULL DEFAULT 'pending',
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (
equest_id),
  KEY idx_ar_session (session_id),
  KEY idx_ar_status (status),
  CONSTRAINT k_ar_session FOREIGN KEY (session_id) REFERENCES gaming_sessions (session_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -- 14. transactions -----------------------------------------
CREATE TABLE IF NOT EXISTS 	ransactions (
  	ransaction_id   INT(11)      NOT NULL AUTO_INCREMENT,
  session_id       INT(11)      DEFAULT NULL,
  user_id          INT(11)      NOT NULL,
  mount           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  	endered_amount  DECIMAL(10,2) DEFAULT NULL,
  shortfall_amount DECIMAL(10,2) DEFAULT NULL,
  payment_note     VARCHAR(255) DEFAULT NULL,
  payment_method   ENUM('cash','gcash','credit_card') NOT NULL DEFAULT 'cash',
  payment_status   ENUM('pending','completed','failed') NOT NULL DEFAULT 'pending',
  	ransaction_date DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  processed_by     INT(11)      NOT NULL,
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (	ransaction_id),
  KEY idx_trans_status (payment_status),
  KEY idx_trans_date (	ransaction_date),
  KEY idx_trans_user (user_id),
  KEY k_trans_session (session_id),
  KEY k_trans_processed_by (processed_by),
  CONSTRAINT k_trans_session FOREIGN KEY (session_id) REFERENCES gaming_sessions (session_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT k_trans_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON UPDATE CASCADE,
  CONSTRAINT k_trans_processed_by FOREIGN KEY (processed_by) REFERENCES users (user_id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -- 15. reservations -----------------------------------------
CREATE TABLE IF NOT EXISTS 
eservations (
  
eservation_id       INT(11)      NOT NULL AUTO_INCREMENT,
  user_id              INT(11)      NOT NULL,
  console_id           INT(11)      DEFAULT NULL,
  console_type_id      INT(11)      DEFAULT NULL,
  
ental_mode          ENUM('hourly','open_time','unlimited') NOT NULL DEFAULT 'hourly',
  planned_minutes      INT(11)      DEFAULT NULL,
  
eserved_date        DATE         NOT NULL,
  
eserved_time        TIME         NOT NULL,
  
otes                TEXT         DEFAULT NULL,
  with_controller      TINYINT(1)   NOT NULL DEFAULT 0,
  controller_id        INT(11)      DEFAULT NULL,
  controller_fee       DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  downpayment_amount   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  downpayment_method   ENUM('cash','gcash','credit_card') DEFAULT NULL,
  downpayment_paid     TINYINT(1)   NOT NULL DEFAULT 0,
  payment_proof        VARCHAR(255) DEFAULT NULL,
  payment_proof_status ENUM('pending','verified','rejected') DEFAULT NULL,
  paymongo_source_id   VARCHAR(60)  DEFAULT NULL,
  paymongo_payment_id  VARCHAR(60)  DEFAULT NULL,
  paymongo_status      VARCHAR(30)  DEFAULT NULL,
  status               ENUM('pending','reserved','converted','cancelled','no_show') NOT NULL DEFAULT 'pending',
  cancelled_by         ENUM('user','admin') DEFAULT NULL,
  cancellation_reason  VARCHAR(300) DEFAULT NULL,
  cancel_reason_type   ENUM('schedule_change','found_alternative','budget_issue','technical_issue','emergency','other','admin_decision') DEFAULT NULL,
  cancel_reason_detail TEXT         DEFAULT NULL,
  
efund_issued        TINYINT(1)   NOT NULL DEFAULT 0,
  created_by           INT(11)      NOT NULL,
  created_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (
eservation_id),
  KEY idx_res_status (status),
  KEY idx_res_date (
eserved_date),
  KEY idx_res_user (user_id),
  KEY idx_res_console (console_id),
  KEY idx_res_console_type (console_type_id),
  KEY idx_res_paymongo_src (paymongo_source_id),
  KEY k_res_controller (controller_id),
  KEY k_res_created_by (created_by),
  CONSTRAINT k_res_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON UPDATE CASCADE,
  CONSTRAINT k_res_console FOREIGN KEY (console_id) REFERENCES consoles (console_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT k_res_console_type FOREIGN KEY (console_type_id) REFERENCES console_types (	ype_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT k_res_controller FOREIGN KEY (controller_id) REFERENCES controllers (controller_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT k_res_created_by FOREIGN KEY (created_by) REFERENCES users (user_id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -- 16. reservation_cancellations ----------------------------
CREATE TABLE IF NOT EXISTS 
eservation_cancellations (
  cancel_id           INT(11)  NOT NULL AUTO_INCREMENT,
  
eservation_id      INT(11)  NOT NULL,
  user_id             INT(11)  NOT NULL,
  cancelled_by        ENUM('user','admin') NOT NULL DEFAULT 'user',
  cancel_reason_type  ENUM('schedule_change','found_alternative','budget_issue','technical_issue','emergency','other','admin_decision') NOT NULL DEFAULT 'other',
  cancel_reason_detail TEXT    DEFAULT NULL,
  
efund_issued       TINYINT(1) NOT NULL DEFAULT 0,
  cancelled_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (cancel_id),
  KEY idx_rc_reservation (
eservation_id),
  KEY idx_rc_user (user_id),
  KEY idx_rc_date (cancelled_at),
  CONSTRAINT k_rc_reservation FOREIGN KEY (
eservation_id) REFERENCES 
eservations (
eservation_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT k_rc_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -- 17. reservation_reschedules ------------------------------
-- Added FK for console_id (was missing)
CREATE TABLE IF NOT EXISTS 
eservation_reschedules (
  
eschedule_id      INT(11)  NOT NULL AUTO_INCREMENT,
  
eservation_id     INT(11)  NOT NULL,
  user_id            INT(11)  NOT NULL,
  old_date           DATE     NOT NULL,
  old_time           TIME     NOT NULL,
  old_console_id     INT(11)  DEFAULT NULL,
  old_console_type_id INT(11) DEFAULT NULL,
  
ew_date           DATE     NOT NULL,
  
ew_time           TIME     NOT NULL,
  console_id         INT(11)  DEFAULT NULL,
  
ew_console_type_id INT(11) DEFAULT NULL,
  
eason             ENUM('typhoon','power_outage','emergency','maintenance','user_request','other') NOT NULL DEFAULT 'other',
  
eason_detail      VARCHAR(300) DEFAULT NULL,
  
escheduled_by     INT(11)  NOT NULL,
  initiated_by       ENUM('user','admin') NOT NULL DEFAULT 'admin',
  status             ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'approved',
  seen_by_user       TINYINT(1) NOT NULL DEFAULT 0,
  
eviewed_by        INT(11)  DEFAULT NULL,
  
eviewed_at        DATETIME DEFAULT NULL,
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (
eschedule_id),
  KEY idx_rr_reservation (
eservation_id),
  KEY idx_rr_user (user_id),
  KEY idx_rr_seen (seen_by_user),
  KEY idx_rr_old_ctype (old_console_type_id),
  KEY idx_rr_new_ctype (
ew_console_type_id),
  KEY k_rr_console (console_id),
  CONSTRAINT k_rr_reservation FOREIGN KEY (
eservation_id) REFERENCES 
eservations (
eservation_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT k_rr_console FOREIGN KEY (console_id) REFERENCES consoles (console_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT k_rr_old_console_type FOREIGN KEY (old_console_type_id) REFERENCES console_types (	ype_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT k_rr_new_console_type FOREIGN KEY (
ew_console_type_id) REFERENCES console_types (	ype_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -- 18. tournaments ------------------------------------------
CREATE TABLE IF NOT EXISTS 	ournaments (
  	ournament_id    INT(11)      NOT NULL AUTO_INCREMENT,
  	ournament_name  VARCHAR(150) NOT NULL,
  game_id          INT(11)      DEFAULT NULL,
  game_name        VARCHAR(150) NOT NULL DEFAULT '',
  console_type     VARCHAR(50)  NOT NULL,
  start_date       DATETIME     NOT NULL,
  end_date         DATETIME     NOT NULL,
  entry_fee        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  prize_pool       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  max_participants INT(11)      NOT NULL DEFAULT 16,
  status           ENUM('upcoming','scheduled','ongoing','completed','cancelled') NOT NULL DEFAULT 'upcoming',
  nnouncement     TEXT         DEFAULT NULL,
  created_by       INT(11)      DEFAULT NULL,
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (	ournament_id),
  KEY idx_tourn_status (status),
  KEY idx_tourn_start (start_date),
  KEY k_tourn_game (game_id),
  KEY k_tourn_created_by (created_by),
  CONSTRAINT k_tourn_game FOREIGN KEY (game_id) REFERENCES games (game_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT k_tourn_created_by FOREIGN KEY (created_by) REFERENCES users (user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -- 19. tournament_participants ------------------------------
CREATE TABLE IF NOT EXISTS 	ournament_participants (
  participant_id     INT(11)      NOT NULL AUTO_INCREMENT,
  	ournament_id      INT(11)      NOT NULL,
  user_id            INT(11)      NOT NULL,
  ign                VARCHAR(100) DEFAULT NULL COMMENT 'In-game name',
  contact_number     VARCHAR(20)  DEFAULT NULL,
  walkin_name        VARCHAR(100) DEFAULT NULL COMMENT 'Display name for walk-ins (user_id=0)',
  payment_status     ENUM('pending','paid') NOT NULL DEFAULT 'pending',
  gcash_proof        VARCHAR(255) DEFAULT NULL,
  paymongo_source_id VARCHAR(80)  DEFAULT NULL,
  paymongo_payment_id VARCHAR(80) DEFAULT NULL,
  paymongo_status    VARCHAR(30)  DEFAULT NULL,
  placement          INT(11)      DEFAULT NULL,
  prize_amount       DECIMAL(10,2) DEFAULT NULL,
  
otes              VARCHAR(255) DEFAULT NULL,
  
egistered_by      INT(11)      DEFAULT NULL COMMENT 'Staff user_id if admin-added; NULL for self-reg',
  
egistration_date  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (participant_id),
  KEY idx_tp_tournament (	ournament_id),
  KEY idx_tp_user (user_id),
  KEY idx_tp_payment (payment_status),
  KEY k_tp_registered_by (
egistered_by),
  CONSTRAINT k_tp_tournament FOREIGN KEY (	ournament_id) REFERENCES 	ournaments (	ournament_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT k_tp_registered_by FOREIGN KEY (
egistered_by) REFERENCES users (user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -- 20. activity_logs ----------------------------------------
CREATE TABLE IF NOT EXISTS ctivity_logs (
  log_id     INT(11)      NOT NULL AUTO_INCREMENT,
  user_id    INT(11)      NOT NULL,
  ction     VARCHAR(255) NOT NULL,
  details    TEXT         DEFAULT NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (log_id),
  KEY idx_al_user (user_id),
  KEY idx_al_action (ction(50)),
  KEY idx_al_date (created_at),
  CONSTRAINT k_al_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -- 21. reports ----------------------------------------------
CREATE TABLE IF NOT EXISTS 
eports (
  
eport_id   INT(11)      NOT NULL AUTO_INCREMENT,
  
eport_type ENUM('daily_sales','rental_records','console_usage','tournament') NOT NULL,
  generated_by INT(11)     NOT NULL,
  date_from   DATE         NOT NULL,
  date_to     DATE         NOT NULL,
  ile_path   VARCHAR(255) DEFAULT NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (
eport_id),
  KEY idx_rep_type (
eport_type),
  KEY idx_rep_date (created_at),
  KEY k_rep_user (generated_by),
  CONSTRAINT k_rep_user FOREIGN KEY (generated_by) REFERENCES users (user_id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -- 22. notifications (NEW) ----------------------------------
-- user_id = NULL means broadcast to all users
CREATE TABLE IF NOT EXISTS 
otifications (
  
otification_id INT(11)      NOT NULL AUTO_INCREMENT,
  user_id         INT(11)      DEFAULT NULL COMMENT 'NULL = broadcast to all',
  	ype            ENUM('reservation_update','session_alert','tournament','system','promotion') NOT NULL DEFAULT 'system',
  	itle           VARCHAR(150) NOT NULL,
  message         TEXT         NOT NULL,
  
eference_type  ENUM('reservation','session','tournament','none') DEFAULT 'none',
  
eference_id    INT(11)      DEFAULT NULL COMMENT 'ID of the related record',
  is_read         TINYINT(1)   NOT NULL DEFAULT 0,
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (
otification_id),
  KEY idx_notif_user (user_id),
  KEY idx_notif_read (is_read),
  KEY idx_notif_type (	ype),
  KEY idx_notif_date (created_at),
  CONSTRAINT k_notif_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- DEFAULT SEED DATA
-- ============================================================

-- Walk-in system user (user_id = 0 reserved)
INSERT IGNORE INTO users (user_id,email,password_hash,ull_name,
ole,status,email_verified)
VALUES (0,'walkin@system.local','','Walk-in Customer','walkin','active',1);

-- Default console types
INSERT IGNORE INTO console_types (	ype_id,	ype_name,hourly_rate) VALUES
(1,'PS5',90.00),
(2,'PS4',80.00),
(3,'Xbox Series X',80.00),
(4,'Nintendo Switch',80.00);

-- Default controller types (FK -> console_types)
INSERT IGNORE INTO controller_types (	ype_id,	ype_name,console_type_id) VALUES
(1,'DualSense',1),
(2,'DualShock 4',2),
(3,'Xbox Controller',3),
(4,'Joy-Con',4);

-- Default games
INSERT IGNORE INTO games (game_name,console_type_id,genre) VALUES
('Tekken 8',1,'Fighting'),
('EA Sports FC 25',1,'Sports'),
('Call of Duty: Modern Warfare III',1,'FPS'),
('Mortal Kombat 1',1,'Fighting'),
('NBA 2K25',1,'Sports'),
('God of War Ragnarok',1,'Action-Adventure'),
('Spider-Man 2',1,'Action-Adventure'),
('Fortnite',NULL,'Battle Royale'),
('Valorant',NULL,'FPS'),
('Minecraft',NULL,'Sandbox');

-- Default pricing tiers (overtime/partial hour charges)
INSERT IGNORE INTO pricing_tiers (	ier_id,min_minutes,max_minutes,charge) VALUES
(1,0,4,0.00),
(2,5,19,20.00),
(3,20,34,40.00),
(4,35,49,60.00),
(5,50,59,80.00);

-- Default system settings
INSERT IGNORE INTO system_settings (setting_key,setting_value,description) VALUES
('shop_name','Good Spot Gaming Hub','Shop name'),
('shop_address','Don Placido Avenue, Dasmariñas, Cavite','Shop address'),
('shop_phone','09171234567','Shop contact number'),
('business_hours_open','12:00','Shop opening time'),
('business_hours_close','00:00','Shop closing time'),
('shop_override_enabled','0','Manual open/close override flag'),
('shop_override_closing_time','','Override closing time HH:MM'),
('unlimited_rate','400','Rate for unlimited play (whole day) in PHP'),
('controller_rental_fee','20.00','Additional controller rental fee in PHP'),
('bonus_paid_minutes','120','Every X paid minutes earns bonus time'),
('bonus_free_minutes','30','Bonus free minutes earned per cycle'),
('max_hourly_minutes','240','Max bookable paid minutes for hourly sessions'),
('session_min_charge','50','Minimum charge for sessions up to 30 min'),
('tournament_default_fee','100.00','Default tournament entry fee in PHP'),
('gcash_number','09XX-XXX-XXXX','Shop GCash number for payments'),
('contact_email','goodspotgaminghub@gmail.com','Shop contact email'),
('base_url','','Base URL for the application'),
('smtp_host','','SMTP host'),
('smtp_port','587','SMTP port'),
('smtp_username','','SMTP username'),
('smtp_password','','SMTP password'),
('smtp_encryption','tls','SMTP encryption'),
('brevo_api_key','','Brevo/Sendinblue API key'),
('sender_email','goodspotgaminghub@gmail.com','Sender email address');

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
-- ============================================================
-- End of gamingspothub_clean.sql
-- ============================================================
