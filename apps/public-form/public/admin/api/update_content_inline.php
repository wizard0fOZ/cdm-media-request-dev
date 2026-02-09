<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_admin_auth();
require_once __DIR__ . '/../../../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!can_manage_content()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

$user = current_user();
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['content_item_id']) || empty($input['field_type'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$contentItemId = (int) $input['content_item_id'];
$fieldType = $input['field_type'];

// Verify content item exists
$stmt = $pdo->prepare('SELECT id FROM content_items WHERE id = :id');
$stmt->execute(['id' => $contentItemId]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Content item not found']);
    exit;
}

try {
    switch ($fieldType) {

        // -----------------------------------------------------------------
        case 'asset_status':
            $value = $input['value'] ?? '';
            $allowed = ['na', 'pending', 'in_progress', 'ready', 'done'];
            if (!in_array($value, $allowed, true)) {
                throw new InvalidArgumentException('Invalid asset status');
            }

            $before = $pdo->prepare('SELECT asset_status FROM content_items WHERE id = :id');
            $before->execute(['id' => $contentItemId]);
            $beforeVal = $before->fetchColumn();

            $stmt = $pdo->prepare('UPDATE content_items SET asset_status = :v, updated_at = NOW() WHERE id = :id');
            $stmt->execute(['v' => $value, 'id' => $contentItemId]);

            write_audit_log($pdo, 'inline_update_asset_status', 'content_item', $contentItemId,
                ['asset_status' => $beforeVal], ['asset_status' => $value]);
            echo json_encode(['success' => true, 'value' => $value]);
            break;

        // -----------------------------------------------------------------
        case 'caption_status':
            $value = $input['value'] ?? '';
            $allowed = ['na', 'pending', 'done'];
            if (!in_array($value, $allowed, true)) {
                throw new InvalidArgumentException('Invalid caption status');
            }

            $before = $pdo->prepare('SELECT caption_status FROM content_items WHERE id = :id');
            $before->execute(['id' => $contentItemId]);
            $beforeVal = $before->fetchColumn();

            $stmt = $pdo->prepare('UPDATE content_items SET caption_status = :v, updated_at = NOW() WHERE id = :id');
            $stmt->execute(['v' => $value, 'id' => $contentItemId]);

            write_audit_log($pdo, 'inline_update_caption_status', 'content_item', $contentItemId,
                ['caption_status' => $beforeVal], ['caption_status' => $value]);
            echo json_encode(['success' => true, 'value' => $value]);
            break;

        // -----------------------------------------------------------------
        case 'channel_toggle':
            $channel = $input['channel'] ?? '';
            $action  = $input['action'] ?? '';
            $allowedChannels = ['facebook', 'instagram', 'telegram', 'tiktok', 'youtube', 'bulletin', 'av_projection', 'cm'];

            if (!in_array($channel, $allowedChannels, true) || !in_array($action, ['add', 'remove'], true)) {
                throw new InvalidArgumentException('Invalid channel or action');
            }

            $pdo->beginTransaction();

            if ($action === 'add') {
                $stmt = $pdo->prepare('
                    INSERT INTO content_channels (content_item_id, channel, status, updated_by_user_id, updated_by_name_snapshot, updated_at)
                    VALUES (:item_id, :ch, :status, :uid, :uname, NOW())
                    ON DUPLICATE KEY UPDATE status = VALUES(status), updated_by_user_id = VALUES(updated_by_user_id), updated_at = NOW()
                ');
                $stmt->execute([
                    'item_id' => $contentItemId,
                    'ch'      => $channel,
                    'status'  => 'pending',
                    'uid'     => $user['id'],
                    'uname'   => $user['name'],
                ]);

                write_audit_log($pdo, 'inline_add_channel', 'content_item', $contentItemId,
                    null, ['channel' => $channel, 'status' => 'pending']);
                $pdo->commit();
                echo json_encode(['success' => true, 'channel' => $channel, 'status' => 'pending']);
            } else {
                $stmt = $pdo->prepare('DELETE FROM content_channels WHERE content_item_id = :item_id AND channel = :ch');
                $stmt->execute(['item_id' => $contentItemId, 'ch' => $channel]);

                write_audit_log($pdo, 'inline_remove_channel', 'content_item', $contentItemId,
                    ['channel' => $channel], null);
                $pdo->commit();
                echo json_encode(['success' => true, 'channel' => $channel, 'removed' => true]);
            }
            break;

        // -----------------------------------------------------------------
        case 'channel_status':
            $channel = $input['channel'] ?? '';
            $status  = $input['status'] ?? '';
            $allowedChannels = ['facebook', 'instagram', 'telegram', 'tiktok', 'youtube', 'bulletin', 'av_projection', 'cm'];
            $allowedStatus   = ['na', 'pending', 'scheduled', 'posted', 'done'];
            $nonSocial       = ['bulletin', 'av_projection', 'cm'];

            if (!in_array($channel, $allowedChannels, true) || !in_array($status, $allowedStatus, true)) {
                throw new InvalidArgumentException('Invalid channel or status');
            }

            if (in_array($channel, $nonSocial, true) && !in_array($status, ['na', 'pending', 'done'], true)) {
                throw new InvalidArgumentException('Non-social channels only support na/pending/done');
            }

            $before = $pdo->prepare('SELECT status FROM content_channels WHERE content_item_id = :item_id AND channel = :ch');
            $before->execute(['item_id' => $contentItemId, 'ch' => $channel]);
            $beforeVal = $before->fetchColumn();

            $stmt = $pdo->prepare('
                UPDATE content_channels
                SET status = :s, updated_by_user_id = :uid, updated_by_name_snapshot = :uname, updated_at = NOW()
                WHERE content_item_id = :item_id AND channel = :ch
            ');
            $stmt->execute([
                's'       => $status,
                'uid'     => $user['id'],
                'uname'   => $user['name'],
                'item_id' => $contentItemId,
                'ch'      => $channel,
            ]);

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('Channel not found for this item');
            }

            write_audit_log($pdo, 'inline_update_channel_status', 'content_item', $contentItemId,
                ['channel' => $channel, 'status' => $beforeVal],
                ['channel' => $channel, 'status' => $status]);
            echo json_encode(['success' => true, 'channel' => $channel, 'status' => $status]);
            break;

        // -----------------------------------------------------------------
        case 'asset_pic_user_id':
        case 'socmed_pic_user_id':
            $value = ($input['value'] ?? '') === '' || $input['value'] === null ? null : (int) $input['value'];
            $column = $fieldType;
            $snapshotColumn = $fieldType === 'asset_pic_user_id'
                ? 'asset_pic_name_snapshot'
                : 'socmed_pic_name_snapshot';

            $picName = null;
            if ($value) {
                $stmt = $pdo->prepare('SELECT name FROM users WHERE id = :id');
                $stmt->execute(['id' => $value]);
                $picName = $stmt->fetchColumn() ?: null;
                if (!$picName) {
                    throw new InvalidArgumentException('User not found');
                }
            }

            $beforeStmt = $pdo->prepare("SELECT $column FROM content_items WHERE id = :id");
            $beforeStmt->execute(['id' => $contentItemId]);
            $beforeVal = $beforeStmt->fetchColumn();

            $stmt = $pdo->prepare(
                "UPDATE content_items SET $column = :v, $snapshotColumn = :name, updated_at = NOW() WHERE id = :id"
            );
            $stmt->execute(['v' => $value, 'name' => $picName, 'id' => $contentItemId]);

            write_audit_log($pdo, 'inline_update_' . $fieldType, 'content_item', $contentItemId,
                [$fieldType => $beforeVal], [$fieldType => $value, 'name' => $picName]);
            echo json_encode(['success' => true, 'value' => $value, 'name' => $picName ?? '']);
            break;

        // -----------------------------------------------------------------
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown field type']);
            break;
    }
} catch (InvalidArgumentException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('update_content_inline error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An error occurred']);
}
