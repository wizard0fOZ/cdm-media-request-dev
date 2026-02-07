<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/db.php';

try {
  // 1) active rooms
  $roomsStmt = $pdo->query("SELECT id, name, notes FROM rooms WHERE is_active = 1 ORDER BY name ASC");
  $rooms = $roomsStmt->fetchAll();

  // 2) equipment mapped by room
  $eqStmt = $pdo->query("
    SELECT
      re.room_id,
      e.id AS equipment_id,
      e.name,
      e.category,
      re.quantity AS available_qty
    FROM room_equipment re
    JOIN equipment e ON e.id = re.equipment_id
    JOIN rooms r ON r.id = re.room_id
    WHERE r.is_active = 1
      AND e.is_active = 1
    ORDER BY re.room_id ASC, e.category ASC, e.name ASC
  ");
  $rows = $eqStmt->fetchAll();

  $equipmentByRoom = [];
  foreach ($rows as $row) {
    $rid = (string)$row['room_id'];
    if (!isset($equipmentByRoom[$rid])) $equipmentByRoom[$rid] = [];
    $equipmentByRoom[$rid][] = [
      'equipment_id'   => (int)$row['equipment_id'],
      'name'           => $row['name'],
      'category'       => $row['category'],
      'available_qty'  => (int)$row['available_qty'],
    ];
  }

  echo json_encode([
    'ok' => true,
    'rooms' => $rooms,
    'equipmentByRoom' => $equipmentByRoom
  ], JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'error' => 'Failed to load lookups.'
  ]);
}
