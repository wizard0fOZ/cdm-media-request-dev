-- CDM Media Request System - Production Database
-- Database: divineme_media_request

SET NAMES utf8mb4;
SET time_zone = '+08:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

-- ============================================
-- USERS TABLE (create admin manually after import)
-- ============================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('sysadmin','media_head','media_asst','designer_head','designer_asst','av_head','av_asst','photo_lead') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ROOMS (Reference Data - KEEP)
-- ============================================
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

INSERT INTO `rooms` (`id`, `name`, `is_active`, `notes`) VALUES
(1, 'St. Faustina Hall', 1, 'Projector HDMI, audio 3.5mm, 4 wireless mics, 5 wired mics, stands, DI boxes, mixer'),
(2, 'St. John Vianney Room', 1, 'Projector HDMI, audio HDMI/XLR, 2 wireless mics'),
(3, 'St. Philip Minh Room', 1, 'Projector HDMI, audio HDMI/XLR, 2 wireless mics'),
(4, 'St. Joseph Room', 1, 'Projector HDMI, audio Bluetooth, 2 wireless mics'),
(5, 'Sacred Heart Room', 1, 'Projector HDMI, audio XLR, 2 wireless mics, analog mixer'),
(6, 'Cafeteria', 1, 'TV HDMI, audio HDMI/Bluetooth, 2 wireless mics');

-- ============================================
-- EQUIPMENT (Reference Data - KEEP)
-- ============================================
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

INSERT INTO `equipment` (`id`, `name`, `category`, `is_active`) VALUES
(1, 'Projector (HDMI)', 'Video', 1),
(2, 'TV (HDMI)', 'Video', 1),
(3, 'Audio Input (3.5mm)', 'Audio', 1),
(4, 'Audio Input (HDMI/XLR)', 'Audio', 1),
(5, 'Audio Input (Bluetooth)', 'Audio', 1),
(6, 'Audio Input (XLR)', 'Audio', 1),
(7, 'Audio Input (HDMI/Bluetooth)', 'Audio', 1),
(8, 'Wireless Microphone', 'Audio', 1),
(9, 'Wired Microphone', 'Audio', 1),
(10, 'Mic Stand', 'Accessories', 1),
(11, 'Music Stand', 'Accessories', 1),
(12, 'DI Box (Behringer Ultra-DI DI100)', 'Audio', 1),
(13, 'Analog Mixer', 'Audio', 1);

-- ============================================
-- ROOM EQUIPMENT (Reference Data - KEEP)
-- ============================================
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

INSERT INTO `room_equipment` (`room_id`, `equipment_id`, `quantity`) VALUES
(1, 1, 1), (1, 3, 1), (1, 8, 4), (1, 9, 5), (1, 10, 4), (1, 11, 2), (1, 12, 4), (1, 13, 1),
(2, 1, 1), (2, 4, 1), (2, 8, 2),
(3, 1, 1), (3, 4, 1), (3, 8, 2),
(4, 1, 1), (4, 5, 1), (4, 8, 2),
(5, 1, 1), (5, 6, 1), (5, 8, 2), (5, 13, 1),
(6, 2, 1), (6, 7, 1), (6, 8, 2);

-- ============================================
-- MEDIA REQUESTS (Main table - with rejection_reason column)
-- ============================================
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
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
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

-- ============================================
-- REQUEST TYPES
-- ============================================
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

-- ============================================
-- EVENT SCHEDULES
-- ============================================
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

-- ============================================
-- EVENT OCCURRENCES
-- ============================================
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
  CONSTRAINT `fk_event_occurrences_schedule` FOREIGN KEY (`event_schedule_id`) REFERENCES `event_schedules` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- REQUEST ROOMS
-- ============================================
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

-- ============================================
-- AV DETAILS
-- ============================================
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

-- ============================================
-- AV ITEMS
-- ============================================
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

-- ============================================
-- MEDIA DETAILS
-- ============================================
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

-- ============================================
-- MEDIA PLATFORMS
-- ============================================
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

-- ============================================
-- PHOTO DETAILS
-- ============================================
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

-- ============================================
-- ROOM BLACKOUTS
-- ============================================
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

-- ============================================
-- INTERNAL NOTES
-- ============================================
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

-- ============================================
-- AUDIT LOGS
-- ============================================
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

-- ============================================
-- CONTENT ITEMS (for future use)
-- ============================================
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
  KEY `fk_ci_asset_pic_user` (`asset_pic_user_id`),
  KEY `fk_ci_socmed_pic_user` (`socmed_pic_user_id`),
  CONSTRAINT `fk_ci_asset_pic_user` FOREIGN KEY (`asset_pic_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ci_request` FOREIGN KEY (`media_request_id`) REFERENCES `media_requests` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ci_socmed_pic_user` FOREIGN KEY (`socmed_pic_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- CONTENT CHANNELS (for future use)
-- ============================================
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
  CONSTRAINT `fk_cc_item` FOREIGN KEY (`content_item_id`) REFERENCES `content_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cc_updated_by` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- CONTENT NOTES (for future use)
-- ============================================
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

SET foreign_key_checks = 1;

-- ============================================
-- DONE!
-- Next: Create your admin user in phpMyAdmin
-- ============================================
