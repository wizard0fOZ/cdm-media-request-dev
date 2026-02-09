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
if ($requestId <= 0) {
    http_response_code(400);
    exit('Invalid parameters');
}

// Permission: must be able to approve or assign media service
if (!can_approve_service('media') && !can_assign_pic('media')) {
    http_response_code(403);
    exit('Access denied');
}

$user = current_user();

// 1. Fetch request
$stmt = $pdo->prepare('SELECT id, event_name, reference_no FROM media_requests WHERE id = :id');
$stmt->execute(['id' => $requestId]);
$request = $stmt->fetch();
if (!$request) {
    http_response_code(404);
    exit('Request not found');
}

// 2. Check if content already generated
$stmt = $pdo->prepare('SELECT COUNT(*) FROM content_items WHERE media_request_id = :id');
$stmt->execute(['id' => $requestId]);
if ((int) $stmt->fetchColumn() > 0) {
    header('Location: ../view.php?id=' . $requestId . '&content_exists=1');
    exit;
}

// 3. Fetch media_details
$stmt = $pdo->prepare('SELECT * FROM media_details WHERE media_request_id = :id');
$stmt->execute(['id' => $requestId]);
$mediaDetails = $stmt->fetch();

// 4. Fetch media_platforms
$stmt = $pdo->prepare('SELECT platform, platform_other_label FROM media_platforms WHERE media_request_id = :id');
$stmt->execute(['id' => $requestId]);
$platforms = $stmt->fetchAll();

// 5. Map platforms → content channels
$platformToChannel = [
    'facebook'  => 'facebook',
    'instagram' => 'instagram',
    'telegram'  => 'telegram',
    'tiktok'    => 'tiktok',
    'youtube'   => 'youtube',
];

$otherLabelMap = [
    'bulletin'      => 'bulletin',
    'av_projection' => 'av_projection',
    'av projection' => 'av_projection',
    'cm'            => 'cm',
];

$channels = [];
foreach ($platforms as $p) {
    $plat = $p['platform'];
    if (isset($platformToChannel[$plat])) {
        $channels[] = $platformToChannel[$plat];
    } elseif ($plat === 'other' && $p['platform_other_label']) {
        $label = strtolower(trim($p['platform_other_label']));
        if (isset($otherLabelMap[$label])) {
            $channels[] = $otherLabelMap[$label];
        }
    }
    // Skip whatsapp, website — no content_channel equivalent
}

try {
    $pdo->beginTransaction();

    // 6. Create content_item
    $stmt = $pdo->prepare('
        INSERT INTO content_items
            (media_request_id, title, content_type, promo_start_date, promo_end_date,
             caption_brief, asset_status, caption_status, created_at, updated_at)
        VALUES
            (:rid, :title, :ctype, :pstart, :pend, :caption, :astatus, :cstatus, NOW(), NOW())
    ');
    $stmt->execute([
        'rid'     => $requestId,
        'title'   => $request['event_name'],
        'ctype'   => 'poster',
        'pstart'  => $mediaDetails['promo_start_date'] ?? null,
        'pend'    => $mediaDetails['promo_end_date'] ?? null,
        'caption' => $mediaDetails['caption_details'] ?? null,
        'astatus' => 'pending',
        'cstatus' => ($mediaDetails['caption_details'] ?? null) ? 'pending' : 'na',
    ]);
    $contentItemId = (int) $pdo->lastInsertId();

    // 7. Create content_channels
    if (!empty($channels)) {
        $channelStmt = $pdo->prepare('
            INSERT INTO content_channels
                (content_item_id, channel, status, updated_at)
            VALUES
                (:item_id, :channel, :status, NOW())
        ');
        foreach ($channels as $ch) {
            $channelStmt->execute([
                'item_id' => $contentItemId,
                'channel' => $ch,
                'status'  => 'pending',
            ]);
        }
    }

    // 8. Audit log
    write_audit_log($pdo, 'generate_content', 'content_item', $contentItemId, null, [
        'media_request_id' => $requestId,
        'title'            => $request['event_name'],
        'channels'         => $channels,
    ]);

    $pdo->commit();
} catch (Throwable $ex) {
    $pdo->rollBack();
    error_log('generate_content error: ' . $ex->getMessage());
    http_response_code(500);
    exit('An error occurred');
}

header('Location: ../content.php?request_id=' . $requestId . '&generated=1');
exit;
