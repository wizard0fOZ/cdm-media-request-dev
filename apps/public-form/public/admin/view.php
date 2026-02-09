<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_admin_auth();
require_once __DIR__ . '/../../includes/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$success        = isset($_GET['success']);
$contentExists  = isset($_GET['content_exists']);
$user           = current_user();

// ---------------------------------------------------------------------------
// Fetch main request
// ---------------------------------------------------------------------------
$stmt = $pdo->prepare("SELECT * FROM media_requests WHERE id = :id");
$stmt->execute(['id' => $id]);
$request = $stmt->fetch();

if (!$request) {
    header('Location: index.php');
    exit;
}

// ---------------------------------------------------------------------------
// Fetch event schedule + occurrences
// ---------------------------------------------------------------------------
$stmt = $pdo->prepare("SELECT * FROM event_schedules WHERE media_request_id = :id");
$stmt->execute(['id' => $id]);
$schedule = $stmt->fetch();

$occurrences = [];
if ($schedule && $schedule['schedule_type'] === 'custom_list') {
    $stmt = $pdo->prepare("SELECT * FROM event_occurrences WHERE event_schedule_id = :sid ORDER BY occurrence_date");
    $stmt->execute(['sid' => $schedule['id']]);
    $occurrences = $stmt->fetchAll();
}

// ---------------------------------------------------------------------------
// Fetch service types with approval info
// ---------------------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT rt.*, u.name AS approver_name, pic.name AS pic_name
    FROM request_types rt
    LEFT JOIN users u ON rt.approved_by_user_id = u.id
    LEFT JOIN users pic ON rt.assigned_pic_user_id = pic.id
    WHERE rt.media_request_id = :id
    ORDER BY FIELD(rt.type, 'av', 'media', 'photo')
");
$stmt->execute(['id' => $id]);
$serviceTypes = $stmt->fetchAll();

$services = array_column($serviceTypes, 'type');
$serviceMap = [];
foreach ($serviceTypes as $st) {
    $serviceMap[$st['type']] = $st;
}

// ---------------------------------------------------------------------------
// Fetch AV details
// ---------------------------------------------------------------------------
$avDetails = null;
$avRooms = [];
$avItems = [];
if (in_array('av', $services)) {
    $stmt = $pdo->prepare("SELECT * FROM av_details WHERE media_request_id = :id");
    $stmt->execute(['id' => $id]);
    $avDetails = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT r.name FROM request_rooms rr JOIN rooms r ON rr.room_id = r.id WHERE rr.media_request_id = :id");
    $stmt->execute(['id' => $id]);
    $avRooms = array_column($stmt->fetchAll(), 'name');

    $stmt = $pdo->prepare("
        SELECT e.name, ai.quantity, ai.note, r.name as room_name
        FROM av_items ai
        JOIN equipment e ON ai.equipment_id = e.id
        LEFT JOIN rooms r ON ai.room_id = r.id
        WHERE ai.media_request_id = :id
    ");
    $stmt->execute(['id' => $id]);
    $avItems = $stmt->fetchAll();
}

// ---------------------------------------------------------------------------
// Fetch Media details
// ---------------------------------------------------------------------------
$mediaDetails = null;
$mediaPlatforms = [];
if (in_array('media', $services)) {
    $stmt = $pdo->prepare("SELECT * FROM media_details WHERE media_request_id = :id");
    $stmt->execute(['id' => $id]);
    $mediaDetails = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT platform, platform_other_label FROM media_platforms WHERE media_request_id = :id");
    $stmt->execute(['id' => $id]);
    $mediaPlatforms = $stmt->fetchAll();

    // Content items count for this request
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM content_items WHERE media_request_id = :id");
    $stmt->execute(['id' => $id]);
    $contentItemCount = (int) $stmt->fetchColumn();
}

// ---------------------------------------------------------------------------
// Fetch Photo details
// ---------------------------------------------------------------------------
$photoDetails = null;
if (in_array('photo', $services)) {
    $stmt = $pdo->prepare("SELECT * FROM photo_details WHERE media_request_id = :id");
    $stmt->execute(['id' => $id]);
    $photoDetails = $stmt->fetch();
}

// ---------------------------------------------------------------------------
// Fetch internal notes
// ---------------------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT n.*, COALESCE(u.name, n.actor_name_snapshot) AS actor_name
    FROM internal_notes n
    LEFT JOIN users u ON n.actor_user_id = u.id
    WHERE n.media_request_id = :id
    ORDER BY n.created_at DESC
");
$stmt->execute(['id' => $id]);
$internalNotes = $stmt->fetchAll();

// ---------------------------------------------------------------------------
// Fetch coordinator info
// ---------------------------------------------------------------------------
$coordinatorName = null;
if ($request['assigned_coordinator_user_id']) {
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = :id");
    $stmt->execute(['id' => $request['assigned_coordinator_user_id']]);
    $coordinatorName = $stmt->fetchColumn();
}

// Coordinators list for dropdown
$coordinators = get_coordinators($pdo);

// Service label/color config
$svcConfig = [
    'av'    => ['label' => 'AV Support',          'icon' => 'fa-headphones',  'color' => 'purple'],
    'media' => ['label' => 'Poster / Video Design','icon' => 'fa-photo-film', 'color' => 'green'],
    'photo' => ['label' => 'Photography',          'icon' => 'fa-camera',     'color' => 'blue'],
];

$pageTitle = "Request " . e($request['reference_no']) . " | CDM Admin";
include __DIR__ . "/partials/header.php";
?>

<main class="mx-auto max-w-7xl px-4 py-8">

  <!-- Back + Success -->
  <div class="mb-6 flex flex-col gap-3">
    <a href="index.php" class="inline-flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100 self-start">
      <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
    </a>
    <?php if ($success): ?>
      <div class="rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/30 px-4 py-3">
        <div class="flex items-center gap-2 text-sm text-green-700 dark:text-green-300">
          <i class="fa-solid fa-check-circle"></i>
          <span>Action completed successfully</span>
        </div>
      </div>
    <?php endif; ?>
    <?php if ($contentExists): ?>
      <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/30 px-4 py-3">
        <div class="flex items-center gap-2 text-sm text-amber-700 dark:text-amber-300">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <span>Content items already exist for this request. <a href="content.php?request_id=<?php echo $id; ?>" class="underline font-medium">View content</a></span>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Header Card -->
  <div class="mb-6 rounded-xl bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
      <div class="min-w-0 flex-1">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-50"><?php echo e($request['event_name']); ?></h1>
        <div class="mt-2 flex flex-wrap items-center gap-2">
          <span class="font-mono text-sm font-semibold text-slate-600 dark:text-slate-400"><?php echo e($request['reference_no']); ?></span>
          <?php if ($request['is_late']): ?>
            <span class="inline-flex items-center rounded-full bg-amber-100 dark:bg-amber-900/30 px-2.5 py-1 text-xs font-medium text-amber-700 dark:text-amber-400">
              <i class="fa-solid fa-exclamation-triangle text-xs mr-1"></i>
              Late (<?php echo $request['lead_days']; ?>d notice)
            </span>
          <?php endif; ?>
        </div>
      </div>
      <div class="flex items-center gap-3 shrink-0">
        <span class="inline-flex items-center rounded-full px-3 py-1.5 text-sm font-semibold <?php echo approval_status_classes($request['request_status']); ?>">
          <?php echo approval_status_label($request['request_status']); ?>
        </span>
      </div>
    </div>

    <!-- Coordinator assignment -->
    <?php if (in_array($user['role'], ROLES_COORDINATOR, true)): ?>
      <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700 flex items-center gap-3" x-data="{ editing: false }">
        <span class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Coordinator:</span>
        <template x-if="!editing">
          <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-slate-900 dark:text-slate-100">
              <?php echo $coordinatorName ? e($coordinatorName) : '<span class="text-slate-400 italic">Unassigned</span>'; ?>
            </span>
            <button @click="editing = true" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">Change</button>
          </div>
        </template>
        <template x-if="editing">
          <form method="POST" action="actions/assign_coordinator.php" class="flex items-center gap-2">
            <input type="hidden" name="request_id" value="<?php echo $id; ?>">
            <select name="user_id" class="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-2 py-1 text-sm text-slate-900 dark:text-slate-100">
              <option value="">-- Unassign --</option>
              <?php foreach ($coordinators as $c): ?>
                <option value="<?php echo $c['id']; ?>" <?php echo $request['assigned_coordinator_user_id'] == $c['id'] ? 'selected' : ''; ?>>
                  <?php echo e($c['name']); ?> (<?php echo role_label($c['role']); ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="rounded-lg bg-slate-900 dark:bg-slate-100 px-3 py-1 text-xs font-medium text-white dark:text-slate-900">Save</button>
            <button type="button" @click="editing = false" class="text-xs text-slate-500 hover:text-slate-700">Cancel</button>
          </form>
        </template>
      </div>
    <?php elseif ($coordinatorName): ?>
      <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
        <span class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Coordinator:</span>
        <span class="ml-2 text-sm font-medium text-slate-900 dark:text-slate-100"><?php echo e($coordinatorName); ?></span>
      </div>
    <?php endif; ?>
  </div>

  <!-- Two Column Layout -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- LEFT COLUMN (2/3) — Request Info -->
    <div class="lg:col-span-2 space-y-6">

      <!-- Requestor Information -->
      <div class="rounded-xl bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
        <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-slate-900 dark:text-slate-50">
          <i class="fa-solid fa-user text-slate-400 dark:text-slate-500"></i>
          Requester Information
        </h2>
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Name</div>
            <div class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100"><?php echo e($request['requestor_name']); ?></div>
          </div>
          <div>
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Ministry</div>
            <div class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100"><?php echo e($request['ministry']) ?: '-'; ?></div>
          </div>
          <div>
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Email</div>
            <div class="mt-1 text-sm"><a href="mailto:<?php echo e($request['email']); ?>" class="text-blue-600 dark:text-blue-400 hover:underline"><?php echo e($request['email']); ?></a></div>
          </div>
          <div>
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Phone</div>
            <div class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100"><?php echo e($request['contact_no']); ?></div>
          </div>
          <div class="sm:col-span-2">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Submitted</div>
            <div class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100"><?php echo date('F j, Y \a\t g:i A', strtotime($request['submitted_at'])); ?></div>
          </div>
        </div>
      </div>

      <!-- Event Details + Schedule -->
      <div class="rounded-xl bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
        <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-slate-900 dark:text-slate-50">
          <i class="fa-solid fa-calendar-day text-slate-400 dark:text-slate-500"></i>
          Event Details
        </h2>
        <div class="space-y-4">
          <?php if ($request['event_description']): ?>
            <div>
              <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Description</div>
              <div class="mt-1 text-sm text-slate-700 dark:text-slate-300"><?php echo nl2br(e($request['event_description'])); ?></div>
            </div>
          <?php endif; ?>
          <?php if ($request['event_location_note']): ?>
            <div>
              <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Location</div>
              <div class="mt-1 text-sm text-slate-700 dark:text-slate-300"><?php echo nl2br(e($request['event_location_note'])); ?></div>
            </div>
          <?php endif; ?>

          <!-- Schedule -->
          <?php if ($schedule): ?>
            <div class="rounded-lg bg-slate-50 dark:bg-slate-900 p-4">
              <?php if ($schedule['schedule_type'] === 'recurring'): ?>
                <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-3">Recurring Event</div>
                <div class="grid gap-3 sm:grid-cols-2 text-sm">
                  <div><span class="text-slate-500 dark:text-slate-400">Pattern:</span> <span class="font-medium text-slate-900 dark:text-slate-100"><?php echo ucfirst(e($schedule['recurrence_pattern'])); ?></span></div>
                  <div><span class="text-slate-500 dark:text-slate-400">Day:</span> <span class="font-medium text-slate-900 dark:text-slate-100"><?php echo e($schedule['recurrence_days_of_week']); ?></span></div>
                  <div><span class="text-slate-500 dark:text-slate-400">Dates:</span> <span class="font-medium text-slate-900 dark:text-slate-100"><?php echo e($schedule['start_date']) . ' to ' . e($schedule['end_date']); ?></span></div>
                  <div><span class="text-slate-500 dark:text-slate-400">Time:</span> <span class="font-medium text-slate-900 dark:text-slate-100"><?php echo e($schedule['start_time']) . ' - ' . e($schedule['end_time']); ?></span></div>
                </div>
              <?php elseif ($schedule['schedule_type'] === 'custom_list'): ?>
                <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-3">Specific Dates</div>
                <div class="space-y-2">
                  <?php foreach ($occurrences as $occ): ?>
                    <div class="flex items-center gap-2 text-sm">
                      <i class="fa-solid fa-circle text-[6px] text-slate-400"></i>
                      <span class="font-medium text-slate-900 dark:text-slate-100"><?php echo date('M j, Y', strtotime($occ['occurrence_date'])); ?></span>
                      <span class="text-slate-500 dark:text-slate-400"><?php echo ($occ['start_time'] ?: '-') . ' - ' . ($occ['end_time'] ?: '-'); ?></span>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-3">Single Event</div>
                <div class="grid gap-3 sm:grid-cols-2 text-sm">
                  <div><span class="text-slate-500 dark:text-slate-400">Date:</span> <span class="font-medium text-slate-900 dark:text-slate-100"><?php echo date('M j, Y', strtotime($schedule['start_date'])); ?></span></div>
                  <?php if ($schedule['start_time']): ?>
                    <div><span class="text-slate-500 dark:text-slate-400">Time:</span> <span class="font-medium text-slate-900 dark:text-slate-100"><?php echo e($schedule['start_time']) . ' - ' . e($schedule['end_time']); ?></span></div>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
              <?php if ($schedule['notes']): ?>
                <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-700 text-sm text-slate-700 dark:text-slate-300"><?php echo nl2br(e($schedule['notes'])); ?></div>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <!-- References -->
          <?php if ($request['reference_url'] || $request['reference_note']): ?>
            <div class="pt-2">
              <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-2">References</div>
              <?php if ($request['reference_url']): ?>
                <div class="text-sm">
                  <a href="<?php echo e($request['reference_url']); ?>" target="_blank" rel="noopener noreferrer" class="text-blue-600 dark:text-blue-400 hover:underline break-all">
                    <?php echo e($request['reference_url']); ?>
                    <i class="fa-solid fa-external-link-alt text-xs ml-1"></i>
                  </a>
                </div>
              <?php endif; ?>
              <?php if ($request['reference_note']): ?>
                <div class="mt-1 text-sm text-slate-700 dark:text-slate-300"><?php echo nl2br(e($request['reference_note'])); ?></div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Service Detail Cards -->
      <?php foreach ($serviceTypes as $st):
        $type = $st['type'];
        $cfg  = $svcConfig[$type] ?? ['label' => ucfirst($type), 'icon' => 'fa-cog', 'color' => 'slate'];
        $clr  = $cfg['color'];
      ?>
        <div class="rounded-xl border border-<?php echo $clr; ?>-200 dark:border-<?php echo $clr; ?>-800 bg-<?php echo $clr; ?>-50/50 dark:bg-<?php echo $clr; ?>-900/20 p-6 shadow-sm">
          <div class="flex items-center justify-between mb-4">
            <h2 class="flex items-center gap-2 text-lg font-bold text-<?php echo $clr; ?>-900 dark:text-<?php echo $clr; ?>-300">
              <i class="fa-solid <?php echo $cfg['icon']; ?> text-<?php echo $clr; ?>-600 dark:text-<?php echo $clr; ?>-400"></i>
              <?php echo $cfg['label']; ?>
            </h2>
            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?php echo approval_status_classes($st['approval_status']); ?>">
              <?php echo approval_status_label($st['approval_status']); ?>
            </span>
          </div>

          <?php if ($type === 'av' && $avDetails): ?>
            <div class="space-y-3 text-sm">
              <?php if (!empty($avRooms)): ?>
                <div>
                  <span class="text-<?php echo $clr; ?>-700 dark:text-<?php echo $clr; ?>-400 font-medium">Rooms:</span>
                  <span class="ml-1 text-slate-900 dark:text-slate-100"><?php echo implode(', ', array_map('e', $avRooms)); ?></span>
                </div>
              <?php endif; ?>
              <?php if (!empty($avItems)): ?>
                <div>
                  <span class="text-<?php echo $clr; ?>-700 dark:text-<?php echo $clr; ?>-400 font-medium">Equipment:</span>
                  <ul class="mt-1 space-y-1 ml-4">
                    <?php foreach ($avItems as $item): ?>
                      <li class="text-slate-700 dark:text-slate-300 flex items-start gap-2">
                        <i class="fa-solid fa-circle text-[5px] mt-1.5 text-slate-400 shrink-0"></i>
                        <span>
                          <?php echo e($item['name']); ?> <span class="text-slate-500">(Qty: <?php echo $item['quantity']; ?>)</span>
                          <?php if ($item['room_name']): ?> — <?php echo e($item['room_name']); ?><?php endif; ?>
                          <?php if ($item['note']): ?> <span class="text-xs text-slate-500 italic"><?php echo e($item['note']); ?></span><?php endif; ?>
                        </span>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endif; ?>
              <?php if ($avDetails['rehearsal_date']): ?>
                <div>
                  <span class="text-<?php echo $clr; ?>-700 dark:text-<?php echo $clr; ?>-400 font-medium">Rehearsal:</span>
                  <span class="ml-1 text-slate-700 dark:text-slate-300">
                    <?php echo date('M j, Y', strtotime($avDetails['rehearsal_date'])); ?>
                    <?php if ($avDetails['rehearsal_start_time']): ?>
                      at <?php echo e($avDetails['rehearsal_start_time']) . ' - ' . e($avDetails['rehearsal_end_time']); ?>
                    <?php endif; ?>
                  </span>
                </div>
              <?php endif; ?>
              <?php if ($avDetails['note']): ?>
                <div>
                  <span class="text-<?php echo $clr; ?>-700 dark:text-<?php echo $clr; ?>-400 font-medium">Notes:</span>
                  <span class="ml-1 text-slate-700 dark:text-slate-300"><?php echo nl2br(e($avDetails['note'])); ?></span>
                </div>
              <?php endif; ?>
            </div>

          <?php elseif ($type === 'media' && $mediaDetails): ?>
            <div class="space-y-3 text-sm">
              <?php if ($mediaDetails['description']): ?>
                <div>
                  <span class="text-<?php echo $clr; ?>-700 dark:text-<?php echo $clr; ?>-400 font-medium">Description:</span>
                  <div class="mt-1 text-slate-700 dark:text-slate-300"><?php echo nl2br(e($mediaDetails['description'])); ?></div>
                </div>
              <?php endif; ?>
              <?php if (!empty($mediaPlatforms)): ?>
                <div>
                  <span class="text-<?php echo $clr; ?>-700 dark:text-<?php echo $clr; ?>-400 font-medium">Platforms:</span>
                  <div class="mt-1 flex flex-wrap gap-1">
                    <?php foreach ($mediaPlatforms as $platform): ?>
                      <span class="inline-flex items-center rounded-full bg-<?php echo $clr; ?>-100 dark:bg-<?php echo $clr; ?>-900/50 px-2 py-0.5 text-xs font-medium text-<?php echo $clr; ?>-800 dark:text-<?php echo $clr; ?>-300">
                        <?php echo ucfirst($platform['platform']); ?>
                        <?php if ($platform['platform'] === 'other' && $platform['platform_other_label']): ?>
                          (<?php echo e($platform['platform_other_label']); ?>)
                        <?php endif; ?>
                      </span>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>
              <?php if ($mediaDetails['promo_start_date'] || $mediaDetails['promo_end_date']): ?>
                <div>
                  <span class="text-<?php echo $clr; ?>-700 dark:text-<?php echo $clr; ?>-400 font-medium">Promo Period:</span>
                  <span class="ml-1 text-slate-700 dark:text-slate-300">
                    <?php echo $mediaDetails['promo_start_date'] ? date('M j, Y', strtotime($mediaDetails['promo_start_date'])) : '-'; ?>
                    to
                    <?php echo $mediaDetails['promo_end_date'] ? date('M j, Y', strtotime($mediaDetails['promo_end_date'])) : '-'; ?>
                  </span>
                </div>
              <?php endif; ?>
              <?php if ($mediaDetails['caption_details']): ?>
                <div>
                  <span class="text-<?php echo $clr; ?>-700 dark:text-<?php echo $clr; ?>-400 font-medium">Caption/Details:</span>
                  <div class="mt-1 text-slate-700 dark:text-slate-300"><?php echo nl2br(e($mediaDetails['caption_details'])); ?></div>
                </div>
              <?php endif; ?>
              <?php if ($mediaDetails['note']): ?>
                <div>
                  <span class="text-<?php echo $clr; ?>-700 dark:text-<?php echo $clr; ?>-400 font-medium">Notes:</span>
                  <div class="mt-1 text-slate-700 dark:text-slate-300"><?php echo nl2br(e($mediaDetails['note'])); ?></div>
                </div>
              <?php endif; ?>
            </div>

            <!-- Content Items Section -->
            <div class="mt-4 pt-4 border-t border-<?php echo $clr; ?>-200 dark:border-<?php echo $clr; ?>-800">
              <?php if ($contentItemCount > 0): ?>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-slate-700 dark:text-slate-300">
                    <i class="fa-solid fa-newspaper mr-1"></i>
                    <?php echo $contentItemCount; ?> content item<?php echo $contentItemCount > 1 ? 's' : ''; ?> generated
                  </span>
                  <a href="content.php?request_id=<?php echo $id; ?>" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">
                    View Content <i class="fa-solid fa-arrow-right ml-1"></i>
                  </a>
                </div>
              <?php else: ?>
                <?php if (can_approve_service('media') || can_assign_pic('media')): ?>
                  <form method="POST" action="actions/generate_content.php"
                        onsubmit="return confirm('Generate content items for all selected platforms?')">
                    <input type="hidden" name="request_id" value="<?php echo $id; ?>">
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-<?php echo $clr; ?>-600 dark:bg-<?php echo $clr; ?>-600 px-4 py-2 text-sm font-semibold text-white hover:bg-<?php echo $clr; ?>-700 dark:hover:bg-<?php echo $clr; ?>-500 transition-colors">
                      <i class="fa-solid fa-wand-magic-sparkles"></i>
                      Generate Content Items
                    </button>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 text-center">
                      Creates content item with channels for each platform
                    </p>
                  </form>
                <?php endif; ?>
              <?php endif; ?>
            </div>

          <?php elseif ($type === 'photo' && $photoDetails): ?>
            <div class="space-y-3 text-sm">
              <?php if ($photoDetails['needed_date']): ?>
                <div>
                  <span class="text-<?php echo $clr; ?>-700 dark:text-<?php echo $clr; ?>-400 font-medium">Date:</span>
                  <span class="ml-1 text-slate-700 dark:text-slate-300">
                    <?php echo date('M j, Y', strtotime($photoDetails['needed_date'])); ?>
                    <?php if ($photoDetails['start_time']): ?>
                      at <?php echo e($photoDetails['start_time']) . ' - ' . e($photoDetails['end_time']); ?>
                    <?php endif; ?>
                  </span>
                </div>
              <?php endif; ?>
              <?php if ($photoDetails['note']): ?>
                <div>
                  <span class="text-<?php echo $clr; ?>-700 dark:text-<?php echo $clr; ?>-400 font-medium">Notes:</span>
                  <div class="mt-1 text-slate-700 dark:text-slate-300"><?php echo nl2br(e($photoDetails['note'])); ?></div>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

    </div><!-- /LEFT COLUMN -->

    <!-- RIGHT COLUMN (1/3) — Workflow & Actions -->
    <div class="space-y-6">

      <!-- Per-Service Approval Blocks -->
      <?php foreach ($serviceTypes as $st):
        $type  = $st['type'];
        $cfg   = $svcConfig[$type] ?? ['label' => ucfirst($type), 'icon' => 'fa-cog', 'color' => 'slate'];
        $clr   = $cfg['color'];
        $canApprove = can_approve_service($type);
        $canAssign  = can_assign_pic($type);
        $picUsers   = $canAssign ? get_users_for_assignment($pdo, $type) : [];
      ?>
        <div class="rounded-xl bg-white dark:bg-slate-800 p-5 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700"
             x-data="{ showReject: false, showInfo: false, showApprove: false }">

          <!-- Service header -->
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-<?php echo $clr; ?>-100 dark:bg-<?php echo $clr; ?>-900/30">
                <i class="fa-solid <?php echo $cfg['icon']; ?> text-sm text-<?php echo $clr; ?>-600 dark:text-<?php echo $clr; ?>-400"></i>
              </div>
              <span class="font-semibold text-slate-900 dark:text-slate-100"><?php echo $cfg['label']; ?></span>
            </div>
            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?php echo approval_status_classes($st['approval_status']); ?>">
              <?php echo approval_status_label($st['approval_status']); ?>
            </span>
          </div>

          <!-- PIC Assignment -->
          <?php if ($canAssign): ?>
            <div class="mb-4" x-data="{ editPic: false }">
              <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Assigned PIC</div>
              <template x-if="!editPic">
                <div class="flex items-center gap-2">
                  <span class="text-sm text-slate-900 dark:text-slate-100">
                    <?php echo $st['pic_name'] ? e($st['pic_name']) : '<span class="text-slate-400 italic">Unassigned</span>'; ?>
                  </span>
                  <button @click="editPic = true" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">Change</button>
                </div>
              </template>
              <template x-if="editPic">
                <form method="POST" action="actions/assign_pic.php" class="flex items-center gap-2">
                  <input type="hidden" name="request_id" value="<?php echo $id; ?>">
                  <input type="hidden" name="service_type" value="<?php echo $type; ?>">
                  <select name="user_id" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-2 py-1 text-sm text-slate-900 dark:text-slate-100">
                    <option value="">-- Unassign --</option>
                    <?php foreach ($picUsers as $pu): ?>
                      <option value="<?php echo $pu['id']; ?>" <?php echo $st['assigned_pic_user_id'] == $pu['id'] ? 'selected' : ''; ?>>
                        <?php echo e($pu['name']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" class="rounded-lg bg-slate-900 dark:bg-slate-100 px-3 py-1 text-xs font-medium text-white dark:text-slate-900">Save</button>
                  <button type="button" @click="editPic = false" class="text-xs text-slate-500 hover:text-slate-700">Cancel</button>
                </form>
              </template>
            </div>
          <?php elseif ($st['pic_name']): ?>
            <div class="mb-4">
              <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Assigned PIC</div>
              <span class="text-sm text-slate-900 dark:text-slate-100"><?php echo e($st['pic_name']); ?></span>
            </div>
          <?php endif; ?>

          <!-- Decision Log -->
          <?php if ($st['approved_at']): ?>
            <div class="mb-4 rounded-lg bg-slate-50 dark:bg-slate-900 p-3 text-xs text-slate-600 dark:text-slate-400">
              <div class="flex items-center gap-1">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span class="font-medium"><?php echo approval_status_label($st['approval_status']); ?></span>
                by <span class="font-medium text-slate-900 dark:text-slate-100"><?php echo e($st['approver_name']); ?></span>
                on <?php echo date('M j, Y g:i A', strtotime($st['approved_at'])); ?>
              </div>
              <?php if ($st['rejected_reason']): ?>
                <div class="mt-2 text-red-600 dark:text-red-400">
                  <span class="font-medium">Reason:</span> <?php echo e($st['rejected_reason']); ?>
                </div>
              <?php endif; ?>
              <?php if ($st['decision_note']): ?>
                <div class="mt-1"><span class="font-medium">Note:</span> <?php echo e($st['decision_note']); ?></div>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <!-- Action Buttons -->
          <?php if ($canApprove): ?>
            <div class="space-y-2">
              <!-- Approve -->
              <button @click="showApprove = !showApprove; showReject = false; showInfo = false"
                      class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-green-600 dark:bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700 dark:hover:bg-green-500 transition-colors">
                <i class="fa-solid fa-check"></i> Approve
              </button>
              <div x-show="showApprove" x-collapse class="rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 p-3">
                <form method="POST" action="actions/approve_service.php">
                  <input type="hidden" name="request_id" value="<?php echo $id; ?>">
                  <input type="hidden" name="service_type" value="<?php echo $type; ?>">
                  <textarea name="decision_note" rows="2" placeholder="Optional note..." class="w-full mb-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-green-500"></textarea>
                  <div class="flex gap-2">
                    <button type="submit" class="flex-1 rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-700">Confirm Approve</button>
                    <button type="button" @click="showApprove = false" class="rounded-lg border border-slate-300 dark:border-slate-600 px-3 py-1.5 text-xs text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700">Cancel</button>
                  </div>
                </form>
              </div>

              <!-- Reject -->
              <button @click="showReject = !showReject; showApprove = false; showInfo = false"
                      class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 dark:bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 dark:hover:bg-red-500 transition-colors">
                <i class="fa-solid fa-xmark"></i> Reject
              </button>
              <div x-show="showReject" x-collapse class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-3">
                <form method="POST" action="actions/reject_service.php" x-data="{ reason: '' }">
                  <input type="hidden" name="request_id" value="<?php echo $id; ?>">
                  <input type="hidden" name="service_type" value="<?php echo $type; ?>">
                  <textarea name="rejected_reason" x-model="reason" rows="3" required placeholder="Reason for rejection *" class="w-full mb-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-red-500"></textarea>
                  <div class="flex gap-2">
                    <button type="submit" :disabled="reason.trim() === ''" class="flex-1 rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed">Confirm Reject</button>
                    <button type="button" @click="showReject = false" class="rounded-lg border border-slate-300 dark:border-slate-600 px-3 py-1.5 text-xs text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700">Cancel</button>
                  </div>
                </form>
              </div>

              <!-- Request More Info -->
              <button @click="showInfo = !showInfo; showApprove = false; showReject = false"
                      class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-orange-300 dark:border-orange-700 bg-orange-50 dark:bg-orange-900/20 px-4 py-2 text-sm font-semibold text-orange-700 dark:text-orange-400 hover:bg-orange-100 dark:hover:bg-orange-900/40 transition-colors">
                <i class="fa-solid fa-question-circle"></i> Request More Info
              </button>
              <div x-show="showInfo" x-collapse class="rounded-lg border border-orange-200 dark:border-orange-800 bg-orange-50 dark:bg-orange-900/20 p-3">
                <form method="POST" action="actions/request_info.php" x-data="{ question: '' }">
                  <input type="hidden" name="request_id" value="<?php echo $id; ?>">
                  <input type="hidden" name="service_type" value="<?php echo $type; ?>">
                  <textarea name="decision_note" x-model="question" rows="3" required placeholder="What information do you need? *" class="w-full mb-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-orange-500"></textarea>
                  <div class="flex gap-2">
                    <button type="submit" :disabled="question.trim() === ''" class="flex-1 rounded-lg bg-orange-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-orange-600 disabled:opacity-50 disabled:cursor-not-allowed">Send Request</button>
                    <button type="button" @click="showInfo = false" class="rounded-lg border border-slate-300 dark:border-slate-600 px-3 py-1.5 text-xs text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700">Cancel</button>
                  </div>
                </form>
              </div>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

      <!-- Internal Notes -->
      <div id="notes" class="rounded-xl bg-white dark:bg-slate-800 p-5 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
        <h3 class="flex items-center gap-2 font-semibold text-slate-900 dark:text-slate-100 mb-4">
          <i class="fa-solid fa-sticky-note text-slate-400"></i>
          Internal Notes
          <?php if (count($internalNotes)): ?>
            <span class="rounded-full bg-slate-100 dark:bg-slate-700 px-2 py-0.5 text-xs text-slate-600 dark:text-slate-400"><?php echo count($internalNotes); ?></span>
          <?php endif; ?>
        </h3>

        <!-- Add note form -->
        <form method="POST" action="actions/add_note.php" class="mb-4" x-data="{ note: '' }">
          <input type="hidden" name="request_id" value="<?php echo $id; ?>">
          <textarea name="note" x-model="note" rows="2" placeholder="Add an internal note..."
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-400 mb-2"></textarea>
          <button type="submit" :disabled="note.trim() === ''"
                  class="rounded-lg bg-slate-900 dark:bg-slate-100 px-4 py-1.5 text-xs font-medium text-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-slate-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
            Add Note
          </button>
        </form>

        <!-- Notes list -->
        <?php if (empty($internalNotes)): ?>
          <p class="text-sm text-slate-400 dark:text-slate-500 italic">No notes yet</p>
        <?php else: ?>
          <div class="space-y-3 max-h-96 overflow-y-auto">
            <?php foreach ($internalNotes as $note): ?>
              <div class="rounded-lg bg-slate-50 dark:bg-slate-900 p-3 text-sm">
                <div class="flex items-center justify-between mb-1">
                  <span class="font-medium text-slate-900 dark:text-slate-100"><?php echo e($note['actor_name']); ?></span>
                  <span class="text-xs text-slate-500 dark:text-slate-400"><?php echo date('M j, g:i A', strtotime($note['created_at'])); ?></span>
                </div>
                <div class="text-slate-700 dark:text-slate-300"><?php echo nl2br(e($note['note'])); ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

    </div><!-- /RIGHT COLUMN -->

  </div><!-- /Two Column Grid -->

</main>

<?php include __DIR__ . "/partials/footer.php"; ?>
