<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_admin_auth();
require_once __DIR__ . '/../../includes/db.php';

// ---------------------------------------------------------------------------
// Filters from GET params
// ---------------------------------------------------------------------------
$filterStatus   = $_GET['status'] ?? '';
$filterService  = $_GET['service'] ?? '';
$filterLate     = isset($_GET['late']) && $_GET['late'] === '1';
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo   = $_GET['date_to'] ?? '';
$filterMinistry = trim($_GET['ministry'] ?? '');
$filterSearch   = trim($_GET['search'] ?? '');
$page           = max(1, (int) ($_GET['page'] ?? 1));
$perPage        = 25;
$offset         = ($page - 1) * $perPage;

$hasFilters = $filterStatus || $filterService || $filterLate || $filterDateFrom || $filterDateTo || $filterMinistry || $filterSearch;

// ---------------------------------------------------------------------------
// Stats cards — separate fast queries
// ---------------------------------------------------------------------------
$pendingOverall = (int) $pdo->query("SELECT COUNT(*) FROM media_requests WHERE request_status = 'pending'")->fetchColumn();

$pendingAV    = (int) $pdo->query("SELECT COUNT(*) FROM request_types WHERE type = 'av' AND approval_status = 'pending'")->fetchColumn();
$pendingMedia = (int) $pdo->query("SELECT COUNT(*) FROM request_types WHERE type = 'media' AND approval_status = 'pending'")->fetchColumn();
$pendingPhoto = (int) $pdo->query("SELECT COUNT(*) FROM request_types WHERE type = 'photo' AND approval_status = 'pending'")->fetchColumn();

$lateCount = (int) $pdo->query("SELECT COUNT(*) FROM media_requests WHERE is_late = 1 AND request_status IN ('pending','approved','in_progress')")->fetchColumn();

$eventsNext7 = (int) $pdo->query("
  SELECT COUNT(DISTINCT mr.id)
  FROM media_requests mr
  JOIN event_schedules es ON mr.id = es.media_request_id
  WHERE es.start_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    AND mr.request_status NOT IN ('rejected','cancelled')
")->fetchColumn();

// ---------------------------------------------------------------------------
// Build request list query with dynamic filters
// ---------------------------------------------------------------------------
$where  = [];
$params = [];

if ($filterStatus !== '') {
    $where[] = 'mr.request_status = :status';
    $params['status'] = $filterStatus;
}

if ($filterLate) {
    $where[] = 'mr.is_late = 1';
}

if ($filterMinistry !== '') {
    $where[] = 'mr.ministry LIKE :ministry';
    $params['ministry'] = '%' . $filterMinistry . '%';
}

if ($filterSearch !== '') {
    $where[] = '(mr.reference_no LIKE :search OR mr.event_name LIKE :search2 OR mr.requestor_name LIKE :search3)';
    $params['search']  = '%' . $filterSearch . '%';
    $params['search2'] = '%' . $filterSearch . '%';
    $params['search3'] = '%' . $filterSearch . '%';
}

if ($filterDateFrom !== '') {
    $where[] = 'es_agg.earliest_date >= :date_from';
    $params['date_from'] = $filterDateFrom;
}

if ($filterDateTo !== '') {
    $where[] = 'es_agg.earliest_date <= :date_to';
    $params['date_to'] = $filterDateTo;
}

// Service filter — must have that service type in request_types
if ($filterService !== '') {
    $where[] = 'EXISTS (SELECT 1 FROM request_types rt_f WHERE rt_f.media_request_id = mr.id AND rt_f.type = :svc_filter)';
    $params['svc_filter'] = $filterService;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Sub-select for earliest event date so we can filter on it
$baseFrom = "
  FROM media_requests mr
  LEFT JOIN (
    SELECT media_request_id, MIN(start_date) AS earliest_date, MAX(COALESCE(end_date, start_date)) AS latest_date
    FROM event_schedules
    GROUP BY media_request_id
  ) es_agg ON mr.id = es_agg.media_request_id
  $whereSQL
";

// Count
$countSQL = "SELECT COUNT(*) $baseFrom";
$stmt = $pdo->prepare($countSQL);
$stmt->execute($params);
$totalRows  = (int) $stmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

// Main data query with service info
$dataSQL = "
  SELECT
    mr.id,
    mr.reference_no,
    mr.event_name,
    mr.requestor_name,
    mr.ministry,
    mr.request_status,
    mr.is_late,
    mr.lead_days,
    mr.submitted_at,
    es_agg.earliest_date,
    es_agg.latest_date,
    (SELECT GROUP_CONCAT(CONCAT(rt2.type, ':', rt2.approval_status) ORDER BY rt2.type)
     FROM request_types rt2 WHERE rt2.media_request_id = mr.id) AS service_info,
    (SELECT es2.schedule_type FROM event_schedules es2 WHERE es2.media_request_id = mr.id LIMIT 1) AS schedule_type
  $baseFrom
  ORDER BY mr.submitted_at DESC
  LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($dataSQL);
$stmt->execute($params);
$requests = $stmt->fetchAll();

$pageTitle = "Dashboard | CDM Admin";
include __DIR__ . "/partials/header.php";
?>

<main class="mx-auto max-w-7xl px-4 py-8">

  <!-- Page Header -->
  <div class="mb-8">
    <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-50">Media Request Dashboard</h1>
    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
      View and manage all media requests submitted by the community
    </p>
  </div>

  <!-- Stats Cards -->
  <div class="mb-8 grid grid-cols-2 gap-4 lg:grid-cols-6">
    <?php
    $cards = [
      ['label' => 'Pending Requests', 'value' => $pendingOverall, 'color' => 'amber',  'icon' => 'fa-clock'],
      ['label' => 'AV Pending',       'value' => $pendingAV,      'color' => 'purple', 'icon' => 'fa-volume-high'],
      ['label' => 'Media Pending',    'value' => $pendingMedia,   'color' => 'green',  'icon' => 'fa-palette'],
      ['label' => 'Photo Pending',    'value' => $pendingPhoto,   'color' => 'blue',   'icon' => 'fa-camera'],
      ['label' => 'Late Submissions', 'value' => $lateCount,      'color' => 'red',    'icon' => 'fa-exclamation-triangle'],
      ['label' => 'Events (7 days)',  'value' => $eventsNext7,    'color' => 'slate',  'icon' => 'fa-calendar-day'],
    ];
    foreach ($cards as $card):
      $c = $card['color'];
    ?>
    <div class="rounded-xl bg-white dark:bg-slate-800 p-4 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
      <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-<?php echo $c; ?>-100 dark:bg-<?php echo $c; ?>-900/30">
          <i class="fa-solid <?php echo $card['icon']; ?> text-<?php echo $c; ?>-600 dark:text-<?php echo $c; ?>-400"></i>
        </div>
        <div class="min-w-0">
          <p class="text-xs text-slate-500 dark:text-slate-400 truncate"><?php echo $card['label']; ?></p>
          <p class="text-2xl font-bold text-<?php echo $c; ?>-600 dark:text-<?php echo $c; ?>-400"><?php echo $card['value']; ?></p>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Filter Bar -->
  <div x-data="{ filtersOpen: <?php echo $hasFilters ? 'true' : 'false'; ?> }" class="mb-6 rounded-xl bg-white dark:bg-slate-800 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
    <button @click="filtersOpen = !filtersOpen"
            class="flex w-full items-center justify-between px-6 py-4 text-left">
      <div class="flex items-center gap-2">
        <i class="fa-solid fa-filter text-slate-400"></i>
        <span class="font-semibold text-slate-900 dark:text-slate-50">Filters</span>
        <?php if ($hasFilters): ?>
          <span class="rounded-full bg-blue-100 dark:bg-blue-900/30 px-2 py-0.5 text-xs font-medium text-blue-700 dark:text-blue-400">Active</span>
        <?php endif; ?>
      </div>
      <i class="fa-solid fa-chevron-down text-slate-400 transition-transform" :class="filtersOpen && 'rotate-180'"></i>
    </button>

    <form x-show="filtersOpen" x-collapse method="GET" action="index.php"
          class="border-t border-slate-200 dark:border-slate-700 px-6 py-4">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Status -->
        <div>
          <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Status</label>
          <select name="status" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100">
            <option value="">All Statuses</option>
            <?php foreach (['pending','approved','in_progress','completed','rejected','cancelled'] as $s): ?>
              <option value="<?php echo $s; ?>" <?php echo $filterStatus === $s ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $s)); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Service -->
        <div>
          <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Service</label>
          <select name="service" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100">
            <option value="">All Services</option>
            <option value="av" <?php echo $filterService === 'av' ? 'selected' : ''; ?>>AV</option>
            <option value="media" <?php echo $filterService === 'media' ? 'selected' : ''; ?>>Media / Design</option>
            <option value="photo" <?php echo $filterService === 'photo' ? 'selected' : ''; ?>>Photography</option>
          </select>
        </div>

        <!-- Search -->
        <div>
          <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Search</label>
          <input type="text" name="search" value="<?php echo e($filterSearch); ?>" placeholder="Ref, event, requestor..."
                 class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100">
        </div>

        <!-- Ministry -->
        <div>
          <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Ministry</label>
          <input type="text" name="ministry" value="<?php echo e($filterMinistry); ?>" placeholder="Filter by ministry..."
                 class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100">
        </div>

        <!-- Date From -->
        <div>
          <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Event Date From</label>
          <input type="date" name="date_from" value="<?php echo e($filterDateFrom); ?>"
                 class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100">
        </div>

        <!-- Date To -->
        <div>
          <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Event Date To</label>
          <input type="date" name="date_to" value="<?php echo e($filterDateTo); ?>"
                 class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100">
        </div>

        <!-- Late Only -->
        <div class="flex items-end">
          <label class="flex items-center gap-2 cursor-pointer py-2">
            <input type="checkbox" name="late" value="1" <?php echo $filterLate ? 'checked' : ''; ?>
                   class="h-4 w-4 rounded border-slate-300 dark:border-slate-600 text-amber-600 focus:ring-amber-500">
            <span class="text-sm text-slate-700 dark:text-slate-300">Late only</span>
          </label>
        </div>

        <!-- Actions -->
        <div class="flex items-end gap-2">
          <button type="submit" class="rounded-lg bg-slate-900 dark:bg-slate-100 px-4 py-2 text-sm font-medium text-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-slate-200 transition-colors">
            <i class="fa-solid fa-search mr-1"></i> Filter
          </button>
          <?php if ($hasFilters): ?>
            <a href="index.php" class="rounded-lg border border-slate-300 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
              Clear
            </a>
          <?php endif; ?>
        </div>
      </div>
    </form>
  </div>

  <!-- Requests Table -->
  <div class="rounded-xl bg-white dark:bg-slate-800 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700 overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-700">
      <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-50">
        Requests
        <span class="ml-2 text-sm font-normal text-slate-500 dark:text-slate-400">(<?php echo $totalRows; ?> total)</span>
      </h2>
    </div>

    <?php if (empty($requests)): ?>
      <div class="px-6 py-12 text-center">
        <div class="flex h-16 w-16 mx-auto items-center justify-center rounded-full bg-slate-100 dark:bg-slate-700 mb-4">
          <i class="fa-solid fa-inbox text-2xl text-slate-400 dark:text-slate-500"></i>
        </div>
        <p class="text-slate-600 dark:text-slate-400">
          <?php echo $hasFilters ? 'No requests match your filters' : 'No requests submitted yet'; ?>
        </p>
        <?php if ($hasFilters): ?>
          <a href="index.php" class="mt-3 inline-block text-sm text-blue-600 dark:text-blue-400 hover:underline">Clear filters</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <!-- Table (Desktop) -->
      <div class="hidden lg:block overflow-x-auto">
        <table class="w-full">
          <thead class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-600 dark:text-slate-400">Reference</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-600 dark:text-slate-400">Event</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-600 dark:text-slate-400">Event Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-600 dark:text-slate-400">Services</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-600 dark:text-slate-400">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-600 dark:text-slate-400">Lead</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-600 dark:text-slate-400">Submitted</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-600 dark:text-slate-400"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
            <?php foreach ($requests as $req):
              // Parse service_info → e.g. "av:approved,media:pending,photo:rejected"
              $serviceList = [];
              if ($req['service_info']) {
                  foreach (explode(',', $req['service_info']) as $si) {
                      [$sType, $sStatus] = explode(':', $si);
                      $serviceList[$sType] = $sStatus;
                  }
              }
            ?>
              <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors cursor-pointer"
                  onclick="window.location='view.php?id=<?php echo $req['id']; ?>'">
                <!-- Reference + Late -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center gap-2">
                    <span class="font-mono text-sm font-semibold text-slate-900 dark:text-slate-100">
                      <?php echo e($req['reference_no']); ?>
                    </span>
                    <?php if ($req['is_late']): ?>
                      <span class="inline-flex items-center rounded-full bg-amber-100 dark:bg-amber-900/30 px-2 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-400" title="Late submission">
                        Late
                      </span>
                    <?php endif; ?>
                  </div>
                  <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    <?php echo e($req['requestor_name']); ?>
                    <?php if ($req['ministry']): ?>
                      &middot; <?php echo e($req['ministry']); ?>
                    <?php endif; ?>
                  </div>
                </td>

                <!-- Event -->
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-slate-900 dark:text-slate-100 max-w-[200px] truncate">
                    <?php echo e($req['event_name']); ?>
                  </div>
                  <?php if ($req['schedule_type']): ?>
                    <span class="text-xs text-slate-500 dark:text-slate-400"><?php echo ucfirst(str_replace('_', ' ', $req['schedule_type'])); ?></span>
                  <?php endif; ?>
                </td>

                <!-- Event Date -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <?php if ($req['earliest_date']): ?>
                    <div class="text-sm text-slate-900 dark:text-slate-100">
                      <?php echo date('M j, Y', strtotime($req['earliest_date'])); ?>
                    </div>
                    <?php if ($req['latest_date'] && $req['latest_date'] !== $req['earliest_date']): ?>
                      <div class="text-xs text-slate-500 dark:text-slate-400">
                        to <?php echo date('M j, Y', strtotime($req['latest_date'])); ?>
                      </div>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="text-sm text-slate-400 dark:text-slate-500">-</span>
                  <?php endif; ?>
                </td>

                <!-- Services with per-service status -->
                <td class="px-6 py-4">
                  <div class="flex flex-wrap gap-1">
                    <?php
                    $svcConfig = [
                      'av'    => ['label' => 'AV',    'color' => 'purple'],
                      'media' => ['label' => 'Media', 'color' => 'green'],
                      'photo' => ['label' => 'Photo', 'color' => 'blue'],
                    ];
                    foreach ($serviceList as $sType => $sStatus):
                      $cfg = $svcConfig[$sType] ?? ['label' => ucfirst($sType), 'color' => 'slate'];
                      $statusIcon = match($sStatus) {
                        'approved'        => '<i class="fa-solid fa-check text-[9px]"></i>',
                        'rejected'        => '<i class="fa-solid fa-xmark text-[9px]"></i>',
                        'needs_more_info' => '<i class="fa-solid fa-question text-[9px]"></i>',
                        'in_progress'     => '<i class="fa-solid fa-spinner text-[9px]"></i>',
                        'completed'       => '<i class="fa-solid fa-check-double text-[9px]"></i>',
                        default           => '',
                      };
                    ?>
                      <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium bg-<?php echo $cfg['color']; ?>-100 dark:bg-<?php echo $cfg['color']; ?>-900/30 text-<?php echo $cfg['color']; ?>-700 dark:text-<?php echo $cfg['color']; ?>-400"
                            title="<?php echo $cfg['label'] . ': ' . approval_status_label($sStatus); ?>">
                        <?php echo $cfg['label']; ?>
                        <?php echo $statusIcon; ?>
                      </span>
                    <?php endforeach; ?>
                  </div>
                </td>

                <!-- Overall Status -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?php echo approval_status_classes($req['request_status']); ?>">
                    <?php echo approval_status_label($req['request_status']); ?>
                  </span>
                </td>

                <!-- Lead Days -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <?php if ($req['lead_days'] !== null): ?>
                    <span class="text-sm <?php echo $req['lead_days'] < 14 ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-slate-600 dark:text-slate-400'; ?>">
                      <?php echo $req['lead_days']; ?>d
                    </span>
                  <?php else: ?>
                    <span class="text-sm text-slate-400">-</span>
                  <?php endif; ?>
                </td>

                <!-- Submitted -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-slate-600 dark:text-slate-400">
                    <?php echo date('M j, Y', strtotime($req['submitted_at'])); ?>
                  </div>
                  <div class="text-xs text-slate-400 dark:text-slate-500">
                    <?php echo date('g:i A', strtotime($req['submitted_at'])); ?>
                  </div>
                </td>

                <!-- View -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <a href="view.php?id=<?php echo $req['id']; ?>"
                     class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                     onclick="event.stopPropagation()">
                    <i class="fa-solid fa-eye"></i> View
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Cards (Mobile) -->
      <div class="lg:hidden divide-y divide-slate-200 dark:divide-slate-700">
        <?php foreach ($requests as $req):
          $serviceList = [];
          if ($req['service_info']) {
              foreach (explode(',', $req['service_info']) as $si) {
                  [$sType, $sStatus] = explode(':', $si);
                  $serviceList[$sType] = $sStatus;
              }
          }
        ?>
          <a href="view.php?id=<?php echo $req['id']; ?>" class="block px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
            <div class="flex items-start justify-between mb-2">
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                  <span class="font-mono text-sm font-semibold text-slate-900 dark:text-slate-100">
                    <?php echo e($req['reference_no']); ?>
                  </span>
                  <?php if ($req['is_late']): ?>
                    <span class="inline-flex items-center rounded-full bg-amber-100 dark:bg-amber-900/30 px-2 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-400">Late</span>
                  <?php endif; ?>
                </div>
                <div class="text-sm font-medium text-slate-900 dark:text-slate-100 truncate">
                  <?php echo e($req['event_name']); ?>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                  <?php echo e($req['requestor_name']); ?>
                  <?php if ($req['ministry']): ?> &middot; <?php echo e($req['ministry']); ?><?php endif; ?>
                </div>
              </div>
              <span class="ml-2 shrink-0 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?php echo approval_status_classes($req['request_status']); ?>">
                <?php echo approval_status_label($req['request_status']); ?>
              </span>
            </div>

            <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400 mb-2">
              <?php if ($req['earliest_date']): ?>
                <span><i class="fa-solid fa-calendar mr-1"></i><?php echo date('M j, Y', strtotime($req['earliest_date'])); ?></span>
              <?php endif; ?>
              <span>Submitted <?php echo date('M j', strtotime($req['submitted_at'])); ?></span>
              <?php if ($req['lead_days'] !== null && $req['lead_days'] < 14): ?>
                <span class="text-red-500 font-medium"><?php echo $req['lead_days']; ?>d lead</span>
              <?php endif; ?>
            </div>

            <div class="flex flex-wrap gap-1">
              <?php
              $svcConfig = [
                'av'    => ['label' => 'AV',    'color' => 'purple'],
                'media' => ['label' => 'Media', 'color' => 'green'],
                'photo' => ['label' => 'Photo', 'color' => 'blue'],
              ];
              foreach ($serviceList as $sType => $sStatus):
                $cfg = $svcConfig[$sType] ?? ['label' => ucfirst($sType), 'color' => 'slate'];
              ?>
                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium bg-<?php echo $cfg['color']; ?>-100 dark:bg-<?php echo $cfg['color']; ?>-900/30 text-<?php echo $cfg['color']; ?>-700 dark:text-<?php echo $cfg['color']; ?>-400">
                  <?php echo $cfg['label']; ?>: <?php echo approval_status_label($sStatus); ?>
                </span>
              <?php endforeach; ?>
            </div>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <div class="flex items-center justify-between border-t border-slate-200 dark:border-slate-700 px-6 py-4">
          <div class="text-sm text-slate-600 dark:text-slate-400">
            Page <?php echo $page; ?> of <?php echo $totalPages; ?>
            (<?php echo $totalRows; ?> results)
          </div>
          <div class="flex gap-2">
            <?php
            // Build current filter params for pagination links
            $filterParams = array_filter([
              'status'   => $filterStatus,
              'service'  => $filterService,
              'late'     => $filterLate ? '1' : '',
              'date_from'=> $filterDateFrom,
              'date_to'  => $filterDateTo,
              'ministry' => $filterMinistry,
              'search'   => $filterSearch,
            ]);
            ?>
            <?php if ($page > 1): ?>
              <a href="?<?php echo http_build_query(array_merge($filterParams, ['page' => $page - 1])); ?>"
                 class="rounded-lg border border-slate-300 dark:border-slate-600 px-3 py-1.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <i class="fa-solid fa-chevron-left mr-1"></i> Prev
              </a>
            <?php endif; ?>
            <?php if ($page < $totalPages): ?>
              <a href="?<?php echo http_build_query(array_merge($filterParams, ['page' => $page + 1])); ?>"
                 class="rounded-lg border border-slate-300 dark:border-slate-600 px-3 py-1.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                Next <i class="fa-solid fa-chevron-right ml-1"></i>
              </a>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

</main>

<?php include __DIR__ . "/partials/footer.php"; ?>
