<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/mailer.php';
require_once __DIR__ . '/../../includes/telegram.php';

/**
 * Helpers
 */
function json_fail(int $code, string $msg): void {
  http_response_code($code);
  echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_SLASHES);
  exit;
}

function get_ip(): string {
  // Shared hosting often uses these headers, but treat as best-effort only.
  $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
  foreach ($keys as $k) {
    if (!empty($_SERVER[$k])) {
      $val = explode(',', (string)$_SERVER[$k])[0];
      return trim($val);
    }
  }
  return '0.0.0.0';
}

function normalize_phone(string $s): string {
  return trim($s);
}

function is_valid_email(string $email): bool {
  return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

function is_valid_url(string $url): bool {
  return (bool)filter_var($url, FILTER_VALIDATE_URL);
}

function ymd(string $s): ?string {
  $s = trim($s);
  if ($s === '') return null;
  // expecting YYYY-MM-DD
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return null;
  return $s;
}

function hm(string $s): ?string {
  $s = trim($s);
  if ($s === '') return null;
  // expecting HH:MM or HH:MM:SS
  if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $s)) return null;
  if (strlen($s) === 5) return $s . ':00';
  return $s;
}

function now_dt(): string {
  return date('Y-m-d H:i:s');
}

function require_csrf(): void {
  $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
  $bodyCsrf = null;

  $raw = file_get_contents('php://input') ?: '';
  $data = json_decode($raw, true);
  if (is_array($data) && isset($data['csrf'])) $bodyCsrf = (string)$data['csrf'];

  $token = (string)($_SESSION['csrf_token'] ?? '');
  if ($token === '' || !hash_equals($token, (string)$header) || !hash_equals($token, (string)$bodyCsrf)) {
    json_fail(419, 'Invalid session token. Please refresh and try again.');
  }

  // store parsed data for reuse
  $GLOBALS['__PAYLOAD__'] = $data;
}

/**
 * Compute MR-YYYY-#### safely in DB transaction.
 * Uses SELECT MAX with FOR UPDATE on the year range.
 */
function generate_reference_no(PDO $pdo, int $year): string {
  $prefix = 'MR-' . $year . '-';

  $stmt = $pdo->prepare("
    SELECT MAX(reference_no) AS max_ref
    FROM media_requests
    WHERE reference_no LIKE :prefixLike
    FOR UPDATE
  ");
  $stmt->execute([':prefixLike' => $prefix . '%']);
  $row = $stmt->fetch();

  $next = 1;
  if (!empty($row['max_ref'])) {
    // max_ref example: MR-2026-0007
    $parts = explode('-', (string)$row['max_ref']);
    $num = (int)($parts[2] ?? 0);
    $next = $num + 1;
  }

  return sprintf("MR-%d-%04d", $year, $next);
}

require_csrf();
$payload = $GLOBALS['__PAYLOAD__'] ?? null;
if (!is_array($payload)) json_fail(400, 'Invalid request payload.');

try {
  // Basic anti-bot honeypot
  if (!empty($payload['requestor']['hp']) || !empty($payload['hp'])) {
    json_fail(400, 'Submission blocked.');
  }

  $requestor = $payload['requestor'] ?? [];
  $event     = $payload['event'] ?? [];
  $schedule  = $payload['schedule'] ?? [];
  $services  = $payload['services'] ?? [];
  $computed  = $payload['computed'] ?? [];
  $references = $payload['references'] ?? [];

  // Validate required approvals hard stop
  $hasApprovals = (bool)($requestor['has_required_approvals'] ?? false);
  if (!$hasApprovals) {
    json_fail(422, 'You cannot submit without required approvals.');
  }

  // Validate requestor
  $requestorName = trim((string)($requestor['name'] ?? ''));
  $ministry      = trim((string)($requestor['ministry'] ?? ''));
  $contactNo     = normalize_phone((string)($requestor['contact_no'] ?? ''));
  $email         = trim((string)($requestor['email'] ?? ''));

  if ($requestorName === '') json_fail(422, 'Name is required.');
  if ($contactNo === '') json_fail(422, 'Contact number is required.');
  if ($email === '' || !is_valid_email($email)) json_fail(422, 'Valid email is required.');

  // Validate event
  $eventName = trim((string)($event['name'] ?? ''));
  $eventDesc = trim((string)($event['description'] ?? ''));
  $eventLoc  = trim((string)($event['location_note'] ?? ''));

  if ($eventName === '') json_fail(422, 'Event name is required.');

  // Validate schedule
  $scheduleType = (string)($schedule['type'] ?? '');
  if (!in_array($scheduleType, ['single', 'recurring', 'custom_list'], true)) {
    json_fail(422, 'Invalid schedule type.');
  }

  // Determine schedule start/end for event_schedules table
  $startDate = null;
  $endDate   = null;
  $startTime = null;
  $endTime   = null;

  $recurPattern = null;
  $recurDOW = null;
  $recurInterval = null;

  $occurrences = [];

  if ($scheduleType === 'recurring') {
    $startDate = ymd((string)($schedule['start_date'] ?? ''));
    $endDate   = ymd((string)($schedule['end_date'] ?? ''));
    $startTime = hm((string)($schedule['start_time'] ?? ''));
    $endTime   = hm((string)($schedule['end_time'] ?? ''));

    $recurPattern = (string)($schedule['recurrence_pattern'] ?? '');
    $recurDOW = (string)($schedule['recurrence_days_of_week'] ?? '');
    $recurInterval = (int)($schedule['recurrence_interval'] ?? 1);

    if (!$startDate) json_fail(422, 'Recurring start date is required.');
    if (!$endDate) json_fail(422, 'Recurring end date is required.');
    if ($endDate < $startDate) json_fail(422, 'Recurring end date cannot be earlier than start date.');
    if (!in_array($recurPattern, ['weekly', 'biweekly', 'monthly'], true)) json_fail(422, 'Invalid recurrence pattern.');
    if (!in_array($recurDOW, ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'], true)) json_fail(422, 'Invalid day of week.');
    if ($recurInterval < 1 || $recurInterval > 12) $recurInterval = 1;

  } else { // custom_list
    $occ = $schedule['occurrences'] ?? [];
    if (!is_array($occ) || count($occ) === 0) {
      json_fail(422, 'Please add at least one occurrence date.');
    }

    foreach ($occ as $o) {
      if (!is_array($o)) continue;
      $d = ymd((string)($o['date'] ?? ''));
      if (!$d) continue;

      $occurrences[] = [
        'date' => $d,
        'start_time' => hm((string)($o['start_time'] ?? '')),
        'end_time' => hm((string)($o['end_time'] ?? '')),
      ];
    }

    if (count($occurrences) === 0) {
      json_fail(422, 'Please add at least one valid occurrence date.');
    }

    usort($occurrences, fn($a, $b) => strcmp($a['date'], $b['date']));
    $startDate = $occurrences[0]['date'];
    $endDate = $occurrences[count($occurrences)-1]['date'];
    $startTime = null;
    $endTime = null;
  }

  $scheduleNotes = trim((string)($schedule['notes'] ?? ''));

  // Services validation
  if (!is_array($services) || count($services) === 0) {
    json_fail(422, 'Select at least one service.');
  }
  $services = array_values(array_unique(array_map('strval', $services)));
  foreach ($services as $t) {
    if (!in_array($t, ['av','media','photo'], true)) json_fail(422, 'Invalid service type.');
  }

  // Late flag
  $isLate = (bool)($computed['is_late'] ?? false);
  $leadDays = isset($computed['lead_days']) ? (int)$computed['lead_days'] : null;

  // References go to media_requests columns reference_url, reference_note
  $refUrl = trim((string)($references['url'] ?? ''));
  $refNote = trim((string)($references['note'] ?? ''));

  if ($refUrl !== '' && !is_valid_url($refUrl)) {
    json_fail(422, 'Reference URL must be a valid URL.');
  }

  // AV payload
  $av = $payload['av'] ?? null;
  $avRoomIds = [];
  $avItems = [];
  $rehearsalDate = null;
  $rehearsalStart = null;
  $rehearsalEnd = null;
  $avNote = null;

  if (in_array('av', $services, true)) {
    if (!is_array($av)) json_fail(422, 'AV details missing.');
    $avRoomIds = $av['room_ids'] ?? [];
    if (!is_array($avRoomIds) || count($avRoomIds) === 0) json_fail(422, 'Select at least one room for AV.');

    $avRoomIds = array_values(array_unique(array_map('intval', $avRoomIds)));

    $avItems = $av['items'] ?? [];
    if (!is_array($avItems)) $avItems = [];

    $rehearsalDate = ymd((string)($av['rehearsal_date'] ?? ''));
    $rehearsalStart = hm((string)($av['rehearsal_start_time'] ?? ''));
    $rehearsalEnd = hm((string)($av['rehearsal_end_time'] ?? ''));
    $avNote = trim((string)($av['note'] ?? ''));

    // sanitize items
    $cleanItems = [];
    foreach ($avItems as $it) {
      if (!is_array($it)) continue;
      $rid = isset($it['room_id']) ? (int)$it['room_id'] : null;
      $eid = isset($it['equipment_id']) ? (int)$it['equipment_id'] : null;
      $qty = isset($it['quantity']) ? (int)$it['quantity'] : 0;
      $note = trim((string)($it['note'] ?? ''));

      if (!$eid || $qty <= 0) continue;
      if ($rid !== null && $rid !== 0 && !in_array($rid, $avRoomIds, true)) continue;

      $cleanItems[] = [
        'room_id' => $rid ?: null,
        'equipment_id' => $eid,
        'quantity' => $qty,
        'note' => ($note === '' ? null : $note),
      ];
    }
    $avItems = $cleanItems;
  }

  // Media payload
  $media = $payload['media'] ?? null;
  $mediaDesc = null;
  $promoStart = null;
  $promoEnd = null;
  $captionDetails = null;
  $mediaNote = null;
  $platforms = [];
  if (in_array('media', $services, true)) {
    if (!is_array($media)) json_fail(422, 'Media details missing.');

    $mediaDesc = trim((string)($media['description'] ?? ''));
    if ($mediaDesc === '') json_fail(422, 'Media description is required.');

    $promoStart = ymd((string)($media['promo_start_date'] ?? ''));
    $promoEnd = ymd((string)($media['promo_end_date'] ?? ''));
    if ($promoStart && $promoEnd && $promoEnd < $promoStart) json_fail(422, 'Promo end date cannot be earlier than start date.');

    $captionDetails = trim((string)($media['caption_details'] ?? ''));
    $mediaNote = trim((string)($media['note'] ?? ''));

    $platformsIn = $media['platforms'] ?? [];
    if (!is_array($platformsIn)) $platformsIn = [];

    $validPlatforms = ['facebook','instagram','telegram','tiktok','youtube','whatsapp','website','other'];
    $seen = [];
    foreach ($platformsIn as $p) {
      if (!is_array($p)) continue;
      $plat = (string)($p['platform'] ?? '');
      if (!in_array($plat, $validPlatforms, true)) continue;
      if (isset($seen[$plat])) continue;
      $seen[$plat] = true;

      $otherLabel = null;
      if ($plat === 'other') {
        $otherLabel = trim((string)($p['other_label'] ?? ''));
        if ($otherLabel === '') $otherLabel = null;
      }

      $platforms[] = ['platform' => $plat, 'other_label' => $otherLabel];
    }
  }

  // Photo payload
  $photo = $payload['photo'] ?? null;
  $photoDate = null;
  $photoStart = null;
  $photoEnd = null;
  $photoNote = null;
  if (in_array('photo', $services, true)) {
    if (!is_array($photo)) json_fail(422, 'Photo details missing.');
    $photoDate = ymd((string)($photo['needed_date'] ?? ''));
    $photoStart = hm((string)($photo['start_time'] ?? ''));
    $photoEnd = hm((string)($photo['end_time'] ?? ''));
    $photoNote = trim((string)($photo['note'] ?? ''));
  }

  // Ready to write
  $pdo->beginTransaction();

  $year = (int)date('Y');
  $referenceNo = generate_reference_no($pdo, $year);

  // 1) media_requests
  $stmtReq = $pdo->prepare("
    INSERT INTO media_requests (
      reference_no,
      requestor_name, ministry, contact_no, email,
      has_required_approvals,
      event_name, event_description, event_location_note,
      reference_url, reference_note,
      request_status,
      is_late, lead_days,
      submitted_at, created_at, updated_at
    ) VALUES (
      :reference_no,
      :requestor_name, :ministry, :contact_no, :email,
      :has_required_approvals,
      :event_name, :event_description, :event_location_note,
      :reference_url, :reference_note,
      'pending',
      :is_late, :lead_days,
      :submitted_at, :created_at, :updated_at
    )
  ");

  $now = now_dt();
  $stmtReq->execute([
    ':reference_no' => $referenceNo,
    ':requestor_name' => $requestorName,
    ':ministry' => ($ministry === '' ? null : $ministry),
    ':contact_no' => $contactNo,
    ':email' => $email,
    ':has_required_approvals' => 1,

    ':event_name' => $eventName,
    ':event_description' => ($eventDesc === '' ? null : $eventDesc),
    ':event_location_note' => ($eventLoc === '' ? null : $eventLoc),

    ':reference_url' => ($refUrl === '' ? null : $refUrl),
    ':reference_note' => ($refNote === '' ? null : $refNote),

    ':is_late' => $isLate ? 1 : 0,
    ':lead_days' => $leadDays,

    ':submitted_at' => $now,
    ':created_at' => $now,
    ':updated_at' => $now
  ]);

  $mediaRequestId = (int)$pdo->lastInsertId();

  // 2) request_types
  $stmtType = $pdo->prepare("
    INSERT INTO request_types (media_request_id, type, approval_status, created_at, updated_at)
    VALUES (:rid, :type, 'pending', :created_at, :updated_at)
  ");
  foreach ($services as $t) {
    $stmtType->execute([
      ':rid' => $mediaRequestId,
      ':type' => $t,
      ':created_at' => $now,
      ':updated_at' => $now
    ]);
  }

  // 3) event_schedules
  $stmtSched = $pdo->prepare("
    INSERT INTO event_schedules (
      media_request_id,
      schedule_type,
      start_date, end_date,
      start_time, end_time,
      recurrence_pattern,
      recurrence_days_of_week,
      recurrence_interval,
      notes,
      created_at, updated_at
    ) VALUES (
      :rid,
      :schedule_type,
      :start_date, :end_date,
      :start_time, :end_time,
      :recurrence_pattern,
      :recurrence_dow,
      :recurrence_interval,
      :notes,
      :created_at, :updated_at
    )
  ");
  $stmtSched->execute([
    ':rid' => $mediaRequestId,
    ':schedule_type' => $scheduleType,
    ':start_date' => $startDate,
    ':end_date' => $endDate,
    ':start_time' => $startTime,
    ':end_time' => $endTime,
    ':recurrence_pattern' => ($scheduleType === 'recurring' ? $recurPattern : null),
    ':recurrence_dow' => ($scheduleType === 'recurring' ? $recurDOW : null),
    ':recurrence_interval' => ($scheduleType === 'recurring' ? $recurInterval : null),
    ':notes' => ($scheduleNotes === '' ? null : $scheduleNotes),
    ':created_at' => $now,
    ':updated_at' => $now
  ]);
  $eventScheduleId = (int)$pdo->lastInsertId();

  // 4) event_occurrences for custom list
  if ($scheduleType === 'custom_list') {
    $stmtOcc = $pdo->prepare("
      INSERT INTO event_occurrences (
        event_schedule_id,
        occurrence_date,
        start_time,
        end_time,
        notes,
        created_at
      ) VALUES (
        :sid,
        :d,
        :st,
        :et,
        NULL,
        :created_at
      )
    ");
    foreach ($occurrences as $o) {
      $stmtOcc->execute([
        ':sid' => $eventScheduleId,
        ':d' => $o['date'],
        ':st' => $o['start_time'],
        ':et' => $o['end_time'],
        ':created_at' => $now
      ]);
    }
  }

  // 5) AV: request_rooms, av_details, av_items
  if (in_array('av', $services, true)) {
    // Validate room IDs exist and active
    $in = implode(',', array_fill(0, count($avRoomIds), '?'));
    $stmtRooms = $pdo->prepare("
      SELECT id FROM rooms WHERE is_active = 1 AND id IN ($in)
    ");
    $stmtRooms->execute($avRoomIds);
    $activeRoomIds = array_map(fn($r) => (int)$r['id'], $stmtRooms->fetchAll());
    $activeSet = array_flip($activeRoomIds);

    foreach ($avRoomIds as $rid) {
      if (!isset($activeSet[$rid])) {
        json_fail(422, 'One or more selected rooms are not available.');
      }
    }

    $stmtReqRoom = $pdo->prepare("
      INSERT INTO request_rooms (media_request_id, room_id, notes)
      VALUES (:rid, :room_id, NULL)
    ");
    foreach ($avRoomIds as $rid) {
      $stmtReqRoom->execute([
        ':rid' => $mediaRequestId,
        ':room_id' => $rid
      ]);
    }

    $stmtAvDetails = $pdo->prepare("
      INSERT INTO av_details (
        media_request_id,
        rehearsal_date,
        rehearsal_start_time,
        rehearsal_end_time,
        note,
        av_internal_status,
        created_at,
        updated_at
      ) VALUES (
        :rid,
        :rd,
        :rst,
        :ret,
        :note,
        'pending',
        :created_at,
        :updated_at
      )
    ");
    $stmtAvDetails->execute([
      ':rid' => $mediaRequestId,
      ':rd' => $rehearsalDate,
      ':rst' => $rehearsalStart,
      ':ret' => $rehearsalEnd,
      ':note' => ($avNote === '' ? null : $avNote),
      ':created_at' => $now,
      ':updated_at' => $now
    ]);

    if (count($avItems) > 0) {
      // Validate equipment active
      $equipmentIds = array_values(array_unique(array_map(fn($x) => (int)$x['equipment_id'], $avItems)));
      $inEq = implode(',', array_fill(0, count($equipmentIds), '?'));
      $stmtEq = $pdo->prepare("SELECT id FROM equipment WHERE is_active = 1 AND id IN ($inEq)");
      $stmtEq->execute($equipmentIds);
      $activeEqIds = array_map(fn($r) => (int)$r['id'], $stmtEq->fetchAll());
      $eqSet = array_flip($activeEqIds);

      $stmtAvItem = $pdo->prepare("
        INSERT INTO av_items (media_request_id, room_id, equipment_id, quantity, note)
        VALUES (:rid, :room_id, :equipment_id, :qty, :note)
      ");

      foreach ($avItems as $it) {
        if (!isset($eqSet[(int)$it['equipment_id']])) {
          json_fail(422, 'One or more selected equipment items are not available.');
        }

        $stmtAvItem->execute([
          ':rid' => $mediaRequestId,
          ':room_id' => $it['room_id'],
          ':equipment_id' => $it['equipment_id'],
          ':qty' => $it['quantity'],
          ':note' => $it['note']
        ]);
      }
    }
  }

  // 6) Media: media_details + media_platforms
  if (in_array('media', $services, true)) {
    $stmtMedia = $pdo->prepare("
      INSERT INTO media_details (
        media_request_id,
        description,
        promo_start_date,
        promo_end_date,
        caption_details,
        note,
        created_at,
        updated_at
      ) VALUES (
        :rid,
        :desc,
        :ps,
        :pe,
        :cap,
        :note,
        :created_at,
        :updated_at
      )
    ");
    $stmtMedia->execute([
      ':rid' => $mediaRequestId,
      ':desc' => ($mediaDesc === '' ? null : $mediaDesc),
      ':ps' => $promoStart,
      ':pe' => $promoEnd,
      ':cap' => ($captionDetails === '' ? null : $captionDetails),
      ':note' => ($mediaNote === '' ? null : $mediaNote),
      ':created_at' => $now,
      ':updated_at' => $now
    ]);

    if (count($platforms) > 0) {
      $stmtPlat = $pdo->prepare("
        INSERT INTO media_platforms (
          media_request_id,
          platform,
          platform_other_label
        ) VALUES (
          :rid,
          :platform,
          :other_label
        )
      ");
      foreach ($platforms as $p) {
        $stmtPlat->execute([
          ':rid' => $mediaRequestId,
          ':platform' => $p['platform'],
          ':other_label' => $p['other_label']
        ]);
      }
    }
  }

  // 7) Photo: photo_details
  if (in_array('photo', $services, true)) {
    $stmtPhoto = $pdo->prepare("
      INSERT INTO photo_details (
        media_request_id,
        needed_date,
        start_time,
        end_time,
        note,
        photo_internal_status,
        created_at,
        updated_at
      ) VALUES (
        :rid,
        :d,
        :st,
        :et,
        :note,
        'pending',
        :created_at,
        :updated_at
      )
    ");
    $stmtPhoto->execute([
      ':rid' => $mediaRequestId,
      ':d' => $photoDate,
      ':st' => $photoStart,
      ':et' => $photoEnd,
      ':note' => ($photoNote === '' ? null : $photoNote),
      ':created_at' => $now,
      ':updated_at' => $now
    ]);
  }

  // 8) audit_logs
  $ip = get_ip();
  $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');

  $stmtAudit = $pdo->prepare("
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
      :actor_name_snapshot,
      'CREATE_REQUEST',
      'media_requests',
      :entity_id,
      NULL,
      :after_json,
      :ip,
      :ua,
      :created_at
    )
  ");

  // keep after_json minimal to avoid storing too much sensitive data
  $after = [
    'reference_no' => $referenceNo,
    'services' => $services,
    'schedule_type' => $scheduleType,
    'is_late' => $isLate,
    'lead_days' => $leadDays
  ];

  $stmtAudit->execute([
    ':actor_name_snapshot' => $requestorName,
    ':entity_id' => $mediaRequestId,
    ':after_json' => json_encode($after, JSON_UNESCAPED_SLASHES),
    ':ip' => $ip,
    ':ua' => $ua,
    ':created_at' => $now
  ]);

  $pdo->commit();

  // Send notifications (after commit, don't block on failure)
  try {
    // Prepare data for notifications
    $notificationRequest = [
      'id' => $mediaRequestId,
      'reference_no' => $referenceNo,
      'requestor_name' => $requestorName,
      'email' => $email,
      'contact_no' => $contactNo,
      'ministry' => $ministry,
      'event_name' => $eventName,
      'event_description' => $eventDesc,
      'event_location_note' => $eventLoc,
      'event_dates' => $startDate . ($endDate && $endDate !== $startDate ? ' to ' . $endDate : ''),
      'event_times' => ($startTime ? substr($startTime, 0, 5) : '') . ($endTime ? ' - ' . substr($endTime, 0, 5) : ''),
    ];

    // Build details array for Telegram
    $notificationDetails = [];

    if (in_array('av', $services, true)) {
      $notificationDetails['av'] = [
        'rehearsal_date' => $rehearsalDate,
        'rehearsal_start_time' => $rehearsalStart,
        'rehearsal_end_time' => $rehearsalEnd,
        'note' => $avNote,
      ];
      // Get equipment by room for display
      $equipmentByRoom = [];
      if (!empty($avItems)) {
        foreach ($avItems as $it) {
          $roomName = 'General';
          if ($it['room_id']) {
            $stmtRoom = $pdo->prepare("SELECT name FROM rooms WHERE id = ?");
            $stmtRoom->execute([$it['room_id']]);
            $roomRow = $stmtRoom->fetch();
            if ($roomRow) $roomName = $roomRow['name'];
          }
          $stmtEqName = $pdo->prepare("SELECT name FROM equipment WHERE id = ?");
          $stmtEqName->execute([$it['equipment_id']]);
          $eqRow = $stmtEqName->fetch();
          $eqName = $eqRow ? $eqRow['name'] : 'Unknown';
          $equipmentByRoom[$roomName][] = $eqName . ($it['quantity'] > 1 ? " x{$it['quantity']}" : '');
        }
      }
      $notificationDetails['equipment'] = $equipmentByRoom;
    }

    if (in_array('media', $services, true)) {
      $notificationDetails['media'] = [
        'description' => $mediaDesc,
        'promo_start_date' => $promoStart,
        'promo_end_date' => $promoEnd,
        'caption_details' => $captionDetails,
        'note' => $mediaNote,
      ];
      $notificationDetails['platforms'] = array_map(fn($p) => $p['platform'], $platforms);
    }

    if (in_array('photo', $services, true)) {
      $notificationDetails['photo'] = [
        'needed_date' => $photoDate,
        'start_time' => $photoStart,
        'end_time' => $photoEnd,
        'note' => $photoNote,
      ];
    }

    // Send Telegram notification to admin group
    sendNewRequestNotification($notificationRequest, $services, $notificationDetails);

    // Send confirmation email to requestor
    sendConfirmationEmail($notificationRequest, $services);

  } catch (Throwable $notifyError) {
    // Log but don't fail the request
    error_log("Notification error: " . $notifyError->getMessage());
  }

  echo json_encode([
    'ok' => true,
    'reference_no' => $referenceNo
  ], JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  json_fail(500, 'Server error. Please try again.');
}
