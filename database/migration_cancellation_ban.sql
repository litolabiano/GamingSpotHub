-- ============================================================
-- GamingSpotHub: FAQ Compliance Migration
-- Run this in phpMyAdmin or via MySQL CLI
-- ============================================================

-- 1. Add cancellation tracking to users table
ALTER TABLE `users`
  ADD COLUMN `consecutive_cancellations` TINYINT UNSIGNED NOT NULL DEFAULT 0
    COMMENT '3-strike rule: increments on each user-cancelled reservation; resets on successful session',
  ADD COLUMN `reservation_banned_until` DATETIME NULL DEFAULT NULL
    COMMENT 'Set to NOW()+7days on 3rd consecutive cancel; NULL means not banned';

-- 2. Fix hourly rate settings (were incorrectly set to 40.00 instead of 80.00)
UPDATE `system_settings` SET `setting_value` = '80.00' WHERE `setting_key` = 'ps5_hourly_rate';
UPDATE `system_settings` SET `setting_value` = '80.00' WHERE `setting_key` = 'xbox_hourly_rate';

-- 3. Add session_min_charge setting if not present (₱50 for first 30-min)
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `description`)
VALUES ('session_min_charge', '50.00', 'Minimum charge for first 30-min block (₱50)');
