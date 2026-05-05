<?php
require_once __DIR__ . '/includes/db_config.php';

// 1. Update reservation status enum
$conn->query("ALTER TABLE reservations MODIFY COLUMN `status` enum('pending','reserved','reserved','converted','cancelled','no_show') NOT NULL DEFAULT 'pending'");
$conn->query("UPDATE reservations SET status = 'reserved' WHERE status = 'reserved'");
$conn->query("ALTER TABLE reservations MODIFY COLUMN `status` enum('pending','reserved','converted','cancelled','no_show') NOT NULL DEFAULT 'pending'");

// 2. Modify reservation_reschedules
$conn->query("ALTER TABLE reservation_reschedules ADD COLUMN initiated_by enum('user','admin') NOT NULL DEFAULT 'admin' AFTER rescheduled_by");
$conn->query("ALTER TABLE reservation_reschedules ADD COLUMN status enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'approved' AFTER initiated_by");
$conn->query("ALTER TABLE reservation_reschedules MODIFY COLUMN reason enum('typhoon','power_outage','emergency','maintenance','user_request','other') NOT NULL DEFAULT 'other'");

echo "DB Schema Updated.\n";
