-- Migration 007: Bulk import users
-- Default password: Changeme1 (all users must change on first login)
-- Edit the INSERT rows below to match your actual users.

INSERT INTO users (name, email, password_hash, must_change_password, role, is_active, created_at, updated_at) VALUES
-- Sysadmin
-- ('Admin Name',        'admin@church.my',        '$2y$10$kFTsYxgYLXq.libhlsPJDO2vjycw16tu4AFZM13v5DujfwpFV0RIC', 1, 'sysadmin',        1, NOW(), NOW()),

-- Media Ministry
-- ('Media Head Name',   'media.head@church.my',   '$2y$10$kFTsYxgYLXq.libhlsPJDO2vjycw16tu4AFZM13v5DujfwpFV0RIC', 1, 'media_head',      1, NOW(), NOW()),
-- ('Media Asst Name',   'media.asst@church.my',   '$2y$10$kFTsYxgYLXq.libhlsPJDO2vjycw16tu4AFZM13v5DujfwpFV0RIC', 1, 'media_asst',      1, NOW(), NOW()),
-- ('Media Member 1',    'media.m1@church.my',     '$2y$10$kFTsYxgYLXq.libhlsPJDO2vjycw16tu4AFZM13v5DujfwpFV0RIC', 1, 'media_member',    1, NOW(), NOW()),

-- Design Team
-- ('Designer Head',     'design.head@church.my',  '$2y$10$kFTsYxgYLXq.libhlsPJDO2vjycw16tu4AFZM13v5DujfwpFV0RIC', 1, 'designer_head',   1, NOW(), NOW()),
-- ('Designer Asst',     'design.asst@church.my',  '$2y$10$kFTsYxgYLXq.libhlsPJDO2vjycw16tu4AFZM13v5DujfwpFV0RIC', 1, 'designer_asst',   1, NOW(), NOW()),
-- ('Designer Member 1', 'design.m1@church.my',    '$2y$10$kFTsYxgYLXq.libhlsPJDO2vjycw16tu4AFZM13v5DujfwpFV0RIC', 1, 'designer_member', 1, NOW(), NOW()),

-- AV Team
-- ('AV Head',           'av.head@church.my',      '$2y$10$kFTsYxgYLXq.libhlsPJDO2vjycw16tu4AFZM13v5DujfwpFV0RIC', 1, 'av_head',         1, NOW(), NOW()),
-- ('AV Asst',           'av.asst@church.my',      '$2y$10$kFTsYxgYLXq.libhlsPJDO2vjycw16tu4AFZM13v5DujfwpFV0RIC', 1, 'av_asst',         1, NOW(), NOW()),
-- ('AV Member 1',       'av.m1@church.my',        '$2y$10$kFTsYxgYLXq.libhlsPJDO2vjycw16tu4AFZM13v5DujfwpFV0RIC', 1, 'av_member',       1, NOW(), NOW()),

-- Photography
-- ('Photo Lead',        'photo.lead@church.my',   '$2y$10$kFTsYxgYLXq.libhlsPJDO2vjycw16tu4AFZM13v5DujfwpFV0RIC', 1, 'photo_lead',      1, NOW(), NOW()),
-- ('Photo Member 1',    'photo.m1@church.my',     '$2y$10$kFTsYxgYLXq.libhlsPJDO2vjycw16tu4AFZM13v5DujfwpFV0RIC', 1, 'photo_member',    1, NOW(), NOW()),

-- =============================================================================
-- HOW TO USE:
-- 1. Uncomment the rows you need (remove the leading --)
-- 2. Replace names and emails with actual user details
-- 3. Copy/paste rows to add more users of the same role
-- 4. Run in Adminer or phpMyAdmin
-- 5. All users will be forced to set a new password on first login
--
-- Available roles:
--   sysadmin, media_head, media_asst, media_member,
--   designer_head, designer_asst, designer_member,
--   av_head, av_asst, av_member,
--   photo_lead, photo_member
--
-- Default password: Changeme1
-- =============================================================================
