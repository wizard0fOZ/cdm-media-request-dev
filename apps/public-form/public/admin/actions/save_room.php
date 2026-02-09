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
$redirect = '../settings.php?tab=rooms';

try {
    switch ($action) {

        // -----------------------------------------------------------------
        case 'create':
            $name  = trim($_POST['name'] ?? '');
            $notes = trim($_POST['notes'] ?? '') ?: null;

            if ($name === '') {
                header("Location: $redirect&error=" . urlencode('Room name is required'));
                exit;
            }

            // Check name uniqueness
            $check = $pdo->prepare('SELECT id FROM rooms WHERE name = :name');
            $check->execute(['name' => $name]);
            if ($check->fetch()) {
                header("Location: $redirect&error=" . urlencode('Room name already exists'));
                exit;
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare('
                INSERT INTO rooms (name, notes, is_active, created_at, updated_at)
                VALUES (:name, :notes, 1, NOW(), NOW())
            ');
            $stmt->execute(['name' => $name, 'notes' => $notes]);
            $roomId = (int) $pdo->lastInsertId();

            // Insert equipment assignments
            $equipmentIds = $_POST['equipment'] ?? [];
            $equipmentQty = $_POST['equipment_qty'] ?? [];
            $equipmentNotes = $_POST['equipment_notes'] ?? [];

            $eqStmt = $pdo->prepare('
                INSERT INTO room_equipment (room_id, equipment_id, quantity, notes)
                VALUES (:rid, :eid, :qty, :notes)
            ');
            foreach ($equipmentIds as $eqId) {
                $eqId = (int) $eqId;
                if ($eqId <= 0) continue;
                $qty = max(1, (int) ($equipmentQty[$eqId] ?? 1));
                $eqNote = trim($equipmentNotes[$eqId] ?? '') ?: null;
                $eqStmt->execute([
                    'rid'   => $roomId,
                    'eid'   => $eqId,
                    'qty'   => $qty,
                    'notes' => $eqNote,
                ]);
            }

            write_audit_log($pdo, 'create_room', 'rooms', $roomId, null, [
                'name' => $name, 'equipment_count' => count($equipmentIds),
            ]);

            $pdo->commit();
            header("Location: $redirect&success=" . urlencode('Room created'));
            exit;

        // -----------------------------------------------------------------
        case 'update':
            $roomId   = (int) ($_POST['room_id'] ?? 0);
            $name     = trim($_POST['name'] ?? '');
            $notes    = trim($_POST['notes'] ?? '') ?: null;
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if (!$roomId || $name === '') {
                header("Location: $redirect&error=" . urlencode('Room name is required'));
                exit;
            }

            // Check name uniqueness (exclude own id)
            $check = $pdo->prepare('SELECT id FROM rooms WHERE name = :name AND id != :id');
            $check->execute(['name' => $name, 'id' => $roomId]);
            if ($check->fetch()) {
                header("Location: $redirect&error=" . urlencode('Room name already in use'));
                exit;
            }

            // Get before state
            $before = $pdo->prepare('SELECT name, notes, is_active FROM rooms WHERE id = :id');
            $before->execute(['id' => $roomId]);
            $beforeData = $before->fetch(PDO::FETCH_ASSOC);
            if (!$beforeData) {
                header("Location: $redirect&error=" . urlencode('Room not found'));
                exit;
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare('
                UPDATE rooms SET name = :name, notes = :notes, is_active = :active, updated_at = NOW()
                WHERE id = :id
            ');
            $stmt->execute([
                'name'   => $name,
                'notes'  => $notes,
                'active' => $isActive,
                'id'     => $roomId,
            ]);

            // Delete and re-insert equipment
            $pdo->prepare('DELETE FROM room_equipment WHERE room_id = :id')
                ->execute(['id' => $roomId]);

            $equipmentIds   = $_POST['equipment'] ?? [];
            $equipmentQty   = $_POST['equipment_qty'] ?? [];
            $equipmentNotes = $_POST['equipment_notes'] ?? [];

            $eqStmt = $pdo->prepare('
                INSERT INTO room_equipment (room_id, equipment_id, quantity, notes)
                VALUES (:rid, :eid, :qty, :notes)
            ');
            foreach ($equipmentIds as $eqId) {
                $eqId = (int) $eqId;
                if ($eqId <= 0) continue;
                $qty = max(1, (int) ($equipmentQty[$eqId] ?? 1));
                $eqNote = trim($equipmentNotes[$eqId] ?? '') ?: null;
                $eqStmt->execute([
                    'rid'   => $roomId,
                    'eid'   => $eqId,
                    'qty'   => $qty,
                    'notes' => $eqNote,
                ]);
            }

            write_audit_log($pdo, 'update_room', 'rooms', $roomId, $beforeData, [
                'name' => $name, 'notes' => $notes, 'is_active' => $isActive,
                'equipment_count' => count($equipmentIds),
            ]);

            $pdo->commit();
            header("Location: $redirect&success=" . urlencode('Room updated'));
            exit;

        // -----------------------------------------------------------------
        case 'toggle_active':
            $roomId = (int) ($_POST['room_id'] ?? 0);
            if (!$roomId) {
                header("Location: $redirect&error=" . urlencode('Invalid room'));
                exit;
            }

            $before = $pdo->prepare('SELECT is_active FROM rooms WHERE id = :id');
            $before->execute(['id' => $roomId]);
            $beforeVal = $before->fetchColumn();
            if ($beforeVal === false) {
                header("Location: $redirect&error=" . urlencode('Room not found'));
                exit;
            }

            $newVal = $beforeVal ? 0 : 1;
            $stmt = $pdo->prepare('UPDATE rooms SET is_active = :active, updated_at = NOW() WHERE id = :id');
            $stmt->execute(['active' => $newVal, 'id' => $roomId]);

            write_audit_log($pdo, 'toggle_room_active', 'rooms', $roomId,
                ['is_active' => (int) $beforeVal], ['is_active' => $newVal]);

            $msg = $newVal ? 'Room activated' : 'Room deactivated';
            header("Location: $redirect&success=" . urlencode($msg));
            exit;

        // -----------------------------------------------------------------
        default:
            header("Location: $redirect&error=" . urlencode('Invalid action'));
            exit;
    }
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('save_room error: ' . $ex->getMessage());
    header("Location: $redirect&error=" . urlencode('An error occurred'));
    exit;
}
