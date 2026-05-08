USE `gamingspothub`;
SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `consoles`;
CREATE TABLE `consoles` (
  `console_id` int(11) NOT NULL AUTO_INCREMENT,
  `console_name` varchar(50) NOT NULL,
  `console_type` varchar(50) NOT NULL,
  `unit_number` varchar(20) NOT NULL,
  `controller_count` int(11) DEFAULT 2,
  `status` enum('available','in_use','maintenance','archived') NOT NULL DEFAULT 'available',
  `hourly_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`console_id`),
  UNIQUE KEY `unit_number` (`unit_number`),
  KEY `idx_consoles_status` (`status`),
  KEY `idx_consoles_type` (`console_type`)
) ENGINE=InnoDB AUTO_INCREMENT=3225 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `consoles` (`console_id`, `console_name`, `console_type`, `unit_number`, `controller_count`, `status`, `hourly_rate`, `created_at`) VALUES
(1, 'PS5 Unit 1', 'PS5', 'PS5-01', 2, 'available', 90.00, '2026-02-21 19:55:41'),
(2, 'PS5 Unit 2', 'PS5', 'PS5-02', 2, 'in_use', 90.00, '2026-02-21 19:55:41'),
(3, 'PS5 Unit 3', 'PS5', 'PS5-03', 2, 'available', 90.00, '2026-02-21 19:55:41'),
(4, 'PS5 Unit 4', 'PS5', 'PS5-04', 2, 'available', 90.00, '2026-02-21 19:55:41'),
(5, 'PS5 Unit 5', 'PS5', 'PS5-05', 2, 'available', 90.00, '2026-02-21 19:55:41'),
(6, 'PS4 Unit 6', 'PS4', 'PS4-06', 2, 'maintenance', 90.00, '2026-02-21 19:55:41'),
(7, 'Xbox Unit 7', 'Xbox Series X', 'XBX-07', 2, 'available', 80.00, '2026-02-21 19:55:41'),
(8, 'Xbox Unit 8', 'Xbox Series X', 'XBX-08', 2, 'available', 80.00, '2026-02-21 19:55:41');

-- --------------------------------------------------------

DROP TABLE IF EXISTS `console_types`;
CREATE TABLE `console_types` (
  `type_id` int(11) NOT NULL AUTO_INCREMENT,
  `type_name` varchar(50) NOT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`type_id`),
  UNIQUE KEY `type_name` (`type_name`)
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `console_types` (`type_id`, `type_name`, `is_archived`, `created_at`) VALUES
(1, 'PS5', 0, '2026-05-08 04:10:19'),
(2, 'PS4', 0, '2026-05-08 04:10:19'),
(3, 'Xbox Series X', 0, '2026-05-08 04:10:19'),
(11, 'NINTENDO', 0, '2026-05-08 04:10:19'),
(12, 'PS6', 0, '2026-05-08 04:10:19');

-- --------------------------------------------------------

DROP TABLE IF EXISTS `controller_types`;
CREATE TABLE `controller_types` (
  `type_id` int(11) NOT NULL AUTO_INCREMENT,
  `type_name` varchar(50) NOT NULL,
  `console_type_id` int(11) DEFAULT NULL COMMENT 'FK -> console_types.type_id',
  `is_archived` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`type_id`),
  UNIQUE KEY `type_name` (`type_name`)
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `controller_types` (`type_id`, `type_name`, `console_type_id`, `is_archived`, `created_at`) VALUES
(13, 'DualSense', 1, 0, '2026-05-08 05:00:32'),
(14, 'DualShock 4', 2, 0, '2026-05-08 05:00:32'),
(15, 'Xbox Controller', 3, 0, '2026-05-08 05:00:32'),
(101, 'joy con', 11, 0, '2026-05-08 05:04:46');

-- --------------------------------------------------------

DROP TABLE IF EXISTS `controllers`;
CREATE TABLE `controllers` (
  `controller_id` int(11) NOT NULL AUTO_INCREMENT,
  `controller_name` varchar(100) NOT NULL,
  `controller_type` varchar(50) DEFAULT NULL,
  `unit_number` varchar(50) NOT NULL,
  `status` enum('available','in_use','maintenance','archived') NOT NULL DEFAULT 'available',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `console_type_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`controller_id`),
  UNIQUE KEY `unit_number` (`unit_number`),
  KEY `fk_controllers_ctrlt` (`console_type_id`),
  CONSTRAINT `fk_controllers_ctrlt` FOREIGN KEY (`console_type_id`) REFERENCES `controller_types` (`type_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `controllers` (`controller_id`, `controller_name`, `controller_type`, `unit_number`, `status`, `notes`, `created_at`, `updated_at`, `console_type_id`) VALUES
(1, 'Xbox Controller', 'Xbox Controller', 'CTRL-01', 'archived', NULL, '2026-05-08 04:10:19', '2026-05-08 06:15:33', 15),
(4, 'Xbox Controller', 'Xbox Controller', 'CTRL-02', 'archived', NULL, '2026-05-08 04:10:19', '2026-05-08 06:15:35', 15),
(13, 'ARCADE STICK', 'Xbox Controller', 'CTRL-03', 'archived', NULL, '2026-05-08 04:10:19', '2026-05-08 06:15:37', 15),
(14, 'NINTENDO', 'Xbox Controller', 'CTRL-04', 'archived', NULL, '2026-05-08 04:10:19', '2026-05-08 06:15:39', 15),
(18, 'joy con', 'joy con', 'CTRL-06', 'available', 'joy con', '2026-05-08 05:05:20', '2026-05-08 05:05:20', 101),
(19, 'ps5 Controller', 'DualSense', 'CTRL-07', 'available', '', '2026-05-08 06:14:28', '2026-05-08 06:14:28', 13);


-- --------------------------------------------------------
-- Update reservations table to include controller info

ALTER TABLE `reservations`
  ADD COLUMN IF NOT EXISTS `with_controller` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Did customer add a controller rental?' AFTER `notes`,
  ADD COLUMN IF NOT EXISTS `controller_id` int(11) DEFAULT NULL COMMENT 'FK -> controllers.controller_id' AFTER `with_controller`,
  ADD COLUMN IF NOT EXISTS `controller_fee` decimal(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Controller rental fee at time of booking' AFTER `controller_id`;

ALTER TABLE `reservations`
  DROP FOREIGN KEY IF EXISTS `fk_res_controller`;
ALTER TABLE `reservations`
  ADD CONSTRAINT `fk_res_controller` FOREIGN KEY (`controller_id`) REFERENCES `controllers` (`controller_id`) ON DELETE SET NULL ON UPDATE CASCADE;

SET FOREIGN_KEY_CHECKS=1;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
