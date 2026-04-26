-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
<<<<<<< HEAD
-- Generation Time: Apr 26, 2026 at 10:34 AM
=======
-- Generation Time: Apr 25, 2026 at 06:12 PM
>>>>>>> main
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

-- --------------------------------------------------------

--
-- Table structure for table `consoles`
--

CREATE TABLE `consoles` (
  `console_id` int(11) NOT NULL,
  `console_name` varchar(50) NOT NULL,
  `console_type` enum('PS5','PS4','Xbox Series X') NOT NULL,
  `unit_number` varchar(10) NOT NULL,
  `status` enum('available','in_use','maintenance') NOT NULL DEFAULT 'available',
  `hourly_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `consoles`
--

INSERT INTO `consoles` (`console_id`, `console_name`, `console_type`, `unit_number`, `status`, `hourly_rate`, `created_at`) VALUES
(1, 'PS5 Unit 1', 'PS5', 'PS5-01', 'available', 80.00, '2026-02-21 19:55:41'),
(2, 'PS5 Unit 2', 'PS5', 'PS5-02', 'in_use', 80.00, '2026-02-21 19:55:41'),
(3, 'PS5 Unit 3', 'PS5', 'PS5-03', 'available', 80.00, '2026-02-21 19:55:41'),
(4, 'PS5 Unit 4', 'PS5', 'PS5-04', 'available', 80.00, '2026-02-21 19:55:41'),
(5, 'PS5 Unit 5', 'PS5', 'PS5-05', 'available', 80.00, '2026-02-21 19:55:41'),
(6, 'PS4 Unit 6', 'PS4', 'PS4-07', 'maintenance', 80.00, '2026-02-21 19:55:41'),
(7, 'Xbox Unit 2', 'Xbox Series X', 'XBX-02', 'available', 80.00, '2026-02-21 19:55:41'),
(8, 'Xbox Unit 3', 'Xbox Series X', 'XBX-03', 'available', 80.00, '2026-02-21 19:55:41');

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
  `extended_minutes` int(11) NOT NULL DEFAULT 0 COMMENT 'Total minutes added via approved extensions'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gaming_sessions`
--

INSERT INTO `gaming_sessions` (`session_id`, `user_id`, `console_id`, `rental_mode`, `planned_minutes`, `start_time`, `end_time`, `duration_minutes`, `hourly_rate`, `total_cost`, `status`, `payment_status`, `created_by`, `created_at`, `extended_minutes`) VALUES
(47, 21, 1, 'hourly', 420, '2026-04-19 22:33:23', '2026-04-19 22:33:38', 0, 80.00, 560.00, 'completed', NULL, 12, '2026-04-19 22:33:23', 0),
(48, 21, 2, 'hourly', 210, '2026-04-20 15:10:41', '2026-04-20 19:23:00', 252, 80.00, 340.00, 'completed', NULL, 12, '2026-04-20 15:10:41', 0),
(49, 17, 1, 'hourly', 330, '2026-04-21 08:38:56', '2026-04-21 11:42:00', 183, 80.00, 440.00, 'completed', NULL, 12, '2026-04-21 08:38:56', 0),
(50, 17, 2, 'hourly', 90, '2026-04-21 08:39:58', '2026-04-25 18:34:10', 6354, 80.00, 6840.00, 'completed', NULL, 12, '2026-04-21 08:39:58', 0),
(51, 12, 1, 'hourly', 150, '2026-04-25 21:16:37', '2026-04-25 21:45:54', 29, 80.00, 200.00, 'completed', NULL, 12, '2026-04-25 21:16:37', 0),
(52, 17, 2, 'hourly', 450, '2026-04-25 21:54:35', '2026-04-25 22:51:10', 57, 80.00, 600.00, 'completed', NULL, 12, '2026-04-25 21:54:35', 300),
(53, 21, 1, 'hourly', 240, '2026-04-25 23:19:33', '2026-04-25 23:36:05', 17, 80.00, 320.00, 'completed', NULL, 12, '2026-04-25 23:19:33', 0),
(54, 21, 2, 'hourly', 240, '2026-04-25 23:21:55', '2026-04-25 23:35:59', 14, 80.00, 320.00, 'completed', NULL, 12, '2026-04-25 23:21:55', 0),
(55, 17, 2, 'hourly', 300, '2026-04-26 00:04:14', '2026-04-26 00:06:09', 2, 80.00, 400.00, 'completed', NULL, 12, '2026-04-26 00:04:14', 180),
(56, 17, 3, 'hourly', 300, '2026-04-26 00:06:01', '2026-04-26 00:06:26', 0, 80.00, 400.00, 'completed', NULL, 12, '2026-04-26 00:06:01', 0),
<<<<<<< HEAD
(57, 21, 2, 'hourly', 300, '2026-04-26 00:09:10', '2026-04-26 09:43:17', 574, 80.00, 560.00, 'completed', NULL, 12, '2026-04-26 00:09:10', 0);
=======
(57, 21, 2, 'hourly', 300, '2026-04-26 00:09:10', NULL, NULL, 80.00, NULL, 'active', NULL, 12, '2026-04-26 00:09:10', 0);
>>>>>>> main

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
  `console_type` enum('PS5','Xbox Series X','PS4') NOT NULL,
  `rental_mode` enum('hourly','open_time','unlimited') NOT NULL DEFAULT 'hourly',
  `planned_minutes` int(11) DEFAULT NULL,
  `reserved_date` date NOT NULL,
  `reserved_time` time NOT NULL,
  `notes` text DEFAULT NULL,
  `downpayment_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `downpayment_method` enum('cash','gcash','credit_card') DEFAULT NULL,
  `downpayment_paid` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('pending','confirmed','converted','cancelled','no_show') NOT NULL DEFAULT 'pending',
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cancelled_by` enum('user','admin') DEFAULT NULL,
  `refund_issued` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`reservation_id`, `user_id`, `console_id`, `console_type`, `rental_mode`, `planned_minutes`, `reserved_date`, `reserved_time`, `notes`, `downpayment_amount`, `downpayment_method`, `downpayment_paid`, `status`, `created_by`, `created_at`, `updated_at`, `cancelled_by`, `refund_issued`) VALUES
(42, 21, NULL, 'PS5', 'hourly', 420, '2026-04-19', '23:30:00', NULL, 560.00, 'cash', 1, 'cancelled', 21, '2026-04-19 22:29:56', '2026-04-19 22:30:28', 'user', 1),
(43, 21, NULL, 'PS5', 'hourly', 240, '2026-04-19', '23:37:00', NULL, 320.00, 'cash', 1, 'cancelled', 21, '2026-04-19 22:36:37', '2026-04-19 22:37:55', 'user', 1),
(44, 21, NULL, 'PS5', 'hourly', 180, '2026-04-19', '23:40:00', NULL, 240.00, 'cash', 1, 'cancelled', 21, '2026-04-19 22:38:22', '2026-04-19 22:39:24', 'user', 1),
(45, 21, NULL, 'PS5', 'hourly', 150, '2026-05-07', '13:23:00', 'stellar blade best game', 200.00, 'cash', 1, 'no_show', 21, '2026-04-20 09:25:47', '2026-04-20 09:29:22', NULL, 0),
(46, 21, NULL, 'PS5', 'hourly', 150, '2026-04-20', '12:00:00', NULL, 200.00, 'cash', 1, 'cancelled', 21, '2026-04-20 09:32:58', '2026-04-20 09:34:00', 'user', 1),
(47, 12, NULL, 'PS5', 'hourly', 120, '2026-04-20', '12:00:00', NULL, 160.00, 'gcash', 1, 'cancelled', 12, '2026-04-20 09:35:36', '2026-04-20 09:41:24', 'admin', 0),
(48, 12, NULL, 'PS5', 'hourly', 360, '2026-04-21', '14:00:00', NULL, 480.00, 'cash', 1, 'cancelled', 12, '2026-04-21 08:06:27', '2026-04-21 08:10:25', 'admin', 0),
(49, 21, NULL, 'PS4', 'hourly', 60, '2026-04-21', '12:00:00', NULL, 80.00, 'cash', 1, 'cancelled', 21, '2026-04-21 08:13:48', '2026-04-21 08:17:03', 'user', 1),
(50, 21, NULL, 'PS5', 'hourly', 180, '2026-04-21', '12:00:00', NULL, 240.00, 'cash', 1, 'cancelled', 21, '2026-04-21 08:14:54', '2026-04-21 08:36:55', 'user', 1),
(51, 21, NULL, 'PS5', 'hourly', 150, '2026-04-21', '20:33:00', NULL, 200.00, 'cash', 1, 'cancelled', 21, '2026-04-21 08:34:14', '2026-04-21 08:36:45', 'user', 1),
(52, 21, NULL, 'PS5', 'hourly', 240, '2026-04-21', '12:00:00', NULL, 320.00, 'cash', 1, 'cancelled', 21, '2026-04-21 08:37:53', '2026-04-25 23:21:33', 'user', 1),
(53, 12, 1, 'PS5', 'hourly', 150, '2026-04-25', '23:11:00', NULL, 200.00, 'cash', 1, 'converted', 12, '2026-04-25 21:16:09', '2026-04-25 21:16:37', NULL, 0),
(54, 21, 1, 'PS5', 'hourly', 240, '2026-04-26', '12:00:00', NULL, 320.00, 'cash', 1, 'converted', 21, '2026-04-25 23:18:00', '2026-04-25 23:19:33', NULL, 0),
(55, 21, 2, 'PS5', 'hourly', 240, '2026-04-26', '12:00:00', NULL, 320.00, 'cash', 1, 'converted', 21, '2026-04-25 23:20:56', '2026-04-25 23:21:55', NULL, 0),
(56, 21, 2, 'PS5', 'hourly', 240, '2026-04-26', '12:00:00', NULL, 160.00, 'cash', 1, 'converted', 21, '2026-04-26 00:08:48', '2026-04-26 00:09:10', NULL, 0);

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
(1, 52, 12, 12, 60, 80.00, 'cash', 'approved', 'Direct extension by staff', '2026-04-25 22:12:44', '2026-04-25 22:12:44'),
(2, 52, 12, 12, 30, 40.00, 'cash', 'approved', 'Direct extension by staff', '2026-04-25 22:14:38', '2026-04-25 22:14:38'),
(3, 52, 12, 12, 90, 120.00, 'cash', 'approved', 'Direct extension by staff', '2026-04-25 22:31:28', '2026-04-25 22:31:28'),
(4, 52, 12, 12, 60, 80.00, 'cash', 'approved', 'Direct extension by staff', '2026-04-25 22:31:50', '2026-04-25 22:31:50'),
(5, 52, 12, 12, 60, 80.00, 'cash', 'approved', 'Direct extension by staff', '2026-04-25 22:32:06', '2026-04-25 22:32:06'),
(6, 55, 12, 12, 60, 80.00, 'cash', 'approved', 'Direct extension by staff', '2026-04-26 00:04:27', '2026-04-26 00:04:27'),
(7, 55, 12, 12, 120, 160.00, 'cash', 'approved', 'Direct extension by staff', '2026-04-26 00:04:35', '2026-04-26 00:04:35');

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
(1, 'ps5_hourly_rate', '40.00', 'Default hourly rate for PS5 units in ₱', '2026-04-25 23:37:07'),
(2, 'xbox_hourly_rate', '40.00', 'Default hourly rate for Xbox Series X units in ₱', '2026-04-25 23:36:58'),
(3, 'unlimited_rate', '400.00', 'Rate for unlimited play (whole day) in ₱', '2026-04-14 09:56:44'),
(4, 'controller_rental_fee', '20.00', 'Additional controller rental fee in ₱', '2026-02-21 19:55:41'),
(5, 'business_hours_open', '12:00', 'Shop opening time', '2026-04-14 09:56:44'),
(6, 'business_hours_close', '00:00', 'Shop closing time', '2026-04-14 09:56:44'),
(7, 'shop_name', 'Good Spot Gaming Hub', 'Shop name', '2026-02-21 19:55:41'),
(8, 'shop_address', 'Don Placido Avenue, Dasmariñas, Cavite', 'Shop address', '2026-02-21 19:55:41'),
(9, 'shop_phone', '09171234567', 'Shop contact number', '2026-02-21 19:55:41'),
(10, 'tournament_default_fee', '100.00', 'Default tournament entry fee in ₱', '2026-02-21 19:55:41'),
(11, 'bonus_paid_minutes', '120', 'Every X paid minutes earns free bonus time', '2026-04-25 23:36:13'),
(12, 'bonus_free_minutes', '30', 'Free minutes awarded per bonus cycle', '2026-04-25 23:36:13'),
(13, 'max_hourly_minutes', '240', 'Maximum bookable paid minutes for hourly session', '2026-04-25 23:36:13');

-- --------------------------------------------------------

--
-- Table structure for table `tournaments`
--

CREATE TABLE `tournaments` (
  `tournament_id` int(11) NOT NULL,
  `tournament_name` varchar(150) NOT NULL,
  `game_id` int(11) NOT NULL,
  `console_type` enum('PS5','Xbox Series X') NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `entry_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `prize_pool` decimal(10,2) NOT NULL DEFAULT 0.00,
  `max_participants` int(11) NOT NULL DEFAULT 16,
  `status` enum('upcoming','ongoing','completed','cancelled') NOT NULL DEFAULT 'upcoming',
  `announcement` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `prize_amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(165, NULL, 21, -560.00, NULL, NULL, 'Refund for cancelled reservation #42', 'cash', 'completed', '2026-04-19 22:30:28', 12, '2026-04-19 22:30:28'),
(166, 47, 21, 200.00, 200.00, 360.00, 'Short payment at session start — short by ₱360.00', 'cash', 'completed', '2026-04-19 22:33:23', 12, '2026-04-19 22:33:23'),
(167, 47, 21, -200.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 00m (₱0.00), refunding unused time (₱200.00)', '', 'completed', '2026-04-19 22:33:38', 12, '2026-04-19 22:33:38'),
(168, NULL, 21, 320.00, 320.00, NULL, 'Downpayment for reservation #43', 'cash', 'completed', '2026-04-19 22:36:37', 21, '2026-04-19 22:36:37'),
(169, 47, 21, 560.00, 560.00, NULL, 'Balance payment collected', 'cash', 'completed', '2026-04-19 22:36:57', 12, '2026-04-19 22:36:57'),
(170, NULL, 21, -320.00, NULL, NULL, 'Refund for cancelled reservation #43', 'cash', 'completed', '2026-04-19 22:37:55', 12, '2026-04-19 22:37:55'),
(171, NULL, 21, 240.00, 240.00, NULL, 'Downpayment for reservation #44', 'cash', 'completed', '2026-04-19 22:38:22', 21, '2026-04-19 22:38:22'),
(172, NULL, 21, -240.00, NULL, NULL, 'Refund for cancelled reservation #44', 'cash', 'completed', '2026-04-19 22:39:24', 12, '2026-04-19 22:39:24'),
(173, NULL, 21, 200.00, 200.00, NULL, 'Downpayment for reservation #45', 'cash', 'completed', '2026-04-20 09:25:47', 21, '2026-04-20 09:25:47'),
(174, NULL, 21, 200.00, 200.00, NULL, 'Downpayment for reservation #46', 'cash', 'completed', '2026-04-20 09:32:58', 21, '2026-04-20 09:32:58'),
(175, NULL, 21, -200.00, NULL, NULL, 'Refund for cancelled reservation #46', 'cash', 'completed', '2026-04-20 09:34:00', 12, '2026-04-20 09:34:00'),
(176, NULL, 12, 160.00, 160.00, NULL, 'Downpayment for reservation #47', 'gcash', 'completed', '2026-04-20 09:35:36', 12, '2026-04-20 09:35:36'),
(177, 48, 21, 280.00, 280.00, NULL, NULL, 'cash', 'completed', '2026-04-20 15:10:41', 12, '2026-04-20 15:10:41'),
(178, 48, 21, -260.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 12m (₱20.00), refunding unused time (₱260.00)', '', 'completed', '2026-04-20 15:23:13', 12, '2026-04-20 15:23:13'),
(179, 48, 21, 320.00, 260.00, NULL, 'Balance payment collected', 'cash', 'completed', '2026-04-20 15:23:23', 12, '2026-04-20 15:23:23'),
(180, NULL, 12, 480.00, 480.00, NULL, 'Downpayment for reservation #48', 'cash', 'completed', '2026-04-21 08:06:27', 12, '2026-04-21 08:06:27'),
(181, NULL, 21, 80.00, 80.00, NULL, 'Downpayment for reservation #49', 'cash', 'completed', '2026-04-21 08:13:48', 21, '2026-04-21 08:13:48'),
(182, NULL, 21, 240.00, 240.00, NULL, 'Downpayment for reservation #50', 'cash', 'completed', '2026-04-21 08:14:54', 21, '2026-04-21 08:14:54'),
(183, NULL, 21, -80.00, NULL, NULL, 'Refund for cancelled reservation #49', 'cash', 'completed', '2026-04-21 08:17:03', 12, '2026-04-21 08:17:03'),
(184, NULL, 21, 200.00, 200.00, NULL, 'Downpayment for reservation #51', 'cash', 'completed', '2026-04-21 08:34:14', 21, '2026-04-21 08:34:14'),
(185, NULL, 21, -200.00, NULL, NULL, 'Refund for cancelled reservation #51', 'cash', 'completed', '2026-04-21 08:36:45', 12, '2026-04-21 08:36:45'),
(186, NULL, 21, -240.00, NULL, NULL, 'Refund for cancelled reservation #50', 'cash', 'completed', '2026-04-21 08:36:55', 12, '2026-04-21 08:36:55'),
(187, NULL, 21, 320.00, 320.00, NULL, 'Downpayment for reservation #52', 'cash', 'completed', '2026-04-21 08:37:53', 21, '2026-04-21 08:37:53'),
(188, 49, 17, 200.00, 200.00, 240.00, 'Short payment at session start — short by ₱240.00', 'cash', 'completed', '2026-04-21 08:38:56', 12, '2026-04-21 08:38:56'),
(189, 50, 17, 0.00, 0.00, 120.00, 'Short payment at session start — short by ₱120.00', 'cash', 'completed', '2026-04-21 08:39:58', 12, '2026-04-21 08:39:58'),
(190, 49, 17, -200.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 03m (₱0.00), refunding unused time (₱200.00)', '', 'completed', '2026-04-21 08:42:12', 12, '2026-04-21 08:42:12'),
(191, 49, 17, 440.00, 440.00, NULL, 'Balance payment collected', 'cash', 'completed', '2026-04-21 08:42:26', 12, '2026-04-21 08:42:26'),
(192, 50, 17, 6840.00, 6840.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-04-25 18:34:10', 12, '2026-04-25 18:34:10'),
(193, NULL, 12, 200.00, 200.00, NULL, 'Downpayment for reservation #53', 'cash', 'completed', '2026-04-25 21:16:09', 12, '2026-04-25 21:16:09'),
(194, 51, 12, 200.00, 200.00, NULL, 'Downpayment transferred from reservation #53', 'cash', 'completed', '2026-04-25 21:16:37', 12, '2026-04-25 21:16:37'),
(195, 51, 12, -160.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 29m (₱40.00), refunding unused time (₱160.00)', '', 'completed', '2026-04-25 21:45:54', 12, '2026-04-25 21:45:54'),
(196, 51, 12, 160.00, 160.00, NULL, 'Balance payment collected', 'cash', 'completed', '2026-04-25 21:52:41', 12, '2026-04-25 21:52:41'),
(197, 52, 17, 200.00, 200.00, NULL, NULL, 'cash', 'completed', '2026-04-25 21:54:35', 12, '2026-04-25 21:54:35'),
(198, 52, 17, 80.00, 80.00, NULL, 'Extension +60 min via staff (Extension #1)', 'cash', 'completed', '2026-04-25 22:12:44', 12, '2026-04-25 22:12:44'),
(199, 52, 17, 40.00, 80.00, NULL, 'Extension +30 min via staff (Extension #2)', 'cash', 'completed', '2026-04-25 22:14:38', 12, '2026-04-25 22:14:38'),
(200, 52, 17, 110.00, 110.00, 10.00, 'Extension +90 min via staff (Extension #3)', 'cash', 'completed', '2026-04-25 22:31:28', 12, '2026-04-25 22:31:28'),
(201, 52, 17, 10.00, 10.00, NULL, 'Balance payment collected', 'cash', 'completed', '2026-04-25 22:31:35', 12, '2026-04-25 22:31:35'),
(202, 52, 17, 80.00, 80.00, NULL, 'Extension +60 min via staff (Extension #4)', 'cash', 'completed', '2026-04-25 22:31:50', 12, '2026-04-25 22:31:50'),
(203, 52, 17, 80.00, 80.00, NULL, 'Extension +60 min via staff (Extension #5)', 'cash', 'completed', '2026-04-25 22:32:06', 12, '2026-04-25 22:32:06'),
(204, 52, 17, -520.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 56m (₱80.00), refunding unused time (₱520.00)', '', 'completed', '2026-04-25 22:51:10', 12, '2026-04-25 22:51:10'),
(205, NULL, 21, 320.00, 320.00, NULL, 'Downpayment for reservation #54', 'cash', 'completed', '2026-04-25 23:18:00', 21, '2026-04-25 23:18:00'),
(206, 53, 21, 320.00, 320.00, NULL, 'Downpayment transferred from reservation #54', 'cash', 'completed', '2026-04-25 23:19:33', 12, '2026-04-25 23:19:33'),
(207, NULL, 21, 320.00, 320.00, NULL, 'Downpayment for reservation #55', 'cash', 'completed', '2026-04-25 23:20:56', 21, '2026-04-25 23:20:56'),
(208, NULL, 21, -320.00, NULL, NULL, 'Refund for cancelled reservation #52', 'cash', 'completed', '2026-04-25 23:21:33', 12, '2026-04-25 23:21:33'),
(209, 54, 21, 320.00, 320.00, NULL, 'Downpayment transferred from reservation #55', 'cash', 'completed', '2026-04-25 23:21:55', 12, '2026-04-25 23:21:55'),
(210, 54, 21, -300.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 14m (₱20.00), refunding unused time (₱300.00)', '', 'completed', '2026-04-25 23:35:59', 12, '2026-04-25 23:35:59'),
(211, 53, 21, -300.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 16m (₱20.00), refunding unused time (₱300.00)', '', 'completed', '2026-04-25 23:36:05', 12, '2026-04-25 23:36:05'),
(212, 55, 17, 80.00, 200.00, NULL, NULL, 'cash', 'completed', '2026-04-26 00:04:14', 12, '2026-04-26 00:04:14'),
(213, 55, 17, 80.00, NULL, NULL, 'Extension +60 min via staff (Extension #6)', 'cash', 'completed', '2026-04-26 00:04:27', 12, '2026-04-26 00:04:27'),
(214, 55, 17, 160.00, NULL, NULL, 'Extension +120 min via staff (Extension #7)', 'cash', 'completed', '2026-04-26 00:04:35', 12, '2026-04-26 00:04:35'),
(215, 56, 17, 200.00, 200.00, 120.00, 'Short payment at session start — short by ₱120.00', 'cash', 'completed', '2026-04-26 00:06:01', 12, '2026-04-26 00:06:01'),
(216, 55, 17, -320.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 01m (₱0.00), refunding unused time (₱320.00)', '', 'completed', '2026-04-26 00:06:09', 12, '2026-04-26 00:06:09'),
(217, 56, 17, -200.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 00m (₱0.00), refunding unused time (₱200.00)', '', 'completed', '2026-04-26 00:06:26', 12, '2026-04-26 00:06:26'),
(218, NULL, 21, 160.00, 160.00, NULL, 'Downpayment for reservation #56', 'cash', 'completed', '2026-04-26 00:08:48', 21, '2026-04-26 00:08:48'),
<<<<<<< HEAD
(219, 57, 21, 160.00, 160.00, NULL, 'Downpayment transferred from reservation #56', 'cash', 'completed', '2026-04-26 00:09:10', 12, '2026-04-26 00:09:10'),
(220, 57, 21, 200.00, 200.00, 200.00, 'Short payment — collected ₱200.00, short by ₱200.00', 'cash', 'completed', '2026-04-26 09:43:17', 12, '2026-04-26 09:43:17');
=======
(219, 57, 21, 160.00, 160.00, NULL, 'Downpayment transferred from reservation #56', 'cash', 'completed', '2026-04-26 00:09:10', 12, '2026-04-26 00:09:10');
>>>>>>> main

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
  `role` enum('customer','shopkeeper','owner') NOT NULL DEFAULT 'customer',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_token` varchar(64) DEFAULT NULL,
  `verification_expires` datetime DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password_hash`, `full_name`, `phone`, `role`, `status`, `email_verified`, `verification_token`, `verification_expires`, `reset_token`, `reset_expires`, `created_at`) VALUES
(12, 'ljlabianao@gmail.com', '$2y$10$nEuBy0VoWqtbRETOnAD99eurxRHdLaGpxROWA//NGpziKgIG1CmmS', 'lito', '09916310227', 'shopkeeper', 'active', 1, NULL, NULL, NULL, NULL, '2026-02-21 20:45:06'),
(17, 'llabianojr@kld.edu.ph', '$2y$10$N97t4MFGjElD3iM.nlpn3uZ/hy1zNC/Lfqi/YozlBB6BG1VWkL.Oe', 'Lito LARGUEZA LABIANO', '09916310227', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-04-12 19:54:25'),
(21, 'hello@gmail.com', '$2y$10$vjAu848ZbS1DpfexUIsM1.x31O/VNLXNwp/sw65NckgmZTHTKLzAO', 'Lito LARGUEZA LABIANO', '09916310227', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-04-17 11:59:29'),
(22, 'admin@gmail.com', '$2y$10$kZCF21cxNKIxXE99XNGyF.nGfT94FZeDaGtbH4YTBOxrpumQLDvIK', 'Admin User', '', 'customer', 'active', 0, '9ad4e0d4b2004d75a763bd2e6e9af695cdb053e39c7cadd65db419783c75b54f', '2026-04-20 16:02:24', NULL, NULL, '2026-04-19 16:02:24'),
(23, 'customer@example.com', '$2y$10$8mmAlQ1UknZorTRMn6NVneRRA5JrVSOO8oMAuDEVll.pICMikYieO', 'Test Customer', '', 'customer', 'active', 0, '5337f25a858a79327c9001f982e6629f2f64be6579986d9da00c816159a6a75c', '2026-04-20 20:49:05', NULL, NULL, '2026-04-19 20:49:05'),
(24, 'lito@example.com', '$2y$10$b1z6jAajQr5NjOyHWkU5kOJ7kx/BdeM6HbUg/qLWdElsilx20bLta', 'Lito Test', '', 'customer', 'active', 0, '10512cc414ac02ff1b961698a17863ad87e3e55d976dc1e14d92a2af161ee3bd', '2026-04-20 20:52:01', NULL, NULL, '2026-04-19 20:52:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `additional_requests`
--
ALTER TABLE `additional_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `idx_addreq_session` (`session_id`),
  ADD KEY `idx_addreq_status` (`status`);

--
-- Indexes for table `consoles`
--
ALTER TABLE `consoles`
  ADD PRIMARY KEY (`console_id`),
  ADD UNIQUE KEY `unit_number` (`unit_number`),
  ADD KEY `idx_consoles_status` (`status`),
  ADD KEY `idx_consoles_type` (`console_type`);

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
  ADD KEY `idx_sessions_pay_status` (`payment_status`);

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
  ADD KEY `idx_res_console_type` (`console_type`),
  ADD KEY `fk_res_created_by` (`created_by`);

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
  ADD UNIQUE KEY `uk_tp_entry` (`tournament_id`,`user_id`),
  ADD KEY `idx_tp_tournament` (`tournament_id`),
  ADD KEY `idx_tp_user` (`user_id`),
  ADD KEY `idx_tp_payment` (`payment_status`);

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
-- AUTO_INCREMENT for table `additional_requests`
--
ALTER TABLE `additional_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `consoles`
--
ALTER TABLE `consoles`
  MODIFY `console_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3212;

--
-- AUTO_INCREMENT for table `gaming_sessions`
--
ALTER TABLE `gaming_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `session_extensions`
--
ALTER TABLE `session_extensions`
  MODIFY `extension_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `tournaments`
--
ALTER TABLE `tournaments`
  MODIFY `tournament_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tournament_participants`
--
ALTER TABLE `tournament_participants`
  MODIFY `participant_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
<<<<<<< HEAD
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=221;
=======
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=220;
>>>>>>> main

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `additional_requests`
--
ALTER TABLE `additional_requests`
  ADD CONSTRAINT `fk_addreq_session` FOREIGN KEY (`session_id`) REFERENCES `gaming_sessions` (`session_id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
  ADD CONSTRAINT `fk_res_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_res_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `session_extensions`
--
ALTER TABLE `session_extensions`
  ADD CONSTRAINT `fk_ext_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ext_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ext_session` FOREIGN KEY (`session_id`) REFERENCES `gaming_sessions` (`session_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tournament_participants`
--
ALTER TABLE `tournament_participants`
  ADD CONSTRAINT `fk_tp_tournament` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`tournament_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_trans_processed_by` FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_trans_session` FOREIGN KEY (`session_id`) REFERENCES `gaming_sessions` (`session_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_trans_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
