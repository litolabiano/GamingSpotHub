-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 08, 2026 at 06:35 PM
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
(3, 12, 'Restore Session', 'Restored session #5 for User #0. Time elapsed since end: 1m 34s. Console: PS5-05. Original End Time: 2026-05-08 19:17:12.', '2026-05-08 19:18:46'),
(4, 12, 'Restore Session', 'Restored session #6 for User #0. Time elapsed since end: 7m 12s. Console: PS5-01. Original End Time: 2026-05-08 19:49:11. Payments made at end were voided.', '2026-05-08 19:56:23'),
(5, 12, 'Restore Session', 'Restored session #5 for User #0. Time elapsed since end: 26m 13s. Console: PS5-05. Original End Time: 2026-05-08 22:17:56. Payments made at end were voided.', '2026-05-08 22:44:09'),
(6, 12, 'Restore Session', 'Restored session #4 for User #33. Time elapsed since end: 34m 22s. Console: PS5-02. Original End Time: 2026-05-08 22:17:59. Payments made at end were voided.', '2026-05-08 22:52:21'),
(7, 35, 'Start Session', 'Started Session #9 for Walk-in. Console: Unknown. Mode: Hourly (30 min)', '2026-05-08 23:20:34'),
(8, 35, 'Start Session', 'Started Session #10 for User #26. Console: Unknown. Mode: Unlimited', '2026-05-08 23:21:22'),
(9, 12, 'Console Status', 'Updated Console ID #3228 status to Archived', '2026-05-08 23:24:50'),
(10, 12, 'Console Status', 'Updated Console ID #3227 status to Archived', '2026-05-08 23:25:06'),
(11, 12, 'Console Status', 'Updated Console ID #3229 status to Archived', '2026-05-08 23:25:13'),
(12, 12, 'Console Status', 'Updated Console ID #3230 status to Archived', '2026-05-08 23:25:19'),
(13, 35, 'End Session', 'Ended Session #8. Console: PS5-11. Duration: 79 min. Total Cost: ₱100.00.', '2026-05-08 23:49:25'),
(14, 12, 'Auto-End Session', 'Auto-ended Unlimited Session #2 for Walk-in Customer on Console: PS5-03 at 12:00 AM', '2026-05-09 00:00:00'),
(15, 12, 'Auto-End Session', 'Auto-ended Unlimited Session #10 for Harvie Bernesto on Console: PS5-05 at 12:00 AM', '2026-05-09 00:00:00');

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
(13, 7, 'controller_rental', 'Controller rental fee', 20.00, 'approved', '2026-05-08 22:18:17'),
(14, 10, 'controller_rental', 'Controller rental fee', 20.00, 'approved', '2026-05-08 23:21:22');

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
(3, '2026-05-29', 'NAGALULU SI GSPOT', '2026-05-08 14:22:02');

-- --------------------------------------------------------

--
-- Table structure for table `consoles`
--

CREATE TABLE `consoles` (
  `console_id` int(11) NOT NULL,
  `console_name` varchar(50) NOT NULL,
  `unit_number` varchar(20) NOT NULL,
  `status` enum('available','in_use','maintenance','archived') NOT NULL DEFAULT 'available',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `console_type` varchar(255) DEFAULT NULL,
  `controller_count` int(11) DEFAULT NULL,
  `hourly_rate` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `consoles`
--

INSERT INTO `consoles` (`console_id`, `console_name`, `unit_number`, `status`, `created_at`, `console_type`, `controller_count`, `hourly_rate`) VALUES
(1, 'PS5 Unit 1', 'PS5-01', 'in_use', '2026-02-21 19:55:41', 'PS5', NULL, NULL),
(2, 'PS5 Unit 2', 'PS5-02', 'available', '2026-02-21 19:55:41', 'PS5', NULL, NULL),
(3, 'PS5 Unit 3', 'PS5-03', 'available', '2026-02-21 19:55:41', 'PS5', NULL, NULL),
(4, 'PS5 Unit 4', 'PS5-04', 'available', '2026-02-21 19:55:41', 'PS5', NULL, NULL),
(5, 'PS5 Unit 5', 'PS5-05', 'available', '2026-02-21 19:55:41', 'PS5', NULL, NULL),
(6, 'PS4 Unit 6', 'PS4-06', 'maintenance', '2026-02-21 19:55:41', 'PS4', NULL, NULL),
(7, 'Xbox Unit 7', 'XBX-07', 'available', '2026-02-21 19:55:41', 'Xbox Series X', NULL, NULL),
(8, 'Xbox Unit 8', 'XBX-08', 'available', '2026-02-21 19:55:41', 'Xbox Series X', NULL, NULL),
(3227, 'Xbox Unit 7', 'PS5-10', 'archived', '2026-05-08 19:45:19', 'AMD RADEON', NULL, NULL),
(3228, 'PS5 UNIT 10', 'PS5-11', 'available', '2026-05-08 19:45:28', 'NINTENDO', NULL, NULL),
(3229, 'TEST1', 'TEST1', 'archived', '2026-05-08 19:46:04', 'TEST1', NULL, NULL),
(3230, 'TEST2', 'TEST2', 'archived', '2026-05-08 19:46:20', 'TEST2', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `console_types`
--

CREATE TABLE `console_types` (
  `type_id` int(11) NOT NULL,
  `type_name` varchar(50) NOT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `console_types`
--

INSERT INTO `console_types` (`type_id`, `type_name`, `is_archived`, `created_at`) VALUES
(1, 'PS5', 0, '2026-05-08 04:10:19'),
(2, 'PS4', 0, '2026-05-08 04:10:19'),
(3, 'Xbox Series X', 0, '2026-05-08 04:10:19'),
(11, 'NINTENDO', 0, '2026-05-08 04:10:19'),
(12, 'PS6', 0, '2026-05-08 04:10:19'),
(105, 'AMD RADEON', 0, '2026-05-08 11:42:33'),
(106, 'TEST1', 0, '2026-05-08 11:45:43'),
(107, 'TEST2', 0, '2026-05-08 11:46:11');

-- --------------------------------------------------------

--
-- Table structure for table `controllers`
--

CREATE TABLE `controllers` (
  `controller_id` int(11) NOT NULL,
  `controller_name` varchar(100) NOT NULL,
  `unit_number` varchar(50) NOT NULL,
  `status` enum('available','in_use','maintenance','archived') NOT NULL DEFAULT 'available',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `console_type_id` int(11) DEFAULT NULL,
  `controller_type` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `controllers`
--

INSERT INTO `controllers` (`controller_id`, `controller_name`, `unit_number`, `status`, `notes`, `created_at`, `updated_at`, `console_type_id`, `controller_type`) VALUES
(1, 'Xbox Controller', 'CTRL-01', 'available', NULL, '2026-05-08 04:10:19', '2026-05-08 16:34:31', 15, 'Xbox Controller'),
(4, 'Xbox Controller', 'CTRL-02', 'available', NULL, '2026-05-08 04:10:19', '2026-05-08 16:34:31', 15, 'Xbox Controller'),
(13, 'ARCADE STICK', 'CTRL-03', 'available', NULL, '2026-05-08 04:10:19', '2026-05-08 16:34:31', 15, 'Xbox Controller'),
(14, 'NINTENDO', 'CTRL-04', 'available', NULL, '2026-05-08 04:10:19', '2026-05-08 16:34:31', 15, 'Xbox Controller'),
(18, 'joy con', 'CTRL-06', 'available', 'joy con', '2026-05-08 05:05:20', '2026-05-08 16:34:31', 101, 'joy con'),
(19, 'ps5 Controller', 'CTRL-07', 'available', '', '2026-05-08 06:14:28', '2026-05-08 16:34:31', 13, 'DualSense');

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
(102, 'PS5 CONTROLLER', 1, 0, '2026-05-08 14:16:18');

-- --------------------------------------------------------

--
-- Table structure for table `gaming_sessions`
--

CREATE TABLE `gaming_sessions` (
  `session_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `console_id` int(11) NOT NULL,
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
  `source_reservation_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gaming_sessions`
--

INSERT INTO `gaming_sessions` (`session_id`, `user_id`, `console_id`, `rental_mode`, `planned_minutes`, `start_time`, `end_time`, `duration_minutes`, `hourly_rate`, `total_cost`, `status`, `payment_status`, `created_by`, `created_at`, `extended_minutes`, `source_reservation_id`) VALUES
(1, 33, 8, 'hourly', 300, '2026-05-08 13:55:35', '2026-05-08 14:18:18', 22, 80.00, 40.00, 'completed', NULL, 12, '2026-05-08 13:55:35', 0, 35),
(2, 0, 3, 'unlimited', NULL, '2026-05-08 14:20:05', '2026-05-09 00:00:00', 579, 80.00, 400.00, 'completed', NULL, 12, '2026-05-08 14:20:05', 0, NULL),
(3, 0, 1, 'hourly', 30, '2026-05-08 14:25:55', '2026-05-08 15:43:00', 77, 80.00, 120.00, 'completed', NULL, 12, '2026-05-08 14:25:55', 0, NULL),
(4, 33, 2, 'hourly', 300, '2026-05-08 14:30:08', '2026-05-08 22:52:29', 502, 80.00, 560.00, 'completed', NULL, 12, '2026-05-08 14:30:08', 0, 36),
(5, 0, 5, 'hourly', 90, '2026-05-08 18:37:07', '2026-05-08 23:03:05', 265, 80.00, 320.00, 'completed', NULL, 12, '2026-05-08 18:37:07', 0, NULL),
(6, 0, 1, 'hourly', 480, '2026-05-08 19:24:16', '2026-05-08 19:57:14', 32, 80.00, 40.00, 'completed', NULL, 12, '2026-05-08 19:24:16', 240, NULL),
(7, 0, 2, 'hourly', 30, '2026-05-08 22:20:17', '2026-05-08 22:52:17', 32, 90.00, 80.00, 'completed', NULL, 12, '2026-05-08 22:18:17', 0, NULL),
(8, 33, 3228, 'hourly', 60, '2026-05-08 22:29:42', '2026-05-08 23:49:25', 79, 100.00, 100.00, 'completed', NULL, 12, '2026-05-08 22:29:42', 0, NULL),
(9, 0, 1, 'hourly', 30, '2026-05-08 23:20:34', NULL, NULL, 90.00, NULL, 'active', NULL, 35, '2026-05-08 23:20:34', 0, NULL),
(10, 26, 5, 'unlimited', NULL, '2026-05-08 23:21:22', '2026-05-09 00:00:00', 38, 90.00, 400.00, 'completed', NULL, 35, '2026-05-08 23:21:22', 0, NULL);

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
  `cancel_reason_detail` text DEFAULT NULL,
  `console_type` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`reservation_id`, `user_id`, `console_id`, `rental_mode`, `planned_minutes`, `reserved_date`, `reserved_time`, `notes`, `with_controller`, `controller_id`, `controller_fee`, `downpayment_amount`, `downpayment_method`, `downpayment_paid`, `payment_proof`, `payment_proof_status`, `paymongo_source_id`, `paymongo_payment_id`, `paymongo_status`, `status`, `created_by`, `created_at`, `updated_at`, `cancelled_by`, `cancellation_reason`, `refund_issued`, `cancel_reason_type`, `cancel_reason_detail`, `console_type`) VALUES
(34, 33, NULL, 'hourly', 240, '2026-05-22', '18:30:00', NULL, 0, NULL, 0.00, 34.00, 'gcash', 1, NULL, NULL, 'cs_928743a630058ce10ce5cc66', 'pay_DhnSXceBRGJDFqg38uxEXzPj', 'paid', 'cancelled', 33, '2026-05-08 12:01:01', '2026-05-09 00:34:31', 'user', NULL, 0, 'found_alternative', NULL, 'PS5'),
(35, 33, 7, 'hourly', 240, '2026-05-08', '13:55:00', NULL, 0, NULL, 0.00, 34.00, 'gcash', 1, NULL, NULL, 'cs_a01260486b81a2d7bb98fb40', 'pay_Be76ri2h4vWmgKQxuFSADYQk', 'paid', 'converted', 33, '2026-05-08 13:51:55', '2026-05-09 00:34:31', NULL, NULL, 0, NULL, NULL, 'Xbox Series X'),
(36, 33, 2, 'hourly', 240, '2026-05-08', '14:30:00', NULL, 0, NULL, 0.00, 34.00, 'gcash', 1, NULL, NULL, 'cs_25248cb3641ac3715f2a704e', 'pay_EttvLKi58zJ5WTcVYjKZJNRX', 'paid', 'converted', 33, '2026-05-08 14:24:45', '2026-05-09 00:34:31', NULL, NULL, 0, NULL, NULL, 'PS5'),
(37, 33, 4, 'unlimited', NULL, '2026-05-08', '16:00:00', NULL, 0, NULL, 0.00, 40.00, 'gcash', 1, NULL, NULL, 'cs_09a12d218a06eef4a27540fe', 'pay_GmjQcojsJJG5CwGjB8QfgmEc', 'paid', 'cancelled', 33, '2026-05-08 14:39:15', '2026-05-09 00:34:31', 'user', NULL, 0, 'found_alternative', NULL, 'PS5'),
(38, 33, 4, 'hourly', 240, '2026-05-12', '17:00:00', NULL, 0, NULL, 0.00, 34.00, 'gcash', 1, NULL, NULL, 'cs_2c901bc199227463ea604510', 'pay_sNqhcfovfktDdYSBoopq8yAN', 'paid', 'cancelled', 33, '2026-05-08 17:43:22', '2026-05-09 00:34:31', 'user', NULL, 0, NULL, NULL, 'PS5'),
(39, 33, 7, 'hourly', 150, '2026-05-12', '13:00:00', NULL, 0, NULL, 0.00, 28.00, 'gcash', 1, NULL, NULL, 'cs_22c5b45631b69b529079ea9f', 'pay_VD4iqJiqERsBxJCUv6TRiEzF', 'paid', 'reserved', 33, '2026-05-08 23:49:05', '2026-05-09 00:34:31', NULL, NULL, 0, NULL, NULL, 'Xbox Series X');

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
(20, 38, 33, 'user', 'found_alternative', NULL, 0, '2026-05-08 19:40:11');

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
  `new_date` date NOT NULL,
  `new_time` time NOT NULL,
  `console_id` int(11) DEFAULT NULL,
  `reason` enum('typhoon','power_outage','emergency','maintenance','user_request','other') NOT NULL DEFAULT 'other',
  `reason_detail` varchar(300) DEFAULT NULL,
  `rescheduled_by` int(11) NOT NULL,
  `initiated_by` enum('user','admin') NOT NULL DEFAULT 'admin',
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'approved',
  `seen_by_user` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `old_console_type` varchar(255) DEFAULT NULL,
  `console_type` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reservation_reschedules`
--

INSERT INTO `reservation_reschedules` (`reschedule_id`, `reservation_id`, `user_id`, `old_date`, `old_time`, `old_console_id`, `new_date`, `new_time`, `console_id`, `reason`, `reason_detail`, `rescheduled_by`, `initiated_by`, `status`, `seen_by_user`, `created_at`, `reviewed_by`, `reviewed_at`, `old_console_type`, `console_type`) VALUES
(1, 21, 32, '2026-05-06', '16:00:00', NULL, '2026-05-07', '17:00:00', NULL, 'other', 'Customer self-reschedule.', 32, 'admin', 'approved', 1, '2026-05-05 14:38:38', NULL, NULL, NULL, NULL),
(2, 21, 32, '2026-05-07', '17:00:00', NULL, '2026-05-07', '17:00:00', NULL, 'other', 'aalis kami sa wednesday', 12, 'admin', 'approved', 1, '2026-05-05 14:42:37', NULL, NULL, NULL, NULL),
(3, 21, 32, '2026-05-07', '17:00:00', NULL, '2026-05-08', '17:00:00', NULL, 'other', 'aalis kami sa wednesday', 12, 'admin', 'approved', 1, '2026-05-05 14:43:08', NULL, NULL, NULL, NULL),
(4, 23, 32, '2026-05-07', '13:30:00', NULL, '2026-05-08', '21:00:00', NULL, 'user_request', 'Customer self-reschedule request.', 32, 'user', 'approved', 1, '2026-05-05 20:18:25', NULL, NULL, NULL, NULL),
(5, 24, 33, '2026-05-07', '17:00:00', NULL, '2026-05-08', '17:00:00', NULL, 'typhoon', 'confirm mo to baka bigwasan kita', 12, 'admin', 'approved', 1, '2026-05-05 20:33:22', NULL, NULL, NULL, NULL),
(6, 23, 32, '2026-05-08', '21:00:00', NULL, '2026-05-09', '21:00:00', NULL, 'emergency', 'nagka emergency', 12, 'admin', 'approved', 1, '2026-05-07 07:28:34', NULL, NULL, NULL, NULL),
(7, 25, 26, '2026-05-08', '15:30:00', NULL, '2026-05-09', '12:00:00', NULL, 'user_request', 'Customer self-reschedule request.', 26, 'user', 'approved', 1, '2026-05-07 08:00:03', NULL, NULL, NULL, NULL),
(8, 26, 26, '2026-05-07', '09:15:00', NULL, '2026-05-07', '19:30:00', NULL, 'emergency', 'asdasdkashd', 12, 'admin', 'cancelled', 1, '2026-05-07 09:51:00', NULL, NULL, NULL, NULL),
(9, 28, 26, '2026-05-08', '12:30:00', 1, '2026-05-14', '19:00:00', NULL, 'user_request', 'Customer self-reschedule request.', 26, 'user', 'approved', 0, '2026-05-07 18:25:37', NULL, NULL, 'PS5', 'PS5'),
(10, 28, 26, '2026-05-14', '19:00:00', NULL, '2026-05-15', '19:00:00', NULL, 'maintenance', NULL, 12, 'admin', 'approved', 1, '2026-05-07 19:24:59', NULL, NULL, 'PS5', 'PS5'),
(11, 28, 26, '2026-05-15', '19:00:00', NULL, '2026-05-16', '19:00:00', 1, 'typhoon', NULL, 12, 'admin', 'approved', 1, '2026-05-07 20:10:46', NULL, NULL, 'PS5', 'PS5'),
(12, 29, 27, '2026-05-07', '22:00:00', 2, '2026-05-08', '22:00:00', NULL, 'typhoon', NULL, 12, 'admin', 'approved', 1, '2026-05-07 20:13:47', NULL, NULL, 'PS5', 'PS5'),
(13, 29, 27, '2026-05-08', '22:00:00', NULL, '2026-05-08', '22:00:00', NULL, 'emergency', NULL, 12, 'admin', 'approved', 1, '2026-05-07 20:17:15', NULL, NULL, 'PS5', 'Xbox Series X'),
(14, 29, 27, '2026-05-08', '22:00:00', NULL, '2026-05-09', '22:00:00', NULL, 'power_outage', NULL, 12, 'admin', 'approved', 1, '2026-05-07 21:08:11', NULL, NULL, 'Xbox Series X', 'Xbox Series X'),
(15, 29, 27, '2026-05-09', '22:00:00', NULL, '2026-05-10', '13:30:00', NULL, 'user_request', 'Customer self-reschedule request.', 27, 'user', 'rejected', 1, '2026-05-07 22:15:06', NULL, NULL, 'PS5', 'PS5'),
(16, 29, 27, '2026-05-09', '22:00:00', NULL, '2026-05-22', '22:00:00', NULL, 'maintenance', NULL, 12, 'admin', 'approved', 1, '2026-05-07 22:16:07', NULL, NULL, 'PS5', 'PS4'),
(17, 29, 27, '2026-05-22', '22:00:00', NULL, '2026-05-22', '22:00:00', NULL, 'power_outage', NULL, 12, 'admin', 'approved', 1, '2026-05-07 22:17:39', NULL, NULL, 'PS5', 'Xbox Series X'),
(18, 29, 27, '2026-05-22', '22:00:00', NULL, '2026-05-13', '22:00:00', NULL, 'typhoon', NULL, 12, 'admin', 'approved', 1, '2026-05-07 22:18:01', NULL, NULL, 'PS5', 'PS5'),
(19, 30, 27, '2026-05-08', '14:00:00', NULL, '2026-05-08', '14:00:00', 7, 'power_outage', NULL, 12, 'admin', 'approved', 1, '2026-05-07 22:20:04', NULL, NULL, 'PS5', 'Xbox Series X'),
(20, 31, 27, '2026-05-08', '18:30:00', 1, '2026-05-22', '15:30:00', NULL, 'user_request', 'Customer self-reschedule request.', 27, 'user', 'rejected', 1, '2026-05-07 22:22:48', NULL, NULL, 'PS5', 'PS5'),
(21, 31, 27, '2026-05-08', '18:30:00', 1, '2026-05-22', '12:00:00', NULL, 'user_request', 'Customer self-reschedule request.', 27, 'user', 'approved', 1, '2026-05-07 22:32:20', NULL, NULL, 'PS5', 'PS5'),
(22, 32, 27, '2026-05-08', '14:00:00', 1, '2026-05-08', '14:00:00', NULL, 'user_request', 'Customer self-reschedule request.', 27, 'user', 'approved', 1, '2026-05-07 22:34:59', NULL, NULL, 'PS5', 'PS5'),
(23, 33, 27, '2026-05-08', '17:00:00', 1, '2026-05-08', '17:00:00', NULL, 'user_request', 'Customer self-reschedule request.', 27, 'user', 'approved', 0, '2026-05-07 22:44:37', NULL, NULL, 'PS5', 'PS5'),
(24, 34, 33, '2026-05-15', '16:30:00', 1, '2026-05-22', '18:30:00', NULL, 'user_request', 'Customer self-reschedule request.', 33, 'user', 'approved', 1, '2026-05-08 12:22:21', NULL, NULL, 'PS5', 'PS5'),
(25, 35, 33, '2026-05-13', '13:30:00', 7, '2026-05-08', '13:55:00', NULL, 'user_request', 'Customer self-reschedule request.', 33, 'user', 'approved', 1, '2026-05-08 13:52:25', NULL, NULL, 'Xbox Series X', 'Xbox Series X'),
(26, 38, 33, '2026-05-08', '19:00:00', 4, '2026-05-10', '19:00:00', 1, 'user_request', 'wala po kasi ako sa may 9', 12, 'user', 'rejected', 1, '2026-05-08 17:44:34', NULL, NULL, 'PS5', 'PS5'),
(27, 38, 33, '2026-05-08', '19:00:00', 4, '2026-05-10', '19:00:00', 3, 'power_outage', NULL, 12, 'admin', 'approved', 1, '2026-05-08 17:45:51', NULL, NULL, 'PS5', 'PS5'),
(28, 38, 33, '2026-05-10', '19:00:00', 3, '2026-05-11', '19:00:00', 4, 'maintenance', 'sira', 12, 'admin', 'approved', 1, '2026-05-08 18:01:45', NULL, NULL, 'PS5', 'PS5'),
(29, 38, 33, '2026-05-11', '19:00:00', 4, '2026-05-12', '17:00:00', 4, 'user_request', 'Customer self-reschedule request.', 33, 'user', 'approved', 1, '2026-05-08 18:02:25', NULL, NULL, 'PS5', 'PS5');

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
(17, 6, 12, 12, 240, 320.00, 'cash', 'approved', 'Direct extension by staff', '2026-05-08 19:26:04', '2026-05-08 19:26:04');

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
(1, 'ps5_hourly_rate', '80', 'Default hourly rate for PS5 units in ₱', '2026-05-08 10:49:51'),
(2, 'xbox_hourly_rate', '80.00', 'Default hourly rate for Xbox Series X units in ₱', '2026-04-26 23:09:54'),
(3, 'unlimited_rate', '400.00', 'Rate for unlimited play (whole day) in ₱', '2026-04-14 09:56:44'),
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
(24, 'session_min_charge', '60', NULL, '2026-05-08 10:57:43'),
(152, 'gcash_number', '09XX-XXX-XXXX', 'Shop GCash number displayed on reservation payment screen', '2026-04-28 19:21:02'),
(164, 'shop_override_enabled', '0', NULL, '2026-05-06 19:55:24'),
(165, 'shop_override_closing_time', '', NULL, '2026-05-06 19:55:24'),
(166, 'contact_email', 'goodspotgaminghub@gmail.com', NULL, '2026-05-06 22:49:41'),
(179, 'smtp_host', '', NULL, '2026-05-06 22:57:45'),
(180, 'smtp_port', '587', NULL, '2026-05-06 22:57:45'),
(181, 'smtp_username', '', NULL, '2026-05-06 22:57:45'),
(182, 'smtp_password', '4th1ngss', NULL, '2026-05-06 22:57:45'),
(183, 'smtp_encryption', 'tls', NULL, '2026-05-06 22:57:45'),
(196, 'brevo_api_key', '', NULL, '2026-05-07 10:16:55'),
(197, 'sender_email', 'goodspotgaminghub@gmail.com', NULL, '2026-05-06 23:13:14');

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
(2, 'tikin 8', NULL, 'tikin et', 'PS5', '2026-05-14 14:30:00', '2026-05-14 16:00:00', 250.00, 10000.00, 16, 'cancelled', '', 12, '2026-05-07 09:22:00');

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

INSERT INTO `tournament_participants` (`participant_id`, `tournament_id`, `user_id`, `registration_date`, `payment_status`, `placement`, `prize_amount`, `ign`, `contact_number`, `gcash_proof`, `registered_by`, `walkin_name`, `notes`, `paymongo_source_id`, `paymongo_payment_id`, `paymongo_status`) VALUES
(1, 1, 27, '2026-05-02 15:14:51', 'paid', NULL, NULL, 'dark fist', '09916310227', NULL, NULL, NULL, 'note', 'src_Cymsv6YwZLgaJhwZhidpawe7', 'pay_3g5WGQgYiqiZwWXQP4vTS24U', 'paid'),
(2, 2, 33, '2026-05-07 09:23:15', 'paid', NULL, NULL, 'sibe', '0921931239', NULL, NULL, NULL, '', NULL, NULL, NULL),
(3, 2, 0, '2026-05-07 09:25:37', 'paid', NULL, NULL, 'Sugarcoat', '012931832123', NULL, 12, 'GSPOT', '', NULL, NULL, NULL);

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
(129, NULL, 33, 34.00, 34.00, NULL, 'Downpayment for reservation #38', 'gcash', 'completed', '2026-05-08 17:43:22', 33, '2026-05-08 17:43:22'),
(130, 3, 0, 120.00, 240.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-05-08 17:43:52', 12, '2026-05-08 17:43:52'),
(131, 4, 33, 226.00, 226.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-05-08 18:17:41', 12, '2026-05-08 18:17:41'),
(132, 5, 0, 60.00, 60.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-05-08 19:17:12', 12, '2026-05-08 19:17:12'),
(133, 6, 0, 0.00, 0.00, 320.00, 'Extension +240 min via staff (Extension #17)', 'cash', 'completed', '2026-05-08 19:26:04', 12, '2026-05-08 19:26:04'),
(135, 6, 0, 40.00, 40.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-05-08 19:57:14', 12, '2026-05-08 19:57:14'),
(139, 7, 0, 80.00, 80.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-05-08 22:52:17', 12, '2026-05-08 22:52:17'),
(140, 4, 33, 240.00, 240.00, 60.00, 'Short payment - collected ₱240.00, short by ₱60.00', 'cash', 'completed', '2026-05-08 22:52:29', 12, '2026-05-08 22:52:29'),
(141, 4, 33, 60.00, 60.00, NULL, 'Balance payment collected', 'cash', 'completed', '2026-05-08 22:52:35', 12, '2026-05-08 22:52:35'),
(142, 5, 0, 200.00, 200.00, 60.00, 'Short payment - collected ₱200.00, short by ₱60.00', 'cash', 'completed', '2026-05-08 23:03:05', 12, '2026-05-08 23:03:05'),
(143, 5, 0, 60.00, 60.00, NULL, 'Balance payment collected', 'cash', 'completed', '2026-05-08 23:03:32', 12, '2026-05-08 23:03:32'),
(144, 10, 26, 400.00, 400.00, 0.00, NULL, 'cash', 'completed', '2026-05-08 23:21:22', 35, '2026-05-08 23:21:22'),
(145, NULL, 33, 28.00, 28.00, NULL, 'Downpayment for reservation #39', 'gcash', 'completed', '2026-05-08 23:49:05', 33, '2026-05-08 23:49:05'),
(146, 8, 33, 100.00, 100.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-05-08 23:49:25', 35, '2026-05-08 23:49:25');

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
(27, 'helios@gmail.com', '$2y$10$R0HlhoPj53cgV9MwvalomuJxieeDE5JWQ4T7trpO.ttzu/U0rXJnm', 'helios', 'helios@gmail.com', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-04-27 23:29:14', 2, NULL),
(28, 'testcustomer@example.com', '$2y$10$kGbRHj87ratNFmTwi2pmTOqMaV2KV0fvB2H5sMYMsEzMfFVM4Uku2', 'Test Customer', '09123456789', 'customer', 'active', 0, '29333d3de426116e2f4f7d304d0b9f4b4b3b76feb6ee30a877d3d5457179f6e1', '2026-04-28 23:52:37', NULL, NULL, '2026-04-27 23:52:37', 0, NULL),
(29, 'test@example.com', '$2y$10$mLQD41zmi3vIoJ8sR8/Ntu6uZojYGjvzKbNb3Y.evmmQ3SyMB7evC', 'Test User', '09171234567', 'customer', 'active', 0, '0ae0598c1a671992ec26dfae71eabe5e2f9269bb1da1ae9f85c78695949d6184', '2026-04-30 20:29:21', NULL, NULL, '2026-04-29 20:29:21', 0, NULL),
(30, '09171234567test@gmail.com', '$2y$10$jyNy.ykur0jghOW.APBCwOpx.Y5fTLFYDdfTbek92i6AffjjIlcBq', 'test@gmail.comTest User', 'Password123!09171234', 'customer', 'active', 0, '6a296a2ad91537491af9a4309082ea6e25e0a818b60dd86e65ccc5e842ccd700', '2026-05-01 00:13:31', NULL, NULL, '2026-04-30 00:13:32', 0, NULL),
(31, 'mariejoy.12.mji@gmail.com', '$2y$10$VDFqBSZiRt9I1ZLIRaVBdOSQ0Red0aogx.VqXx1EMrGP.905x0bX2', 'MJ IGNACIO', '', 'customer', 'active', 0, 'd0e91ebfe1587c05cd41f92225bfa010276bb3e2c5c3ae0e7fefabd03a28c942', '2026-05-01 08:01:11', NULL, NULL, '2026-04-30 08:01:11', 0, NULL),
(32, 'harvieloresco2004@gmail.com', '$2y$10$5ra6ycYpFWsty4s.ikJESuw688uGiHUCKkXPlA8z44ZS9CzZnGU86', 'Harvie Loresco', '09944084214', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-05-05 14:34:15', 0, '2026-05-14 07:38:38'),
(33, 'sibeloresco@gmail.com', '$2y$10$th7lcBzspd8ES7d3oyIM7utZLr3u5r9zPJiCkIkpuWI44vi/ICxc6', 'Sibe Loresco', '09944084214', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-05-05 20:26:57', 1, NULL),
(35, 'kianpablo1996@gmail.com', '$2y$10$9HVf3fBPYcbdleA0/2fo8emj4MH52KuVtwHR1uXszxWT9yQJy/vo6', 'Kian Pablo', '09944084214', 'shopkeeper', 'active', 1, NULL, NULL, NULL, NULL, '2026-05-06 00:25:13', 0, NULL),
(37, 'zerotwo02darlingninelota@gmail.com', '$2y$10$Q72iGgv0rWvD6I9OaSk6Iuq7BxVJY5Tmw2HuYCH.kGlLT6tSVqNom', 'Gelo', '09123455666', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-05-07 09:01:18', 0, NULL),
(38, 'unique_tester_99@example.com', '$2y$10$KlQu2AzHZNth7OfEOAY5T.iRsiw9FsKR9MAkplksBodg/vMp84vny', 'Test User', '', 'customer', 'active', 0, 'c2d332cbbbde9a33cc7e290f45c9ce3acaa51ce1bb1d9e9836f5c2504f018794', '2026-05-08 13:02:28', NULL, NULL, '2026-05-07 13:02:28', 0, NULL),
(40, 'notribbie0@gmail.com', '$2y$10$sWea1ry/llho6m8b5s7qCeN1A/jqfeOmDwX.icUkYC7rJHEF.gYL2', 'Gelo', '09944084214', 'customer', 'active', 0, '6808a3a05fa16f91b2026817302f94e6f4911197d6d82a34a46a562e63e04250', '2026-05-09 19:58:16', NULL, NULL, '2026-05-08 19:58:16', 0, NULL),
(41, 'bernadittealmoguerra@gmail.com', '$2y$10$tf8LuMS4fG2kx5CW51hpmu3q5nTPMeOZzgdehdb17/FSAGJ//zmpS', 'bernadit', '', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-05-08 20:01:41', 0, NULL);

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
  ADD KEY `idx_consoles_status` (`status`);

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
  ADD KEY `fk_controllers_ctrlt` (`console_type_id`);

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
  ADD KEY `fk_res_controller` (`controller_id`);

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
  ADD KEY `idx_rr_seen` (`seen_by_user`);

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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `additional_requests`
--
ALTER TABLE `additional_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `blocked_dates`
--
ALTER TABLE `blocked_dates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `consoles`
--
ALTER TABLE `consoles`
  MODIFY `console_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3231;

--
-- AUTO_INCREMENT for table `console_types`
--
ALTER TABLE `console_types`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT for table `controllers`
--
ALTER TABLE `controllers`
  MODIFY `controller_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `controller_types`
--
ALTER TABLE `controller_types`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT for table `gaming_sessions`
--
ALTER TABLE `gaming_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `reservation_cancellations`
--
ALTER TABLE `reservation_cancellations`
  MODIFY `cancel_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `reservation_reschedules`
--
ALTER TABLE `reservation_reschedules`
  MODIFY `reschedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `session_extensions`
--
ALTER TABLE `session_extensions`
  MODIFY `extension_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=366;

--
-- AUTO_INCREMENT for table `tournaments`
--
ALTER TABLE `tournaments`
  MODIFY `tournament_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tournament_participants`
--
ALTER TABLE `tournament_participants`
  MODIFY `participant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=147;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `additional_requests`
--
ALTER TABLE `additional_requests`
  ADD CONSTRAINT `fk_addreq_session` FOREIGN KEY (`session_id`) REFERENCES `gaming_sessions` (`session_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `controllers`
--
ALTER TABLE `controllers`
  ADD CONSTRAINT `fk_controllers_ctrlt` FOREIGN KEY (`console_type_id`) REFERENCES `controller_types` (`type_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `gaming_sessions`
--
ALTER TABLE `gaming_sessions`
  ADD CONSTRAINT `fk_sessions_console` FOREIGN KEY (`console_id`) REFERENCES `consoles` (`console_id`) ON UPDATE CASCADE,
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
  ADD CONSTRAINT `fk_res_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `reservation_cancellations`
--
ALTER TABLE `reservation_cancellations`
  ADD CONSTRAINT `fk_rc_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

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
