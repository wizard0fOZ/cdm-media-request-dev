-- Production dump for dev seed
CREATE DATABASE IF NOT EXISTS `appdb_dev`;
USE `appdb_dev`;

-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 07, 2026 at 01:00 PM
-- Server version: 8.0.44-cll-lve
-- PHP Version: 8.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `divineme_media_request`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `actor_user_id` bigint UNSIGNED DEFAULT NULL,
  `actor_name_snapshot` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` bigint UNSIGNED NOT NULL,
  `before_json` json DEFAULT NULL,
  `after_json` json DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `actor_user_id`, `actor_name_snapshot`, `action`, `entity_type`, `entity_id`, `before_json`, `after_json`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, NULL, 'Osmund Michael', 'CREATE_REQUEST', 'media_requests', 1, NULL, '{"is_late": false, "services": ["media", "photo"], "lead_days": 20, "reference_no": "MR-2026-0001", "schedule_type": "custom_list"}', '113.211.215.199', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 21:44:31'),
(2, NULL, 'Admin', 'APPROVE_REQUEST', 'media_requests', 1, '{"status": "pending", "reference_no": "MR-2026-0001"}', '{"status": "approved", "reference_no": "MR-2026-0001"}', '113.211.215.199', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 21:47:55'),
(3, NULL, 'Jonathan Michael', 'CREATE_REQUEST', 'media_requests', 2, NULL, '{"is_late": false, "services": ["photo", "media", "av"], "lead_days": 20, "reference_no": "MR-2026-0002", "schedule_type": "recurring"}', '172.224.240.20', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-02-01 22:05:34'),
(4, NULL, 'Admin', 'REJECT_REQUEST', 'media_requests', 2, '{"status": "pending", "reference_no": "MR-2026-0002"}', '{"status": "rejected", "reference_no": "MR-2026-0002", "rejection_reason": "womp womp"}', '113.211.215.199', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 22:11:41'),
(5, NULL, 'Joshua', 'CREATE_REQUEST', 'media_requests', 3, NULL, '{"is_late": true, "services": ["av", "photo", "media"], "lead_days": 7, "reference_no": "MR-2026-0003", "schedule_type": "custom_list"}', '110.159.150.126', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-03 15:09:05'),
(6, NULL, 'Joshua', 'CREATE_REQUEST', 'media_requests', 4, NULL, '{"is_late": false, "services": ["av", "media", "photo"], "lead_days": 43, "reference_no": "MR-2026-0004", "schedule_type": "custom_list"}', '110.159.150.126', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-03 16:08:20'),
(7, NULL, 'Omund', 'CREATE_REQUEST', 'media_requests', 5, NULL, '{"is_late": false, "services": ["av"], "lead_days": 18, "reference_no": "MR-2026-0005", "schedule_type": "custom_list"}', '121.120.98.141', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 15:02:20'),
(8, NULL, 'Jane Doe Test', 'CREATE_REQUEST', 'media_requests', 6, NULL, '{"is_late": false, "services": ["av"], "lead_days": 17, "reference_no": "MR-2026-0006", "schedule_type": "custom_list"}', '113.211.212.7', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 20:16:35'),
(9, NULL, 'Admin', 'APPROVE_REQUEST', 'media_requests', 6, '{"status": "pending", "reference_no": "MR-2026-0006"}', '{"status": "approved", "reference_no": "MR-2026-0006"}', '113.211.212.7', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 20:26:34'),
(10, NULL, 'Admin', 'APPROVE_REQUEST', 'media_requests', 5, '{"status": "pending", "reference_no": "MR-2026-0005"}', '{"status": "approved", "reference_no": "MR-2026-0005"}', '113.211.212.7', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 20:26:48'),
(11, NULL, 'Admin', 'REJECT_REQUEST', 'media_requests', 4, '{"status": "pending", "reference_no": "MR-2026-0004"}', '{"status": "rejected", "reference_no": "MR-2026-0004", "rejection_reason": "Test Rejection"}', '113.211.212.7', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 20:28:52'),
(12, NULL, 'Admin', 'APPROVE_REQUEST', 'media_requests', 3, '{"status": "pending", "reference_no": "MR-2026-0003"}', '{"status": "approved", "reference_no": "MR-2026-0003"}', '113.211.212.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 20:42:52');

-- --------------------------------------------------------

--
-- Table structure for table `av_details`
--

CREATE TABLE `av_details` (
  `id` bigint UNSIGNED NOT NULL,
  `media_request_id` bigint UNSIGNED NOT NULL,
  `rehearsal_date` date DEFAULT NULL,
  `rehearsal_start_time` time DEFAULT NULL,
  `rehearsal_end_time` time DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `av_internal_status` enum('na','pending','planned','setup_ready','done') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'na',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `av_details`
--

INSERT INTO `av_details` (`id`, `media_request_id`, `rehearsal_date`, `rehearsal_start_time`, `rehearsal_end_time`, `note`, `av_internal_status`, `created_at`, `updated_at`) VALUES
(1, 2, '2026-02-01', NULL, NULL, NULL, 'pending', '2026-02-01 22:05:34', '2026-02-01 22:05:34'),
(2, 3, '2026-02-18', '14:50:00', '16:22:00', 'nothing', 'pending', '2026-02-03 15:09:05', '2026-02-03 15:09:05'),
(3, 4, NULL, NULL, NULL, 'No notes', 'pending', '2026-02-03 16:08:20', '2026-02-03 16:08:20'),
(4, 5, NULL, NULL, NULL, NULL, 'pending', '2026-02-04 15:02:20', '2026-02-04 15:02:20'),
(5, 6, NULL, NULL, NULL, NULL, 'pending', '2026-02-05 20:16:35', '2026-02-05 20:16:35');

-- --------------------------------------------------------

--
-- Table structure for table `av_items`
--

CREATE TABLE `av_items` (
  `id` bigint UNSIGNED NOT NULL,
  `media_request_id` bigint UNSIGNED NOT NULL,
  `room_id` bigint UNSIGNED DEFAULT NULL,
  `equipment_id` bigint UNSIGNED NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `av_items`
--

INSERT INTO `av_items` (`id`, `media_request_id`, `room_id`, `equipment_id`, `quantity`, `note`) VALUES
(1, 2, 4, 8, 1, NULL),
(2, 2, 4, 1, 1, NULL),
(3, 3, 4, 8, 1, NULL),
(4, 3, 4, 1, 1, NULL),
(5, 4, 2, 8, 1, NULL),
(6, 4, 3, 1, 1, NULL),
(7, 4, 2, 1, 1, NULL),
(8, 5, 6, 7, 1, NULL),
(9, 6, 6, 8, 1, NULL),
(10, 6, 6, 2, 1, NULL),
(11, 6, 1, 8, 4, NULL),
(12, 6, 1, 1, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `content_channels`
--

CREATE TABLE `content_channels` (
  `id` bigint UNSIGNED NOT NULL,
  `content_item_id` bigint UNSIGNED NOT NULL,
  `channel` enum('facebook','instagram','telegram','tiktok','youtube','bulletin','av_projection','cm') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `publish_at` datetime DEFAULT NULL,
  `display_start_date` date DEFAULT NULL,
  `display_end_date` date DEFAULT NULL,
  `status` enum('na','pending','scheduled','posted','done') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'na',
  `updated_by_user_id` bigint UNSIGNED DEFAULT NULL,
  `updated_by_name_snapshot` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `content_items`
--

CREATE TABLE `content_items` (
  `id` bigint UNSIGNED NOT NULL,
  `media_request_id` bigint UNSIGNED DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_type` enum('poster','video','story','reel','article','slide','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'poster',
  `language` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `promo_start_date` date DEFAULT NULL,
  `promo_end_date` date DEFAULT NULL,
  `default_publish_at` datetime DEFAULT NULL,
  `default_display_start_date` date DEFAULT NULL,
  `default_display_end_date` date DEFAULT NULL,
  `caption_brief` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `final_caption` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `caption_status` enum('na','pending','done') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'na',
  `asset_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `asset_status` enum('na','pending','in_progress','ready','done') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'na',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `do_not_display` tinyint(1) NOT NULL DEFAULT '0',
  `asset_pic_user_id` bigint UNSIGNED DEFAULT NULL,
  `asset_pic_name_snapshot` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `socmed_pic_user_id` bigint UNSIGNED DEFAULT NULL,
  `socmed_pic_name_snapshot` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `content_notes`
--

CREATE TABLE `content_notes` (
  `id` bigint UNSIGNED NOT NULL,
  `content_item_id` bigint UNSIGNED NOT NULL,
  `actor_user_id` bigint UNSIGNED DEFAULT NULL,
  `actor_name_snapshot` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`id`, `name`, `category`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Projector (HDMI)', 'Video', 1, '2026-02-01 21:20:56', '2026-02-01 21:20:56'),
(2, 'TV (HDMI)', 'Video', 1, '2026-02-01 21:20:56', '2026-02-01 21:20:56'),
(3, 'Audio Input (3.5mm)', 'Audio', 1, '2026-02-01 21:20:56', '2026-02-01 21:20:56'),
(4, 'Audio Input (HDMI/XLR)', 'Audio', 1, '2026-02-01 21:20:56', '2026-02-01 21:20:56'),
(5, 'Audio Input (Bluetooth)', 'Audio', 1, '2026-02-01 21:20:56', '2026-02-01 21:20:56'),
(6, 'Audio Input (XLR)', 'Audio', 1, '2026-02-01 21:20:56', '2026-02-01 21:20:56'),
(7, 'Audio Input (HDMI/Bluetooth)', 'Audio', 1, '2026-02-01 21:20:56', '2026-02-01 21:20:56'),
(8, 'Wireless Microphone', 'Audio', 1, '2026-02-01 21:20:56', '2026-02-01 21:20:56'),
(9, 'Wired Microphone', 'Audio', 1, '2026-02-01 21:20:56', '2026-02-01 21:20:56'),
(10, 'Mic Stand', 'Accessories', 1, '2026-02-01 21:20:56', '2026-02-01 21:20:56'),
(11, 'Music Stand', 'Accessories', 1, '2026-02-01 21:20:56', '2026-02-01 21:20:56'),
(12, 'DI Box (Behringer Ultra-DI DI100)', 'Audio', 1, '2026-02-01 21:20:56', '2026-02-01 21:20:56'),
(13, 'Analog Mixer', 'Audio', 1, '2026-02-01 21:20:56', '2026-02-01 21:20:56'),
(14, 'Choir Mic (Wired)', 'Audio', 1, '2026-02-04 15:17:39', '2026-02-04 15:17:39'),
(15, 'Stage Mic', 'Audio', 1, '2026-02-04 15:17:39', '2026-02-04 15:17:39'),
(16, 'Commentator Mic', 'Audio', 1, '2026-02-04 15:17:39', '2026-02-04 15:17:39'),
(17, 'Lector Mic', 'Audio', 1, '2026-02-04 15:17:39', '2026-02-04 15:17:39'),
(18, 'Altar Mic', 'Audio', 1, '2026-02-04 15:17:39', '2026-02-04 15:17:39'),
(19, 'Ambo Mic', 'Audio', 1, '2026-02-04 15:17:39', '2026-02-04 15:17:39'),
(20, 'Piano', 'Instruments', 1, '2026-02-04 15:17:39', '2026-02-04 15:17:39'),
(21, 'Organ', 'Instruments', 1, '2026-02-04 15:17:39', '2026-02-04 15:17:39'),
(22, 'Drums', 'Instruments', 1, '2026-02-04 15:17:39', '2026-02-04 15:17:39'),
(23, 'Amplifier', 'Instruments', 1, '2026-02-04 15:17:39', '2026-02-04 15:17:39'),
(24, 'Audio System', 'Audio', 1, '2026-02-04 15:17:39', '2026-02-04 15:17:39'),
(25, 'Projection System', 'Video', 1, '2026-02-04 15:17:39', '2026-02-04 15:17:39');

-- --------------------------------------------------------

--
-- Table structure for table `event_occurrences`
--

CREATE TABLE `event_occurrences` (
  `id` bigint UNSIGNED NOT NULL,
  `event_schedule_id` bigint UNSIGNED NOT NULL,
  `occurrence_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `event_occurrences`
--

INSERT INTO `event_occurrences` (`id`, `event_schedule_id`, `occurrence_date`, `start_time`, `end_time`, `notes`, `created_at`) VALUES
(1, 1, '2026-02-21', NULL, NULL, NULL, '2026-02-01 21:44:31'),
(2, 3, '2026-02-10', NULL, NULL, NULL, '2026-02-03 15:09:05'),
(3, 3, '2026-02-17', '14:41:00', '19:38:00', NULL, '2026-02-03 15:09:05'),
(4, 4, '2026-03-18', '16:00:00', '17:00:00', NULL, '2026-02-03 16:08:20'),
(5, 5, '2026-02-22', NULL, NULL, NULL, '2026-02-04 15:02:20'),
(6, 6, '2026-02-22', NULL, NULL, NULL, '2026-02-05 20:16:35'),
(7, 6, '2026-03-08', NULL, NULL, NULL, '2026-02-05 20:16:35');

-- --------------------------------------------------------

--
-- Table structure for table `event_schedules`
--

CREATE TABLE `event_schedules` (
  `id` bigint UNSIGNED NOT NULL,
  `media_request_id` bigint UNSIGNED NOT NULL,
  `schedule_type` enum('single','recurring','custom_list') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `recurrence_pattern` enum('weekly','biweekly','monthly') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recurrence_days_of_week` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recurrence_interval` int DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `event_schedules`
--

INSERT INTO `event_schedules` (`id`, `media_request_id`, `schedule_type`, `start_date`, `end_date`, `start_time`, `end_time`, `recurrence_pattern`, `recurrence_days_of_week`, `recurrence_interval`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'custom_list', '2026-02-21', '2026-02-21', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-01 21:44:31', '2026-02-01 21:44:31'),
(2, 2, 'recurring', '2026-02-21', '2026-03-21', '22:03:00', '10:03:00', 'weekly', 'Wed', 1, NULL, '2026-02-01 22:05:34', '2026-02-01 22:05:34'),
(3, 3, 'custom_list', '2026-02-10', '2026-02-17', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-03 15:09:05', '2026-02-03 15:09:05'),
(4, 4, 'custom_list', '2026-03-18', '2026-03-18', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-03 16:08:20', '2026-02-03 16:08:20'),
(5, 5, 'custom_list', '2026-02-22', '2026-02-22', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-04 15:02:20', '2026-02-04 15:02:20'),
(6, 6, 'custom_list', '2026-02-22', '2026-03-08', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-05 20:16:35', '2026-02-05 20:16:35');

-- --------------------------------------------------------

--
-- Table structure for table `internal_notes`
--

CREATE TABLE `internal_notes` (
  `id` bigint UNSIGNED NOT NULL,
  `media_request_id` bigint UNSIGNED NOT NULL,
  `actor_name_snapshot` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actor_user_id` bigint UNSIGNED DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `media_details`
--

CREATE TABLE `media_details` (
  `id` bigint UNSIGNED NOT NULL,
  `media_request_id` bigint UNSIGNED NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `promo_start_date` date DEFAULT NULL,
  `promo_end_date` date DEFAULT NULL,
  `caption_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `media_details`
--

INSERT INTO `media_details` (`id`, `media_request_id`, `description`, `promo_start_date`, `promo_end_date`, `caption_details`, `note`, `created_at`, `updated_at`) VALUES
(1, 1, 'Poster description', NULL, NULL, 'caption details', NULL, '2026-02-01 21:44:31', '2026-02-01 21:44:31'),
(2, 2, 'Testing for fun', '2026-02-21', '2026-03-13', NULL, NULL, '2026-02-01 22:05:34', '2026-02-01 22:05:34'),
(3, 3, 'all okay', '2026-02-07', '2026-02-28', NULL, NULL, '2026-02-03 15:09:05', '2026-02-03 15:09:05'),
(4, 4, 'poster desc.', '2026-02-07', '2026-02-15', 'caption test', 'test additional notes', '2026-02-03 16:08:20', '2026-02-03 16:08:20');

-- --------------------------------------------------------

--
-- Table structure for table `media_platforms`
--

CREATE TABLE `media_platforms` (
  `id` bigint UNSIGNED NOT NULL,
  `media_request_id` bigint UNSIGNED NOT NULL,
  `platform` enum('facebook','instagram','telegram','tiktok','youtube','whatsapp','website','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `platform_other_label` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `media_platforms`
--

INSERT INTO `media_platforms` (`id`, `media_request_id`, `platform`, `platform_other_label`) VALUES
(1, 1, 'other', 'Bulletin'),
(2, 1, 'facebook', NULL),
(3, 1, 'instagram', NULL),
(4, 1, 'telegram', NULL),
(5, 2, 'facebook', NULL),
(6, 2, 'instagram', NULL),
(7, 2, 'telegram', NULL),
(8, 3, 'other', 'Bulletin'),
(9, 3, 'facebook', NULL),
(10, 3, 'instagram', NULL),
(11, 3, 'telegram', NULL),
(12, 4, 'other', 'Bulletin'),
(13, 4, 'facebook', NULL),
(14, 4, 'instagram', NULL),
(15, 4, 'telegram', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `media_requests`
--

CREATE TABLE `media_requests` (
  `id` bigint UNSIGNED NOT NULL,
  `reference_no` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `requestor_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ministry` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `has_required_approvals` tinyint(1) NOT NULL DEFAULT '0',
  `event_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `event_location_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `reference_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `reference_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `request_status` enum('pending','approved','rejected','in_progress','completed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `rejection_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_late` tinyint(1) NOT NULL DEFAULT '0',
  `lead_days` int DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `media_requests`
--

INSERT INTO `media_requests` (`id`, `reference_no`, `requestor_name`, `ministry`, `contact_no`, `email`, `has_required_approvals`, `event_name`, `event_description`, `event_location_note`, `reference_url`, `reference_note`, `request_status`, `rejection_reason`, `is_late`, `lead_days`, `submitted_at`, `created_at`, `updated_at`) VALUES
(1, 'MR-2026-0001', 'Osmund Michael', 'CDM AV', '0127200435', 'osmund.dev@gmail.com', 1, 'Test Event', 'Test Event Description', 'My Home', NULL, NULL, 'approved', NULL, 0, 20, '2026-02-01 21:44:31', '2026-02-01 21:44:31', '2026-02-01 21:47:55'),
(2, 'MR-2026-0002', 'Jonathan Michael', 'Altar server', '0183164877', 'jonathanmichael488@gmail.com', 1, 'Test event 2', 'Testing for fun', NULL, 'https://google.com', NULL, 'rejected', 'womp womp', 0, 20, '2026-02-01 22:05:34', '2026-02-01 22:05:34', '2026-02-01 22:11:41'),
(3, 'MR-2026-0003', 'Joshua', 'Media', '0122642745', 'media@divinemercy.my', 1, 'Form Testing', 'testing the form', 'Philip Minh', NULL, NULL, 'approved', NULL, 1, 7, '2026-02-03 15:09:05', '2026-02-03 15:09:05', '2026-02-05 20:42:52'),
(4, 'MR-2026-0004', 'Joshua', 'Media', '0122642745', 'media@divinemercy.my', 1, 'Test Name', 'test description', 'test locations', 'https://1drv.ms/f/c/727eed611c362984/IgB69YvwYam9TI8iX3phwIa1AZ_io02u4W7emGmhqY9v3ag?e=ZEKxVw', 'additional notes references', 'rejected', 'Test Rejection', 0, 43, '2026-02-03 16:08:20', '2026-02-03 16:08:20', '2026-02-05 20:28:52'),
(5, 'MR-2026-0005', 'Omund', NULL, '0127200435', 'osmundmicheal1@gmail.com', 1, 'Event Test', 'Hopefully it doesnt break T_T', NULL, NULL, NULL, 'approved', NULL, 0, 18, '2026-02-04 15:02:20', '2026-02-04 15:02:20', '2026-02-05 20:26:48'),
(6, 'MR-2026-0006', 'Jane Doe Test', 'Test Group', '0123456789', 'osmundmicheal1@gmail.com', 1, 'Test Event', 'Test Description', NULL, NULL, NULL, 'approved', NULL, 0, 17, '2026-02-05 20:16:35', '2026-02-05 20:16:35', '2026-02-05 20:26:34');

-- --------------------------------------------------------

--
-- Table structure for table `photo_details`
--

CREATE TABLE `photo_details` (
  `id` bigint UNSIGNED NOT NULL,
  `media_request_id` bigint UNSIGNED NOT NULL,
  `needed_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `photo_internal_status` enum('na','pending','assigned','done') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'na',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `photo_details`
--

INSERT INTO `photo_details` (`id`, `media_request_id`, `needed_date`, `start_time`, `end_time`, `note`, `photo_internal_status`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, NULL, NULL, NULL, 'pending', '2026-02-01 21:44:31', '2026-02-01 21:44:31'),
(2, 2, NULL, NULL, NULL, NULL, 'pending', '2026-02-01 22:05:34', '2026-02-01 22:05:34'),
(3, 3, NULL, NULL, NULL, 'none', 'pending', '2026-02-03 15:09:05', '2026-02-03 15:09:05'),
(4, 4, NULL, NULL, NULL, 'pictures of people', 'pending', '2026-02-03 16:08:20', '2026-02-03 16:08:20');

-- --------------------------------------------------------

--
-- Table structure for table `request_rooms`
--

CREATE TABLE `request_rooms` (
  `id` bigint UNSIGNED NOT NULL,
  `media_request_id` bigint UNSIGNED NOT NULL,
  `room_id` bigint UNSIGNED NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `request_rooms`
--

INSERT INTO `request_rooms` (`id`, `media_request_id`, `room_id`, `notes`) VALUES
(1, 2, 4, NULL),
(2, 3, 4, NULL),
(3, 3, 3, NULL),
(4, 4, 2, NULL),
(5, 4, 3, NULL),
(6, 5, 6, NULL),
(7, 6, 6, NULL),
(8, 6, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `request_types`
--

CREATE TABLE `request_types` (
  `id` bigint UNSIGNED NOT NULL,
  `media_request_id` bigint UNSIGNED NOT NULL,
  `type` enum('av','media','photo') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `approval_status` enum('pending','approved','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approved_by_user_id` bigint UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `request_types`
--

INSERT INTO `request_types` (`id`, `media_request_id`, `type`, `approval_status`, `approved_by_user_id`, `approved_at`, `rejected_reason`, `created_at`, `updated_at`) VALUES
(1, 1, 'media', 'pending', NULL, NULL, NULL, '2026-02-01 21:44:31', '2026-02-01 21:44:31'),
(2, 1, 'photo', 'pending', NULL, NULL, NULL, '2026-02-01 21:44:31', '2026-02-01 21:44:31'),
(3, 2, 'photo', 'pending', NULL, NULL, NULL, '2026-02-01 22:05:34', '2026-02-01 22:05:34'),
(4, 2, 'media', 'pending', NULL, NULL, NULL, '2026-02-01 22:05:34', '2026-02-01 22:05:34'),
(5, 2, 'av', 'pending', NULL, NULL, NULL, '2026-02-01 22:05:34', '2026-02-01 22:05:34'),
(6, 3, 'av', 'pending', NULL, NULL, NULL, '2026-02-03 15:09:05', '2026-02-03 15:09:05'),
(7, 3, 'photo', 'pending', NULL, NULL, NULL, '2026-02-03 15:09:05', '2026-02-03 15:09:05'),
(8, 3, 'media', 'pending', NULL, NULL, NULL, '2026-02-03 15:09:05', '2026-02-03 15:09:05'),
(9, 4, 'av', 'pending', NULL, NULL, NULL, '2026-02-03 16:08:20', '2026-02-03 16:08:20'),
(10, 4, 'media', 'pending', NULL, NULL, NULL, '2026-02-03 16:08:20', '2026-02-03 16:08:20'),
(11, 4, 'photo', 'pending', NULL, NULL, NULL, '2026-02-03 16:08:20', '2026-02-03 16:08:20'),
(12, 5, 'av', 'pending', NULL, NULL, NULL, '2026-02-04 15:02:20', '2026-02-04 15:02:20'),
(13, 6, 'av', 'pending', NULL, NULL, NULL, '2026-02-05 20:16:35', '2026-02-05 20:16:35');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `name`, `is_active`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'St. Faustina Hall', 1, 'Projector HDMI, audio 3.5mm, 4 wireless mics, 5 wired mics, stands, DI boxes, mixer', '2026-02-01 21:20:56', '2026-02-01 21:20:56'),
(2, 'St. John Vianney Room', 1, 'Projector HDMI, audio HDMI/XLR, 2 wireless mics', '2026-02-01 21:20:56', '2026-02-01 21:20:56'),
(3, 'St. Philip Minh Room', 1, 'Projector HDMI, audio HDMI/XLR, 2 wireless mics', '2026-02-01 21:20:56', '2026-02-01 21:20:56'),
(4, 'St. Joseph Room', 1, 'Projector HDMI, audio Bluetooth, 2 wireless mics', '2026-02-01 21:20:56', '2026-02-01 21:20:56'),
(5, 'Sacred Heart Room', 1, 'Projector HDMI, audio XLR, 2 wireless mics, analog mixer', '2026-02-01 21:20:56', '2026-02-01 21:20:56'),
(6, 'Cafeteria', 1, 'TV HDMI, audio HDMI/Bluetooth, 2 wireless mics', '2026-02-01 21:20:56', '2026-02-01 21:20:56'),
(7, 'Church', 1, 'Main church sanctuary', '2026-02-04 15:17:39', '2026-02-04 15:17:39');

-- --------------------------------------------------------

--
-- Table structure for table `room_blackouts`
--

CREATE TABLE `room_blackouts` (
  `id` bigint UNSIGNED NOT NULL,
  `room_id` bigint UNSIGNED NOT NULL,
  `start_at` datetime NOT NULL,
  `end_at` datetime NOT NULL,
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by_user_id` bigint UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `room_equipment`
--

CREATE TABLE `room_equipment` (
  `room_id` bigint UNSIGNED NOT NULL,
  `equipment_id` bigint UNSIGNED NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `room_equipment`
--

INSERT INTO `room_equipment` (`room_id`, `equipment_id`, `quantity`, `notes`) VALUES
(1, 1, 1, NULL),
(1, 3, 1, NULL),
(1, 8, 4, NULL),
(1, 9, 5, NULL),
(1, 10, 4, NULL),
(1, 11, 2, NULL),
(1, 12, 4, NULL),
(1, 13, 1, NULL),
(2, 1, 1, NULL),
(2, 4, 1, NULL),
(2, 8, 2, NULL),
(3, 1, 1, NULL),
(3, 4, 1, NULL),
(3, 8, 2, NULL),
(4, 1, 1, NULL),
(4, 5, 1, NULL),
(4, 8, 2, NULL),
(5, 1, 1, NULL),
(5, 6, 1, NULL),
(5, 8, 2, NULL),
(5, 13, 1, NULL),
(6, 2, 1, NULL),
(6, 7, 1, NULL),
(6, 8, 2, NULL),
(7, 8, 2, 'Cordless microphones'),
(7, 14, 16, 'Wired choir microphones'),
(7, 15, 2, 'Stage microphones'),
(7, 16, 1, NULL),
(7, 17, 1, NULL),
(7, 18, 1, NULL),
(7, 19, 1, NULL),
(7, 20, 1, NULL),
(7, 21, 1, NULL),
(7, 22, 1, NULL),
(7, 23, 3, 'Guitar/instrument amplifiers'),
(7, 24, 1, 'Main church audio system'),
(7, 25, 1, 'Church projection system');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('office_admin','media_head','media_asst','media_member','designer_head','designer_asst','designer_member','av_head','av_asst','av_member','sysadmin') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'media_member',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_audit_actor_date` (`actor_user_id`,`created_at`);

--
-- Indexes for table `av_details`
--
ALTER TABLE `av_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_av_details_request` (`media_request_id`);

--
-- Indexes for table `av_items`
--
ALTER TABLE `av_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_av_items_request` (`media_request_id`),
  ADD KEY `idx_av_items_room` (`room_id`),
  ADD KEY `idx_av_items_equipment` (`equipment_id`);

--
-- Indexes for table `content_channels`
--
ALTER TABLE `content_channels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cc_item_channel` (`content_item_id`,`channel`),
  ADD KEY `idx_cc_channel_status` (`channel`,`status`),
  ADD KEY `idx_cc_updated_by` (`updated_by_user_id`,`updated_at`);

--
-- Indexes for table `content_items`
--
ALTER TABLE `content_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ci_request` (`media_request_id`),
  ADD KEY `fk_ci_asset_pic_user` (`asset_pic_user_id`),
  ADD KEY `fk_ci_socmed_pic_user` (`socmed_pic_user_id`);

--
-- Indexes for table `content_notes`
--
ALTER TABLE `content_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cn_item_date` (`content_item_id`,`created_at`),
  ADD KEY `idx_cn_actor_date` (`actor_user_id`,`created_at`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_equipment_name` (`name`),
  ADD KEY `idx_equipment_active` (`is_active`),
  ADD KEY `idx_equipment_category` (`category`);

--
-- Indexes for table `event_occurrences`
--
ALTER TABLE `event_occurrences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_occurrence_date` (`occurrence_date`),
  ADD KEY `idx_occurrence_schedule` (`event_schedule_id`);

--
-- Indexes for table `event_schedules`
--
ALTER TABLE `event_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_schedule_request` (`media_request_id`),
  ADD KEY `idx_schedule_start` (`start_date`);

--
-- Indexes for table `internal_notes`
--
ALTER TABLE `internal_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notes_request_date` (`media_request_id`,`created_at`),
  ADD KEY `fk_internal_notes_user` (`actor_user_id`);

--
-- Indexes for table `media_details`
--
ALTER TABLE `media_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_media_details_request` (`media_request_id`);

--
-- Indexes for table `media_platforms`
--
ALTER TABLE `media_platforms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_media_platform` (`media_request_id`,`platform`);

--
-- Indexes for table `media_requests`
--
ALTER TABLE `media_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_requests_reference` (`reference_no`),
  ADD KEY `idx_requests_status_date` (`request_status`,`submitted_at`),
  ADD KEY `idx_requests_late_date` (`is_late`,`submitted_at`),
  ADD KEY `idx_requests_email` (`email`);

--
-- Indexes for table `photo_details`
--
ALTER TABLE `photo_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_photo_details_request` (`media_request_id`);

--
-- Indexes for table `request_rooms`
--
ALTER TABLE `request_rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_request_room` (`media_request_id`,`room_id`),
  ADD KEY `idx_request_rooms_room` (`room_id`);

--
-- Indexes for table `request_types`
--
ALTER TABLE `request_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_request_type` (`media_request_id`,`type`),
  ADD KEY `idx_type_status` (`type`,`approval_status`),
  ADD KEY `fk_request_types_approver` (`approved_by_user_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_rooms_name` (`name`),
  ADD KEY `idx_rooms_active` (`is_active`);

--
-- Indexes for table `room_blackouts`
--
ALTER TABLE `room_blackouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_blackouts_room` (`room_id`),
  ADD KEY `idx_blackouts_range` (`start_at`,`end_at`),
  ADD KEY `fk_blackouts_user` (`created_by_user_id`);

--
-- Indexes for table `room_equipment`
--
ALTER TABLE `room_equipment`
  ADD PRIMARY KEY (`room_id`,`equipment_id`),
  ADD KEY `fk_room_equipment_equipment` (`equipment_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_active` (`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `av_details`
--
ALTER TABLE `av_details`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `av_items`
--
ALTER TABLE `av_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `content_channels`
--
ALTER TABLE `content_channels`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content_items`
--
ALTER TABLE `content_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content_notes`
--
ALTER TABLE `content_notes`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `event_occurrences`
--
ALTER TABLE `event_occurrences`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `event_schedules`
--
ALTER TABLE `event_schedules`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `internal_notes`
--
ALTER TABLE `internal_notes`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `media_details`
--
ALTER TABLE `media_details`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `media_platforms`
--
ALTER TABLE `media_platforms`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `media_requests`
--
ALTER TABLE `media_requests`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `photo_details`
--
ALTER TABLE `photo_details`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `request_rooms`
--
ALTER TABLE `request_rooms`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `request_types`
--
ALTER TABLE `request_types`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `room_blackouts`
--
ALTER TABLE `room_blackouts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_actor` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `av_details`
--
ALTER TABLE `av_details`
  ADD CONSTRAINT `fk_av_details_request` FOREIGN KEY (`media_request_id`) REFERENCES `media_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `av_items`
--
ALTER TABLE `av_items`
  ADD CONSTRAINT `fk_av_items_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_av_items_request` FOREIGN KEY (`media_request_id`) REFERENCES `media_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_av_items_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `content_channels`
--
ALTER TABLE `content_channels`
  ADD CONSTRAINT `fk_cc_item` FOREIGN KEY (`content_item_id`) REFERENCES `content_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cc_updated_by` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `content_items`
--
ALTER TABLE `content_items`
  ADD CONSTRAINT `fk_ci_asset_pic_user` FOREIGN KEY (`asset_pic_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ci_request` FOREIGN KEY (`media_request_id`) REFERENCES `media_requests` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ci_socmed_pic_user` FOREIGN KEY (`socmed_pic_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `content_notes`
--
ALTER TABLE `content_notes`
  ADD CONSTRAINT `fk_cn_actor` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cn_item` FOREIGN KEY (`content_item_id`) REFERENCES `content_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `event_occurrences`
--
ALTER TABLE `event_occurrences`
  ADD CONSTRAINT `fk_event_occurrences_schedule` FOREIGN KEY (`event_schedule_id`) REFERENCES `event_schedules` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `event_schedules`
--
ALTER TABLE `event_schedules`
  ADD CONSTRAINT `fk_event_schedules_request` FOREIGN KEY (`media_request_id`) REFERENCES `media_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `internal_notes`
--
ALTER TABLE `internal_notes`
  ADD CONSTRAINT `fk_internal_notes_request` FOREIGN KEY (`media_request_id`) REFERENCES `media_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_internal_notes_user` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `media_details`
--
ALTER TABLE `media_details`
  ADD CONSTRAINT `fk_media_details_request` FOREIGN KEY (`media_request_id`) REFERENCES `media_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `media_platforms`
--
ALTER TABLE `media_platforms`
  ADD CONSTRAINT `fk_media_platforms_request` FOREIGN KEY (`media_request_id`) REFERENCES `media_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `photo_details`
--
ALTER TABLE `photo_details`
  ADD CONSTRAINT `fk_photo_details_request` FOREIGN KEY (`media_request_id`) REFERENCES `media_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `request_rooms`
--
ALTER TABLE `request_rooms`
  ADD CONSTRAINT `fk_request_rooms_request` FOREIGN KEY (`media_request_id`) REFERENCES `media_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_request_rooms_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `request_types`
--
ALTER TABLE `request_types`
  ADD CONSTRAINT `fk_request_types_approver` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_request_types_request` FOREIGN KEY (`media_request_id`) REFERENCES `media_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `room_blackouts`
--
ALTER TABLE `room_blackouts`
  ADD CONSTRAINT `fk_blackouts_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_blackouts_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `room_equipment`
--
ALTER TABLE `room_equipment`
  ADD CONSTRAINT `fk_room_equipment_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_room_equipment_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
