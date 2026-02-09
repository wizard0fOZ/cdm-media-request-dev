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
$note      = trim($_POST['note'] ?? '');

if ($requestId <= 0) {
    http_response_code(400);
    exit('Invalid parameters');
}

if ($note === '') {
    http_response_code(400);
    exit('Note cannot be empty');
}

// Verify request exists
$stmt = $pdo->prepare('SELECT id FROM media_requests WHERE id = :id');
$stmt->execute(['id' => $requestId]);
if (!$stmt->fetch()) {
    http_response_code(404);
    exit('Request not found');
}

$user = current_user();

try {
    $stmt = $pdo->prepare('
        INSERT INTO internal_notes (media_request_id, actor_user_id, note, created_at)
        VALUES (:rid, :uid, :note, NOW())
    ');
    $stmt->execute([
        'rid'  => $requestId,
        'uid'  => $user['id'],
        'note' => $note,
    ]);
} catch (Throwable $ex) {
    error_log('add_note error: ' . $ex->getMessage());
    http_response_code(500);
    exit('An error occurred');
}

header('Location: ../view.php?id=' . $requestId . '&success=1#notes');
exit;
