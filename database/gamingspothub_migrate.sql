SET FOREIGN_KEY_CHECKS = 0;
ALTER TABLE consoles
  ADD COLUMN hourly_rate DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE consoles
  ADD COLUMN controller_count INT(11) NOT NULL DEFAULT 2;
ALTER TABLE consoles
  ADD COLUMN 
otes TEXT DEFAULT NULL;
ALTER TABLE gaming_sessions
  ADD COLUMN 
otes TEXT DEFAULT NULL;
UPDATE console_types SET category = 'console'
  WHERE category = '' OR category IS NULL;
UPDATE console_types SET is_archived = 1
  WHERE console_type_id IN (148,149,150);
CREATE TABLE IF NOT EXISTS games (
  game_id         INT(11)      NOT NULL AUTO_INCREMENT,
  game_name       VARCHAR(150) NOT NULL,
  console_type_id INT(11)      DEFAULT NULL,
  genre           VARCHAR(50)  DEFAULT NULL,
  is_active       TINYINT(1)   NOT NULL DEFAULT 1,
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (game_id),
  UNIQUE KEY uq_game_name (game_name),
  KEY k_games_console_type (console_type_id),
  CONSTRAINT k_games_console_type FOREIGN KEY (console_type_id)
    REFERENCES console_types (console_type_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS 
otifications (
  
otification_id INT(11)      NOT NULL AUTO_INCREMENT,
  user_id         INT(11)      DEFAULT NULL,
  	ype            ENUM('reservation_update','session_alert','tournament','system','promotion') NOT NULL DEFAULT 'system',
  	itle           VARCHAR(150) NOT NULL,
  message         TEXT         NOT NULL,
  eference_type  ENUM('reservation','session','tournament','none') DEFAULT 'none',
  eference_id    INT(11)      DEFAULT NULL,
  is_read         TINYINT(1)   NOT NULL DEFAULT 0,
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (
otification_id),
  KEY idx_notif_user (user_id),
  KEY idx_notif_read (is_read),
  KEY idx_notif_type (	ype),
  KEY idx_notif_date (created_at),
  CONSTRAINT k_notif_user FOREIGN KEY (user_id)
    REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE eservation_reschedules
  ADD CONSTRAINT k_rr_console FOREIGN KEY (console_id)
    REFERENCES consoles (console_id) ON DELETE SET NULL ON UPDATE CASCADE;
INSERT IGNORE INTO games (game_name,genre) VALUES
  ('Tekken 8','Fighting'),('EA Sports FC 25','Sports'),
  ('Call of Duty: Modern Warfare III','FPS'),('Mortal Kombat 1','Fighting'),
  ('NBA 2K25','Sports'),('God of War Ragnarok','Action-Adventure'),
  ('Spider-Man 2','Action-Adventure'),('Fortnite','Battle Royale'),
  ('Valorant','FPS'),('Minecraft','Sandbox');
INSERT IGNORE INTO system_settings (setting_key,setting_value,description) VALUES
  ('session_min_charge','50','Minimum charge for sessions up to 30 min'),
  ('base_url','','Base URL for the application');
SET FOREIGN_KEY_CHECKS = 1;
