<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_admin_auth();

require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/mailer.php';

// Helper to get IP address
function get_ip(): string {
  $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
  foreach ($keys as $k) {
    if (!empty($_SERVER[$k])) {
      $val = explode(',', (string)$_SERVER[$k])[0];
      return trim($val);
    }
  }
  return '0.0.0.0';
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../index.php');
  exit;
}

// Get request ID
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
  header('Location: ../index.php');
  exit;
}

// Get rejection reason (required)
$rejectionReason = isset($_POST['rejection_reason']) ? trim($_POST['rejection_reason']) : '';
if ($rejectionReason === '') {
  die('Error: Rejection reason is required.');
}

try {
  $pdo->beginTransaction();

  // Check if request exists and is pending - get full details for email
  $stmt = $pdo->prepare("
    SELECT id, reference_no, request_status, requestor_name, email, event_name
    FROM media_requests
    WHERE id = :id
  ");
  $stmt->execute([':id' => $id]);
  $request = $stmt->fetch();

  if (!$request) {
    $pdo->rollBack();
    die('Error: Request not found');
  }

  if ($request['request_status'] !== 'pending') {
    $pdo->rollBack();
    header('Location: ../view.php?id=' . $id);
    exit;
  }

  // Update request status and rejection reason
  $stmt = $pdo->prepare("
    UPDATE media_requests
    SET request_status = 'rejected', rejection_reason = :reason, updated_at = NOW()
    WHERE id = :id
  ");
  $stmt->execute([':id' => $id, ':reason' => $rejectionReason]);

  // Insert audit log
  $ip = get_ip();
  $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');

  $stmt = $pdo->prepare("
    INSERT INTO audit_logs (
      actor_user_id,
      actor_name_snapshot,
      action,
      entity_type,
      entity_id,
      before_json,
      after_json,
      ip_address,
      user_agent,
      created_at
    ) VALUES (
      NULL,
      'Admin',
      'REJECT_REQUEST',
      'media_requests',
      :entity_id,
      :before_json,
      :after_json,
      :ip,
      :ua,
      NOW()
    )
  ");

  $stmt->execute([
    ':entity_id' => $id,
    ':before_json' => json_encode(['status' => 'pending', 'reference_no' => $request['reference_no']], JSON_UNESCAPED_SLASHES),
    ':after_json' => json_encode(['status' => 'rejected', 'reference_no' => $request['reference_no'], 'rejection_reason' => $rejectionReason], JSON_UNESCAPED_SLASHES),
    ':ip' => $ip,
    ':ua' => $ua
  ]);

  $pdo->commit();

  // Send rejection email (after commit, don't block on failure)
  $emailData = [
    'requestor_name' => $request['requestor_name'],
    'reference_no' => $request['reference_no'],
    'event_name' => $request['event_name'],
    'email' => $request['email'],
  ];
  sendRejectionEmail($emailData, $rejectionReason);

  // Redirect back to view page with success message
  header('Location: ../view.php?id=' . $id . '&success=1');
  exit;

} catch (Throwable $e) {
  if ($pdo->inTransaction()) {
    $pdo->rollBack();
  }
  error_log("Reject request error: " . $e->getMessage());
  die('Error: Unable to reject request. Please try again.');
}
