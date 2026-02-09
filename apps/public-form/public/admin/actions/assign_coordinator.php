<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_admin_auth();
require_once __DIR__ . '/../../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$requestId = (int) ($_POST['request_id'] ?? 0);
$userId    = ($_POST['user_id'] ?? '') === '' ? null : (int) $_POST['user_id'];

if ($requestId <= 0) {
    http_response_code(400);
    exit('Invalid parameters');
}

$user = current_user();
if (!$user || !in_array($user['role'], ROLES_COORDINATOR, true)) {
    http_response_code(403);
    exit('Access denied');
}

$stmt = $pdo->prepare('SELECT id, assigned_coordinator_user_id FROM media_requests WHERE id = :id');
$stmt->execute(['id' => $requestId]);
$request = $stmt->fetch();

if (!$request) {
    http_response_code(404);
    exit('Request not found');
}

$before = $request;

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('
        UPDATE media_requests
        SET assigned_coordinator_user_id = :uid, updated_at = NOW()
        WHERE id = :id
    ');
    $stmt->execute(['uid' => $userId, 'id' => $requestId]);

    $assignedName = null;
    if ($userId) {
        $stmt = $pdo->prepare('SELECT name FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $assignedName = $stmt->fetchColumn();
    }

    write_audit_log($pdo, 'assign_coordinator', 'media_request', $requestId, $before, [
        'assigned_coordinator_user_id' => $userId,
        'assigned_name'                => $assignedName,
    ]);

    $pdo->commit();
} catch (Throwable $ex) {
    $pdo->rollBack();
    error_log('assign_coordinator error: ' . $ex->getMessage());
    http_response_code(500);
    exit('An error occurred');
}

header('Location: ../view.php?id=' . $requestId . '&success=1');
exit;
