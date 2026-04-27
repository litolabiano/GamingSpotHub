-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 27, 2026 at 02:36 PM
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
(2, 'PS5 Unit 2', 'PS5', 'PS5-02', 'available', 80.00, '2026-02-21 19:55:41'),
(3, 'PS5 Unit 3', 'PS5', 'PS5-03', 'in_use', 80.00, '2026-02-21 19:55:41'),
(4, 'PS5 Unit 4', 'PS5', 'PS5-04', 'available', 80.00, '2026-02-21 19:55:41'),
(5, 'PS5 Unit 5', 'PS5', 'PS5-05', 'available', 80.00, '2026-02-21 19:55:41'),
(6, 'PS4 Unit 6', 'PS4', 'PS4-07', 'maintenance', 80.00, '2026-02-21 19:55:41'),
(7, 'Xbox Unit 2', 'Xbox Series X', 'XBX-02', 'available', 80.00, '2026-02-21 19:55:41'),
(8, 'Xbox Unit 3', 'Xbox Series X', 'XBX-03', 'available', 80.00, '2026-02-21 19:55:41');

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

CREATE TABLE `games` (
  `game_id` int(11) NOT NULL,
  `game_name` varchar(150) NOT NULL,
  `genre` varchar(80) NOT NULL DEFAULT 'Action',
  `console_type` enum('PS5','Xbox Series X','PS4','PC','Multi') NOT NULL DEFAULT 'Multi',
  `platform` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `games`
--

INSERT INTO `games` (`game_id`, `game_name`, `genre`, `console_type`, `platform`, `description`, `is_active`, `created_at`) VALUES
(1, 'FIFA 24', 'Sports', 'PS5', NULL, NULL, 1, '2026-04-27 01:10:34'),
(2, 'God of War Ragnarok', 'Action', 'PS5', NULL, NULL, 1, '2026-04-27 01:10:34'),
(3, 'Spider-Man 2', 'Action', 'PS5', NULL, NULL, 1, '2026-04-27 01:10:34'),
(4, 'Call of Duty: MW3', 'Shooter', 'Multi', NULL, NULL, 1, '2026-04-27 01:10:34'),
(5, 'Tekken 8', 'Fighting', 'PS5', NULL, NULL, 1, '2026-04-27 01:10:34'),
(6, 'Mortal Kombat 1', 'Fighting', 'PS5', NULL, NULL, 1, '2026-04-27 01:10:34'),
(7, 'Forza Horizon 5', 'Racing', 'Xbox Series X', NULL, NULL, 1, '2026-04-27 01:10:34'),
(8, 'Halo Infinite', 'Shooter', 'Xbox Series X', NULL, NULL, 1, '2026-04-27 01:10:34'),
(9, 'GTA V', 'Open World', 'Multi', NULL, NULL, 1, '2026-04-27 01:10:34'),
(10, 'Minecraft', 'Sandbox', 'Multi', NULL, NULL, 1, '2026-04-27 01:10:34'),
(11, 'NBA 2K24', 'Sports', 'Multi', NULL, NULL, 1, '2026-04-27 01:10:34'),
(12, 'Elden Ring', 'RPG', 'Multi', NULL, NULL, 1, '2026-04-27 01:10:34');

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
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gaming_sessions`
--

INSERT INTO `gaming_sessions` (`session_id`, `user_id`, `console_id`, `rental_mode`, `planned_minutes`, `start_time`, `end_time`, `duration_minutes`, `hourly_rate`, `total_cost`, `status`, `payment_status`, `created_by`, `created_at`) VALUES
(47, 21, 1, 'hourly', 420, '2026-04-19 22:33:23', '2026-04-19 22:33:38', 0, 80.00, 560.00, 'completed', NULL, 12, '2026-04-19 22:33:23'),
(48, 21, 2, 'hourly', 210, '2026-04-20 15:10:41', '2026-04-20 19:23:00', 252, 80.00, 340.00, 'completed', NULL, 12, '2026-04-20 15:10:41'),
(49, 17, 1, 'hourly', 330, '2026-04-21 08:38:56', '2026-04-21 11:42:00', 183, 80.00, 440.00, 'completed', NULL, 12, '2026-04-21 08:38:56'),
(50, 17, 2, 'hourly', 90, '2026-04-21 08:39:58', '2026-04-25 13:34:08', 6054, 80.00, 6520.00, 'completed', NULL, 12, '2026-04-21 08:39:58'),
(58, 17, 1, 'hourly', 60, '2026-04-26 18:04:00', '2026-04-26 18:08:05', 4, 80.00, 80.00, 'completed', NULL, 12, '2026-04-26 18:04:00'),
(59, 17, 2, 'hourly', 60, '2026-04-26 18:08:47', '2026-04-26 18:12:42', 4, 80.00, 80.00, 'completed', NULL, 12, '2026-04-26 18:08:47'),
(60, 17, 3, 'hourly', 90, '2026-04-26 18:17:12', '2026-04-26 23:37:51', 321, 80.00, 400.00, 'completed', NULL, 12, '2026-04-26 18:17:12'),
(61, 17, 2, 'hourly', 60, '2026-04-26 18:29:01', '2026-04-26 18:34:22', 5, 80.00, 100.00, 'completed', NULL, 12, '2026-04-26 18:29:01'),
(62, 22, 2, 'hourly', 60, '2026-04-26 19:31:00', '2026-04-26 23:37:42', 247, 80.00, 320.00, 'completed', NULL, 12, '2026-04-26 19:31:00'),
(63, 17, 3, 'hourly', 90, '2026-04-26 23:38:19', '2026-04-26 23:48:13', 10, 80.00, 140.00, 'completed', NULL, 12, '2026-04-26 23:38:19'),
(64, 21, 2, 'hourly', 90, '2026-04-27 00:32:48', '2026-04-27 00:42:00', 9, 80.00, 40.00, 'completed', NULL, 12, '2026-04-27 00:32:48'),
(65, 0, 3, 'hourly', 60, '2026-04-27 00:40:14', '2026-04-27 00:41:53', 2, 80.00, 20.00, 'completed', NULL, 12, '2026-04-27 00:40:14'),
(71, 0, 2, 'hourly', 60, '2026-04-27 00:51:37', '2026-04-27 00:51:51', 0, 80.00, 20.00, 'completed', NULL, 12, '2026-04-27 00:51:37'),
(72, 0, 1, 'hourly', 30, '2026-04-27 01:07:58', '2026-04-27 01:08:09', 0, 80.00, 20.00, 'completed', NULL, 12, '2026-04-27 01:07:58'),
(73, 0, 2, 'hourly', 150, '2026-04-27 01:08:29', '2026-04-27 01:08:46', 0, 80.00, 20.00, 'completed', NULL, 12, '2026-04-27 01:08:29'),
(74, 0, 2, 'hourly', 60, '2026-04-27 01:16:30', '2026-04-27 01:16:40', 0, 80.00, 20.00, 'completed', NULL, 12, '2026-04-27 01:16:30'),
(75, 0, 2, 'hourly', 60, '2026-04-27 01:16:41', '2026-04-27 01:16:52', 0, 80.00, 20.00, 'completed', NULL, 12, '2026-04-27 01:16:41'),
(76, 0, 2, 'hourly', 60, '2026-04-27 01:16:53', '2026-04-27 01:24:08', 7, 80.00, 40.00, 'completed', NULL, 12, '2026-04-27 01:16:53'),
(77, 0, 2, 'hourly', 150, '2026-04-27 01:24:25', '2026-04-27 01:24:43', 0, 80.00, 20.00, 'completed', NULL, 12, '2026-04-27 01:24:25'),
(78, 0, 3, 'hourly', 30, '2026-04-27 01:25:07', '2026-04-27 01:25:12', 0, 80.00, 20.00, 'completed', NULL, 12, '2026-04-27 01:25:07'),
(79, 0, 3, 'hourly', 60, '2026-04-27 19:31:50', NULL, NULL, 80.00, NULL, 'active', NULL, 12, '2026-04-27 19:31:50');

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
(52, 21, NULL, 'PS5', 'hourly', 240, '2026-04-21', '12:00:00', NULL, 320.00, 'cash', 1, 'pending', 21, '2026-04-21 08:37:53', '2026-04-21 08:37:53', NULL, 0),
(57, 21, 1, 'PS5', 'hourly', 30, '2026-04-27', '12:00:00', NULL, 20.00, 'cash', 1, 'cancelled', 21, '2026-04-27 01:18:17', '2026-04-27 01:18:28', 'user', 0);

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
(1, 'ps5_hourly_rate', '80.00', 'Default hourly rate for PS5 units in ₱', '2026-04-26 23:09:54'),
(2, 'xbox_hourly_rate', '80.00', 'Default hourly rate for Xbox Series X units in ₱', '2026-04-26 23:09:54'),
(3, 'unlimited_rate', '400.00', 'Rate for unlimited play (whole day) in ₱', '2026-04-14 09:56:44'),
(4, 'controller_rental_fee', '20.00', 'Additional controller rental fee in ₱', '2026-02-21 19:55:41'),
(5, 'business_hours_open', '12:00', 'Shop opening time', '2026-04-14 09:56:44'),
(6, 'business_hours_close', '00:00', 'Shop closing time', '2026-04-14 09:56:44'),
(7, 'shop_name', 'Good Spot Gaming Hub', 'Shop name', '2026-02-21 19:55:41'),
(8, 'shop_address', 'Don Placido Avenue, Dasmariñas, Cavite', 'Shop address', '2026-02-21 19:55:41'),
(9, 'shop_phone', '09171234567', 'Shop contact number', '2026-02-21 19:55:41'),
(10, 'tournament_default_fee', '100.00', 'Default tournament entry fee in ₱', '2026-02-21 19:55:41'),
(21, 'bonus_paid_minutes', '120', NULL, '2026-04-26 16:47:16'),
(22, 'bonus_free_minutes', '30', NULL, '2026-04-26 16:47:16'),
(23, 'max_hourly_minutes', '240', NULL, '2026-04-26 16:47:16'),
(24, 'session_min_charge', '20', NULL, '2026-04-26 23:21:14'),
(151, 'inconvenience_fee', '50.00', 'Fee deducted from downpayment refund when customer cancels after their reserved start time.', '2026-04-27 01:02:48');

-- --------------------------------------------------------

--
-- Table structure for table `tournaments`
--

CREATE TABLE `tournaments` (
  `tournament_id` int(11) NOT NULL,
  `tournament_name` varchar(150) NOT NULL,
  `game_id` int(11) DEFAULT NULL,
  `game_name` varchar(150) NOT NULL DEFAULT '',
  `console_type` enum('PS5','Xbox Series X','PS4','PC','Multi') NOT NULL,
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
(192, 50, 17, 123.00, 123.00, 6397.00, 'Short payment — collected ₱123.00, short by ₱6,397.00', 'cash', 'completed', '2026-04-25 13:34:08', 12, '2026-04-25 13:34:08'),
(193, 50, 17, 6397.00, NULL, NULL, 'Balance payment collected', 'cash', 'completed', '2026-04-25 13:34:16', 12, '2026-04-25 13:34:16'),
(221, 58, 17, 0.00, 0.00, 100.00, 'Short payment at session start — short by ₱100.00', 'cash', 'completed', '2026-04-26 18:04:00', 12, '2026-04-26 18:04:00'),
(222, 58, 17, 80.00, 80.00, NULL, 'Balance payment collected', 'cash', 'completed', '2026-04-26 18:08:12', 12, '2026-04-26 18:08:12'),
(223, 59, 17, 9.00, 9.00, 91.00, 'Short payment at session start — short by ₱91.00', 'cash', 'completed', '2026-04-26 18:08:47', 12, '2026-04-26 18:08:47'),
(224, 59, 17, -9.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 03m (₱0.00), refunding unused time (₱9.00)', '', 'completed', '2026-04-26 18:12:42', 12, '2026-04-26 18:12:42'),
(225, 62, 22, 100.00, 2000.00, NULL, NULL, 'cash', 'completed', '2026-04-26 19:31:00', 12, '2026-04-26 19:31:00'),
(226, 61, 17, 140.00, 140.00, NULL, 'Balance payment collected', 'cash', 'completed', '2026-04-26 20:13:56', 12, '2026-04-26 20:13:56'),
(227, 59, 17, 180.00, 180.00, NULL, 'Balance payment collected', 'cash', 'completed', '2026-04-26 20:14:01', 12, '2026-04-26 20:14:01'),
(228, 62, 22, 220.00, 220.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-04-26 23:37:42', 12, '2026-04-26 23:37:42'),
(229, 60, 17, 200.00, 200.00, 200.00, 'Short payment — collected ₱200.00, short by ₱200.00', 'cash', 'completed', '2026-04-26 23:37:51', 12, '2026-04-26 23:37:51'),
(230, 60, 17, 200.00, 200.00, NULL, 'Balance payment collected', 'cash', 'completed', '2026-04-26 23:37:55', 12, '2026-04-26 23:37:55'),
(232, 71, 0, 20.00, 20.00, 80.00, 'Short payment at session start — short by ₱80.00', 'cash', 'completed', '2026-04-27 00:51:37', 12, '2026-04-27 00:51:37'),
(233, 72, 0, 20.00, 20.00, 20.00, 'Short payment at session start — short by ₱20.00', 'cash', 'completed', '2026-04-27 01:07:58', 12, '2026-04-27 01:07:58'),
(234, 73, 0, 100.00, 100.00, 80.00, 'Short payment at session start — short by ₱80.00', 'cash', 'completed', '2026-04-27 01:08:29', 12, '2026-04-27 01:08:29'),
(235, 73, 0, -80.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 00m (₱20.00), refunding unused time (₱80.00)', '', 'completed', '2026-04-27 01:08:46', 12, '2026-04-27 01:08:46'),
(236, NULL, 21, 20.00, 20.00, NULL, 'Downpayment for reservation #57', 'cash', 'completed', '2026-04-27 01:18:17', 21, '2026-04-27 01:18:17'),
(237, 77, 0, 120.00, 120.00, 60.00, 'Short payment at session start — short by ₱60.00', 'cash', 'completed', '2026-04-27 01:24:25', 12, '2026-04-27 01:24:25'),
(238, 77, 0, 80.00, 80.00, NULL, 'Balance payment collected', 'cash', 'completed', '2026-04-27 01:24:33', 12, '2026-04-27 01:24:33'),
(239, 77, 0, -180.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 00m (₱20.00), refunding unused time (₱180.00)', '', 'completed', '2026-04-27 01:24:43', 12, '2026-04-27 01:24:43'),
(240, 79, 0, 20.00, 20.00, 80.00, 'Short payment at session start — short by ₱80.00', 'cash', 'completed', '2026-04-27 19:31:50', 12, '2026-04-27 19:31:50');

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
(12, 'ljlabianao@gmail.com', '$2y$10$nEuBy0VoWqtbRETOnAD99eurxRHdLaGpxROWA//NGpziKgIG1CmmS', 'lito', '09916310227', 'shopkeeper', 'active', 1, NULL, NULL, NULL, NULL, '2026-02-21 20:45:06', 0, NULL),
(17, 'llabianojr@kld.edu.ph', '$2y$10$N97t4MFGjElD3iM.nlpn3uZ/hy1zNC/Lfqi/YozlBB6BG1VWkL.Oe', 'Lito LARGUEZA LABIANO', '09916310227', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-04-12 19:54:25', 0, NULL),
(21, 'hello@gmail.com', '$2y$10$vjAu848ZbS1DpfexUIsM1.x31O/VNLXNwp/sw65NckgmZTHTKLzAO', 'Lito LARGUEZA LABIANO', '09916310227', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-04-17 11:59:29', 1, NULL),
(22, 'admin@gmail.com', '$2y$10$kZCF21cxNKIxXE99XNGyF.nGfT94FZeDaGtbH4YTBOxrpumQLDvIK', 'Admin User', '', 'customer', 'active', 0, '9ad4e0d4b2004d75a763bd2e6e9af695cdb053e39c7cadd65db419783c75b54f', '2026-04-20 16:02:24', NULL, NULL, '2026-04-19 16:02:24', 0, NULL),
(23, 'customer@example.com', '$2y$10$8mmAlQ1UknZorTRMn6NVneRRA5JrVSOO8oMAuDEVll.pICMikYieO', 'Test Customer', '', 'customer', 'active', 0, '5337f25a858a79327c9001f982e6629f2f64be6579986d9da00c816159a6a75c', '2026-04-20 20:49:05', NULL, NULL, '2026-04-19 20:49:05', 0, NULL),
(24, 'lito@example.com', '$2y$10$b1z6jAajQr5NjOyHWkU5kOJ7kx/BdeM6HbUg/qLWdElsilx20bLta', 'Lito Test', '', 'customer', 'active', 0, '10512cc414ac02ff1b961698a17863ad87e3e55d976dc1e14d92a2af161ee3bd', '2026-04-20 20:52:01', NULL, NULL, '2026-04-19 20:52:01', 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `consoles`
--
ALTER TABLE `consoles`
  ADD PRIMARY KEY (`console_id`),
  ADD UNIQUE KEY `unit_number` (`unit_number`),
  ADD KEY `idx_consoles_status` (`status`),
  ADD KEY `idx_consoles_type` (`console_type`);

--
-- Indexes for table `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`game_id`);

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
-- AUTO_INCREMENT for table `consoles`
--
ALTER TABLE `consoles`
  MODIFY `console_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3212;

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `game_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `gaming_sessions`
--
ALTER TABLE `gaming_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `session_extensions`
--
ALTER TABLE `session_extensions`
  MODIFY `extension_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=152;

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
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=241;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Constraints for dumped tables
--

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
-- Constraints for table `tournaments`
--
ALTER TABLE `tournaments`
  ADD CONSTRAINT `fk_tourn_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON UPDATE CASCADE;

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
