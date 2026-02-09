<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_admin_auth();
require_role(ROLES_ADMIN_SETTINGS);
require_once __DIR__ . '/../../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$user   = current_user();
$action = $_POST['action'] ?? '';

$validRoles = [
    'sysadmin', 'office_admin',
    'media_head', 'media_asst', 'media_member',
    'designer_head', 'designer_asst', 'designer_member',
    'av_head', 'av_asst', 'av_member',
];

$redirect = '../settings.php?tab=users';

try {
    switch ($action) {

        // -----------------------------------------------------------------
        case 'create':
            $name     = trim($_POST['name'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $role     = $_POST['role'] ?? '';
            $password = $_POST['password'] ?? '';

            if ($name === '' || $email === '') {
                header("Location: $redirect&error=" . urlencode('Name and email are required'));
                exit;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                header("Location: $redirect&error=" . urlencode('Invalid email address'));
                exit;
            }
            if (!in_array($role, $validRoles, true)) {
                header("Location: $redirect&error=" . urlencode('Invalid role'));
                exit;
            }
            if (strlen($password) < 8) {
                header("Location: $redirect&error=" . urlencode('Password must be at least 8 characters'));
                exit;
            }

            // Check email uniqueness
            $check = $pdo->prepare('SELECT id FROM users WHERE email = :email');
            $check->execute(['email' => $email]);
            if ($check->fetch()) {
                header("Location: $redirect&error=" . urlencode('Email already exists'));
                exit;
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('
                INSERT INTO users (name, email, password_hash, role, is_active, created_at, updated_at)
                VALUES (:name, :email, :hash, :role, 1, NOW(), NOW())
            ');
            $stmt->execute([
                'name'  => $name,
                'email' => $email,
                'hash'  => $hash,
                'role'  => $role,
            ]);
            $newId = (int) $pdo->lastInsertId();

            write_audit_log($pdo, 'create_user', 'users', $newId, null, [
                'name' => $name, 'email' => $email, 'role' => $role,
            ]);

            header("Location: $redirect&success=" . urlencode('User created'));
            exit;

        // -----------------------------------------------------------------
        case 'update':
            $userId   = (int) ($_POST['user_id'] ?? 0);
            $name     = trim($_POST['name'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $role     = $_POST['role'] ?? '';
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if (!$userId || $name === '' || $email === '') {
                header("Location: $redirect&error=" . urlencode('Name and email are required'));
                exit;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                header("Location: $redirect&error=" . urlencode('Invalid email address'));
                exit;
            }
            if (!in_array($role, $validRoles, true)) {
                header("Location: $redirect&error=" . urlencode('Invalid role'));
                exit;
            }

            // Check email uniqueness (exclude own id)
            $check = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id');
            $check->execute(['email' => $email, 'id' => $userId]);
            if ($check->fetch()) {
                header("Location: $redirect&error=" . urlencode('Email already in use by another user'));
                exit;
            }

            // Get before state
            $before = $pdo->prepare('SELECT name, email, role, is_active FROM users WHERE id = :id');
            $before->execute(['id' => $userId]);
            $beforeData = $before->fetch(PDO::FETCH_ASSOC);
            if (!$beforeData) {
                header("Location: $redirect&error=" . urlencode('User not found'));
                exit;
            }

            // Prevent deactivating own account
            if ($userId === (int) $user['id'] && $isActive === 0) {
                $isActive = 1;
            }

            $stmt = $pdo->prepare('
                UPDATE users SET name = :name, email = :email, role = :role, is_active = :active, updated_at = NOW()
                WHERE id = :id
            ');
            $stmt->execute([
                'name'   => $name,
                'email'  => $email,
                'role'   => $role,
                'active' => $isActive,
                'id'     => $userId,
            ]);

            write_audit_log($pdo, 'update_user', 'users', $userId, $beforeData, [
                'name' => $name, 'email' => $email, 'role' => $role, 'is_active' => $isActive,
            ]);

            header("Location: $redirect&success=" . urlencode('User updated'));
            exit;

        // -----------------------------------------------------------------
        case 'reset_password':
            $userId   = (int) ($_POST['user_id'] ?? 0);
            $password = $_POST['password'] ?? '';

            if (!$userId) {
                header("Location: $redirect&error=" . urlencode('Invalid user'));
                exit;
            }
            if (strlen($password) < 8) {
                header("Location: $redirect&error=" . urlencode('Password must be at least 8 characters'));
                exit;
            }

            // Verify user exists
            $check = $pdo->prepare('SELECT id FROM users WHERE id = :id');
            $check->execute(['id' => $userId]);
            if (!$check->fetch()) {
                header("Location: $redirect&error=" . urlencode('User not found'));
                exit;
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id');
            $stmt->execute(['hash' => $hash, 'id' => $userId]);

            write_audit_log($pdo, 'reset_password', 'users', $userId, null, null);

            header("Location: $redirect&success=" . urlencode('Password reset'));
            exit;

        // -----------------------------------------------------------------
        case 'toggle_active':
            $userId = (int) ($_POST['user_id'] ?? 0);

            if (!$userId) {
                header("Location: $redirect&error=" . urlencode('Invalid user'));
                exit;
            }

            // Cannot toggle own account
            if ($userId === (int) $user['id']) {
                header("Location: $redirect&error=" . urlencode('Cannot deactivate your own account'));
                exit;
            }

            $before = $pdo->prepare('SELECT is_active FROM users WHERE id = :id');
            $before->execute(['id' => $userId]);
            $beforeVal = $before->fetchColumn();
            if ($beforeVal === false) {
                header("Location: $redirect&error=" . urlencode('User not found'));
                exit;
            }

            $newVal = $beforeVal ? 0 : 1;
            $stmt = $pdo->prepare('UPDATE users SET is_active = :active, updated_at = NOW() WHERE id = :id');
            $stmt->execute(['active' => $newVal, 'id' => $userId]);

            write_audit_log($pdo, 'toggle_user_active', 'users', $userId,
                ['is_active' => (int) $beforeVal], ['is_active' => $newVal]);

            $msg = $newVal ? 'User activated' : 'User deactivated';
            header("Location: $redirect&success=" . urlencode($msg));
            exit;

        // -----------------------------------------------------------------
        default:
            header("Location: $redirect&error=" . urlencode('Invalid action'));
            exit;
    }
} catch (Throwable $ex) {
    error_log('save_user error: ' . $ex->getMessage());
    header("Location: $redirect&error=" . urlencode('An error occurred'));
    exit;
}
