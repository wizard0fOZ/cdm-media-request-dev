<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_admin_auth();

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/mailer.php';

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

  // Get event dates for email
  $stmt = $pdo->prepare("
    SELECT start_date, end_date FROM event_schedules WHERE media_request_id = :id
  ");
  $stmt->execute([':id' => $id]);
  $schedule = $stmt->fetch();
  $eventDates = '';
  if ($schedule) {
    $eventDates = $schedule['start_date'];
    if ($schedule['end_date'] && $schedule['end_date'] !== $schedule['start_date']) {
      $eventDates .= ' to ' . $schedule['end_date'];
    }
  }

  // Update request status
  $stmt = $pdo->prepare("
    UPDATE media_requests
    SET request_status = 'approved', updated_at = NOW()
    WHERE id = :id
  ");
  $stmt->execute([':id' => $id]);

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
      'APPROVE_REQUEST',
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
    ':after_json' => json_encode(['status' => 'approved', 'reference_no' => $request['reference_no']], JSON_UNESCAPED_SLASHES),
    ':ip' => $ip,
    ':ua' => $ua
  ]);

  $pdo->commit();

  // Send approval email (after commit, don't block on failure)
  $emailData = [
    'requestor_name' => $request['requestor_name'],
    'reference_no' => $request['reference_no'],
    'event_name' => $request['event_name'],
    'email' => $request['email'],
    'event_dates' => $eventDates,
  ];
  sendApprovalEmail($emailData);

  // Redirect back to view page with success message
  header('Location: ../view.php?id=' . $id . '&success=1');
  exit;

} catch (Throwable $e) {
  if ($pdo->inTransaction()) {
    $pdo->rollBack();
  }
  error_log("Approve request error: " . $e->getMessage());
  die('Error: Unable to approve request. Please try again.');
}
