-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 14, 2026 at 11:29 AM
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
  `console_type` enum('PS5','Xbox Series X','PS4') NOT NULL,
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
(3, 'PS5 Unit 3', 'PS5', 'PS5-03', 'in_use', 80.00, '2026-02-21 19:55:41'),
(4, 'PS5 Unit 4', 'PS5', 'PS5-04', 'maintenance', 80.00, '2026-02-21 19:55:41'),
(5, 'PS5 Unit 5', 'PS5', 'PS5-05', 'available', 80.00, '2026-02-21 19:55:41'),
(6, 'PS4 Unit 6', 'PS4', 'PS4-06', 'available', 80.00, '2026-02-21 19:55:41'),
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
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gaming_sessions`
--

INSERT INTO `gaming_sessions` (`session_id`, `user_id`, `console_id`, `rental_mode`, `planned_minutes`, `start_time`, `end_time`, `duration_minutes`, `hourly_rate`, `total_cost`, `status`, `created_by`, `created_at`) VALUES
(1, 17, 1, 'hourly', NULL, '2026-04-14 10:12:13', '2026-04-14 11:42:00', 90, 80.00, 120.00, 'completed', 12, '2026-04-14 11:42:13'),
(2, 17, 1, 'hourly', NULL, '2026-04-14 10:24:52', '2026-04-15 00:20:00', 835, 80.00, 1120.00, 'completed', 12, '2026-04-14 11:44:52'),
(3, 17, 1, 'hourly', 180, '2026-04-14 12:19:37', '2026-04-14 15:25:00', 185, 80.00, 260.00, 'completed', 12, '2026-04-14 12:19:37'),
(4, 18, 1, 'hourly', 30, '2026-04-14 13:03:37', '2026-04-14 13:33:00', 29, 80.00, 50.00, 'completed', 12, '2026-04-14 13:03:37'),
(5, 5, 2, 'hourly', 60, '2026-04-14 13:03:58', '2026-04-14 14:25:34', 82, 80.00, 120.00, 'completed', 12, '2026-04-14 13:03:58'),
(6, 19, 5, 'hourly', 30, '2026-04-14 14:11:38', '2026-04-14 14:11:42', 0, 80.00, 50.00, 'completed', 12, '2026-04-14 14:11:38'),
(7, 18, 1, 'unlimited', NULL, '2026-04-14 14:17:34', '2026-04-14 14:28:25', 11, 80.00, 400.00, 'completed', 12, '2026-04-14 14:17:34'),
(8, 4, 2, 'open_time', NULL, '2026-04-14 14:27:44', '2026-04-14 14:28:22', 1, 80.00, 0.00, 'completed', 12, '2026-04-14 14:27:44'),
(9, 4, 3, 'hourly', 150, '2026-04-14 14:28:04', '2026-04-14 14:28:26', 0, 80.00, 200.00, 'completed', 12, '2026-04-14 14:28:04'),
(10, 18, 1, 'hourly', 30, '2026-04-14 14:29:16', '2026-04-14 15:54:19', 85, 80.00, 130.00, 'completed', 12, '2026-04-14 14:29:16'),
(11, 3, 2, 'hourly', 60, '2026-04-14 14:39:29', '2026-04-14 14:39:39', 0, 80.00, 80.00, 'completed', 12, '2026-04-14 14:39:29'),
(12, 3, 2, 'hourly', 210, '2026-04-14 14:42:09', '2026-04-14 15:54:35', 72, 80.00, 280.00, 'completed', 12, '2026-04-14 14:42:09'),
(13, 3, 2, 'hourly', 210, '2026-04-14 15:05:39', '2026-04-14 16:26:04', 80, 80.00, 280.00, 'completed', 12, '2026-04-14 15:05:39'),
(14, 3, 2, 'hourly', 210, '2026-04-14 15:14:11', '2026-04-14 16:26:02', 72, 80.00, 280.00, 'completed', 12, '2026-04-14 15:14:11'),
(15, 3, 2, 'hourly', 210, '2026-04-14 15:14:13', '2026-04-14 16:26:00', 72, 80.00, 280.00, 'completed', 12, '2026-04-14 15:14:13'),
(16, 3, 2, 'hourly', 210, '2026-04-14 15:14:19', '2026-04-14 16:19:47', 65, 80.00, 280.00, 'completed', 12, '2026-04-14 15:14:19'),
(17, 3, 2, 'hourly', 210, '2026-04-14 15:15:19', '2026-04-14 15:59:14', 44, 80.00, 280.00, 'completed', 12, '2026-04-14 15:15:19'),
(18, 3, 2, 'hourly', 210, '2026-04-14 15:15:22', '2026-04-14 15:54:14', 39, 80.00, 280.00, 'completed', 12, '2026-04-14 15:15:22'),
(19, 3, 2, 'hourly', 210, '2026-04-14 15:15:41', '2026-04-14 15:54:09', 38, 80.00, 280.00, 'completed', 12, '2026-04-14 15:15:41'),
(20, 3, 2, 'hourly', 210, '2026-04-14 15:36:27', '2026-04-14 15:54:08', 18, 80.00, 280.00, 'completed', 12, '2026-04-14 15:36:27'),
(21, 3, 2, 'hourly', 210, '2026-04-14 15:37:13', '2026-04-14 15:54:06', 17, 80.00, 280.00, 'completed', 12, '2026-04-14 15:37:13'),
(22, 3, 2, 'hourly', 210, '2026-04-14 15:43:32', '2026-04-14 15:53:31', 10, 80.00, 280.00, 'completed', 12, '2026-04-14 15:43:32'),
(23, 4, 1, 'hourly', 30, '2026-04-14 15:21:12', '2026-04-14 16:44:00', 83, 80.00, 130.00, 'completed', 12, '2026-04-14 16:12:02'),
(24, 3, 1, 'hourly', 240, '2026-04-14 16:21:19', '2026-04-14 16:25:58', 5, 80.00, 320.00, 'completed', 12, '2026-04-14 16:21:19'),
(25, 3, 2, 'hourly', 30, '2026-04-14 15:23:42', '2026-04-14 16:56:23', 93, 80.00, 130.00, 'completed', 12, '2026-04-14 16:23:42'),
(26, 17, 1, 'hourly', 30, '2026-04-14 16:37:26', '2026-04-14 17:05:25', 28, 80.00, 50.00, 'completed', 12, '2026-04-14 16:37:26'),
(27, 18, 2, 'hourly', 30, '2026-04-14 16:55:13', '2026-04-14 17:05:21', 10, 80.00, 50.00, 'completed', 12, '2026-04-14 16:55:13'),
(28, 3, 3, 'hourly', 150, '2026-04-14 16:58:05', '2026-04-14 17:05:16', 7, 80.00, 200.00, 'completed', 12, '2026-04-14 16:58:05'),
(29, 4, 2, 'hourly', 30, '2026-04-14 17:05:02', '2026-04-14 17:05:07', 0, 80.00, 50.00, 'completed', 12, '2026-04-14 17:05:02'),
(30, 3, 3, 'hourly', 420, '2026-04-14 17:06:31', NULL, NULL, 80.00, NULL, 'active', 12, '2026-04-14 17:06:31'),
(31, 3, 2, 'hourly', 480, '2026-04-14 17:20:32', NULL, NULL, 80.00, NULL, 'active', 12, '2026-04-14 17:20:32');

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

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`report_id`, `report_type`, `generated_by`, `date_from`, `date_to`, `file_path`, `created_at`) VALUES
(1, 'daily_sales', 1, '2026-02-20', '2026-02-20', NULL, '2026-02-21 19:55:41'),
(2, 'console_usage', 1, '2026-02-01', '2026-02-20', NULL, '2026-02-21 19:55:41'),
(3, 'rental_records', 1, '2026-02-01', '2026-02-21', NULL, '2026-02-21 19:55:41');

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

--
-- Dumping data for table `tournaments`
--

INSERT INTO `tournaments` (`tournament_id`, `tournament_name`, `game_id`, `console_type`, `start_date`, `end_date`, `entry_fee`, `prize_pool`, `max_participants`, `status`, `announcement`, `created_at`) VALUES
(1, 'NBA 2K25 Monthly Showdown', 1, 'PS5', '2026-03-01 13:00:00', '2026-03-01 20:00:00', 100.00, 3000.00, 16, 'upcoming', 'Join our monthly NBA 2K25 tournament! ₱100 entry fee with ₱3,000 prize pool. Register now at the shop or through the website.', '2026-02-21 19:55:41'),
(2, 'Tekken 8 Fight Night', 5, 'PS5', '2026-02-15 14:00:00', '2026-02-15 21:00:00', 50.00, 1500.00, 32, 'completed', 'Tekken 8 tournament completed! Congratulations to all winners!', '2026-02-21 19:55:41'),
(3, 'COD Warzone Battle', 3, 'Xbox Series X', '2026-03-15 10:00:00', '2026-03-15 18:00:00', 75.00, 2000.00, 20, 'upcoming', 'Call of Duty tournament coming soon! Team up and compete for ₱2,000!', '2026-02-21 19:55:41');

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

--
-- Dumping data for table `tournament_participants`
--

INSERT INTO `tournament_participants` (`participant_id`, `tournament_id`, `user_id`, `registration_date`, `payment_status`, `placement`, `prize_amount`) VALUES
(1, 2, 3, '2026-02-13 10:00:00', 'paid', 1, 800.00),
(2, 2, 4, '2026-02-13 11:00:00', 'paid', 2, 450.00),
(3, 2, 5, '2026-02-14 09:00:00', 'paid', 3, 250.00),
(4, 1, 3, '2026-02-20 15:00:00', 'paid', NULL, NULL),
(5, 1, 4, '2026-02-21 10:00:00', 'pending', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('cash','gcash','credit_card') NOT NULL DEFAULT 'cash',
  `payment_status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `transaction_date` datetime NOT NULL DEFAULT current_timestamp(),
  `processed_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transaction_id`, `session_id`, `user_id`, `amount`, `payment_method`, `payment_status`, `transaction_date`, `processed_by`, `created_at`) VALUES
(1, 1, 17, 120.00, 'cash', 'completed', '2026-04-14 11:42:57', 12, '2026-04-14 11:42:57'),
(2, 2, 17, 1120.00, 'cash', 'completed', '2026-04-14 11:46:26', 12, '2026-04-14 11:46:26'),
(3, 3, 17, 260.00, 'cash', 'completed', '2026-04-14 12:20:27', 12, '2026-04-14 12:20:27'),
(4, 4, 18, 50.00, 'cash', 'completed', '2026-04-14 13:42:08', 12, '2026-04-14 13:42:08'),
(5, 6, 19, 50.00, 'cash', 'completed', '2026-04-14 14:11:42', 12, '2026-04-14 14:11:42'),
(6, 5, 5, 120.00, 'cash', 'completed', '2026-04-14 14:25:34', 12, '2026-04-14 14:25:34'),
(7, 8, 4, 0.00, 'cash', 'completed', '2026-04-14 14:28:23', 12, '2026-04-14 14:28:23'),
(8, 7, 18, 400.00, 'cash', 'completed', '2026-04-14 14:28:25', 12, '2026-04-14 14:28:25'),
(9, 9, 4, 200.00, 'cash', 'completed', '2026-04-14 14:28:26', 12, '2026-04-14 14:28:26'),
(10, 11, 3, 80.00, 'cash', 'completed', '2026-04-14 14:39:29', 12, '2026-04-14 14:39:29'),
(11, 12, 3, 280.00, 'cash', 'completed', '2026-04-14 14:42:09', 12, '2026-04-14 14:42:09'),
(12, 22, 3, 280.00, 'cash', 'completed', '2026-04-14 15:53:31', 12, '2026-04-14 15:53:31'),
(13, 21, 3, 280.00, 'cash', 'completed', '2026-04-14 15:54:06', 12, '2026-04-14 15:54:06'),
(14, 20, 3, 280.00, 'cash', 'completed', '2026-04-14 15:54:08', 12, '2026-04-14 15:54:08'),
(15, 19, 3, 280.00, 'cash', 'completed', '2026-04-14 15:54:09', 12, '2026-04-14 15:54:09'),
(16, 18, 3, 280.00, 'cash', 'completed', '2026-04-14 15:54:14', 12, '2026-04-14 15:54:14'),
(17, 10, 18, 130.00, 'cash', 'completed', '2026-04-14 15:54:19', 12, '2026-04-14 15:54:19'),
(18, 17, 3, 280.00, 'cash', 'completed', '2026-04-14 15:59:14', 12, '2026-04-14 15:59:14'),
(19, 23, 4, 130.00, 'cash', 'completed', '2026-04-14 16:14:21', 12, '2026-04-14 16:14:21'),
(20, 16, 3, 280.00, 'cash', 'completed', '2026-04-14 16:19:47', 12, '2026-04-14 16:19:47'),
(21, 24, 3, 320.00, 'cash', 'completed', '2026-04-14 16:21:19', 12, '2026-04-14 16:21:19'),
(22, 15, 3, 280.00, 'cash', 'completed', '2026-04-14 16:26:00', 12, '2026-04-14 16:26:00'),
(23, 14, 3, 280.00, 'cash', 'completed', '2026-04-14 16:26:02', 12, '2026-04-14 16:26:02'),
(24, 13, 3, 280.00, 'cash', 'completed', '2026-04-14 16:26:04', 12, '2026-04-14 16:26:04'),
(25, 25, 3, 130.00, 'cash', 'completed', '2026-04-14 16:56:23', 12, '2026-04-14 16:56:23'),
(26, 29, 4, 50.00, 'cash', 'completed', '2026-04-14 17:05:07', 12, '2026-04-14 17:05:07'),
(27, 28, 3, 200.00, 'cash', 'completed', '2026-04-14 17:05:16', 12, '2026-04-14 17:05:16'),
(28, 27, 18, 50.00, 'cash', 'completed', '2026-04-14 17:05:21', 12, '2026-04-14 17:05:21'),
(29, 26, 17, 50.00, 'cash', 'completed', '2026-04-14 17:05:25', 12, '2026-04-14 17:05:25'),
(30, 30, 3, 560.00, 'cash', 'completed', '2026-04-14 17:06:31', 12, '2026-04-14 17:06:31'),
(31, 31, 3, 640.00, 'cash', 'completed', '2026-04-14 17:20:32', 12, '2026-04-14 17:20:32');

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
(1, 'owner@goodspot.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan Dela Cruz', '09171234567', 'owner', 'active', 1, NULL, NULL, NULL, NULL, '2026-02-21 19:55:40'),
(2, 'shopkeeper@goodspot.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria Santos', '09181234567', 'shopkeeper', 'active', 1, NULL, NULL, NULL, NULL, '2026-02-21 19:55:40'),
(3, 'carlos@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carlos Reyes', '09191234567', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-02-21 19:55:40'),
(4, 'anna@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Anna Garcia', '09201234567', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-02-21 19:55:40'),
(5, 'mark@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mark Bautista', '09211234567', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-02-21 19:55:40'),
(12, 'ljlabianao@gmail.com', '$2y$10$nEuBy0VoWqtbRETOnAD99eurxRHdLaGpxROWA//NGpziKgIG1CmmS', 'lito', '09916310227', 'shopkeeper', 'active', 1, NULL, NULL, NULL, NULL, '2026-02-21 20:45:06'),
(17, 'llabianojr@kld.edu.ph', '$2y$10$N97t4MFGjElD3iM.nlpn3uZ/hy1zNC/Lfqi/YozlBB6BG1VWkL.Oe', 'Lito LARGUEZA LABIANO', '09916310227', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-04-12 19:54:25'),
(18, 'casansdkas@dasm.csaosa', '$2y$10$tV7HN.E35YZut4Zl4SNdQuUAq0Bw0goQKPSthSwMyavH3rNEpDypi', 'kry', '', 'customer', 'active', 0, '5b19a837b7908226bef29dc2bf76e46ad734ec0638087eb5d3156f2c9527e451', '2026-04-15 02:36:15', NULL, NULL, '2026-04-14 08:36:15'),
(19, 'dzong@kld.edu.ph', '$2y$10$Tb6WBsj2hjwvHh97ihRFnu4s77TJLEgVk1IBVWsRZM7Ldz0Tuz5vu', 'zeus', '', 'customer', 'active', 1, '6e1acddd2b2d56f157ac13cb34162bde6e74d0b7d94ebe82df97119b7cd7cb4c', '2026-04-15 02:38:31', NULL, NULL, '2026-04-14 08:38:31');

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
  ADD KEY `fk_sessions_created_by` (`created_by`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `idx_reports_type` (`report_type`),
  ADD KEY `idx_reports_date` (`created_at`),
  ADD KEY `fk_reports_user` (`generated_by`);

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
  MODIFY `console_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `gaming_sessions`
--
ALTER TABLE `gaming_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tournaments`
--
ALTER TABLE `tournaments`
  MODIFY `tournament_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tournament_participants`
--
ALTER TABLE `tournament_participants`
  MODIFY `participant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

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
