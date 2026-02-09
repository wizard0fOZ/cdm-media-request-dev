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
$note        = trim($_POST['decision_note'] ?? '');

if ($requestId <= 0 || !in_array($serviceType, ['av', 'media', 'photo'], true)) {
    http_response_code(400);
    exit('Invalid parameters');
}

if (!can_approve_service($serviceType)) {
    http_response_code(403);
    exit('Access denied');
}

$user = current_user();

// Fetch current service record
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
        'status' => 'approved',
        'uid'    => $user['id'],
        'note'   => $note ?: null,
        'rid'    => $requestId,
        'type'   => $serviceType,
    ]);

    $overall = recalculate_overall_status($pdo, $requestId);

    write_audit_log($pdo, 'approve_service', 'request_type', (int) $service['id'], $before, [
        'approval_status' => 'approved',
        'approved_by'     => $user['name'],
        'decision_note'   => $note,
        'overall_status'  => $overall,
    ]);

    $pdo->commit();
} catch (Throwable $ex) {
    $pdo->rollBack();
    error_log('approve_service error: ' . $ex->getMessage());
    http_response_code(500);
    exit('An error occurred');
}

// Send approval email when overall status becomes 'approved'
if ($overall === 'approved') {
    $stmt = $pdo->prepare('
        SELECT mr.requestor_name, mr.email, mr.reference_no, mr.event_name,
               es.start_date, es.end_date
        FROM media_requests mr
        LEFT JOIN event_schedules es ON mr.id = es.media_request_id
        WHERE mr.id = :id
    ');
    $stmt->execute(['id' => $requestId]);
    $reqData = $stmt->fetch();
    if ($reqData) {
        $eventDates = $reqData['start_date'] ?? '';
        if (!empty($reqData['end_date']) && $reqData['end_date'] !== $reqData['start_date']) {
            $eventDates .= ' to ' . $reqData['end_date'];
        }
        $reqData['event_dates'] = $eventDates;
        sendApprovalEmail($reqData);
    }
}

header('Location: ../view.php?id=' . $requestId . '&success=1');
exit;
