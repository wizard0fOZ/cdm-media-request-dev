<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_admin_auth();
require_once __DIR__ . '/../../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$requestId   = (int) ($_POST['request_id'] ?? 0);
$serviceType = $_POST['service_type'] ?? '';
$userId      = ($_POST['user_id'] ?? '') === '' ? null : (int) $_POST['user_id'];

if ($requestId <= 0 || !in_array($serviceType, ['av', 'media', 'photo'], true)) {
    http_response_code(400);
    exit('Invalid parameters');
}

if (!can_assign_pic($serviceType)) {
    http_response_code(403);
    exit('Access denied');
}

$stmt = $pdo->prepare('SELECT id, assigned_pic_user_id FROM request_types WHERE media_request_id = :rid AND type = :type');
$stmt->execute(['rid' => $requestId, 'type' => $serviceType]);
$service = $stmt->fetch();

if (!$service) {
    http_response_code(404);
    exit('Service not found for this request');
}

$before = $service;

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('
        UPDATE request_types
        SET assigned_pic_user_id = :uid, updated_at = NOW()
        WHERE media_request_id = :rid AND type = :type
    ');
    $stmt->execute([
        'uid'  => $userId,
        'rid'  => $requestId,
        'type' => $serviceType,
    ]);

    // Get assigned user name for audit
    $assignedName = null;
    if ($userId) {
        $stmt = $pdo->prepare('SELECT name FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $assignedName = $stmt->fetchColumn();
    }

    write_audit_log($pdo, 'assign_pic', 'request_type', (int) $service['id'], $before, [
        'assigned_pic_user_id' => $userId,
        'assigned_name'        => $assignedName,
        'service_type'         => $serviceType,
    ]);

    $pdo->commit();
} catch (Throwable $ex) {
    $pdo->rollBack();
    error_log('assign_pic error: ' . $ex->getMessage());
    http_response_code(500);
    exit('An error occurred');
}

header('Location: ../view.php?id=' . $requestId . '&success=1');
exit;
