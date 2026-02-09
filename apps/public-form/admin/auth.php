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
