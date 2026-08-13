-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 13, 2025 at 08:50 PM
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
-- Database: `php_preutix`
--

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `details` text NOT NULL,
  `image` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 25.00,
  `category` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `location` varchar(255) NOT NULL DEFAULT 'President University',
  `is_premium` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `details`, `image`, `created_at`, `description`, `price`, `category`, `created_by`, `location`, `is_premium`) VALUES
(1, 'Summer Music Festival', 'Join us for an unforgettable music experience!', 'assets/Music.heic', '2025-05-07 16:57:48', 'A vibrant music festival featuring top artists.', 50.00, 'concerts', 1, 'President University', 0),
(2, 'Championship Finals', 'Watch the best teams compete for the title!', 'assets/Championship.webp', '2025-05-07 16:57:48', 'Annual sports championship with exciting matches.', 30.00, 'sports', 1, 'President University', 0),
(3, 'Tech Conference 2025', 'Learn from industry leaders in technology.', 'assets/Conference.webp', '2025-05-07 16:57:48', 'Tech conference with industry leaders and workshops.', 35.00, 'conferences', 1, 'President University', 0),
(4, 'Theater Play 2025', 'Date: 2025-06-15, Time: 18:00', 'assets/muscom.heic', '2025-05-10 14:32:44', 'A captivating theater performance.', 20.00, 'theater', 1, 'President University', 0),
(7, 'Communication Festival', '12 February 2026', 'assets/uploads/1747024485_Screenshot 2025-01-14 140604.png', '2025-05-12 04:34:45', 'Full 5 day for comeptition in sport', 1.00, 'Sports', 1, 'President University', 0),
(8, 'Theater', '10.00-12.00', 'assets/uploads/1747161898_theatre.heic', '2025-05-13 18:44:58', 'PUCC Theater 2', 0.01, 'Sports', 8, 'President University', 0);

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_subscribers`
--

CREATE TABLE `newsletter_subscribers` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `newsletter_subscribers`
--

INSERT INTO `newsletter_subscribers` (`id`, `email`, `subscribed_at`) VALUES
(1, 'yoseelvis1304@gmail.com', '2025-05-12 02:37:16');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `testimonials`
--

CREATE TABLE `testimonials` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `text` text NOT NULL,
  `avatar` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `testimonials`
--

INSERT INTO `testimonials` (`id`, `name`, `position`, `text`, `avatar`, `created_at`) VALUES
(1, 'Sarah Johnson', 'Student', 'Preutix is everything I\'ve been looking for in an event platform. The interface is intuitive, and I was able to find and book tickets for my favorite band in minutes!', 'assets/student1.heic', '2025-05-07 16:57:48'),
(2, 'Frank Ocean', 'Event Organizer', 'As an event organizer, Preutix has transformed how we manage our events. The analytics and customer management tools are excellent!', 'assets/student2.heic', '2025-05-07 16:57:48');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `invoice_number` varchar(20) NOT NULL,
  `purchase_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `user_id`, `event_id`, `quantity`, `total_price`, `invoice_number`, `purchase_date`) VALUES
(1, 3, 1, 2, 50.00, 'INV-2025-0001', '2025-05-09 02:24:44'),
(7, 1, 1, 1, 50.00, 'INV-2025-0002', '2025-05-10 22:57:28'),
(8, 1, 1, 1, 50.00, 'INV-2025-0003', '2025-05-12 03:15:26'),
(9, 1, 3, 1, 35.00, 'INV-2025-0004', '2025-05-12 03:22:19'),
(10, 1, 2, 1, 30.00, 'INV-2025-0005', '2025-05-12 05:35:57'),
(11, 1, 2, 1, 30.00, 'INV-2025-0006', '2025-05-12 05:46:46'),
(12, 1, 2, 4, 120.00, 'INV-2025-0007', '2025-05-12 13:34:35'),
(16, 8, 8, 1, 0.01, 'INV-2025-0008', '2025-05-14 01:47:34'),
(17, 8, 3, 1, 35.00, 'INV-2025-0009', '2025-05-14 01:47:52');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `premium` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `created_at`, `reset_token`, `reset_expires`, `premium`) VALUES
(1, 'q', 'fernolie65@gmail.com', '$2y$10$SEbkSoLF.IJIXfYvxhwPFOzbmaROzKSjfpC9vBQYhoKKWyhbPS0gq', '2025-05-07 17:58:20', '2d072405f630efde0a9090da83967c1695d9d202f57189c9230fa83e64cc5e89', '2025-05-08 22:27:03', 0),
(3, 'ferno', '1@gmail.com', '$2y$10$Udmd7lZ.ZPiHOW3T1PpxFeE.7AolkIXfjpjFEbdnAZP9Mupg8mSv2', '2025-05-08 15:49:20', NULL, NULL, 0),
(4, 'testuser', 'test@example.com', '$2y$10$...', '2025-05-08 20:14:22', NULL, NULL, 0),
(5, 'oce', 'yose@gmail.com', '$2y$10$d5JwR43gaeXVsRYUDw1XDOKDS3ZmjIEtiz8lZz7w0wVAojckxyj0y', '2025-05-09 02:42:01', NULL, NULL, 0),
(7, 'testuser2', 'test2@example.com', 'hashed_password', '2025-05-12 05:07:57', NULL, NULL, 0),
(8, 'dboy', 'backintheday4u@gmail.com', '$2y$10$eHBernOkL.iCEFrCG7DSx.NKf29HSLFZNhereXhrM.cKmBKShro2m', '2025-05-13 18:46:59', NULL, NULL, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`email`),
  ADD KEY `token` (`token`);

--
-- Indexes for table `testimonials`
--
ALTER TABLE `testimonials`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `email_2` (`email`),
  ADD UNIQUE KEY `username_2` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `testimonials`
--
ALTER TABLE `testimonials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
