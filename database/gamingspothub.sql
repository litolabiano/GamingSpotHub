-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 04, 2026 at 03:40 PM
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
(3, 'PS5 Unit 3', 'PS5', 'PS5-03', 'in_use', 80.00, '2026-02-21 19:55:41'),
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
  `extended_minutes` int(11) NOT NULL DEFAULT 0 COMMENT 'Total minutes added via approved extensions',
  `source_reservation_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gaming_sessions`
--

INSERT INTO `gaming_sessions` (`session_id`, `user_id`, `console_id`, `rental_mode`, `planned_minutes`, `start_time`, `end_time`, `duration_minutes`, `hourly_rate`, `total_cost`, `status`, `payment_status`, `created_by`, `created_at`, `extended_minutes`, `source_reservation_id`) VALUES
(1, 27, 2, 'hourly', 30, '2026-05-02 00:03:59', '2026-05-02 00:04:17', 0, 80.00, 0.00, 'completed', NULL, 12, '2026-05-02 00:03:59', 0, NULL),
(2, 22, 1, 'hourly', 90, '2026-05-02 00:04:39', '2026-05-02 00:17:04', 12, 80.00, 20.00, 'completed', NULL, 12, '2026-05-02 00:04:39', 60, NULL),
(3, 22, 2, 'hourly', 30, '2026-05-02 15:20:18', '2026-05-02 15:20:27', 0, 80.00, 0.00, 'completed', NULL, 12, '2026-05-02 15:20:18', 0, NULL),
(4, 26, 2, 'hourly', 60, '2026-05-02 17:13:57', '2026-05-03 19:35:17', 1581, 80.00, 1720.00, 'completed', NULL, 12, '2026-05-02 17:13:57', 0, NULL),
(5, 0, 1, 'hourly', 30, '2026-05-02 17:14:37', '2026-05-03 19:35:15', 1581, 80.00, 1730.00, 'completed', NULL, 12, '2026-05-02 17:14:37', 0, NULL),
(6, 27, 1, 'hourly', 90, '2026-05-03 21:14:25', '2026-05-03 21:15:37', 1, 80.00, 0.00, 'completed', NULL, 12, '2026-05-03 21:14:25', 0, NULL),
(7, 27, 1, 'hourly', 240, '2026-05-03 21:25:08', '2026-05-03 21:25:46', 1, 80.00, 0.00, 'completed', NULL, 12, '2026-05-03 21:25:08', 0, NULL),
(8, 27, 2, 'hourly', 60, '2026-05-03 21:37:14', '2026-05-03 21:56:25', 19, 80.00, 20.00, 'completed', NULL, 12, '2026-05-03 21:37:14', 0, NULL),
(9, 27, 1, 'hourly', 30, '2026-05-03 22:11:20', '2026-05-03 22:12:57', 2, 80.00, 0.00, 'completed', NULL, 12, '2026-05-03 22:11:20', 0, NULL),
(10, 27, 3, 'hourly', 60, '2026-05-03 22:13:39', '2026-05-03 22:14:03', 0, 80.00, 0.00, 'completed', NULL, 12, '2026-05-03 22:13:39', 0, NULL),
(11, 27, 2, 'hourly', 210, '2026-05-03 22:15:30', '2026-05-03 22:15:52', 0, 80.00, 0.00, 'completed', NULL, 12, '2026-05-03 22:15:30', 0, NULL),
(12, 27, 1, 'hourly', 210, '2026-05-03 22:25:57', '2026-05-03 23:17:12', 51, 80.00, 80.00, 'completed', NULL, 12, '2026-05-03 22:25:57', 0, NULL),
(13, 0, 3, 'hourly', 90, '2026-05-03 22:27:04', '2026-05-03 23:17:05', 50, 80.00, 80.00, 'completed', NULL, 12, '2026-05-03 22:27:04', 0, NULL),
(14, 27, 2, 'hourly', 90, '2026-05-03 23:18:12', '2026-05-03 23:19:11', 1, 80.00, 0.00, 'completed', NULL, 12, '2026-05-03 23:18:12', 0, 15),
(15, 27, 3, 'hourly', 90, '2026-05-03 23:48:46', '2026-05-03 23:49:03', 0, 80.00, 0.00, 'completed', NULL, 12, '2026-05-03 23:48:46', 0, 16),
(16, 26, 3, 'hourly', 60, '2026-05-03 23:49:15', '2026-05-03 23:49:22', 0, 80.00, 0.00, 'completed', NULL, 12, '2026-05-03 23:49:15', 0, NULL),
(17, 27, 2, 'hourly', 60, '2026-05-04 00:00:39', '2026-05-04 00:01:08', 0, 80.00, 0.00, 'completed', NULL, 12, '2026-05-04 00:00:39', 0, 17),
(18, 27, 2, 'hourly', 60, '2026-05-04 00:06:31', '2026-05-04 00:19:13', 13, 80.00, 20.00, 'completed', NULL, 12, '2026-05-04 00:06:31', 0, 18),
(19, 0, 3, 'open_time', NULL, '2026-05-04 16:19:39', NULL, NULL, 80.00, NULL, 'active', NULL, 12, '2026-05-04 16:19:39', 0, NULL),
(20, 0, 2, 'hourly', 300, '2026-05-04 16:23:45', NULL, NULL, 80.00, NULL, 'active', NULL, 12, '2026-05-04 16:23:45', 0, NULL);

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
  `payment_proof` varchar(255) DEFAULT NULL,
  `payment_proof_status` enum('pending','verified','rejected') DEFAULT NULL,
  `paymongo_source_id` varchar(60) DEFAULT NULL,
  `paymongo_payment_id` varchar(60) DEFAULT NULL,
  `paymongo_status` varchar(30) DEFAULT NULL,
  `status` enum('pending','confirmed','converted','cancelled','no_show') NOT NULL DEFAULT 'pending',
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

INSERT INTO `reservations` (`reservation_id`, `user_id`, `console_id`, `console_type`, `rental_mode`, `planned_minutes`, `reserved_date`, `reserved_time`, `notes`, `downpayment_amount`, `downpayment_method`, `downpayment_paid`, `payment_proof`, `payment_proof_status`, `paymongo_source_id`, `paymongo_payment_id`, `paymongo_status`, `status`, `created_by`, `created_at`, `updated_at`, `cancelled_by`, `cancellation_reason`, `refund_issued`, `cancel_reason_type`, `cancel_reason_detail`) VALUES
(1, 27, 2, 'PS5', 'hourly', 180, '2026-05-01', '21:30:00', NULL, 32.00, 'gcash', 1, NULL, NULL, 'src_1vDGQX8cNyVFSX9nhPTz6AjN', 'pay_G6MHwN8GKN4Dzx5yVVga8DCL', 'paid', 'cancelled', 27, '2026-05-01 19:56:50', '2026-05-01 19:57:31', 'user', NULL, 0, 'schedule_change', NULL),
(2, 27, 1, 'PS5', 'hourly', 60, '2026-05-01', '22:30:00', NULL, 24.00, 'gcash', 1, NULL, NULL, 'src_zBnQc83ZGDNc3FYmLcMMa4Xw', 'pay_pZACwQPXNStvFBB2uM9jYss6', 'paid', 'cancelled', 27, '2026-05-01 20:48:35', '2026-05-01 20:49:16', 'admin', NULL, 0, 'admin_decision', 'event that day nigga'),
(3, 27, 2, 'PS5', 'hourly', 30, '2026-05-02', '12:30:00', NULL, 23.00, 'gcash', 1, NULL, NULL, 'src_4keDrbFouD9ap4MLQg2PRAho', 'pay_ww8WyKP28SU2H3pazuCo1XhF', 'paid', 'converted', 27, '2026-05-02 00:02:46', '2026-05-02 00:03:59', NULL, NULL, 0, NULL, NULL),
(4, 27, 1, 'PS5', 'hourly', 90, '2026-05-03', '22:00:00', NULL, 26.00, 'gcash', 1, NULL, NULL, 'src_MfhyN3EDUNjuD6i35gxsfhMT', 'pay_3aeHb5pm7RwHwdBGuToffWkx', 'paid', 'cancelled', 27, '2026-05-03 19:43:07', '2026-05-03 19:43:41', 'user', NULL, 0, 'budget_issue', NULL),
(5, 27, 1, 'PS5', 'hourly', 60, '2026-05-03', '22:30:00', NULL, 24.00, 'gcash', 1, NULL, NULL, 'cs_e268b1c8bda2bfd037c0526e', 'pay_pKVPRS3NSkCDRbjPNuffkRLo', 'paid', 'cancelled', 27, '2026-05-03 20:48:38', '2026-05-03 20:49:26', 'user', NULL, 0, 'other', 'no'),
(6, 27, 1, 'PS5', 'hourly', 60, '2026-05-03', '22:30:00', NULL, 24.00, 'gcash', 1, NULL, NULL, 'cs_e5a46a718ca4836d1fe4fc7d', 'pay_cPLucGDC6uLtH9zyGL6B4kqg', 'paid', 'cancelled', 27, '2026-05-03 20:49:58', '2026-05-03 21:12:23', 'user', NULL, 0, 'budget_issue', NULL),
(7, 27, 1, 'PS5', 'hourly', 90, '2026-05-03', '23:00:00', NULL, 26.00, 'gcash', 1, NULL, NULL, 'cs_c8d96e4918a03316c59fd881', 'pay_H3z7AXcMmAGfcRiA9jdKfCjY', 'paid', 'converted', 27, '2026-05-03 21:13:25', '2026-05-03 21:14:25', NULL, NULL, 0, NULL, NULL),
(8, 27, 1, 'PS5', 'hourly', 210, '2026-05-03', '23:00:00', NULL, 34.00, 'gcash', 1, NULL, NULL, 'cs_0d94b6d85f9f1ae405789275', 'pay_qRUSNbjR3bKrhRToNPR91apK', 'paid', 'cancelled', 27, '2026-05-03 21:20:53', '2026-05-03 21:23:35', 'admin', NULL, 0, 'found_alternative', NULL),
(9, 27, 1, 'PS5', 'hourly', 210, '2026-05-03', '23:00:00', NULL, 34.00, 'gcash', 1, NULL, NULL, 'cs_f843554097d90778b5f7033b', 'pay_xcbGmV7xAn59b5gWwkJPXg95', 'paid', 'converted', 27, '2026-05-03 21:24:47', '2026-05-03 21:25:08', NULL, NULL, 0, NULL, NULL),
(10, 27, 2, 'PS5', 'hourly', 60, '2026-05-03', '23:00:00', NULL, 24.00, 'gcash', 1, NULL, NULL, 'cs_cd82316260eb1d9948a482f8', 'pay_5beLD8PfnZC4sDeL47eKgS7v', 'paid', 'converted', 27, '2026-05-03 21:33:03', '2026-05-03 21:37:14', NULL, NULL, 0, NULL, NULL),
(11, 27, 1, 'PS5', 'hourly', 30, '2026-05-14', '15:30:00', NULL, 23.00, 'gcash', 1, NULL, NULL, 'cs_867f35e47c8efa0dde09d059', 'pay_pzX1L3727mqG3KVkoNpuT5wy', 'paid', 'converted', 27, '2026-05-03 21:55:58', '2026-05-03 22:11:20', NULL, NULL, 0, NULL, NULL),
(12, 27, 3, 'PS5', 'hourly', 60, '2026-05-04', '13:30:00', NULL, 24.00, 'gcash', 1, NULL, NULL, 'cs_dc30e2e144bb052cec611757', 'pay_tuyuY32Ey4aECyqn3f3nJhLf', 'paid', 'converted', 27, '2026-05-03 22:12:13', '2026-05-03 22:13:39', NULL, NULL, 0, NULL, NULL),
(13, 27, 2, 'PS5', 'hourly', 180, '2026-05-05', '19:30:00', NULL, 32.00, 'gcash', 1, NULL, NULL, 'cs_0fe0514425d874f724ff76a7', 'pay_amVEDoZphF9FSQFvKmYzLFDG', 'paid', 'converted', 27, '2026-05-03 22:15:16', '2026-05-03 22:15:30', NULL, NULL, 0, NULL, NULL),
(14, 27, 1, 'PS5', 'hourly', 180, '2026-05-04', '17:30:00', NULL, 32.00, 'gcash', 1, NULL, NULL, 'cs_ac046727b57767b94272bd30', 'pay_KXUX2wxhPA348gFyVG4ZBK6r', 'paid', 'converted', 27, '2026-05-03 22:25:46', '2026-05-03 22:25:57', NULL, NULL, 0, NULL, NULL),
(15, 27, 2, 'PS5', 'hourly', 90, '2026-05-04', '14:00:00', NULL, 26.00, 'gcash', 1, NULL, NULL, 'cs_3d4c7589f7561c89a5d3b910', 'pay_65kuF9MG31YkmWFAEBGbMXAs', 'paid', 'converted', 27, '2026-05-03 23:17:48', '2026-05-03 23:18:12', NULL, NULL, 0, NULL, NULL),
(16, 27, 3, 'PS5', 'hourly', 90, '2026-05-04', '14:00:00', NULL, 26.00, 'gcash', 1, NULL, NULL, 'cs_6749205be6bf853f265a6e0d', 'pay_ZEzz1kFLLiDh8mBkgacu2DYH', 'paid', 'converted', 27, '2026-05-03 23:47:59', '2026-05-03 23:48:46', NULL, NULL, 0, NULL, NULL),
(17, 27, 2, 'PS5', 'hourly', 60, '2026-05-04', '20:00:00', NULL, 24.00, 'gcash', 1, NULL, NULL, 'cs_1c19b40fe31000856dfe0a4a', 'pay_vjuMy7GyQLKZKMvVyMQeNDo9', 'paid', 'converted', 27, '2026-05-03 23:58:29', '2026-05-04 00:00:39', NULL, NULL, 0, NULL, NULL),
(18, 27, 2, 'PS5', 'hourly', 60, '2026-05-04', '16:00:00', NULL, 24.00, 'gcash', 1, NULL, NULL, 'cs_6a402e098b0a6daaa6bd53a4', 'pay_g59YUP1QhjwSSrJCC6PTL86Y', 'paid', 'converted', 27, '2026-05-04 00:05:24', '2026-05-04 00:06:31', NULL, NULL, 0, NULL, NULL),
(19, 27, 2, 'PS5', 'hourly', 60, '2026-05-04', '17:00:00', NULL, 24.00, 'gcash', 1, NULL, NULL, 'cs_c54236ebb6a4a7bd7d172ea5', 'pay_sMDHBZden2yDv2FDdhNh4bEw', 'paid', 'cancelled', 27, '2026-05-04 12:35:27', '2026-05-04 12:36:49', 'user', NULL, 0, 'found_alternative', NULL),
(20, 27, 1, 'PS5', 'hourly', 60, '2026-05-04', '18:00:00', NULL, 24.00, 'gcash', 1, NULL, NULL, 'cs_921f735bfe2e40ff309d2f78', 'pay_NfEpfdgWf73dXVLTnEak1MNZ', 'paid', 'confirmed', 27, '2026-05-04 16:11:04', '2026-05-04 16:16:07', NULL, NULL, 0, NULL, NULL);

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
  `console_type` enum('PS5','PS4','Xbox Series X') NOT NULL,
  `rental_mode` enum('hourly','open_time','unlimited') NOT NULL DEFAULT 'hourly',
  `reserved_date` date NOT NULL,
  `downpayment_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `refund_issued` tinyint(1) NOT NULL DEFAULT 0,
  `cancelled_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reservation_cancellations`
--

INSERT INTO `reservation_cancellations` (`cancel_id`, `reservation_id`, `user_id`, `cancelled_by`, `cancel_reason_type`, `cancel_reason_detail`, `console_type`, `rental_mode`, `reserved_date`, `downpayment_amount`, `refund_issued`, `cancelled_at`) VALUES
(1, 1, 27, 'user', 'schedule_change', NULL, 'PS5', 'hourly', '2026-05-01', 32.00, 0, '2026-05-01 19:57:31'),
(2, 2, 27, 'admin', 'admin_decision', 'event that day nigga', 'PS5', 'hourly', '2026-05-01', 24.00, 0, '2026-05-01 20:49:16'),
(3, 4, 27, 'user', 'budget_issue', NULL, 'PS5', 'hourly', '2026-05-03', 26.00, 0, '2026-05-03 19:43:41'),
(4, 5, 27, 'user', 'other', 'no', 'PS5', 'hourly', '2026-05-03', 24.00, 0, '2026-05-03 20:49:26'),
(5, 6, 27, 'user', 'budget_issue', NULL, 'PS5', 'hourly', '2026-05-03', 24.00, 0, '2026-05-03 21:12:23'),
(6, 8, 27, 'admin', 'found_alternative', NULL, 'PS5', 'hourly', '2026-05-03', 34.00, 0, '2026-05-03 21:23:35'),
(7, 19, 27, 'user', 'found_alternative', NULL, 'PS5', 'hourly', '2026-05-04', 24.00, 0, '2026-05-04 12:36:49');

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
  `new_date` date NOT NULL,
  `new_time` time NOT NULL,
  `reason` enum('typhoon','power_outage','emergency','maintenance','other') NOT NULL DEFAULT 'other',
  `reason_detail` varchar(300) DEFAULT NULL,
  `rescheduled_by` int(11) NOT NULL,
  `seen_by_user` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(2, 2, 12, 12, 30, 40.00, 'cash', 'approved', 'Direct extension by staff', '2026-05-02 00:16:53', '2026-05-02 00:16:53');

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
(24, 'session_min_charge', '50', NULL, '2026-04-30 00:23:53'),
(152, 'gcash_number', '09XX-XXX-XXXX', 'Shop GCash number displayed on reservation payment screen', '2026-04-28 19:21:02');

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

--
-- Dumping data for table `tournaments`
--

INSERT INTO `tournaments` (`tournament_id`, `tournament_name`, `game_id`, `game_name`, `console_type`, `start_date`, `end_date`, `entry_fee`, `prize_pool`, `max_participants`, `status`, `announcement`, `created_by`, `created_at`) VALUES
(1, 'tekken 7', NULL, 'tekken 7', 'PS5', '2026-05-09 08:00:00', '2026-05-15 19:00:00', 500.00, 0.00, 16, 'cancelled', 'join na', 12, '2026-05-02 15:04:42');

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
(1, 1, 27, '2026-05-02 15:14:51', 'paid', NULL, NULL, 'dark fist', '09916310227', NULL, NULL, NULL, 'note', 'src_Cymsv6YwZLgaJhwZhidpawe7', 'pay_3g5WGQgYiqiZwWXQP4vTS24U', 'paid');

-- --------------------------------------------------------

--
-- Table structure for table `tournament_registrations`
--

CREATE TABLE `tournament_registrations` (
  `reg_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `ign` varchar(100) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `gcash_proof` varchar(255) NOT NULL,
  `tournament` varchar(150) NOT NULL DEFAULT 'Tekken 8 Tournament',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `registered_at` datetime NOT NULL DEFAULT current_timestamp()
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
(1, NULL, 27, 32.00, 32.00, NULL, 'Downpayment for reservation #1', 'gcash', 'completed', '2026-05-01 19:56:50', 27, '2026-05-01 19:56:50'),
(2, NULL, 27, 24.00, 24.00, NULL, 'Downpayment for reservation #2', 'gcash', 'completed', '2026-05-01 20:48:35', 27, '2026-05-01 20:48:35'),
(3, NULL, 27, 23.00, 23.00, NULL, 'Downpayment for reservation #3', 'gcash', 'completed', '2026-05-02 00:02:46', 27, '2026-05-02 00:02:46'),
(4, 1, 27, 23.00, 23.00, NULL, 'Downpayment transferred from reservation #3', 'gcash', 'completed', '2026-05-02 00:03:59', 12, '2026-05-02 00:03:59'),
(5, 1, 27, -23.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 00m (₱0.00), refunding unused time (₱23.00)', '', 'completed', '2026-05-02 00:04:17', 12, '2026-05-02 00:04:17'),
(6, 2, 22, 50.00, 50.00, NULL, NULL, 'cash', 'completed', '2026-05-02 00:04:39', 12, '2026-05-02 00:04:39'),
(7, 2, 22, 40.00, 40.00, NULL, 'Extension +30 min via staff (Extension #1)', 'cash', 'completed', '2026-05-02 00:16:43', 12, '2026-05-02 00:16:43'),
(8, 2, 22, 40.00, 40.00, NULL, 'Extension +30 min via staff (Extension #2)', 'cash', 'completed', '2026-05-02 00:16:53', 12, '2026-05-02 00:16:53'),
(9, 2, 22, -110.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 12m (₱20.00), refunding unused time (₱110.00)', '', 'completed', '2026-05-02 00:17:05', 12, '2026-05-02 00:17:05'),
(10, 3, 22, 50.00, 50.00, NULL, NULL, 'cash', 'completed', '2026-05-02 15:20:18', 12, '2026-05-02 15:20:18'),
(11, 3, 22, -50.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 00m (₱0.00), refunding unused time (₱50.00)', '', 'completed', '2026-05-02 15:20:27', 12, '2026-05-02 15:20:27'),
(12, 4, 26, 80.00, 80.00, NULL, NULL, 'cash', 'completed', '2026-05-02 17:13:57', 12, '2026-05-02 17:13:57'),
(13, 5, 0, 50.00, 50.00, NULL, NULL, 'cash', 'completed', '2026-05-02 17:14:37', 12, '2026-05-02 17:14:37'),
(14, 5, 0, 1680.00, 1680.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-05-03 19:35:15', 12, '2026-05-03 19:35:15'),
(15, 4, 26, 1640.00, 1640.00, NULL, 'Balance payment collected at session end', 'cash', 'completed', '2026-05-03 19:35:17', 12, '2026-05-03 19:35:17'),
(16, NULL, 27, 26.00, 26.00, NULL, 'Downpayment for reservation #4', 'gcash', 'completed', '2026-05-03 19:43:07', 27, '2026-05-03 19:43:07'),
(17, NULL, 27, 24.00, 24.00, NULL, 'Downpayment for reservation #5', 'gcash', 'completed', '2026-05-03 20:48:38', 27, '2026-05-03 20:48:38'),
(18, NULL, 27, 24.00, 24.00, NULL, 'Downpayment for reservation #6', 'gcash', 'completed', '2026-05-03 20:49:58', 27, '2026-05-03 20:49:58'),
(19, NULL, 27, 26.00, 26.00, NULL, 'Downpayment for reservation #7', 'gcash', 'completed', '2026-05-03 21:13:25', 27, '2026-05-03 21:13:25'),
(20, 6, 27, 26.00, 26.00, NULL, 'Downpayment transferred from reservation #7', 'gcash', 'completed', '2026-05-03 21:14:25', 12, '2026-05-03 21:14:25'),
(21, 6, 27, -26.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 01m (₱0.00), refunding unused time (₱26.00)', '', 'completed', '2026-05-03 21:15:37', 12, '2026-05-03 21:15:37'),
(22, NULL, 27, 34.00, 34.00, NULL, 'Downpayment for reservation #8', 'gcash', 'completed', '2026-05-03 21:20:53', 27, '2026-05-03 21:20:53'),
(23, NULL, 27, 34.00, 34.00, NULL, 'Downpayment for reservation #9', 'gcash', 'completed', '2026-05-03 21:24:47', 27, '2026-05-03 21:24:47'),
(24, 7, 27, 34.00, 34.00, NULL, 'Downpayment transferred from reservation #9', 'gcash', 'completed', '2026-05-03 21:25:08', 12, '2026-05-03 21:25:08'),
(25, 7, 27, -34.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 00m (₱0.00), refunding unused time (₱34.00)', '', 'completed', '2026-05-03 21:25:46', 12, '2026-05-03 21:25:46'),
(26, NULL, 27, 24.00, 24.00, NULL, 'Downpayment for reservation #10', 'gcash', 'completed', '2026-05-03 21:33:03', 27, '2026-05-03 21:33:03'),
(27, 8, 27, 24.00, 24.00, NULL, 'Downpayment transferred from reservation #10', 'gcash', 'completed', '2026-05-03 21:37:14', 12, '2026-05-03 21:37:14'),
(28, 9, 27, 23.00, 23.00, NULL, 'Downpayment transferred from reservation #11', 'gcash', 'completed', '2026-05-03 21:55:58', 12, '2026-05-03 21:55:58'),
(29, 8, 27, -4.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 19m (₱20.00), refunding unused time (₱4.00)', '', 'completed', '2026-05-03 21:56:25', 12, '2026-05-03 21:56:25'),
(30, 10, 27, 24.00, 24.00, NULL, 'Downpayment transferred from reservation #12', 'gcash', 'completed', '2026-05-03 22:12:13', 12, '2026-05-03 22:12:13'),
(31, 9, 27, -23.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 01m (₱0.00), refunding unused time (₱23.00)', '', 'completed', '2026-05-03 22:12:57', 12, '2026-05-03 22:12:57'),
(32, 10, 27, 56.00, 56.00, NULL, 'Balance payment collected', 'cash', 'completed', '2026-05-03 22:13:53', 12, '2026-05-03 22:13:53'),
(33, 10, 27, -80.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 00m (₱0.00), refunding unused time (₱80.00)', '', 'completed', '2026-05-03 22:14:03', 12, '2026-05-03 22:14:03'),
(34, 11, 27, 32.00, 32.00, NULL, 'Downpayment transferred from reservation #13', 'gcash', 'completed', '2026-05-03 22:15:16', 12, '2026-05-03 22:15:16'),
(35, 11, 27, -32.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 00m (₱0.00), refunding unused time (₱32.00)', '', 'completed', '2026-05-03 22:15:52', 12, '2026-05-03 22:15:52'),
(36, 12, 27, 32.00, 32.00, NULL, 'Downpayment transferred from reservation #14', 'gcash', 'completed', '2026-05-03 22:25:46', 12, '2026-05-03 22:25:46'),
(37, 13, 0, 120.00, 120.00, NULL, NULL, 'cash', 'completed', '2026-05-03 22:27:04', 12, '2026-05-03 22:27:04'),
(38, 12, 27, 248.00, 248.00, NULL, 'Balance payment collected', 'cash', 'completed', '2026-05-03 22:28:19', 12, '2026-05-03 22:28:19'),
(39, 13, 0, -60.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 49m (₱60.00), refunding unused time (₱60.00)', '', 'completed', '2026-05-03 23:17:05', 12, '2026-05-03 23:17:05'),
(40, 12, 27, -200.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 51m (₱80.00), refunding unused time (₱200.00)', '', 'completed', '2026-05-03 23:17:12', 12, '2026-05-03 23:17:12'),
(41, 14, 27, 26.00, 26.00, NULL, 'Downpayment transferred from reservation #15', 'gcash', 'completed', '2026-05-03 23:17:48', 12, '2026-05-03 23:17:48'),
(42, 14, 27, -26.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 00m (₱0.00), refunding unused time (₱26.00)', '', 'completed', '2026-05-03 23:19:11', 12, '2026-05-03 23:19:11'),
(43, 15, 27, 26.00, 26.00, NULL, 'Downpayment transferred from reservation #16', 'gcash', 'completed', '2026-05-03 23:47:59', 12, '2026-05-03 23:47:59'),
(44, 16, 26, 80.00, 80.00, NULL, NULL, 'cash', 'completed', '2026-05-03 23:49:15', 12, '2026-05-03 23:49:15'),
(45, 16, 26, -80.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 00m (₱0.00), refunding unused time (₱80.00)', '', 'completed', '2026-05-03 23:49:22', 12, '2026-05-03 23:49:22'),
(46, 17, 27, 24.00, 24.00, NULL, 'Downpayment transferred from reservation #17', 'gcash', 'completed', '2026-05-03 23:58:29', 12, '2026-05-03 23:58:29'),
(47, 17, 27, 56.00, 56.00, NULL, 'Balance payment collected', 'cash', 'completed', '2026-05-04 00:00:47', 12, '2026-05-04 00:00:47'),
(48, 17, 27, -56.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 00m (₱0.00), refunding unused time (₱56.00)', '', 'completed', '2026-05-04 00:01:08', 12, '2026-05-04 00:01:08'),
(49, 18, 27, 24.00, 24.00, NULL, 'Downpayment transferred from reservation #18', 'gcash', 'completed', '2026-05-04 00:05:24', 12, '2026-05-04 00:05:24'),
(50, 18, 27, 56.00, 56.00, NULL, 'Balance payment collected', 'cash', 'completed', '2026-05-04 00:17:57', 12, '2026-05-04 00:17:57'),
(51, 18, 27, -36.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 11m (₱20.00), refunding unused time (₱36.00)', '', 'completed', '2026-05-04 00:19:13', 12, '2026-05-04 00:19:13'),
(52, NULL, 27, 24.00, 24.00, NULL, 'Downpayment for reservation #19', 'gcash', 'completed', '2026-05-04 12:35:27', 27, '2026-05-04 12:35:27'),
(53, NULL, 27, 24.00, 24.00, NULL, 'Downpayment for reservation #20', 'gcash', 'completed', '2026-05-04 16:11:04', 27, '2026-05-04 16:11:04'),
(54, 20, 0, 320.00, 320.00, NULL, NULL, 'cash', 'completed', '2026-05-04 16:23:45', 12, '2026-05-04 16:23:45');

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
(21, 'hello@gmail.com', '$2y$10$vjAu848ZbS1DpfexUIsM1.x31O/VNLXNwp/sw65NckgmZTHTKLzAO', 'Lito LARGUEZA LABIANO', '09916310227', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-04-17 11:59:29', 0, '2026-05-04 23:10:54'),
(22, 'admin@gmail.com', '$2y$10$kZCF21cxNKIxXE99XNGyF.nGfT94FZeDaGtbH4YTBOxrpumQLDvIK', 'Admin User', '', 'customer', 'active', 0, '9ad4e0d4b2004d75a763bd2e6e9af695cdb053e39c7cadd65db419783c75b54f', '2026-04-20 16:02:24', NULL, NULL, '2026-04-19 16:02:24', 0, NULL),
(23, 'customer@example.com', '$2y$10$8mmAlQ1UknZorTRMn6NVneRRA5JrVSOO8oMAuDEVll.pICMikYieO', 'Test Customer', '', 'customer', 'active', 0, '5337f25a858a79327c9001f982e6629f2f64be6579986d9da00c816159a6a75c', '2026-04-20 20:49:05', NULL, NULL, '2026-04-19 20:49:05', 0, NULL),
(24, 'lito@example.com', '$2y$10$b1z6jAajQr5NjOyHWkU5kOJ7kx/BdeM6HbUg/qLWdElsilx20bLta', 'Lito Test', '', 'customer', 'active', 0, '10512cc414ac02ff1b961698a17863ad87e3e55d976dc1e14d92a2af161ee3bd', '2026-04-20 20:52:01', NULL, NULL, '2026-04-19 20:52:01', 0, NULL),
(26, 'hbernesto@kld.edu.ph', '$2y$10$tS7MGOXvsgcjV7DcP99UyuoGk6l4XgtEwyeWTREjw4PrrIu/7381G', 'Harvie Bernesto', '09944084214', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-04-27 23:19:32', 0, '2026-05-07 07:36:37'),
(27, 'helios@gmail.com', '$2y$10$R0HlhoPj53cgV9MwvalomuJxieeDE5JWQ4T7trpO.ttzu/U0rXJnm', 'helios', 'helios@gmail.com', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-04-27 23:29:14', 0, NULL),
(28, 'testcustomer@example.com', '$2y$10$kGbRHj87ratNFmTwi2pmTOqMaV2KV0fvB2H5sMYMsEzMfFVM4Uku2', 'Test Customer', '09123456789', 'customer', 'active', 0, '29333d3de426116e2f4f7d304d0b9f4b4b3b76feb6ee30a877d3d5457179f6e1', '2026-04-28 23:52:37', NULL, NULL, '2026-04-27 23:52:37', 0, NULL),
(29, 'test@example.com', '$2y$10$mLQD41zmi3vIoJ8sR8/Ntu6uZojYGjvzKbNb3Y.evmmQ3SyMB7evC', 'Test User', '09171234567', 'customer', 'active', 0, '0ae0598c1a671992ec26dfae71eabe5e2f9269bb1da1ae9f85c78695949d6184', '2026-04-30 20:29:21', NULL, NULL, '2026-04-29 20:29:21', 0, NULL),
(30, '09171234567test@gmail.com', '$2y$10$jyNy.ykur0jghOW.APBCwOpx.Y5fTLFYDdfTbek92i6AffjjIlcBq', 'test@gmail.comTest User', 'Password123!09171234', 'customer', 'active', 0, '6a296a2ad91537491af9a4309082ea6e25e0a818b60dd86e65ccc5e842ccd700', '2026-05-01 00:13:31', NULL, NULL, '2026-04-30 00:13:32', 0, NULL),
(31, 'mariejoy.12.mji@gmail.com', '$2y$10$VDFqBSZiRt9I1ZLIRaVBdOSQ0Red0aogx.VqXx1EMrGP.905x0bX2', 'MJ IGNACIO', '', 'customer', 'active', 0, 'd0e91ebfe1587c05cd41f92225bfa010276bb3e2c5c3ae0e7fefabd03a28c942', '2026-05-01 08:01:11', NULL, NULL, '2026-04-30 08:01:11', 0, NULL);

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
  ADD KEY `idx_sessions_pay_status` (`payment_status`),
  ADD KEY `idx_gs_source_res` (`source_reservation_id`);

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
  ADD KEY `fk_res_created_by` (`created_by`),
  ADD KEY `idx_res_paymongo_src` (`paymongo_source_id`);

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
  ADD KEY `idx_rc_console` (`console_type`);

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
-- Indexes for table `tournament_registrations`
--
ALTER TABLE `tournament_registrations`
  ADD PRIMARY KEY (`reg_id`);

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
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `reservation_cancellations`
--
ALTER TABLE `reservation_cancellations`
  MODIFY `cancel_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `reservation_reschedules`
--
ALTER TABLE `reservation_reschedules`
  MODIFY `reschedule_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `session_extensions`
--
ALTER TABLE `session_extensions`
  MODIFY `extension_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=164;

--
-- AUTO_INCREMENT for table `tournaments`
--
ALTER TABLE `tournaments`
  MODIFY `tournament_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tournament_participants`
--
ALTER TABLE `tournament_participants`
  MODIFY `participant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tournament_registrations`
--
ALTER TABLE `tournament_registrations`
  MODIFY `reg_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

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
