-- Migration 005: Add must_change_password flag to users table
-- New users and password-reset users must change password on first login.

ALTER TABLE users
  ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0
  AFTER password_hash;
