<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_admin_auth();
require_role(ROLES_ADMIN_SETTINGS);
require_once __DIR__ . '/../../includes/db.php';

// ---------------------------------------------------------------------------
// Tab + flash messages
// ---------------------------------------------------------------------------
$tab = $_GET['tab'] ?? 'users';
if (!in_array($tab, ['users', 'rooms', 'equipment'], true)) $tab = 'users';

$successMsg = $_GET['success'] ?? '';
$errorMsg   = $_GET['error'] ?? '';

// ---------------------------------------------------------------------------
// Data loading
// ---------------------------------------------------------------------------

// Users
$users = $pdo->query('
    SELECT id, name, email, role, is_active, last_login_at, created_at
    FROM users ORDER BY name
')->fetchAll(PDO::FETCH_ASSOC);

// Rooms with equipment count
$rooms = $pdo->query('
    SELECT r.*, COUNT(re.equipment_id) AS equip_count
    FROM rooms r
    LEFT JOIN room_equipment re ON r.id = re.room_id
    GROUP BY r.id
    ORDER BY r.name
')->fetchAll(PDO::FETCH_ASSOC);

// Equipment with room count
$equipment = $pdo->query('
    SELECT e.*, COUNT(re.room_id) AS room_count
    FROM equipment e
    LEFT JOIN room_equipment re ON e.id = re.equipment_id
    GROUP BY e.id
    ORDER BY e.category, e.name
')->fetchAll(PDO::FETCH_ASSOC);

// Active equipment for room modal
$allEquipment = $pdo->query('
    SELECT id, name, category FROM equipment WHERE is_active = 1 ORDER BY category, name
')->fetchAll(PDO::FETCH_ASSOC);

// Room-equipment map: room_id => [ equipment_id => {qty, notes} ]
$roomEquipMap = [];
$reRows = $pdo->query('SELECT room_id, equipment_id, quantity, notes FROM room_equipment')->fetchAll(PDO::FETCH_ASSOC);
foreach ($reRows as $re) {
    $roomEquipMap[(int) $re['room_id']][(int) $re['equipment_id']] = [
        'qty'   => (int) $re['quantity'],
        'notes' => $re['notes'],
    ];
}

// Equipment-to-rooms map for equipment tab
$equipRoomMap = [];
foreach ($reRows as $re) {
    $equipRoomMap[(int) $re['equipment_id']][] = (int) $re['room_id'];
}

// Room name lookup
$roomNameMap = [];
foreach ($rooms as $r) {
    $roomNameMap[(int) $r['id']] = $r['name'];
}

// Distinct categories
$categories = $pdo->query('SELECT DISTINCT category FROM equipment ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);

// Group equipment by category for room modal
$equipByCategory = [];
foreach ($allEquipment as $eq) {
    $equipByCategory[$eq['category']][] = $eq;
}

// Current user id (for self-protection)
$currentUserId = (int) current_user()['id'];

// Valid roles for user modal
$validRoles = [
    'sysadmin',
    'media_head', 'media_asst',
    'designer_head', 'designer_asst',
    'av_head', 'av_asst',
    'photo_lead',
];

$pageTitle = "Settings | CDM Media Admin";
include __DIR__ . '/partials/header.php';
?>

<main class="flex-1 animate-fade-in" x-data="settingsPage()">
  <div class="mx-auto max-w-7xl px-4 py-6 space-y-6">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-50">Settings</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manage users, rooms, and equipment</p>
      </div>

      <!-- Create button (context-sensitive) -->
      <?php if ($tab === 'users'): ?>
        <button @click="openUserModal()" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 dark:bg-slate-100 px-4 py-2 text-sm font-medium text-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-slate-200 transition-colors">
          <i class="fa-solid fa-plus"></i> New User
        </button>
      <?php elseif ($tab === 'rooms'): ?>
        <button @click="openRoomModal()" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 dark:bg-slate-100 px-4 py-2 text-sm font-medium text-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-slate-200 transition-colors">
          <i class="fa-solid fa-plus"></i> New Room
        </button>
      <?php elseif ($tab === 'equipment'): ?>
        <button @click="openEquipmentModal()" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 dark:bg-slate-100 px-4 py-2 text-sm font-medium text-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-slate-200 transition-colors">
          <i class="fa-solid fa-plus"></i> New Equipment
        </button>
      <?php endif; ?>
    </div>

    <!-- Flash Messages -->
    <?php if ($successMsg): ?>
      <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-400 flex items-center gap-2">
        <i class="fa-solid fa-check-circle"></i> <?php echo e($successMsg); ?>
      </div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400 flex items-center gap-2">
        <i class="fa-solid fa-exclamation-circle"></i> <?php echo e($errorMsg); ?>
      </div>
    <?php endif; ?>

    <!-- Tab Navigation -->
    <div class="flex items-center gap-1 bg-slate-200 dark:bg-slate-700 rounded-lg p-1 w-fit">
      <a href="?tab=users"
         class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors <?php echo $tab === 'users' ? 'bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100'; ?>">
        <i class="fa-solid fa-users mr-1"></i> Users
      </a>
      <a href="?tab=rooms"
         class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors <?php echo $tab === 'rooms' ? 'bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100'; ?>">
        <i class="fa-solid fa-door-open mr-1"></i> Rooms
      </a>
      <a href="?tab=equipment"
         class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors <?php echo $tab === 'equipment' ? 'bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100'; ?>">
        <i class="fa-solid fa-toolbox mr-1"></i> Equipment
      </a>
    </div>

    <!-- ================================================================= -->
    <!-- USERS TAB                                                         -->
    <!-- ================================================================= -->
    <?php if ($tab === 'users'): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">

      <!-- Desktop Table -->
      <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
              <th class="text-left px-4 py-3 font-semibold text-slate-600 dark:text-slate-400">Name</th>
              <th class="text-left px-4 py-3 font-semibold text-slate-600 dark:text-slate-400">Email</th>
              <th class="text-left px-4 py-3 font-semibold text-slate-600 dark:text-slate-400">Role</th>
              <th class="text-center px-4 py-3 font-semibold text-slate-600 dark:text-slate-400">Status</th>
              <th class="text-left px-4 py-3 font-semibold text-slate-600 dark:text-slate-400">Last Login</th>
              <th class="text-center px-4 py-3 font-semibold text-slate-600 dark:text-slate-400">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
            <?php foreach ($users as $u): ?>
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
              <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100"><?php echo e($u['name']); ?></td>
              <td class="px-4 py-3 text-slate-600 dark:text-slate-400"><?php echo e($u['email']); ?></td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                  <?php echo e(role_label($u['role'])); ?>
                </span>
              </td>
              <td class="px-4 py-3 text-center">
                <?php if ($u['is_active']): ?>
                  <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">Active</span>
                <?php else: ?>
                  <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400">Inactive</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-xs">
                <?php echo $u['last_login_at'] ? date('d M Y, H:i', strtotime($u['last_login_at'])) : '<span class="text-slate-400 dark:text-slate-500">Never</span>'; ?>
              </td>
              <td class="px-4 py-3 text-center">
                <div class="inline-flex items-center gap-1">
                  <button @click='openUserModal(<?php echo json_encode($u); ?>)'
                          class="rounded-lg p-1.5 text-slate-500 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-slate-100 dark:hover:bg-slate-700 transition-colors"
                          title="Edit">
                    <i class="fa-solid fa-pen-to-square text-sm"></i>
                  </button>
                  <button @click='openResetPassword(<?php echo $u["id"]; ?>, <?php echo json_encode($u["name"]); ?>)'
                          class="rounded-lg p-1.5 text-slate-500 hover:text-amber-600 hover:bg-amber-50 dark:text-slate-400 dark:hover:text-amber-400 dark:hover:bg-amber-900/20 transition-colors"
                          title="Reset Password">
                    <i class="fa-solid fa-key text-sm"></i>
                  </button>
                  <?php if ((int) $u['id'] !== $currentUserId): ?>
                  <form method="POST" action="actions/save_user.php" class="inline"
                        onsubmit="return confirm('<?php echo $u['is_active'] ? 'Deactivate' : 'Activate'; ?> this user?')">
                    <input type="hidden" name="action" value="toggle_active">
                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                    <button type="submit"
                            class="rounded-lg p-1.5 text-slate-500 hover:text-<?php echo $u['is_active'] ? 'red-600' : 'emerald-600'; ?> hover:bg-<?php echo $u['is_active'] ? 'red' : 'emerald'; ?>-50 dark:text-slate-400 dark:hover:text-<?php echo $u['is_active'] ? 'red' : 'emerald'; ?>-400 dark:hover:bg-<?php echo $u['is_active'] ? 'red' : 'emerald'; ?>-900/20 transition-colors"
                            title="<?php echo $u['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                      <i class="fa-solid fa-<?php echo $u['is_active'] ? 'toggle-on' : 'toggle-off'; ?> text-sm"></i>
                    </button>
                  </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Mobile Cards -->
      <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-700/50">
        <?php foreach ($users as $u): ?>
        <div class="p-4 space-y-2">
          <div class="flex items-center justify-between">
            <div class="font-medium text-slate-900 dark:text-slate-100"><?php echo e($u['name']); ?></div>
            <?php if ($u['is_active']): ?>
              <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">Active</span>
            <?php else: ?>
              <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400">Inactive</span>
            <?php endif; ?>
          </div>
          <div class="text-sm text-slate-500 dark:text-slate-400"><?php echo e($u['email']); ?></div>
          <div class="flex items-center justify-between">
            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
              <?php echo e(role_label($u['role'])); ?>
            </span>
            <div class="flex items-center gap-1">
              <button @click='openUserModal(<?php echo json_encode($u); ?>)'
                      class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700" title="Edit">
                <i class="fa-solid fa-pen-to-square text-sm"></i>
              </button>
              <button @click='openResetPassword(<?php echo $u["id"]; ?>, <?php echo json_encode($u["name"]); ?>)'
                      class="rounded-lg p-1.5 text-slate-500 hover:bg-amber-50 dark:hover:bg-amber-900/20" title="Reset Password">
                <i class="fa-solid fa-key text-sm"></i>
              </button>
              <?php if ((int) $u['id'] !== $currentUserId): ?>
              <form method="POST" action="actions/save_user.php" class="inline"
                    onsubmit="return confirm('<?php echo $u['is_active'] ? 'Deactivate' : 'Activate'; ?> this user?')">
                <input type="hidden" name="action" value="toggle_active">
                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                <button type="submit" class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700">
                  <i class="fa-solid fa-<?php echo $u['is_active'] ? 'toggle-on' : 'toggle-off'; ?> text-sm"></i>
                </button>
              </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400 border-t border-slate-200 dark:border-slate-700">
        <?php echo count($users); ?> user<?php echo count($users) !== 1 ? 's' : ''; ?>
      </div>
    </div>

    <!-- ================================================================= -->
    <!-- ROOMS TAB                                                         -->
    <!-- ================================================================= -->
    <?php elseif ($tab === 'rooms'): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">

      <!-- Desktop Table -->
      <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
              <th class="text-left px-4 py-3 font-semibold text-slate-600 dark:text-slate-400">Name</th>
              <th class="text-center px-4 py-3 font-semibold text-slate-600 dark:text-slate-400">Status</th>
              <th class="text-left px-4 py-3 font-semibold text-slate-600 dark:text-slate-400">Notes</th>
              <th class="text-center px-4 py-3 font-semibold text-slate-600 dark:text-slate-400">Equipment</th>
              <th class="text-center px-4 py-3 font-semibold text-slate-600 dark:text-slate-400">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
            <?php foreach ($rooms as $r): ?>
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
              <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100"><?php echo e($r['name']); ?></td>
              <td class="px-4 py-3 text-center">
                <?php if ($r['is_active']): ?>
                  <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">Active</span>
                <?php else: ?>
                  <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400">Inactive</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 text-slate-600 dark:text-slate-400 max-w-xs truncate"><?php echo e($r['notes'] ?? ''); ?></td>
              <td class="px-4 py-3 text-center">
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400">
                  <?php echo (int) $r['equip_count']; ?> item<?php echo (int) $r['equip_count'] !== 1 ? 's' : ''; ?>
                </span>
              </td>
              <td class="px-4 py-3 text-center">
                <div class="inline-flex items-center gap-1">
                  <button @click='openRoomModal(<?php echo json_encode($r); ?>)'
                          class="rounded-lg p-1.5 text-slate-500 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-slate-100 dark:hover:bg-slate-700 transition-colors"
                          title="Edit">
                    <i class="fa-solid fa-pen-to-square text-sm"></i>
                  </button>
                  <form method="POST" action="actions/save_room.php" class="inline"
                        onsubmit="return confirm('<?php echo $r['is_active'] ? 'Deactivate' : 'Activate'; ?> this room?')">
                    <input type="hidden" name="action" value="toggle_active">
                    <input type="hidden" name="room_id" value="<?php echo $r['id']; ?>">
                    <button type="submit"
                            class="rounded-lg p-1.5 text-slate-500 hover:text-<?php echo $r['is_active'] ? 'red-600' : 'emerald-600'; ?> hover:bg-<?php echo $r['is_active'] ? 'red' : 'emerald'; ?>-50 dark:text-slate-400 dark:hover:text-<?php echo $r['is_active'] ? 'red' : 'emerald'; ?>-400 dark:hover:bg-<?php echo $r['is_active'] ? 'red' : 'emerald'; ?>-900/20 transition-colors"
                            title="<?php echo $r['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                      <i class="fa-solid fa-<?php echo $r['is_active'] ? 'toggle-on' : 'toggle-off'; ?> text-sm"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Mobile Cards -->
      <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-700/50">
        <?php foreach ($rooms as $r): ?>
        <div class="p-4 space-y-2">
          <div class="flex items-center justify-between">
            <div class="font-medium text-slate-900 dark:text-slate-100"><?php echo e($r['name']); ?></div>
            <?php if ($r['is_active']): ?>
              <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">Active</span>
            <?php else: ?>
              <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400">Inactive</span>
            <?php endif; ?>
          </div>
          <?php if ($r['notes']): ?>
            <div class="text-sm text-slate-500 dark:text-slate-400 line-clamp-2"><?php echo e($r['notes']); ?></div>
          <?php endif; ?>
          <div class="flex items-center justify-between">
            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400">
              <?php echo (int) $r['equip_count']; ?> equipment
            </span>
            <div class="flex items-center gap-1">
              <button @click='openRoomModal(<?php echo json_encode($r); ?>)'
                      class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700" title="Edit">
                <i class="fa-solid fa-pen-to-square text-sm"></i>
              </button>
              <form method="POST" action="actions/save_room.php" class="inline"
                    onsubmit="return confirm('<?php echo $r['is_active'] ? 'Deactivate' : 'Activate'; ?> this room?')">
                <input type="hidden" name="action" value="toggle_active">
                <input type="hidden" name="room_id" value="<?php echo $r['id']; ?>">
                <button type="submit" class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700">
                  <i class="fa-solid fa-<?php echo $r['is_active'] ? 'toggle-on' : 'toggle-off'; ?> text-sm"></i>
                </button>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400 border-t border-slate-200 dark:border-slate-700">
        <?php echo count($rooms); ?> room<?php echo count($rooms) !== 1 ? 's' : ''; ?>
      </div>
    </div>

    <!-- ================================================================= -->
    <!-- EQUIPMENT TAB                                                     -->
    <!-- ================================================================= -->
    <?php elseif ($tab === 'equipment'): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">

      <!-- Desktop Table -->
      <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
              <th class="text-left px-4 py-3 font-semibold text-slate-600 dark:text-slate-400">Name</th>
              <th class="text-left px-4 py-3 font-semibold text-slate-600 dark:text-slate-400">Category</th>
              <th class="text-center px-4 py-3 font-semibold text-slate-600 dark:text-slate-400">Status</th>
              <th class="text-center px-4 py-3 font-semibold text-slate-600 dark:text-slate-400">Used In</th>
              <th class="text-center px-4 py-3 font-semibold text-slate-600 dark:text-slate-400">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
            <?php foreach ($equipment as $eq): ?>
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
              <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100"><?php echo e($eq['name']); ?></td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                  <?php echo e($eq['category']); ?>
                </span>
              </td>
              <td class="px-4 py-3 text-center">
                <?php if ($eq['is_active']): ?>
                  <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">Active</span>
                <?php else: ?>
                  <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400">Inactive</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 text-center">
                <?php
                  $roomCount = (int) $eq['room_count'];
                  $roomIds = $equipRoomMap[(int) $eq['id']] ?? [];
                  $roomNames = array_map(fn($rid) => $roomNameMap[$rid] ?? '?', $roomIds);
                ?>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400"
                      <?php if ($roomNames): ?>title="<?php echo e(implode(', ', $roomNames)); ?>"<?php endif; ?>>
                  <?php echo $roomCount; ?> room<?php echo $roomCount !== 1 ? 's' : ''; ?>
                </span>
              </td>
              <td class="px-4 py-3 text-center">
                <div class="inline-flex items-center gap-1">
                  <button @click='openEquipmentModal(<?php echo json_encode($eq); ?>)'
                          class="rounded-lg p-1.5 text-slate-500 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-slate-100 dark:hover:bg-slate-700 transition-colors"
                          title="Edit">
                    <i class="fa-solid fa-pen-to-square text-sm"></i>
                  </button>
                  <form method="POST" action="actions/save_equipment.php" class="inline"
                        onsubmit="return confirm('<?php echo $eq['is_active'] ? 'Deactivate' : 'Activate'; ?> this equipment?')">
                    <input type="hidden" name="action" value="toggle_active">
                    <input type="hidden" name="equipment_id" value="<?php echo $eq['id']; ?>">
                    <button type="submit"
                            class="rounded-lg p-1.5 text-slate-500 hover:text-<?php echo $eq['is_active'] ? 'red-600' : 'emerald-600'; ?> hover:bg-<?php echo $eq['is_active'] ? 'red' : 'emerald'; ?>-50 dark:text-slate-400 dark:hover:text-<?php echo $eq['is_active'] ? 'red' : 'emerald'; ?>-400 dark:hover:bg-<?php echo $eq['is_active'] ? 'red' : 'emerald'; ?>-900/20 transition-colors"
                            title="<?php echo $eq['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                      <i class="fa-solid fa-<?php echo $eq['is_active'] ? 'toggle-on' : 'toggle-off'; ?> text-sm"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Mobile Cards -->
      <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-700/50">
        <?php foreach ($equipment as $eq): ?>
        <div class="p-4 space-y-2">
          <div class="flex items-center justify-between">
            <div class="font-medium text-slate-900 dark:text-slate-100"><?php echo e($eq['name']); ?></div>
            <?php if ($eq['is_active']): ?>
              <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">Active</span>
            <?php else: ?>
              <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400">Inactive</span>
            <?php endif; ?>
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                <?php echo e($eq['category']); ?>
              </span>
              <span class="text-xs text-slate-400"><?php echo (int) $eq['room_count']; ?> room<?php echo (int) $eq['room_count'] !== 1 ? 's' : ''; ?></span>
            </div>
            <div class="flex items-center gap-1">
              <button @click='openEquipmentModal(<?php echo json_encode($eq); ?>)'
                      class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700" title="Edit">
                <i class="fa-solid fa-pen-to-square text-sm"></i>
              </button>
              <form method="POST" action="actions/save_equipment.php" class="inline"
                    onsubmit="return confirm('<?php echo $eq['is_active'] ? 'Deactivate' : 'Activate'; ?> this equipment?')">
                <input type="hidden" name="action" value="toggle_active">
                <input type="hidden" name="equipment_id" value="<?php echo $eq['id']; ?>">
                <button type="submit" class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700">
                  <i class="fa-solid fa-<?php echo $eq['is_active'] ? 'toggle-on' : 'toggle-off'; ?> text-sm"></i>
                </button>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400 border-t border-slate-200 dark:border-slate-700">
        <?php echo count($equipment); ?> equipment item<?php echo count($equipment) !== 1 ? 's' : ''; ?>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /max-w-7xl -->

  <!-- ===================================================================== -->
  <!-- USER CREATE/EDIT MODAL                                                -->
  <!-- ===================================================================== -->
  <div x-show="showModal === 'user'" x-cloak
       class="fixed inset-0 z-50 flex items-center justify-center p-4"
       @keydown.escape.window="closeModal()">
    <div class="fixed inset-0 bg-black/40" @click="closeModal()"></div>
    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 w-full max-w-lg max-h-[90vh] overflow-y-auto"
         @click.stop>
      <form method="POST" action="actions/save_user.php">
        <input type="hidden" name="action" :value="editId ? 'update' : 'create'">
        <input type="hidden" name="user_id" :value="editId || ''">

        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-50" x-text="editId ? 'Edit User' : 'New User'"></h3>
          <button type="button" @click="closeModal()" class="rounded-lg p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <div class="px-6 py-4 space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" required x-model="formData.name"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email <span class="text-red-500">*</span></label>
            <input type="email" name="email" required x-model="formData.email"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Role <span class="text-red-500">*</span></label>
            <select name="role" required x-model="formData.role"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              <option value="">Select role...</option>
              <?php foreach ($validRoles as $r): ?>
                <option value="<?php echo $r; ?>"><?php echo e(role_label($r)); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div x-show="!editId">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Password <span class="text-red-500">*</span></label>
            <input type="password" name="password" :required="!editId" minlength="8" x-model="formData.password"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   placeholder="Min 8 characters">
          </div>
          <div x-show="editId" class="flex items-center gap-3">
            <input type="checkbox" name="is_active" value="1" x-model="formData.is_active" :id="'user-active'"
                   class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500">
            <label :for="'user-active'" class="text-sm font-medium text-slate-700 dark:text-slate-300">Active</label>
          </div>
        </div>

        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-3">
          <button type="button" @click="closeModal()"
                  class="rounded-lg border border-slate-300 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
            Cancel
          </button>
          <button type="submit"
                  class="rounded-lg bg-slate-900 dark:bg-slate-100 px-4 py-2 text-sm font-medium text-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-slate-200 transition-colors">
            <span x-text="editId ? 'Save Changes' : 'Create User'"></span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- ===================================================================== -->
  <!-- RESET PASSWORD MODAL                                                  -->
  <!-- ===================================================================== -->
  <div x-show="showResetModal" x-cloak
       class="fixed inset-0 z-50 flex items-center justify-center p-4"
       @keydown.escape.window="showResetModal = false">
    <div class="fixed inset-0 bg-black/40" @click="showResetModal = false"></div>
    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 w-full max-w-md"
         @click.stop>
      <form method="POST" action="actions/save_user.php" x-ref="resetForm"
            @submit="if ($refs.resetPw.value !== $refs.resetPwConfirm.value) { alert('Passwords do not match'); $event.preventDefault(); }">
        <input type="hidden" name="action" value="reset_password">
        <input type="hidden" name="user_id" :value="resetUserId || ''">

        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-50">Reset Password</h3>
          <button type="button" @click="showResetModal = false" class="rounded-lg p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <div class="px-6 py-4 space-y-4">
          <p class="text-sm text-slate-600 dark:text-slate-400">Reset password for <strong x-text="resetUserName" class="text-slate-900 dark:text-slate-100"></strong></p>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">New Password <span class="text-red-500">*</span></label>
            <input type="password" name="password" required minlength="8" x-ref="resetPw"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   placeholder="Min 8 characters">
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Confirm Password <span class="text-red-500">*</span></label>
            <input type="password" required minlength="8" x-ref="resetPwConfirm"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   placeholder="Repeat password">
          </div>
        </div>

        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-3">
          <button type="button" @click="showResetModal = false"
                  class="rounded-lg border border-slate-300 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
            Cancel
          </button>
          <button type="submit"
                  class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700 transition-colors">
            Reset Password
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- ===================================================================== -->
  <!-- ROOM CREATE/EDIT MODAL                                                -->
  <!-- ===================================================================== -->
  <div x-show="showModal === 'room'" x-cloak
       class="fixed inset-0 z-50 flex items-center justify-center p-4"
       @keydown.escape.window="closeModal()">
    <div class="fixed inset-0 bg-black/40" @click="closeModal()"></div>
    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 w-full max-w-2xl max-h-[90vh] overflow-y-auto"
         @click.stop>
      <form method="POST" action="actions/save_room.php">
        <input type="hidden" name="action" :value="editId ? 'update' : 'create'">
        <input type="hidden" name="room_id" :value="editId || ''">

        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-50" x-text="editId ? 'Edit Room' : 'New Room'"></h3>
          <button type="button" @click="closeModal()" class="rounded-lg p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <div class="px-6 py-4 space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Room Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" required x-model="formData.name"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Notes</label>
            <textarea name="notes" rows="2" x-model="formData.notes"
                      class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
          </div>
          <div x-show="editId" class="flex items-center gap-3">
            <input type="checkbox" name="is_active" value="1" x-model="formData.is_active" :id="'room-active'"
                   class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500">
            <label :for="'room-active'" class="text-sm font-medium text-slate-700 dark:text-slate-300">Active</label>
          </div>

          <!-- Equipment Assignment -->
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Equipment</label>
            <div class="border border-slate-200 dark:border-slate-600 rounded-lg divide-y divide-slate-100 dark:divide-slate-700 max-h-64 overflow-y-auto">
              <?php foreach ($equipByCategory as $cat => $items): ?>
              <div class="p-3">
                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2"><?php echo e($cat); ?></div>
                <div class="space-y-2">
                  <?php foreach ($items as $eqItem): ?>
                  <div class="flex items-center gap-3">
                    <input type="checkbox" name="equipment[]" value="<?php echo $eqItem['id']; ?>"
                           :checked="formData.equipment && formData.equipment[<?php echo $eqItem['id']; ?>]"
                           @change="if ($event.target.checked) { if (!formData.equipment) formData.equipment = {}; formData.equipment[<?php echo $eqItem['id']; ?>] = true; } else { delete formData.equipment[<?php echo $eqItem['id']; ?>]; }"
                           class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm text-slate-700 dark:text-slate-300 flex-1"><?php echo e($eqItem['name']); ?></span>
                    <input type="number" name="equipment_qty[<?php echo $eqItem['id']; ?>]" min="1" value="1"
                           :value="formData.equipQty && formData.equipQty[<?php echo $eqItem['id']; ?>] || 1"
                           @input="if (!formData.equipQty) formData.equipQty = {}; formData.equipQty[<?php echo $eqItem['id']; ?>] = parseInt($event.target.value) || 1"
                           class="w-16 rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-2 py-1 text-xs text-center text-slate-900 dark:text-slate-100">
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-3">
          <button type="button" @click="closeModal()"
                  class="rounded-lg border border-slate-300 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
            Cancel
          </button>
          <button type="submit"
                  class="rounded-lg bg-slate-900 dark:bg-slate-100 px-4 py-2 text-sm font-medium text-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-slate-200 transition-colors">
            <span x-text="editId ? 'Save Changes' : 'Create Room'"></span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- ===================================================================== -->
  <!-- EQUIPMENT CREATE/EDIT MODAL                                           -->
  <!-- ===================================================================== -->
  <div x-show="showModal === 'equipment'" x-cloak
       class="fixed inset-0 z-50 flex items-center justify-center p-4"
       @keydown.escape.window="closeModal()">
    <div class="fixed inset-0 bg-black/40" @click="closeModal()"></div>
    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 w-full max-w-lg max-h-[90vh] overflow-y-auto"
         @click.stop>
      <form method="POST" action="actions/save_equipment.php">
        <input type="hidden" name="action" :value="editId ? 'update' : 'create'">
        <input type="hidden" name="equipment_id" :value="editId || ''">

        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-50" x-text="editId ? 'Edit Equipment' : 'New Equipment'"></h3>
          <button type="button" @click="closeModal()" class="rounded-lg p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <div class="px-6 py-4 space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" required x-model="formData.name"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Category <span class="text-red-500">*</span></label>
            <input type="text" name="category" required x-model="formData.category" list="category-list"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   placeholder="e.g. Audio, Video, Accessories">
            <datalist id="category-list">
              <?php foreach ($categories as $cat): ?>
                <option value="<?php echo e($cat); ?>">
              <?php endforeach; ?>
            </datalist>
          </div>
          <div x-show="editId" class="flex items-center gap-3">
            <input type="checkbox" name="is_active" value="1" x-model="formData.is_active" :id="'equip-active'"
                   class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500">
            <label :for="'equip-active'" class="text-sm font-medium text-slate-700 dark:text-slate-300">Active</label>
          </div>

          <!-- Show rooms using this equipment (edit only) -->
          <template x-if="editId && formData.rooms && formData.rooms.length > 0">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Used in rooms</label>
              <div class="flex flex-wrap gap-1">
                <template x-for="room in formData.rooms" :key="room">
                  <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400" x-text="room"></span>
                </template>
              </div>
            </div>
          </template>
        </div>

        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-3">
          <button type="button" @click="closeModal()"
                  class="rounded-lg border border-slate-300 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
            Cancel
          </button>
          <button type="submit"
                  class="rounded-lg bg-slate-900 dark:bg-slate-100 px-4 py-2 text-sm font-medium text-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-slate-200 transition-colors">
            <span x-text="editId ? 'Save Changes' : 'Create Equipment'"></span>
          </button>
        </div>
      </form>
    </div>
  </div>

</main>

<script>
function settingsPage() {
  // Pre-load room-equipment map from PHP
  const roomEquipMap = <?php echo json_encode($roomEquipMap); ?>;
  const equipRoomMap = <?php echo json_encode(
    array_map(fn($rids) => array_map(fn($rid) => $roomNameMap[$rid] ?? '?', $rids), $equipRoomMap)
  ); ?>;

  return {
    showModal: null,
    editId: null,
    formData: {},
    showResetModal: false,
    resetUserId: null,
    resetUserName: '',

    openUserModal(user = null) {
      if (user) {
        this.editId = user.id;
        this.formData = {
          name: user.name,
          email: user.email,
          role: user.role,
          is_active: user.is_active == 1,
          password: '',
        };
      } else {
        this.editId = null;
        this.formData = { name: '', email: '', role: '', password: '', is_active: true };
      }
      this.showModal = 'user';
    },

    openRoomModal(room = null) {
      if (room) {
        this.editId = room.id;
        const equipMap = roomEquipMap[room.id] || {};
        const equipment = {};
        const equipQty = {};
        for (const [eid, info] of Object.entries(equipMap)) {
          equipment[eid] = true;
          equipQty[eid] = info.qty;
        }
        this.formData = {
          name: room.name,
          notes: room.notes || '',
          is_active: room.is_active == 1,
          equipment,
          equipQty,
        };
      } else {
        this.editId = null;
        this.formData = { name: '', notes: '', is_active: true, equipment: {}, equipQty: {} };
      }
      this.showModal = 'room';

      // Sync checkbox/qty values after DOM update
      this.$nextTick(() => {
        if (room) {
          const equipMap = roomEquipMap[room.id] || {};
          document.querySelectorAll('input[name="equipment[]"]').forEach(cb => {
            const eid = cb.value;
            cb.checked = !!equipMap[eid];
          });
          document.querySelectorAll('input[name^="equipment_qty"]').forEach(inp => {
            const match = inp.name.match(/\[(\d+)\]/);
            if (match) {
              const eid = match[1];
              inp.value = equipMap[eid] ? equipMap[eid].qty : 1;
            }
          });
        } else {
          document.querySelectorAll('input[name="equipment[]"]').forEach(cb => cb.checked = false);
          document.querySelectorAll('input[name^="equipment_qty"]').forEach(inp => inp.value = 1);
        }
      });
    },

    openEquipmentModal(equip = null) {
      if (equip) {
        this.editId = equip.id;
        this.formData = {
          name: equip.name,
          category: equip.category,
          is_active: equip.is_active == 1,
          rooms: equipRoomMap[equip.id] || [],
        };
      } else {
        this.editId = null;
        this.formData = { name: '', category: '', is_active: true, rooms: [] };
      }
      this.showModal = 'equipment';
    },

    openResetPassword(userId, userName) {
      this.resetUserId = userId;
      this.resetUserName = userName;
      this.showResetModal = true;
    },

    closeModal() {
      this.showModal = null;
      this.editId = null;
      this.formData = {};
    },
  };
}
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
