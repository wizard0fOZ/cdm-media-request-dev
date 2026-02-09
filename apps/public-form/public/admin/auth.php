<?php
declare(strict_types=1);

session_start();

/**
 * Require the user to be authenticated.
 * Redirects to login.php if not logged in.
 */
function require_admin_auth(): void {
  if (!is_authenticated()) {
    header('Location: login.php');
    exit;
  }
}

/**
 * Check if the current session has an authenticated user.
 */
function is_authenticated(): bool {
  return !empty($_SESSION['user_id']);
}

/**
 * Get the current logged-in user as an associative array.
 * Returns null if not authenticated.
 */
function current_user(): ?array {
  if (!is_authenticated()) {
    return null;
  }
  return [
    'id'   => $_SESSION['user_id'],
    'name' => $_SESSION['user_name'] ?? '',
    'email'=> $_SESSION['user_email'] ?? '',
    'role' => $_SESSION['user_role'] ?? '',
  ];
}

/**
 * Require the current user to have one of the given roles.
 * Sends 403 and exits if the user's role is not in the list.
 */
function require_role(array $roles): void {
  $user = current_user();
  if (!$user || !in_array($user['role'], $roles, true)) {
    http_response_code(403);
    echo 'Access denied.';
    exit;
  }
}

// ---------------------------------------------------------------------------
// Role → capability mapping
// ---------------------------------------------------------------------------

/** Roles with full system access. */
define('ROLES_FULL_ACCESS', ['sysadmin']);

/** Roles that can approve/reject any service and assign any PIC. */
define('ROLES_COORDINATOR', ['sysadmin', 'office_admin']);

/** Roles that can approve/reject AV service. */
define('ROLES_AV_APPROVE', ['sysadmin', 'office_admin', 'av_head', 'av_asst']);

/** Roles that can approve/reject Media/Design service. */
define('ROLES_MEDIA_APPROVE', ['sysadmin', 'office_admin', 'media_head', 'media_asst', 'designer_head', 'designer_asst']);

/** Roles that can approve/reject Photo service. */
define('ROLES_PHOTO_APPROVE', ['sysadmin', 'office_admin', 'media_head', 'media_asst']);

/** Roles that can manage admin settings (users, rooms, equipment). */
define('ROLES_ADMIN_SETTINGS', ['sysadmin']);

/** All roles that have at least dashboard + view access. */
define('ROLES_ALL', [
  'sysadmin', 'office_admin',
  'media_head', 'media_asst', 'media_member',
  'designer_head', 'designer_asst', 'designer_member',
  'av_head', 'av_asst', 'av_member',
]);

/**
 * Check if the current user can approve/reject a given service type.
 */
function can_approve_service(string $serviceType): bool {
  $user = current_user();
  if (!$user) return false;

  $role = $user['role'];

  return match ($serviceType) {
    'av'    => in_array($role, ROLES_AV_APPROVE, true),
    'media' => in_array($role, ROLES_MEDIA_APPROVE, true),
    'photo' => in_array($role, ROLES_PHOTO_APPROVE, true),
    default => false,
  };
}

/**
 * Check if the current user can assign PICs for a given service type.
 * Coordinators can assign any service; leads can assign their own.
 */
function can_assign_pic(string $serviceType): bool {
  $user = current_user();
  if (!$user) return false;

  $role = $user['role'];

  // Coordinators can assign anyone
  if (in_array($role, ROLES_COORDINATOR, true)) return true;

  return match ($serviceType) {
    'av'    => in_array($role, ['av_head', 'av_asst'], true),
    'media' => in_array($role, ['media_head', 'media_asst', 'designer_head', 'designer_asst'], true),
    'photo' => in_array($role, ['media_head', 'media_asst'], true),
    default => false,
  };
}

/**
 * Check if the current user has access to admin settings.
 */
function can_manage_settings(): bool {
  $user = current_user();
  return $user && in_array($user['role'], ROLES_ADMIN_SETTINGS, true);
}

/**
 * Get a human-readable label for a role slug.
 */
function role_label(string $role): string {
  return match ($role) {
    'sysadmin'        => 'System Admin',
    'office_admin'    => 'Office Admin',
    'media_head'      => 'Media Head',
    'media_asst'      => 'Media Asst. Head',
    'media_member'    => 'Media Member',
    'designer_head'   => 'Designer Head',
    'designer_asst'   => 'Designer Asst. Head',
    'designer_member' => 'Designer Member',
    'av_head'         => 'AV Head',
    'av_asst'         => 'AV Asst. Head',
    'av_member'       => 'AV Member',
    default           => ucfirst(str_replace('_', ' ', $role)),
  };
}

/**
 * Get a human-readable label for a service approval status.
 */
function approval_status_label(string $status): string {
  return match ($status) {
    'pending'         => 'Pending Review',
    'approved'        => 'Approved',
    'rejected'        => 'Rejected',
    'needs_more_info' => 'Needs More Info',
    'in_progress'     => 'In Progress',
    'completed'       => 'Completed',
    default           => ucfirst(str_replace('_', ' ', $status)),
  };
}

/**
 * CSS classes for a service approval status badge.
 */
function approval_status_classes(string $status): string {
  return match ($status) {
    'pending'         => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
    'approved'        => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
    'rejected'        => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
    'needs_more_info' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400',
    'in_progress'     => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
    'completed'       => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
    default           => 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400',
  };
}

/**
 * Derive the overall request status from per-service approval statuses.
 *
 * Rules (evaluated in order):
 *  1. All completed → completed
 *  2. All rejected  → rejected
 *  3. Any in_progress → in_progress
 *  4. All approved (none pending/needs_more_info) → approved
 *  5. Mix of approved+rejected (none pending) → approved (partially, shown in UI)
 *  6. Any pending or needs_more_info → pending
 */
function derive_overall_status(array $serviceStatuses): string {
  if (empty($serviceStatuses)) return 'pending';

  $counts = array_count_values($serviceStatuses);
  $total  = count($serviceStatuses);

  if (($counts['completed'] ?? 0) === $total) return 'completed';
  if (($counts['rejected'] ?? 0) === $total)  return 'rejected';
  if (($counts['in_progress'] ?? 0) > 0)      return 'in_progress';

  $hasPending  = ($counts['pending'] ?? 0) > 0;
  $hasNeedInfo = ($counts['needs_more_info'] ?? 0) > 0;

  if ($hasPending || $hasNeedInfo) return 'pending';

  // Remaining: mix of approved, rejected, completed (none pending)
  if (($counts['approved'] ?? 0) + ($counts['completed'] ?? 0) > 0) return 'approved';

  return 'pending';
}

/**
 * Recalculate and persist the overall request status based on current service statuses.
 * Call this after any service status change.
 */
function recalculate_overall_status(PDO $pdo, int $requestId): string {
  $stmt = $pdo->prepare('SELECT approval_status FROM request_types WHERE media_request_id = :id');
  $stmt->execute(['id' => $requestId]);
  $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);

  $overall = derive_overall_status($statuses);

  $stmt = $pdo->prepare('UPDATE media_requests SET request_status = :status, updated_at = NOW() WHERE id = :id');
  $stmt->execute(['status' => $overall, 'id' => $requestId]);

  return $overall;
}

/**
 * Get users eligible for PIC assignment for a given service type.
 * Returns array of ['id' => ..., 'name' => ..., 'role' => ...].
 */
function get_users_for_assignment(PDO $pdo, string $serviceType): array {
  $roles = match ($serviceType) {
    'av'    => ['sysadmin', 'office_admin', 'av_head', 'av_asst', 'av_member'],
    'media' => ['sysadmin', 'office_admin', 'media_head', 'media_asst', 'media_member', 'designer_head', 'designer_asst', 'designer_member'],
    'photo' => ['sysadmin', 'office_admin', 'media_head', 'media_asst', 'media_member'],
    default => [],
  };

  if (empty($roles)) return [];

  $placeholders = implode(',', array_fill(0, count($roles), '?'));
  $stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE is_active = 1 AND role IN ($placeholders) ORDER BY name");
  $stmt->execute($roles);
  return $stmt->fetchAll();
}

/**
 * Get coordinators (users who can be assigned as request coordinator).
 */
function get_coordinators(PDO $pdo): array {
  $roles = ROLES_COORDINATOR;
  $placeholders = implode(',', array_fill(0, count($roles), '?'));
  $stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE is_active = 1 AND role IN ($placeholders) ORDER BY name");
  $stmt->execute($roles);
  return $stmt->fetchAll();
}

/**
 * Write an audit log entry.
 */
function write_audit_log(PDO $pdo, string $action, string $entityType, int $entityId, ?array $before, ?array $after): void {
  $user = current_user();
  $stmt = $pdo->prepare('
    INSERT INTO audit_logs (actor_user_id, action, entity_type, entity_id, before_json, after_json, ip_address, user_agent, created_at)
    VALUES (:uid, :action, :etype, :eid, :before, :after, :ip, :ua, NOW())
  ');
  $stmt->execute([
    'uid'    => $user ? $user['id'] : null,
    'action' => $action,
    'etype'  => $entityType,
    'eid'    => $entityId,
    'before' => $before ? json_encode($before) : null,
    'after'  => $after ? json_encode($after) : null,
    'ip'     => $_SERVER['REMOTE_ADDR'] ?? null,
    'ua'     => $_SERVER['HTTP_USER_AGENT'] ?? null,
  ]);
}

// ---------------------------------------------------------------------------
// Content helpers
// ---------------------------------------------------------------------------

/**
 * Check if the current user can manage content items (create, edit, change status).
 */
function can_manage_content(): bool {
  $user = current_user();
  if (!$user) return false;
  return in_array($user['role'], [
    'sysadmin', 'office_admin',
    'media_head', 'media_asst', 'media_member',
    'designer_head', 'designer_asst', 'designer_member',
  ], true);
}

/**
 * Human-readable label for asset status.
 */
function asset_status_label(string $status): string {
  return match ($status) {
    'na'          => 'N/A',
    'pending'     => 'Pending',
    'in_progress' => 'In Progress',
    'ready'       => 'Ready',
    'done'        => 'Done',
    default       => ucfirst(str_replace('_', ' ', $status)),
  };
}

/**
 * CSS classes for asset status badge.
 */
function asset_status_classes(string $status): string {
  return match ($status) {
    'na'          => 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400',
    'pending'     => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
    'in_progress' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
    'ready'       => 'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-400',
    'done'        => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
    default       => 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400',
  };
}

/**
 * Human-readable label for content channel.
 */
function channel_label(string $channel): string {
  return match ($channel) {
    'facebook'      => 'Facebook',
    'instagram'     => 'Instagram',
    'telegram'      => 'Telegram',
    'tiktok'        => 'TikTok',
    'youtube'       => 'YouTube',
    'bulletin'      => 'Bulletin',
    'av_projection' => 'AV Projection',
    'cm'            => 'CM',
    default         => ucfirst($channel),
  };
}

/**
 * Human-readable label for channel status.
 */
function channel_status_label(string $status): string {
  return match ($status) {
    'na'        => 'N/A',
    'pending'   => 'Pending',
    'scheduled' => 'Scheduled',
    'posted'    => 'Posted',
    'done'      => 'Done',
    default     => ucfirst(str_replace('_', ' ', $status)),
  };
}

/**
 * CSS classes for channel status badge (smaller, inline).
 */
function channel_status_classes(string $status): string {
  return match ($status) {
    'na'        => 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400',
    'pending'   => 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400',
    'scheduled' => 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400',
    'posted'    => 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400',
    'done'      => 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400',
    default     => 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400',
  };
}

/**
 * Escape HTML output. Shortcut for htmlspecialchars.
 */
function e(?string $s): string {
  return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
