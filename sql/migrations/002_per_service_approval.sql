-- Migration 002: Per-service approval enhancements
-- Expands the per-service approval workflow with additional statuses,
-- PIC assignment, decision notes, and coordinator assignment.

-- 1. Expand approval_status enum on request_types
ALTER TABLE request_types
  MODIFY COLUMN approval_status
    ENUM('pending','approved','rejected','needs_more_info','in_progress','completed')
    NOT NULL DEFAULT 'pending';

-- 2. Add decision_note for any decision context (approval note, info request question, etc.)
ALTER TABLE request_types
  ADD COLUMN decision_note TEXT AFTER rejected_reason;

-- 3. Add PIC assignment per service
ALTER TABLE request_types
  ADD COLUMN assigned_pic_user_id BIGINT UNSIGNED NULL AFTER decision_note,
  ADD KEY idx_request_types_pic (assigned_pic_user_id),
  ADD CONSTRAINT fk_request_types_pic
    FOREIGN KEY (assigned_pic_user_id) REFERENCES users(id) ON DELETE SET NULL;

-- 4. Add coordinator assignment on main request
ALTER TABLE media_requests
  ADD COLUMN assigned_coordinator_user_id BIGINT UNSIGNED NULL AFTER rejection_reason,
  ADD KEY idx_media_requests_coordinator (assigned_coordinator_user_id),
  ADD CONSTRAINT fk_media_requests_coordinator
    FOREIGN KEY (assigned_coordinator_user_id) REFERENCES users(id) ON DELETE SET NULL;
