<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_admin_auth();
require_once __DIR__ . '/../../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

if (!can_manage_content()) {
    http_response_code(403);
    exit('Access denied');
}

$user = current_user();

$contentItemId = ($_POST['content_item_id'] ?? '') === '' ? null : (int) $_POST['content_item_id'];
$isQuickStatus = isset($_POST['quick_status']);

// ---------------------------------------------------------------------------
// Quick status update (inline badge click)
// ---------------------------------------------------------------------------
if ($isQuickStatus && $contentItemId) {
    $assetStatus = $_POST['asset_status'] ?? '';
    $allowed = ['na', 'pending', 'in_progress', 'ready', 'done'];
    if (!in_array($assetStatus, $allowed, true)) {
        http_response_code(400);
        exit('Invalid status');
    }

    $stmt = $pdo->prepare('UPDATE content_items SET asset_status = :s, updated_at = NOW() WHERE id = :id');
    $stmt->execute(['s' => $assetStatus, 'id' => $contentItemId]);

    write_audit_log($pdo, 'quick_status_content', 'content_item', $contentItemId, null, [
        'asset_status' => $assetStatus,
    ]);

    // Redirect back to content page
    $referer = $_SERVER['HTTP_REFERER'] ?? '../content.php';
    header('Location: ' . $referer);
    exit;
}

// ---------------------------------------------------------------------------
// Full create / update
// ---------------------------------------------------------------------------
$title         = trim($_POST['title'] ?? '');
$contentType   = $_POST['content_type'] ?? 'poster';
$language      = trim($_POST['language'] ?? '') ?: null;
$promoStart    = ($_POST['promo_start_date'] ?? '') ?: null;
$promoEnd      = ($_POST['promo_end_date'] ?? '') ?: null;
$defaultPublish = ($_POST['default_publish_at'] ?? '') ?: null;
$captionBrief  = trim($_POST['caption_brief'] ?? '') ?: null;
$finalCaption  = trim($_POST['final_caption'] ?? '') ?: null;
$captionStatus = $_POST['caption_status'] ?? 'na';
$assetUrl      = trim($_POST['asset_url'] ?? '') ?: null;
$assetStatus   = $_POST['asset_status'] ?? 'pending';
$notes         = trim($_POST['notes'] ?? '') ?: null;
$doNotDisplay  = isset($_POST['do_not_display']) ? 1 : 0;
$assetPicId    = ($_POST['asset_pic_user_id'] ?? '') === '' ? null : (int) $_POST['asset_pic_user_id'];
$socmedPicId   = ($_POST['socmed_pic_user_id'] ?? '') === '' ? null : (int) $_POST['socmed_pic_user_id'];
$mediaRequestId = ($_POST['media_request_id'] ?? '') === '' ? null : (int) $_POST['media_request_id'];

// Channels from form
$channelsList   = $_POST['channels'] ?? [];
$channelPublish = $_POST['channel_publish'] ?? [];
$channelStatus  = $_POST['channel_status'] ?? [];

// Validate
if ($title === '') {
    http_response_code(400);
    exit('Title is required');
}

$allowedTypes = ['poster', 'video', 'story', 'reel', 'article', 'slide', 'other'];
if (!in_array($contentType, $allowedTypes, true)) {
    $contentType = 'poster';
}

$allowedAssetStatus = ['na', 'pending', 'in_progress', 'ready', 'done'];
if (!in_array($assetStatus, $allowedAssetStatus, true)) {
    $assetStatus = 'pending';
}

$allowedCaptionStatus = ['na', 'pending', 'done'];
if (!in_array($captionStatus, $allowedCaptionStatus, true)) {
    $captionStatus = 'na';
}

// Resolve PIC name snapshots
$assetPicSnapshot = null;
$socmedPicSnapshot = null;
if ($assetPicId) {
    $stmt = $pdo->prepare('SELECT name FROM users WHERE id = :id');
    $stmt->execute(['id' => $assetPicId]);
    $assetPicSnapshot = $stmt->fetchColumn() ?: null;
}
if ($socmedPicId) {
    $stmt = $pdo->prepare('SELECT name FROM users WHERE id = :id');
    $stmt->execute(['id' => $socmedPicId]);
    $socmedPicSnapshot = $stmt->fetchColumn() ?: null;
}

try {
    $pdo->beginTransaction();

    if ($contentItemId) {
        // UPDATE existing
        $stmt = $pdo->prepare('
            UPDATE content_items SET
                title = :title,
                content_type = :ctype,
                language = :lang,
                promo_start_date = :pstart,
                promo_end_date = :pend,
                default_publish_at = :dpub,
                caption_brief = :cb,
                final_caption = :fc,
                caption_status = :cs,
                asset_url = :aurl,
                asset_status = :astatus,
                notes = :notes,
                do_not_display = :dnd,
                asset_pic_user_id = :apic,
                asset_pic_name_snapshot = :apic_name,
                socmed_pic_user_id = :spic,
                socmed_pic_name_snapshot = :spic_name,
                updated_at = NOW()
            WHERE id = :id
        ');
        $stmt->execute([
            'title'     => $title,
            'ctype'     => $contentType,
            'lang'      => $language,
            'pstart'    => $promoStart,
            'pend'      => $promoEnd,
            'dpub'      => $defaultPublish,
            'cb'        => $captionBrief,
            'fc'        => $finalCaption,
            'cs'        => $captionStatus,
            'aurl'      => $assetUrl,
            'astatus'   => $assetStatus,
            'notes'     => $notes,
            'dnd'       => $doNotDisplay,
            'apic'      => $assetPicId,
            'apic_name' => $assetPicSnapshot,
            'spic'      => $socmedPicId,
            'spic_name' => $socmedPicSnapshot,
            'id'        => $contentItemId,
        ]);

        // Delete existing channels and re-insert
        $pdo->prepare('DELETE FROM content_channels WHERE content_item_id = :id')
            ->execute(['id' => $contentItemId]);

        $action = 'update_content';
    } else {
        // INSERT new
        $stmt = $pdo->prepare('
            INSERT INTO content_items
                (media_request_id, title, content_type, language,
                 promo_start_date, promo_end_date, default_publish_at,
                 caption_brief, final_caption, caption_status,
                 asset_url, asset_status, notes, do_not_display,
                 asset_pic_user_id, asset_pic_name_snapshot,
                 socmed_pic_user_id, socmed_pic_name_snapshot,
                 created_at, updated_at)
            VALUES
                (:rid, :title, :ctype, :lang,
                 :pstart, :pend, :dpub,
                 :cb, :fc, :cs,
                 :aurl, :astatus, :notes, :dnd,
                 :apic, :apic_name,
                 :spic, :spic_name,
                 NOW(), NOW())
        ');
        $stmt->execute([
            'rid'       => $mediaRequestId,
            'title'     => $title,
            'ctype'     => $contentType,
            'lang'      => $language,
            'pstart'    => $promoStart,
            'pend'      => $promoEnd,
            'dpub'      => $defaultPublish,
            'cb'        => $captionBrief,
            'fc'        => $finalCaption,
            'cs'        => $captionStatus,
            'aurl'      => $assetUrl,
            'astatus'   => $assetStatus,
            'notes'     => $notes,
            'dnd'       => $doNotDisplay,
            'apic'      => $assetPicId,
            'apic_name' => $assetPicSnapshot,
            'spic'      => $socmedPicId,
            'spic_name' => $socmedPicSnapshot,
        ]);
        $contentItemId = (int) $pdo->lastInsertId();
        $action = 'create_content';
    }

    // Insert channels
    $allowedChannels = ['facebook', 'instagram', 'telegram', 'tiktok', 'youtube', 'bulletin', 'av_projection', 'cm'];
    $allowedChStatus = ['na', 'pending', 'scheduled', 'posted', 'done'];

    $chStmt = $pdo->prepare('
        INSERT INTO content_channels
            (content_item_id, channel, status, publish_at, updated_by_user_id, updated_by_name_snapshot, updated_at)
        VALUES
            (:item_id, :channel, :status, :pub_at, :uid, :uname, NOW())
    ');

    foreach ($channelsList as $ch) {
        if (!in_array($ch, $allowedChannels, true)) continue;

        $chSt = $channelStatus[$ch] ?? 'pending';
        if (!in_array($chSt, $allowedChStatus, true)) $chSt = 'pending';

        // Non-social channels can only be na/pending/done
        $nonSocial = ['bulletin', 'av_projection', 'cm'];
        if (in_array($ch, $nonSocial, true) && !in_array($chSt, ['na', 'pending', 'done'], true)) {
            $chSt = 'pending';
        }

        $pubAt = ($channelPublish[$ch] ?? '') ?: null;

        $chStmt->execute([
            'item_id' => $contentItemId,
            'channel' => $ch,
            'status'  => $chSt,
            'pub_at'  => $pubAt,
            'uid'     => $user['id'],
            'uname'   => $user['name'],
        ]);
    }

    write_audit_log($pdo, $action, 'content_item', $contentItemId, null, [
        'title'        => $title,
        'asset_status' => $assetStatus,
        'channels'     => $channelsList,
    ]);

    $pdo->commit();
} catch (Throwable $ex) {
    $pdo->rollBack();
    error_log('save_content error: ' . $ex->getMessage());
    http_response_code(500);
    exit('An error occurred');
}

header('Location: ../content.php?success=1');
exit;
