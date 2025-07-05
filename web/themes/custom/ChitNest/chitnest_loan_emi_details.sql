-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 05, 2025 at 04:54 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.1.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `chitnest`
--

-- --------------------------------------------------------

--
-- Table structure for table `chitnest_loan_emi_details`
--

CREATE TABLE `chitnest_loan_emi_details` (
  `table_id` int(10) UNSIGNED NOT NULL COMMENT 'Primary Key: Unique Loan ID.',
  `loan_id` varchar(100) NOT NULL COMMENT 'Loan Id.',
  `customer_name` varchar(255) NOT NULL COMMENT 'Name of the customer.',
  `start_date` datetime NOT NULL COMMENT 'Start date of the loan.',
  `end_date` datetime NOT NULL COMMENT 'End date of the loan.',
  `emi_number` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'EMI number or count.',
  `status` varchar(100) NOT NULL DEFAULT 'pending' COMMENT 'Status of the EMI.',
  `penalty` float DEFAULT NULL COMMENT 'Penalty amount, if any.',
  `agent_collected` varchar(255) DEFAULT NULL COMMENT 'Name of the collecting agent.',
  `collected_date` datetime DEFAULT NULL COMMENT 'Date of collection.',
  `collected_mode` varchar(100) DEFAULT NULL COMMENT 'Mode of collection (Cash, Bank, etc.).'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores Loan EMI details.';

--
-- Dumping data for table `chitnest_loan_emi_details`
--

INSERT INTO `chitnest_loan_emi_details` (`table_id`, `loan_id`, `customer_name`, `start_date`, `end_date`, `emi_number`, `status`, `penalty`, `agent_collected`, `collected_date`, `collected_mode`) VALUES
(1, '12345', 'Customer 1', '2025-06-20 00:00:00', '2025-06-20 00:00:00', 1, 'paid', 0, 'Agent 2', '2025-07-05 00:00:00', 'Cash-600'),
(2, '12345', 'Customer 1', '2025-06-20 00:00:00', '2025-06-21 00:00:00', 2, 'paid', 100, 'Agent 1', '2025-07-25 00:00:00', 'hand'),
(3, '12345', 'Customer 1', '2025-06-20 00:00:00', '2025-06-22 00:00:00', 3, 'pending', 0, 'Agent 1', NULL, ''),
(4, '12345', 'Customer 1', '2025-06-20 00:00:00', '2025-06-23 00:00:00', 4, 'pending', 0, 'Agent 1', NULL, ''),
(5, '12345', 'Customer 1', '2025-06-20 00:00:00', '2025-06-24 00:00:00', 5, 'pending', 0, 'Agent 1', NULL, ''),
(6, '12345', 'Customer 1', '2025-06-20 00:00:00', '2025-06-25 00:00:00', 6, 'pending', 0, 'Agent 1', NULL, ''),
(7, '12345', 'Customer 1', '2025-06-20 00:00:00', '2025-06-26 00:00:00', 7, 'pending', 0, 'Agent 1', NULL, ''),
(8, '12345', 'Customer 1', '2025-06-20 00:00:00', '2025-06-27 00:00:00', 8, 'paid', 800, 'Agent 1', '2025-07-18 00:00:00', '8 paid'),
(9, '12345', 'Customer 1', '2025-06-20 00:00:00', '2025-06-28 00:00:00', 9, 'pending', 0, 'Agent 1', NULL, ''),
(10, '12345', 'Customer 1', '2025-06-20 00:00:00', '2025-06-29 00:00:00', 10, 'pending', 0, 'Agent 2', NULL, ''),
(11, '117', 'customer 7', '2025-07-04 00:00:00', '2025-07-04 00:00:00', 1, 'paid', 0, '', '2025-07-05 00:00:00', 'Cash-600'),
(12, '117', 'customer 7', '2025-07-04 00:00:00', '2025-07-05 00:00:00', 2, 'paid', 100, '', '2025-07-25 00:00:00', 'hand'),
(13, '117', 'customer 7', '2025-07-04 00:00:00', '2025-07-06 00:00:00', 3, 'paid', 233, '', NULL, '73'),
(14, '117', 'customer 7', '2025-07-04 00:00:00', '2025-07-07 00:00:00', 4, 'pending', 0, '', NULL, ''),
(15, '117', 'customer 7', '2025-07-04 00:00:00', '2025-07-08 00:00:00', 5, 'pending', 0, '', NULL, ''),
(16, '117', 'customer 7', '2025-07-04 00:00:00', '2025-07-09 00:00:00', 6, 'pending', 0, '', NULL, ''),
(17, '117', 'customer 7', '2025-07-04 00:00:00', '2025-07-10 00:00:00', 7, 'pending', 0, '', NULL, ''),
(18, '117', 'customer 7', '2025-07-04 00:00:00', '2025-07-11 00:00:00', 8, 'pending', 0, '', NULL, ''),
(19, '117', 'customer 7', '2025-07-04 00:00:00', '2025-07-12 00:00:00', 9, 'pending', 0, '', NULL, ''),
(20, '117', 'customer 7', '2025-07-04 00:00:00', '2025-07-13 00:00:00', 10, 'pending', 0, '', NULL, '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chitnest_loan_emi_details`
--
ALTER TABLE `chitnest_loan_emi_details`
  ADD PRIMARY KEY (`table_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chitnest_loan_emi_details`
--
ALTER TABLE `chitnest_loan_emi_details`
  MODIFY `table_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key: Unique Loan ID.', AUTO_INCREMENT=21;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
