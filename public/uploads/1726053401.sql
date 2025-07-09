-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 29, 2024 at 02:25 PM
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
-- Database: `terminal`
--

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `amount` double NOT NULL,
  `payment_link` varchar(255) NOT NULL,
  `is_paid` enum('paid','unpaid') NOT NULL DEFAULT 'unpaid',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `order_id`, `amount`, `payment_link`, `is_paid`, `created_at`, `updated_at`) VALUES
(6, 605, 150, 'http://127.0.0.1:8000/payment_terminal/eyJpdiI6InY0Vk15WDVuTmdUK1o1SG1XdFkrVXc9PSIsInZhbHVlIjoicHJhUUQ0T0dyNGtHbTZzOUpDWVB2QT09IiwibWFjIjoiMjc1M2Q2YTI0ZjY5MGUwODk1MzJjMTY1NjlmYjQwNGE5MzBlNGQxZjVkYjQ2YmNmNDdiM2E3NGI3NTA3NTZiYiIsInRhZyI6IiJ9', 'unpaid', '2024-08-29 03:24:00', '2024-08-29 03:24:00'),
(7, 606, 1500, 'http://127.0.0.1:8000/payment_terminal/eyJpdiI6Ikl2Um9zbGkxYjY3N3JKWnVyS0k3QUE9PSIsInZhbHVlIjoiOGZIckM1OGJHUjRUemMwUmxMUW5Hdz09IiwibWFjIjoiYTAyMmZiNDlkOTk4ODk3MmEwOTA1ZDUwMjRiNzY5YWE5ZDkyYTk5Njk1Njg1Yzk3NDU4Zjc4NmJmZTEwMTBmZSIsInRhZyI6IiJ9', 'unpaid', '2024-08-29 03:25:18', '2024-08-29 03:25:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
