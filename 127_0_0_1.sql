-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 28, 2026 at 07:35 PM
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
-- Database: `fashion_platform`
--
CREATE DATABASE IF NOT EXISTS `fashion_platform` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `fashion_platform`;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `image`, `is_active`, `created_at`) VALUES
(1, 'Sneakers', 'Custom sneaker designs', NULL, 1, '2026-01-28 12:10:56'),
(2, 'Clothing', 'Custom clothing items', NULL, 1, '2026-01-28 12:10:56'),
(3, 'Bags', 'Custom bag designs', NULL, 1, '2026-01-28 12:10:56');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_id` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `artist_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `customization_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`customization_details`)),
  `reference_images` text DEFAULT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','assigned','in_progress','review','approved','completed','cancelled') DEFAULT 'pending',
  `deadline` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_id`, `customer_id`, `artist_id`, `product_id`, `customization_details`, `reference_images`, `total_price`, `status`, `deadline`, `created_at`, `updated_at`) VALUES
(7, 'ORD202601289254', 2, NULL, 1, '{\"primary_color\":\"#00ffee\",\"secondary_color\":\"#ff0000\",\"custom_text\":\"\",\"size\":\"XS\",\"premium\":\"1\",\"instructions\":\"\"}', '[\"1769615391_867286-full_product.webp\"]', 25.00, 'cancelled', '2026-02-11', '2026-01-28 15:49:51', '2026-01-28 15:52:58'),
(11, 'ORD202601287792', 2, 5, 1, '{\"primary_color\":\"#fff700\",\"secondary_color\":\"#003d31\",\"custom_text\":\"\",\"size\":\"M\",\"premium\":\"1\",\"instructions\":\"\"}', '[\"1769621743_867286-full_product.webp\"]', 175.00, 'completed', '2026-02-11', '2026-01-28 17:35:43', '2026-01-28 17:59:06');

-- --------------------------------------------------------

--
-- Table structure for table `order_progress`
--

CREATE TABLE `order_progress` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `message` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `featured` tinyint(1) DEFAULT 0,
  `customization_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`customization_options`)),
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `contact_info` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `base_price`, `image`, `featured`, `customization_options`, `is_active`, `created_at`, `contact_info`) VALUES
(1, 1, 'Classic Sneakers', 'Customizable classic sneaker design', 150.00, '697a20231e8f6.jpg', 1, NULL, 1, '2026-01-28 12:10:56', NULL),
(2, 1, 'High-Top Sneakers', 'Custom high-top sneaker design', 180.00, '697a250ab6010.jpg', 1, NULL, 1, '2026-01-28 12:10:56', NULL),
(3, 2, 'Custom T-Shirt', 'Personalized t-shirt design', 45.99, '697a4c4e80f15.jpg', 1, NULL, 1, '2026-01-28 12:10:56', 'T-Shirts Polo Shirt modern Design'),
(4, 3, 'Tote Bag', 'Custom tote bag design', 65.00, '697a251eef1a2.jpg', 0, NULL, 1, '2026-01-28 12:10:56', NULL),
(5, NULL, 'Hoodie', NULL, 200.00, '697a238f9e395.jpg', 0, NULL, 1, '2026-01-28 14:56:01', 'A Preowned H & M Woman size medium black and white Sweatshirt Hoodie with flower');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'commission_rate', '20', '2026-01-28 12:10:56'),
(2, 'default_deadline_days', '14', '2026-01-28 12:10:56'),
(3, 'max_file_size', '5242880', '2026-01-28 12:10:56'),
(4, 'allowed_file_types', 'jpg,jpeg,png,gif', '2026-01-28 12:10:56'),
(7, 'max_file_size_mb', '5', '2026-01-28 13:53:24'),
(9, 'platform_name', 'Fashion Platform', '2026-01-28 13:53:24'),
(10, 'contact_email', 'admin@fashionplatform.com', '2026-01-28 13:53:24'),
(11, 'support_phone', '', '2026-01-28 13:53:24'),
(12, 'auto_assign_orders', '0', '2026-01-28 13:53:24'),
(13, 'cancellation_period_hours', '24', '2026-01-28 13:53:24'),
(15, 'artist_payout_rate', '80', '2026-01-28 15:28:38'),
(16, 'minimum_order_value', '25', '2026-01-28 15:28:38'),
(20, 'platform_status', 'active', '2026-01-28 15:28:38'),
(21, 'allow_registration', '1', '2026-01-28 15:28:38'),
(22, 'allow_orders', '1', '2026-01-28 15:28:38'),
(23, 'assignment_method', 'manual', '2026-01-28 15:28:38'),
(24, 'max_orders_per_artist', '5', '2026-01-28 15:28:38'),
(25, 'cancellation_window_hours', '24', '2026-01-28 15:28:38');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','artist','customer') NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_approved` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`, `address`, `profile_image`, `is_active`, `created_at`, `updated_at`, `is_approved`) VALUES
(2, 'Alice', 'alice@gmail.com', '$2y$10$3ITJeqa4NOFBCmznLBmqDOK1fZHu0iVqSpkCldARvEe9PsF.Bh/VO', 'customer', NULL, NULL, NULL, 1, '2026-01-28 12:14:10', '2026-01-28 14:59:11', 1),
(3, 'Tom Smith', 'tom@gmail.com', '$2y$10$kMO8aY4N/uFQ8XQPWKqmNuOLCQ9R.qXGnQRA1BWYT8f8J.EURLsNe', 'artist', NULL, NULL, NULL, 1, '2026-01-28 12:33:59', '2026-01-28 15:54:11', 1),
(4, 'xann', 'xann@gmail.com', '$2y$10$CQ3ttXzWBHH/ahTWBfVTiuK8FMXqxXtmoZunRMjp37c3BuBhTUHXu', 'admin', NULL, NULL, NULL, 1, '2026-01-28 12:44:37', '2026-01-28 12:47:44', 0),
(5, 'Jun Snow', 'jun@gmail.com', '$2y$10$xdmfyO6/dXisV1NKI5UwZOStW873beIYzerPKbEYK/eGab0ZY05Da', 'artist', NULL, NULL, NULL, 1, '2026-01-28 12:46:41', '2026-01-28 14:58:42', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `artist_id` (`artist_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `order_progress`
--
ALTER TABLE `order_progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `order_progress`
--
ALTER TABLE `order_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`artist_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `order_progress`
--
ALTER TABLE `order_progress`
  ADD CONSTRAINT `order_progress_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_progress_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
