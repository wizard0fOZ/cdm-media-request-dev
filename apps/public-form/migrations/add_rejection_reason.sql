-- Migration: Add rejection_reason column to media_requests table
-- Run this SQL in your database to add support for storing rejection reasons

ALTER TABLE media_requests
ADD COLUMN rejection_reason TEXT NULL AFTER request_status;

-- Verify the column was added
-- SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
-- FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_NAME = 'media_requests' AND COLUMN_NAME = 'rejection_reason';
