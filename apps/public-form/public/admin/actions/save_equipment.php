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

$action   = $_POST['action'] ?? '';
$redirect = '../settings.php?tab=equipment';

try {
    switch ($action) {

        // -----------------------------------------------------------------
        case 'create':
            $name     = trim($_POST['name'] ?? '');
            $category = trim($_POST['category'] ?? '');

            if ($name === '' || $category === '') {
                header("Location: $redirect&error=" . urlencode('Name and category are required'));
                exit;
            }

            // Check name uniqueness
            $check = $pdo->prepare('SELECT id FROM equipment WHERE name = :name');
            $check->execute(['name' => $name]);
            if ($check->fetch()) {
                header("Location: $redirect&error=" . urlencode('Equipment name already exists'));
                exit;
            }

            $stmt = $pdo->prepare('
                INSERT INTO equipment (name, category, is_active, created_at, updated_at)
                VALUES (:name, :category, 1, NOW(), NOW())
            ');
            $stmt->execute(['name' => $name, 'category' => $category]);
            $newId = (int) $pdo->lastInsertId();

            write_audit_log($pdo, 'create_equipment', 'equipment', $newId, null, [
                'name' => $name, 'category' => $category,
            ]);

            header("Location: $redirect&success=" . urlencode('Equipment created'));
            exit;

        // -----------------------------------------------------------------
        case 'update':
            $equipId  = (int) ($_POST['equipment_id'] ?? 0);
            $name     = trim($_POST['name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if (!$equipId || $name === '' || $category === '') {
                header("Location: $redirect&error=" . urlencode('Name and category are required'));
                exit;
            }

            // Check name uniqueness (exclude own id)
            $check = $pdo->prepare('SELECT id FROM equipment WHERE name = :name AND id != :id');
            $check->execute(['name' => $name, 'id' => $equipId]);
            if ($check->fetch()) {
                header("Location: $redirect&error=" . urlencode('Equipment name already in use'));
                exit;
            }

            // Get before state
            $before = $pdo->prepare('SELECT name, category, is_active FROM equipment WHERE id = :id');
            $before->execute(['id' => $equipId]);
            $beforeData = $before->fetch(PDO::FETCH_ASSOC);
            if (!$beforeData) {
                header("Location: $redirect&error=" . urlencode('Equipment not found'));
                exit;
            }

            $stmt = $pdo->prepare('
                UPDATE equipment SET name = :name, category = :category, is_active = :active, updated_at = NOW()
                WHERE id = :id
            ');
            $stmt->execute([
                'name'     => $name,
                'category' => $category,
                'active'   => $isActive,
                'id'       => $equipId,
            ]);

            write_audit_log($pdo, 'update_equipment', 'equipment', $equipId, $beforeData, [
                'name' => $name, 'category' => $category, 'is_active' => $isActive,
            ]);

            header("Location: $redirect&success=" . urlencode('Equipment updated'));
            exit;

        // -----------------------------------------------------------------
        case 'toggle_active':
            $equipId = (int) ($_POST['equipment_id'] ?? 0);
            if (!$equipId) {
                header("Location: $redirect&error=" . urlencode('Invalid equipment'));
                exit;
            }

            $before = $pdo->prepare('SELECT is_active FROM equipment WHERE id = :id');
            $before->execute(['id' => $equipId]);
            $beforeVal = $before->fetchColumn();
            if ($beforeVal === false) {
                header("Location: $redirect&error=" . urlencode('Equipment not found'));
                exit;
            }

            $newVal = $beforeVal ? 0 : 1;
            $stmt = $pdo->prepare('UPDATE equipment SET is_active = :active, updated_at = NOW() WHERE id = :id');
            $stmt->execute(['active' => $newVal, 'id' => $equipId]);

            write_audit_log($pdo, 'toggle_equipment_active', 'equipment', $equipId,
                ['is_active' => (int) $beforeVal], ['is_active' => $newVal]);

            $msg = $newVal ? 'Equipment activated' : 'Equipment deactivated';
            header("Location: $redirect&success=" . urlencode($msg));
            exit;

        // -----------------------------------------------------------------
        default:
            header("Location: $redirect&error=" . urlencode('Invalid action'));
            exit;
    }
} catch (Throwable $ex) {
    error_log('save_equipment error: ' . $ex->getMessage());
    header("Location: $redirect&error=" . urlencode('An error occurred'));
    exit;
}
