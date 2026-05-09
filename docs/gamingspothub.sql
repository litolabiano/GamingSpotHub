-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 09, 2026 at 10:27 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gamingspothub`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `action`, `details`, `created_at`) VALUES
(1, 12, 'Restore Session', 'Restored session #4 for User #33. Time elapsed since end: 1m 45s. Console: PS5-02. Original End Time: 2026-05-08 18:17:41.', '2026-05-08 18:19:26'),
(2, 12, 'Restore Session', 'Restored session #4 for User #33. Time elapsed since end: 1m 10s. Console: PS5-02. Original End Time: 2026-05-08 18:19:47.', '2026-05-08 18:20:57'),
(3, 12, 'Restore Session', 'Restored session #8 for User #0. Time elapsed since end: 0m 16s. Console: PS5-02. Original End Time: 2026-05-08 18:49:16.', '2026-05-08 18:49:32'),
(4, 12, 'Restore Session', 'Restored session #9 for User #0. Time elapsed since end: 0m 46s. Console: PS5-03. Original End Time: 2026-05-08 18:51:25.', '2026-05-08 18:52:11'),
(5, 12, 'Restore Session', 'Restored session #10 for User #0. Time elapsed since end: 0m 43s. Console: PS5-01. Original End Time: 2026-05-08 18:53:30.', '2026-05-08 18:54:13'),
(6, 12, 'Restore Session', 'Restored session #11 for User #0. Time elapsed since end: 0m 58s. Console: PS5-05. Original End Time: 2026-05-08 19:13:45.', '2026-05-08 19:14:43'),
(7, 12, 'Restore Session', 'Restored session #7 for User #0. Time elapsed since end: 41m 21s. Console: 1. Original End Time: 2026-05-08 18:40:47.', '2026-05-08 19:22:08'),
(8, 12, 'Restore Session', 'Restored session #9 for User #0. Time elapsed since end: 1m 7s. Console: PS5-03. Original End Time: 2026-05-08 19:21:20.', '2026-05-08 19:22:27'),
(9, 12, 'Restore Session', 'Restored session #11 for User #0. Time elapsed since end: -6m -52s. Console: PS5-05. Original End Time: 2026-05-08 19:30:00.', '2026-05-08 19:24:08'),
(10, 12, 'Restore Session', 'Restored session #10 for User #0. Time elapsed since end: 10m 30s. Console: PS5-01. Original End Time: 2026-05-08 19:22:53.', '2026-05-08 19:33:23'),
(11, 12, 'Restore Session', 'Restored session #12 for User #0. Time elapsed since end: 1m 5s. Console: PS5-02. Original End Time: 2026-05-08 19:42:17. Payments made at end were voided.', '2026-05-08 19:43:22'),
(12, 12, 'Restore Session', 'Restored session #12 for User #0. Time elapsed since end: 54m 45s. Console: PS5-02. Original End Time: 2026-05-08 19:45:13. Payments made at end were voided.', '2026-05-08 20:39:58'),
(13, 12, 'Tournament Status', 'Updated Tournament #2 status to Upcoming', '2026-05-09 00:14:24'),
(14, 12, 'Tournament Status', 'Updated Tournament #2 status to Scheduled', '2026-05-09 00:14:28'),
(15, 12, 'End Session', 'Ended Session #13. Console: PS5-03. Duration: 59 min. Total Cost: ₱100.00.', '2026-05-09 00:23:30'),
(16, 12, 'Restore Session', 'Restored session #13 for User #0. Time elapsed since end: 30m 31s. Console: PS5-03. Original End Time: 2026-05-09 00:23:30. Payments made at end were voided.', '2026-05-09 00:54:01'),
(17, 12, 'End Session', 'Ended Session #13. Console: PS5-03. Duration: 90 min. Total Cost: ₱140.00.', '2026-05-09 00:54:10'),
(18, 12, 'Controller Type', 'Added new controller type: hello (for hello)', '2026-05-09 09:43:20'),
(19, 12, 'Controller unit', 'Added new controller: 67 (hello), Unit: 67', '2026-05-09 10:11:03'),
(20, 12, 'Edit Console', 'Updated Console ID #1: PS5 Unit 1, Unit: PS5-01, Rate: ₱/hr', '2026-05-09 10:27:17'),
(21, 12, 'Edit Console', 'Updated Console ID #1: PS5 Unit 1, Unit: PS5-01, Rate: ₱/hr', '2026-05-09 10:27:47'),
(22, 12, 'Edit Console', 'Updated Console ID #1: PS5 Unit 1, Unit: PS5-01, Rate: ₱/hr', '2026-05-09 10:30:25'),
(23, 12, 'Console Status', 'Updated Console ID #1 status to Available', '2026-05-09 10:30:33'),
(24, 12, 'Edit Console', 'Updated Console ID #1: PS5 Unit 1, Unit: PS5-01, Rate: ₱/hr', '2026-05-09 10:30:39'),
(25, 12, 'Edit Console', 'Updated Console ID #1: PS5 Unit 1, Unit: PS5-01, Rate: ₱/hr', '2026-05-09 10:32:33'),
(26, 12, 'Edit Console', 'Updated Console ID #1: PS5 Unit 1, Unit: PS5-01, Rate: ₱/hr', '2026-05-09 10:32:41'),
(27, 12, 'Edit Console', 'Updated Console ID #1: PS5 Unit 1, Unit: PS5-01', '2026-05-09 10:34:55'),
(28, 12, 'Edit Console', 'Updated Console ID #8: Xbox Unit 8, Unit: XBX-08', '2026-05-09 10:49:14'),
(29, 12, 'Edit Console', 'Updated Console ID #8: Xbox Unit 8, Unit: XBX-08', '2026-05-09 10:50:33'),
(30, 12, 'Controller unit', 'Added new controller: hello, Unit: CTRL-08', '2026-05-09 10:54:00'),
(31, 12, 'Controller Unit', 'Permanently deleted Controller ID #1', '2026-05-09 10:54:29'),
(32, 12, 'Controller Status', 'Updated Controller ID #4 status to Available', '2026-05-09 10:54:34'),
(33, 12, 'Controller Status', 'Updated Controller ID #13 status to Available', '2026-05-09 10:54:40'),
(34, 12, 'Controller Status', 'Updated Controller ID #14 status to Available', '2026-05-09 10:54:47'),
(35, 12, 'Reschedule Proposal', 'Proposed new schedule for Reservation #45: 2026-05-11 12:00. Reason: Typhoon', '2026-05-09 11:29:08'),
(36, 12, 'System Settings', 'Updated system settings and synced console rates.', '2026-05-09 12:38:44'),
(37, 12, 'System Settings', 'Updated system settings and synced console rates.', '2026-05-09 12:38:51'),
(38, 12, 'System Settings', 'Updated system settings and synced console rates.', '2026-05-09 12:48:47'),
(39, 12, 'Tournament Status', 'Updated Tournament #2 status to Upcoming', '2026-05-09 12:55:55'),
(40, 12, 'Tournament Status', 'Updated Tournament #2 status to Scheduled', '2026-05-09 12:55:57'),
(41, 12, 'Tournament Status', 'Updated Tournament #2 status to Ongoing', '2026-05-09 12:55:57'),
(42, 12, 'Tournament Status', 'Updated Tournament #2 status to Completed', '2026-05-09 12:55:59'),
(43, 12, 'Create Tournament', 'Created Tournament #3: tiken it (tekin eyt) on PS5', '2026-05-09 12:56:25'),
(44, 12, 'Tournament Status', 'Updated Tournament #3 status to Scheduled', '2026-05-09 12:56:26'),
(45, 12, 'Tournament Status', 'Updated Tournament #3 status to Upcoming', '2026-05-09 12:56:29'),
(46, 12, 'Tournament Status', 'Updated Tournament #3 status to Scheduled', '2026-05-09 12:56:43'),
(47, 12, 'Start Session', 'Started Session #14 for Walk-in. Console: PS5-01. Mode: Hourly (30 min)', '2026-05-09 13:24:06'),
(48, 12, 'Start Session', 'Started Session #15 for Walk-in. Console: PS5-04. Mode: Hourly (150 min)', '2026-05-09 13:24:21'),
(49, 12, 'Start Session', 'Started Session #16 for Walk-in. Console: 1. Mode: Hourly (30 min)', '2026-05-09 13:24:42'),
(50, 12, 'Extend Session', 'Directly extended Session #14 by 15 min. Total added: 15 min (including bonus).', '2026-05-09 13:24:52'),
(51, 39, 'Reschedule Request', 'Customer requested to reschedule Reservation #49 to 2026-05-10 13:00', '2026-05-09 13:30:21'),
(52, 12, 'Reschedule Approve', 'Approved reschedule request #30 for Reservation #49', '2026-05-09 13:30:45'),
(53, 12, 'Reschedule Proposal', 'Proposed new schedule for Reservation #49: 2026-05-09 13:00. Reason: Maintenance', '2026-05-09 13:31:01'),
(54, 39, 'Reschedule Confirm', 'User confirmed reschedule for Reservation #49 to 2026-05-09 13:00', '2026-05-09 13:31:16'),
(55, 12, 'Console Type', 'Archived Console Type ID #138', '2026-05-09 13:38:19'),
(56, 12, 'Console Type', 'Added new console type: PC', '2026-05-09 14:47:46'),
(57, 12, 'Add Console', 'Added new console: PC UNIT 1, Unit: PC-01', '2026-05-09 14:48:05'),
(58, 12, 'Edit Console', 'Updated Console ID #3228: PC UNIT 1, Unit: PC-01', '2026-05-09 14:48:16'),
(59, 12, 'Start Session', 'Started Session #18 for Walk-in. Console: PC-01. Mode: Hourly (60 min)', '2026-05-09 14:48:33'),
(60, 12, 'Controller Type', 'Added new controller type: pc1 controller (for PC)', '2026-05-09 14:49:53'),
(61, 12, 'Edit Console', 'Updated Console ID #3228: PC UNIT 1, Unit: PC-01', '2026-05-09 14:49:59'),
(62, 12, 'Controller unit', 'Added new controller: pc1 controller, Unit: ctrl sa pc', '2026-05-09 14:50:14'),
(63, 12, 'Edit Console', 'Updated Console ID #3228: PC UNIT 1, Unit: PC-01', '2026-05-09 15:21:32'),
(64, 12, 'Edit Console', 'Updated Console ID #3228: PC UNIT 1, Unit: PC-01', '2026-05-09 15:21:59'),
(65, 12, 'Console Status', 'Updated Console ID #3228 status to Archived', '2026-05-09 15:22:02'),
(66, 12, 'Tournament Participant Removed', 'Removed Participant Sibe Loresco from Tournament: tiken it (#3) at May 09, 2026 03:23 PM', '2026-05-09 15:23:44'),
(67, 12, 'Console Status', 'Updated Console ID #3228 status to Available', '2026-05-09 15:26:01'),
(68, 12, 'Console Status', 'Updated Console ID #3228 status to Archived', '2026-05-09 15:26:08'),
(69, 12, 'Tournament Payment Updated', 'Updated payment status to PAID for Participant Sibe in Tournament: tiken it (#3)', '2026-05-09 15:32:08'),
(70, 12, 'Tournament Participant Removed', 'Removed Participant Sibe from Tournament: tiken it (#3) at May 09, 2026 03:32 PM', '2026-05-09 15:32:10'),
(71, 12, 'Tournament Participant Removed', 'Removed Participant Sibe from Tournament: tiken it (#3) at May 09, 2026 03:32 PM', '2026-05-09 15:32:13'),
(72, 12, 'Tournament Participant Removed', 'Removed Participant Sibe Loresco from Tournament: tiken it (#3) at May 09, 2026 03:32 PM', '2026-05-09 15:32:35'),
(73, 12, 'End Session', 'Ended Session #18. Console: PC-01. Duration: 71 min. Total Cost: ₱100.00.', '2026-05-09 16:00:29'),
(74, 12, 'End Session', 'Ended Session #16. Console: 1. Duration: 155 min. Total Cost: ₱230.00.', '2026-05-09 16:00:38'),
(75, 12, 'Console Status', 'Updated Console ID #3228 status to Archived', '2026-05-09 16:00:49'),
(76, 12, 'Console Status', 'Updated Console ID #3227 status to Archived', '2026-05-09 16:00:53'),
(77, 12, 'Delete Console', 'Permanently deleted Console: PC UNIT 1 (Unit: PC-01, Type: PC)', '2026-05-09 16:00:57'),
(78, 12, 'Delete Console', 'Permanently deleted Console: PS5 (Unit: 1, Type: hello)', '2026-05-09 16:01:17'),
(79, 12, 'Console Status', 'Updated Console ID #3226 status to Archived', '2026-05-09 16:01:25'),
(80, 12, 'Delete Console', 'Permanently deleted Console: ps 6 (Unit: NINTENDO-01, Type: PS6)', '2026-05-09 16:01:31'),
(81, 12, 'Console Status', 'Updated Console ID #3225 status to Archived', '2026-05-09 16:01:37'),
(82, 12, 'Delete Console', 'Permanently deleted Console: PS5 (Unit: PS5-9, Type: PS5)', '2026-05-09 16:01:44'),
(83, 12, 'End Session', 'Ended Session #14. Console: PS5-01. Duration: 171 min. Total Cost: ₱220.00.', '2026-05-09 16:16:00'),
(84, 12, 'Unblock Date', 'Unblocked system for date: 2026-05-29', '2026-05-09 16:18:07'),
(85, 12, 'Block Date', 'Blocked system for date: 2026-05-10. Reason: MAINTENANCE', '2026-05-09 16:18:22'),
(86, 12, 'Console Type', 'Archived Console Type ID #11', '2026-05-09 16:18:49');

-- --------------------------------------------------------

--
-- Table structure for table `additional_requests`
--

CREATE TABLE `additional_requests` (
  `request_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `request_type` enum('extra_hours','controller_rental','other') NOT NULL,
  `description` text DEFAULT NULL,
  `extra_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','approved','denied') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `additional_requests`
--

INSERT INTO `additional_requests` (`request_id`, `session_id`, `request_type`, `description`, `extra_cost`, `status`, `created_at`) VALUES
(14, 12, 'controller_rental', 'Controller rental fee', 20.00, 'approved', '2026-05-08 19:42:11'),
(15, 13, 'controller_rental', 'Controller rental fee', 20.00, 'approved', '2026-05-08 23:23:40'),
(16, 15, 'controller_rental', 'Controller rental fee', 20.00, 'approved', '2026-05-09 13:24:21'),
(17, 16, 'controller_rental', 'Controller rental fee', 20.00, 'approved', '2026-05-09 13:24:42');

-- --------------------------------------------------------

--
-- Table structure for table `blocked_dates`
--

CREATE TABLE `blocked_dates` (
  `id` int(11) NOT NULL,
  `blocked_date` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blocked_dates`
--

INSERT INTO `blocked_dates` (`id`, `blocked_date`, `reason`, `created_at`) VALUES
(4, '2026-05-10', 'MAINTENANCE', '2026-05-09 16:18:22');

-- --------------------------------------------------------

--
-- Table structure for table `consoles`
--

CREATE TABLE `consoles` (
  `console_id` int(11) NOT NULL,
  `console_name` varchar(50) NOT NULL,
  `console_type_id` int(11) DEFAULT NULL,
  `controller_count` int(11) NOT NULL DEFAULT 2,
  `unit_number` varchar(20) NOT NULL,
  `status` enum('available','in_use','maintenance','archived') NOT NULL DEFAULT 'available',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `consoles`
--

INSERT INTO `consoles` (`console_id`, `console_name`, `console_type_id`, `controller_count`, `unit_number`, `status`, `created_at`) VALUES
(1, 'PS5 Unit 1', 1, 2, 'PS5-01', 'available', '2026-02-21 19:55:41'),
(2, 'PS5 Unit 2', 1, 2, 'PS5-02', 'available', '2026-02-21 19:55:41'),
(3, 'PS5 Unit 3', 1, 2, 'PS5-03', 'in_use', '2026-02-21 19:55:41'),
(4, 'PS5 Unit 4', 1, 2, 'PS5-04', 'in_use', '2026-02-21 19:55:41'),
(5, 'PS5 Unit 5', 1, 2, 'PS5-05', 'in_use', '2026-02-21 19:55:41'),
(6, 'PS4 Unit 6', 2, 2, 'PS4-06', 'maintenance', '2026-02-21 19:55:41'),
(7, 'Xbox Unit 7', 3, 2, 'XBX-07', 'available', '2026-02-21 19:55:41'),
(8, 'Xbox Unit 8', 3, 2, 'XBX-08', 'available', '2026-02-21 19:55:41');

-- --------------------------------------------------------

--
-- Table structure for table `console_types`
--

CREATE TABLE `console_types` (
  `type_id` int(11) NOT NULL,
  `type_name` varchar(50) NOT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `category` enum('console') NOT NULL DEFAULT 'console',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `hourly_rate` decimal(10,2) NOT NULL DEFAULT 80.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `console_types`
--

INSERT INTO `console_types` (`type_id`, `type_name`, `is_archived`, `category`, `created_at`, `hourly_rate`) VALUES
(1, 'PS5', 0, 'console', '2026-05-08 04:10:19', 90.00),
(2, 'PS4', 0, 'console', '2026-05-08 04:10:19', 80.00),
(3, 'Xbox Series X', 0, 'console', '2026-05-08 04:10:19', 80.00),
(11, 'NINTENDO', 1, 'console', '2026-05-08 04:10:19', 90.00),
(12, 'PS6', 0, 'console', '2026-05-08 04:10:19', 80.00),
(138, 'hello', 1, 'console', '2026-05-08 09:39:33', 180.00),
(148, 'DualSense', 1, '', '2026-05-08 10:44:34', 80.00),
(149, 'DualShock 4', 1, '', '2026-05-08 10:44:34', 80.00),
(150, 'Xbox Controller', 1, '', '2026-05-08 10:44:34', 80.00),
(151, 'PC', 0, 'console', '2026-05-09 06:47:46', 80.00);

-- --------------------------------------------------------

--
-- Table structure for table `controllers`
--

CREATE TABLE `controllers` (
  `controller_id` int(11) NOT NULL,
  `controller_type_id` int(11) DEFAULT NULL,
  `unit_number` varchar(50) NOT NULL,
  `status` enum('available','in_use','maintenance','archived') NOT NULL DEFAULT 'available',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `console_type_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `controllers`
--

INSERT INTO `controllers` (`controller_id`, `controller_type_id`, `unit_number`, `status`, `notes`, `created_at`, `updated_at`, `console_type_id`) VALUES
(4, 15, 'CTRL-02', 'available', NULL, '2026-05-08 04:10:19', '2026-05-09 02:54:34', 15),
(13, 15, 'CTRL-03', 'available', NULL, '2026-05-08 04:10:19', '2026-05-09 02:54:40', 15),
(14, 15, 'CTRL-04', 'available', NULL, '2026-05-08 04:10:19', '2026-05-09 02:54:47', 15),
(18, 101, 'CTRL-06', 'available', 'joy con', '2026-05-08 05:05:20', '2026-05-08 12:53:39', 101),
(19, 13, 'CTRL-07', 'available', '', '2026-05-08 06:14:28', '2026-05-08 12:53:39', 13),
(20, 13, '8', 'available', '', '2026-05-08 14:07:22', '2026-05-08 14:07:22', 13),
(21, 102, '67', 'available', '', '2026-05-09 02:11:03', '2026-05-09 02:11:03', 102),
(22, 102, 'CTRL-08', 'available', 'minor', '2026-05-09 02:54:00', '2026-05-09 02:54:00', 102),
(23, 103, 'ctrl sa pc', 'available', '', '2026-05-09 06:50:14', '2026-05-09 06:50:14', 103);

-- --------------------------------------------------------

--
-- Table structure for table `controller_types`
--

CREATE TABLE `controller_types` (
  `type_id` int(11) NOT NULL,
  `type_name` varchar(50) NOT NULL,
  `console_type_id` int(11) DEFAULT NULL COMMENT 'FK -> console_types.type_id',
  `is_archived` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `controller_types`
--

INSERT INTO `controller_types` (`type_id`, `type_name`, `console_type_id`, `is_archived`, `created_at`) VALUES
(13, 'DualSense', 1, 0, '2026-05-08 05:00:32'),
(14, 'DualShock 4', 2, 0, '2026-05-08 05:00:32'),
(15, 'Xbox Controller', 3, 0, '2026-05-08 05:00:32'),
(101, 'joy con', 11, 0, '2026-05-08 05:04:46'),
(102, 'hello', 138, 0, '2026-05-09 01:43:20'),
(103, 'pc1 controller', 151, 0, '2026-05-09 06:49:53');

-- --------------------------------------------------------

--
-- Table structure for table `gaming_sessions`
--

CREATE TABLE `gaming_sessions` (
  `session_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `console_id` int(11) DEFAULT NULL,
  `rental_mode` enum('hourly','open_time','unlimited') NOT NULL,
  `planned_minutes` int(11) DEFAULT NULL,
  `start_time` datetime NOT NULL DEFAULT current_timestamp(),
  `end_time` datetime DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `hourly_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `status` enum('active','completed','cancelled') NOT NULL DEFAULT 'active',
  `payment_status` enum('paid','unpaid','partial') DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `extended_minutes` int(11) NOT NULL DEFAULT 0 COMMENT 'Total minutes added via approved extensions',
  `source_reservation_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gaming_sessions`
--

INSERT INTO `gaming_sessions` (`session_id`, `user_id`, `console_id`, `rental_mode`, `planned_minutes`, `start_time`, `end_time`, `duration_minutes`, `hourly_rate`, `total_cost`, `status`, `payment_status`, `created_by`, `created_at`, `extended_minutes`, `source_reservation_id`, `notes`) VALUES
(12, 0, 3, 'hourly', 90, '2026-05-08 19:42:11', '2026-05-08 20:40:06', 57, 90.00, 100.00, 'completed', NULL, 12, '2026-05-08 19:42:11', 0, NULL, NULL),
(13, 0, 3, 'hourly', 60, '2026-05-08 23:23:40', '2026-05-09 00:54:10', 90, 90.00, 140.00, 'completed', NULL, 12, '2026-05-08 23:23:40', 0, NULL, NULL),
(14, 0, 1, 'hourly', 45, '2026-05-09 13:24:06', '2026-05-09 16:16:00', 171, 90.00, 220.00, 'completed', NULL, 12, '2026-05-09 13:24:06', 15, NULL, NULL),
(15, 0, 4, 'hourly', 180, '2026-05-09 13:24:21', NULL, NULL, 90.00, NULL, 'active', NULL, 12, '2026-05-09 13:24:21', 0, NULL, NULL),
(16, 0, NULL, 'hourly', 30, '2026-05-09 13:24:42', '2026-05-09 16:00:38', 155, 180.00, 230.00, 'completed', NULL, 12, '2026-05-09 13:24:42', 0, NULL, '[Deleted Console: PS5 (1)] '),
(17, 39, 3, 'hourly', 300, '2026-05-09 13:31:17', NULL, NULL, 90.00, NULL, 'active', NULL, 12, '2026-05-09 13:31:17', 0, 49, NULL),
(18, 0, NULL, 'hourly', 60, '2026-05-09 14:48:33', '2026-05-09 16:00:29', 71, 80.00, 100.00, 'completed', NULL, 12, '2026-05-09 14:48:33', 0, NULL, '[Deleted Console: PC UNIT 1 (PC-01)] ');

-- --------------------------------------------------------

--
-- Table structure for table `pricing_tiers`
--

CREATE TABLE `pricing_tiers` (
  `tier_id` int(11) NOT NULL,
  `min_minutes` int(11) NOT NULL,
  `max_minutes` int(11) NOT NULL,
  `charge` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pricing_tiers`
--

INSERT INTO `pricing_tiers` (`tier_id`, `min_minutes`, `max_minutes`, `charge`) VALUES
(1, 0, 4, 0.00),
(2, 5, 19, 20.00),
(3, 20, 34, 40.00),
(4, 35, 49, 60.00),
(5, 50, 59, 80.00);

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `report_type` enum('daily_sales','rental_records','console_usage','tournament') NOT NULL,
  `generated_by` int(11) NOT NULL,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `reservation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `console_id` int(11) DEFAULT NULL,
  `console_type_id` int(11) DEFAULT NULL,
  `rental_mode` enum('hourly','open_time','unlimited') NOT NULL DEFAULT 'hourly',
  `planned_minutes` int(11) DEFAULT NULL,
  `reserved_date` date NOT NULL,
  `reserved_time` time NOT NULL,
  `notes` text DEFAULT NULL,
  `with_controller` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Did customer add a controller rental?',
  `controller_id` int(11) DEFAULT NULL COMMENT 'FK -> controllers.controller_id',
  `controller_fee` decimal(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Controller rental fee at time of booking',
  `downpayment_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `downpayment_method` enum('cash','gcash','credit_card') DEFAULT NULL,
  `downpayment_paid` tinyint(1) NOT NULL DEFAULT 0,
  `payment_proof` varchar(255) DEFAULT NULL,
  `payment_proof_status` enum('pending','verified','rejected') DEFAULT NULL,
  `paymongo_source_id` varchar(60) DEFAULT NULL,
  `paymongo_payment_id` varchar(60) DEFAULT NULL,
  `paymongo_status` varchar(30) DEFAULT NULL,
  `status` enum('pending','reserved','converted','cancelled','no_show') NOT NULL DEFAULT 'pending',
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cancelled_by` enum('user','admin') DEFAULT NULL,
  `cancellation_reason` varchar(300) DEFAULT NULL,
  `refund_issued` tinyint(1) NOT NULL DEFAULT 0,
  `cancel_reason_type` enum('schedule_change','found_alternative','budget_issue','technical_issue','emergency','other','admin_decision') DEFAULT NULL,
  `cancel_reason_detail` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`reservation_id`, `user_id`, `console_id`, `console_type_id`, `rental_mode`, `planned_minutes`, `reserved_date`, `reserved_time`, `notes`, `with_controller`, `controller_id`, `controller_fee`, `downpayment_amount`, `downpayment_method`, `downpayment_paid`, `payment_proof`, `payment_proof_status`, `paymongo_source_id`, `paymongo_payment_id`, `paymongo_status`, `status`, `created_by`, `created_at`, `updated_at`, `cancelled_by`, `cancellation_reason`, `refund_issued`, `cancel_reason_type`, `cancel_reason_detail`) VALUES
(34, 33, NULL, 1, 'hourly', 240, '2026-05-22', '18:30:00', NULL, 0, NULL, 0.00, 34.00, 'gcash', 1, NULL, NULL, 'cs_928743a630058ce10ce5cc66', 'pay_DhnSXceBRGJDFqg38uxEXzPj', 'paid', 'cancelled', 33, '2026-05-08 12:01:01', '2026-05-08 20:53:39', 'user', NULL, 0, 'found_alternative', NULL),
(35, 33, 7, 3, 'hourly', 240, '2026-05-08', '13:55:00', NULL, 0, NULL, 0.00, 34.00, 'gcash', 1, NULL, NULL, 'cs_a01260486b81a2d7bb98fb40', 'pay_Be76ri2h4vWmgKQxuFSADYQk', 'paid', 'converted', 33, '2026-05-08 13:51:55', '2026-05-08 20:53:39', NULL, NULL, 0, NULL, NULL),
(36, 33, 2, 1, 'hourly', 240, '2026-05-08', '14:30:00', NULL, 0, NULL, 0.00, 34.00, 'gcash', 1, NULL, NULL, 'cs_25248cb3641ac3715f2a704e', 'pay_EttvLKi58zJ5WTcVYjKZJNRX', 'paid', 'converted', 33, '2026-05-08 14:24:45', '2026-05-08 20:53:39', NULL, NULL, 0, NULL, NULL),
(37, 33, 4, 1, 'unlimited', NULL, '2026-05-08', '16:00:00', NULL, 0, NULL, 0.00, 40.00, 'gcash', 1, NULL, NULL, 'cs_09a12d218a06eef4a27540fe', 'pay_GmjQcojsJJG5CwGjB8QfgmEc', 'paid', 'cancelled', 33, '2026-05-08 14:39:15', '2026-05-08 20:53:39', 'user', NULL, 0, 'found_alternative', NULL),
(38, 27, 1, 1, 'open_time', NULL, '2026-05-08', '17:00:00', NULL, 0, NULL, 0.00, 0.00, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'converted', 27, '2026-05-08 17:04:00', '2026-05-08 20:53:39', NULL, NULL, 0, NULL, NULL),
(39, 27, NULL, NULL, 'hourly', 30, '2026-05-08', '17:45:00', NULL, 0, NULL, 0.00, 25.00, 'cash', 0, NULL, NULL, NULL, NULL, NULL, 'no_show', 27, '2026-05-08 17:43:26', '2026-05-08 17:46:59', NULL, NULL, 0, NULL, NULL),
(40, 27, 1, 1, 'hourly', 30, '2026-05-08', '17:30:00', NULL, 0, NULL, 0.00, 25.00, 'cash', 0, NULL, NULL, NULL, NULL, NULL, 'converted', 27, '2026-05-08 17:47:40', '2026-05-08 20:53:39', NULL, NULL, 0, NULL, NULL),
(41, 27, NULL, 1, 'hourly', 60, '2026-05-08', '21:00:00', NULL, 0, NULL, 0.00, 24.00, 'gcash', 1, NULL, NULL, 'cs_11d736455a4ffc8ca849ed2f', 'pay_ruaBB7xY2yKdqFnpJ2oNcwo6', 'paid', 'cancelled', 27, '2026-05-08 17:58:10', '2026-05-08 20:53:39', 'user', NULL, 0, NULL, NULL),
(42, 27, 2, 1, 'hourly', 60, '2026-05-09', '20:00:00', NULL, 0, NULL, 0.00, 24.00, 'gcash', 1, NULL, NULL, 'cs_bd003f675afac8fa22184a46', 'pay_dYN2kJPnV8a6ydcgtCZZZFqi', 'paid', 'cancelled', 27, '2026-05-08 18:05:08', '2026-05-08 20:53:39', 'user', NULL, 0, NULL, NULL),
(43, 27, 1, 1, 'hourly', 60, '2026-05-22', '17:30:00', NULL, 0, NULL, 0.00, 24.00, 'gcash', 1, NULL, NULL, 'cs_855c4d477beb9942fa347747', 'pay_uUPwrLdjMX8TBo3e4EFX8QoA', 'paid', 'cancelled', 27, '2026-05-08 18:24:56', '2026-05-08 20:53:39', 'user', NULL, 0, NULL, NULL),
(44, 27, 2, 1, '', NULL, '2026-05-09', '19:30:00', NULL, 0, NULL, 0.00, 40.00, 'gcash', 1, NULL, NULL, 'cs_84b2975b2e99fdfeb53b2f63', 'pay_JuSktbWrLZmL1ZUfHdoFSfTT', 'paid', 'cancelled', 27, '2026-05-08 23:20:19', '2026-05-08 23:37:15', 'user', NULL, 0, NULL, NULL),
(45, 37, NULL, 11, '', NULL, '2026-05-28', '14:15:00', NULL, 0, NULL, 0.00, 0.00, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'pending', 37, '2026-05-08 23:23:14', '2026-05-09 11:29:08', NULL, NULL, 0, NULL, NULL),
(46, 27, 8, 3, 'unlimited', NULL, '2026-05-09', '17:30:00', NULL, 0, NULL, 0.00, 60.00, 'gcash', 1, NULL, NULL, 'cs_33d41a848f907cab4e6f7ff1', 'pay_iWfoFx8WVrJ5PSnZiStEHHpV', 'paid', 'cancelled', 27, '2026-05-09 10:59:39', '2026-05-09 11:15:38', 'user', NULL, 0, NULL, NULL),
(47, 27, 1, 1, 'hourly', 90, '2026-05-09', '13:00:00', NULL, 0, 19, 0.00, 47.00, 'gcash', 1, NULL, NULL, 'cs_bc8415a7fe8cb766bb0a489f', 'pay_enXXxEKP7HrpEZjSDBGKZ49t', 'paid', 'cancelled', 27, '2026-05-09 11:16:35', '2026-05-09 11:25:53', 'user', NULL, 0, NULL, NULL),
(48, 27, 1, 1, 'hourly', 60, '2026-05-09', '16:00:00', NULL, 1, 19, 20.00, 45.00, 'gcash', 1, NULL, NULL, 'cs_f724535341a48bf469a26aa2', 'pay_wXw5VoxAhiVcQP4GFrT8K5gW', 'paid', 'cancelled', 27, '2026-05-09 11:26:32', '2026-05-09 11:48:04', 'user', NULL, 0, NULL, NULL),
(49, 39, 3, 1, 'hourly', 240, '2026-05-09', '13:00:00', NULL, 1, 19, 20.00, 56.00, 'gcash', 1, NULL, NULL, 'cs_425bf29332cf10e08b6b4578', 'pay_SzvQwomQWc4GbJ3nEfYAdTfg', 'paid', 'converted', 39, '2026-05-09 13:10:47', '2026-05-09 13:31:17', NULL, NULL, 0, NULL, NULL),
(50, 39, NULL, 151, 'hourly', 240, '2026-05-15', '16:00:00', '[Deleted Console: PC UNIT 1 (PC-01)] ', 1, 23, 20.00, 54.00, 'gcash', 1, NULL, NULL, 'cs_c184bc2322521e98aa975c34', 'pay_NeiosogjvYQRDPZTZJS54UBL', 'paid', 'reserved', 39, '2026-05-09 14:52:23', '2026-05-09 16:00:57', NULL, NULL, 0, NULL, NULL),
(51, 33, 2, 1, 'hourly', 60, '2026-05-09', '18:30:00', NULL, 1, 19, 20.00, 45.00, 'gcash', 1, NULL, NULL, 'cs_792e33eeaa337843491d7c61', 'pay_LbGVbuC8qnFNgbQocWwf44oD', 'paid', 'reserved', 33, '2026-05-09 16:26:30', '2026-05-09 16:26:30', NULL, NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reservation_cancellations`
--

CREATE TABLE `reservation_cancellations` (
  `cancel_id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `cancelled_by` enum('user','admin') NOT NULL DEFAULT 'user',
  `cancel_reason_type` enum('schedule_change','found_alternative','budget_issue','technical_issue','emergency','other','admin_decision') NOT NULL DEFAULT 'other',
  `cancel_reason_detail` text DEFAULT NULL,
  `refund_issued` tinyint(1) NOT NULL DEFAULT 0,
  `cancelled_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reservation_cancellations`
--

INSERT INTO `reservation_cancellations` (`cancel_id`, `reservation_id`, `user_id`, `cancelled_by`, `cancel_reason_type`, `cancel_reason_detail`, `refund_issued`, `cancelled_at`) VALUES
(18, 34, 33, 'user', 'found_alternative', NULL, 0, '2026-05-08 13:38:43'),
(19, 37, 33, 'user', 'found_alternative', NULL, 0, '2026-05-08 14:40:23'),
(20, 41, 27, 'user', 'schedule_change', NULL, 0, '2026-05-08 18:02:45'),
(21, 42, 27, 'user', 'technical_issue', NULL, 0, '2026-05-08 18:12:42'),
(22, 43, 27, 'user', 'schedule_change', NULL, 0, '2026-05-08 18:25:45'),
(23, 44, 27, 'user', 'budget_issue', NULL, 0, '2026-05-08 23:37:15'),
(24, 46, 27, 'user', 'schedule_change', NULL, 0, '2026-05-09 11:15:38'),
(25, 47, 27, 'user', 'found_alternative', NULL, 0, '2026-05-09 11:25:53'),
(26, 48, 27, 'user', 'schedule_change', NULL, 0, '2026-05-09 11:48:04');

-- --------------------------------------------------------

--
-- Table structure for table `reservation_reschedules`
--

CREATE TABLE `reservation_reschedules` (
  `reschedule_id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `old_date` date NOT NULL,
  `old_time` time NOT NULL,
  `old_console_id` int(11) DEFAULT NULL,
  `old_console_type_id` int(11) DEFAULT NULL,
  `new_date` date NOT NULL,
  `new_time` time NOT NULL,
  `console_id` int(11) DEFAULT NULL,
  `new_console_type_id` int(11) DEFAULT NULL,
  `reason` enum('typhoon','power_outage','emergency','maintenance','user_request','other') NOT NULL DEFAULT 'other',
  `reason_detail` varchar(300) DEFAULT NULL,
  `rescheduled_by` int(11) NOT NULL,
  `initiated_by` enum('user','admin') NOT NULL DEFAULT 'admin',
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'approved',
  `seen_by_user` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reservation_reschedules`
--

INSERT INTO `reservation_reschedules` (`reschedule_id`, `reservation_id`, `user_id`, `old_date`, `old_time`, `old_console_id`, `old_console_type_id`, `new_date`, `new_time`, `console_id`, `new_console_type_id`, `reason`, `reason_detail`, `rescheduled_by`, `initiated_by`, `status`, `seen_by_user`, `created_at`, `reviewed_by`, `reviewed_at`) VALUES
(1, 21, 32, '2026-05-06', '16:00:00', NULL, NULL, '2026-05-07', '17:00:00', NULL, NULL, 'other', 'Customer self-reschedule.', 32, 'admin', 'approved', 1, '2026-05-05 14:38:38', NULL, NULL),
(2, 21, 32, '2026-05-07', '17:00:00', NULL, NULL, '2026-05-07', '17:00:00', NULL, NULL, 'other', 'aalis kami sa wednesday', 12, 'admin', 'approved', 1, '2026-05-05 14:42:37', NULL, NULL),
(3, 21, 32, '2026-05-07', '17:00:00', NULL, NULL, '2026-05-08', '17:00:00', NULL, NULL, 'other', 'aalis kami sa wednesday', 12, 'admin', 'approved', 1, '2026-05-05 14:43:08', NULL, NULL),
(4, 23, 32, '2026-05-07', '13:30:00', NULL, NULL, '2026-05-08', '21:00:00', NULL, NULL, 'user_request', 'Customer self-reschedule request.', 32, 'user', 'approved', 1, '2026-05-05 20:18:25', NULL, NULL),
(5, 24, 33, '2026-05-07', '17:00:00', NULL, NULL, '2026-05-08', '17:00:00', NULL, NULL, 'typhoon', 'confirm mo to baka bigwasan kita', 12, 'admin', 'approved', 1, '2026-05-05 20:33:22', NULL, NULL),
(6, 23, 32, '2026-05-08', '21:00:00', NULL, NULL, '2026-05-09', '21:00:00', NULL, NULL, 'emergency', 'nagka emergency', 12, 'admin', 'approved', 1, '2026-05-07 07:28:34', NULL, NULL),
(7, 25, 26, '2026-05-08', '15:30:00', NULL, NULL, '2026-05-09', '12:00:00', NULL, NULL, 'user_request', 'Customer self-reschedule request.', 26, 'user', 'approved', 1, '2026-05-07 08:00:03', NULL, NULL),
(8, 26, 26, '2026-05-07', '09:15:00', NULL, NULL, '2026-05-07', '19:30:00', NULL, NULL, 'emergency', 'asdasdkashd', 12, 'admin', 'cancelled', 1, '2026-05-07 09:51:00', NULL, NULL),
(9, 28, 26, '2026-05-08', '12:30:00', 1, 1, '2026-05-14', '19:00:00', NULL, 1, 'user_request', 'Customer self-reschedule request.', 26, 'user', 'approved', 0, '2026-05-07 18:25:37', NULL, NULL),
(10, 28, 26, '2026-05-14', '19:00:00', NULL, 1, '2026-05-15', '19:00:00', NULL, 1, 'maintenance', NULL, 12, 'admin', 'approved', 1, '2026-05-07 19:24:59', NULL, NULL),
(11, 28, 26, '2026-05-15', '19:00:00', NULL, 1, '2026-05-16', '19:00:00', 1, 1, 'typhoon', NULL, 12, 'admin', 'approved', 1, '2026-05-07 20:10:46', NULL, NULL),
(12, 29, 27, '2026-05-07', '22:00:00', 2, 1, '2026-05-08', '22:00:00', NULL, 1, 'typhoon', NULL, 12, 'admin', 'approved', 1, '2026-05-07 20:13:47', NULL, NULL),
(13, 29, 27, '2026-05-08', '22:00:00', NULL, 1, '2026-05-08', '22:00:00', NULL, 3, 'emergency', NULL, 12, 'admin', 'approved', 1, '2026-05-07 20:17:15', NULL, NULL),
(14, 29, 27, '2026-05-08', '22:00:00', NULL, 3, '2026-05-09', '22:00:00', NULL, 3, 'power_outage', NULL, 12, 'admin', 'approved', 1, '2026-05-07 21:08:11', NULL, NULL),
(15, 29, 27, '2026-05-09', '22:00:00', NULL, 1, '2026-05-10', '13:30:00', NULL, 1, 'user_request', 'Customer self-reschedule request.', 27, 'user', 'rejected', 1, '2026-05-07 22:15:06', NULL, NULL),
(16, 29, 27, '2026-05-09', '22:00:00', NULL, 1, '2026-05-22', '22:00:00', NULL, 2, 'maintenance', NULL, 12, 'admin', 'approved', 1, '2026-05-07 22:16:07', NULL, NULL),
(17, 29, 27, '2026-05-22', '22:00:00', NULL, 1, '2026-05-22', '22:00:00', NULL, 3, 'power_outage', NULL, 12, 'admin', 'approved', 1, '2026-05-07 22:17:39', NULL, NULL),
(18, 29, 27, '2026-05-22', '22:00:00', NULL, 1, '2026-05-13', '22:00:00', NULL, 1, 'typhoon', NULL, 12, 'admin', 'approved', 1, '2026-05-07 22:18:01', NULL, NULL),
(19, 30, 27, '2026-05-08', '14:00:00', NULL, 1, '2026-05-08', '14:00:00', 7, 3, 'power_outage', NULL, 12, 'admin', 'approved', 1, '2026-05-07 22:20:04', NULL, NULL),
(20, 31, 27, '2026-05-08', '18:30:00', 1, 1, '2026-05-22', '15:30:00', NULL, 1, 'user_request', 'Customer self-reschedule request.', 27, 'user', 'rejected', 1, '2026-05-07 22:22:48', NULL, NULL),
(21, 31, 27, '2026-05-08', '18:30:00', 1, 1, '2026-05-22', '12:00:00', NULL, 1, 'user_request', 'Customer self-reschedule request.', 27, 'user', 'approved', 1, '2026-05-07 22:32:20', NULL, NULL),
(22, 32, 27, '2026-05-08', '14:00:00', 1, 1, '2026-05-08', '14:00:00', NULL, 1, 'user_request', 'Customer self-reschedule request.', 27, 'user', 'approved', 1, '2026-05-07 22:34:59', NULL, NULL),
(23, 33, 27, '2026-05-08', '17:00:00', 1, 1, '2026-05-08', '17:00:00', NULL, 1, 'user_request', 'Customer self-reschedule request.', 27, 'user', 'approved', 0, '2026-05-07 22:44:37', NULL, NULL),
(24, 34, 33, '2026-05-15', '16:30:00', 1, 1, '2026-05-22', '18:30:00', NULL, 1, 'user_request', 'Customer self-reschedule request.', 33, 'user', 'approved', 1, '2026-05-08 12:22:21', NULL, NULL),
(25, 35, 33, '2026-05-13', '13:30:00', 7, 3, '2026-05-08', '13:55:00', NULL, 3, 'user_request', 'Customer self-reschedule request.', 33, 'user', 'approved', 1, '2026-05-08 13:52:25', NULL, NULL),
(26, 38, 27, '2026-05-08', '17:15:00', NULL, 1, '2026-05-08', '17:00:00', 1, 1, 'typhoon', NULL, 12, 'admin', 'approved', 1, '2026-05-08 17:06:21', NULL, NULL),
(27, 41, 27, '2026-05-08', '19:30:00', 3, 1, '2026-05-08', '21:00:00', NULL, 1, 'user_request', 'not avail that time', 12, 'user', 'approved', 1, '2026-05-08 17:59:41', NULL, NULL),
(28, 43, 27, '2026-05-08', '20:00:00', NULL, 1, '2026-05-22', '17:30:00', 1, 1, 'user_request', 'Customer self-reschedule request.', 27, 'user', 'approved', 1, '2026-05-08 18:25:08', NULL, NULL),
(29, 45, 37, '2026-05-28', '14:15:00', NULL, 11, '2026-05-11', '12:00:00', NULL, 11, 'typhoon', NULL, 12, 'admin', 'pending', 0, '2026-05-09 11:29:08', NULL, NULL),
(30, 49, 39, '2026-05-09', '14:30:00', 1, 1, '2026-05-10', '13:00:00', 2, 1, 'user_request', 'Customer self-reschedule request.', 39, 'user', 'approved', 1, '2026-05-09 13:30:21', NULL, NULL),
(31, 49, 39, '2026-05-10', '13:00:00', 2, 1, '2026-05-09', '13:00:00', 3, 1, 'maintenance', NULL, 12, 'admin', 'approved', 1, '2026-05-09 13:31:01', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `session_extensions`
--

CREATE TABLE `session_extensions` (
  `extension_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL COMMENT 'Active session being extended',
  `requested_by` int(11) NOT NULL COMMENT 'user_id who requested (customer or staff)',
  `approved_by` int(11) DEFAULT NULL COMMENT 'user_id of staff who approved (NULL if pending/denied)',
  `extra_minutes` int(11) NOT NULL COMMENT 'Minutes to add to the session',
  `extra_cost` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Cost of the extension (0 for open_time/unlimited)',
  `payment_method` enum('cash','gcash','credit_card') DEFAULT NULL COMMENT 'NULL until staff collects payment on approval',
  `status` enum('pending','approved','denied') NOT NULL DEFAULT 'pending',
  `note` varchar(255) DEFAULT NULL COMMENT 'Optional reason or remark',
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL COMMENT 'When staff approved or denied'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `session_extensions`
--

INSERT INTO `session_extensions` (`extension_id`, `session_id`, `requested_by`, `approved_by`, `extra_minutes`, `extra_cost`, `payment_method`, `status`, `note`, `requested_at`, `resolved_at`) VALUES
(1, 2, 12, 12, 30, 40.00, 'cash', 'approved', 'Direct extension by staff', '2026-05-02 00:16:43', '2026-05-02 00:16:43'),
(2, 2, 12, 12, 30, 40.00, 'cash', 'approved', 'Direct extension by staff', '2026-05-02 00:16:53', '2026-05-02 00:16:53'),
(3, 24, 12, 12, 90, 120.00, 'cash', 'approved', 'Direct extension by staff', '2026-05-05 19:21:08', '2026-05-05 19:21:08'),
(4, 24, 12, 12, 60, 80.00, 'cash', 'approved', 'Direct extension by staff', '2026-05-05 19:53:08', '2026-05-05 19:53:08'),
(5, 43, 12, 12, 120, 160.00, 'cash', 'approved', 'Direct extension by staff', '2026-05-07 07:14:32', '2026-05-07 07:14:32'),
(6, 45, 12, 12, 30, 40.00, 'cash', 'approved', 'Direct extension by staff', '2026-05-07 08:24:19', '2026-05-07 08:24:19'),
(7, 45, 12, 12, 30, 40.00, 'cash', 'approved', 'Direct extension by staff', '2026-05-07 08:24:27', '2026-05-07 08:24:27'),
(8, 45, 12, 12, 60, 80.00, 'cash', 'approved', 'Direct extension by staff', '2026-05-07 08:24:35', '2026-05-07 08:24:35'),
(9, 45, 12, 12, 30, 40.00, 'cash', 'approved', 'Direct extension by staff', '2026-05-07 08:24:55', '2026-05-07 08:24:55'),
(10, 46, 12, 12, 30, 40.00, 'cash', 'approved', 'Direct extension by staff', '2026-05-07 08:26:19', '2026-05-07 08:26:19'),
(11, 46, 12, 12, 120, 160.00, 'cash', 'approved', 'Direct extension by staff', '2026-05-07 08:26:27', '2026-05-07 08:26:27'),
(12, 51, 12, 12, 30, 40.00, 'cash', 'approved', 'Direct extension by staff', '2026-05-07 09:53:58', '2026-05-07 09:53:58'),
(13, 51, 12, 12, 60, 80.00, 'cash', 'approved', 'Direct extension by staff', '2026-05-07 09:54:19', '2026-05-07 09:54:19'),
(14, 54, 12, 12, 120, 160.00, 'cash', 'approved', 'Direct extension by staff', '2026-05-07 10:22:39', '2026-05-07 10:22:39'),
(15, 56, 12, 12, 30, 40.00, 'cash', 'approved', 'Direct extension by staff', '2026-05-07 18:27:14', '2026-05-07 18:27:14'),
(16, 4, 33, NULL, 120, 160.00, NULL, 'pending', NULL, '2026-05-08 14:38:14', NULL),
(17, 14, 12, 12, 15, 22.50, 'cash', 'approved', 'Direct extension by staff', '2026-05-09 13:24:52', '2026-05-09 13:24:52');

-- --------------------------------------------------------

--
-- Table structure for table `shop_hours_overrides`
--

CREATE TABLE `shop_hours_overrides` (
  `override_id` int(11) NOT NULL,
  `activated_by` int(11) NOT NULL,
  `action` enum('enabled','disabled') NOT NULL,
  `extended_until` varchar(5) DEFAULT NULL COMMENT 'HH:MM or NULL = no specific end time',
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(3, 'unlimited_rate', '400', 'Rate for unlimited play (whole day) in ₱', '2026-05-08 23:38:35'),
(4, 'controller_rental_fee', '20.00', 'Additional controller rental fee in ₱', '2026-02-21 19:55:41'),
(5, 'business_hours_open', '12:00', 'Shop opening time', '2026-05-07 10:20:23'),
(6, 'business_hours_close', '00:00', 'Shop closing time', '2026-04-14 09:56:44'),
(7, 'shop_name', 'Good Spot Gaming Hub', 'Shop name', '2026-02-21 19:55:41'),
(8, 'shop_address', 'Don Placido Avenue, Dasmariñas, Cavite', 'Shop address', '2026-02-21 19:55:41'),
(9, 'shop_phone', '09171234567', 'Shop contact number', '2026-02-21 19:55:41'),
(10, 'tournament_default_fee', '100.00', 'Default tournament entry fee in ₱', '2026-02-21 19:55:41'),
(21, 'bonus_paid_minutes', '120', NULL, '2026-04-26 16:47:16'),
(22, 'bonus_free_minutes', '30', NULL, '2026-05-07 10:23:13'),
(23, 'max_hourly_minutes', '240', NULL, '2026-04-26 16:47:16'),
(24, 'session_min_charge', '50', NULL, '2026-05-08 23:41:00'),
(152, 'gcash_number', '09XX-XXX-XXXX', 'Shop GCash number displayed on reservation payment screen', '2026-04-28 19:21:02'),
(164, 'shop_override_enabled', '0', NULL, '2026-05-06 19:55:24'),
(165, 'shop_override_closing_time', '', NULL, '2026-05-06 19:55:24'),
(166, 'contact_email', 'goodspotgaminghub@gmail.com', NULL, '2026-05-06 22:49:41'),
(179, 'smtp_host', '', NULL, '2026-05-06 22:57:45'),
(180, 'smtp_port', '587', NULL, '2026-05-06 22:57:45'),
(181, 'smtp_username', '', NULL, '2026-05-06 22:57:45'),
(182, 'smtp_password', '4th1ngss', NULL, '2026-05-06 22:57:45'),
(183, 'smtp_encryption', 'tls', NULL, '2026-05-06 22:57:45'),
(196, 'brevo_api_key', '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', NULL, '2026-05-09 12:48:47'),
(197, 'sender_email', 'goodspotgaminghub@gmail.com', NULL, '2026-05-06 23:13:14'),
(405, 'base_url', '', NULL, '2026-05-08 23:38:15');

-- --------------------------------------------------------

--
-- Table structure for table `tournaments`
--

CREATE TABLE `tournaments` (
  `tournament_id` int(11) NOT NULL,
  `tournament_name` varchar(150) NOT NULL,
  `game_id` int(11) DEFAULT NULL,
  `game_name` varchar(150) NOT NULL DEFAULT '',
  `console_type` varchar(50) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `entry_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `prize_pool` decimal(10,2) NOT NULL DEFAULT 0.00,
  `max_participants` int(11) NOT NULL DEFAULT 16,
  `status` enum('upcoming','scheduled','ongoing','completed','cancelled') NOT NULL DEFAULT 'upcoming',
  `announcement` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tournaments`
--

INSERT INTO `tournaments` (`tournament_id`, `tournament_name`, `game_id`, `game_name`, `console_type`, `start_date`, `end_date`, `entry_fee`, `prize_pool`, `max_participants`, `status`, `announcement`, `created_by`, `created_at`) VALUES
(1, 'tekken 7', NULL, 'tekken 7', 'PS5', '2026-05-09 08:00:00', '2026-05-15 19:00:00', 500.00, 0.00, 16, 'cancelled', 'join na', 12, '2026-05-02 15:04:42'),
(2, 'asdas', NULL, 'asdad', 'NINTENDO', '2026-05-14 14:30:00', '2026-05-14 16:00:00', 250.00, 10000.00, 16, 'completed', '', 12, '2026-05-07 09:22:00'),
(3, 'tiken it', NULL, 'tekin eyt', 'PS5', '2026-05-16 12:00:00', '2026-05-16 18:00:00', 250.00, 0.00, 0, 'scheduled', '', 12, '2026-05-09 12:56:25');

-- --------------------------------------------------------

--
-- Table structure for table `tournament_participants`
--

CREATE TABLE `tournament_participants` (
  `participant_id` int(11) NOT NULL,
  `tournament_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `registration_date` datetime NOT NULL DEFAULT current_timestamp(),
  `payment_status` enum('pending','paid') NOT NULL DEFAULT 'pending',
  `status` enum('active','removed') NOT NULL DEFAULT 'active',
  `removed_at` datetime DEFAULT NULL,
  `seen_by_user` tinyint(1) DEFAULT 0,
  `placement` int(11) DEFAULT NULL,
  `prize_amount` decimal(10,2) DEFAULT NULL,
  `ign` varchar(100) DEFAULT NULL COMMENT 'In-game name / alias provided at registration',
  `contact_number` varchar(20) DEFAULT NULL COMMENT 'Contact number provided at registration',
  `gcash_proof` varchar(255) DEFAULT NULL COMMENT 'Uploaded GCash receipt filename (self-registrations only)',
  `registered_by` int(11) DEFAULT NULL COMMENT 'Staff user_id if admin-added; NULL for self-registration',
  `walkin_name` varchar(100) DEFAULT NULL COMMENT 'Display name for walk-in participants (user_id = 0)',
  `notes` varchar(255) DEFAULT NULL COMMENT 'Optional note from registrant or staff',
  `paymongo_source_id` varchar(80) DEFAULT NULL,
  `paymongo_payment_id` varchar(80) DEFAULT NULL,
  `paymongo_status` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tournament_participants`
--

INSERT INTO `tournament_participants` (`participant_id`, `tournament_id`, `user_id`, `registration_date`, `payment_status`, `status`, `removed_at`, `seen_by_user`, `placement`, `prize_amount`, `ign`, `contact_number`, `gcash_proof`, `registered_by`, `walkin_name`, `notes`, `paymongo_source_id`, `paymongo_payment_id`, `paymongo_status`) VALUES
(1, 1, 27, '2026-05-02 15:14:51', 'paid', 'active', NULL, 0, NULL, NULL, 'dark fist', '09916310227', NULL, NULL, NULL, 'note', 'src_Cymsv6YwZLgaJhwZhidpawe7', 'pay_3g5WGQgYiqiZwWXQP4vTS24U', 'paid'),
(2, 2, 33, '2026-05-07 09:23:15', 'paid', 'active', NULL, 0, NULL, NULL, 'sibe', '0921931239', NULL, NULL, NULL, '', NULL, NULL, NULL),
(3, 2, 0, '2026-05-07 09:25:37', 'paid', 'active', NULL, 0, NULL, NULL, 'Sugarcoat', '012931832123', NULL, 12, 'GSPOT', '', NULL, NULL, NULL),
(5, 3, 39, '2026-05-09 14:23:45', 'paid', 'removed', '2026-05-09 15:32:13', 0, NULL, NULL, 'Sugarcoat', '09944084214', NULL, NULL, NULL, '', NULL, NULL, NULL),
(6, 3, 32, '2026-05-09 15:05:30', 'pending', 'active', NULL, 0, NULL, NULL, 'SUGARDAD', '09944084214', NULL, NULL, NULL, '', NULL, NULL, NULL),
(7, 3, 33, '2026-05-09 15:31:58', 'pending', 'removed', '2026-05-09 15:32:35', 1, NULL, NULL, 'SUGARDAD', '09944084214', NULL, NULL, NULL, '', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `session_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tendered_amount` decimal(10,2) DEFAULT NULL,
  `shortfall_amount` decimal(10,2) DEFAULT NULL,
  `payment_note` varchar(255) DEFAULT NULL,
  `payment_method` enum('cash','gcash','credit_card') NOT NULL DEFAULT 'cash',
  `payment_status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `transaction_date` datetime NOT NULL DEFAULT current_timestamp(),
  `processed_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transaction_id`, `session_id`, `user_id`, `amount`, `tendered_amount`, `shortfall_amount`, `payment_note`, `payment_method`, `payment_status`, `transaction_date`, `processed_by`, `created_at`) VALUES
(123, NULL, 33, 34.00, 34.00, NULL, 'Downpayment for reservation #34', 'gcash', 'completed', '2026-05-08 12:01:01', 33, '2026-05-08 12:01:01'),
(124, 1, 33, 34.00, 34.00, NULL, 'Downpayment transferred from reservation #35', 'gcash', 'completed', '2026-05-08 13:51:55', 12, '2026-05-08 13:51:55'),
(125, 1, 33, 6.00, 6.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-05-08 14:18:18', 12, '2026-05-08 14:18:18'),
(126, 2, 0, 400.00, 400.00, 0.00, NULL, 'cash', 'completed', '2026-05-08 14:20:05', 12, '2026-05-08 14:20:05'),
(127, 4, 33, 34.00, 34.00, NULL, 'Downpayment transferred from reservation #36', 'gcash', 'completed', '2026-05-08 14:24:45', 12, '2026-05-08 14:24:45'),
(128, NULL, 33, 40.00, 40.00, NULL, 'Downpayment for reservation #37', 'gcash', 'completed', '2026-05-08 14:39:15', 33, '2026-05-08 14:39:15'),
(129, NULL, 27, 25.00, 25.00, NULL, 'Downpayment for reservation #39', 'cash', 'completed', '2026-05-08 17:43:26', 27, '2026-05-08 17:43:26'),
(130, 5, 27, 60.00, 60.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-05-08 17:46:45', 12, '2026-05-08 17:46:45'),
(131, 6, 27, 25.00, 25.00, NULL, 'Downpayment transferred from reservation #40', 'cash', 'completed', '2026-05-08 17:47:40', 12, '2026-05-08 17:47:40'),
(132, 3, 0, 260.00, 260.00, NULL, 'Balance payment collected', 'cash', 'completed', '2026-05-08 17:57:03', 12, '2026-05-08 17:57:03'),
(133, 4, 33, 206.00, 206.00, NULL, 'Balance payment collected', 'cash', 'completed', '2026-05-08 17:57:09', 12, '2026-05-08 17:57:09'),
(134, NULL, 27, 24.00, 24.00, NULL, 'Downpayment for reservation #41', 'gcash', 'completed', '2026-05-08 17:58:10', 27, '2026-05-08 17:58:10'),
(135, NULL, 27, 24.00, 24.00, NULL, 'Downpayment for reservation #42', 'gcash', 'completed', '2026-05-08 18:05:08', 27, '2026-05-08 18:05:08'),
(136, 6, 27, 15.00, 15.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-05-08 18:19:17', 12, '2026-05-08 18:19:17'),
(137, 4, 33, 20.00, 20.00, NULL, 'Balance payment collected', 'cash', 'completed', '2026-05-08 18:19:29', 12, '2026-05-08 18:19:29'),
(138, 3, 0, 40.00, 240.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-05-08 18:19:35', 12, '2026-05-08 18:19:35'),
(139, NULL, 27, 24.00, 24.00, NULL, 'Downpayment for reservation #43', 'gcash', 'completed', '2026-05-08 18:24:56', 27, '2026-05-08 18:24:56'),
(140, 7, 0, 120.00, 120.00, NULL, NULL, 'cash', 'completed', '2026-05-08 18:40:30', 12, '2026-05-08 18:40:30'),
(141, 7, 0, -120.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 00m (₱0.00), refunding unused time (₱120.00)', '', 'completed', '2026-05-08 18:40:47', 12, '2026-05-08 18:40:47'),
(142, 8, 0, 60.00, 60.00, NULL, NULL, 'cash', 'completed', '2026-05-08 18:49:11', 12, '2026-05-08 18:49:11'),
(143, 8, 0, -60.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 00m (₱0.00), refunding unused time (₱60.00)', '', 'completed', '2026-05-08 18:49:16', 12, '2026-05-08 18:49:16'),
(144, 9, 0, 80.00, 80.00, NULL, NULL, 'cash', 'completed', '2026-05-08 18:51:18', 12, '2026-05-08 18:51:18'),
(145, 9, 0, -60.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 00m (₱20.00), refunding unused time (₱60.00)', '', 'completed', '2026-05-08 18:51:25', 12, '2026-05-08 18:51:25'),
(146, 10, 0, 60.00, 60.00, NULL, NULL, 'cash', 'completed', '2026-05-08 18:53:05', 12, '2026-05-08 18:53:05'),
(147, 10, 0, -60.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 00m (₱0.00), refunding unused time (₱60.00)', '', 'completed', '2026-05-08 18:53:30', 12, '2026-05-08 18:53:30'),
(148, 9, 0, 40.00, NULL, NULL, NULL, 'cash', 'completed', '2026-05-08 19:21:20', 12, '2026-05-08 19:21:20'),
(149, 10, 0, 40.00, NULL, NULL, NULL, 'cash', 'completed', '2026-05-08 19:22:53', 12, '2026-05-08 19:22:53'),
(150, 8, 0, 60.00, NULL, NULL, NULL, 'cash', 'completed', '2026-05-08 19:23:31', 12, '2026-05-08 19:23:31'),
(151, 9, 0, 20.00, 20.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-05-08 19:23:34', 12, '2026-05-08 19:23:34'),
(152, 11, 0, 20.00, 20.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-05-08 19:23:38', 12, '2026-05-08 19:23:38'),
(153, 12, 0, 140.00, 140.00, NULL, NULL, 'cash', 'completed', '2026-05-08 19:42:11', 12, '2026-05-08 19:42:11'),
(156, 12, 0, -40.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 57m (₱100.00), refunding unused time (₱40.00)', '', 'completed', '2026-05-08 20:40:06', 12, '2026-05-08 20:40:06'),
(157, NULL, 27, 40.00, 40.00, NULL, 'Downpayment for reservation #44', 'gcash', 'completed', '2026-05-08 23:20:19', 27, '2026-05-08 23:20:19'),
(158, 13, 0, 100.00, 110.00, NULL, NULL, 'cash', 'completed', '2026-05-08 23:23:40', 12, '2026-05-08 23:23:40'),
(159, 13, 0, 40.00, 60.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-05-09 00:54:10', 12, '2026-05-09 00:54:10'),
(160, NULL, 27, 60.00, 60.00, NULL, 'Downpayment for reservation #46', 'gcash', 'completed', '2026-05-09 10:59:39', 27, '2026-05-09 10:59:39'),
(161, NULL, 27, 47.00, 47.00, NULL, 'Downpayment for reservation #47', 'gcash', 'completed', '2026-05-09 11:16:35', 27, '2026-05-09 11:16:35'),
(162, NULL, 27, 45.00, 45.00, NULL, 'Downpayment for reservation #48 (+₱20.00 controller rental)', 'gcash', 'completed', '2026-05-09 11:26:32', 27, '2026-05-09 11:26:32'),
(163, NULL, 39, 56.00, 56.00, NULL, 'Downpayment for reservation #49 (+₱20.00 controller rental)', 'gcash', 'completed', '2026-05-09 13:10:47', 39, '2026-05-09 13:10:47'),
(164, 14, 0, 0.00, 0.00, 22.50, 'Extension +15 min via staff (Extension #17)', 'cash', 'completed', '2026-05-09 13:24:52', 12, '2026-05-09 13:24:52'),
(165, 17, 39, 56.00, 56.00, NULL, 'Downpayment transferred from reservation #49', 'gcash', 'completed', '2026-05-09 13:31:17', 12, '2026-05-09 13:31:17'),
(166, NULL, 39, 54.00, 54.00, NULL, 'Downpayment for reservation #50 (+₱20.00 controller rental)', 'gcash', 'completed', '2026-05-09 14:52:23', 39, '2026-05-09 14:52:23'),
(167, 18, 0, 100.00, 100.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-05-09 16:00:29', 12, '2026-05-09 16:00:29'),
(168, 16, 0, 230.00, 230.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-05-09 16:00:38', 12, '2026-05-09 16:00:38'),
(169, 14, 0, 220.00, 220.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-05-09 16:16:00', 12, '2026-05-09 16:16:00'),
(170, NULL, 33, 45.00, 45.00, NULL, 'Downpayment for reservation #51 (+₱20.00 controller rental)', 'gcash', 'completed', '2026-05-09 16:26:30', 33, '2026-05-09 16:26:30');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('customer','shopkeeper','owner','walkin') NOT NULL DEFAULT 'customer',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_token` varchar(64) DEFAULT NULL,
  `verification_expires` datetime DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `consecutive_cancellations` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `reservation_banned_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password_hash`, `full_name`, `phone`, `role`, `status`, `email_verified`, `verification_token`, `verification_expires`, `reset_token`, `reset_expires`, `created_at`, `consecutive_cancellations`, `reservation_banned_until`) VALUES
(0, 'walkin@system.local', '', 'Walk-in Customer', NULL, 'walkin', 'active', 1, NULL, NULL, NULL, NULL, '2026-04-27 00:33:18', 0, NULL),
(12, 'ljlabianao@gmail.com', '$2y$10$nEuBy0VoWqtbRETOnAD99eurxRHdLaGpxROWA//NGpziKgIG1CmmS', 'lito', '09916310227', 'owner', 'active', 1, NULL, NULL, NULL, NULL, '2026-02-21 20:45:06', 0, NULL),
(17, 'llabianojr@kld.edu.ph', '$2y$10$N97t4MFGjElD3iM.nlpn3uZ/hy1zNC/Lfqi/YozlBB6BG1VWkL.Oe', 'Lito LARGUEZA LABIANO', '09916310227', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-04-12 19:54:25', 0, NULL),
(21, 'hello@gmail.com', '$2y$10$vjAu848ZbS1DpfexUIsM1.x31O/VNLXNwp/sw65NckgmZTHTKLzAO', 'Lito LARGUEZA LABIANO', '09916310227', 'shopkeeper', 'active', 1, NULL, NULL, NULL, NULL, '2026-04-17 11:59:29', 0, '2026-05-04 23:10:54'),
(22, 'admin@gmail.com', '$2y$10$kZCF21cxNKIxXE99XNGyF.nGfT94FZeDaGtbH4YTBOxrpumQLDvIK', 'Admin User', '', 'customer', 'active', 0, '9ad4e0d4b2004d75a763bd2e6e9af695cdb053e39c7cadd65db419783c75b54f', '2026-04-20 16:02:24', NULL, NULL, '2026-04-19 16:02:24', 0, NULL),
(23, 'customer@example.com', '$2y$10$8mmAlQ1UknZorTRMn6NVneRRA5JrVSOO8oMAuDEVll.pICMikYieO', 'Test Customer', '', 'customer', 'active', 0, '5337f25a858a79327c9001f982e6629f2f64be6579986d9da00c816159a6a75c', '2026-04-20 20:49:05', NULL, NULL, '2026-04-19 20:49:05', 0, NULL),
(24, 'lito@example.com', '$2y$10$b1z6jAajQr5NjOyHWkU5kOJ7kx/BdeM6HbUg/qLWdElsilx20bLta', 'Lito Test', '', 'customer', 'active', 0, '10512cc414ac02ff1b961698a17863ad87e3e55d976dc1e14d92a2af161ee3bd', '2026-04-20 20:52:01', NULL, NULL, '2026-04-19 20:52:01', 0, NULL),
(26, 'hbernesto@kld.edu.ph', '$2y$10$/cCpA7hhbUMKxyVwk9r4Uuh5p6r4qo70eP9Wv1UmFfVnLl8rD4vLG', 'Harvie Bernesto', '09944084214', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-04-27 23:19:32', 1, '2026-05-07 07:36:37'),
(27, 'helios@gmail.com', '$2y$10$.T/FeCELcCi8CIuswNHl7.tbPi62hXm.AfCJUFt/UU5TNJuFlBGVS', 'helios', 'helios@gmail.com', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-04-27 23:29:14', 0, NULL),
(28, 'testcustomer@example.com', '$2y$10$kGbRHj87ratNFmTwi2pmTOqMaV2KV0fvB2H5sMYMsEzMfFVM4Uku2', 'Test Customer', '09123456789', 'customer', 'active', 0, '29333d3de426116e2f4f7d304d0b9f4b4b3b76feb6ee30a877d3d5457179f6e1', '2026-04-28 23:52:37', NULL, NULL, '2026-04-27 23:52:37', 0, NULL),
(29, 'test@example.com', '$2y$10$mLQD41zmi3vIoJ8sR8/Ntu6uZojYGjvzKbNb3Y.evmmQ3SyMB7evC', 'Test User', '09171234567', 'customer', 'active', 1, '0ae0598c1a671992ec26dfae71eabe5e2f9269bb1da1ae9f85c78695949d6184', '2026-04-30 20:29:21', NULL, NULL, '2026-04-29 20:29:21', 0, NULL),
(30, '09171234567test@gmail.com', '$2y$10$jyNy.ykur0jghOW.APBCwOpx.Y5fTLFYDdfTbek92i6AffjjIlcBq', 'test@gmail.comTest User', 'Password123!09171234', 'customer', 'active', 0, '6a296a2ad91537491af9a4309082ea6e25e0a818b60dd86e65ccc5e842ccd700', '2026-05-01 00:13:31', NULL, NULL, '2026-04-30 00:13:32', 0, NULL),
(31, 'mariejoy.12.mji@gmail.com', '$2y$10$VDFqBSZiRt9I1ZLIRaVBdOSQ0Red0aogx.VqXx1EMrGP.905x0bX2', 'MJ IGNACIO', '', 'customer', 'active', 0, 'd0e91ebfe1587c05cd41f92225bfa010276bb3e2c5c3ae0e7fefabd03a28c942', '2026-05-01 08:01:11', NULL, NULL, '2026-04-30 08:01:11', 0, NULL),
(32, 'harvieloresco2004@gmail.com', '$2y$10$5ra6ycYpFWsty4s.ikJESuw688uGiHUCKkXPlA8z44ZS9CzZnGU86', 'Harvie Loresco', '09944084214', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-05-05 14:34:15', 0, '2026-05-14 07:38:38'),
(33, 'sibeloresco@gmail.com', '$2y$10$th7lcBzspd8ES7d3oyIM7utZLr3u5r9zPJiCkIkpuWI44vi/ICxc6', 'Sibe Loresco', '09944084214', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-05-05 20:26:57', 0, NULL),
(35, 'kianpablo1996@gmail.com', '$2y$10$9HVf3fBPYcbdleA0/2fo8emj4MH52KuVtwHR1uXszxWT9yQJy/vo6', 'Kian Pablo', '09944084214', 'shopkeeper', 'active', 1, NULL, NULL, NULL, NULL, '2026-05-06 00:25:13', 0, NULL),
(37, 'zerotwo02darlingninelota@gmail.com', '$2y$10$Q72iGgv0rWvD6I9OaSk6Iuq7BxVJY5Tmw2HuYCH.kGlLT6tSVqNom', 'Gelo', '09123455666', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-05-07 09:01:18', 0, NULL),
(38, 'unique_tester_99@example.com', '$2y$10$KlQu2AzHZNth7OfEOAY5T.iRsiw9FsKR9MAkplksBodg/vMp84vny', 'Test User', '', 'customer', 'active', 0, 'c2d332cbbbde9a33cc7e290f45c9ce3acaa51ce1bb1d9e9836f5c2504f018794', '2026-05-08 13:02:28', NULL, NULL, '2026-05-07 13:02:28', 0, NULL),
(39, 'harviebernesto1215@gmail.com', '$2y$10$VNVRswTT5A.iQCt0gJSM5ObT411z2rObQOH7EQvw5ryQ/HR5nTItm', 'Sibe', '09944084214', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-05-09 12:58:58', 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `additional_requests`
--
ALTER TABLE `additional_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `idx_addreq_session` (`session_id`),
  ADD KEY `idx_addreq_status` (`status`);

--
-- Indexes for table `blocked_dates`
--
ALTER TABLE `blocked_dates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `blocked_date` (`blocked_date`);

--
-- Indexes for table `consoles`
--
ALTER TABLE `consoles`
  ADD PRIMARY KEY (`console_id`),
  ADD UNIQUE KEY `unit_number` (`unit_number`),
  ADD KEY `idx_consoles_status` (`status`),
  ADD KEY `idx_consoles_type` (`console_type_id`);

--
-- Indexes for table `console_types`
--
ALTER TABLE `console_types`
  ADD PRIMARY KEY (`type_id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

--
-- Indexes for table `controllers`
--
ALTER TABLE `controllers`
  ADD PRIMARY KEY (`controller_id`),
  ADD UNIQUE KEY `unit_number` (`unit_number`),
  ADD KEY `fk_controllers_ctrlt` (`console_type_id`),
  ADD KEY `fk_controllers_type` (`controller_type_id`);

--
-- Indexes for table `controller_types`
--
ALTER TABLE `controller_types`
  ADD PRIMARY KEY (`type_id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

--
-- Indexes for table `gaming_sessions`
--
ALTER TABLE `gaming_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `idx_sessions_status` (`status`),
  ADD KEY `idx_sessions_user` (`user_id`),
  ADD KEY `idx_sessions_console` (`console_id`),
  ADD KEY `idx_sessions_start` (`start_time`),
  ADD KEY `fk_sessions_created_by` (`created_by`),
  ADD KEY `idx_sessions_pay_status` (`payment_status`),
  ADD KEY `idx_gs_source_res` (`source_reservation_id`);

--
-- Indexes for table `pricing_tiers`
--
ALTER TABLE `pricing_tiers`
  ADD PRIMARY KEY (`tier_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `idx_reports_type` (`report_type`),
  ADD KEY `idx_reports_date` (`created_at`),
  ADD KEY `fk_reports_user` (`generated_by`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `idx_res_status` (`status`),
  ADD KEY `idx_res_date` (`reserved_date`),
  ADD KEY `idx_res_user` (`user_id`),
  ADD KEY `idx_res_console` (`console_id`),
  ADD KEY `fk_res_created_by` (`created_by`),
  ADD KEY `idx_res_paymongo_src` (`paymongo_source_id`),
  ADD KEY `fk_res_controller` (`controller_id`),
  ADD KEY `idx_res_console_type` (`console_type_id`);

--
-- Indexes for table `reservation_cancellations`
--
ALTER TABLE `reservation_cancellations`
  ADD PRIMARY KEY (`cancel_id`),
  ADD KEY `idx_rc_reservation` (`reservation_id`),
  ADD KEY `idx_rc_user` (`user_id`),
  ADD KEY `idx_rc_cancelled_by` (`cancelled_by`),
  ADD KEY `idx_rc_reason_type` (`cancel_reason_type`),
  ADD KEY `idx_rc_date` (`cancelled_at`),
  ADD KEY `idx_rc_res` (`reservation_id`);

--
-- Indexes for table `reservation_reschedules`
--
ALTER TABLE `reservation_reschedules`
  ADD PRIMARY KEY (`reschedule_id`),
  ADD KEY `idx_rr_reservation` (`reservation_id`),
  ADD KEY `idx_rr_user` (`user_id`),
  ADD KEY `idx_rr_seen` (`seen_by_user`),
  ADD KEY `idx_rr_old_ctype` (`old_console_type_id`),
  ADD KEY `idx_rr_new_ctype` (`new_console_type_id`);

--
-- Indexes for table `session_extensions`
--
ALTER TABLE `session_extensions`
  ADD PRIMARY KEY (`extension_id`),
  ADD KEY `idx_ext_session` (`session_id`),
  ADD KEY `idx_ext_status` (`status`),
  ADD KEY `idx_ext_requested_by` (`requested_by`),
  ADD KEY `fk_ext_approved_by` (`approved_by`);

--
-- Indexes for table `shop_hours_overrides`
--
ALTER TABLE `shop_hours_overrides`
  ADD PRIMARY KEY (`override_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `tournaments`
--
ALTER TABLE `tournaments`
  ADD PRIMARY KEY (`tournament_id`),
  ADD KEY `idx_tourn_status` (`status`),
  ADD KEY `idx_tourn_start` (`start_date`),
  ADD KEY `fk_tourn_game` (`game_id`);

--
-- Indexes for table `tournament_participants`
--
ALTER TABLE `tournament_participants`
  ADD PRIMARY KEY (`participant_id`),
  ADD KEY `idx_tp_tournament` (`tournament_id`),
  ADD KEY `idx_tp_user` (`user_id`),
  ADD KEY `idx_tp_payment` (`payment_status`),
  ADD KEY `fk_tp_registered_by` (`registered_by`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `idx_trans_status` (`payment_status`),
  ADD KEY `idx_trans_date` (`transaction_date`),
  ADD KEY `idx_trans_user` (`user_id`),
  ADD KEY `fk_trans_session` (`session_id`),
  ADD KEY `fk_trans_processed_by` (`processed_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `additional_requests`
--
ALTER TABLE `additional_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `blocked_dates`
--
ALTER TABLE `blocked_dates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `consoles`
--
ALTER TABLE `consoles`
  MODIFY `console_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3229;

--
-- AUTO_INCREMENT for table `console_types`
--
ALTER TABLE `console_types`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=152;

--
-- AUTO_INCREMENT for table `controllers`
--
ALTER TABLE `controllers`
  MODIFY `controller_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `controller_types`
--
ALTER TABLE `controller_types`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `gaming_sessions`
--
ALTER TABLE `gaming_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `pricing_tiers`
--
ALTER TABLE `pricing_tiers`
  MODIFY `tier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `reservation_cancellations`
--
ALTER TABLE `reservation_cancellations`
  MODIFY `cancel_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `reservation_reschedules`
--
ALTER TABLE `reservation_reschedules`
  MODIFY `reschedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `session_extensions`
--
ALTER TABLE `session_extensions`
  MODIFY `extension_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `shop_hours_overrides`
--
ALTER TABLE `shop_hours_overrides`
  MODIFY `override_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=478;

--
-- AUTO_INCREMENT for table `tournaments`
--
ALTER TABLE `tournaments`
  MODIFY `tournament_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tournament_participants`
--
ALTER TABLE `tournament_participants`
  MODIFY `participant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `additional_requests`
--
ALTER TABLE `additional_requests`
  ADD CONSTRAINT `fk_addreq_session` FOREIGN KEY (`session_id`) REFERENCES `gaming_sessions` (`session_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `consoles`
--
ALTER TABLE `consoles`
  ADD CONSTRAINT `fk_consoles_console_type` FOREIGN KEY (`console_type_id`) REFERENCES `console_types` (`type_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `controllers`
--
ALTER TABLE `controllers`
  ADD CONSTRAINT `fk_controllers_ctrlt` FOREIGN KEY (`console_type_id`) REFERENCES `controller_types` (`type_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_controllers_type` FOREIGN KEY (`controller_type_id`) REFERENCES `controller_types` (`type_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `gaming_sessions`
--
ALTER TABLE `gaming_sessions`
  ADD CONSTRAINT `fk_sessions_console` FOREIGN KEY (`console_id`) REFERENCES `consoles` (`console_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sessions_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_reports_user` FOREIGN KEY (`generated_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `fk_res_console` FOREIGN KEY (`console_id`) REFERENCES `consoles` (`console_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_res_controller` FOREIGN KEY (`controller_id`) REFERENCES `controllers` (`controller_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_res_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_res_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reservations_console_type` FOREIGN KEY (`console_type_id`) REFERENCES `console_types` (`type_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `reservation_cancellations`
--
ALTER TABLE `reservation_cancellations`
  ADD CONSTRAINT `fk_rc_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `reservation_reschedules`
--
ALTER TABLE `reservation_reschedules`
  ADD CONSTRAINT `fk_rr_new_console_type` FOREIGN KEY (`new_console_type_id`) REFERENCES `console_types` (`type_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rr_old_console_type` FOREIGN KEY (`old_console_type_id`) REFERENCES `console_types` (`type_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `tournament_participants`
--
ALTER TABLE `tournament_participants`
  ADD CONSTRAINT `fk_tp_registered_by` FOREIGN KEY (`registered_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tp_tournament` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`tournament_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
