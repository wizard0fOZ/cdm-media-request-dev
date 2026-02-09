<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_admin_auth();
require_once __DIR__ . '/../../../includes/db.php';

header('Content-Type: application/json');

// FullCalendar sends start/end as ISO dates
$rangeStart = $_GET['start'] ?? '';
$rangeEnd   = $_GET['end'] ?? '';

if ($rangeStart === '' || $rangeEnd === '') {
    echo json_encode([]);
    exit;
}

// Normalize to Y-m-d
$rangeStart = substr($rangeStart, 0, 10);
$rangeEnd   = substr($rangeEnd, 0, 10);

// Optional filters
$filterStatus  = $_GET['status'] ?? '';
$filterService = $_GET['service'] ?? '';
$filterLate    = ($_GET['late'] ?? '') === '1';

// ---------------------------------------------------------------------------
// Build request filter conditions
// ---------------------------------------------------------------------------
$reqWhere  = [];
$reqParams = [];

if ($filterStatus !== '') {
    $reqWhere[] = 'mr.request_status = :status';
    $reqParams['status'] = $filterStatus;
}

if ($filterLate) {
    $reqWhere[] = 'mr.is_late = 1';
}

if ($filterService !== '') {
    $reqWhere[] = 'EXISTS (SELECT 1 FROM request_types rt_f WHERE rt_f.media_request_id = mr.id AND rt_f.type = :svc)';
    $reqParams['svc'] = $filterService;
}

$reqWhereSQL = $reqWhere ? ' AND ' . implode(' AND ', $reqWhere) : '';

// ---------------------------------------------------------------------------
// Status → color mapping
// ---------------------------------------------------------------------------
$statusColors = [
    'pending'     => '#f59e0b',
    'approved'    => '#22c55e',
    'in_progress' => '#3b82f6',
    'completed'   => '#10b981',
    'rejected'    => '#ef4444',
    'cancelled'   => '#94a3b8',
];

$events = [];

// ---------------------------------------------------------------------------
// 1) Single + custom_list occurrences (from event_occurrences)
// ---------------------------------------------------------------------------
$sql = "
    SELECT
        mr.id AS request_id,
        mr.reference_no,
        mr.event_name,
        mr.request_status,
        mr.is_late,
        eo.occurrence_date,
        eo.start_time,
        eo.end_time,
        GROUP_CONCAT(DISTINCT rt.type ORDER BY rt.type) AS services
    FROM event_occurrences eo
    JOIN event_schedules es ON eo.event_schedule_id = es.id
    JOIN media_requests mr ON es.media_request_id = mr.id
    LEFT JOIN request_types rt ON mr.id = rt.media_request_id
    WHERE eo.occurrence_date >= :range_start
      AND eo.occurrence_date <= :range_end
      $reqWhereSQL
    GROUP BY eo.id
    ORDER BY eo.occurrence_date, eo.start_time
";

$params = array_merge(['range_start' => $rangeStart, 'range_end' => $rangeEnd], $reqParams);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

foreach ($stmt->fetchAll() as $row) {
    $events[] = formatEvent($row, $row['occurrence_date'], $row['start_time'], $row['end_time'], $statusColors);
}

// ---------------------------------------------------------------------------
// 2) Single schedules without occurrences (start_date only)
// ---------------------------------------------------------------------------
$sql = "
    SELECT
        mr.id AS request_id,
        mr.reference_no,
        mr.event_name,
        mr.request_status,
        mr.is_late,
        es.start_date,
        es.start_time,
        es.end_time,
        GROUP_CONCAT(DISTINCT rt.type ORDER BY rt.type) AS services
    FROM event_schedules es
    JOIN media_requests mr ON es.media_request_id = mr.id
    LEFT JOIN request_types rt ON mr.id = rt.media_request_id
    WHERE es.schedule_type = 'single'
      AND es.start_date >= :range_start
      AND es.start_date <= :range_end
      AND NOT EXISTS (SELECT 1 FROM event_occurrences eo2 WHERE eo2.event_schedule_id = es.id)
      $reqWhereSQL
    GROUP BY es.id
    ORDER BY es.start_date, es.start_time
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

foreach ($stmt->fetchAll() as $row) {
    $events[] = formatEvent($row, $row['start_date'], $row['start_time'], $row['end_time'], $statusColors);
}

// ---------------------------------------------------------------------------
// 3) Recurring schedules — generate occurrences on-the-fly
// ---------------------------------------------------------------------------
$sql = "
    SELECT
        mr.id AS request_id,
        mr.reference_no,
        mr.event_name,
        mr.request_status,
        mr.is_late,
        es.start_date,
        es.end_date,
        es.start_time,
        es.end_time,
        es.recurrence_pattern,
        es.recurrence_days_of_week,
        GROUP_CONCAT(DISTINCT rt.type ORDER BY rt.type) AS services
    FROM event_schedules es
    JOIN media_requests mr ON es.media_request_id = mr.id
    LEFT JOIN request_types rt ON mr.id = rt.media_request_id
    WHERE es.schedule_type = 'recurring'
      AND es.start_date <= :range_end
      AND COALESCE(es.end_date, es.start_date) >= :range_start
      $reqWhereSQL
    GROUP BY es.id
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$dayMap = [
    'sun' => 0, 'sunday' => 0,
    'mon' => 1, 'monday' => 1,
    'tue' => 2, 'tuesday' => 2,
    'wed' => 3, 'wednesday' => 3,
    'thu' => 4, 'thursday' => 4,
    'fri' => 5, 'friday' => 5,
    'sat' => 6, 'saturday' => 6,
];

foreach ($stmt->fetchAll() as $row) {
    $schedStart = $row['start_date'];
    $schedEnd   = $row['end_date'] ?: $row['start_date'];

    // Parse target days of week
    $targetDays = [];
    $daysStr = strtolower(trim($row['recurrence_days_of_week'] ?? ''));
    foreach (preg_split('/[\s,]+/', $daysStr) as $d) {
        $d = trim($d);
        if (isset($dayMap[$d])) {
            $targetDays[] = $dayMap[$d];
        }
    }

    if (empty($targetDays)) continue;

    // Generate occurrences within the visible range
    $iterStart = max($schedStart, $rangeStart);
    $iterEnd   = min($schedEnd, $rangeEnd);

    $current = new DateTime($iterStart);
    $end     = new DateTime($iterEnd);

    $interval = match ($row['recurrence_pattern']) {
        'biweekly' => 14,
        'monthly'  => null, // handled differently
        default    => 1,    // weekly: iterate daily, check day match
    };

    while ($current <= $end) {
        $dow = (int) $current->format('w');
        if (in_array($dow, $targetDays, true)) {
            $events[] = formatEvent($row, $current->format('Y-m-d'), $row['start_time'], $row['end_time'], $statusColors);

            // For biweekly, skip ahead 13 more days (total 14)
            if ($row['recurrence_pattern'] === 'biweekly') {
                $current->modify('+13 days');
            }
        }
        $current->modify('+1 day');
    }
}

echo json_encode($events);

// ---------------------------------------------------------------------------
// Helper
// ---------------------------------------------------------------------------
function formatEvent(array $row, string $date, ?string $startTime, ?string $endTime, array $statusColors): array {
    $start = $date;
    $end   = null;

    if ($startTime) {
        $start = $date . 'T' . $startTime;
    }
    if ($endTime) {
        $end = $date . 'T' . $endTime;
    }

    $svcs = $row['services'] ? explode(',', $row['services']) : [];

    return [
        'id'    => 'req-' . $row['request_id'] . '-' . $date,
        'title' => $row['reference_no'] . ' — ' . $row['event_name'],
        'start' => $start,
        'end'   => $end,
        'url'   => 'view.php?id=' . $row['request_id'],
        'color' => $statusColors[$row['request_status']] ?? '#94a3b8',
        'extendedProps' => [
            'requestId'   => (int) $row['request_id'],
            'referenceNo' => $row['reference_no'],
            'status'      => $row['request_status'],
            'services'    => $svcs,
            'isLate'      => (bool) $row['is_late'],
        ],
    ];
}
