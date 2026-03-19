-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 09, 2026 at 02:41 PM
-- Server version: 10.3.39-MariaDB
-- PHP Version: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `paulgreb_adminability`
--

-- --------------------------------------------------------

--
-- Table structure for table `docs`
--

CREATE TABLE `docs` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `doc_type` enum('reference','process','workflow','guide') DEFAULT 'reference',
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `sort_order` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `docs`
--

INSERT INTO `docs` (`id`, `category_id`, `parent_id`, `title`, `slug`, `content`, `doc_type`, `status`, `sort_order`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'Test', 'test', '<p>Test from Paul</p>', 'reference', 'draft', 0, 1, 1, '2025-12-25 03:05:28', '2025-12-25 03:05:38');

-- --------------------------------------------------------

--
-- Table structure for table `doc_categories`
--

CREATE TABLE `doc_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'folder',
  `color` varchar(20) DEFAULT 'gray',
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `doc_categories`
--

INSERT INTO `doc_categories` (`id`, `name`, `slug`, `description`, `icon`, `color`, `sort_order`, `created_at`) VALUES
(1, 'Reference', 'reference', NULL, 'book', 'blue', 1, '2025-12-25 03:01:26'),
(2, 'Processes', 'processes', NULL, 'list', 'green', 2, '2025-12-25 03:01:26'),
(3, 'Workflows', 'workflows', NULL, 'flow', 'purple', 3, '2025-12-25 03:01:26'),
(4, 'Guides', 'guides', NULL, 'map', 'orange', 4, '2025-12-25 03:01:26');

-- --------------------------------------------------------

--
-- Table structure for table `doc_tags`
--

CREATE TABLE `doc_tags` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `color` varchar(20) DEFAULT 'gray',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `doc_tags`
--

INSERT INTO `doc_tags` (`id`, `name`, `slug`, `color`, `created_at`) VALUES
(1, 'Reference', 'reference', 'blue', '2026-01-14 01:59:21'),
(2, 'Process', 'process', 'green', '2026-01-14 01:59:21'),
(3, 'Guide', 'guide', 'purple', '2026-01-14 01:59:21'),
(4, 'Processes', 'processes', 'green', '2026-01-14 02:32:12'),
(5, 'Workflows', 'workflows', 'purple', '2026-01-14 02:32:12'),
(6, 'Guides', 'guides', 'orange', '2026-01-14 02:32:12');

-- --------------------------------------------------------

--
-- Table structure for table `doc_tag_map`
--

CREATE TABLE `doc_tag_map` (
  `doc_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `doc_tag_map`
--

INSERT INTO `doc_tag_map` (`doc_id`, `tag_id`) VALUES
(1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `ip_lockouts`
--

CREATE TABLE `ip_lockouts` (
  `ip_address` varchar(45) NOT NULL,
  `locked_until` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `attempt_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notes`
--

CREATE TABLE `notes` (
  `id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT 1,
  `parent_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text NOT NULL,
  `type` enum('note','idea','task','question') DEFAULT 'note',
  `status` enum('idea','in_progress','done') DEFAULT 'idea',
  `priority` enum('low','normal','high') DEFAULT 'normal',
  `is_pinned` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `notes`
--

INSERT INTO `notes` (`id`, `project_id`, `parent_id`, `title`, `content`, `type`, `status`, `priority`, `is_pinned`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(2, 1, NULL, NULL, 'Need to make UTM generator', 'task', 'idea', 'normal', 0, 2, NULL, '2025-12-16 16:54:24', '2025-12-16 16:54:24'),
(6, 1, NULL, 'Email Dashboard System', 'For Monday and Thursday emails, check drive to see where work is at.', 'note', 'idea', 'normal', 0, 2, NULL, '2025-12-17 01:54:19', '2025-12-17 01:54:19'),
(7, 1, NULL, 'Social Media Systems', 'Note that Metricool upload sheet for Instagram Carly Style posts is the basis for all other Instagram posts whether they are about articles or other things.\r\n\r\nOther article based posts - exactly the same setup\r\nnon-article based posts\r\n- either \r\n- 1 - caption with DM word to prompt link to lead magnet\r\n- 2 - awareness post of some kind with no prompt to DM anything', 'note', 'idea', 'normal', 0, 2, NULL, '2025-12-17 20:24:27', '2025-12-17 20:24:27'),
(9, 1, NULL, 'Upload scripts', 'For A&I and Anita namesite, we have to have all upload batch files created.', 'note', 'idea', 'normal', 0, 2, NULL, '2025-12-18 17:35:09', '2025-12-18 17:35:09'),
(14, 1, NULL, 'Additional Social Media Work', '- prep more Pinterest images to keep cycle going\r\n- do same for square images\r\n\r\n- curate title pins\r\n- make square article title pins\r\n\r\n- step lesson Angel Quotes further\r\n- create Metricool sheets for different platforms for Angel Quotes', 'note', 'idea', 'normal', 0, 2, NULL, '2025-12-25 03:18:57', '2025-12-25 03:18:57'),
(15, 1, NULL, 'Subjects - # coding', 'Need to continue assigning #s to the subjects.', 'task', 'idea', 'normal', 0, 2, NULL, '2025-12-25 03:20:17', '2025-12-25 03:20:17');

-- --------------------------------------------------------

--
-- Table structure for table `note_projects`
--

CREATE TABLE `note_projects` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `color` varchar(20) DEFAULT 'gray',
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `note_projects`
--

INSERT INTO `note_projects` (`id`, `name`, `color`, `sort_order`, `created_at`) VALUES
(1, 'General', 'gray', 1, '2025-12-15 21:57:41'),
(2, 'Affirmations Project', 'purple', 2, '2025-12-15 21:57:41'),
(3, 'Website', 'blue', 3, '2025-12-15 21:57:41'),
(4, 'Ideas', 'yellow', 4, '2025-12-15 21:57:41');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'users.view', 'View users', '2025-12-14 19:00:38'),
(2, 'users.create', 'Create users', '2025-12-14 19:00:38'),
(3, 'users.edit', 'Edit users', '2025-12-14 19:00:38'),
(4, 'users.delete', 'Delete users', '2025-12-14 19:00:38'),
(5, 'roles.view', 'View roles', '2025-12-14 19:00:38'),
(6, 'roles.manage', 'Manage roles and permissions', '2025-12-14 19:00:38'),
(7, 'dashboard.view', 'View dashboard', '2025-12-14 19:00:38'),
(8, 'notes.view', 'View notes', '2025-12-14 19:00:38'),
(9, 'notes.create', 'Create notes', '2025-12-14 19:00:38'),
(10, 'notes.edit', 'Edit notes', '2025-12-14 19:00:38'),
(11, 'notes.delete', 'Delete notes', '2025-12-14 19:00:38'),
(12, 'videos.view', 'View video tracker', '2025-12-15 02:30:39'),
(13, 'videos.create', 'Create videos and categories', '2025-12-15 02:30:39'),
(14, 'videos.edit', 'Edit video progress', '2025-12-15 02:30:39'),
(15, 'videos.delete', 'Delete videos and categories', '2025-12-15 02:30:39'),
(20, 'docs.view', 'View documents', '2025-12-25 03:01:26'),
(21, 'docs.create', 'Create documents', '2025-12-25 03:01:26'),
(22, 'docs.edit', 'Edit documents', '2025-12-25 03:01:26'),
(23, 'docs.delete', 'Delete documents', '2025-12-25 03:01:26');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'super_admin', 'Full access to all features', '2025-12-14 19:00:38'),
(2, 'admin', 'Administrative access', '2025-12-14 19:00:38'),
(3, 'editor', 'Can view and edit content', '2025-12-14 19:00:38'),
(4, 'viewer', 'Read-only access', '2025-12-14 19:00:38');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(1, 12),
(1, 13),
(1, 14),
(1, 15),
(1, 20),
(1, 21),
(1, 22),
(1, 23),
(2, 1),
(2, 2),
(2, 3),
(2, 5),
(2, 7),
(2, 8),
(2, 9),
(2, 10),
(2, 11),
(2, 12),
(2, 13),
(2, 14),
(2, 15),
(3, 7),
(3, 8),
(3, 9),
(3, 10),
(3, 12),
(3, 13),
(3, 14),
(3, 15),
(4, 7),
(4, 8),
(4, 12),
(4, 13),
(4, 14),
(4, 15);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `name`, `first_name`, `role_id`, `is_active`, `created_at`, `updated_at`, `last_login`) VALUES
(1, 'paul@paulgreblick.com', '$2y$12$F/mgVio/tX9BEPzjcbqPJe1yjX9tHJfoL1Epu9YHgN1VWvBEZpzgC', 'Paul Greblick', 'Paul', 1, 1, '2025-12-14 19:00:38', '2025-12-25 21:03:04', '2025-12-25 03:04:28'),
(2, 'anita@angelsandinsights.com', '$2y$10$gt/Pb8vm1lnbrq/mNlARBeBcgyEjfX7BBnbOlykwBZ71wWEXwpXmO', 'Anita Colussi-Zanon', 'Anita', 1, 1, '2025-12-14 19:07:11', '2026-01-15 14:54:26', '2026-01-15 14:54:26');

-- --------------------------------------------------------

--
-- Table structure for table `videos`
--

CREATE TABLE `videos` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `folder_link` varchar(500) DEFAULT NULL,
  `youtube_url` varchar(255) DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `videos`
--

INSERT INTO `videos` (`id`, `category_id`, `title`, `notes`, `folder_link`, `youtube_url`, `published_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'Manifestation', NULL, NULL, NULL, NULL, '2025-12-15 03:28:54', '2025-12-15 03:28:54'),
(2, 1, 'Stress', NULL, NULL, NULL, NULL, '2025-12-15 03:28:54', '2025-12-15 03:28:54'),
(3, 1, 'Overwhelm', NULL, NULL, NULL, NULL, '2025-12-15 03:28:54', '2025-12-15 03:28:54'),
(4, 1, 'Anxiety', NULL, NULL, NULL, NULL, '2025-12-15 03:28:54', '2025-12-15 03:28:54'),
(5, 1, 'Abundance', NULL, NULL, NULL, NULL, '2025-12-15 03:28:54', '2025-12-15 03:28:54'),
(6, 1, 'Happiness', NULL, NULL, NULL, NULL, '2025-12-15 03:28:54', '2025-12-15 03:28:54'),
(7, 1, 'Self Love', NULL, NULL, NULL, NULL, '2025-12-15 03:28:54', '2025-12-15 03:28:54'),
(8, 1, 'Morning Positive Energy', NULL, NULL, NULL, NULL, '2025-12-15 03:28:54', '2025-12-15 03:28:54'),
(9, 1, 'Money', NULL, NULL, NULL, NULL, '2025-12-15 03:28:54', '2025-12-15 03:28:54'),
(10, 1, 'Healing from the Past', NULL, NULL, NULL, NULL, '2025-12-15 03:28:54', '2025-12-15 03:28:54'),
(11, 1, 'Worry', NULL, NULL, NULL, NULL, '2025-12-15 03:28:54', '2025-12-15 03:28:54'),
(12, 1, 'Peace and Calm', NULL, NULL, NULL, NULL, '2025-12-15 03:28:54', '2025-12-15 03:28:54'),
(13, 1, 'Positive Life Changes', NULL, NULL, NULL, NULL, '2025-12-15 03:28:54', '2025-12-15 03:28:54'),
(14, 1, 'Success', NULL, NULL, NULL, NULL, '2025-12-15 03:28:54', '2025-12-15 03:28:54'),
(15, 1, 'Wealth', NULL, NULL, NULL, NULL, '2025-12-15 03:28:54', '2025-12-15 03:28:54'),
(16, 1, 'Health', NULL, NULL, NULL, NULL, '2025-12-15 03:28:54', '2025-12-15 03:28:54');

-- --------------------------------------------------------

--
-- Table structure for table `video_categories`
--

CREATE TABLE `video_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `video_categories`
--

INSERT INTO `video_categories` (`id`, `name`, `description`, `sort_order`, `created_at`) VALUES
(1, 'Affirmations', 'Faceless YouTube affirmation videos', 0, '2025-12-15 03:28:54');

-- --------------------------------------------------------

--
-- Table structure for table `video_progress`
--

CREATE TABLE `video_progress` (
  `id` int(11) NOT NULL,
  `video_id` int(11) NOT NULL,
  `step_id` int(11) NOT NULL,
  `status` enum('not_started','in_progress','complete') DEFAULT 'not_started',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `video_progress`
--

INSERT INTO `video_progress` (`id`, `video_id`, `step_id`, `status`, `updated_at`) VALUES
(1, 1, 1, 'not_started', '2025-12-15 03:28:54'),
(2, 1, 2, 'not_started', '2025-12-15 03:28:54'),
(3, 1, 3, 'not_started', '2025-12-15 03:28:54'),
(4, 1, 4, 'not_started', '2025-12-15 03:28:54'),
(5, 1, 5, 'not_started', '2025-12-15 03:28:54'),
(6, 1, 6, 'not_started', '2025-12-15 03:28:54'),
(7, 1, 7, 'not_started', '2025-12-15 03:28:54'),
(8, 1, 8, 'not_started', '2025-12-15 03:28:54'),
(9, 1, 9, 'not_started', '2025-12-15 03:28:54'),
(10, 1, 10, 'not_started', '2025-12-15 03:28:54'),
(11, 1, 11, 'not_started', '2025-12-15 03:28:54'),
(12, 1, 12, 'not_started', '2025-12-15 03:28:54'),
(13, 2, 1, 'not_started', '2025-12-15 03:28:54'),
(14, 2, 2, 'not_started', '2025-12-15 03:28:54'),
(15, 2, 3, 'not_started', '2025-12-15 03:28:54'),
(16, 2, 4, 'not_started', '2025-12-15 03:28:54'),
(17, 2, 5, 'not_started', '2025-12-15 03:28:54'),
(18, 2, 6, 'not_started', '2025-12-15 03:28:54'),
(19, 2, 7, 'not_started', '2025-12-15 03:28:54'),
(20, 2, 8, 'not_started', '2025-12-15 03:28:54'),
(21, 2, 9, 'not_started', '2025-12-15 03:28:54'),
(22, 2, 10, 'not_started', '2025-12-15 03:28:54'),
(23, 2, 11, 'not_started', '2025-12-15 03:28:54'),
(24, 2, 12, 'not_started', '2025-12-15 03:28:54'),
(25, 3, 1, 'not_started', '2025-12-15 03:28:54'),
(26, 3, 2, 'not_started', '2025-12-15 03:28:54'),
(27, 3, 3, 'not_started', '2025-12-15 03:28:54'),
(28, 3, 4, 'not_started', '2025-12-15 03:28:54'),
(29, 3, 5, 'not_started', '2025-12-15 03:28:54'),
(30, 3, 6, 'not_started', '2025-12-15 03:28:54'),
(31, 3, 7, 'not_started', '2025-12-15 03:28:54'),
(32, 3, 8, 'not_started', '2025-12-15 03:28:54'),
(33, 3, 9, 'not_started', '2025-12-15 03:28:54'),
(34, 3, 10, 'not_started', '2025-12-15 03:28:54'),
(35, 3, 11, 'not_started', '2025-12-15 03:28:54'),
(36, 3, 12, 'not_started', '2025-12-15 03:28:54'),
(37, 4, 1, 'not_started', '2025-12-15 03:28:54'),
(38, 4, 2, 'not_started', '2025-12-15 03:28:54'),
(39, 4, 3, 'not_started', '2025-12-15 03:28:54'),
(40, 4, 4, 'not_started', '2025-12-15 03:28:54'),
(41, 4, 5, 'not_started', '2025-12-15 03:28:54'),
(42, 4, 6, 'not_started', '2025-12-15 03:28:54'),
(43, 4, 7, 'not_started', '2025-12-15 03:28:54'),
(44, 4, 8, 'not_started', '2025-12-15 03:28:54'),
(45, 4, 9, 'not_started', '2025-12-15 03:28:54'),
(46, 4, 10, 'not_started', '2025-12-15 03:28:54'),
(47, 4, 11, 'not_started', '2025-12-15 03:28:54'),
(48, 4, 12, 'not_started', '2025-12-15 03:28:54'),
(49, 5, 1, 'not_started', '2025-12-15 03:28:54'),
(50, 5, 2, 'not_started', '2025-12-15 03:28:54'),
(51, 5, 3, 'in_progress', '2025-12-16 00:16:45'),
(52, 5, 4, 'not_started', '2025-12-15 03:28:54'),
(53, 5, 5, 'not_started', '2025-12-15 03:28:54'),
(54, 5, 6, 'not_started', '2025-12-15 03:28:54'),
(55, 5, 7, 'not_started', '2025-12-15 03:52:03'),
(56, 5, 8, 'not_started', '2025-12-15 03:28:54'),
(57, 5, 9, 'not_started', '2025-12-15 03:28:54'),
(58, 5, 10, 'not_started', '2025-12-15 03:28:54'),
(59, 5, 11, 'not_started', '2025-12-15 03:28:54'),
(60, 5, 12, 'not_started', '2025-12-15 03:28:54'),
(61, 6, 1, 'not_started', '2025-12-15 03:28:54'),
(62, 6, 2, 'not_started', '2025-12-15 03:28:54'),
(63, 6, 3, 'not_started', '2025-12-15 03:28:54'),
(64, 6, 4, 'not_started', '2025-12-15 03:28:54'),
(65, 6, 5, 'not_started', '2025-12-15 03:28:54'),
(66, 6, 6, 'not_started', '2025-12-15 03:28:54'),
(67, 6, 7, 'not_started', '2025-12-15 03:28:54'),
(68, 6, 8, 'not_started', '2025-12-15 03:28:54'),
(69, 6, 9, 'not_started', '2025-12-15 03:28:54'),
(70, 6, 10, 'not_started', '2025-12-15 03:28:54'),
(71, 6, 11, 'not_started', '2025-12-15 03:28:54'),
(72, 6, 12, 'not_started', '2025-12-15 03:28:54'),
(73, 7, 1, 'not_started', '2025-12-15 03:28:54'),
(74, 7, 2, 'not_started', '2025-12-15 03:28:54'),
(75, 7, 3, 'not_started', '2025-12-15 03:28:54'),
(76, 7, 4, 'not_started', '2025-12-15 03:28:54'),
(77, 7, 5, 'not_started', '2025-12-15 03:28:54'),
(78, 7, 6, 'not_started', '2025-12-15 03:28:54'),
(79, 7, 7, 'not_started', '2025-12-15 03:28:54'),
(80, 7, 8, 'not_started', '2025-12-15 03:28:54'),
(81, 7, 9, 'not_started', '2025-12-15 03:28:54'),
(82, 7, 10, 'not_started', '2025-12-15 03:28:54'),
(83, 7, 11, 'not_started', '2025-12-15 03:28:54'),
(84, 7, 12, 'not_started', '2025-12-15 03:28:54'),
(85, 8, 1, 'not_started', '2025-12-15 03:28:54'),
(86, 8, 2, 'not_started', '2025-12-15 03:28:54'),
(87, 8, 3, 'not_started', '2025-12-15 03:28:54'),
(88, 8, 4, 'not_started', '2025-12-15 03:28:54'),
(89, 8, 5, 'not_started', '2025-12-15 03:28:54'),
(90, 8, 6, 'not_started', '2025-12-15 03:28:54'),
(91, 8, 7, 'not_started', '2025-12-15 03:28:54'),
(92, 8, 8, 'not_started', '2025-12-15 03:28:54'),
(93, 8, 9, 'not_started', '2025-12-15 03:28:54'),
(94, 8, 10, 'not_started', '2025-12-15 03:28:54'),
(95, 8, 11, 'not_started', '2025-12-15 03:28:54'),
(96, 8, 12, 'not_started', '2025-12-15 03:28:54'),
(97, 9, 1, 'not_started', '2025-12-15 03:28:54'),
(98, 9, 2, 'not_started', '2025-12-15 03:28:54'),
(99, 9, 3, 'not_started', '2025-12-15 03:28:54'),
(100, 9, 4, 'not_started', '2025-12-15 03:28:54'),
(101, 9, 5, 'not_started', '2025-12-15 03:28:54'),
(102, 9, 6, 'not_started', '2025-12-15 03:28:54'),
(103, 9, 7, 'not_started', '2025-12-15 03:28:54'),
(104, 9, 8, 'not_started', '2025-12-15 03:28:54'),
(105, 9, 9, 'not_started', '2025-12-15 03:28:54'),
(106, 9, 10, 'not_started', '2025-12-15 03:28:54'),
(107, 9, 11, 'not_started', '2025-12-15 03:28:54'),
(108, 9, 12, 'not_started', '2025-12-15 03:28:54'),
(109, 10, 1, 'not_started', '2025-12-15 03:28:54'),
(110, 10, 2, 'not_started', '2025-12-15 03:28:54'),
(111, 10, 3, 'not_started', '2025-12-15 03:28:54'),
(112, 10, 4, 'not_started', '2025-12-15 03:28:54'),
(113, 10, 5, 'not_started', '2025-12-15 03:28:54'),
(114, 10, 6, 'not_started', '2025-12-15 03:28:54'),
(115, 10, 7, 'not_started', '2025-12-15 03:28:54'),
(116, 10, 8, 'not_started', '2025-12-15 03:28:54'),
(117, 10, 9, 'not_started', '2025-12-15 03:28:54'),
(118, 10, 10, 'not_started', '2025-12-15 03:28:54'),
(119, 10, 11, 'not_started', '2025-12-15 03:28:54'),
(120, 10, 12, 'not_started', '2025-12-15 03:28:54'),
(121, 11, 1, 'not_started', '2025-12-15 03:28:54'),
(122, 11, 2, 'not_started', '2025-12-15 03:28:54'),
(123, 11, 3, 'not_started', '2025-12-15 03:28:54'),
(124, 11, 4, 'not_started', '2025-12-15 03:28:54'),
(125, 11, 5, 'not_started', '2025-12-15 03:28:54'),
(126, 11, 6, 'not_started', '2025-12-15 03:28:54'),
(127, 11, 7, 'not_started', '2025-12-15 03:28:54'),
(128, 11, 8, 'not_started', '2025-12-15 03:28:54'),
(129, 11, 9, 'not_started', '2025-12-15 03:28:54'),
(130, 11, 10, 'not_started', '2025-12-15 03:28:54'),
(131, 11, 11, 'not_started', '2025-12-15 03:28:54'),
(132, 11, 12, 'not_started', '2025-12-15 03:28:54'),
(133, 12, 1, 'not_started', '2025-12-15 03:28:54'),
(134, 12, 2, 'not_started', '2025-12-15 03:28:54'),
(135, 12, 3, 'not_started', '2025-12-15 03:28:54'),
(136, 12, 4, 'not_started', '2025-12-15 03:28:54'),
(137, 12, 5, 'not_started', '2025-12-15 03:28:54'),
(138, 12, 6, 'not_started', '2025-12-15 03:28:54'),
(139, 12, 7, 'not_started', '2025-12-15 03:28:54'),
(140, 12, 8, 'not_started', '2025-12-15 03:28:54'),
(141, 12, 9, 'not_started', '2025-12-15 03:28:54'),
(142, 12, 10, 'not_started', '2025-12-15 03:28:54'),
(143, 12, 11, 'not_started', '2025-12-15 03:28:54'),
(144, 12, 12, 'not_started', '2025-12-15 03:28:54'),
(145, 13, 1, 'not_started', '2025-12-15 03:28:54'),
(146, 13, 2, 'not_started', '2025-12-15 03:28:54'),
(147, 13, 3, 'not_started', '2025-12-15 03:28:54'),
(148, 13, 4, 'not_started', '2025-12-15 03:28:54'),
(149, 13, 5, 'not_started', '2025-12-15 03:28:54'),
(150, 13, 6, 'not_started', '2025-12-15 03:28:54'),
(151, 13, 7, 'not_started', '2025-12-15 03:28:54'),
(152, 13, 8, 'not_started', '2025-12-15 03:28:54'),
(153, 13, 9, 'not_started', '2025-12-15 03:28:54'),
(154, 13, 10, 'not_started', '2025-12-15 03:28:54'),
(155, 13, 11, 'not_started', '2025-12-15 03:28:54'),
(156, 13, 12, 'not_started', '2025-12-15 03:28:54'),
(157, 14, 1, 'not_started', '2025-12-15 03:28:54'),
(158, 14, 2, 'not_started', '2025-12-15 03:28:54'),
(159, 14, 3, 'not_started', '2025-12-15 03:28:54'),
(160, 14, 4, 'not_started', '2025-12-15 03:28:54'),
(161, 14, 5, 'not_started', '2025-12-15 03:28:54'),
(162, 14, 6, 'not_started', '2025-12-15 03:28:54'),
(163, 14, 7, 'not_started', '2025-12-15 03:28:54'),
(164, 14, 8, 'not_started', '2025-12-15 03:28:54'),
(165, 14, 9, 'not_started', '2025-12-15 03:28:54'),
(166, 14, 10, 'not_started', '2025-12-15 03:28:54'),
(167, 14, 11, 'not_started', '2025-12-15 03:28:54'),
(168, 14, 12, 'not_started', '2025-12-15 03:28:54'),
(169, 15, 1, 'not_started', '2025-12-15 03:28:54'),
(170, 15, 2, 'not_started', '2025-12-15 03:28:54'),
(171, 15, 3, 'not_started', '2025-12-15 03:28:54'),
(172, 15, 4, 'not_started', '2025-12-15 03:28:54'),
(173, 15, 5, 'not_started', '2025-12-15 03:28:54'),
(174, 15, 6, 'not_started', '2025-12-15 03:28:54'),
(175, 15, 7, 'not_started', '2025-12-15 03:28:54'),
(176, 15, 8, 'not_started', '2025-12-15 03:28:54'),
(177, 15, 9, 'not_started', '2025-12-15 03:28:54'),
(178, 15, 10, 'not_started', '2025-12-15 03:28:54'),
(179, 15, 11, 'not_started', '2025-12-15 03:28:54'),
(180, 15, 12, 'not_started', '2025-12-15 03:28:54'),
(181, 16, 1, 'not_started', '2025-12-15 03:49:55'),
(182, 16, 2, 'not_started', '2025-12-15 03:28:54'),
(183, 16, 3, 'not_started', '2025-12-15 03:28:54'),
(184, 16, 4, 'not_started', '2025-12-15 03:28:54'),
(185, 16, 5, 'not_started', '2025-12-15 03:28:54'),
(186, 16, 6, 'not_started', '2025-12-15 03:28:54'),
(187, 16, 7, 'not_started', '2025-12-15 03:28:54'),
(188, 16, 8, 'not_started', '2025-12-15 03:28:54'),
(189, 16, 9, 'not_started', '2025-12-15 03:28:54'),
(190, 16, 10, 'not_started', '2025-12-15 03:28:54'),
(191, 16, 11, 'not_started', '2025-12-15 03:28:54'),
(192, 16, 12, 'not_started', '2025-12-15 03:28:54');

-- --------------------------------------------------------

--
-- Table structure for table `workflow_steps`
--

CREATE TABLE `workflow_steps` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phase` enum('writing','audio','video','publish','final') NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `workflow_steps`
--

INSERT INTO `workflow_steps` (`id`, `name`, `phase`, `sort_order`, `created_at`) VALUES
(1, 'Script Draft', 'writing', 1, '2025-12-15 03:28:54'),
(2, 'Script Final', 'writing', 2, '2025-12-15 03:28:54'),
(3, 'Base Recording', 'audio', 3, '2025-12-15 03:28:54'),
(4, 'Editing', 'audio', 4, '2025-12-15 03:28:54'),
(5, 'PowerPoint Created', 'video', 5, '2025-12-15 03:28:54'),
(6, 'PowerPoint Assembled', 'video', 6, '2025-12-15 03:28:54'),
(7, 'Title Confirmed', 'publish', 7, '2025-12-15 03:28:54'),
(8, 'Thumbnail Created', 'publish', 8, '2025-12-15 03:28:54'),
(9, 'Description Created', 'publish', 9, '2025-12-15 03:28:54'),
(10, 'IG Comments Created', 'publish', 10, '2025-12-15 03:28:54'),
(11, 'Uploaded to YouTube', 'final', 11, '2025-12-15 03:28:54'),
(12, 'Comments Pinned', 'final', 12, '2025-12-15 03:28:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `docs`
--
ALTER TABLE `docs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `doc_categories`
--
ALTER TABLE `doc_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `doc_tags`
--
ALTER TABLE `doc_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `doc_tag_map`
--
ALTER TABLE `doc_tag_map`
  ADD PRIMARY KEY (`doc_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexes for table `ip_lockouts`
--
ALTER TABLE `ip_lockouts`
  ADD PRIMARY KEY (`ip_address`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_time` (`ip_address`,`attempted_at`);

--
-- Indexes for table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_notes_project` (`project_id`),
  ADD KEY `idx_notes_parent` (`parent_id`);

--
-- Indexes for table `note_projects`
--
ALTER TABLE `note_projects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `video_categories`
--
ALTER TABLE `video_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `video_progress`
--
ALTER TABLE `video_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_video_step` (`video_id`,`step_id`),
  ADD KEY `step_id` (`step_id`);

--
-- Indexes for table `workflow_steps`
--
ALTER TABLE `workflow_steps`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `docs`
--
ALTER TABLE `docs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `doc_categories`
--
ALTER TABLE `doc_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `doc_tags`
--
ALTER TABLE `doc_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `note_projects`
--
ALTER TABLE `note_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `video_categories`
--
ALTER TABLE `video_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `video_progress`
--
ALTER TABLE `video_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=193;

--
-- AUTO_INCREMENT for table `workflow_steps`
--
ALTER TABLE `workflow_steps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `docs`
--
ALTER TABLE `docs`
  ADD CONSTRAINT `docs_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `doc_categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `docs_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `docs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `docs_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `docs_ibfk_4` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `doc_tag_map`
--
ALTER TABLE `doc_tag_map`
  ADD CONSTRAINT `doc_tag_map_ibfk_1` FOREIGN KEY (`doc_id`) REFERENCES `docs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doc_tag_map_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `doc_tags` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notes_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `note_projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notes_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `notes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notes_ibfk_4` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `videos`
--
ALTER TABLE `videos`
  ADD CONSTRAINT `videos_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `video_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `video_progress`
--
ALTER TABLE `video_progress`
  ADD CONSTRAINT `video_progress_ibfk_1` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `video_progress_ibfk_2` FOREIGN KEY (`step_id`) REFERENCES `workflow_steps` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
