-- Migration 006: Create app_settings table for maintenance mode and future settings

CREATE TABLE IF NOT EXISTS app_settings (
  setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
  setting_value TEXT DEFAULT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  updated_by_user_id BIGINT UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default maintenance settings
INSERT INTO app_settings (setting_key, setting_value) VALUES
  ('maintenance_public', '0'),
  ('maintenance_admin', '0'),
  ('maintenance_message', 'We are currently performing scheduled maintenance. Please check back shortly.');
