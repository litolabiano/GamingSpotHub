-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 19, 2026 at 11:18 AM
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
(2, 'PS5 Unit 2', 'PS5', 'PS5-02', 'available', 80.00, '2026-02-21 19:55:41'),
(3, 'PS5 Unit 3', 'PS5', 'PS5-03', 'available', 80.00, '2026-02-21 19:55:41'),
(4, 'PS5 Unit 4', 'PS5', 'PS5-04', 'available', 80.00, '2026-02-21 19:55:41'),
(5, 'PS5 Unit 5', 'PS5', 'PS5-05', 'available', 80.00, '2026-02-21 19:55:41'),
(6, 'PS4 Unit 6', 'PS4', 'PS4-07', 'available', 80.00, '2026-02-21 19:55:41'),
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
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gaming_sessions`
--

INSERT INTO `gaming_sessions` (`session_id`, `user_id`, `console_id`, `rental_mode`, `planned_minutes`, `start_time`, `end_time`, `duration_minutes`, `hourly_rate`, `total_cost`, `status`, `payment_status`, `created_by`, `created_at`) VALUES
(1, 17, 1, 'hourly', 390, '2026-04-15 20:10:32', '2026-04-15 20:10:40', 0, 80.00, 520.00, 'completed', NULL, 12, '2026-04-15 20:10:32'),
(2, 17, 1, 'hourly', 420, '2026-04-15 20:12:27', '2026-04-15 20:30:26', 18, 80.00, 560.00, 'completed', NULL, 12, '2026-04-15 20:12:27'),
(3, 17, 3, 'hourly', 450, '2026-04-15 20:20:08', '2026-04-15 20:30:35', 10, 80.00, 600.00, 'completed', NULL, 12, '2026-04-15 20:20:08'),
(4, 17, 2, 'hourly', 1020, '2026-04-15 20:31:02', '2026-04-15 20:57:50', 27, 80.00, 1360.00, 'completed', NULL, 12, '2026-04-15 20:31:02'),
(5, 17, 1, 'hourly', 360, '2026-04-15 20:58:00', '2026-04-15 20:58:05', 0, 80.00, 480.00, 'completed', NULL, 12, '2026-04-15 20:58:00'),
(6, 17, 2, 'hourly', 420, '2026-04-15 20:58:35', '2026-04-15 21:01:49', 3, 80.00, 560.00, 'completed', NULL, 12, '2026-04-15 20:58:35'),
(7, 17, 2, 'hourly', 420, '2026-04-15 21:02:04', '2026-04-15 21:02:32', 0, 80.00, 560.00, 'completed', NULL, 12, '2026-04-15 21:02:04'),
(8, 17, 2, 'hourly', 480, '2026-04-15 21:02:47', '2026-04-15 21:04:23', 2, 80.00, 640.00, 'completed', NULL, 12, '2026-04-15 21:02:47'),
(9, 17, 1, 'hourly', 390, '2026-04-15 21:07:16', '2026-04-15 21:07:18', 0, 80.00, 520.00, 'completed', 'unpaid', 12, '2026-04-15 21:07:16'),
(10, 17, 1, 'hourly', 420, '2026-04-15 21:14:10', '2026-04-15 21:14:22', 0, 80.00, 560.00, 'completed', 'unpaid', 12, '2026-04-15 21:14:10'),
(11, 17, 2, 'hourly', 390, '2026-04-15 21:20:37', '2026-04-15 21:34:56', 14, 80.00, 520.00, 'completed', NULL, 12, '2026-04-15 21:20:37'),
(12, 17, 1, 'hourly', 390, '2026-04-15 21:30:05', '2026-04-15 21:34:54', 5, 80.00, 520.00, 'completed', NULL, 12, '2026-04-15 21:30:05'),
(13, 17, 3, 'hourly', 420, '2026-04-15 21:31:12', '2026-04-15 21:34:53', 4, 80.00, 560.00, 'completed', NULL, 12, '2026-04-15 21:31:12'),
(14, 17, 4, 'hourly', 390, '2026-04-15 21:31:37', '2026-04-15 21:34:50', 3, 80.00, 520.00, 'completed', NULL, 12, '2026-04-15 21:31:37'),
(15, 17, 2, 'hourly', 435, '2026-04-15 21:35:06', '2026-04-15 23:44:15', 129, 80.00, 580.00, 'completed', NULL, 12, '2026-04-15 21:35:06'),
(16, 17, 3, 'hourly', 420, '2026-04-15 21:36:05', '2026-04-15 21:36:14', 0, 80.00, 560.00, 'completed', NULL, 12, '2026-04-15 21:36:05'),
(17, 17, 2, 'hourly', 420, '2026-04-15 23:48:55', '2026-04-15 23:52:51', 4, 80.00, 560.00, 'completed', NULL, 12, '2026-04-15 23:48:55'),
(18, 17, 1, 'hourly', 390, '2026-04-15 23:53:11', '2026-04-15 23:54:00', 1, 80.00, 520.00, 'completed', NULL, 12, '2026-04-15 23:53:11'),
(19, 17, 1, 'hourly', 390, '2026-04-15 23:54:21', '2026-04-15 23:57:39', 3, 80.00, 520.00, 'completed', NULL, 12, '2026-04-15 23:54:21'),
(20, 17, 1, 'hourly', 390, '2026-04-15 23:57:51', '2026-04-16 00:36:44', 39, 80.00, 520.00, 'completed', NULL, 12, '2026-04-15 23:57:51'),
(21, 17, 3, 'hourly', 330, '2026-04-16 12:00:27', '2026-04-16 13:25:16', 85, 80.00, 440.00, 'completed', NULL, 12, '2026-04-16 12:00:27'),
(22, 17, 1, 'hourly', 360, '2026-04-16 13:25:41', '2026-04-16 13:26:18', 1, 80.00, 480.00, 'completed', NULL, 12, '2026-04-16 13:25:41'),
(23, 17, 2, 'hourly', 450, '2026-04-16 22:48:29', '2026-04-16 22:49:15', 1, 80.00, 600.00, 'completed', NULL, 12, '2026-04-16 22:48:29'),
(24, 17, 1, 'hourly', 390, '2026-04-17 12:15:40', '2026-04-17 12:17:52', 2, 80.00, 520.00, 'completed', NULL, 12, '2026-04-17 12:15:40'),
(25, 17, 1, 'hourly', 450, '2026-04-17 12:20:19', '2026-04-17 12:20:25', 0, 80.00, 600.00, 'completed', NULL, 12, '2026-04-17 12:20:19'),
(26, 17, 1, 'hourly', 360, '2026-04-17 12:23:05', '2026-04-17 12:23:13', 0, 80.00, 480.00, 'completed', NULL, 12, '2026-04-17 12:23:05'),
(27, 21, 3, 'hourly', 420, '2026-04-17 12:26:48', '2026-04-17 18:01:18', 335, 80.00, 560.00, 'completed', NULL, 12, '2026-04-17 12:26:48'),
(28, 17, 1, 'hourly', 390, '2026-04-17 18:02:47', '2026-04-17 18:03:30', 1, 80.00, 520.00, 'completed', NULL, 12, '2026-04-17 18:02:47'),
(29, 21, 1, 'hourly', 390, '2026-04-17 19:09:50', '2026-04-17 19:14:51', 5, 80.00, 520.00, 'completed', NULL, 12, '2026-04-17 19:09:50'),
(30, 21, 1, 'hourly', 390, '2026-04-17 19:17:32', '2026-04-17 22:33:07', 196, 80.00, 520.00, 'completed', NULL, 12, '2026-04-17 19:17:32'),
(31, 21, 1, 'hourly', 420, '2026-04-18 00:31:57', '2026-04-18 00:32:45', 1, 80.00, 560.00, 'completed', NULL, 12, '2026-04-18 00:31:57'),
(32, 17, 1, 'hourly', 60, '2026-04-18 16:12:24', '2026-04-18 16:12:47', 0, 80.00, 80.00, 'completed', NULL, 12, '2026-04-18 16:12:24'),
(33, 17, 1, 'hourly', 120, '2026-04-18 16:21:20', '2026-04-18 16:24:37', 3, 80.00, 160.00, 'completed', NULL, 12, '2026-04-18 16:21:20'),
(34, 21, 6, 'hourly', 180, '2026-04-18 16:24:20', '2026-04-18 16:24:32', 0, 80.00, 240.00, 'completed', NULL, 12, '2026-04-18 16:24:20'),
(35, 21, 1, 'hourly', 240, '2026-04-18 16:25:04', '2026-04-18 16:26:07', 1, 80.00, 320.00, 'completed', NULL, 12, '2026-04-18 16:25:04'),
(36, 21, 2, 'hourly', 240, '2026-04-18 16:25:30', '2026-04-18 16:26:03', 1, 80.00, 320.00, 'completed', NULL, 12, '2026-04-18 16:25:30'),
(37, 17, 3, 'hourly', 240, '2026-04-18 16:25:52', '2026-04-18 16:25:58', 0, 80.00, 320.00, 'completed', NULL, 12, '2026-04-18 16:25:52'),
(38, 12, 2, 'hourly', 480, '2026-04-18 16:38:33', '2026-04-18 16:41:32', 3, 80.00, 640.00, 'completed', NULL, 12, '2026-04-18 16:38:33'),
(39, 17, 6, 'hourly', 90, '2026-04-18 16:44:07', '2026-04-18 17:08:14', 24, 80.00, 120.00, 'completed', NULL, 12, '2026-04-18 16:44:07'),
(40, 17, 1, 'hourly', 450, '2026-04-18 17:08:28', '2026-04-18 17:08:33', 0, 80.00, 600.00, 'completed', NULL, 12, '2026-04-18 17:08:28'),
(41, 21, 2, 'hourly', 150, '2026-04-19 15:38:47', '2026-04-19 15:41:05', 2, 80.00, 200.00, 'completed', NULL, 12, '2026-04-19 15:38:47'),
(42, 21, 1, 'hourly', 390, '2026-04-19 15:45:41', '2026-04-19 18:46:00', 180, 80.00, 520.00, 'completed', NULL, 12, '2026-04-19 15:45:41'),
(43, 21, 2, 'hourly', 420, '2026-04-19 15:47:02', '2026-04-19 16:57:57', 71, 80.00, 560.00, 'completed', NULL, 12, '2026-04-19 15:47:02'),
(44, 17, 2, 'hourly', 390, '2026-04-19 16:58:09', '2026-04-19 16:58:18', 0, 80.00, 520.00, 'completed', NULL, 12, '2026-04-19 16:58:09');

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
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`reservation_id`, `user_id`, `console_id`, `console_type`, `rental_mode`, `planned_minutes`, `reserved_date`, `reserved_time`, `notes`, `downpayment_amount`, `downpayment_method`, `downpayment_paid`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 17, NULL, 'PS5', 'hourly', 60, '2321-12-31', '12:31:00', NULL, 1333340.00, 'cash', 1, 'cancelled', 17, '2026-04-16 22:49:03', '2026-04-18 16:05:12'),
(2, 17, 1, 'PS5', 'hourly', 60, '2321-12-31', '12:31:00', NULL, 1333340.00, 'cash', 1, 'converted', 17, '2026-04-16 22:49:11', '2026-04-18 16:12:24'),
(3, 21, NULL, 'PS5', 'open_time', NULL, '2026-06-10', '00:33:00', NULL, 1200.00, 'cash', 1, 'cancelled', 21, '2026-04-17 12:18:53', '2026-04-18 16:13:06'),
(4, 21, 1, 'PS5', 'hourly', 240, '2026-06-19', '10:00:00', NULL, 90.00, 'cash', 1, 'converted', 21, '2026-04-17 18:59:53', '2026-04-18 16:25:04'),
(5, 17, 1, 'PS5', 'hourly', 120, '2026-06-19', '10:10:00', NULL, 100.00, 'cash', 1, 'converted', 17, '2026-04-17 19:13:05', '2026-04-18 16:21:20'),
(6, 12, NULL, 'PS4', 'open_time', NULL, '2026-06-19', '12:13:00', NULL, 100.00, 'cash', 1, 'cancelled', 12, '2026-04-17 22:34:02', '2026-04-18 16:23:59'),
(7, 21, 6, 'PS4', 'hourly', 180, '2026-06-19', '10:10:00', 'nothing', 100.00, 'cash', 1, 'converted', 21, '2026-04-18 00:25:54', '2026-04-18 16:24:20'),
(8, 21, 2, 'PS5', 'hourly', 240, '3030-06-01', '10:10:00', NULL, 100.00, 'cash', 1, 'converted', 21, '2026-04-18 00:30:59', '2026-04-18 16:25:30'),
(9, 12, 2, 'PS5', 'hourly', 480, '2026-06-10', '10:20:00', NULL, 320.00, 'cash', 1, 'converted', 12, '2026-04-18 16:38:16', '2026-04-18 16:38:33'),
(10, 17, 6, 'PS4', 'hourly', 90, '2026-06-19', '10:10:00', NULL, 60.00, 'cash', 1, 'converted', 17, '2026-04-18 16:43:53', '2026-04-18 16:44:07'),
(11, 21, 2, 'PS5', 'hourly', 150, '2026-06-19', '10:00:00', NULL, 100.00, 'cash', 1, 'converted', 21, '2026-04-19 15:36:54', '2026-04-19 15:38:47');

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
(1, 'ps5_hourly_rate', '80.00', 'Default hourly rate for PS5 units in ₱', '2026-04-14 09:56:44'),
(2, 'xbox_hourly_rate', '80.00', 'Default hourly rate for Xbox Series X units in ₱', '2026-04-14 09:56:44'),
(3, 'unlimited_rate', '400.00', 'Rate for unlimited play (whole day) in ₱', '2026-04-14 09:56:44'),
(4, 'controller_rental_fee', '20.00', 'Additional controller rental fee in ₱', '2026-02-21 19:55:41'),
(5, 'business_hours_open', '12:00', 'Shop opening time', '2026-04-14 09:56:44'),
(6, 'business_hours_close', '00:00', 'Shop closing time', '2026-04-14 09:56:44'),
(7, 'shop_name', 'Good Spot Gaming Hub', 'Shop name', '2026-02-21 19:55:41'),
(8, 'shop_address', 'Don Placido Avenue, Dasmariñas, Cavite', 'Shop address', '2026-02-21 19:55:41'),
(9, 'shop_phone', '09171234567', 'Shop contact number', '2026-02-21 19:55:41'),
(10, 'tournament_default_fee', '100.00', 'Default tournament entry fee in ₱', '2026-02-21 19:55:41');

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
  `session_id` int(11) NOT NULL,
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
(1, 1, 17, 200.00, 200.00, 320.00, 'Short payment at session start — short by ₱320.00', 'cash', 'completed', '2026-04-15 20:10:32', 12, '2026-04-15 20:10:32'),
(2, 1, 17, 320.00, 31.00, 289.00, 'Short payment — customer short by ₱289.00', 'cash', 'completed', '2026-04-15 20:10:40', 12, '2026-04-15 20:10:40'),
(3, 2, 17, 200.00, 200.00, 360.00, 'Short payment at session start — short by ₱360.00', 'cash', 'completed', '2026-04-15 20:12:27', 12, '2026-04-15 20:12:27'),
(4, 3, 17, 12.00, 12.00, 588.00, 'Short payment at session start — short by ₱588.00', 'cash', 'completed', '2026-04-15 20:20:08', 12, '2026-04-15 20:20:08'),
(5, 2, 17, 360.00, 360.00, NULL, NULL, 'cash', 'completed', '2026-04-15 20:30:26', 12, '2026-04-15 20:30:26'),
(6, 3, 17, 588.00, 588.00, NULL, NULL, 'cash', 'completed', '2026-04-15 20:30:35', 12, '2026-04-15 20:30:35'),
(7, 4, 17, 200.00, 200.00, 400.00, 'Short payment at session start — short by ₱400.00', 'cash', 'completed', '2026-04-15 20:31:02', 12, '2026-04-15 20:31:02'),
(8, 4, 17, -100.00, NULL, NULL, 'Refund issued — ₱100.00 returned to customer.', 'cash', 'completed', '2026-04-15 20:36:43', 12, '2026-04-15 20:36:43'),
(9, 4, 17, 1260.00, NULL, NULL, NULL, 'cash', 'completed', '2026-04-15 20:57:50', 12, '2026-04-15 20:57:50'),
(10, 5, 17, 200.00, 200.00, 280.00, 'Short payment at session start — short by ₱280.00', 'cash', 'completed', '2026-04-15 20:58:00', 12, '2026-04-15 20:58:00'),
(11, 5, 17, 280.00, NULL, NULL, NULL, 'cash', 'completed', '2026-04-15 20:58:05', 12, '2026-04-15 20:58:05'),
(12, 6, 17, 200.00, 200.00, 360.00, 'Short payment at session start — short by ₱360.00', 'cash', 'completed', '2026-04-15 20:58:35', 12, '2026-04-15 20:58:35'),
(13, 7, 17, 200.00, 200.00, 360.00, 'Short payment at session start — short by ₱360.00', 'cash', 'completed', '2026-04-15 21:02:04', 12, '2026-04-15 21:02:04'),
(14, 7, 17, 360.00, NULL, NULL, NULL, 'cash', 'completed', '2026-04-15 21:02:32', 12, '2026-04-15 21:02:32'),
(15, 8, 17, 200.00, 200.00, 440.00, 'Short payment at session start — short by ₱440.00', 'cash', 'completed', '2026-04-15 21:02:47', 12, '2026-04-15 21:02:47'),
(16, 9, 17, 200.00, 200.00, 320.00, 'Short payment at session start — short by ₱320.00', 'cash', 'completed', '2026-04-15 21:07:16', 12, '2026-04-15 21:07:16'),
(17, 10, 17, 300.00, 300.00, 260.00, 'Short payment at session start — short by ₱260.00', 'cash', 'completed', '2026-04-15 21:14:10', 12, '2026-04-15 21:14:10'),
(18, 11, 17, 520.00, NULL, NULL, NULL, 'cash', 'completed', '2026-04-15 21:20:37', 12, '2026-04-15 21:20:37'),
(19, 12, 17, 520.00, NULL, NULL, NULL, 'cash', 'completed', '2026-04-15 21:30:05', 12, '2026-04-15 21:30:05'),
(20, 13, 17, 560.00, NULL, NULL, NULL, 'cash', 'completed', '2026-04-15 21:31:12', 12, '2026-04-15 21:31:12'),
(21, 14, 17, 520.00, NULL, NULL, NULL, 'cash', 'completed', '2026-04-15 21:31:37', 12, '2026-04-15 21:31:37'),
(22, 15, 17, 520.00, 200.00, 320.00, 'Short payment at session start — short by ₱320.00', 'cash', 'completed', '2026-04-15 21:35:06', 12, '2026-04-15 21:35:06'),
(23, 16, 17, 200.00, 200.00, 360.00, 'Short payment at session start — short by ₱360.00', 'cash', 'completed', '2026-04-15 21:36:05', 12, '2026-04-15 21:36:05'),
(24, 16, 17, 360.00, NULL, NULL, NULL, 'cash', 'completed', '2026-04-15 21:36:14', 12, '2026-04-15 21:36:14'),
(25, 15, 17, 10.00, 123.00, NULL, 'Mid-session payment collected', 'cash', 'completed', '2026-04-15 23:42:56', 12, '2026-04-15 23:42:56'),
(26, 15, 17, -50.00, NULL, NULL, 'Refund issued: idk', '', 'completed', '2026-04-15 23:43:55', 12, '2026-04-15 23:43:55'),
(27, 15, 17, 100.00, NULL, NULL, NULL, 'cash', 'completed', '2026-04-15 23:44:15', 12, '2026-04-15 23:44:15'),
(28, 17, 17, 20.00, 20.00, 540.00, 'Short payment at session start — short by ₱540.00', 'cash', 'completed', '2026-04-15 23:48:55', 12, '2026-04-15 23:48:55'),
(29, 17, 17, 540.00, 20.00, 520.00, 'Mid-session partial payment — short by ₱520.00', 'cash', 'completed', '2026-04-15 23:52:03', 12, '2026-04-15 23:52:03'),
(30, 17, 17, 540.00, 20.00, 520.00, 'Mid-session partial payment — short by ₱520.00', 'cash', 'completed', '2026-04-15 23:52:32', 12, '2026-04-15 23:52:32'),
(31, 18, 17, 20.00, 20.00, 500.00, 'Short payment at session start — short by ₱500.00', 'cash', 'completed', '2026-04-15 23:53:11', 12, '2026-04-15 23:53:11'),
(32, 18, 17, 500.00, 123.00, 377.00, 'Mid-session partial payment — short by ₱377.00', 'cash', 'completed', '2026-04-15 23:53:47', 12, '2026-04-15 23:53:47'),
(33, 19, 17, 123.00, 123.00, 397.00, 'Short payment at session start — short by ₱397.00', 'cash', 'completed', '2026-04-15 23:54:21', 12, '2026-04-15 23:54:21'),
(34, 19, 17, 397.00, 123.00, 274.00, 'Mid-session partial payment — short by ₱274.00', 'cash', 'completed', '2026-04-15 23:56:14', 12, '2026-04-15 23:56:14'),
(35, 20, 17, 123.00, 123.00, 397.00, 'Short payment at session start — short by ₱397.00', 'cash', 'completed', '2026-04-15 23:57:51', 12, '2026-04-15 23:57:51'),
(36, 20, 17, 397.00, NULL, NULL, 'Balance payment collected', 'cash', 'completed', '2026-04-16 00:10:17', 12, '2026-04-16 00:10:17'),
(37, 20, 17, 397.00, NULL, NULL, 'Balance payment collected', 'cash', 'completed', '2026-04-16 00:10:24', 12, '2026-04-16 00:10:24'),
(38, 20, 17, -1220.00, NULL, NULL, 'Refund issued: 123', '', 'completed', '2026-04-16 00:11:10', 12, '2026-04-16 00:11:10'),
(39, 20, 17, -1220.00, NULL, NULL, 'Refund issued: 123', '', 'completed', '2026-04-16 00:12:18', 12, '2026-04-16 00:12:18'),
(40, 20, 17, -1220.00, NULL, NULL, 'Refund issued: 123', '', 'completed', '2026-04-16 00:12:27', 12, '2026-04-16 00:12:27'),
(41, 20, 17, -1220.00, NULL, NULL, 'Refund issued: 123', '', 'completed', '2026-04-16 00:12:37', 12, '2026-04-16 00:12:37'),
(42, 20, 17, -1220.00, NULL, NULL, 'Refund issued: 123', '', 'completed', '2026-04-16 00:18:37', 12, '2026-04-16 00:18:37'),
(43, 20, 17, -1220.00, NULL, NULL, 'Refund issued: 123', '', 'completed', '2026-04-16 00:18:51', 12, '2026-04-16 00:18:51'),
(44, 20, 17, -1220.00, NULL, NULL, 'Refund issued: 123', '', 'completed', '2026-04-16 00:19:10', 12, '2026-04-16 00:19:10'),
(45, 20, 17, -1220.00, NULL, NULL, 'Refund issued: 123', '', 'completed', '2026-04-16 00:20:34', 12, '2026-04-16 00:20:34'),
(46, 20, 17, -1220.00, NULL, NULL, 'Refund issued: 123', '', 'completed', '2026-04-16 00:22:02', 12, '2026-04-16 00:22:02'),
(47, 20, 17, -1220.00, NULL, NULL, 'Refund issued: 123', '', 'completed', '2026-04-16 00:22:14', 12, '2026-04-16 00:22:14'),
(48, 20, 17, -1220.00, NULL, NULL, 'Refund issued: 123', '', 'completed', '2026-04-16 00:22:25', 12, '2026-04-16 00:22:25'),
(49, 20, 17, -1220.00, NULL, NULL, 'Refund issued: 123', '', 'completed', '2026-04-16 00:24:01', 12, '2026-04-16 00:24:01'),
(50, 20, 17, -1220.00, NULL, NULL, 'Refund issued: 123', '', 'completed', '2026-04-16 00:24:49', 12, '2026-04-16 00:24:49'),
(51, 20, 17, -1220.00, NULL, NULL, 'Refund issued: 123', '', 'completed', '2026-04-16 00:25:08', 12, '2026-04-16 00:25:08'),
(52, 20, 17, -1220.00, NULL, NULL, 'Refund issued: 123', '', 'completed', '2026-04-16 00:27:41', 12, '2026-04-16 00:27:41'),
(53, 20, 17, -1220.00, NULL, NULL, 'Refund issued: 123', '', 'completed', '2026-04-16 00:27:41', 12, '2026-04-16 00:27:41'),
(54, 20, 17, -1220.00, NULL, NULL, 'Refund issued: 123', '', 'completed', '2026-04-16 00:28:11', 12, '2026-04-16 00:28:11'),
(55, 20, 17, -1220.00, NULL, NULL, 'Refund issued: 123', '', 'completed', '2026-04-16 00:29:09', 12, '2026-04-16 00:29:09'),
(56, 20, 17, -1220.00, NULL, NULL, 'Refund issued: 123', '', 'completed', '2026-04-16 00:29:14', 12, '2026-04-16 00:29:14'),
(57, 20, 17, -1220.00, NULL, NULL, 'Refund issued: 123', '', 'completed', '2026-04-16 00:29:20', 12, '2026-04-16 00:29:20'),
(58, 20, 17, -1220.00, NULL, NULL, 'Refund issued: 123', '', 'completed', '2026-04-16 00:30:02', 12, '2026-04-16 00:30:02'),
(59, 20, 17, -1220.00, NULL, NULL, 'Refund issued: 123', '', 'completed', '2026-04-16 00:30:52', 12, '2026-04-16 00:30:52'),
(60, 20, 17, -1220.00, NULL, NULL, 'Refund issued: 123', '', 'completed', '2026-04-16 00:30:55', 12, '2026-04-16 00:30:55'),
(61, 20, 17, -1220.00, NULL, NULL, 'Refund issued: 123', '', 'completed', '2026-04-16 00:35:54', 12, '2026-04-16 00:35:54'),
(62, 20, 17, -1220.00, NULL, NULL, 'Refund issued: 123', '', 'completed', '2026-04-16 00:36:18', 12, '2026-04-16 00:36:18'),
(63, 20, 17, 12312.00, 12312.00, 17791.00, 'Partial payment — collected ₱12,312.00, short by ₱17,791.00 of ₱30,103.00 balance', 'cash', 'completed', '2026-04-16 00:36:37', 12, '2026-04-16 00:36:37'),
(64, 20, 17, 17791.00, NULL, NULL, NULL, 'cash', 'completed', '2026-04-16 00:36:44', 12, '2026-04-16 00:36:44'),
(65, 21, 17, 200.00, 200.00, 240.00, 'Short payment at session start — short by ₱240.00', 'cash', 'completed', '2026-04-16 12:00:27', 12, '2026-04-16 12:00:27'),
(66, 21, 17, 12.00, 12.00, 228.00, 'Partial payment — collected ₱12.00, short by ₱228.00 of ₱240.00 balance', 'cash', 'completed', '2026-04-16 12:00:40', 12, '2026-04-16 12:00:40'),
(67, 21, 17, 228.00, NULL, NULL, NULL, 'cash', 'completed', '2026-04-16 13:25:16', 12, '2026-04-16 13:25:16'),
(68, 22, 17, 200.00, 200.00, 280.00, 'Short payment at session start — short by ₱280.00', 'cash', 'completed', '2026-04-16 13:25:41', 12, '2026-04-16 13:25:41'),
(69, 22, 17, 100.00, 100.00, 180.00, 'Partial payment — collected ₱100.00, short by ₱180.00 of ₱280.00 balance', 'cash', 'completed', '2026-04-16 13:25:57', 12, '2026-04-16 13:25:57'),
(70, 22, 17, 120.00, 120.00, 60.00, 'Partial payment — collected ₱120.00, short by ₱60.00 of ₱180.00 balance', 'cash', 'completed', '2026-04-16 13:26:11', 12, '2026-04-16 13:26:11'),
(71, 22, 17, 60.00, NULL, NULL, NULL, 'cash', 'completed', '2026-04-16 13:26:18', 12, '2026-04-16 13:26:18'),
(72, 23, 17, 200.00, 200.00, 400.00, 'Short payment at session start — short by ₱400.00', 'cash', 'completed', '2026-04-16 22:48:29', 12, '2026-04-16 22:48:29'),
(73, 23, 17, 400.00, NULL, NULL, NULL, 'cash', 'completed', '2026-04-16 22:49:15', 12, '2026-04-16 22:49:15'),
(74, 24, 17, 300.00, 300.00, 220.00, 'Short payment at session start — short by ₱220.00', 'cash', 'completed', '2026-04-17 12:15:40', 12, '2026-04-17 12:15:40'),
(75, 24, 17, 12.00, 12.00, 208.00, 'Partial payment — collected ₱12.00, short by ₱208.00 of ₱220.00 balance', 'cash', 'completed', '2026-04-17 12:17:40', 12, '2026-04-17 12:17:40'),
(76, 24, 17, 208.00, NULL, NULL, NULL, 'cash', 'completed', '2026-04-17 12:17:52', 12, '2026-04-17 12:17:52'),
(77, 25, 17, 200.00, 200.00, 400.00, 'Short payment at session start — short by ₱400.00', 'cash', 'completed', '2026-04-17 12:20:19', 12, '2026-04-17 12:20:19'),
(78, 25, 17, 400.00, 200.00, 200.00, 'Short payment — customer short by ₱200.00', 'cash', 'completed', '2026-04-17 12:20:25', 12, '2026-04-17 12:20:25'),
(79, 26, 17, 200.00, 200.00, 280.00, 'Short payment at session start — short by ₱280.00', 'cash', 'completed', '2026-04-17 12:23:05', 12, '2026-04-17 12:23:05'),
(80, 26, 17, 280.00, 100.00, 180.00, 'Short payment — customer short by ₱180.00', 'cash', 'completed', '2026-04-17 12:23:13', 12, '2026-04-17 12:23:13'),
(81, 27, 21, 300.00, 300.00, 260.00, 'Short payment at session start — short by ₱260.00', 'cash', 'completed', '2026-04-17 12:26:48', 12, '2026-04-17 12:26:48'),
(82, 27, 21, 26.00, 26.00, 234.00, 'Partial payment — collected ₱26.00, still short by ₱234.00', 'cash', 'completed', '2026-04-17 12:26:56', 12, '2026-04-17 12:26:56'),
(83, 27, 21, 100.00, 100.00, 134.00, 'Partial payment — collected ₱100.00, short by ₱134.00 of ₱234.00 balance', 'cash', 'completed', '2026-04-17 18:00:49', 12, '2026-04-17 18:00:49'),
(84, 27, 21, 100.00, 100.00, 34.00, 'Partial payment — collected ₱100.00, short by ₱34.00 of ₱134.00 balance', 'cash', 'completed', '2026-04-17 18:00:54', 12, '2026-04-17 18:00:54'),
(85, 27, 21, 34.00, 120.00, NULL, 'Balance payment collected', 'cash', 'completed', '2026-04-17 18:01:00', 12, '2026-04-17 18:01:00'),
(86, 10, 17, 1000.00, 1000.00, 1680.00, 'Partial payment — collected ₱1,000.00, short by ₱1,680.00 of ₱2,680.00 balance', 'cash', 'completed', '2026-04-17 18:01:23', 12, '2026-04-17 18:01:23'),
(87, 9, 17, 2000.00, 2000.00, 800.00, 'Partial payment — collected ₱2,000.00, short by ₱800.00 of ₱2,800.00 balance', 'cash', 'completed', '2026-04-17 18:01:28', 12, '2026-04-17 18:01:28'),
(88, 8, 17, 200.00, 200.00, 2640.00, 'Partial payment — collected ₱200.00, short by ₱2,640.00 of ₱2,840.00 balance', 'cash', 'completed', '2026-04-17 18:01:46', 12, '2026-04-17 18:01:46'),
(89, 8, 17, 200.00, 200.00, 2440.00, 'Partial payment — collected ₱200.00, short by ₱2,440.00 of ₱2,640.00 balance', 'cash', 'completed', '2026-04-17 18:01:50', 12, '2026-04-17 18:01:50'),
(90, 8, 17, 40.00, 40.00, 2400.00, 'Partial payment — collected ₱40.00, short by ₱2,400.00 of ₱2,440.00 balance', 'cash', 'completed', '2026-04-17 18:01:54', 12, '2026-04-17 18:01:54'),
(91, 6, 17, 200.00, 200.00, 2600.00, 'Partial payment — collected ₱200.00, short by ₱2,600.00 of ₱2,800.00 balance', 'cash', 'completed', '2026-04-17 18:02:19', 12, '2026-04-17 18:02:19'),
(92, 6, 17, 100.00, 100.00, 2500.00, 'Partial payment — collected ₱100.00, short by ₱2,500.00 of ₱2,600.00 balance', 'cash', 'completed', '2026-04-17 18:02:25', 12, '2026-04-17 18:02:25'),
(93, 6, 17, 10.00, 10.00, 2490.00, 'Partial payment — collected ₱10.00, short by ₱2,490.00 of ₱2,500.00 balance', 'cash', 'completed', '2026-04-17 18:02:30', 12, '2026-04-17 18:02:30'),
(94, 6, 17, 100.00, 100.00, 2390.00, 'Partial payment — collected ₱100.00, short by ₱2,390.00 of ₱2,490.00 balance', 'cash', 'completed', '2026-04-17 18:02:35', 12, '2026-04-17 18:02:35'),
(95, 28, 17, 300.00, 300.00, 220.00, 'Short payment at session start — short by ₱220.00', 'cash', 'completed', '2026-04-17 18:02:47', 12, '2026-04-17 18:02:47'),
(96, 28, 17, 100.00, 100.00, 120.00, 'Partial payment — collected ₱100.00, short by ₱120.00 of ₱220.00 balance', 'cash', 'completed', '2026-04-17 18:02:58', 12, '2026-04-17 18:02:58'),
(97, 28, 17, 10.00, 10.00, 110.00, 'Partial payment — collected ₱10.00, short by ₱110.00 of ₱120.00 balance', 'cash', 'completed', '2026-04-17 18:03:09', 12, '2026-04-17 18:03:09'),
(98, 28, 17, 20.00, 20.00, 90.00, 'Partial payment — collected ₱20.00, short by ₱90.00 of ₱110.00 balance', 'cash', 'completed', '2026-04-17 18:03:13', 12, '2026-04-17 18:03:13'),
(99, 28, 17, 0.00, 0.00, 90.00, 'Partial payment — collected ₱0.00, short by ₱90.00 of ₱90.00 balance', 'cash', 'completed', '2026-04-17 18:03:17', 12, '2026-04-17 18:03:17'),
(100, 28, 17, 10.00, 10.00, 80.00, 'Short payment — collected ₱10.00, short by ₱80.00', 'cash', 'completed', '2026-04-17 18:03:30', 12, '2026-04-17 18:03:30'),
(101, 28, 17, 10.00, 10.00, 70.00, 'Partial payment — collected ₱10.00, short by ₱70.00 of ₱80.00 balance', 'cash', 'completed', '2026-04-17 18:03:36', 12, '2026-04-17 18:03:36'),
(102, 28, 17, 70.00, 100.00, NULL, 'Balance payment collected', 'cash', 'completed', '2026-04-17 18:03:40', 12, '2026-04-17 18:03:40'),
(103, 29, 21, 200.00, 200.00, 320.00, 'Short payment at session start — short by ₱320.00', 'cash', 'completed', '2026-04-17 19:09:50', 12, '2026-04-17 19:09:50'),
(104, 29, 21, 100.00, 100.00, 220.00, 'Short payment — collected ₱100.00, short by ₱220.00', 'cash', 'completed', '2026-04-17 19:14:51', 12, '2026-04-17 19:14:51'),
(105, 29, 21, 220.00, 220.00, NULL, 'Balance payment collected', 'cash', 'completed', '2026-04-17 19:15:10', 12, '2026-04-17 19:15:10'),
(106, 30, 21, 23.00, 23.00, 497.00, 'Short payment at session start — short by ₱497.00', 'cash', 'completed', '2026-04-17 19:17:32', 12, '2026-04-17 19:17:32'),
(107, 30, 21, 100.00, 100.00, 397.00, 'Partial payment — collected ₱100.00, short by ₱397.00 of ₱497.00 balance', 'cash', 'completed', '2026-04-17 22:32:20', 12, '2026-04-17 22:32:20'),
(108, 30, 21, 397.00, 400.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-04-17 22:33:07', 12, '2026-04-17 22:33:07'),
(109, 31, 21, 200.00, 200.00, 360.00, 'Short payment at session start — short by ₱360.00', 'cash', 'completed', '2026-04-18 00:31:57', 12, '2026-04-18 00:31:57'),
(110, 31, 21, 360.00, 360.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-04-18 00:32:45', 12, '2026-04-18 00:32:45'),
(111, 32, 17, 20.00, 20.00, 60.00, 'Short payment — collected ₱20.00, short by ₱60.00', 'cash', 'completed', '2026-04-18 16:12:47', 12, '2026-04-18 16:12:47'),
(112, 34, 21, 200.00, 200.00, 40.00, 'Short payment — collected ₱200.00, short by ₱40.00', 'cash', 'completed', '2026-04-18 16:24:32', 12, '2026-04-18 16:24:32'),
(113, 33, 17, 100.00, 100.00, 60.00, 'Short payment — collected ₱100.00, short by ₱60.00', 'cash', 'completed', '2026-04-18 16:24:37', 12, '2026-04-18 16:24:37'),
(114, 34, 21, 40.00, 40.00, NULL, 'Balance payment collected', 'cash', 'completed', '2026-04-18 16:24:45', 12, '2026-04-18 16:24:45'),
(115, 33, 17, 60.00, 60.00, NULL, 'Balance payment collected', 'cash', 'completed', '2026-04-18 16:24:50', 12, '2026-04-18 16:24:50'),
(116, 32, 17, 60.00, 80.00, NULL, 'Balance payment collected', 'cash', 'completed', '2026-04-18 16:24:55', 12, '2026-04-18 16:24:55'),
(117, 37, 17, 200.00, 200.00, 120.00, 'Short payment at session start — short by ₱120.00', 'cash', 'completed', '2026-04-18 16:25:52', 12, '2026-04-18 16:25:52'),
(118, 37, 17, 120.00, 120.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-04-18 16:25:58', 12, '2026-04-18 16:25:58'),
(119, 36, 21, 320.00, 320.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-04-18 16:26:03', 12, '2026-04-18 16:26:03'),
(120, 35, 21, 320.00, 320.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-04-18 16:26:07', 12, '2026-04-18 16:26:07'),
(121, 38, 12, 640.00, 640.00, NULL, 'Balance payment collected', 'cash', 'completed', '2026-04-18 16:41:27', 12, '2026-04-18 16:41:27'),
(122, 39, 17, 60.00, 60.00, NULL, 'Downpayment transferred from reservation #10', 'cash', 'completed', '2026-04-18 16:44:07', 12, '2026-04-18 16:44:07'),
(123, 39, 17, 60.00, 60.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-04-18 17:08:14', 12, '2026-04-18 17:08:14'),
(124, 40, 17, 20.00, 20.00, 580.00, 'Short payment at session start — short by ₱580.00', 'cash', 'completed', '2026-04-18 17:08:28', 12, '2026-04-18 17:08:28'),
(125, 40, 17, 580.00, 580.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-04-18 17:08:33', 12, '2026-04-18 17:08:33'),
(126, 41, 21, 100.00, 100.00, NULL, 'Downpayment transferred from reservation #11', 'cash', 'completed', '2026-04-19 15:38:47', 12, '2026-04-19 15:38:47'),
(127, 41, 21, 100.00, 100.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-04-19 15:41:05', 12, '2026-04-19 15:41:05'),
(128, 42, 21, 204.00, 204.00, 316.00, 'Short payment at session start — short by ₱316.00', 'cash', 'completed', '2026-04-19 15:45:41', 12, '2026-04-19 15:45:41'),
(129, 42, 21, 316.00, 316.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-04-19 15:46:00', 12, '2026-04-19 15:46:00'),
(130, 43, 21, 200.00, 200.00, 360.00, 'Short payment at session start — short by ₱360.00', 'cash', 'completed', '2026-04-19 15:47:02', 12, '2026-04-19 15:47:02'),
(131, 43, 21, -100.00, NULL, NULL, 'Refund issued: Early end – used 1h 05m (₱100.00), refunding unused time (₱100.00)', '', 'completed', '2026-04-19 16:52:17', 12, '2026-04-19 16:52:17'),
(132, 43, 21, -100.00, NULL, NULL, 'Refund issued: Early end – used 1h 05m (₱100.00), refunding unused time (₱100.00)', '', 'completed', '2026-04-19 16:53:42', 12, '2026-04-19 16:53:42'),
(133, 43, 21, -100.00, NULL, NULL, 'Refund issued: Early end – used 1h 05m (₱100.00), refunding unused time (₱100.00)', '', 'completed', '2026-04-19 16:53:47', 12, '2026-04-19 16:53:47'),
(134, 43, 21, -100.00, NULL, NULL, 'Refund issued: Early end – used 1h 05m (₱100.00), refunding unused time (₱100.00)', '', 'completed', '2026-04-19 16:54:03', 12, '2026-04-19 16:54:03'),
(135, 43, 21, -100.00, NULL, NULL, 'Refund issued: Early end – used 1h 05m (₱100.00), refunding unused time (₱100.00)', '', 'completed', '2026-04-19 16:54:05', 12, '2026-04-19 16:54:05'),
(136, 43, 21, -100.00, NULL, NULL, 'Refund issued: Early end – used 1h 05m (₱100.00), refunding unused time (₱100.00)', '', 'completed', '2026-04-19 16:54:34', 12, '2026-04-19 16:54:34'),
(137, 43, 21, -100.00, NULL, NULL, 'Refund issued: Early end – used 1h 05m (₱100.00), refunding unused time (₱100.00)', '', 'completed', '2026-04-19 16:54:39', 12, '2026-04-19 16:54:39'),
(138, 43, 21, -100.00, NULL, NULL, 'Refund issued: Early end – used 1h 05m (₱100.00), refunding unused time (₱100.00)', '', 'completed', '2026-04-19 16:54:57', 12, '2026-04-19 16:54:57'),
(139, 43, 21, -100.00, NULL, NULL, 'Refund issued: Early end – used 1h 05m (₱100.00), refunding unused time (₱100.00)', '', 'completed', '2026-04-19 16:54:59', 12, '2026-04-19 16:54:59'),
(140, 44, 17, 200.00, 200.00, 320.00, 'Short payment at session start — short by ₱320.00', 'cash', 'completed', '2026-04-19 16:58:09', 12, '2026-04-19 16:58:09'),
(141, 44, 17, -200.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 00m (₱0.00), refunding unused time (₱200.00)', '', 'completed', '2026-04-19 16:58:18', 12, '2026-04-19 16:58:18'),
(142, 44, 17, 520.00, 520.00, NULL, 'Balance payment collected', 'cash', 'completed', '2026-04-19 17:13:17', 12, '2026-04-19 17:13:17'),
(143, 43, 21, 1260.00, 1260.00, NULL, 'Balance payment collected', 'cash', 'completed', '2026-04-19 17:13:25', 12, '2026-04-19 17:13:25');

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
(12, 'ljlabianao@gmail.com', '$2y$10$nEuBy0VoWqtbRETOnAD99eurxRHdLaGpxROWA//NGpziKgIG1CmmS', 'lito', '09916310227', 'owner', 'active', 1, NULL, NULL, NULL, NULL, '2026-02-21 20:45:06'),
(17, 'llabianojr@kld.edu.ph', '$2y$10$N97t4MFGjElD3iM.nlpn3uZ/hy1zNC/Lfqi/YozlBB6BG1VWkL.Oe', 'Lito LARGUEZA LABIANO', '09916310227', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-04-12 19:54:25'),
(21, 'hello@gmail.com', '$2y$10$vjAu848ZbS1DpfexUIsM1.x31O/VNLXNwp/sw65NckgmZTHTKLzAO', 'Lito LARGUEZA LABIANO', '09916310227', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-04-17 11:59:29'),
(22, 'admin@gmail.com', '$2y$10$kZCF21cxNKIxXE99XNGyF.nGfT94FZeDaGtbH4YTBOxrpumQLDvIK', 'Admin User', '', 'customer', 'active', 0, '9ad4e0d4b2004d75a763bd2e6e9af695cdb053e39c7cadd65db419783c75b54f', '2026-04-20 16:02:24', NULL, NULL, '2026-04-19 16:02:24');

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
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=144;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

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
