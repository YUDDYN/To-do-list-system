-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 03, 2026 at 04:08 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `todo_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id_log` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `log_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_activities`
--

CREATE TABLE `daily_activities` (
  `id_daily_activity` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','done') DEFAULT 'pending',
  `priority` enum('low','medium','high') DEFAULT 'low',
  `activity_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `label` varchar(50) DEFAULT 'lainnya',
  `deadline` date DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_activities`
--

INSERT INTO `daily_activities` (`id_daily_activity`, `user_id`, `title`, `description`, `status`, `priority`, `activity_date`, `created_at`, `label`, `deadline`, `project_id`) VALUES
(1, 1, 'tidur', 'cepetan', 'pending', 'high', '2026-03-20', '2026-03-12 21:19:31', 'lainnya', '0000-00-00', NULL),
(5, 2, 'tes1', '1', 'done', 'high', '2026-04-01', '2026-04-01 11:33:09', 'kuliah', NULL, NULL),
(6, 2, 'tes', '', 'done', 'low', '2026-04-01', '2026-04-01 11:36:03', 'personal', NULL, NULL),
(7, 2, 'tidur', 'besok nugas', 'done', 'high', '2026-04-30', '2026-04-28 20:40:37', 'sekolah', '2026-05-21', NULL),
(8, 2, 'a', 'mau tau aja luh', 'pending', 'low', '2026-04-28', '2026-04-28 20:41:08', 'kuliah', NULL, NULL),
(9, 2, 'ngockok', 'ok', 'done', 'low', '2026-04-29', '2026-04-29 08:11:05', 'personal', NULL, NULL),
(10, 2, 'ugik', 'uis', 'done', 'low', '2026-04-29', '2026-04-29 08:11:25', 'personal', NULL, NULL),
(11, 2, 'kicau', '', 'pending', 'high', '2026-04-29', '2026-04-29 20:02:55', 'tidor', NULL, 2),
(12, 2, 'tidur', '', 'pending', 'medium', '2026-04-30', '2026-04-30 07:08:34', 'General', NULL, 3),
(13, 4, 'ada', '', 'pending', 'medium', '2026-05-01', '2026-05-01 22:21:21', 'General', NULL, 4),
(14, 2, 'testing1', '1', 'pending', 'medium', '2026-05-03', '2026-05-03 21:04:39', 'personal', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id_project` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nama_project` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `warna` varchar(20) DEFAULT '#4f8ef7',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id_project`, `user_id`, `nama_project`, `deskripsi`, `warna`, `created_at`) VALUES
(1, 3, 'jdoihfoihs', 'gfisg', '#733036', '2026-04-29 11:46:16'),
(2, 2, 'tidur mania', 'Workspace: Tim ah', '#31da25', '2026-04-29 20:01:35'),
(3, 2, 'azril suka sawit', '', '#000000', '2026-04-30 07:08:01'),
(4, 4, 'none', '', '#235323', '2026-05-01 22:07:06');

-- --------------------------------------------------------

--
-- Table structure for table `project_sections`
--

CREATE TABLE `project_sections` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `section_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_sections`
--

INSERT INTO `project_sections` (`id`, `project_id`, `section_name`, `created_at`) VALUES
(1, 3, 'penting', '2026-05-03 13:23:22');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'yanto yokab', '$2y$10$reY5KJY41GGijp8QGM.FMO29qoxUvAJsWHmPHRGlu9KPlpwWHxmmG', 'user', '2026-03-11 10:56:11'),
(2, 'ada', '$2y$10$1VFW2Ge35dSvDUUiAxJkF.OvkXv9UxebI0ZFpceGfFdg9Wr5liGri', 'user', '2026-03-29 21:04:00'),
(3, 'uis', '$2y$10$tOFdO2kMU6Q4ZrqDpfi.D.xPZM5Hz4fy1UGIC6TLMsvkjDvLwFEVK', 'user', '2026-04-29 08:12:34'),
(4, 'Test', '$2y$10$pjSR3em9R5WGOlveXaT9jep9Ak/5h1oW4PdMaurk2acvx7ELqXM36', 'user', '2026-05-01 21:44:20');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id_log`);

--
-- Indexes for table `daily_activities`
--
ALTER TABLE `daily_activities`
  ADD PRIMARY KEY (`id_daily_activity`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id_project`);

--
-- Indexes for table `project_sections`
--
ALTER TABLE `project_sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `daily_activities`
--
ALTER TABLE `daily_activities`
  MODIFY `id_daily_activity` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id_project` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `project_sections`
--
ALTER TABLE `project_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `project_sections`
--
ALTER TABLE `project_sections`
  ADD CONSTRAINT `project_sections_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id_project`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
