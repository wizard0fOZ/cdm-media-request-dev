<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_admin_auth();
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$requestId   = (int) ($_POST['request_id'] ?? 0);
$serviceType = $_POST['service_type'] ?? '';
$reason      = trim($_POST['rejected_reason'] ?? '');
$note        = trim($_POST['decision_note'] ?? '');

if ($requestId <= 0 || !in_array($serviceType, ['av', 'media', 'photo'], true)) {
    http_response_code(400);
    exit('Invalid parameters');
}

if ($reason === '') {
    http_response_code(400);
    exit('Rejection reason is required');
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
            rejected_reason = :reason,
            decision_note = :note,
            updated_at = NOW()
        WHERE media_request_id = :rid AND type = :type
    ');
    $stmt->execute([
        'status' => 'rejected',
        'uid'    => $user['id'],
        'reason' => $reason,
        'note'   => $note ?: null,
        'rid'    => $requestId,
        'type'   => $serviceType,
    ]);

    $overall = recalculate_overall_status($pdo, $requestId);

    write_audit_log($pdo, 'reject_service', 'request_type', (int) $service['id'], $before, [
        'approval_status' => 'rejected',
        'rejected_by'     => $user['name'],
        'rejected_reason' => $reason,
        'decision_note'   => $note,
        'overall_status'  => $overall,
    ]);

    $pdo->commit();
} catch (Throwable $ex) {
    $pdo->rollBack();
    error_log('reject_service error: ' . $ex->getMessage());
    http_response_code(500);
    exit('An error occurred');
}

// Send rejection email when overall status becomes 'rejected'
if ($overall === 'rejected') {
    $stmt = $pdo->prepare('SELECT requestor_name, email, reference_no, event_name FROM media_requests WHERE id = :id');
    $stmt->execute(['id' => $requestId]);
    $reqData = $stmt->fetch();
    if ($reqData) {
        sendRejectionEmail($reqData, $reason);
    }
}

header('Location: ../view.php?id=' . $requestId . '&success=1');
exit;
