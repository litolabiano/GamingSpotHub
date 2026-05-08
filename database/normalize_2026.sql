SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;

-- ============================================================
-- 1. CLEAN UP console_types
-- ============================================================

UPDATE `console_types`
SET `is_archived` = 1
WHERE `type_id` IN (148, 149, 150);

-- ============================================================
-- 2. consoles table
-- ============================================================

ALTER TABLE `consoles`
  ADD COLUMN `console_type_id` INT(11) DEFAULT NULL
    AFTER `console_name`;

UPDATE `consoles` c
JOIN `console_types` ct
  ON ct.`type_name` = c.`console_type`
 AND ct.`is_archived` = 0
SET c.`console_type_id` = ct.`type_id`;

ALTER TABLE `consoles`
  ADD CONSTRAINT `fk_consoles_console_type`
    FOREIGN KEY (`console_type_id`)
    REFERENCES `console_types` (`type_id`)
    ON UPDATE CASCADE
    ON DELETE SET NULL;

ALTER TABLE `consoles`
  DROP COLUMN `console_type`,
  DROP COLUMN `controller_count`,
  DROP COLUMN `hourly_rate`;

-- ============================================================
-- 3. controllers table
-- ============================================================

ALTER TABLE `controllers`
  ADD COLUMN `controller_type_id` INT(11) DEFAULT NULL
    AFTER `controller_name`;

UPDATE `controllers` c
JOIN `controller_types` ct
  ON ct.`type_name` = c.`controller_type`
SET c.`controller_type_id` = ct.`type_id`;

ALTER TABLE `controllers`
  ADD CONSTRAINT `fk_controllers_type`
    FOREIGN KEY (`controller_type_id`)
    REFERENCES `controller_types` (`type_id`)
    ON UPDATE CASCADE
    ON DELETE SET NULL;

ALTER TABLE `controllers`
  DROP COLUMN `controller_type`;

-- ============================================================
-- 4. reservations table
-- ============================================================

ALTER TABLE `reservations`
  ADD COLUMN `console_type_id` INT(11) DEFAULT NULL
    AFTER `console_id`;

UPDATE `reservations` r
JOIN `console_types` ct
  ON ct.`type_name` = r.`console_type`
 AND ct.`is_archived` = 0
SET r.`console_type_id` = ct.`type_id`;

ALTER TABLE `reservations`
  ADD CONSTRAINT `fk_reservations_console_type`
    FOREIGN KEY (`console_type_id`)
    REFERENCES `console_types` (`type_id`)
    ON UPDATE CASCADE
    ON DELETE SET NULL;

ALTER TABLE `reservations`
  DROP COLUMN `console_type`;

-- ============================================================
-- 5. reservation_reschedules table
-- ============================================================

ALTER TABLE `reservation_reschedules`
  ADD COLUMN `old_console_type_id` INT(11) DEFAULT NULL
    AFTER `old_console_id`,
  ADD COLUMN `new_console_type_id` INT(11) DEFAULT NULL
    AFTER `console_id`;

UPDATE `reservation_reschedules` rr
JOIN `console_types` ct
  ON ct.`type_name` = rr.`old_console_type`
 AND ct.`is_archived` = 0
SET rr.`old_console_type_id` = ct.`type_id`;

UPDATE `reservation_reschedules` rr
JOIN `console_types` ct
  ON ct.`type_name` = rr.`console_type`
 AND ct.`is_archived` = 0
SET rr.`new_console_type_id` = ct.`type_id`;

ALTER TABLE `reservation_reschedules`
  ADD CONSTRAINT `fk_rr_old_console_type`
    FOREIGN KEY (`old_console_type_id`)
    REFERENCES `console_types` (`type_id`)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rr_new_console_type`
    FOREIGN KEY (`new_console_type_id`)
    REFERENCES `console_types` (`type_id`)
    ON UPDATE CASCADE
    ON DELETE SET NULL;

ALTER TABLE `reservation_reschedules`
  DROP COLUMN `old_console_type`,
  DROP COLUMN `console_type`;

-- ============================================================
-- 6. Add index on new FK columns for query performance
-- ============================================================

ALTER TABLE `consoles`
  ADD INDEX `idx_consoles_type` (`console_type_id`);

ALTER TABLE `reservations`
  ADD INDEX `idx_res_console_type` (`console_type_id`);

ALTER TABLE `reservation_reschedules`
  ADD INDEX `idx_rr_old_ctype` (`old_console_type_id`),
  ADD INDEX `idx_rr_new_ctype` (`new_console_type_id`);

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
