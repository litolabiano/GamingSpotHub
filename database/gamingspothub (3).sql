-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 13, 2026 at 12:59 PM
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
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `action`, `details`, `created_at`, `entity_type`, `entity_id`, `meta`) VALUES
(1, 12, 'Restore Session', 'Restored session #4 for User #33. Time elapsed since end: 1m 45s. Console: PS5-02. Original End Time: 2026-05-08 18:17:41.', '2026-05-08 18:19:26', NULL, NULL, NULL),
(2, 12, 'Restore Session', 'Restored session #4 for User #33. Time elapsed since end: 1m 10s. Console: PS5-02. Original End Time: 2026-05-08 18:19:47.', '2026-05-08 18:20:57', NULL, NULL, NULL),
(3, 12, 'Restore Session', 'Restored session #8 for User #0. Time elapsed since end: 0m 16s. Console: PS5-02. Original End Time: 2026-05-08 18:49:16.', '2026-05-08 18:49:32', NULL, NULL, NULL),
(4, 12, 'Restore Session', 'Restored session #9 for User #0. Time elapsed since end: 0m 46s. Console: PS5-03. Original End Time: 2026-05-08 18:51:25.', '2026-05-08 18:52:11', NULL, NULL, NULL),
(5, 12, 'Restore Session', 'Restored session #10 for User #0. Time elapsed since end: 0m 43s. Console: PS5-01. Original End Time: 2026-05-08 18:53:30.', '2026-05-08 18:54:13', NULL, NULL, NULL),
(6, 12, 'Restore Session', 'Restored session #11 for User #0. Time elapsed since end: 0m 58s. Console: PS5-05. Original End Time: 2026-05-08 19:13:45.', '2026-05-08 19:14:43', NULL, NULL, NULL),
(7, 12, 'Restore Session', 'Restored session #7 for User #0. Time elapsed since end: 41m 21s. Console: 1. Original End Time: 2026-05-08 18:40:47.', '2026-05-08 19:22:08', NULL, NULL, NULL),
(8, 12, 'Restore Session', 'Restored session #9 for User #0. Time elapsed since end: 1m 7s. Console: PS5-03. Original End Time: 2026-05-08 19:21:20.', '2026-05-08 19:22:27', NULL, NULL, NULL),
(9, 12, 'Restore Session', 'Restored session #11 for User #0. Time elapsed since end: -6m -52s. Console: PS5-05. Original End Time: 2026-05-08 19:30:00.', '2026-05-08 19:24:08', NULL, NULL, NULL),
(10, 12, 'Restore Session', 'Restored session #10 for User #0. Time elapsed since end: 10m 30s. Console: PS5-01. Original End Time: 2026-05-08 19:22:53.', '2026-05-08 19:33:23', NULL, NULL, NULL),
(11, 12, 'Restore Session', 'Restored session #12 for User #0. Time elapsed since end: 1m 5s. Console: PS5-02. Original End Time: 2026-05-08 19:42:17. Payments made at end were voided.', '2026-05-08 19:43:22', NULL, NULL, NULL),
(12, 12, 'Restore Session', 'Restored session #12 for User #0. Time elapsed since end: 54m 45s. Console: PS5-02. Original End Time: 2026-05-08 19:45:13. Payments made at end were voided.', '2026-05-08 20:39:58', NULL, NULL, NULL),
(13, 12, 'Tournament Status', 'Updated Tournament #2 status to Upcoming', '2026-05-09 00:14:24', NULL, NULL, NULL),
(14, 12, 'Tournament Status', 'Updated Tournament #2 status to Scheduled', '2026-05-09 00:14:28', NULL, NULL, NULL),
(15, 12, 'End Session', 'Ended Session #13. Console: PS5-03. Duration: 59 min. Total Cost: ₱100.00.', '2026-05-09 00:23:30', NULL, NULL, NULL),
(16, 12, 'Restore Session', 'Restored session #13 for User #0. Time elapsed since end: 30m 31s. Console: PS5-03. Original End Time: 2026-05-09 00:23:30. Payments made at end were voided.', '2026-05-09 00:54:01', NULL, NULL, NULL),
(17, 12, 'End Session', 'Ended Session #13. Console: PS5-03. Duration: 90 min. Total Cost: ₱140.00.', '2026-05-09 00:54:10', NULL, NULL, NULL),
(18, 12, 'Controller Type', 'Added new controller type: hello (for hello)', '2026-05-09 09:43:20', NULL, NULL, NULL),
(19, 12, 'Controller unit', 'Added new controller: 67 (hello), Unit: 67', '2026-05-09 10:11:03', NULL, NULL, NULL),
(20, 12, 'Edit Console', 'Updated Console ID #1: PS5 Unit 1, Unit: PS5-01, Rate: ₱/hr', '2026-05-09 10:27:17', NULL, NULL, NULL),
(21, 12, 'Edit Console', 'Updated Console ID #1: PS5 Unit 1, Unit: PS5-01, Rate: ₱/hr', '2026-05-09 10:27:47', NULL, NULL, NULL),
(22, 12, 'Edit Console', 'Updated Console ID #1: PS5 Unit 1, Unit: PS5-01, Rate: ₱/hr', '2026-05-09 10:30:25', NULL, NULL, NULL),
(23, 12, 'Console Status', 'Updated Console ID #1 status to Available', '2026-05-09 10:30:33', NULL, NULL, NULL),
(24, 12, 'Edit Console', 'Updated Console ID #1: PS5 Unit 1, Unit: PS5-01, Rate: ₱/hr', '2026-05-09 10:30:39', NULL, NULL, NULL),
(25, 12, 'Edit Console', 'Updated Console ID #1: PS5 Unit 1, Unit: PS5-01, Rate: ₱/hr', '2026-05-09 10:32:33', NULL, NULL, NULL),
(26, 12, 'Edit Console', 'Updated Console ID #1: PS5 Unit 1, Unit: PS5-01, Rate: ₱/hr', '2026-05-09 10:32:41', NULL, NULL, NULL),
(27, 12, 'Edit Console', 'Updated Console ID #1: PS5 Unit 1, Unit: PS5-01', '2026-05-09 10:34:55', NULL, NULL, NULL),
(28, 12, 'Edit Console', 'Updated Console ID #8: Xbox Unit 8, Unit: XBX-08', '2026-05-09 10:49:14', NULL, NULL, NULL),
(29, 12, 'Edit Console', 'Updated Console ID #8: Xbox Unit 8, Unit: XBX-08', '2026-05-09 10:50:33', NULL, NULL, NULL),
(30, 12, 'Controller unit', 'Added new controller: hello, Unit: CTRL-08', '2026-05-09 10:54:00', NULL, NULL, NULL),
(31, 12, 'Controller Unit', 'Permanently deleted Controller ID #1', '2026-05-09 10:54:29', NULL, NULL, NULL),
(32, 12, 'Controller Status', 'Updated Controller ID #4 status to Available', '2026-05-09 10:54:34', NULL, NULL, NULL),
(33, 12, 'Controller Status', 'Updated Controller ID #13 status to Available', '2026-05-09 10:54:40', NULL, NULL, NULL),
(34, 12, 'Controller Status', 'Updated Controller ID #14 status to Available', '2026-05-09 10:54:47', NULL, NULL, NULL),
(35, 12, 'Reschedule Proposal', 'Proposed new schedule for Reservation #45: 2026-05-11 12:00. Reason: Typhoon', '2026-05-09 11:29:08', NULL, NULL, NULL),
(36, 12, 'System Settings', 'Updated system settings and synced console rates.', '2026-05-09 12:38:44', NULL, NULL, NULL),
(37, 12, 'System Settings', 'Updated system settings and synced console rates.', '2026-05-09 12:38:51', NULL, NULL, NULL),
(38, 12, 'System Settings', 'Updated system settings and synced console rates.', '2026-05-09 12:48:47', NULL, NULL, NULL),
(39, 12, 'Tournament Status', 'Updated Tournament #2 status to Upcoming', '2026-05-09 12:55:55', NULL, NULL, NULL),
(40, 12, 'Tournament Status', 'Updated Tournament #2 status to Scheduled', '2026-05-09 12:55:57', NULL, NULL, NULL),
(41, 12, 'Tournament Status', 'Updated Tournament #2 status to Ongoing', '2026-05-09 12:55:57', NULL, NULL, NULL),
(42, 12, 'Tournament Status', 'Updated Tournament #2 status to Completed', '2026-05-09 12:55:59', NULL, NULL, NULL),
(43, 12, 'Create Tournament', 'Created Tournament #3: tiken it (tekin eyt) on PS5', '2026-05-09 12:56:25', NULL, NULL, NULL),
(44, 12, 'Tournament Status', 'Updated Tournament #3 status to Scheduled', '2026-05-09 12:56:26', NULL, NULL, NULL),
(45, 12, 'Tournament Status', 'Updated Tournament #3 status to Upcoming', '2026-05-09 12:56:29', NULL, NULL, NULL),
(46, 12, 'Tournament Status', 'Updated Tournament #3 status to Scheduled', '2026-05-09 12:56:43', NULL, NULL, NULL),
(47, 12, 'Start Session', 'Started Session #14 for Walk-in. Console: PS5-01. Mode: Hourly (30 min)', '2026-05-09 13:24:06', NULL, NULL, NULL),
(48, 12, 'Start Session', 'Started Session #15 for Walk-in. Console: PS5-04. Mode: Hourly (150 min)', '2026-05-09 13:24:21', NULL, NULL, NULL),
(49, 12, 'Start Session', 'Started Session #16 for Walk-in. Console: 1. Mode: Hourly (30 min)', '2026-05-09 13:24:42', NULL, NULL, NULL),
(50, 12, 'Extend Session', 'Directly extended Session #14 by 15 min. Total added: 15 min (including bonus).', '2026-05-09 13:24:52', NULL, NULL, NULL),
(51, 39, 'Reschedule Request', 'Customer requested to reschedule Reservation #49 to 2026-05-10 13:00', '2026-05-09 13:30:21', NULL, NULL, NULL),
(52, 12, 'Reschedule Approve', 'Approved reschedule request #30 for Reservation #49', '2026-05-09 13:30:45', NULL, NULL, NULL),
(53, 12, 'Reschedule Proposal', 'Proposed new schedule for Reservation #49: 2026-05-09 13:00. Reason: Maintenance', '2026-05-09 13:31:01', NULL, NULL, NULL),
(54, 39, 'Reschedule Confirm', 'User confirmed reschedule for Reservation #49 to 2026-05-09 13:00', '2026-05-09 13:31:16', NULL, NULL, NULL),
(55, 12, 'Console Type', 'Archived Console Type ID #138', '2026-05-09 13:38:19', NULL, NULL, NULL),
(56, 12, 'Console Type', 'Added new console type: PC', '2026-05-09 14:47:46', NULL, NULL, NULL),
(57, 12, 'Add Console', 'Added new console: PC UNIT 1, Unit: PC-01', '2026-05-09 14:48:05', NULL, NULL, NULL),
(58, 12, 'Edit Console', 'Updated Console ID #3228: PC UNIT 1, Unit: PC-01', '2026-05-09 14:48:16', NULL, NULL, NULL),
(59, 12, 'Start Session', 'Started Session #18 for Walk-in. Console: PC-01. Mode: Hourly (60 min)', '2026-05-09 14:48:33', NULL, NULL, NULL),
(60, 12, 'Controller Type', 'Added new controller type: pc1 controller (for PC)', '2026-05-09 14:49:53', NULL, NULL, NULL),
(61, 12, 'Edit Console', 'Updated Console ID #3228: PC UNIT 1, Unit: PC-01', '2026-05-09 14:49:59', NULL, NULL, NULL),
(62, 12, 'Controller unit', 'Added new controller: pc1 controller, Unit: ctrl sa pc', '2026-05-09 14:50:14', NULL, NULL, NULL),
(63, 12, 'Edit Console', 'Updated Console ID #3228: PC UNIT 1, Unit: PC-01', '2026-05-09 15:21:32', NULL, NULL, NULL),
(64, 12, 'Edit Console', 'Updated Console ID #3228: PC UNIT 1, Unit: PC-01', '2026-05-09 15:21:59', NULL, NULL, NULL),
(65, 12, 'Console Status', 'Updated Console ID #3228 status to Archived', '2026-05-09 15:22:02', NULL, NULL, NULL),
(66, 12, 'Tournament Participant Removed', 'Removed Participant Sibe Loresco from Tournament: tiken it (#3) at May 09, 2026 03:23 PM', '2026-05-09 15:23:44', NULL, NULL, NULL),
(67, 12, 'Console Status', 'Updated Console ID #3228 status to Available', '2026-05-09 15:26:01', NULL, NULL, NULL),
(68, 12, 'Console Status', 'Updated Console ID #3228 status to Archived', '2026-05-09 15:26:08', NULL, NULL, NULL),
(69, 12, 'Tournament Payment Updated', 'Updated payment status to PAID for Participant Sibe in Tournament: tiken it (#3)', '2026-05-09 15:32:08', NULL, NULL, NULL),
(70, 12, 'Tournament Participant Removed', 'Removed Participant Sibe from Tournament: tiken it (#3) at May 09, 2026 03:32 PM', '2026-05-09 15:32:10', NULL, NULL, NULL),
(71, 12, 'Tournament Participant Removed', 'Removed Participant Sibe from Tournament: tiken it (#3) at May 09, 2026 03:32 PM', '2026-05-09 15:32:13', NULL, NULL, NULL),
(72, 12, 'Tournament Participant Removed', 'Removed Participant Sibe Loresco from Tournament: tiken it (#3) at May 09, 2026 03:32 PM', '2026-05-09 15:32:35', NULL, NULL, NULL),
(73, 12, 'End Session', 'Ended Session #18. Console: PC-01. Duration: 71 min. Total Cost: ₱100.00.', '2026-05-09 16:00:29', NULL, NULL, NULL),
(74, 12, 'End Session', 'Ended Session #16. Console: 1. Duration: 155 min. Total Cost: ₱230.00.', '2026-05-09 16:00:38', NULL, NULL, NULL),
(75, 12, 'Console Status', 'Updated Console ID #3228 status to Archived', '2026-05-09 16:00:49', NULL, NULL, NULL),
(76, 12, 'Console Status', 'Updated Console ID #3227 status to Archived', '2026-05-09 16:00:53', NULL, NULL, NULL),
(77, 12, 'Delete Console', 'Permanently deleted Console: PC UNIT 1 (Unit: PC-01, Type: PC)', '2026-05-09 16:00:57', NULL, NULL, NULL),
(78, 12, 'Delete Console', 'Permanently deleted Console: PS5 (Unit: 1, Type: hello)', '2026-05-09 16:01:17', NULL, NULL, NULL),
(79, 12, 'Console Status', 'Updated Console ID #3226 status to Archived', '2026-05-09 16:01:25', NULL, NULL, NULL),
(80, 12, 'Delete Console', 'Permanently deleted Console: ps 6 (Unit: NINTENDO-01, Type: PS6)', '2026-05-09 16:01:31', NULL, NULL, NULL),
(81, 12, 'Console Status', 'Updated Console ID #3225 status to Archived', '2026-05-09 16:01:37', NULL, NULL, NULL),
(82, 12, 'Delete Console', 'Permanently deleted Console: PS5 (Unit: PS5-9, Type: PS5)', '2026-05-09 16:01:44', NULL, NULL, NULL),
(83, 12, 'End Session', 'Ended Session #14. Console: PS5-01. Duration: 171 min. Total Cost: ₱220.00.', '2026-05-09 16:16:00', NULL, NULL, NULL),
(84, 12, 'Unblock Date', 'Unblocked system for date: 2026-05-29', '2026-05-09 16:18:07', NULL, NULL, NULL),
(85, 12, 'Block Date', 'Blocked system for date: 2026-05-10. Reason: MAINTENANCE', '2026-05-09 16:18:22', NULL, NULL, NULL),
(86, 12, 'Console Type', 'Archived Console Type ID #11', '2026-05-09 16:18:49', NULL, NULL, NULL),
(87, 12, 'Add Reservation', 'Created new Reservation #52 for User #26 on 2026-05-09 17:00. Console Type: PS5 (Console Unit #4)', '2026-05-09 16:51:22', NULL, NULL, NULL),
(88, 12, 'Extend Session', 'Directly extended Session #15 by 30 min. Total added: 30 min (including bonus).', '2026-05-09 16:52:20', NULL, NULL, NULL),
(89, 12, 'Start Session', 'Started Session #20 for Walk-in. Console: PS5-02. Mode: Hourly (60 min)', '2026-05-09 17:54:39', NULL, NULL, NULL),
(90, 12, 'Start Session', 'Started Session #21 for Walk-in. Console: XBX-07. Mode: Hourly (240 min)', '2026-05-09 17:55:15', NULL, NULL, NULL),
(91, 40, 'Tournament Registration', 'Registered and paid for tournament: tiken it', '2026-05-09 18:23:08', NULL, NULL, NULL),
(92, 12, 'Tournament Payment Updated', 'Updated payment status to PAID for Participant Harvie Loresco in Tournament: tiken it (#3)', '2026-05-09 18:24:36', NULL, NULL, NULL),
(93, 12, 'Tournament Status', 'Updated Tournament #3 status to Upcoming', '2026-05-09 18:30:47', NULL, NULL, NULL),
(94, 12, 'Tournament Status', 'Updated Tournament #3 status to Scheduled', '2026-05-09 18:30:49', NULL, NULL, NULL),
(95, 12, 'Tournament Participant Archived', 'Archived Participant Harvie Loresco from Tournament: tiken it (#3) at May 09, 2026 06:32 PM', '2026-05-09 18:32:25', NULL, NULL, NULL),
(97, 12, 'Console Type', 'Archived Console Type ID #151', '2026-05-09 19:01:38', NULL, NULL, NULL),
(99, 39, 'Reschedule Confirm', 'User confirmed reschedule for Reservation #50 to 2026-05-09 20:00', '2026-05-09 19:12:11', NULL, NULL, NULL),
(100, 12, 'Reschedule Proposal', 'Proposed new schedule for Reservation #54: 2026-05-09 19:30. Reason: Emergency', '2026-05-09 19:15:07', NULL, NULL, NULL),
(101, 12, 'Controller Type', 'Archived Controller Type ID #103', '2026-05-09 19:23:00', NULL, NULL, NULL),
(102, 39, 'Reschedule Confirm', 'User confirmed reschedule for Reservation #54 to 2026-05-09 19:30', '2026-05-09 19:23:58', NULL, NULL, NULL),
(103, 12, 'Reschedule Proposal', 'Proposed new schedule for Reservation #54: 2026-05-09 20:00. Reason: Maintenance', '2026-05-09 19:24:16', NULL, NULL, NULL),
(104, 39, 'Reschedule Confirm', 'User confirmed reschedule for Reservation #54 to 2026-05-09 20:00', '2026-05-09 19:30:12', NULL, NULL, NULL),
(105, 12, 'Reschedule Proposal', 'Proposed new schedule for Reservation #54: 2026-05-09 20:00. Reason: Maintenance', '2026-05-09 19:31:54', NULL, NULL, NULL),
(106, 12, 'System Settings', 'Updated system settings and synced console rates.', '2026-05-09 19:53:13', NULL, NULL, NULL),
(107, 12, 'End Session', 'Ended Session #20. Console: PS5-02. Duration: 168 min. Total Cost: ₱220.00.', '2026-05-09 20:42:58', NULL, NULL, NULL),
(108, 12, 'End Session', 'Ended Session #17. Console: PS5-03. Duration: 431 min. Total Cost: ₱480.00.', '2026-05-09 20:43:15', NULL, NULL, NULL),
(109, 12, 'End Session', 'Ended Session #15. Console: PS5-04. Duration: 439 min. Total Cost: ₱520.00.', '2026-05-09 20:43:21', NULL, NULL, NULL),
(110, 39, 'Reschedule Decline', 'User declined reschedule proposal for Reservation #54', '2026-05-09 21:18:51', NULL, NULL, NULL),
(111, 12, 'Restore Session', 'Restored session #17 for User #39. Time elapsed since end: 52m 51s. Console: PS5-03. Original End Time: 2026-05-09 20:43:15. Payments made at end were voided.', '2026-05-09 21:36:06', NULL, NULL, NULL),
(112, 12, 'End Session', 'Ended Session #17. Console: PS5-03. Duration: 485 min. Total Cost: ₱540.00.', '2026-05-09 21:36:23', NULL, NULL, NULL),
(113, 12, 'Restore Session', 'Restored session #15 for User #0. Time elapsed since end: 53m 38s. Console: PS5-04. Original End Time: 2026-05-09 20:43:21. Payments made at end were voided.', '2026-05-09 21:36:59', NULL, NULL, NULL),
(114, 12, 'End Session', 'Ended Session #15. Console: PS5-04. Duration: 492 min. Total Cost: ₱580.00.', '2026-05-09 21:37:11', NULL, NULL, NULL),
(115, 12, 'Shopkeeper Account Creation', 'Created new Shopkeeper account for GSPOT (goodspotgaminghub@gmail.com)', '2026-05-09 23:14:39', NULL, NULL, NULL),
(116, 37, 'Reschedule Confirm', 'User confirmed reschedule for Reservation #45 to 2026-05-11 12:00', '2026-05-09 23:34:48', NULL, NULL, NULL),
(117, 12, 'Unblock Date', 'Unblocked system for date: 2026-05-10', '2026-05-10 00:01:20', NULL, NULL, NULL),
(118, 12, 'Console Status', 'Updated Console ID #1 status to Available', '2026-05-10 00:05:31', NULL, NULL, NULL),
(119, 12, 'Console Status', 'Updated Console ID #2 status to Available', '2026-05-10 00:05:33', NULL, NULL, NULL),
(120, 12, 'Console Status', 'Updated Console ID #5 status to Available', '2026-05-10 00:05:36', NULL, NULL, NULL),
(121, 12, 'Console Status', 'Updated Console ID #7 status to Available', '2026-05-10 00:05:37', NULL, NULL, NULL),
(122, 12, 'Controller Type', 'Added new controller type: AMD RADEON (for PS5)', '2026-05-10 00:06:06', NULL, NULL, NULL),
(123, 37, 'Reschedule Request', 'Customer requested to reschedule Reservation #55 to 2026-05-10 15:00', '2026-05-10 00:16:08', NULL, NULL, NULL),
(124, 12, 'Reschedule Reject', 'Rejected reschedule request #36 for Reservation #55', '2026-05-10 00:16:57', NULL, NULL, NULL),
(125, 12, 'Reschedule Proposal', 'Proposed new schedule for Reservation #55: 2026-05-10 14:00. Reason: Maintenance', '2026-05-10 00:18:18', NULL, NULL, NULL),
(126, 37, 'Reschedule Counter', 'User sent counter-proposal for Reservation #55 to 2026-05-10 15:00', '2026-05-10 00:18:54', NULL, NULL, NULL),
(127, 12, 'Reschedule Approve', 'Approved reschedule request #37 for Reservation #55', '2026-05-10 00:19:06', NULL, NULL, NULL),
(128, 12, 'Reschedule Proposal', 'Proposed new schedule for Reservation #55: 2026-05-11 15:00. Reason: Maintenance', '2026-05-10 00:27:36', NULL, NULL, NULL),
(129, 37, 'Reschedule Counter', 'User sent counter-proposal for Reservation #55 to 2026-05-11 16:00', '2026-05-10 00:28:05', NULL, NULL, NULL),
(130, 12, 'Reschedule Approve', 'Approved reschedule request #38 for Reservation #55', '2026-05-10 00:28:15', NULL, NULL, NULL),
(131, 12, 'Reschedule Proposal', 'Proposed new schedule for Reservation #56: 2026-05-11 20:00. Reason: Maintenance', '2026-05-10 00:29:49', NULL, NULL, NULL),
(132, 37, 'Reschedule Counter', 'User sent counter-proposal for Reservation #56 to 2026-05-11 22:00', '2026-05-10 00:41:12', NULL, NULL, NULL),
(133, 12, 'Reschedule Approve', 'Approved reschedule request #39 for Reservation #56', '2026-05-10 00:41:25', NULL, NULL, NULL),
(134, 12, 'Block Date', 'Blocked system for date: 2026-05-11. Reason: NAGALULU SI GSPOT', '2026-05-10 09:48:45', NULL, NULL, NULL),
(135, 12, 'Unblock Date', 'Unblocked system for date: 2026-05-11', '2026-05-10 09:48:50', NULL, NULL, NULL),
(136, 12, 'Block Date', 'Blocked system for date: 2026-05-11. Reason: brukin si jisi lim', '2026-05-10 09:49:34', NULL, NULL, NULL),
(137, 12, 'Unblock Date', 'Unblocked system for date: 2026-05-11', '2026-05-10 09:50:05', NULL, NULL, NULL),
(138, 12, 'Block Date', 'Blocked system for date: 2026-05-11. Reason: brukin si jisi lim', '2026-05-10 09:50:15', NULL, NULL, NULL),
(139, 12, 'Unblock Date', 'Unblocked system for date: 2026-05-11', '2026-05-10 09:53:28', NULL, NULL, NULL),
(140, 12, 'Start Session', 'Started Session #23 for Walk-in. Console: PS5-01. Mode: Hourly (240 min)', '2026-05-10 10:29:54', NULL, NULL, NULL),
(141, 12, 'Start Session', 'Started Session #24 for Walk-in. Console: PS5-05. Mode: Hourly (240 min)', '2026-05-10 10:36:21', NULL, NULL, NULL),
(142, 12, 'Start Session', 'Started Session #25 for Walk-in. Console: PS5-03. Mode: Unlimited', '2026-05-10 10:42:42', NULL, NULL, NULL),
(143, 12, 'End Session', 'Ended Session #25. Console: PS5-03. Duration: 1 min. Total Cost: ₱440.00.', '2026-05-10 10:44:10', NULL, NULL, NULL),
(144, 12, 'End Session', 'Ended Session #24. Console: PS5-05. Duration: 7 min. Total Cost: ₱100.00.', '2026-05-10 10:44:16', NULL, NULL, NULL),
(145, 12, 'End Session', 'Ended Session #23. Console: PS5-01. Duration: 14 min. Total Cost: ₱20.00.', '2026-05-10 10:44:20', NULL, NULL, NULL),
(146, 12, 'Start Session', 'Started Session #26 for Walk-in. Console: PS5-04. Mode: Unlimited', '2026-05-10 10:45:22', NULL, NULL, NULL),
(147, 12, 'Start Session', 'Started Session #27 for Walk-in. Console: PS5-03. Mode: Hourly (60 min)', '2026-05-10 10:46:30', NULL, NULL, NULL),
(148, 12, 'Record Payment', 'Recorded payment of ₱80.00 via Cash for Session #27', '2026-05-10 10:49:00', NULL, NULL, NULL),
(149, 12, 'End Session', 'Ended Session #27. Console: PS5-03. Duration: 10 min. Total Cost: ₱100.00.', '2026-05-10 10:56:31', NULL, NULL, NULL),
(150, 12, 'Reschedule Proposal', 'Proposed new schedule for Reservation #57: 2026-05-12 21:00. Reason: Maintenance', '2026-05-10 11:20:44', NULL, NULL, NULL),
(151, 33, 'Reschedule Confirm', 'User confirmed reschedule for Reservation #57 to 2026-05-12 21:00', '2026-05-10 11:21:25', NULL, NULL, NULL),
(152, 12, 'Controller Status', 'Updated Controller ID #18 status to Archived', '2026-05-10 11:28:52', NULL, NULL, NULL),
(153, 12, 'Controller Status', 'Updated Controller ID #22 status to Archived', '2026-05-10 11:28:57', NULL, NULL, NULL),
(154, 12, 'Controller Status', 'Updated Controller ID #23 status to Archived', '2026-05-10 11:29:02', NULL, NULL, NULL),
(155, 12, 'Controller Status', 'Updated Controller ID #21 status to Archived', '2026-05-10 11:29:04', NULL, NULL, NULL),
(156, 12, 'Bulk Delete Console Types', 'Permanently deleted 6 console types (IDs: 148,149,138,11,151,150)', '2026-05-10 12:35:19', NULL, NULL, NULL),
(157, 12, 'Extend Controller', 'Extended Controller Rental for Session #26 by 300 min (Added ₱100.00)', '2026-05-10 12:57:09', NULL, NULL, NULL),
(158, 12, 'Extend Controller', 'Extended Controller Rental for Session #26 by 300 min (Added ₱100.00)', '2026-05-10 12:57:11', NULL, NULL, NULL),
(159, 12, 'Extend Controller', 'Extended Controller Rental for Session #26 by 300 min (Added ₱100.00)', '2026-05-10 12:57:11', NULL, NULL, NULL),
(160, 12, 'Extend Controller', 'Extended Controller Rental for Session #26 by 300 min (Added ₱100.00)', '2026-05-10 12:57:12', NULL, NULL, NULL),
(161, 12, 'Extend Controller', 'Extended Controller Rental for Session #26 by 300 min (Added ₱100.00)', '2026-05-10 12:57:12', NULL, NULL, NULL),
(162, 12, 'Extend Controller', 'Extended Controller Rental for Session #26 by 300 min (Added ₱100.00)', '2026-05-10 12:57:12', NULL, NULL, NULL),
(163, 12, 'Extend Controller', 'Extended Controller Rental for Session #26 by 300 min (Added ₱100.00)', '2026-05-10 12:57:13', NULL, NULL, NULL),
(164, 12, 'Extend Controller', 'Extended Controller Rental for Session #26 by 300 min (Added ₱100.00)', '2026-05-10 12:57:13', NULL, NULL, NULL),
(165, 12, 'Extend Controller', 'Extended Controller Rental for Session #26 by 300 min (Added ₱100.00)', '2026-05-10 12:57:13', NULL, NULL, NULL),
(166, 12, 'Extend Controller', 'Extended Controller Rental for Session #26 by 300 min (Added ₱100.00)', '2026-05-10 12:57:13', NULL, NULL, NULL),
(167, 12, 'Extend Controller', 'Extended Controller Rental for Session #26 by 300 min (Added ₱100.00)', '2026-05-10 12:57:29', NULL, NULL, NULL),
(168, 12, 'Extend Controller', 'Extended Controller Rental for Session #26 by 120 min (Added ₱40.00)', '2026-05-10 12:57:36', NULL, NULL, NULL),
(169, 12, 'Extend Controller', 'Extended Controller Rental for Session #26 by 120 min (Added ₱40.00)', '2026-05-10 12:57:37', NULL, NULL, NULL),
(170, 12, 'Extend Controller', 'Extended Controller Rental for Session #26 by 120 min (Added ₱40.00)', '2026-05-10 12:57:37', NULL, NULL, NULL),
(171, 12, 'Controller rental', 'Ended controller rental early for session #26 (elapsed 138 min). Approved extras now ₱46.00.', '2026-05-10 13:04:03', NULL, NULL, NULL),
(172, 12, 'Controller rental', 'Ended controller rental early for session #26 (elapsed 138 min). Approved extras now ₱46.00.', '2026-05-10 13:04:08', NULL, NULL, NULL),
(173, 12, 'Controller rental', 'Ended controller rental early for session #26 (elapsed 138 min). Approved extras now ₱46.00.', '2026-05-10 13:04:13', NULL, NULL, NULL),
(174, 12, 'Controller rental', 'Ended controller rental early for session #26 (elapsed 138 min). Approved extras now ₱46.00.', '2026-05-10 13:04:18', NULL, NULL, NULL),
(175, 12, 'Extend Controller', 'Extended Controller Rental for Session #26 by 300 min (Added ₱100.00)', '2026-05-10 13:04:25', NULL, NULL, NULL),
(176, 12, 'Extend Controller', 'Extended Controller Rental for Session #26 by 300 min (Added ₱100.00)', '2026-05-10 13:06:14', NULL, NULL, NULL),
(177, 12, 'Extend Controller', 'Extended Controller Rental for Session #26 by 180 min (Added ₱60.00)', '2026-05-10 13:06:21', NULL, NULL, NULL),
(178, 12, 'Controller rental', 'Ended controller rental early for session #26 (elapsed 145 min). Approved extras now ₱48.33.', '2026-05-10 13:10:51', NULL, NULL, NULL),
(179, 12, 'Extend Controller', 'Extended Controller Rental for Session #26 by 300 min (Added ₱100.00)', '2026-05-10 13:10:53', NULL, NULL, NULL),
(180, 12, 'Extend Controller', 'Extended Controller Rental for Session #26 by 300 min (Added ₱100.00)', '2026-05-10 13:11:12', NULL, NULL, NULL),
(181, 12, 'Controller rental', 'Ended controller rental early for session #26 (elapsed 146 min). Approved extras now ₱48.67.', '2026-05-10 13:11:23', NULL, NULL, NULL),
(182, 12, 'Extend Controller', 'Extended Controller Rental for Session #26 by 300 min (Total Fee: ₱100)', '2026-05-10 13:32:46', NULL, NULL, NULL),
(183, 12, 'Extend Controller', 'Extended Controller Rental for Session #26 by 180 min (Total Fee: ₱160)', '2026-05-10 13:54:25', NULL, NULL, NULL),
(184, 12, 'Controller rental', 'Ended Controller #19 (CTRL-07) early on session #26 (elapsed 190 min). Prorated: ₱63.33 / Original: ₱160.00 / Refund: ₱96.67.', '2026-05-10 13:55:41', NULL, NULL, NULL),
(185, 12, 'End Session', 'Ended Session #26. Console: PS5-04. Duration: 190 min. Total Cost: ₱463.33.', '2026-05-10 13:55:59', NULL, NULL, NULL),
(186, 12, 'Start Session', 'Started Session #28 for Walk-in. Console: PS5-03. Mode: Unlimited', '2026-05-10 13:56:30', NULL, NULL, NULL),
(187, 12, 'Extend Controller', 'Extended Controller Rental for Session #28 by 300 min (Total Fee: ₱200)', '2026-05-10 13:58:18', NULL, NULL, NULL),
(188, 12, 'Extend Controller', 'Extended Controller Rental for Session #28 by 300 min (Total Fee: ₱400)', '2026-05-10 14:13:08', NULL, NULL, NULL),
(189, 12, 'Controller rental', 'Ended Controller #19 (CTRL-07) early on session #28 (elapsed 18 min). Prorated: ₱10.00 / Original: ₱200.00 / Refund: ₱190.00.', '2026-05-10 14:14:31', NULL, NULL, NULL),
(190, 12, 'Controller rental', 'Ended Controller #20 (8) early on session #28 (elapsed 18 min). Prorated: ₱10.00 / Original: ₱200.00 / Refund: ₱190.00.', '2026-05-10 14:14:37', NULL, NULL, NULL),
(191, 12, 'End Session', 'Ended Session #28. Console: PS5-03. Duration: 18 min. Total Cost: ₱420.00.', '2026-05-10 14:14:40', NULL, NULL, NULL),
(192, 12, 'Start Session', 'Started Session #29 for User #26. Console: PS5-02. Mode: Unlimited', '2026-05-10 14:16:50', NULL, NULL, NULL),
(193, 12, 'End Session', 'Ended Session #29. Console: PS5-02. Duration: 0 min. Total Cost: ₱560.00.', '2026-05-10 14:16:55', NULL, NULL, NULL),
(194, 12, 'Start Session', 'Started Session #30 for Walk-in. Console: PS5-02. Mode: Unlimited', '2026-05-10 14:26:50', NULL, NULL, NULL),
(195, 12, 'End Session', 'Ended Session #30. Console: PS5-02. Duration: 0 min. Total Cost: ₱400.00.', '2026-05-10 14:26:57', NULL, NULL, NULL),
(196, 12, 'Restore Session', 'Restored session #30 for User #0. Time elapsed since end: 0m 13s. Console: PS5-02. Original End Time: 2026-05-10 14:26:57. Payments made at end were voided.', '2026-05-10 14:27:10', NULL, NULL, NULL),
(197, 12, 'Extend Controller', 'Extended Controller Rental for Session #30 by 300 min (Total Fee: ₱200)', '2026-05-10 15:08:41', NULL, NULL, NULL),
(198, 12, 'End Session', 'Ended Session #30. Console: PS5-02. Duration: 42 min. Total Cost: ₱440.00.', '2026-05-10 15:08:50', NULL, NULL, NULL),
(199, 12, 'Start Session', 'Started Session #31 for Walk-in. Console: PS5-01. Mode: Unlimited', '2026-05-10 15:21:26', NULL, NULL, NULL),
(200, 12, 'End Session', 'Ended Session #31. Console: PS5-01. Duration: 0 min. Total Cost: ₱400.00.', '2026-05-10 15:21:36', NULL, NULL, NULL),
(201, 12, 'Start Session', 'Started Session #32 for Walk-in. Console: PS5-03. Mode: Hourly (90 min)', '2026-05-10 15:21:54', NULL, NULL, NULL),
(202, 12, 'End Session', 'Ended Session #32. Console: PS5-03. Duration: 0 min. Total Cost: ₱0.00.', '2026-05-10 15:21:58', NULL, NULL, NULL),
(203, 12, 'Start Session', 'Started Session #33 for Walk-in. Console: PS5-02. Mode: Unlimited', '2026-05-10 15:22:20', NULL, NULL, NULL),
(204, 12, 'Extend Controller', 'Extended Controller Rental for Session #33 by 60 min (Total Fee: ₱40)', '2026-05-10 15:36:12', NULL, NULL, NULL),
(205, 12, 'End Session', 'Ended Session #33. Console: PS5-02. Duration: 15 min. Total Cost: ₱420.00.', '2026-05-10 15:37:46', NULL, NULL, NULL),
(206, 12, 'Start Session', 'Started Session #34 for Walk-in. Console: PS5-04. Mode: Unlimited', '2026-05-10 15:38:22', NULL, NULL, NULL),
(207, 12, 'Tournament Status', 'Updated Tournament #3 status to Ongoing', '2026-05-10 15:41:15', NULL, NULL, NULL),
(208, 12, 'Tournament Status', 'Updated Tournament #3 status to Completed', '2026-05-10 15:41:17', NULL, NULL, NULL),
(209, 12, 'Create Tournament', 'Created Tournament #4: tikin sibin (tikin kivin) on PS5', '2026-05-10 15:42:00', NULL, NULL, NULL),
(210, 12, 'Tournament Status', 'Updated Tournament #4 status to Scheduled', '2026-05-10 15:42:01', NULL, NULL, NULL),
(211, 33, 'Tournament Registration', 'Registered and paid for tournament: tikin sibin', '2026-05-10 15:42:23', NULL, NULL, NULL),
(212, 12, 'End Session', 'Ended Session #34. Console: PS5-04. Duration: 8 min. Total Cost: ₱560.00.', '2026-05-10 15:46:33', NULL, NULL, NULL),
(213, 37, 'Tournament Registration', 'Registered and paid for tournament: tikin sibin', '2026-05-10 16:20:21', NULL, NULL, NULL),
(214, 12, 'Tournament Participant Archived', 'Archived Participant Sibe Loresco from Tournament: tikin sibin (#4) at May 10, 2026 04:20 PM', '2026-05-10 16:20:33', NULL, NULL, NULL),
(215, 12, 'Tournament Participant Archived', 'Archived Participant Gelo from Tournament: tikin sibin (#4) at May 10, 2026 04:20 PM', '2026-05-10 16:20:45', NULL, NULL, NULL),
(216, 12, 'Tournament Participant Restored', 'Restored Participant Sibe Loresco to Tournament: tikin sibin (#4) at May 10, 2026 04:21 PM', '2026-05-10 16:21:02', NULL, NULL, NULL),
(217, 12, 'Tournament Participant Restored', 'Restored Participant Gelo to Tournament: tikin sibin (#4) at May 10, 2026 04:21 PM', '2026-05-10 16:21:08', NULL, NULL, NULL),
(218, 12, 'Tournament Participant Archived', 'Archived Participant Sibe Loresco from Tournament: tikin sibin (#4) at May 10, 2026 04:23 PM', '2026-05-10 16:23:02', NULL, NULL, NULL),
(219, 12, 'Tournament Participant Archived', 'Archived Participant Gelo from Tournament: tikin sibin (#4) at May 10, 2026 04:31 PM', '2026-05-10 16:31:13', NULL, NULL, NULL),
(220, 12, 'Tournament Participant Restored', 'Restored Participant Sibe Loresco to Tournament: tikin sibin (#4) at May 10, 2026 04:31 PM', '2026-05-10 16:31:42', NULL, NULL, NULL),
(221, 12, 'Tournament Participant Restored', 'Restored Participant Gelo to Tournament: tikin sibin (#4) at May 10, 2026 04:31 PM', '2026-05-10 16:31:49', NULL, NULL, NULL),
(222, 12, 'Tournament Participant Archived', 'Archived Participant Sibe Loresco from Tournament: tikin sibin (#4) at May 10, 2026 04:34 PM', '2026-05-10 16:34:05', NULL, NULL, NULL),
(223, 12, 'Tournament Participant Archived', 'Archived Participant Sibe Loresco from Tournament: tikin sibin (#4) at May 10, 2026 04:34 PM', '2026-05-10 16:34:46', NULL, NULL, NULL),
(224, 12, 'Tournament Participant Archived', 'Archived Participant Gelo from Tournament: tikin sibin (#4) at May 10, 2026 05:04 PM', '2026-05-10 17:04:19', NULL, NULL, NULL),
(225, 12, 'Controller Type', 'Archived Controller Type ID #104', '2026-05-10 17:06:06', NULL, NULL, NULL),
(226, 12, 'Controller Type', 'Restored Controller Type ID #104', '2026-05-10 17:18:21', NULL, NULL, NULL),
(227, 12, 'Controller Type', 'Archived Controller Type ID #104', '2026-05-10 17:24:20', NULL, NULL, NULL),
(228, 12, 'Edit Console', 'Updated Console ID #6: PS4 Unit 6, Unit: PS4-06', '2026-05-10 17:44:12', NULL, NULL, NULL),
(229, 12, 'Edit Console', 'Updated Console ID #6: PS4 Unit 6, Unit: PS4-06', '2026-05-10 17:44:24', NULL, NULL, NULL),
(230, 12, 'Console Status', 'Updated Console ID #6 status to Archived', '2026-05-10 17:44:31', NULL, NULL, NULL),
(231, 12, 'Delete Console', 'Permanently deleted Console: PS4 Unit 6 (Unit: PS4-06, Type: PS4)', '2026-05-10 17:44:47', NULL, NULL, NULL),
(232, 12, 'Add Console', 'Added new console: PS4 UNIT 6, Unit: PS4-06', '2026-05-10 17:45:33', NULL, NULL, NULL),
(233, 12, 'Console Status', 'Updated Console ID #3229 status to Maintenance', '2026-05-10 17:45:38', NULL, NULL, NULL),
(234, 12, 'Edit Console', 'Updated Console ID #3229: PS4 UNIT 6, Unit: PS4-06', '2026-05-10 17:45:52', NULL, NULL, NULL),
(235, 12, 'Edit Console', 'Updated Console ID #3229: PS4 UNIT 6, Unit: PS4-07', '2026-05-10 17:46:52', NULL, NULL, NULL),
(236, 12, 'Edit Console', 'Updated Console ID #3229: PS4 UNIT 6, Unit: PS4-06', '2026-05-10 17:46:58', NULL, NULL, NULL),
(237, 37, 'Reschedule Confirm', 'User confirmed reschedule for Reservation #45 to 2026-05-11 12:00', '2026-05-10 17:49:30', NULL, NULL, NULL),
(238, 12, 'Tournament Participant Restored', 'Restored Participant Sibe Loresco to Tournament: tikin sibin (#4) at May 10, 2026 05:52 PM', '2026-05-10 17:52:58', NULL, NULL, NULL),
(239, 12, 'Tournament Participant Restored', 'Restored Participant Gelo to Tournament: tikin sibin (#4) at May 10, 2026 05:53 PM', '2026-05-10 17:53:05', NULL, NULL, NULL),
(240, 12, 'Edit Console', 'Updated Console ID #3229: PS4 UNIT 6 (PS4-06)', '2026-05-10 18:09:33', NULL, NULL, NULL),
(241, 12, 'Start Session', 'Started Session #35 for Walk-in. Console: PS5-02. Mode: Open_time', '2026-05-10 18:22:27', NULL, NULL, NULL),
(242, 12, 'Controller Type', 'Archived Controller Type ID #102', '2026-05-10 18:23:37', NULL, NULL, NULL),
(243, 12, 'Controller Type', 'Archived Controller Type ID #101', '2026-05-10 18:23:46', NULL, NULL, NULL),
(244, 12, 'Controller Type', 'Added new controller type: xbox controller 1 (for Xbox Series X)', '2026-05-10 18:24:07', NULL, NULL, NULL),
(245, 12, 'Add Controller', 'Added new controller: xbox ctrl 1 (xbox controller 1)', '2026-05-10 18:55:53', NULL, NULL, NULL),
(246, 12, 'Controller Type', 'Added new controller type: ps5 ctrl1 (for PS5)', '2026-05-10 18:56:35', NULL, NULL, NULL),
(247, 12, 'Add Controller', 'Added new controller: new ctrl (ps5 ctrl1)', '2026-05-10 18:56:54', NULL, NULL, NULL),
(248, 12, 'Console Type', 'Added new console type: PS7', '2026-05-10 22:41:07', NULL, NULL, NULL),
(249, 12, 'Add Console', 'Added new console: PS7 UNIT 1, Unit: PS7-01', '2026-05-10 22:42:08', NULL, NULL, NULL),
(250, 12, 'Edit Console', 'Updated Console ID #3230: PS7 UNIT 1 (PS7-01)', '2026-05-10 22:42:25', NULL, NULL, NULL),
(251, 12, 'Edit Console', 'Updated Console ID #3230: PS7 UNIT 1 (PS7-01)', '2026-05-10 22:42:36', NULL, NULL, NULL),
(252, 12, 'Controller Type', 'Added new controller type: PS7 (for PS7)', '2026-05-10 22:43:07', NULL, NULL, NULL),
(253, 12, 'Add Controller', 'Added new controller: PS7-01 (PS7)', '2026-05-10 22:43:52', NULL, NULL, NULL),
(254, 12, 'End Session', 'Ended Session #36. Console: PS5-03. Duration: 313 min. Total Cost: â‚±340.00.', '2026-05-10 23:44:05', NULL, NULL, NULL),
(255, 12, 'End Session', 'Ended Session #35. Console: PS5-02. Duration: 321 min. Total Cost: â‚±580.00.', '2026-05-10 23:44:17', NULL, NULL, NULL),
(256, 12, 'Start Session', 'Started Session #37 for Walk-in. Console: PS5-03. Mode: Hourly (240 min)', '2026-05-11 08:03:18', NULL, NULL, NULL),
(257, 12, 'Start Session', 'Started Session #38 for Walk-in. Console: PS5-02. Mode: Hourly (210 min)', '2026-05-11 08:28:30', NULL, NULL, NULL),
(258, 12, 'Record Payment', 'Recorded payment of ₱800.00 via Cash for Session #35', '2026-05-11 08:28:46', NULL, NULL, NULL),
(259, 12, 'Record Payment', 'Recorded payment of ₱866.00 via Cash for Session #36', '2026-05-11 08:28:49', NULL, NULL, NULL),
(260, 12, 'Start Session', 'Started Session #39 for Walk-in. Console: PS5-05. Mode: Unlimited', '2026-05-11 08:33:03', NULL, NULL, NULL),
(261, 12, 'System Settings', 'Updated system settings and synced console rates.', '2026-05-11 09:04:53', NULL, NULL, NULL),
(262, 39, 'Tournament Registration', 'Registered and paid for tournament: tikin sibin', '2026-05-11 10:04:45', NULL, NULL, NULL),
(263, 12, 'Tournament Payment Updated', 'Updated payment status to PENDING for Participant Sibe in Tournament: tikin sibin (#4)', '2026-05-11 10:05:04', NULL, NULL, NULL),
(264, 12, 'Tournament Payment Updated', 'Updated payment status to PAID for Participant Sibe in Tournament: tikin sibin (#4)', '2026-05-11 10:05:04', NULL, NULL, NULL),
(265, 12, 'Shopkeeper Account Creation', 'Created new Shopkeeper account for Kian Pablo (kianpablo1996@gmail.com)', '2026-05-11 10:26:12', NULL, NULL, NULL),
(266, 12, 'Shopkeeper Account Creation', 'Created new Shopkeeper account for Kian Pablo (kianpablo1996@gmail.com)', '2026-05-11 10:27:30', NULL, NULL, NULL),
(267, 12, 'Start Session', 'Started Session #40 for Walk-in. Console: PS5-01. Mode: Hourly (60 min)', '2026-05-11 11:03:40', NULL, NULL, NULL),
(268, 12, 'End All Controller Rentals', 'Ended ALL controller rentals for Session #39. IDs [19,20] (2h 30m). Final Fee: ₱100', '2026-05-11 11:03:51', NULL, NULL, NULL),
(269, 12, 'End Session', 'Ended Session #39. Console: PS5-05. Duration: 150 min. Total Cost: ₱400.00.', '2026-05-11 11:03:54', NULL, NULL, NULL),
(270, 12, 'Reschedule Proposal', 'Proposed new schedule for Reservation #61: 2026-05-12 13:30. Reason: Typhoon', '2026-05-11 11:53:58', NULL, NULL, NULL),
(271, 12, 'Reschedule Proposal', 'Proposed new schedule for Reservation #62: 2026-05-12 16:30. Reason: Power_outage', '2026-05-11 11:58:35', NULL, NULL, NULL),
(272, 27, 'Reschedule Confirm', 'User confirmed reschedule for Reservation #62 to 2026-05-12 16:30', '2026-05-11 11:58:55', NULL, NULL, NULL),
(273, 12, 'Reschedule Proposal', 'Proposed new schedule for Reservation #62: 2026-05-11 14:00. Reason: Typhoon', '2026-05-11 11:59:08', NULL, NULL, NULL),
(274, 27, 'Reschedule Confirm', 'User confirmed reschedule for Reservation #62 to 2026-05-11 14:00', '2026-05-11 11:59:27', NULL, NULL, NULL),
(275, 12, 'Reschedule Proposal', 'Proposed new schedule for Reservation #62: 2026-05-20 14:00. Reason: Typhoon', '2026-05-11 11:59:48', NULL, NULL, NULL),
(276, 27, 'Reschedule Confirm', 'User confirmed reschedule for Reservation #62 to 2026-05-20 14:00', '2026-05-11 12:00:08', NULL, NULL, NULL),
(277, 12, 'Reschedule Proposal', 'Proposed new schedule for Reservation #62: 2026-05-22 14:00. Reason: Power_outage', '2026-05-11 12:00:27', NULL, NULL, NULL),
(278, 27, 'Reschedule Confirm', 'User confirmed reschedule for Reservation #62 to 2026-05-22 14:00', '2026-05-11 12:01:26', NULL, NULL, NULL),
(279, 12, 'Reschedule Proposal', 'Proposed new schedule for Reservation #62: 2026-05-22 15:00. Reason: Emergency', '2026-05-11 12:02:37', NULL, NULL, NULL),
(280, 27, 'Reschedule Decline', 'User declined reschedule proposal for Reservation #62', '2026-05-11 12:03:09', NULL, NULL, NULL),
(281, 12, 'Record Payment', 'Recorded payment of ₱280.00 via Cash for Session #37', '2026-05-11 12:05:38', NULL, NULL, NULL),
(282, 12, 'Start Session', 'Started Session #41 for Walk-in. Console: PS7-01. Mode: Hourly (120 min)', '2026-05-11 12:07:02', NULL, NULL, NULL),
(283, 12, 'End Session', 'Ended Session #40. Console: PS5-01. Duration: 70 min. Total Cost: ₱130.00.', '2026-05-11 12:13:48', NULL, NULL, NULL),
(284, 12, 'End Session', 'Ended Session #41. Console: PS7-01. Duration: 73 min. Total Cost: ₱100.00.', '2026-05-11 12:13:52', NULL, NULL, NULL),
(285, 12, 'End Session', 'Ended Session #38. Console: PS5-02. Duration: 225 min. Total Cost: ₱260.00.', '2026-05-11 12:13:57', NULL, NULL, NULL),
(286, 12, 'End Session', 'Ended Session #37. Console: PS5-03. Duration: 250 min. Total Cost: ₱300.00.', '2026-05-11 12:14:01', NULL, NULL, NULL),
(287, 12, 'Record Payment', 'Recorded payment of ₱30.00 via Cash for Session #40', '2026-05-11 12:14:06', NULL, NULL, NULL),
(288, 12, 'Start Session', 'Started Session #42 for Walk-in. Console: PS5-02. Mode: Hourly (90 min)', '2026-05-11 12:14:24', NULL, NULL, NULL),
(289, 12, 'Edit Console', 'Updated Console ID #2: PS5 Unit 2 (PS5-02)', '2026-05-11 12:14:46', NULL, NULL, NULL),
(290, 12, 'End Session', 'Ended Session #42. Console: PS5-02. Duration: 62 min. Total Cost: ₱80.00.', '2026-05-11 12:16:53', NULL, NULL, NULL),
(291, 12, 'Start Session', 'Started Session #43 for Walk-in. Console: PS5-03. Mode: Hourly (60 min)', '2026-05-11 12:17:09', NULL, NULL, NULL),
(292, 12, 'End Session', 'Ended Session #43. Console: PS5-03. Duration: 70 min. Total Cost: ₱100.00.', '2026-05-11 12:28:02', NULL, NULL, NULL),
(293, 12, 'Start Session', 'Started Session #44 for Walk-in. Console: PS5-01. Mode: Hourly (60 min)', '2026-05-11 12:28:23', NULL, NULL, NULL),
(294, 12, 'End Session', 'Ended Session #44. Console: PS5-01. Duration: 51 min. Total Cost: ₱80.00.', '2026-05-11 13:19:34', NULL, NULL, NULL),
(295, 12, 'Start Session', 'Started Session #45 for Walk-in. Console: PS5-04. Mode: Hourly (90 min)', '2026-05-11 13:20:10', NULL, NULL, NULL),
(296, 12, 'End Session', 'Ended Session #45. Console: PS5-04. Duration: 71 min. Total Cost: ₱100.00.', '2026-05-11 13:31:30', NULL, NULL, NULL),
(297, 12, 'Start Session', 'Started Session #46 for Walk-in. Console: PS5-02. Mode: Hourly (60 min)', '2026-05-11 13:31:48', NULL, NULL, NULL),
(298, 12, 'End Session', 'Ended Session #46. Console: PS5-02. Duration: 69 min. Total Cost: ₱100.00.', '2026-05-11 13:41:00', NULL, NULL, NULL),
(299, 12, 'Start Session', 'Started Session #47 for Walk-in. Console: PS5-04. Mode: Hourly (60 min)', '2026-05-11 13:41:14', NULL, NULL, NULL),
(300, 12, 'Confirm Reservation', 'Confirmed Reservation #61', '2026-05-11 13:48:57', NULL, NULL, NULL),
(301, 27, 'Reschedule Request', 'Customer requested to reschedule Reservation #62 to 2026-05-22 14:00', '2026-05-11 13:49:12', NULL, NULL, NULL),
(302, 12, 'Reschedule Approve', 'Approved reschedule request #39 for Reservation #62', '2026-05-11 13:49:26', NULL, NULL, NULL),
(303, 27, 'Reschedule Request', 'Customer requested to reschedule Reservation #62 to 2026-05-22 14:00', '2026-05-11 13:49:34', NULL, NULL, NULL),
(304, 12, 'Reschedule Approve', 'Approved reschedule request #40 for Reservation #62', '2026-05-11 13:49:48', NULL, NULL, NULL),
(305, 27, 'Reschedule Request', 'Customer requested to reschedule Reservation #63 to 2026-05-11 17:30', '2026-05-11 13:52:18', NULL, NULL, NULL),
(306, 12, 'Reschedule Approve', 'Approved reschedule request #41 for Reservation #63', '2026-05-11 13:52:36', NULL, NULL, NULL),
(307, 27, 'Reschedule Request', 'Customer requested to reschedule Reservation #63 to 2026-05-11 17:30', '2026-05-11 13:52:49', NULL, NULL, NULL),
(308, 12, 'Reschedule Approve', 'Approved reschedule request #42 for Reservation #63', '2026-05-11 13:52:57', NULL, NULL, NULL),
(309, 27, 'Reschedule Request', 'Customer requested to reschedule Reservation #64 to 2026-05-11 16:30', '2026-05-11 14:00:47', NULL, NULL, NULL),
(310, 12, 'Reschedule Approve', 'Approved reschedule request #43 for Reservation #64', '2026-05-11 14:01:43', NULL, NULL, NULL),
(311, 27, 'Reschedule Request', 'Customer requested to reschedule Reservation #64 to 2026-05-11 16:30', '2026-05-11 14:01:47', NULL, NULL, NULL),
(312, 12, 'Reschedule Approve', 'Approved reschedule request #44 for Reservation #64', '2026-05-11 14:02:03', NULL, NULL, NULL),
(313, 12, 'Start Session', 'Started Session #49 for User #27. Console: PS5-01. Mode: Hourly (60 min)', '2026-05-11 15:12:27', NULL, NULL, NULL),
(314, 12, 'End Session', 'Ended Session #49. Console: PS5-01. Duration: 0 min. Total Cost: ₱0.00.', '2026-05-11 15:12:34', NULL, NULL, NULL),
(315, 12, 'End Session', 'Ended Session #48. Console: PS5-03. Duration: 83 min. Total Cost: ₱220.00.', '2026-05-11 15:12:39', NULL, NULL, NULL),
(316, 12, 'End Session', 'Ended Session #47. Console: PS5-04. Duration: 151 min. Total Cost: ₱200.00.', '2026-05-11 15:12:43', NULL, NULL, NULL),
(317, 12, 'Start Session', 'Started Session #50 for Walk-in. Console: PS5-02. Mode: Hourly (150 min)', '2026-05-11 15:20:13', NULL, NULL, NULL),
(318, 12, 'Start Session', 'Started Session #51 for User #27. Console: PS5-03. Mode: Hourly (30 min)', '2026-05-11 15:26:34', NULL, NULL, NULL),
(319, 12, 'End Session', 'Ended Session #51. Console: PS5-03. Duration: 3048 min. Total Cost: ₱3,270.00.', '2026-05-13 18:14:59', NULL, NULL, NULL),
(320, 12, 'End Session', 'Ended Session #50. Console: PS5-02. Duration: 3114 min. Total Cost: ₱3,360.00.', '2026-05-13 18:15:02', NULL, NULL, NULL),
(321, 12, 'Start Session', 'Started Session #52 for Walk-in. Console: PS5-05. Mode: Hourly (60 min)', '2026-05-13 18:15:33', NULL, NULL, NULL),
(322, 12, 'End Session', 'Ended Session #52. Console: PS5-05. Duration: 86 min. Total Cost: ₱120.00.', '2026-05-13 18:42:21', NULL, NULL, NULL),
(323, 12, 'Start Session', 'Started Session #54 for Walk-in. Console: PS5-05. Mode: Hourly (180 min)', '2026-05-13 18:42:36', NULL, NULL, NULL);

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
-- Table structure for table `blocked_dates`
--

CREATE TABLE `blocked_dates` (
  `id` int(11) NOT NULL,
  `blocked_date` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `hourly_rate` decimal(10,2) DEFAULT NULL,
  `status` enum('available','in_use','maintenance','archived') NOT NULL DEFAULT 'available',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `otes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `consoles`
--

INSERT INTO `consoles` (`console_id`, `console_name`, `console_type_id`, `controller_count`, `unit_number`, `hourly_rate`, `status`, `created_at`, `otes`) VALUES
(1, 'PS5 Unit 1', 1, 2, 'PS5-01', 120.00, 'available', '2026-02-21 19:55:41', NULL),
(2, 'PS5 Unit 2', 1, 2, 'PS5-02', 120.00, 'available', '2026-02-21 19:55:41', NULL),
(3, 'PS5 Unit 3', 1, 2, 'PS5-03', NULL, 'available', '2026-02-21 19:55:41', NULL),
(4, 'PS5 Unit 4', 1, 2, 'PS5-04', NULL, 'available', '2026-02-21 19:55:41', NULL),
(5, 'PS5 Unit 5', 1, 2, 'PS5-05', NULL, 'in_use', '2026-02-21 19:55:41', NULL),
(7, 'Xbox Unit 7', 3, 2, 'XBX-07', NULL, 'available', '2026-02-21 19:55:41', NULL),
(8, 'Xbox Unit 8', 3, 2, 'XBX-08', NULL, 'available', '2026-02-21 19:55:41', NULL),
(3229, 'PS4 UNIT 6', 2, 1, 'PS4-06', 80.00, 'maintenance', '2026-05-10 17:45:33', NULL),
(3230, 'PS7 UNIT 1', 152, 1, 'PS7-01', 100.00, 'available', '2026-05-10 22:42:08', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `console_types`
--

CREATE TABLE `console_types` (
  `console_type_id` int(11) NOT NULL,
  `type_name` varchar(50) NOT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `category` enum('console') NOT NULL DEFAULT 'console',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `hourly_rate` decimal(10,2) NOT NULL DEFAULT 80.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `console_types`
--

INSERT INTO `console_types` (`console_type_id`, `type_name`, `is_archived`, `category`, `created_at`, `hourly_rate`) VALUES
(1, 'PS5', 0, 'console', '2026-05-08 04:10:19', 130.00),
(2, 'PS4', 0, 'console', '2026-05-08 04:10:19', 80.00),
(3, 'Xbox Series X', 0, 'console', '2026-05-08 04:10:19', 80.00),
(12, 'PS6', 0, 'console', '2026-05-08 04:10:19', 80.00),
(152, 'PS7', 0, 'console', '2026-05-10 14:41:07', 80.00);

-- --------------------------------------------------------

--
-- Table structure for table `controllers`
--

CREATE TABLE `controllers` (
  `controller_id` int(11) NOT NULL,
  `controller_type_id` int(11) DEFAULT NULL,
  `unit_number` varchar(50) NOT NULL,
  `hourly_rate` decimal(10,2) NOT NULL DEFAULT 20.00,
  `status` enum('available','in_use','maintenance','archived') NOT NULL DEFAULT 'available',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `console_type_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `controllers`
--

INSERT INTO `controllers` (`controller_id`, `controller_type_id`, `unit_number`, `hourly_rate`, `status`, `notes`, `created_at`, `updated_at`, `console_type_id`) VALUES
(4, 15, 'CTRL-02', 20.00, 'available', NULL, '2026-05-08 04:10:19', '2026-05-10 10:51:15', 3),
(13, 15, 'CTRL-03', 20.00, 'available', NULL, '2026-05-08 04:10:19', '2026-05-10 10:51:15', 3),
(14, 15, 'CTRL-04', 20.00, 'available', NULL, '2026-05-08 04:10:19', '2026-05-10 10:51:15', 3),
(18, 101, 'CTRL-06', 20.00, 'archived', 'joy con', '2026-05-08 05:05:20', '2026-05-10 10:54:23', NULL),
(19, 13, 'CTRL-07', 20.00, 'available', '', '2026-05-08 06:14:28', '2026-05-11 03:03:51', 1),
(20, 13, '8', 20.00, 'available', '', '2026-05-08 14:07:22', '2026-05-11 03:03:51', 1),
(21, 102, '67', 20.00, 'archived', '', '2026-05-09 02:11:03', '2026-05-10 10:54:23', NULL),
(22, 102, 'CTRL-08', 20.00, 'archived', 'minor', '2026-05-09 02:54:00', '2026-05-10 10:54:23', NULL),
(23, 103, 'ctrl sa pc', 20.00, 'archived', '', '2026-05-09 06:50:14', '2026-05-10 10:54:23', NULL),
(29, 105, 'xbox ctrl 1', 20.00, 'available', '', '2026-05-10 10:55:53', '2026-05-10 10:55:53', 3),
(30, 106, 'new ctrl', 20.00, 'available', '', '2026-05-10 10:56:54', '2026-05-11 07:12:39', 1),
(31, 107, 'PS7-01', 20.00, 'available', '', '2026-05-10 14:43:52', '2026-05-10 14:43:52', 152);

-- --------------------------------------------------------

--
-- Table structure for table `controller_types`
--

CREATE TABLE `controller_types` (
  `controller_type_id` int(11) NOT NULL,
  `type_name` varchar(50) NOT NULL,
  `console_type_id` int(11) DEFAULT NULL COMMENT 'FK -> console_types.type_id',
  `is_archived` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `controller_types`
--

INSERT INTO `controller_types` (`controller_type_id`, `type_name`, `console_type_id`, `is_archived`, `created_at`) VALUES
(13, 'DualSense', 1, 0, '2026-05-08 05:00:32'),
(14, 'DualShock 4', 2, 0, '2026-05-08 05:00:32'),
(15, 'Xbox Controller', 3, 0, '2026-05-08 05:00:32'),
(101, 'joy con', NULL, 1, '2026-05-08 05:04:46'),
(102, 'hello', NULL, 1, '2026-05-09 01:43:20'),
(103, 'pc1 controller', NULL, 1, '2026-05-09 06:49:53'),
(104, 'AMD RADEON', 1, 1, '2026-05-09 16:06:06'),
(105, 'xbox controller 1', 3, 0, '2026-05-10 10:24:07'),
(106, 'ps5 ctrl1', 1, 0, '2026-05-10 10:56:35'),
(107, 'PS7', 152, 0, '2026-05-10 14:43:07');

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

CREATE TABLE `games` (
  `game_id` int(11) NOT NULL,
  `game_name` varchar(150) NOT NULL,
  `console_type_id` int(11) DEFAULT NULL,
  `genre` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `games`
--

INSERT INTO `games` (`game_id`, `game_name`, `console_type_id`, `genre`, `is_active`, `is_available`, `created_at`) VALUES
(1, 'Tekken 8', NULL, 'Fighting', 1, 1, '2026-05-13 16:12:02'),
(2, 'EA Sports FC 25', NULL, 'Sports', 1, 1, '2026-05-13 16:12:02'),
(3, 'Call of Duty: Modern Warfare III', NULL, 'FPS', 1, 1, '2026-05-13 16:12:02'),
(4, 'Mortal Kombat 1', NULL, 'Fighting', 1, 1, '2026-05-13 16:12:02'),
(5, 'NBA 2K25', NULL, 'Sports', 1, 1, '2026-05-13 16:12:02'),
(6, 'God of War Ragnarok', NULL, 'Action-Adventure', 1, 1, '2026-05-13 16:12:02'),
(7, 'Spider-Man 2', NULL, 'Action-Adventure', 1, 1, '2026-05-13 16:12:02'),
(8, 'Fortnite', NULL, 'Battle Royale', 1, 1, '2026-05-13 16:12:02'),
(9, 'Valorant', NULL, 'FPS', 1, 1, '2026-05-13 16:12:02'),
(10, 'Minecraft', NULL, 'Sandbox', 1, 1, '2026-05-13 16:12:02');

-- --------------------------------------------------------

--
-- Table structure for table `gaming_sessions`
--

CREATE TABLE `gaming_sessions` (
  `session_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
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
  `notes` text DEFAULT NULL,
  `is_walkin` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` enum('reservation_update','session_alert','tournament','system','promotion') NOT NULL DEFAULT 'system',
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `reference_type` enum('reservation','session','tournament','none') DEFAULT 'none',
  `reference_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `method_id` tinyint(3) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `label` varchar(60) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`method_id`, `code`, `label`, `is_active`, `created_at`) VALUES
(1, 'cash', 'Cash', 1, '2026-05-13 18:48:22'),
(2, 'gcash', 'GCash', 1, '2026-05-13 18:48:22'),
(3, 'credit_card', 'Credit Card', 1, '2026-05-13 18:48:22'),
(7, 'maya_pay', 'Maya', 1, '2026-05-13 18:50:26');

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
  `controller_id_2` int(11) DEFAULT NULL,
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
  `old_controller_id` int(11) DEFAULT NULL,
  `old_controller_id_2` int(11) DEFAULT NULL,
  `new_date` date NOT NULL,
  `new_time` time NOT NULL,
  `console_id` int(11) DEFAULT NULL,
  `new_console_type_id` int(11) DEFAULT NULL,
  `new_controller_id` int(11) DEFAULT NULL,
  `new_controller_id_2` int(11) DEFAULT NULL,
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
(17, 14, 12, 12, 15, 22.50, 'cash', 'approved', 'Direct extension by staff', '2026-05-09 13:24:52', '2026-05-09 13:24:52'),
(18, 15, 12, 12, 30, 45.00, 'cash', 'approved', 'Direct extension by staff', '2026-05-09 16:52:20', '2026-05-09 16:52:20');

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
(196, 'brevo_api_key', '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', NULL, '2026-05-11 09:04:53'),
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
(3, 'tiken it', NULL, 'tekin eyt', 'PS5', '2026-05-16 12:00:00', '2026-05-16 18:00:00', 250.00, 0.00, 0, 'completed', '', 12, '2026-05-09 12:56:25'),
(4, 'tikin sibin', NULL, 'tikin kivin', 'PS5', '2026-05-21 13:00:00', '2026-05-21 17:00:00', 250.00, 0.00, 0, 'scheduled', '', 12, '2026-05-10 15:42:00');

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
  `status` enum('active','removed','archived') NOT NULL DEFAULT 'active',
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
(5, 3, 39, '2026-05-09 14:23:45', 'paid', 'removed', '2026-05-09 15:32:13', 1, NULL, NULL, 'Sugarcoat', '09944084214', NULL, NULL, NULL, '', NULL, NULL, NULL),
(6, 3, 32, '2026-05-09 15:05:30', 'paid', 'archived', '2026-05-09 18:32:25', 0, NULL, NULL, 'SUGARDAD', '09944084214', NULL, NULL, NULL, '', NULL, NULL, NULL),
(7, 3, 33, '2026-05-09 15:31:58', 'pending', 'removed', '2026-05-09 15:32:35', 1, NULL, NULL, 'SUGARDAD', '09944084214', NULL, NULL, NULL, '', NULL, NULL, NULL),
(9, 3, 40, '2026-05-09 18:23:08', 'paid', 'active', NULL, 0, NULL, NULL, 'Lads', '09124173609', NULL, NULL, NULL, 'PALUBOG', 'cs_8be2d5a3bc7e0d710c8055e2', 'pay_uKEXgyCnja6wNtDtDQH73rMf', 'paid'),
(10, 4, 33, '2026-05-10 15:42:23', 'paid', 'active', NULL, 0, NULL, NULL, 'Sugarcoat', '09944084214', NULL, NULL, NULL, '', 'cs_afedd03c74cde2f4895712cc', 'pay_81fo8afTaM6ttxhCHqWfdazW', 'paid'),
(11, 4, 37, '2026-05-10 16:20:21', 'paid', 'active', NULL, 0, NULL, NULL, 'SUGARMOM', '09944084214', NULL, NULL, NULL, '', 'cs_4b0b97718ff84755d0cd3eda', 'pay_MuzoyWD7bCqWAvfNDVDUgG5N', 'paid'),
(12, 4, 39, '2026-05-11 10:04:45', 'paid', 'active', NULL, 0, NULL, NULL, 'Sevi', '09944084214', NULL, NULL, NULL, 'Palubog Esports', 'cs_341347d52b2f267f0d0454b9', 'pay_HxubYvukVcaAAQCWq9miU48k', 'paid');

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
  `payment_method_id` tinyint(3) UNSIGNED DEFAULT NULL,
  `payment_method` enum('cash','gcash','credit_card') NOT NULL DEFAULT 'cash',
  `payment_status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `transaction_date` datetime NOT NULL DEFAULT current_timestamp(),
  `processed_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transaction_id`, `session_id`, `user_id`, `amount`, `tendered_amount`, `shortfall_amount`, `payment_note`, `payment_method_id`, `payment_method`, `payment_status`, `transaction_date`, `processed_by`, `created_at`) VALUES
(123, NULL, 33, 34.00, 34.00, NULL, 'Downpayment for reservation #34', 2, 'gcash', 'completed', '2026-05-08 12:01:01', 33, '2026-05-08 12:01:01'),
(124, 1, 33, 34.00, 34.00, NULL, 'Downpayment transferred from reservation #35', 2, 'gcash', 'completed', '2026-05-08 13:51:55', 12, '2026-05-08 13:51:55'),
(125, 1, 33, 6.00, 6.00, NULL, 'Balance payment collected at session end', 1, 'cash', 'completed', '2026-05-08 14:18:18', 12, '2026-05-08 14:18:18'),
(126, 2, 0, 400.00, 400.00, 0.00, NULL, 1, 'cash', 'completed', '2026-05-08 14:20:05', 12, '2026-05-08 14:20:05'),
(127, 4, 33, 34.00, 34.00, NULL, 'Downpayment transferred from reservation #36', 2, 'gcash', 'completed', '2026-05-08 14:24:45', 12, '2026-05-08 14:24:45'),
(128, NULL, 33, 40.00, 40.00, NULL, 'Downpayment for reservation #37', 2, 'gcash', 'completed', '2026-05-08 14:39:15', 33, '2026-05-08 14:39:15'),
(129, NULL, 27, 25.00, 25.00, NULL, 'Downpayment for reservation #39', 1, 'cash', 'completed', '2026-05-08 17:43:26', 27, '2026-05-08 17:43:26'),
(130, 5, 27, 60.00, 60.00, NULL, 'Balance payment collected at session end', 1, 'cash', 'completed', '2026-05-08 17:46:45', 12, '2026-05-08 17:46:45'),
(131, 6, 27, 25.00, 25.00, NULL, 'Downpayment transferred from reservation #40', 1, 'cash', 'completed', '2026-05-08 17:47:40', 12, '2026-05-08 17:47:40'),
(132, 3, 0, 260.00, 260.00, NULL, 'Balance payment collected', 1, 'cash', 'completed', '2026-05-08 17:57:03', 12, '2026-05-08 17:57:03'),
(133, 4, 33, 206.00, 206.00, NULL, 'Balance payment collected', 1, 'cash', 'completed', '2026-05-08 17:57:09', 12, '2026-05-08 17:57:09'),
(134, NULL, 27, 24.00, 24.00, NULL, 'Downpayment for reservation #41', 2, 'gcash', 'completed', '2026-05-08 17:58:10', 27, '2026-05-08 17:58:10'),
(135, NULL, 27, 24.00, 24.00, NULL, 'Downpayment for reservation #42', 2, 'gcash', 'completed', '2026-05-08 18:05:08', 27, '2026-05-08 18:05:08'),
(136, 6, 27, 15.00, 15.00, NULL, 'Balance payment collected at session end', 1, 'cash', 'completed', '2026-05-08 18:19:17', 12, '2026-05-08 18:19:17'),
(137, 4, 33, 20.00, 20.00, NULL, 'Balance payment collected', 1, 'cash', 'completed', '2026-05-08 18:19:29', 12, '2026-05-08 18:19:29'),
(138, 3, 0, 40.00, 240.00, NULL, 'Balance payment collected at session end', 1, 'cash', 'completed', '2026-05-08 18:19:35', 12, '2026-05-08 18:19:35'),
(139, NULL, 27, 24.00, 24.00, NULL, 'Downpayment for reservation #43', 2, 'gcash', 'completed', '2026-05-08 18:24:56', 27, '2026-05-08 18:24:56'),
(140, 7, 0, 120.00, 120.00, NULL, NULL, 1, 'cash', 'completed', '2026-05-08 18:40:30', 12, '2026-05-08 18:40:30'),
(141, 7, 0, -120.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 00m (₱0.00), refunding unused time (₱120.00)', NULL, '', 'completed', '2026-05-08 18:40:47', 12, '2026-05-08 18:40:47'),
(142, 8, 0, 60.00, 60.00, NULL, NULL, 1, 'cash', 'completed', '2026-05-08 18:49:11', 12, '2026-05-08 18:49:11'),
(143, 8, 0, -60.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 00m (₱0.00), refunding unused time (₱60.00)', NULL, '', 'completed', '2026-05-08 18:49:16', 12, '2026-05-08 18:49:16'),
(144, 9, 0, 80.00, 80.00, NULL, NULL, 1, 'cash', 'completed', '2026-05-08 18:51:18', 12, '2026-05-08 18:51:18'),
(145, 9, 0, -60.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 00m (₱20.00), refunding unused time (₱60.00)', NULL, '', 'completed', '2026-05-08 18:51:25', 12, '2026-05-08 18:51:25'),
(146, 10, 0, 60.00, 60.00, NULL, NULL, 1, 'cash', 'completed', '2026-05-08 18:53:05', 12, '2026-05-08 18:53:05'),
(147, 10, 0, -60.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 00m (₱0.00), refunding unused time (₱60.00)', NULL, '', 'completed', '2026-05-08 18:53:30', 12, '2026-05-08 18:53:30'),
(148, 9, 0, 40.00, NULL, NULL, NULL, 1, 'cash', 'completed', '2026-05-08 19:21:20', 12, '2026-05-08 19:21:20'),
(149, 10, 0, 40.00, NULL, NULL, NULL, 1, 'cash', 'completed', '2026-05-08 19:22:53', 12, '2026-05-08 19:22:53'),
(150, 8, 0, 60.00, NULL, NULL, NULL, 1, 'cash', 'completed', '2026-05-08 19:23:31', 12, '2026-05-08 19:23:31'),
(151, 9, 0, 20.00, 20.00, NULL, 'Balance payment collected at session end', 1, 'cash', 'completed', '2026-05-08 19:23:34', 12, '2026-05-08 19:23:34'),
(152, 11, 0, 20.00, 20.00, NULL, 'Balance payment collected at session end', 1, 'cash', 'completed', '2026-05-08 19:23:38', 12, '2026-05-08 19:23:38'),
(153, 12, 0, 140.00, 140.00, NULL, NULL, 1, 'cash', 'completed', '2026-05-08 19:42:11', 12, '2026-05-08 19:42:11'),
(156, 12, 0, -40.00, NULL, NULL, 'Early end – refund for unused time: Early end – used 57m (₱100.00), refunding unused time (₱40.00)', NULL, '', 'completed', '2026-05-08 20:40:06', 12, '2026-05-08 20:40:06'),
(157, NULL, 27, 40.00, 40.00, NULL, 'Downpayment for reservation #44', 2, 'gcash', 'completed', '2026-05-08 23:20:19', 27, '2026-05-08 23:20:19'),
(158, 13, 0, 100.00, 110.00, NULL, NULL, 1, 'cash', 'completed', '2026-05-08 23:23:40', 12, '2026-05-08 23:23:40'),
(159, 13, 0, 40.00, 60.00, NULL, 'Balance payment collected at session end', 1, 'cash', 'completed', '2026-05-09 00:54:10', 12, '2026-05-09 00:54:10'),
(160, NULL, 27, 60.00, 60.00, NULL, 'Downpayment for reservation #46', 2, 'gcash', 'completed', '2026-05-09 10:59:39', 27, '2026-05-09 10:59:39'),
(161, NULL, 27, 47.00, 47.00, NULL, 'Downpayment for reservation #47', 2, 'gcash', 'completed', '2026-05-09 11:16:35', 27, '2026-05-09 11:16:35'),
(162, NULL, 27, 45.00, 45.00, NULL, 'Downpayment for reservation #48 (+₱20.00 controller rental)', 2, 'gcash', 'completed', '2026-05-09 11:26:32', 27, '2026-05-09 11:26:32'),
(163, NULL, 39, 56.00, 56.00, NULL, 'Downpayment for reservation #49 (+₱20.00 controller rental)', 2, 'gcash', 'completed', '2026-05-09 13:10:47', 39, '2026-05-09 13:10:47'),
(164, 14, 0, 0.00, 0.00, 22.50, 'Extension +15 min via staff (Extension #17)', 1, 'cash', 'completed', '2026-05-09 13:24:52', 12, '2026-05-09 13:24:52'),
(165, 17, 39, 56.00, 56.00, NULL, 'Downpayment transferred from reservation #49', 2, 'gcash', 'completed', '2026-05-09 13:31:17', 12, '2026-05-09 13:31:17'),
(166, NULL, 39, 54.00, 54.00, NULL, 'Downpayment for reservation #50 (+₱20.00 controller rental)', 2, 'gcash', 'completed', '2026-05-09 14:52:23', 39, '2026-05-09 14:52:23'),
(167, 18, 0, 100.00, 100.00, NULL, 'Balance payment collected at session end', 1, 'cash', 'completed', '2026-05-09 16:00:29', 12, '2026-05-09 16:00:29'),
(168, 16, 0, 230.00, 230.00, NULL, 'Balance payment collected at session end', 1, 'cash', 'completed', '2026-05-09 16:00:38', 12, '2026-05-09 16:00:38'),
(169, 14, 0, 220.00, 220.00, NULL, 'Balance payment collected at session end', 1, 'cash', 'completed', '2026-05-09 16:16:00', 12, '2026-05-09 16:16:00'),
(170, NULL, 33, 45.00, 45.00, NULL, 'Downpayment for reservation #51 (+₱20.00 controller rental)', 2, 'gcash', 'completed', '2026-05-09 16:26:30', 33, '2026-05-09 16:26:30'),
(171, 15, 0, 0.00, 0.00, 45.00, 'Extension +30 min via staff (Extension #18)', 1, 'cash', 'completed', '2026-05-09 16:52:20', 12, '2026-05-09 16:52:20'),
(172, NULL, 33, 56.00, 56.00, NULL, 'Downpayment for reservation #53 (+₱20.00 controller rental)', 2, 'gcash', 'completed', '2026-05-09 17:37:58', 33, '2026-05-09 17:37:58'),
(173, NULL, 40, 250.00, 250.00, NULL, 'Tournament registration fee for tiken it (Reg #9)', 2, 'gcash', 'completed', '2026-05-09 18:23:08', 40, '2026-05-09 18:23:08'),
(174, NULL, 39, 56.00, 56.00, NULL, 'Downpayment for reservation #54 (+₱20.00 controller rental)', 2, 'gcash', 'completed', '2026-05-09 19:12:49', 39, '2026-05-09 19:12:49'),
(175, 20, 0, 220.00, 220.00, NULL, 'Balance payment collected at session end', 1, 'cash', 'completed', '2026-05-09 20:42:58', 12, '2026-05-09 20:42:58'),
(178, 22, 39, 56.00, 56.00, NULL, 'Downpayment transferred from reservation #54', 2, 'gcash', 'completed', '2026-05-09 21:18:53', 12, '2026-05-09 21:18:53'),
(179, 17, 39, 220.00, 220.00, 264.00, 'Short payment - collected ₱220.00, short by ₱264.00', 1, 'cash', 'completed', '2026-05-09 21:36:23', 12, '2026-05-09 21:36:23'),
(180, 15, 0, 580.00, 580.00, NULL, 'Balance payment collected at session end', 1, 'cash', 'completed', '2026-05-09 21:37:11', 12, '2026-05-09 21:37:11'),
(181, NULL, 37, 41.50, 41.50, NULL, 'Downpayment for reservation #55 (+₱80.00 controller rental)', 2, 'gcash', 'completed', '2026-05-10 00:03:38', 37, '2026-05-10 00:03:38'),
(182, NULL, 37, 32.00, 32.00, NULL, 'Downpayment for reservation #56', 2, 'gcash', 'completed', '2026-05-10 00:29:20', 37, '2026-05-10 00:29:20'),
(183, 25, 0, 440.00, 400.00, 0.00, NULL, 1, 'cash', 'completed', '2026-05-10 10:42:42', 12, '2026-05-10 10:42:42'),
(184, 24, 0, 100.00, 100.00, NULL, 'Balance payment collected at session end', 1, 'cash', 'completed', '2026-05-10 10:44:16', 12, '2026-05-10 10:44:16'),
(185, 23, 0, 20.00, 20.00, NULL, 'Balance payment collected at session end', 1, 'cash', 'completed', '2026-05-10 10:44:20', 12, '2026-05-10 10:44:20'),
(186, 26, 0, 480.00, 400.00, 0.00, NULL, 1, 'cash', 'completed', '2026-05-10 10:45:22', 12, '2026-05-10 10:45:22'),
(187, 27, 0, 80.00, 80.00, NULL, 'Balance payment collected', 1, 'cash', 'completed', '2026-05-10 10:49:00', 12, '2026-05-10 10:49:00'),
(188, 27, 0, 20.00, 20.00, NULL, 'Balance payment collected at session end', 1, 'cash', 'completed', '2026-05-10 10:56:31', 12, '2026-05-10 10:56:31'),
(189, NULL, 33, 28.00, 28.00, NULL, 'Downpayment for reservation #57 (+₱0.00 controller rental)', 2, 'gcash', 'completed', '2026-05-10 11:15:06', 33, '2026-05-10 11:15:06'),
(190, 28, 0, 560.00, 400.00, 0.00, NULL, 1, 'cash', 'completed', '2026-05-10 13:56:30', 12, '2026-05-10 13:56:30'),
(191, 28, 12, 10.00, NULL, NULL, NULL, NULL, '', 'completed', '2026-05-10 14:14:31', 0, '2026-05-10 14:14:31'),
(192, 28, 12, 10.00, NULL, NULL, NULL, NULL, '', 'completed', '2026-05-10 14:14:37', 0, '2026-05-10 14:14:37'),
(193, 29, 26, 560.00, 400.00, 0.00, NULL, 1, 'cash', 'completed', '2026-05-10 14:16:50', 12, '2026-05-10 14:16:50'),
(194, 30, 0, 400.00, 400.00, 0.00, NULL, 1, 'cash', 'completed', '2026-05-10 14:26:50', 12, '2026-05-10 14:26:50'),
(195, NULL, 33, 34.00, 34.00, NULL, 'Downpayment for reservation #58', 2, 'gcash', 'completed', '2026-05-10 14:32:24', 33, '2026-05-10 14:32:24'),
(196, 30, 0, 40.00, NULL, NULL, NULL, 1, 'cash', 'completed', '2026-05-10 15:08:50', 12, '2026-05-10 15:08:50'),
(197, 31, 0, 400.00, 400.00, 0.00, NULL, 1, 'cash', 'completed', '2026-05-10 15:21:26', 12, '2026-05-10 15:21:26'),
(198, 33, 0, 400.00, 400.00, 0.00, NULL, 1, 'cash', 'completed', '2026-05-10 15:22:20', 12, '2026-05-10 15:22:20'),
(199, 36, 33, 34.00, 34.00, NULL, 'Downpayment transferred from reservation #59', 2, 'gcash', 'completed', '2026-05-10 15:32:50', 12, '2026-05-10 15:32:50'),
(200, 33, 0, 20.00, NULL, NULL, NULL, 1, 'cash', 'completed', '2026-05-10 15:37:46', 12, '2026-05-10 15:37:46'),
(201, 34, 0, 560.00, 400.00, 0.00, NULL, 1, 'cash', 'completed', '2026-05-10 15:38:22', 12, '2026-05-10 15:38:22'),
(202, NULL, 33, 250.00, 250.00, NULL, 'Tournament registration fee for tikin sibin (Reg #10)', 2, 'gcash', 'completed', '2026-05-10 15:42:23', 33, '2026-05-10 15:42:23'),
(203, NULL, 37, 250.00, 250.00, NULL, 'Tournament registration fee for tikin sibin (Reg #11)', 2, 'gcash', 'completed', '2026-05-10 16:20:21', 37, '2026-05-10 16:20:21'),
(204, 36, 33, 20.00, 20.00, 286.00, 'Short payment - collected â‚±20.00, short by â‚±286.00', 1, 'cash', 'completed', '2026-05-10 23:44:05', 12, '2026-05-10 23:44:05'),
(205, 35, 0, 360.00, 360.00, 220.00, 'Short payment - collected â‚±360.00, short by â‚±220.00', 1, 'cash', 'completed', '2026-05-10 23:44:17', 12, '2026-05-10 23:44:17'),
(206, NULL, 39, 34.00, 34.00, NULL, 'Downpayment for reservation #60', 2, 'gcash', 'completed', '2026-05-11 07:42:21', 39, '2026-05-11 07:42:21'),
(207, 38, 0, 280.00, 280.00, NULL, NULL, 1, 'cash', 'completed', '2026-05-11 08:28:30', 12, '2026-05-11 08:28:30'),
(208, 35, 0, 800.00, 800.00, NULL, 'Balance payment collected', 1, 'cash', 'completed', '2026-05-11 08:28:46', 12, '2026-05-11 08:28:46'),
(209, 36, 33, 866.00, 866.00, NULL, 'Balance payment collected', 1, 'cash', 'completed', '2026-05-11 08:28:49', 12, '2026-05-11 08:28:49'),
(210, 39, 0, 560.00, 400.00, 0.00, NULL, 1, 'cash', 'completed', '2026-05-11 08:33:03', 12, '2026-05-11 08:33:03'),
(211, 48, 39, 41.00, 41.00, NULL, 'Downpayment transferred from reservation #61', 2, 'gcash', 'completed', '2026-05-11 10:02:07', 12, '2026-05-11 10:02:07'),
(212, NULL, 39, 250.00, 250.00, NULL, 'Tournament registration fee for tikin sibin (Reg #12)', 2, 'gcash', 'completed', '2026-05-11 10:04:45', 39, '2026-05-11 10:04:45'),
(213, 40, 0, 80.00, 80.00, NULL, NULL, 1, 'cash', 'completed', '2026-05-11 11:03:40', 12, '2026-05-11 11:03:40'),
(214, 39, 0, 100.00, NULL, NULL, 'Controller Rental Fee — Session #39 (Ended: IDs [19,20] (2h 30m))', 1, 'cash', 'completed', '2026-05-11 11:03:51', 12, '2026-05-11 11:03:51'),
(215, NULL, 27, 25.00, 25.00, NULL, 'Downpayment for reservation #62 (+₱20.00 controller rental)', 2, 'gcash', 'completed', '2026-05-11 11:57:42', 27, '2026-05-11 11:57:42'),
(216, 37, 0, 280.00, 280.00, NULL, 'Balance payment collected', 1, 'cash', 'completed', '2026-05-11 12:05:38', 12, '2026-05-11 12:05:38'),
(217, 40, 0, 20.00, 20.00, 30.00, 'Short payment - collected ₱20.00, short by ₱30.00', 1, 'cash', 'completed', '2026-05-11 12:13:48', 12, '2026-05-11 12:13:48'),
(218, 41, 0, 100.00, 100.00, NULL, 'Balance payment collected at session end', 1, 'cash', 'completed', '2026-05-11 12:13:52', 12, '2026-05-11 12:13:52'),
(219, 37, 0, 20.00, 20.00, NULL, 'Balance payment collected at session end', 1, 'cash', 'completed', '2026-05-11 12:14:01', 12, '2026-05-11 12:14:01'),
(220, 40, 0, 30.00, 30.00, NULL, 'Balance payment collected', 1, 'cash', 'completed', '2026-05-11 12:14:06', 12, '2026-05-11 12:14:06'),
(221, 42, 0, 80.00, 80.00, NULL, 'Balance payment collected at session end', 1, 'cash', 'completed', '2026-05-11 12:16:53', 12, '2026-05-11 12:16:53'),
(222, 43, 0, 100.00, 100.00, NULL, 'Balance payment collected at session end', 1, 'cash', 'completed', '2026-05-11 12:28:02', 12, '2026-05-11 12:28:02'),
(223, 44, 0, 80.00, 80.00, NULL, 'Balance payment collected at session end', 1, 'cash', 'completed', '2026-05-11 13:19:34', 12, '2026-05-11 13:19:34'),
(224, 45, 0, 100.00, 100.00, NULL, 'Balance payment collected at session end', 1, 'cash', 'completed', '2026-05-11 13:31:30', 12, '2026-05-11 13:31:30'),
(225, 46, 0, 100.00, 100.00, NULL, 'Balance payment collected at session end', 1, 'cash', 'completed', '2026-05-11 13:41:00', 12, '2026-05-11 13:41:00'),
(226, NULL, 27, 22.50, 22.50, NULL, 'Downpayment for reservation #63', 2, 'gcash', 'completed', '2026-05-11 13:52:01', 27, '2026-05-11 13:52:01'),
(227, NULL, 27, 29.00, 29.00, NULL, 'Downpayment for reservation #64', 2, 'gcash', 'completed', '2026-05-11 14:00:33', 27, '2026-05-11 14:00:33'),
(228, NULL, 27, 38.00, 38.00, NULL, 'Downpayment for reservation #65', 2, 'gcash', 'completed', '2026-05-11 14:30:33', 27, '2026-05-11 14:30:33'),
(229, 48, 39, 179.00, 179.00, NULL, 'Balance payment collected at session end', 1, 'cash', 'completed', '2026-05-11 15:12:39', 12, '2026-05-11 15:12:39'),
(230, 47, 0, 200.00, 200.00, NULL, 'Balance payment collected at session end', 1, 'cash', 'completed', '2026-05-11 15:12:43', 12, '2026-05-11 15:12:43'),
(231, 51, 27, 3270.00, 3270.00, NULL, 'Balance payment collected at session end', 1, 'cash', 'completed', '2026-05-13 18:14:59', 12, '2026-05-13 18:14:59'),
(232, 50, 0, 3360.00, 3360.00, NULL, 'Balance payment collected at session end', 1, 'cash', 'completed', '2026-05-13 18:15:02', 12, '2026-05-13 18:15:02'),
(233, 52, 0, 120.00, 120.00, NULL, 'Balance payment collected at session end', 1, 'cash', 'completed', '2026-05-13 18:42:21', 12, '2026-05-13 18:42:21');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
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
(0, 'walkin@system.local', '', 'Walk-in Customer', '09000000000', 'walkin', 'active', 1, NULL, NULL, NULL, NULL, '2026-04-27 00:33:18', 0, NULL),
(12, 'ljlabianao@gmail.com', '$2y$10$nEuBy0VoWqtbRETOnAD99eurxRHdLaGpxROWA//NGpziKgIG1CmmS', 'lito', '09916310227', 'owner', 'active', 1, NULL, NULL, NULL, NULL, '2026-02-21 20:45:06', 0, NULL),
(17, 'llabianojr@kld.edu.ph', '$2y$10$N97t4MFGjElD3iM.nlpn3uZ/hy1zNC/Lfqi/YozlBB6BG1VWkL.Oe', 'Lito LARGUEZA LABIANO', '09916310227', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-04-12 19:54:25', 0, NULL),
(21, 'hello@gmail.com', '$2y$10$vjAu848ZbS1DpfexUIsM1.x31O/VNLXNwp/sw65NckgmZTHTKLzAO', 'Lito LARGUEZA LABIANO', '09916310227', 'shopkeeper', 'active', 1, NULL, NULL, NULL, NULL, '2026-04-17 11:59:29', 0, '2026-05-04 23:10:54'),
(22, 'admin@gmail.com', '$2y$10$kZCF21cxNKIxXE99XNGyF.nGfT94FZeDaGtbH4YTBOxrpumQLDvIK', 'Admin User', '09000000000', 'customer', 'active', 0, '9ad4e0d4b2004d75a763bd2e6e9af695cdb053e39c7cadd65db419783c75b54f', '2026-04-20 16:02:24', NULL, NULL, '2026-04-19 16:02:24', 0, NULL),
(23, 'customer@example.com', '$2y$10$8mmAlQ1UknZorTRMn6NVneRRA5JrVSOO8oMAuDEVll.pICMikYieO', 'Test Customer', '09000000000', 'customer', 'active', 0, '5337f25a858a79327c9001f982e6629f2f64be6579986d9da00c816159a6a75c', '2026-04-20 20:49:05', NULL, NULL, '2026-04-19 20:49:05', 0, NULL),
(24, 'lito@example.com', '$2y$10$b1z6jAajQr5NjOyHWkU5kOJ7kx/BdeM6HbUg/qLWdElsilx20bLta', 'Lito Test', '09000000000', 'customer', 'active', 0, '10512cc414ac02ff1b961698a17863ad87e3e55d976dc1e14d92a2af161ee3bd', '2026-04-20 20:52:01', NULL, NULL, '2026-04-19 20:52:01', 0, NULL),
(27, 'helios@gmail.com', '$2y$10$.T/FeCELcCi8CIuswNHl7.tbPi62hXm.AfCJUFt/UU5TNJuFlBGVS', 'helios', 'helios@gmail.com', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-04-27 23:29:14', 2, NULL),
(28, 'testcustomer@example.com', '$2y$10$kGbRHj87ratNFmTwi2pmTOqMaV2KV0fvB2H5sMYMsEzMfFVM4Uku2', 'Test Customer', '09123456789', 'customer', 'active', 0, '29333d3de426116e2f4f7d304d0b9f4b4b3b76feb6ee30a877d3d5457179f6e1', '2026-04-28 23:52:37', NULL, NULL, '2026-04-27 23:52:37', 0, NULL),
(29, 'test@example.com', '$2y$10$mLQD41zmi3vIoJ8sR8/Ntu6uZojYGjvzKbNb3Y.evmmQ3SyMB7evC', 'Test User', '09171234567', 'customer', 'active', 1, '0ae0598c1a671992ec26dfae71eabe5e2f9269bb1da1ae9f85c78695949d6184', '2026-04-30 20:29:21', NULL, NULL, '2026-04-29 20:29:21', 0, NULL),
(30, '09171234567test@gmail.com', '$2y$10$jyNy.ykur0jghOW.APBCwOpx.Y5fTLFYDdfTbek92i6AffjjIlcBq', 'test@gmail.comTest User', 'Password123!09171234', 'customer', 'active', 0, '6a296a2ad91537491af9a4309082ea6e25e0a818b60dd86e65ccc5e842ccd700', '2026-05-01 00:13:31', NULL, NULL, '2026-04-30 00:13:32', 0, NULL),
(31, 'mariejoy.12.mji@gmail.com', '$2y$10$VDFqBSZiRt9I1ZLIRaVBdOSQ0Red0aogx.VqXx1EMrGP.905x0bX2', 'MJ IGNACIO', '09000000000', 'customer', 'active', 0, 'd0e91ebfe1587c05cd41f92225bfa010276bb3e2c5c3ae0e7fefabd03a28c942', '2026-05-01 08:01:11', NULL, NULL, '2026-04-30 08:01:11', 0, NULL),
(32, 'harvieloresco2004@gmail.com', '$2y$10$5ra6ycYpFWsty4s.ikJESuw688uGiHUCKkXPlA8z44ZS9CzZnGU86', 'Harvie Loresco', '09944084214', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-05-05 14:34:15', 0, '2026-05-14 07:38:38'),
(33, 'sibeloresco@gmail.com', '$2y$10$vlRchYPFio5tarpM0bhj.evLqxB.xckjHN8siFlmhrITmY5fbjQ/G', 'Sibe Loresco', '09944084214', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-05-05 20:26:57', 0, NULL),
(37, 'zerotwo02darlingninelota@gmail.com', '$2y$10$Q72iGgv0rWvD6I9OaSk6Iuq7BxVJY5Tmw2HuYCH.kGlLT6tSVqNom', 'Gelo', '09123455666', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-05-07 09:01:18', 0, NULL),
(38, 'unique_tester_99@example.com', '$2y$10$KlQu2AzHZNth7OfEOAY5T.iRsiw9FsKR9MAkplksBodg/vMp84vny', 'Test User', '09000000000', 'customer', 'active', 0, 'c2d332cbbbde9a33cc7e290f45c9ce3acaa51ce1bb1d9e9836f5c2504f018794', '2026-05-08 13:02:28', NULL, NULL, '2026-05-07 13:02:28', 0, NULL),
(39, 'harviebernesto1215@gmail.com', '$2y$10$begj7OLMR61VTxH/oTUNCu.IY15lyRGFEHFbNB0A4SA5O2vAgtU86', 'Sibe', '09124173609', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-05-09 12:58:58', 0, NULL),
(40, 'bernadittealmoguerra@gmail.com', '$2y$10$9gG0Q6/YTYKEt5mPRtXxyOR/GkdcV/a0UzfDcQcBHRMc9vutw/n9C', 'berna', '09000000000', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-05-09 17:59:51', 0, NULL),
(41, 'rommelorejana77@gmail.com', '$2y$10$0Wv7mZOiWpTYOOVlj7pjBuOcvZVbQwOpILgv3PYOA6S85zMVpX2bW', 'Rommel', '09000000000', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-05-09 18:45:35', 0, NULL),
(42, 'goodspotgaminghub@gmail.com', '$2y$10$CznkFvPfEpiqEkrfaJW35.LlQ2ea0Uw1CxS4KU5hi.wr87Jlv0OtO', 'GSPOT', '09123455666', 'shopkeeper', 'active', 1, NULL, NULL, NULL, NULL, '2026-05-09 23:14:39', 0, NULL),
(43, 'infocribquest@gmail.com', '$2y$10$roAsFciz6hJL1hbWDSQ.l.EvvB22s0qWXokZYA09iP1AWhPqA8XgS', 'Cribquest2025', '09944084214', 'customer', 'active', 0, '5A3DA73D', '2026-05-11 23:39:31', NULL, NULL, '2026-05-10 23:39:31', 0, NULL),
(46, 'hbernesto@kld.edu.ph', '$2y$10$9WrvUIUU8uRewfHgf/j6OO.Y5qjdVU/P4gOh5eMjN4HE.SxgyS9jS', 'Harvie Sevi', '09091239872', 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-05-11 10:17:47', 0, NULL),
(48, 'kianpablo1996@gmail.com', '$2y$10$FBPS6QSsbhfbjlqG6t324.qsGDRXKTR8rUQtAMtNB3lb/8.yQ8OUK', 'Kian Pablo', '09944084214', 'shopkeeper', 'active', 1, NULL, NULL, NULL, NULL, '2026-05-11 10:27:30', 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `fk_activity_logs_user` (`user_id`);

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
  ADD PRIMARY KEY (`console_type_id`),
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
  ADD PRIMARY KEY (`controller_type_id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

--
-- Indexes for table `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`game_id`),
  ADD UNIQUE KEY `uq_game_name` (`game_name`),
  ADD KEY `fk_games_console_type` (`console_type_id`);

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
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_notif_user` (`user_id`),
  ADD KEY `idx_notif_read` (`is_read`),
  ADD KEY `idx_notif_type` (`type`),
  ADD KEY `idx_notif_date` (`created_at`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`method_id`),
  ADD UNIQUE KEY `uq_code` (`code`);

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
  ADD KEY `idx_res_console_type` (`console_type_id`),
  ADD KEY `controller_id_2` (`controller_id_2`);

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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=324;

--
-- AUTO_INCREMENT for table `additional_requests`
--
ALTER TABLE `additional_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `blocked_dates`
--
ALTER TABLE `blocked_dates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `consoles`
--
ALTER TABLE `consoles`
  MODIFY `console_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3231;

--
-- AUTO_INCREMENT for table `console_types`
--
ALTER TABLE `console_types`
  MODIFY `console_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=153;

--
-- AUTO_INCREMENT for table `controllers`
--
ALTER TABLE `controllers`
  MODIFY `controller_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `controller_types`
--
ALTER TABLE `controller_types`
  MODIFY `controller_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `game_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `gaming_sessions`
--
ALTER TABLE `gaming_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `method_id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `reservation_cancellations`
--
ALTER TABLE `reservation_cancellations`
  MODIFY `cancel_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservation_reschedules`
--
ALTER TABLE `reservation_reschedules`
  MODIFY `reschedule_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `session_extensions`
--
ALTER TABLE `session_extensions`
  MODIFY `extension_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `shop_hours_overrides`
--
ALTER TABLE `shop_hours_overrides`
  MODIFY `override_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=506;

--
-- AUTO_INCREMENT for table `tournaments`
--
ALTER TABLE `tournaments`
  MODIFY `tournament_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tournament_participants`
--
ALTER TABLE `tournament_participants`
  MODIFY `participant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=234;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `additional_requests`
--
ALTER TABLE `additional_requests`
  ADD CONSTRAINT `fk_addreq_session` FOREIGN KEY (`session_id`) REFERENCES `gaming_sessions` (`session_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `consoles`
--
ALTER TABLE `consoles`
  ADD CONSTRAINT `fk_consoles_console_type` FOREIGN KEY (`console_type_id`) REFERENCES `console_types` (`console_type_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `controllers`
--
ALTER TABLE `controllers`
  ADD CONSTRAINT `fk_controllers_console` FOREIGN KEY (`console_type_id`) REFERENCES `console_types` (`console_type_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_controllers_type` FOREIGN KEY (`controller_type_id`) REFERENCES `controller_types` (`controller_type_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `games`
--
ALTER TABLE `games`
  ADD CONSTRAINT `fk_games_console_type` FOREIGN KEY (`console_type_id`) REFERENCES `console_types` (`console_type_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `gaming_sessions`
--
ALTER TABLE `gaming_sessions`
  ADD CONSTRAINT `fk_sessions_console` FOREIGN KEY (`console_id`) REFERENCES `consoles` (`console_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sessions_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
  ADD CONSTRAINT `fk_reservations_console_type` FOREIGN KEY (`console_type_id`) REFERENCES `console_types` (`console_type_id`) ON DELETE SET NULL ON UPDATE CASCADE;

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
  ADD CONSTRAINT `fk_rr_new_console_type` FOREIGN KEY (`new_console_type_id`) REFERENCES `console_types` (`console_type_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rr_old_console_type` FOREIGN KEY (`old_console_type_id`) REFERENCES `console_types` (`console_type_id`) ON DELETE SET NULL ON UPDATE CASCADE;

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
