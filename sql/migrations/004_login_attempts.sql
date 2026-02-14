-- Migration 004: Create login_attempts table for rate limiting
-- Run this on both dev and production databases

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip_address VARCHAR(45) NOT NULL,
  email VARCHAR(255) DEFAULT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
