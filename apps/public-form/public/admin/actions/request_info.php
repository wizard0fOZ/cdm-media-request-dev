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
$note        = trim($_POST['decision_note'] ?? '');

if ($requestId <= 0 || !in_array($serviceType, ['av', 'media', 'photo'], true)) {
    http_response_code(400);
    exit('Invalid parameters');
}

if ($note === '') {
    http_response_code(400);
    exit('A question or note is required when requesting more info');
}

if (!can_approve_service($serviceType)) {
    http_response_code(403);
    exit('Access denied');
}

$user = current_user();

$stmt = $pdo->prepare('SELECT id, approval_status FROM request_types WHERE media_request_id = :rid AND type = :type');
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
        SET approval_status = :status,
            approved_by_user_id = :uid,
            approved_at = NOW(),
            decision_note = :note,
            updated_at = NOW()
        WHERE media_request_id = :rid AND type = :type
    ');
    $stmt->execute([
        'status' => 'needs_more_info',
        'uid'    => $user['id'],
        'note'   => $note,
        'rid'    => $requestId,
        'type'   => $serviceType,
    ]);

    $overall = recalculate_overall_status($pdo, $requestId);

    write_audit_log($pdo, 'request_more_info', 'request_type', (int) $service['id'], $before, [
        'approval_status' => 'needs_more_info',
        'requested_by'    => $user['name'],
        'decision_note'   => $note,
        'overall_status'  => $overall,
    ]);

    $pdo->commit();
} catch (Throwable $ex) {
    $pdo->rollBack();
    error_log('request_info error: ' . $ex->getMessage());
    http_response_code(500);
    exit('An error occurred');
}

header('Location: ../view.php?id=' . $requestId . '&success=1');
exit;
