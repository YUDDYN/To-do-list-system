-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 21, 2026 at 05:43 PM
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
  `project_id` int(11) DEFAULT NULL,
  `file_attachment` varchar(255) DEFAULT NULL,
  `file_original_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_activities`
--

INSERT INTO `daily_activities` (`id_daily_activity`, `user_id`, `title`, `description`, `status`, `priority`, `activity_date`, `created_at`, `label`, `deadline`, `project_id`, `file_attachment`, `file_original_name`) VALUES
(54, 2, 'tugas', '', 'done', 'medium', '2026-05-20', '2026-05-20 13:16:38', 'personal', '2026-05-23', NULL, NULL, NULL),
(55, 2, 'test', '', 'done', 'medium', '2026-05-20', '2026-05-20 13:16:53', 'personal', '2026-05-22', NULL, NULL, NULL),
(56, 2, 'test', '', 'done', 'medium', '2026-05-20', '2026-05-20 13:17:03', 'personal', '2026-05-23', NULL, NULL, NULL),
(57, 2, 't', '', 'done', 'medium', '2026-05-17', '2026-05-20 13:18:37', 'personal', NULL, NULL, NULL, NULL),
(58, 2, 'p', '', 'done', 'medium', '2026-05-20', '2026-05-20 13:18:51', 'personal', '2026-05-17', NULL, NULL, NULL),
(59, 2, 'laporan webbb', 'cepat', 'pending', 'medium', '2026-05-20', '2026-05-20 20:04:05', 'personal', '2026-05-23', NULL, 'task_1779282245_PAKET_1.docx', 'PAKET 1.docx'),
(60, 2, 'pepey', '', 'pending', 'medium', '2026-05-21', '2026-05-21 06:58:26', 'personal', '2026-05-25', NULL, 'task_1779321506_PAKET_1.docx', 'PAKET 1.docx'),
(66, 6, '1234567890', '', 'pending', 'medium', '2026-05-21', '2026-05-21 10:40:52', 'personal', NULL, NULL, NULL, NULL),
(67, 6, 'qwertyuiopasdfghjklz', '', 'pending', 'medium', '2026-05-21', '2026-05-21 10:41:24', 'personal', NULL, NULL, NULL, NULL),
(68, 2, 'lari', '', 'done', 'medium', '2026-05-21', '2026-05-21 21:20:21', 'uwgqud', NULL, 5, NULL, NULL),
(69, 2, 'mblayu', '', 'pending', 'medium', '2026-05-21', '2026-05-21 21:45:26', 'General', NULL, 5, NULL, NULL),
(70, 2, 'ya gitu', '', 'pending', 'medium', '2026-05-21', '2026-05-21 21:48:52', 'uwgqud', NULL, 5, NULL, NULL);

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
(3, 2, 'azril suka sawit', '', '#000000', '2026-04-30 07:08:01'),
(4, 4, 'none', '', '#235323', '2026-05-01 22:07:06'),
(5, 2, 'hai', 'Workspace: test', '#e63946', '2026-05-06 06:33:55'),
(6, 5, 'laporan', '', '#e63946', '2026-05-06 08:53:15'),
(8, 6, 'test', '', '#126422', '2026-05-21 09:13:54');

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
(1, 3, 'penting', '2026-05-03 13:23:22'),
(2, 5, 'uwgqud', '2026-05-05 23:34:06'),
(3, 5, 'qhewofweb', '2026-05-05 23:34:17');

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
(4, 'Test', '$2y$10$pjSR3em9R5WGOlveXaT9jep9Ak/5h1oW4PdMaurk2acvx7ELqXM36', 'user', '2026-05-01 21:44:20'),
(5, 'udin', '$2y$10$.3aULbo96DlBr5TodsD4y.Hm.azF3lSRaAk2GCO12kicm4SJWXBRC', 'user', '2026-05-06 08:40:57'),
(6, 'testing1', '$2y$10$bNELF8PVPyifB8xqqlHpEOiBTeGe1fAIjUNbEuWrNfSrgPgUDkVjG', 'user', '2026-05-21 08:51:26');

-- --------------------------------------------------------

--
-- Table structure for table `user_streak`
--

CREATE TABLE `user_streak` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `progress` int(11) NOT NULL DEFAULT 0,
  `streak_days` int(11) NOT NULL DEFAULT 0,
  `last_activity` date DEFAULT NULL,
  `tree_level` int(11) NOT NULL DEFAULT 1,
  `completed_tasks` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_streak`
--

INSERT INTO `user_streak` (`id`, `user_id`, `progress`, `streak_days`, `last_activity`, `tree_level`, `completed_tasks`, `updated_at`) VALUES
(92, 2, 110, 2, '2026-05-21', 1, 0, '2026-05-21 00:55:33'),
(301, 6, 0, 0, NULL, 1, 0, '2026-05-21 01:51:39');

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
-- Indexes for table `user_streak`
--
ALTER TABLE `user_streak`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user` (`user_id`);

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
  MODIFY `id_daily_activity` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id_project` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `project_sections`
--
ALTER TABLE `project_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_streak`
--
ALTER TABLE `user_streak`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=447;

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
