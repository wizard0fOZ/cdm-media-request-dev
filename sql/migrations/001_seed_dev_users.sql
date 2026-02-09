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
  ('Osmund Raj',       'sysadmin@church.my',      '$2y$10$hnzdWgwQDrOWGCzH6rRLLOODgFtrutFOevaHmI711SiT0R3Fbl8w2', 'sysadmin',        1, NOW(), NOW()),
  ('Daniel Lim',       'media.head@church.my',    '$2y$10$hnzdWgwQDrOWGCzH6rRLLOODgFtrutFOevaHmI711SiT0R3Fbl8w2', 'media_head',      1, NOW(), NOW()),
  ('Sarah Tan',        'media.asst@church.my',    '$2y$10$hnzdWgwQDrOWGCzH6rRLLOODgFtrutFOevaHmI711SiT0R3Fbl8w2', 'media_asst',      1, NOW(), NOW()),
  ('Rachel Wong',      'designer.head@church.my', '$2y$10$hnzdWgwQDrOWGCzH6rRLLOODgFtrutFOevaHmI711SiT0R3Fbl8w2', 'designer_head',   1, NOW(), NOW()),
  ('Marcus Lee',       'designer.asst@church.my', '$2y$10$hnzdWgwQDrOWGCzH6rRLLOODgFtrutFOevaHmI711SiT0R3Fbl8w2', 'designer_asst',   1, NOW(), NOW()),
  ('Joshua Ng',        'av.head@church.my',       '$2y$10$hnzdWgwQDrOWGCzH6rRLLOODgFtrutFOevaHmI711SiT0R3Fbl8w2', 'av_head',         1, NOW(), NOW()),
  ('Timothy Chen',     'av.asst@church.my',       '$2y$10$hnzdWgwQDrOWGCzH6rRLLOODgFtrutFOevaHmI711SiT0R3Fbl8w2', 'av_asst',         1, NOW(), NOW()),
  ('Grace Fernandez',  'photo.lead@church.my',    '$2y$10$hnzdWgwQDrOWGCzH6rRLLOODgFtrutFOevaHmI711SiT0R3Fbl8w2', 'photo_lead',      1, NOW(), NOW());
