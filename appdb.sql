-- Adminer 5.4.1 MySQL 8.4.7 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

CREATE DATABASE `appdb` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `appdb`;

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `actor_user_id` bigint unsigned DEFAULT NULL,
  `actor_name_snapshot` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` bigint unsigned NOT NULL,
  `before_json` json DEFAULT NULL,
  `after_json` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_entity` (`entity_type`,`entity_id`),
  KEY `idx_audit_actor_date` (`actor_user_id`,`created_at`),
  CONSTRAINT `fk_audit_actor` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DELIMITER ;;

CREATE TRIGGER `bi_audit_logs_snapshot` BEFORE INSERT ON `audit_logs` FOR EACH ROW
BEGIN
  IF NEW.actor_user_id IS NOT NULL THEN
    SET NEW.actor_name_snapshot = NULL;
  END IF;
END;;

CREATE TRIGGER `bu_audit_logs_snapshot` BEFORE UPDATE ON `audit_logs` FOR EACH ROW
BEGIN
  IF NEW.actor_user_id IS NOT NULL THEN
    SET NEW.actor_name_snapshot = NULL;
  END IF;
END;;

DELIMITER ;

DROP TABLE IF EXISTS `av_details`;
CREATE TABLE `av_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `media_request_id` bigint unsigned NOT NULL,
  `rehearsal_date` date DEFAULT NULL,
  `rehearsal_start_time` time DEFAULT NULL,
  `rehearsal_end_time` time DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `av_internal_status` enum('na','pending','planned','setup_ready','done') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'na',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_av_details_request` (`media_request_id`),
  CONSTRAINT `fk_av_details_request` FOREIGN KEY (`media_request_id`) REFERENCES `media_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `av_details` (`id`, `media_request_id`, `rehearsal_date`, `rehearsal_start_time`, `rehearsal_end_time`, `note`, `av_internal_status`, `created_at`, `updated_at`) VALUES
(1,	1,	'2026-01-30',	'20:00:00',	'21:00:00',	'Rehearsal only needs 2 mics and projector check.',	'pending',	'2026-01-10 23:56:56',	'2026-01-10 23:56:56');

DROP TABLE IF EXISTS `av_items`;
CREATE TABLE `av_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `media_request_id` bigint unsigned NOT NULL,
  `room_id` bigint unsigned DEFAULT NULL,
  `equipment_id` bigint unsigned NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_av_items_request` (`media_request_id`),
  KEY `idx_av_items_room` (`room_id`),
  KEY `idx_av_items_equipment` (`equipment_id`),
  CONSTRAINT `fk_av_items_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_av_items_request` FOREIGN KEY (`media_request_id`) REFERENCES `media_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_av_items_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `av_items` (`id`, `media_request_id`, `room_id`, `equipment_id`, `quantity`, `note`) VALUES
(1,	1,	1,	1,	2,	'Wireless mics for speakers'),
(2,	1,	1,	3,	1,	'Projector required'),
(3,	1,	1,	4,	2,	'HDMI backup'),
(4,	1,	2,	6,	1,	'Overflow portable speaker');

DROP TABLE IF EXISTS `content_channels`;
CREATE TABLE `content_channels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `content_item_id` bigint unsigned NOT NULL,
  `channel` enum('facebook','instagram','telegram','tiktok','youtube','bulletin','av_projection','cm') COLLATE utf8mb4_unicode_ci NOT NULL,
  `publish_at` datetime DEFAULT NULL,
  `display_start_date` date DEFAULT NULL,
  `display_end_date` date DEFAULT NULL,
  `status` enum('na','pending','scheduled','posted','done') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'na',
  `updated_by_user_id` bigint unsigned DEFAULT NULL,
  `updated_by_name_snapshot` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cc_item_channel` (`content_item_id`,`channel`),
  KEY `idx_cc_channel_status` (`channel`,`status`),
  KEY `idx_cc_updated_by` (`updated_by_user_id`,`updated_at`),
  KEY `idx_cc_display_window` (`channel`,`status`,`display_start_date`,`display_end_date`),
  KEY `idx_cc_publish_queue` (`channel`,`status`,`publish_at`),
  CONSTRAINT `fk_cc_item` FOREIGN KEY (`content_item_id`) REFERENCES `content_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cc_updated_by` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_cc_non_social_status` CHECK (((`channel` not in (_utf8mb4'bulletin',_utf8mb4'av_projection',_utf8mb4'cm')) or (`status` in (_utf8mb4'na',_utf8mb4'pending',_utf8mb4'done'))))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DELIMITER ;;

CREATE TRIGGER `bi_content_channels_snapshot` BEFORE INSERT ON `content_channels` FOR EACH ROW
BEGIN
  IF NEW.updated_by_user_id IS NOT NULL THEN
    SET NEW.updated_by_name_snapshot = NULL;
  END IF;
END;;

CREATE TRIGGER `bu_content_channels_snapshot` BEFORE UPDATE ON `content_channels` FOR EACH ROW
BEGIN
  IF NEW.updated_by_user_id IS NOT NULL THEN
    SET NEW.updated_by_name_snapshot = NULL;
  END IF;
END;;

DELIMITER ;

DROP TABLE IF EXISTS `content_items`;
CREATE TABLE `content_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `media_request_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_type` enum('poster','video','story','reel','article','slide','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'poster',
  `language` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `promo_start_date` date DEFAULT NULL,
  `promo_end_date` date DEFAULT NULL,
  `default_publish_at` datetime DEFAULT NULL,
  `default_display_start_date` date DEFAULT NULL,
  `default_display_end_date` date DEFAULT NULL,
  `caption_brief` text COLLATE utf8mb4_unicode_ci,
  `final_caption` text COLLATE utf8mb4_unicode_ci,
  `caption_status` enum('na','pending','done') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'na',
  `asset_url` text COLLATE utf8mb4_unicode_ci,
  `asset_status` enum('na','pending','in_progress','ready','done') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'na',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `do_not_display` tinyint(1) NOT NULL DEFAULT '0',
  `asset_pic_user_id` bigint unsigned DEFAULT NULL,
  `asset_pic_name_snapshot` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `socmed_pic_user_id` bigint unsigned DEFAULT NULL,
  `socmed_pic_name_snapshot` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ci_request` (`media_request_id`),
  KEY `idx_ci_promo` (`promo_start_date`,`promo_end_date`),
  KEY `idx_ci_asset_status` (`asset_status`),
  KEY `idx_ci_caption_status` (`caption_status`),
  KEY `idx_ci_hide` (`do_not_display`,`created_at`),
  KEY `fk_ci_asset_pic_user` (`asset_pic_user_id`),
  KEY `fk_ci_socmed_pic_user` (`socmed_pic_user_id`),
  KEY `idx_ci_default_publish` (`default_publish_at`),
  KEY `idx_ci_default_display_window` (`default_display_start_date`,`default_display_end_date`),
  CONSTRAINT `fk_ci_asset_pic_user` FOREIGN KEY (`asset_pic_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ci_request` FOREIGN KEY (`media_request_id`) REFERENCES `media_requests` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ci_socmed_pic_user` FOREIGN KEY (`socmed_pic_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `content_notes`;
CREATE TABLE `content_notes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `content_item_id` bigint unsigned NOT NULL,
  `actor_user_id` bigint unsigned DEFAULT NULL,
  `actor_name_snapshot` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cn_item_date` (`content_item_id`,`created_at`),
  KEY `idx_cn_actor_date` (`actor_user_id`,`created_at`),
  CONSTRAINT `fk_cn_actor` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cn_item` FOREIGN KEY (`content_item_id`) REFERENCES `content_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DELIMITER ;;

CREATE TRIGGER `bi_content_notes_snapshot` BEFORE INSERT ON `content_notes` FOR EACH ROW
BEGIN
  IF NEW.actor_user_id IS NOT NULL THEN
    SET NEW.actor_name_snapshot = NULL;
  END IF;
END;;

CREATE TRIGGER `bu_content_notes_snapshot` BEFORE UPDATE ON `content_notes` FOR EACH ROW
BEGIN
  IF NEW.actor_user_id IS NOT NULL THEN
    SET NEW.actor_name_snapshot = NULL;
  END IF;
END;;

DELIMITER ;

DROP TABLE IF EXISTS `equipment`;
CREATE TABLE `equipment` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_equipment_name` (`name`),
  KEY `idx_equipment_active` (`is_active`),
  KEY `idx_equipment_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `equipment` (`id`, `name`, `category`, `is_active`, `created_at`, `updated_at`) VALUES
(1,	'Projector (HDMI)',	'Video',	1,	'2026-01-31 23:14:18',	'2026-01-31 23:14:18'),
(2,	'TV (HDMI)',	'Video',	1,	'2026-01-31 23:14:18',	'2026-01-31 23:14:18'),
(3,	'Audio Input (3.5mm)',	'Audio',	1,	'2026-01-31 23:14:18',	'2026-01-31 23:14:18'),
(4,	'Audio Input (HDMI/XLR)',	'Audio',	1,	'2026-01-31 23:14:18',	'2026-01-31 23:14:18'),
(5,	'Audio Input (Bluetooth)',	'Audio',	1,	'2026-01-31 23:14:18',	'2026-01-31 23:14:18'),
(6,	'Audio Input (XLR)',	'Audio',	1,	'2026-01-31 23:14:18',	'2026-01-31 23:14:18'),
(7,	'Audio Input (HDMI/Bluetooth)',	'Audio',	1,	'2026-01-31 23:14:18',	'2026-01-31 23:14:18'),
(8,	'Wireless Microphone',	'Audio',	1,	'2026-01-31 23:14:18',	'2026-01-31 23:14:18'),
(9,	'Wired Microphone',	'Audio',	1,	'2026-01-31 23:14:18',	'2026-01-31 23:14:18'),
(10,	'Mic Stand',	'Accessories',	1,	'2026-01-31 23:14:18',	'2026-01-31 23:14:18'),
(11,	'Music Stand',	'Accessories',	1,	'2026-01-31 23:14:18',	'2026-01-31 23:14:18'),
(12,	'DI Box (Behringer Ultra-DI DI100)',	'Audio',	1,	'2026-01-31 23:14:18',	'2026-01-31 23:14:18'),
(13,	'Analog Mixer',	'Audio',	1,	'2026-01-31 23:14:18',	'2026-01-31 23:14:18');

DROP TABLE IF EXISTS `event_occurrences`;
CREATE TABLE `event_occurrences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_schedule_id` bigint unsigned NOT NULL,
  `occurrence_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_occurrence_date` (`occurrence_date`),
  KEY `idx_occurrence_schedule` (`event_schedule_id`),
  KEY `idx_occurrence_date_schedule` (`occurrence_date`,`event_schedule_id`),
  KEY `idx_occurrence_schedule_date` (`event_schedule_id`,`occurrence_date`),
  CONSTRAINT `fk_event_occurrences_schedule` FOREIGN KEY (`event_schedule_id`) REFERENCES `event_schedules` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `event_schedules`;
CREATE TABLE `event_schedules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `media_request_id` bigint unsigned NOT NULL,
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
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_schedule_request` (`media_request_id`),
  KEY `idx_schedule_start` (`start_date`),
  CONSTRAINT `fk_event_schedules_request` FOREIGN KEY (`media_request_id`) REFERENCES `media_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `event_schedules` (`id`, `media_request_id`, `schedule_type`, `start_date`, `end_date`, `start_time`, `end_time`, `recurrence_pattern`, `recurrence_days_of_week`, `recurrence_interval`, `notes`, `created_at`, `updated_at`) VALUES
(1,	1,	'single',	'2026-01-31',	'2026-01-31',	'19:30:00',	'22:00:00',	NULL,	NULL,	NULL,	'Setup starts 18:30',	'2026-01-10 23:56:43',	'2026-01-10 23:56:43'),
(2,	2,	'single',	'2026-01-17',	'2026-01-18',	NULL,	NULL,	NULL,	NULL,	NULL,	'Event time TBD',	'2026-01-10 23:56:43',	'2026-01-10 23:56:43'),
(3,	3,	'recurring',	'2026-02-01',	'2026-05-31',	'10:00:00',	'12:00:00',	'weekly',	'Sat',	1,	'Weekly sessions',	'2026-01-10 23:56:43',	'2026-01-10 23:56:43');

DROP TABLE IF EXISTS `internal_notes`;
CREATE TABLE `internal_notes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `media_request_id` bigint unsigned NOT NULL,
  `actor_name_snapshot` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actor_user_id` bigint unsigned DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notes_request_date` (`media_request_id`,`created_at`),
  KEY `fk_internal_notes_user` (`actor_user_id`),
  CONSTRAINT `fk_internal_notes_request` FOREIGN KEY (`media_request_id`) REFERENCES `media_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_internal_notes_user` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `internal_notes` (`id`, `media_request_id`, `actor_name_snapshot`, `actor_user_id`, `note`, `created_at`) VALUES
(1,	1,	'Media Head',	3,	'Pending approval. Waiting confirmation on QR signup link.',	'2026-01-10 23:57:33'),
(2,	1,	'Jason Tan',	NULL,	'QR link confirmed: https://example.com/signup (replace later)',	'2026-01-10 23:57:33'),
(3,	2,	'Office Admin',	2,	'Late request, flagged for urgent review.',	'2026-01-10 23:57:33');

DROP TABLE IF EXISTS `media_details`;
CREATE TABLE `media_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `media_request_id` bigint unsigned NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `promo_start_date` date DEFAULT NULL,
  `promo_end_date` date DEFAULT NULL,
  `caption_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_media_details_request` (`media_request_id`),
  CONSTRAINT `fk_media_details_request` FOREIGN KEY (`media_request_id`) REFERENCES `media_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `media_details` (`id`, `media_request_id`, `description`, `promo_start_date`, `promo_end_date`, `caption_details`, `note`, `created_at`, `updated_at`) VALUES
(1,	1,	'Need poster + IG story for youth gathering. Theme: “Come and See”.',	'2026-01-18',	'2026-01-31',	'Short caption, friendly tone, include date/time/location.',	'Include QR for signup if possible.',	'2026-01-10 23:57:07',	'2026-01-10 23:57:07'),
(2,	2,	'Need simple poster for recruitment weekend, clean and formal.',	'2026-01-10',	'2026-01-18',	'Caption: recruitment details + contact person.',	'No video required.',	'2026-01-10 23:57:07',	'2026-01-10 23:57:07'),
(3,	3,	'Need weekly reminder visual, same template reused every week.',	'2026-02-01',	'2026-05-31',	'Caption can be reused, change date weekly.',	'Prefer minimal text on poster.',	'2026-01-10 23:57:07',	'2026-01-10 23:57:07');

DROP TABLE IF EXISTS `media_platforms`;
CREATE TABLE `media_platforms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `media_request_id` bigint unsigned NOT NULL,
  `platform` enum('facebook','instagram','telegram','tiktok','youtube','whatsapp','website','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `platform_other_label` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_media_platform` (`media_request_id`,`platform`),
  CONSTRAINT `fk_media_platforms_request` FOREIGN KEY (`media_request_id`) REFERENCES `media_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `media_platforms` (`id`, `media_request_id`, `platform`, `platform_other_label`) VALUES
(1,	1,	'instagram',	NULL),
(2,	1,	'facebook',	NULL),
(3,	1,	'telegram',	NULL),
(4,	2,	'facebook',	NULL),
(5,	2,	'instagram',	NULL),
(6,	3,	'telegram',	NULL),
(7,	3,	'whatsapp',	NULL);

DROP TABLE IF EXISTS `media_requests`;
CREATE TABLE `media_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reference_no` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `requestor_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ministry` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `has_required_approvals` tinyint(1) NOT NULL DEFAULT '0',
  `event_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `event_location_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `reference_url` text COLLATE utf8mb4_unicode_ci,
  `reference_note` text COLLATE utf8mb4_unicode_ci,
  `request_status` enum('pending','approved','rejected','in_progress','completed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `is_late` tinyint(1) NOT NULL DEFAULT '0',
  `lead_days` int DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_requests_reference` (`reference_no`),
  KEY `idx_requests_status_date` (`request_status`,`submitted_at`),
  KEY `idx_requests_late_date` (`is_late`,`submitted_at`),
  KEY `idx_requests_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `media_requests` (`id`, `reference_no`, `requestor_name`, `ministry`, `contact_no`, `email`, `has_required_approvals`, `event_name`, `event_description`, `event_location_note`, `reference_url`, `reference_note`, `request_status`, `is_late`, `lead_days`, `submitted_at`, `created_at`, `updated_at`) VALUES
(1,	'MR-2026-0001',	'Jason Tan',	'Youth Ministry',	'012-3456789',	'jason.tan@email.com',	1,	'Youth Gathering Night',	'Worship + sharing session',	'Main Hall + overflow area',	NULL,	NULL,	'pending',	0,	21,	'2026-01-10 10:00:00',	'2026-01-10 23:56:30',	'2026-01-10 23:56:30'),
(2,	'MR-2026-0002',	'Melissa Lee',	'Choir',	'013-8889999',	'melissa.lee@email.com',	1,	'Choir Recruitment Weekend',	'Recruitment booth and announcements',	'Chapel lobby',	NULL,	NULL,	'pending',	1,	7,	'2026-01-10 10:05:00',	'2026-01-10 23:56:30',	'2026-01-10 23:56:30'),
(3,	'MR-2026-0003',	'Andrew Lim',	'Catechism',	'014-2223333',	'andrew.lim@email.com',	1,	'Weekly Catechism Class',	'Weekly class session announcements',	'Meeting Room 1',	NULL,	NULL,	'pending',	0,	30,	'2026-01-10 10:10:00',	'2026-01-10 23:56:30',	'2026-01-10 23:56:30');

DROP TABLE IF EXISTS `photo_details`;
CREATE TABLE `photo_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `media_request_id` bigint unsigned NOT NULL,
  `needed_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `photo_internal_status` enum('na','pending','assigned','done') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'na',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_photo_details_request` (`media_request_id`),
  CONSTRAINT `fk_photo_details_request` FOREIGN KEY (`media_request_id`) REFERENCES `media_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `photo_details` (`id`, `media_request_id`, `needed_date`, `start_time`, `end_time`, `note`, `photo_internal_status`, `created_at`, `updated_at`) VALUES
(1,	1,	'2026-01-31',	'19:00:00',	'22:00:00',	'Need 20-30 edited photos + group photo.',	'pending',	'2026-01-10 23:57:20',	'2026-01-10 23:57:20');

DROP TABLE IF EXISTS `request_rooms`;
CREATE TABLE `request_rooms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `media_request_id` bigint unsigned NOT NULL,
  `room_id` bigint unsigned NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_request_room` (`media_request_id`,`room_id`),
  KEY `idx_request_rooms_room` (`room_id`),
  CONSTRAINT `fk_request_rooms_request` FOREIGN KEY (`media_request_id`) REFERENCES `media_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_request_rooms_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `request_rooms` (`id`, `media_request_id`, `room_id`, `notes`) VALUES
(1,	1,	1,	'Main event space'),
(2,	1,	2,	'Overflow area if needed'),
(3,	2,	2,	'Recruitment booth nearby'),
(4,	3,	3,	'Classroom setup');

DROP TABLE IF EXISTS `request_types`;
CREATE TABLE `request_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `media_request_id` bigint unsigned NOT NULL,
  `type` enum('av','media','photo') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `approval_status` enum('pending','approved','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approved_by_user_id` bigint unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_request_type` (`media_request_id`,`type`),
  KEY `idx_type_status` (`type`,`approval_status`),
  KEY `fk_request_types_approver` (`approved_by_user_id`),
  CONSTRAINT `fk_request_types_approver` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_request_types_request` FOREIGN KEY (`media_request_id`) REFERENCES `media_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `request_types` (`id`, `media_request_id`, `type`, `approval_status`, `approved_by_user_id`, `approved_at`, `rejected_reason`, `created_at`, `updated_at`) VALUES
(1,	1,	'av',	'pending',	NULL,	NULL,	NULL,	'2026-01-10 23:56:37',	'2026-01-10 23:56:37'),
(2,	1,	'media',	'pending',	NULL,	NULL,	NULL,	'2026-01-10 23:56:37',	'2026-01-10 23:56:37'),
(3,	1,	'photo',	'pending',	NULL,	NULL,	NULL,	'2026-01-10 23:56:37',	'2026-01-10 23:56:37'),
(4,	2,	'media',	'pending',	NULL,	NULL,	NULL,	'2026-01-10 23:56:37',	'2026-01-10 23:56:37'),
(5,	3,	'media',	'pending',	NULL,	NULL,	NULL,	'2026-01-10 23:56:37',	'2026-01-10 23:56:37');

DROP TABLE IF EXISTS `room_blackouts`;
CREATE TABLE `room_blackouts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `room_id` bigint unsigned NOT NULL,
  `start_at` datetime NOT NULL,
  `end_at` datetime NOT NULL,
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by_user_id` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_blackouts_room` (`room_id`),
  KEY `idx_blackouts_range` (`start_at`,`end_at`),
  KEY `fk_blackouts_user` (`created_by_user_id`),
  CONSTRAINT `fk_blackouts_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_blackouts_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `room_blackouts` (`id`, `room_id`, `start_at`, `end_at`, `reason`, `created_by_user_id`, `created_at`) VALUES
(1,	4,	'2026-01-10 00:00:00',	'2026-02-10 23:59:59',	'Meeting Room 2 renovation',	1,	'2026-01-10 23:56:22');

DROP TABLE IF EXISTS `room_equipment`;
CREATE TABLE `room_equipment` (
  `room_id` bigint unsigned NOT NULL,
  `equipment_id` bigint unsigned NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`room_id`,`equipment_id`),
  KEY `fk_room_equipment_equipment` (`equipment_id`),
  CONSTRAINT `fk_room_equipment_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_room_equipment_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `room_equipment` (`room_id`, `equipment_id`, `quantity`, `notes`) VALUES
(1,	1,	1,	NULL),
(1,	3,	1,	NULL),
(1,	8,	4,	NULL),
(1,	9,	5,	NULL),
(1,	10,	4,	NULL),
(1,	11,	2,	NULL),
(1,	12,	4,	NULL),
(1,	13,	1,	NULL),
(2,	1,	1,	NULL),
(2,	4,	1,	NULL),
(2,	8,	2,	NULL),
(3,	1,	1,	NULL),
(3,	4,	1,	NULL),
(3,	8,	2,	NULL),
(4,	1,	1,	NULL),
(4,	5,	1,	NULL),
(4,	8,	2,	NULL),
(5,	1,	1,	NULL),
(5,	6,	1,	NULL),
(5,	8,	2,	NULL),
(5,	13,	1,	NULL),
(6,	2,	1,	NULL),
(6,	7,	1,	NULL),
(6,	8,	2,	NULL);

DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rooms_name` (`name`),
  KEY `idx_rooms_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `rooms` (`id`, `name`, `is_active`, `notes`, `created_at`, `updated_at`) VALUES
(1,	'St. Faustina Hall',	1,	'Projector HDMI, audio 3.5mm, 4 wireless mics, 5 wired mics, stands, DI boxes, mixer',	'2026-01-31 23:14:18',	'2026-01-31 23:14:18'),
(2,	'St. John Vianney Room',	1,	'Projector HDMI, audio HDMI/XLR, 2 wireless mics',	'2026-01-31 23:14:18',	'2026-01-31 23:14:18'),
(3,	'St. Philip Minh Room',	1,	'Projector HDMI, audio HDMI/XLR, 2 wireless mics',	'2026-01-31 23:14:18',	'2026-01-31 23:14:18'),
(4,	'St. Joseph Room',	1,	'Projector HDMI, audio Bluetooth, 2 wireless mics',	'2026-01-31 23:14:18',	'2026-01-31 23:14:18'),
(5,	'Sacred Heart Room',	1,	'Projector HDMI, audio XLR, 2 wireless mics, analog mixer',	'2026-01-31 23:14:18',	'2026-01-31 23:14:18'),
(6,	'Cafeteria',	1,	'TV HDMI, audio HDMI/Bluetooth, 2 wireless mics',	'2026-01-31 23:14:18',	'2026-01-31 23:14:18');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('office_admin','media_head','media_asst','media_member','designer_head','designer_asst','designer_member','av_head','av_asst','av_member','sysadmin') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'media_member',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `is_active`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1,	'System Admin',	'sysadmin@church.my',	'__BCRYPT_HASH__',	'sysadmin',	1,	NULL,	'2026-01-10 23:55:54',	'2026-01-10 23:55:54'),
(2,	'Office Admin',	'office.admin@church.my',	'__BCRYPT_HASH__',	'office_admin',	1,	NULL,	'2026-01-10 23:55:54',	'2026-01-10 23:55:54'),
(3,	'Media Head',	'media.head@church.my',	'__BCRYPT_HASH__',	'media_head',	1,	NULL,	'2026-01-10 23:55:54',	'2026-01-10 23:55:54'),
(4,	'Media Asst Head',	'media.asst@church.my',	'__BCRYPT_HASH__',	'media_asst',	1,	NULL,	'2026-01-10 23:55:54',	'2026-01-10 23:55:54'),
(5,	'Media Member A',	'media.member1@church.my',	'__BCRYPT_HASH__',	'media_member',	1,	NULL,	'2026-01-10 23:55:54',	'2026-01-10 23:55:54'),
(6,	'Designer Head',	'designer.head@church.my',	'__BCRYPT_HASH__',	'designer_head',	1,	NULL,	'2026-01-10 23:55:54',	'2026-01-10 23:55:54'),
(7,	'Designer Asst Head',	'designer.asst@church.my',	'__BCRYPT_HASH__',	'designer_asst',	1,	NULL,	'2026-01-10 23:55:54',	'2026-01-10 23:55:54'),
(8,	'Designer Member A',	'designer.member1@church.my',	'__BCRYPT_HASH__',	'designer_member',	1,	NULL,	'2026-01-10 23:55:54',	'2026-01-10 23:55:54'),
(9,	'AV Head',	'av.head@church.my',	'__BCRYPT_HASH__',	'av_head',	1,	NULL,	'2026-01-10 23:55:54',	'2026-01-10 23:55:54'),
(10,	'AV Asst Head',	'av.asst@church.my',	'__BCRYPT_HASH__',	'av_asst',	1,	NULL,	'2026-01-10 23:55:54',	'2026-01-10 23:55:54'),
(11,	'AV Member A',	'av.member1@church.my',	'__BCRYPT_HASH__',	'av_member',	1,	NULL,	'2026-01-10 23:55:54',	'2026-01-10 23:55:54');

-- 2026-01-31 15:16:28 UTC
