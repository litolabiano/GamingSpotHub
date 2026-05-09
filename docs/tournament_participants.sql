-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 09, 2026 at 10:23 AM
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

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tournament_participants`
--
ALTER TABLE `tournament_participants`
  MODIFY `participant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

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
