-- Migration 003: Role restructure (11 roles → 8 roles)
-- Removes: office_admin, media_member, designer_member, av_member
-- Adds: photo_lead
-- Run order: migrate data FIRST (while old enum values still valid), then ALTER enum

-- Step 1: Migrate office_admin → media_head (closest equivalent)
UPDATE users SET role = 'media_head', updated_at = NOW() WHERE role = 'office_admin';

-- Step 2: Delete member-level users (no FK references)
DELETE FROM users WHERE role IN ('media_member', 'designer_member', 'av_member');

-- Step 3: Alter enum to new 8-role set
ALTER TABLE users MODIFY COLUMN role ENUM(
  'sysadmin',
  'media_head', 'media_asst',
  'designer_head', 'designer_asst',
  'av_head', 'av_asst',
  'photo_lead'
) NOT NULL;

-- Step 4: Seed a Photography Lead user for dev
INSERT INTO users (name, email, password_hash, role, is_active, created_at, updated_at)
VALUES (
  'Photo Lead',
  'photo.lead@church.my',
  '$2y$10$kW8pQ5WzqZxB6K8eNhO5muYsXGMvZfN0yLv8J1RZ9bHQnKqYdW4QC',
  'photo_lead',
  1,
  NOW(),
  NOW()
);
