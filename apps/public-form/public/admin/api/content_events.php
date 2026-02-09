<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_admin_auth();
require_once __DIR__ . '/../../../includes/db.php';

header('Content-Type: application/json');

$rangeStart = $_GET['start'] ?? '';
$rangeEnd   = $_GET['end'] ?? '';

if ($rangeStart === '' || $rangeEnd === '') {
    echo json_encode([]);
    exit;
}

$rangeStart = substr($rangeStart, 0, 10);
$rangeEnd   = substr($rangeEnd, 0, 10);

// Optional filters
$filterAssetStatus = $_GET['asset_status'] ?? '';
$filterContentType = $_GET['content_type'] ?? '';
$filterChannel     = $_GET['channel'] ?? '';

// ---------------------------------------------------------------------------
// Build filter conditions
// ---------------------------------------------------------------------------
$where  = [];
$params = ['range_start' => $rangeStart, 'range_end' => $rangeEnd];

if ($filterAssetStatus !== '') {
    $where[] = 'ci.asset_status = :asset_status';
    $params['asset_status'] = $filterAssetStatus;
}

if ($filterContentType !== '') {
    $where[] = 'ci.content_type = :content_type';
    $params['content_type'] = $filterContentType;
}

if ($filterChannel !== '') {
    $where[] = 'cc.channel = :channel';
    $params['channel'] = $filterChannel;
}

$whereSQL = $where ? ' AND ' . implode(' AND ', $where) : '';

// ---------------------------------------------------------------------------
// Status → color mapping (by asset_status)
// ---------------------------------------------------------------------------
$statusColors = [
    'na'          => '#94a3b8',
    'pending'     => '#f59e0b',
    'in_progress' => '#3b82f6',
    'ready'       => '#8b5cf6',
    'done'        => '#10b981',
];

// ---------------------------------------------------------------------------
// Query: each content_channel becomes a calendar event
// Date used: channel publish_at > default_publish_at > promo_start_date
// ---------------------------------------------------------------------------
$sql = "
    SELECT
        ci.id AS item_id,
        ci.title,
        ci.content_type,
        ci.asset_status,
        ci.media_request_id,
        mr.reference_no,
        cc.id AS channel_id,
        cc.channel,
        cc.status AS channel_status,
        cc.publish_at AS channel_publish_at,
        ci.default_publish_at,
        ci.promo_start_date
    FROM content_channels cc
    JOIN content_items ci ON cc.content_item_id = ci.id
    LEFT JOIN media_requests mr ON ci.media_request_id = mr.id
    WHERE COALESCE(DATE(cc.publish_at), DATE(ci.default_publish_at), ci.promo_start_date) >= :range_start
      AND COALESCE(DATE(cc.publish_at), DATE(ci.default_publish_at), ci.promo_start_date) <= :range_end
      $whereSQL
    ORDER BY COALESCE(cc.publish_at, ci.default_publish_at, ci.promo_start_date)
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$events = [];
foreach ($stmt->fetchAll() as $row) {
    // Determine effective date/time
    $start = null;
    if ($row['channel_publish_at']) {
        $start = $row['channel_publish_at'];
    } elseif ($row['default_publish_at']) {
        $start = $row['default_publish_at'];
    } elseif ($row['promo_start_date']) {
        $start = $row['promo_start_date'];
    }

    if (!$start) continue;

    $channelLabels = [
        'facebook' => 'FB', 'instagram' => 'IG', 'telegram' => 'TG',
        'tiktok' => 'TT', 'youtube' => 'YT', 'bulletin' => 'Bul',
        'av_projection' => 'AV', 'cm' => 'CM',
    ];
    $chShort = $channelLabels[$row['channel']] ?? ucfirst($row['channel']);

    $events[] = [
        'id'    => 'content-' . $row['item_id'] . '-' . $row['channel'],
        'title' => '[' . $chShort . '] ' . $row['title'],
        'start' => $start,
        'url'   => 'content.php?request_id=' . ($row['media_request_id'] ?: ''),
        'color' => $statusColors[$row['asset_status']] ?? '#94a3b8',
        'extendedProps' => [
            'itemId'        => (int) $row['item_id'],
            'channel'       => $row['channel'],
            'channelStatus' => $row['channel_status'],
            'assetStatus'   => $row['asset_status'],
            'contentType'   => $row['content_type'],
            'referenceNo'   => $row['reference_no'],
        ],
    ];
}

echo json_encode($events);
