-- ============================================
-- Seed development users with bcrypt passwords
-- All users: password = "password123"
-- Run this on your dev database after schema init
-- ============================================

-- Clear existing users (dev only!)
DELETE FROM users;

-- Reset auto-increment
ALTER TABLE users AUTO_INCREMENT = 1;

-- Insert users (password: password123)
-- Hash generated via: password_hash('password123', PASSWORD_BCRYPT)
INSERT INTO users (name, email, password_hash, role, is_active, created_at, updated_at) VALUES
  ('System Admin',             'sysadmin@church.my',      '$2y$10$hnzdWgwQDrOWGCzH6rRLLOODgFtrutFOevaHmI711SiT0R3Fbl8w2', 'sysadmin',        1, NOW(), NOW()),
  ('Media Ministry Head',      'media.head@church.my',    '$2y$10$hnzdWgwQDrOWGCzH6rRLLOODgFtrutFOevaHmI711SiT0R3Fbl8w2', 'media_head',      1, NOW(), NOW()),
  ('Media Ministry Asst Head', 'media.asst@church.my',    '$2y$10$hnzdWgwQDrOWGCzH6rRLLOODgFtrutFOevaHmI711SiT0R3Fbl8w2', 'media_asst',      1, NOW(), NOW()),
  ('Designer Head',            'designer.head@church.my', '$2y$10$hnzdWgwQDrOWGCzH6rRLLOODgFtrutFOevaHmI711SiT0R3Fbl8w2', 'designer_head',   1, NOW(), NOW()),
  ('Designer Asst Head',       'designer.asst@church.my', '$2y$10$hnzdWgwQDrOWGCzH6rRLLOODgFtrutFOevaHmI711SiT0R3Fbl8w2', 'designer_asst',   1, NOW(), NOW()),
  ('AV Head',                  'av.head@church.my',       '$2y$10$hnzdWgwQDrOWGCzH6rRLLOODgFtrutFOevaHmI711SiT0R3Fbl8w2', 'av_head',         1, NOW(), NOW()),
  ('AV Asst Head',             'av.asst@church.my',       '$2y$10$hnzdWgwQDrOWGCzH6rRLLOODgFtrutFOevaHmI711SiT0R3Fbl8w2', 'av_asst',         1, NOW(), NOW()),
  ('Photography Lead',         'photo.lead@church.my',    '$2y$10$hnzdWgwQDrOWGCzH6rRLLOODgFtrutFOevaHmI711SiT0R3Fbl8w2', 'photo_lead',      1, NOW(), NOW());
