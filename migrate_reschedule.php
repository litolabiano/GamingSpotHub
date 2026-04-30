<?php
/**
 * Migration: Create reservation_reschedules table
 * Run once via browser: http://localhost/GamingSpotHub/migrate_reschedule.php
 */
require_once __DIR__ . '/includes/db_config.php';

$sql = "CREATE TABLE IF NOT EXISTS `reservation_reschedules` (
  `reschedule_id`   int(11)      NOT NULL AUTO_INCREMENT,
  `reservation_id`  int(11)      NOT NULL,
  `user_id`         int(11)      NOT NULL,
  `old_date`        date         NOT NULL,
  `old_time`        time         NOT NULL,
  `new_date`        date         NOT NULL,
  `new_time`        time         NOT NULL,
  `reason`          enum('typhoon','power_outage','emergency','maintenance','other') NOT NULL DEFAULT 'other',
  `reason_detail`   varchar(300) DEFAULT NULL,
  `rescheduled_by`  int(11)      NOT NULL,
  `seen_by_user`    tinyint(1)   NOT NULL DEFAULT 0,
  `created_at`      datetime     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`reschedule_id`),
  KEY `idx_rr_reservation` (`reservation_id`),
  KEY `idx_rr_user`        (`user_id`),
  KEY `idx_rr_seen`        (`seen_by_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql)) {
    echo "<b style='color:green;'>✔ reservation_reschedules table created (or already exists).</b><br>";
} else {
    echo "<b style='color:red;'>✘ Error: " . $conn->error . "</b><br>";
}
$conn->close();
