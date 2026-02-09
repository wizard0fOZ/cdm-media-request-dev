<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_admin_auth();
require_once __DIR__ . '/../../includes/db.php';

// ---------------------------------------------------------------------------
// Filters from GET params
// ---------------------------------------------------------------------------
$filterAssetStatus = $_GET['asset_status'] ?? '';
$filterContentType = $_GET['content_type'] ?? '';
$filterChannel     = $_GET['channel'] ?? '';
$filterPic         = $_GET['pic'] ?? '';
$filterDateFrom    = $_GET['date_from'] ?? '';
$filterDateTo      = $_GET['date_to'] ?? '';
$filterSearch      = trim($_GET['search'] ?? '');
$filterRequestId   = ($_GET['request_id'] ?? '') !== '' ? (int) $_GET['request_id'] : null;
$activeView        = $_GET['view'] ?? 'list';
$page              = max(1, (int) ($_GET['page'] ?? 1));
$perPage           = 25;
$offset            = ($page - 1) * $perPage;

$generated  = isset($_GET['generated']);
$success    = isset($_GET['success']);

$hasFilters = $filterAssetStatus || $filterContentType || $filterChannel || $filterPic || $filterDateFrom || $filterDateTo || $filterSearch || $filterRequestId;

$user       = current_user();
$canManage  = can_manage_content();

// ---------------------------------------------------------------------------
// Stats cards
// ---------------------------------------------------------------------------
$totalItems     = (int) $pdo->query("SELECT COUNT(*) FROM content_items")->fetchColumn();
$pendingDesign  = (int) $pdo->query("SELECT COUNT(*) FROM content_items WHERE asset_status = 'pending'")->fetchColumn();
$inProgress     = (int) $pdo->query("SELECT COUNT(*) FROM content_items WHERE asset_status = 'in_progress'")->fetchColumn();
$readyDone      = (int) $pdo->query("SELECT COUNT(*) FROM content_items WHERE asset_status IN ('ready','done')")->fetchColumn();

// ---------------------------------------------------------------------------
// Build filtered content items query
// ---------------------------------------------------------------------------
$where  = [];
$params = [];

if ($filterAssetStatus !== '') {
    $where[] = 'ci.asset_status = :asset_status';
    $params['asset_status'] = $filterAssetStatus;
}

if ($filterContentType !== '') {
    $where[] = 'ci.content_type = :content_type';
    $params['content_type'] = $filterContentType;
}

if ($filterChannel !== '') {
    $where[] = 'EXISTS (SELECT 1 FROM content_channels cc_f WHERE cc_f.content_item_id = ci.id AND cc_f.channel = :channel)';
    $params['channel'] = $filterChannel;
}

if ($filterPic !== '') {
    $where[] = '(ci.asset_pic_user_id = :pic1 OR ci.socmed_pic_user_id = :pic2)';
    $params['pic1'] = $filterPic;
    $params['pic2'] = $filterPic;
}

if ($filterDateFrom !== '') {
    $where[] = 'ci.promo_start_date >= :date_from';
    $params['date_from'] = $filterDateFrom;
}

if ($filterDateTo !== '') {
    $where[] = '(ci.promo_end_date <= :date_to OR (ci.promo_end_date IS NULL AND ci.promo_start_date <= :date_to2))';
    $params['date_to'] = $filterDateTo;
    $params['date_to2'] = $filterDateTo;
}

if ($filterSearch !== '') {
    $where[] = '(ci.title LIKE :search1 OR mr.reference_no LIKE :search2 OR mr.event_name LIKE :search3)';
    $params['search1'] = '%' . $filterSearch . '%';
    $params['search2'] = '%' . $filterSearch . '%';
    $params['search3'] = '%' . $filterSearch . '%';
}

if ($filterRequestId !== null) {
    $where[] = 'ci.media_request_id = :request_id';
    $params['request_id'] = $filterRequestId;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$baseFrom = "
    FROM content_items ci
    LEFT JOIN media_requests mr ON ci.media_request_id = mr.id
    $whereSQL
";

// Count
$countSQL = "SELECT COUNT(*) $baseFrom";
$stmt = $pdo->prepare($countSQL);
$stmt->execute($params);
$totalFiltered = (int) $stmt->fetchColumn();
$totalPages    = max(1, (int) ceil($totalFiltered / $perPage));

// Data query
$dataSQL = "
    SELECT
        ci.id,
        ci.title,
        ci.content_type,
        ci.asset_status,
        ci.caption_status,
        ci.promo_start_date,
        ci.promo_end_date,
        ci.default_publish_at,
        ci.asset_url,
        ci.do_not_display,
        ci.notes,
        ci.caption_brief,
        ci.final_caption,
        ci.language,
        ci.asset_pic_user_id,
        ci.socmed_pic_user_id,
        ci.created_at,
        ci.media_request_id,
        mr.reference_no,
        mr.event_name,
        asset_pic.name AS asset_pic_name,
        socmed_pic.name AS socmed_pic_name,
        (SELECT GROUP_CONCAT(CONCAT(cc2.channel, ':', cc2.status) ORDER BY cc2.channel)
         FROM content_channels cc2 WHERE cc2.content_item_id = ci.id) AS channel_info
    FROM content_items ci
    LEFT JOIN media_requests mr ON ci.media_request_id = mr.id
    LEFT JOIN users asset_pic ON ci.asset_pic_user_id = asset_pic.id
    LEFT JOIN users socmed_pic ON ci.socmed_pic_user_id = socmed_pic.id
    $whereSQL
    ORDER BY ci.created_at DESC
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($dataSQL);
$stmt->execute($params);
$items = $stmt->fetchAll();

// Users for PIC assignment dropdowns
$picUsers = $canManage ? get_users_for_assignment($pdo, 'media') : [];

// Request name for pre-filter header
$filterRequestName = null;
if ($filterRequestId) {
    $stmt = $pdo->prepare("SELECT CONCAT(reference_no, ' — ', event_name) FROM media_requests WHERE id = :id");
    $stmt->execute(['id' => $filterRequestId]);
    $filterRequestName = $stmt->fetchColumn() ?: null;
}

// Build current query string for pagination links
$queryParams = array_filter([
    'status'       => $filterAssetStatus ?: null,
    'asset_status' => $filterAssetStatus ?: null,
    'content_type' => $filterContentType ?: null,
    'channel'      => $filterChannel ?: null,
    'pic'          => $filterPic ?: null,
    'date_from'    => $filterDateFrom ?: null,
    'date_to'      => $filterDateTo ?: null,
    'search'       => $filterSearch ?: null,
    'request_id'   => $filterRequestId ? (string) $filterRequestId : null,
    'view'         => $activeView !== 'list' ? $activeView : null,
]);

$pageTitle = "Content Calendar | CDM Admin";
include __DIR__ . "/partials/header.php";
?>

<main class="mx-auto max-w-7xl px-4 py-8"
      x-data="contentPage()">

  <!-- Page Header -->
  <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
    <div>
      <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-50">Content Calendar</h1>
      <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
        <?php if ($filterRequestName): ?>
          Showing content for: <strong><?php echo e($filterRequestName); ?></strong>
          <a href="content.php" class="ml-2 text-blue-600 dark:text-blue-400 hover:underline">Clear</a>
        <?php else: ?>
          Manage content items and publishing schedule
        <?php endif; ?>
      </p>
    </div>
    <div class="flex items-center gap-3">
      <!-- View toggle -->
      <div class="inline-flex rounded-lg border border-slate-300 dark:border-slate-600 overflow-hidden">
        <a href="?<?php echo http_build_query(array_merge($queryParams, ['view' => 'list'])); ?>"
           class="px-3 py-1.5 text-sm font-medium <?php echo $activeView === 'list' ? 'bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700'; ?>">
          <i class="fa-solid fa-list mr-1"></i> List
        </a>
        <a href="?<?php echo http_build_query(array_merge($queryParams, ['view' => 'spreadsheet'])); ?>"
           class="px-3 py-1.5 text-sm font-medium <?php echo $activeView === 'spreadsheet' ? 'bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700'; ?>">
          <i class="fa-solid fa-table-cells mr-1"></i> Spreadsheet
        </a>
        <a href="?<?php echo http_build_query(array_merge($queryParams, ['view' => 'calendar'])); ?>"
           class="px-3 py-1.5 text-sm font-medium <?php echo $activeView === 'calendar' ? 'bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700'; ?>">
          <i class="fa-solid fa-calendar mr-1"></i> Calendar
        </a>
      </div>
      <?php if ($canManage): ?>
        <button @click="openCreateModal()"
                class="inline-flex items-center gap-2 rounded-lg bg-slate-900 dark:bg-slate-100 px-4 py-2 text-sm font-semibold text-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-slate-200 transition-colors">
          <i class="fa-solid fa-plus"></i> New Content
        </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- Success / Generated Banner -->
  <?php if ($generated): ?>
    <div class="mb-6 rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/30 px-4 py-3">
      <div class="flex items-center gap-2 text-sm text-green-700 dark:text-green-300">
        <i class="fa-solid fa-check-circle"></i>
        <span>Content items generated successfully</span>
      </div>
    </div>
  <?php elseif ($success): ?>
    <div class="mb-6 rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/30 px-4 py-3">
      <div class="flex items-center gap-2 text-sm text-green-700 dark:text-green-300">
        <i class="fa-solid fa-check-circle"></i>
        <span>Content item saved successfully</span>
      </div>
    </div>
  <?php endif; ?>

  <!-- Stats Cards -->
  <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
    <div class="rounded-xl bg-white dark:bg-slate-800 p-4 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
      <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Total Items</div>
      <div class="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-50"><?php echo $totalItems; ?></div>
    </div>
    <div class="rounded-xl bg-white dark:bg-slate-800 p-4 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
      <div class="text-xs font-medium uppercase tracking-wide text-amber-600 dark:text-amber-400">Pending Design</div>
      <div class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400"><?php echo $pendingDesign; ?></div>
    </div>
    <div class="rounded-xl bg-white dark:bg-slate-800 p-4 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
      <div class="text-xs font-medium uppercase tracking-wide text-blue-600 dark:text-blue-400">In Progress</div>
      <div class="mt-2 text-2xl font-bold text-blue-600 dark:text-blue-400"><?php echo $inProgress; ?></div>
    </div>
    <div class="rounded-xl bg-white dark:bg-slate-800 p-4 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
      <div class="text-xs font-medium uppercase tracking-wide text-emerald-600 dark:text-emerald-400">Ready / Done</div>
      <div class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400"><?php echo $readyDone; ?></div>
    </div>
  </div>

  <!-- Filter Bar -->
  <div class="mb-6 rounded-xl bg-white dark:bg-slate-800 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700"
       x-data="{ open: <?php echo $hasFilters ? 'true' : 'false'; ?> }">
    <button @click="open = !open" class="flex w-full items-center justify-between px-4 py-3 text-sm font-medium text-slate-700 dark:text-slate-300">
      <span><i class="fa-solid fa-filter mr-2"></i> Filters <?php if ($hasFilters): ?><span class="ml-1 inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900/30 px-2 py-0.5 text-xs font-semibold text-blue-600 dark:text-blue-400">Active</span><?php endif; ?></span>
      <i class="fa-solid fa-chevron-down text-xs transition-transform" :class="open && 'rotate-180'"></i>
    </button>
    <div x-show="open" x-collapse class="border-t border-slate-200 dark:border-slate-700 px-4 py-4">
      <form method="GET" action="content.php">
        <?php if ($activeView !== 'list'): ?><input type="hidden" name="view" value="<?php echo e($activeView); ?>"><?php endif; ?>
        <?php if ($filterRequestId): ?><input type="hidden" name="request_id" value="<?php echo $filterRequestId; ?>"><?php endif; ?>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
          <!-- Asset Status -->
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Asset Status</label>
            <select name="asset_status" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100">
              <option value="">All</option>
              <option value="pending" <?php echo $filterAssetStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
              <option value="in_progress" <?php echo $filterAssetStatus === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
              <option value="ready" <?php echo $filterAssetStatus === 'ready' ? 'selected' : ''; ?>>Ready</option>
              <option value="done" <?php echo $filterAssetStatus === 'done' ? 'selected' : ''; ?>>Done</option>
            </select>
          </div>
          <!-- Content Type -->
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Content Type</label>
            <select name="content_type" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100">
              <option value="">All</option>
              <option value="poster" <?php echo $filterContentType === 'poster' ? 'selected' : ''; ?>>Poster</option>
              <option value="video" <?php echo $filterContentType === 'video' ? 'selected' : ''; ?>>Video</option>
              <option value="story" <?php echo $filterContentType === 'story' ? 'selected' : ''; ?>>Story</option>
              <option value="reel" <?php echo $filterContentType === 'reel' ? 'selected' : ''; ?>>Reel</option>
              <option value="article" <?php echo $filterContentType === 'article' ? 'selected' : ''; ?>>Article</option>
              <option value="slide" <?php echo $filterContentType === 'slide' ? 'selected' : ''; ?>>Slide</option>
              <option value="other" <?php echo $filterContentType === 'other' ? 'selected' : ''; ?>>Other</option>
            </select>
          </div>
          <!-- Channel -->
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Channel</label>
            <select name="channel" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100">
              <option value="">All</option>
              <option value="facebook" <?php echo $filterChannel === 'facebook' ? 'selected' : ''; ?>>Facebook</option>
              <option value="instagram" <?php echo $filterChannel === 'instagram' ? 'selected' : ''; ?>>Instagram</option>
              <option value="telegram" <?php echo $filterChannel === 'telegram' ? 'selected' : ''; ?>>Telegram</option>
              <option value="tiktok" <?php echo $filterChannel === 'tiktok' ? 'selected' : ''; ?>>TikTok</option>
              <option value="youtube" <?php echo $filterChannel === 'youtube' ? 'selected' : ''; ?>>YouTube</option>
              <option value="bulletin" <?php echo $filterChannel === 'bulletin' ? 'selected' : ''; ?>>Bulletin</option>
              <option value="av_projection" <?php echo $filterChannel === 'av_projection' ? 'selected' : ''; ?>>AV Projection</option>
              <option value="cm" <?php echo $filterChannel === 'cm' ? 'selected' : ''; ?>>CM</option>
            </select>
          </div>
          <!-- Assigned PIC -->
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Assigned PIC</label>
            <select name="pic" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100">
              <option value="">All</option>
              <?php foreach ($picUsers as $pu): ?>
                <option value="<?php echo $pu['id']; ?>" <?php echo $filterPic === (string) $pu['id'] ? 'selected' : ''; ?>><?php echo e($pu['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <!-- Date From -->
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Promo From</label>
            <input type="date" name="date_from" value="<?php echo e($filterDateFrom); ?>" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100">
          </div>
          <!-- Date To -->
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Promo To</label>
            <input type="date" name="date_to" value="<?php echo e($filterDateTo); ?>" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100">
          </div>
        </div>
        <!-- Search + Actions -->
        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
          <div class="flex-1">
            <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Search</label>
            <input type="text" name="search" value="<?php echo e($filterSearch); ?>" placeholder="Title, ref no, event name..."
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100">
          </div>
          <div class="flex gap-2">
            <button type="submit" class="rounded-lg bg-slate-900 dark:bg-slate-100 px-4 py-2 text-sm font-semibold text-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-slate-200">Apply</button>
            <a href="content.php<?php echo $activeView !== 'list' ? '?view=' . urlencode($activeView) : ''; ?>" class="rounded-lg border border-slate-300 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">Clear</a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <?php if ($activeView === 'list'): ?>
  <!-- ===== LIST VIEW ===== -->
  <div class="rounded-xl bg-white dark:bg-slate-800 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">

    <?php if (empty($items)): ?>
      <div class="px-6 py-16 text-center">
        <i class="fa-solid fa-newspaper text-4xl text-slate-300 dark:text-slate-600 mb-4"></i>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-1">No content items found</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400">
          <?php if ($hasFilters): ?>
            Try adjusting your filters.
          <?php else: ?>
            Generate content items from approved media requests.
          <?php endif; ?>
        </p>
      </div>
    <?php else: ?>

      <!-- Desktop Table -->
      <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50">
              <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-300">Title</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-300">Ref No</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-300">Status</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-300">Channels</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-300">Promo Period</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-300">PIC</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-300">Created</th>
              <th class="px-4 py-3 text-center font-semibold text-slate-700 dark:text-slate-300">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
            <?php foreach ($items as $item): ?>
              <?php
                $channels = [];
                if ($item['channel_info']) {
                    foreach (explode(',', $item['channel_info']) as $ch) {
                        [$chName, $chStatus] = explode(':', $ch);
                        $channels[] = ['name' => $chName, 'status' => $chStatus];
                    }
                }
                $typeColors = [
                    'poster' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400',
                    'video' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
                    'story' => 'bg-pink-100 dark:bg-pink-900/30 text-pink-700 dark:text-pink-400',
                    'reel' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400',
                    'article' => 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-400',
                    'slide' => 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400',
                ];
                $typeClass = $typeColors[$item['content_type']] ?? 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400';
              ?>
              <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                <td class="px-4 py-3">
                  <div class="font-medium text-slate-900 dark:text-slate-100"><?php echo e($item['title']); ?></div>
                  <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold <?php echo $typeClass; ?> mt-0.5">
                    <?php echo ucfirst($item['content_type']); ?>
                  </span>
                  <?php if ($item['do_not_display']): ?>
                    <span class="inline-flex items-center rounded-full bg-red-100 dark:bg-red-900/30 px-1.5 py-0.5 text-[10px] font-semibold text-red-600 dark:text-red-400 ml-1">Hidden</span>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-3">
                  <?php if ($item['media_request_id']): ?>
                    <a href="view.php?id=<?php echo $item['media_request_id']; ?>" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">
                      <?php echo e($item['reference_no']); ?>
                    </a>
                  <?php else: ?>
                    <span class="text-slate-400">—</span>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-3">
                  <?php if ($canManage): ?>
                    <div x-data="{ showStatusDrop: false }">
                      <button @click="showStatusDrop = !showStatusDrop" @click.outside="showStatusDrop = false"
                              class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold <?php echo asset_status_classes($item['asset_status']); ?> cursor-pointer hover:ring-2 hover:ring-slate-300 dark:hover:ring-slate-600 transition">
                        <?php echo asset_status_label($item['asset_status']); ?>
                        <i class="fa-solid fa-caret-down text-[10px]"></i>
                      </button>
                      <div x-show="showStatusDrop" x-transition class="absolute z-20 mt-1 w-36 rounded-lg bg-white dark:bg-slate-800 shadow-lg ring-1 ring-slate-200 dark:ring-slate-700 py-1">
                        <?php foreach (['pending', 'in_progress', 'ready', 'done'] as $st): ?>
                          <form method="POST" action="actions/save_content.php" class="block">
                            <input type="hidden" name="content_item_id" value="<?php echo $item['id']; ?>">
                            <input type="hidden" name="asset_status" value="<?php echo $st; ?>">
                            <input type="hidden" name="quick_status" value="1">
                            <button type="submit" class="w-full text-left px-3 py-1.5 text-xs hover:bg-slate-50 dark:hover:bg-slate-700 <?php echo $item['asset_status'] === $st ? 'font-bold text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-400'; ?>">
                              <?php echo asset_status_label($st); ?>
                            </button>
                          </form>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  <?php else: ?>
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?php echo asset_status_classes($item['asset_status']); ?>">
                      <?php echo asset_status_label($item['asset_status']); ?>
                    </span>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-3">
                  <div class="flex flex-wrap gap-1">
                    <?php foreach ($channels as $ch): ?>
                      <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-medium <?php echo channel_status_classes($ch['status']); ?>">
                        <?php echo channel_label($ch['name']); ?>
                      </span>
                    <?php endforeach; ?>
                    <?php if (empty($channels)): ?>
                      <span class="text-slate-400 text-xs">—</span>
                    <?php endif; ?>
                  </div>
                </td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300 whitespace-nowrap">
                  <?php if ($item['promo_start_date']): ?>
                    <?php echo date('M j', strtotime($item['promo_start_date'])); ?>
                    <?php if ($item['promo_end_date']): ?>
                      - <?php echo date('M j', strtotime($item['promo_end_date'])); ?>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="text-slate-400">—</span>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-3 text-xs text-slate-700 dark:text-slate-300">
                  <?php if ($item['asset_pic_name']): ?>
                    <div><span class="text-slate-400">Design:</span> <?php echo e($item['asset_pic_name']); ?></div>
                  <?php endif; ?>
                  <?php if ($item['socmed_pic_name']): ?>
                    <div><span class="text-slate-400">SocMed:</span> <?php echo e($item['socmed_pic_name']); ?></div>
                  <?php endif; ?>
                  <?php if (!$item['asset_pic_name'] && !$item['socmed_pic_name']): ?>
                    <span class="text-slate-400">—</span>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-3 text-slate-500 dark:text-slate-400 whitespace-nowrap">
                  <?php echo date('M j, Y', strtotime($item['created_at'])); ?>
                </td>
                <td class="px-4 py-3 text-center">
                  <?php if ($canManage): ?>
                    <button @click='openEditModal(<?php echo json_encode([
                        "id" => $item["id"],
                        "title" => $item["title"],
                        "content_type" => $item["content_type"],
                        "language" => $item["language"],
                        "promo_start_date" => $item["promo_start_date"] ?? "",
                        "promo_end_date" => $item["promo_end_date"] ?? "",
                        "default_publish_at" => $item["default_publish_at"] ? substr($item["default_publish_at"], 0, 16) : "",
                        "caption_brief" => $item["caption_brief"] ?? "",
                        "final_caption" => $item["final_caption"] ?? "",
                        "caption_status" => $item["caption_status"],
                        "asset_url" => $item["asset_url"] ?? "",
                        "asset_status" => $item["asset_status"],
                        "notes" => $item["notes"] ?? "",
                        "do_not_display" => (bool)$item["do_not_display"],
                        "asset_pic_user_id" => $item["asset_pic_user_id"] ?? "",
                        "socmed_pic_user_id" => $item["socmed_pic_user_id"] ?? "",
                        "media_request_id" => $item["media_request_id"] ?? "",
                        "channels" => $channels,
                    ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                            class="inline-flex items-center gap-1 rounded-lg border border-slate-300 dark:border-slate-600 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">
                      <i class="fa-solid fa-pen-to-square"></i> Edit
                    </button>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Mobile Cards -->
      <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-700/50">
        <?php foreach ($items as $item): ?>
          <?php
            $channels = [];
            if ($item['channel_info']) {
                foreach (explode(',', $item['channel_info']) as $ch) {
                    [$chName, $chStatus] = explode(':', $ch);
                    $channels[] = ['name' => $chName, 'status' => $chStatus];
                }
            }
            $typeColors = [
                'poster' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400',
                'video' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
                'story' => 'bg-pink-100 dark:bg-pink-900/30 text-pink-700 dark:text-pink-400',
                'reel' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400',
            ];
            $typeClass = $typeColors[$item['content_type']] ?? 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400';
          ?>
          <div class="px-4 py-4">
            <div class="flex items-start justify-between mb-2">
              <div>
                <div class="font-medium text-slate-900 dark:text-slate-100"><?php echo e($item['title']); ?></div>
                <div class="flex items-center gap-2 mt-1">
                  <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold <?php echo $typeClass; ?>">
                    <?php echo ucfirst($item['content_type']); ?>
                  </span>
                  <?php if ($item['reference_no']): ?>
                    <a href="view.php?id=<?php echo $item['media_request_id']; ?>" class="text-xs text-blue-600 dark:text-blue-400 hover:underline"><?php echo e($item['reference_no']); ?></a>
                  <?php endif; ?>
                </div>
              </div>
              <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?php echo asset_status_classes($item['asset_status']); ?>">
                <?php echo asset_status_label($item['asset_status']); ?>
              </span>
            </div>
            <div class="flex flex-wrap gap-1 mb-2">
              <?php foreach ($channels as $ch): ?>
                <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-medium <?php echo channel_status_classes($ch['status']); ?>">
                  <?php echo channel_label($ch['name']); ?>
                </span>
              <?php endforeach; ?>
            </div>
            <div class="text-xs text-slate-500 dark:text-slate-400">
              <?php if ($item['promo_start_date']): ?>
                <?php echo date('M j', strtotime($item['promo_start_date'])); ?><?php if ($item['promo_end_date']): ?> - <?php echo date('M j', strtotime($item['promo_end_date'])); ?><?php endif; ?>
                &middot;
              <?php endif; ?>
              Created <?php echo date('M j, Y', strtotime($item['created_at'])); ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <div class="flex items-center justify-between border-t border-slate-200 dark:border-slate-700 px-4 py-3">
          <div class="text-xs text-slate-500 dark:text-slate-400">
            Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $perPage, $totalFiltered); ?> of <?php echo $totalFiltered; ?>
          </div>
          <div class="flex gap-1">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
              <a href="?<?php echo http_build_query(array_merge($queryParams, ['page' => $p])); ?>"
                 class="px-3 py-1 rounded text-sm <?php echo $p === $page ? 'bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'; ?>">
                <?php echo $p; ?>
              </a>
            <?php endfor; ?>
          </div>
        </div>
      <?php endif; ?>

    <?php endif; ?>
  </div>

  <?php elseif ($activeView === 'spreadsheet'): ?>
  <!-- ===== SPREADSHEET VIEW ===== -->
  <div class="rounded-xl bg-white dark:bg-slate-800 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">

    <?php if (empty($items)): ?>
      <div class="px-6 py-16 text-center">
        <i class="fa-solid fa-table-cells text-4xl text-slate-300 dark:text-slate-600 mb-4"></i>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-1">No content items found</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400">
          <?php if ($hasFilters): ?>
            Try adjusting your filters.
          <?php else: ?>
            Generate content items from approved media requests or create new ones.
          <?php endif; ?>
        </p>
      </div>
    <?php else: ?>

      <!-- Desktop Spreadsheet Table -->
      <div class="hidden md:block spreadsheet-wrap">
        <table class="spreadsheet-table w-full">
          <thead>
            <!-- Group header row -->
            <tr>
              <th rowspan="2" class="col-group-sticky px-3 py-2 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 min-w-[180px] sticky left-0 z-20 border-b border-r border-slate-200 dark:border-slate-700">
                Topic
              </th>
              <th colspan="8" class="col-group-social px-2 py-1.5 text-center text-[10px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 border-b border-blue-200 dark:border-blue-800">
                Social Media
              </th>
              <th colspan="5" class="col-group-church px-2 py-1.5 text-center text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 border-b border-amber-200 dark:border-amber-800">
                Church
              </th>
              <th colspan="9" class="col-group-details px-2 py-1.5 text-center text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 border-b border-emerald-200 dark:border-emerald-800">
                Details
              </th>
            </tr>
            <!-- Individual column headers -->
            <tr class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              <!-- Social Media -->
              <th class="col-group-social px-1 py-2 text-center w-[40px] border-b border-slate-200 dark:border-slate-700">Day</th>
              <th class="col-group-social px-1 py-2 text-center w-[70px] border-b border-slate-200 dark:border-slate-700">Date</th>
              <th class="col-group-social px-1 py-2 text-center w-[50px] border-b border-slate-200 dark:border-slate-700">Time</th>
              <th class="col-group-social px-1 py-2 text-center w-[46px] border-b border-slate-200 dark:border-slate-700">FB</th>
              <th class="col-group-social px-1 py-2 text-center w-[46px] border-b border-slate-200 dark:border-slate-700">IG</th>
              <th class="col-group-social px-1 py-2 text-center w-[46px] border-b border-slate-200 dark:border-slate-700">TG</th>
              <th class="col-group-social px-1 py-2 text-center w-[46px] border-b border-slate-200 dark:border-slate-700">TT</th>
              <th class="col-group-social px-1 py-2 text-center w-[46px] border-b border-slate-200 dark:border-slate-700">YT</th>
              <!-- Church -->
              <th class="col-group-church px-1 py-2 text-center w-[70px] border-b border-slate-200 dark:border-slate-700">Start</th>
              <th class="col-group-church px-1 py-2 text-center w-[70px] border-b border-slate-200 dark:border-slate-700">End</th>
              <th class="col-group-church px-1 py-2 text-center w-[46px] border-b border-slate-200 dark:border-slate-700">Bul</th>
              <th class="col-group-church px-1 py-2 text-center w-[46px] border-b border-slate-200 dark:border-slate-700">AV</th>
              <th class="col-group-church px-1 py-2 text-center w-[46px] border-b border-slate-200 dark:border-slate-700">CM</th>
              <!-- Details -->
              <th class="col-group-details px-1 py-2 text-center w-[40px] border-b border-slate-200 dark:border-slate-700">Lang</th>
              <th class="col-group-details px-1 py-2 text-center w-[55px] border-b border-slate-200 dark:border-slate-700">Format</th>
              <th class="col-group-details px-1 py-2 text-center w-[70px] border-b border-slate-200 dark:border-slate-700">Caption</th>
              <th class="col-group-details px-1 py-2 text-center w-[46px] border-b border-slate-200 dark:border-slate-700">Asset</th>
              <th class="col-group-details px-1 py-2 text-center w-[80px] border-b border-slate-200 dark:border-slate-700">Asset St</th>
              <th class="col-group-details px-1 py-2 text-center w-[90px] border-b border-slate-200 dark:border-slate-700">Design PIC</th>
              <th class="col-group-details px-1 py-2 text-center w-[90px] border-b border-slate-200 dark:border-slate-700">SM PIC</th>
              <th class="col-group-details px-1 py-2 text-center w-[40px] border-b border-slate-200 dark:border-slate-700"><i class="fa-solid fa-pen-to-square"></i></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item):
              // Build channel map for this row
              $channelMap = [];
              if ($item['channel_info']) {
                  foreach (explode(',', $item['channel_info']) as $ch) {
                      [$chName, $chStatus] = explode(':', $ch);
                      $channelMap[$chName] = $chStatus;
                  }
              }
              $typeColors = [
                  'poster' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400',
                  'video' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
                  'story' => 'bg-pink-100 dark:bg-pink-900/30 text-pink-700 dark:text-pink-400',
                  'reel' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400',
                  'article' => 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-400',
                  'slide' => 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400',
              ];
              $typeClass = $typeColors[$item['content_type']] ?? 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400';
              $channels = [];
              if ($item['channel_info']) {
                  foreach (explode(',', $item['channel_info']) as $ch) {
                      [$chName, $chStatus] = explode(':', $ch);
                      $channels[] = ['name' => $chName, 'status' => $chStatus];
                  }
              }

              // Date/time derived fields
              $dayAbbr = $item['promo_start_date'] ? date('D', strtotime($item['promo_start_date'])) : '';
              $dateStr = $item['promo_start_date'] ? date('j-M', strtotime($item['promo_start_date'])) : '';
              $timeStr = $item['default_publish_at'] ? date('g:iA', strtotime($item['default_publish_at'])) : '';
            ?>
              <tr class="group hover:bg-slate-50/80 dark:hover:bg-slate-700/20 transition-colors border-b border-slate-100 dark:border-slate-700/50"
                  x-data='{
                    channels: <?php echo json_encode((object) $channelMap, JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
                    assetStatus: <?php echo json_encode($item["asset_status"]); ?>,
                    captionStatus: <?php echo json_encode($item["caption_status"]); ?>,
                    assetPic: <?php echo json_encode($item["asset_pic_user_id"] ? (int) $item["asset_pic_user_id"] : 0); ?>,
                    assetPicName: <?php echo json_encode($item["asset_pic_name"] ?? ""); ?>,
                    socmedPic: <?php echo json_encode($item["socmed_pic_user_id"] ? (int) $item["socmed_pic_user_id"] : 0); ?>,
                    socmedPicName: <?php echo json_encode($item["socmed_pic_name"] ?? ""); ?>,
                    itemId: <?php echo $item["id"]; ?>
                  }'>

                <!-- Topic (sticky) -->
                <td class="px-3 py-2 sticky left-0 z-10 bg-white dark:bg-slate-800 group-hover:bg-slate-50/80 dark:group-hover:bg-slate-700/20 border-r border-slate-200 dark:border-slate-700 min-w-[180px] max-w-[200px]">
                  <div class="font-medium text-slate-900 dark:text-slate-100 truncate text-xs" title="<?php echo e($item['title']); ?>">
                    <?php echo e($item['title']); ?>
                  </div>
                  <?php if ($item['reference_no']): ?>
                    <a href="view.php?id=<?php echo $item['media_request_id']; ?>" class="text-[10px] text-blue-600 dark:text-blue-400 hover:underline">
                      <?php echo e($item['reference_no']); ?>
                    </a>
                  <?php endif; ?>
                  <?php if ($item['do_not_display']): ?>
                    <span class="inline-flex items-center rounded-full bg-red-100 dark:bg-red-900/30 px-1 py-0.5 text-[8px] font-semibold text-red-600 dark:text-red-400 ml-1">Hidden</span>
                  <?php endif; ?>
                </td>

                <!-- Day -->
                <td class="px-1 py-2 text-center text-[11px] text-slate-500 dark:text-slate-400"><?php echo $dayAbbr; ?></td>
                <!-- Date -->
                <td class="px-1 py-2 text-center text-[11px] text-slate-700 dark:text-slate-300 whitespace-nowrap"><?php echo $dateStr ?: '<span class="text-slate-300 dark:text-slate-600">--</span>'; ?></td>
                <!-- Time -->
                <td class="px-1 py-2 text-center text-[11px] text-slate-700 dark:text-slate-300 whitespace-nowrap"><?php echo $timeStr ?: '<span class="text-slate-300 dark:text-slate-600">--</span>'; ?></td>

                <!-- Social channels: FB, IG, TG, TT, YT -->
                <?php foreach (['facebook', 'instagram', 'telegram', 'tiktok', 'youtube'] as $ch): ?>
                  <td class="px-0.5 py-1 text-center relative">
                    <?php if ($canManage): ?>
                      <!-- Active channel -->
                      <template x-if="channels.hasOwnProperty('<?php echo $ch; ?>')">
                        <div class="relative">
                          <button @click="$root.activeChDrop === itemId + '-<?php echo $ch; ?>' ? $root.activeChDrop = null : $root.activeChDrop = itemId + '-<?php echo $ch; ?>'"
                                  :class="$root.chColor(channels['<?php echo $ch; ?>'])"
                                  class="inline-flex items-center justify-center w-7 h-6 rounded text-[10px] font-bold cursor-pointer hover:ring-2 hover:ring-slate-300 dark:hover:ring-slate-500 transition"
                                  x-text="$root.chAbbr(channels['<?php echo $ch; ?>'])"></button>
                          <div x-show="$root.activeChDrop === itemId + '-<?php echo $ch; ?>'"
                               @click.outside="$root.activeChDrop = null" x-transition
                               class="absolute z-30 mt-0.5 left-1/2 -translate-x-1/2 w-28 rounded-lg bg-white dark:bg-slate-800 shadow-lg ring-1 ring-slate-200 dark:ring-slate-700 py-1 text-xs">
                            <?php foreach (['pending', 'scheduled', 'posted', 'done'] as $st): ?>
                              <button @click="channels['<?php echo $ch; ?>'] = '<?php echo $st; ?>'; $root.updateInline(itemId, 'channel_status', {channel:'<?php echo $ch; ?>', status:'<?php echo $st; ?>'}); $root.activeChDrop = null"
                                      class="w-full text-left px-3 py-1.5 hover:bg-slate-50 dark:hover:bg-slate-700"
                                      :class="channels['<?php echo $ch; ?>'] === '<?php echo $st; ?>' && 'font-bold text-slate-900 dark:text-slate-100'">
                                <?php echo channel_status_label($st); ?>
                              </button>
                            <?php endforeach; ?>
                            <hr class="my-1 border-slate-200 dark:border-slate-700">
                            <button @click="delete channels['<?php echo $ch; ?>']; channels = {...channels}; $root.updateInline(itemId, 'channel_toggle', {channel:'<?php echo $ch; ?>', action:'remove'}); $root.activeChDrop = null"
                                    class="w-full text-left px-3 py-1.5 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                              <i class="fa-solid fa-xmark mr-1"></i> Remove
                            </button>
                          </div>
                        </div>
                      </template>
                      <!-- Inactive channel -->
                      <template x-if="!channels.hasOwnProperty('<?php echo $ch; ?>')">
                        <button @click="channels['<?php echo $ch; ?>'] = 'pending'; channels = {...channels}; $root.updateInline(itemId, 'channel_toggle', {channel:'<?php echo $ch; ?>', action:'add'})"
                                class="text-slate-300 dark:text-slate-600 hover:text-blue-500 dark:hover:text-blue-400 text-[11px] cursor-pointer transition w-7 h-6 inline-flex items-center justify-center"
                                title="Add <?php echo channel_label($ch); ?>">--</button>
                      </template>
                    <?php else: ?>
                      <span class="text-[10px]" :class="channels.hasOwnProperty('<?php echo $ch; ?>') ? $root.chColor(channels['<?php echo $ch; ?>']) + ' font-bold' : 'text-slate-300 dark:text-slate-600'"
                            x-text="channels.hasOwnProperty('<?php echo $ch; ?>') ? $root.chAbbr(channels['<?php echo $ch; ?>']) : '--'"></span>
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>

                <!-- Start Date -->
                <td class="px-1 py-2 text-center text-[11px] text-slate-700 dark:text-slate-300 whitespace-nowrap">
                  <?php echo $item['promo_start_date'] ? date('j-M', strtotime($item['promo_start_date'])) : '<span class="text-slate-300 dark:text-slate-600">--</span>'; ?>
                </td>
                <!-- End Date -->
                <td class="px-1 py-2 text-center text-[11px] text-slate-700 dark:text-slate-300 whitespace-nowrap">
                  <?php echo $item['promo_end_date'] ? date('j-M', strtotime($item['promo_end_date'])) : '<span class="text-slate-300 dark:text-slate-600">--</span>'; ?>
                </td>

                <!-- Church channels: Bulletin, AV, CM -->
                <?php foreach (['bulletin', 'av_projection', 'cm'] as $ch):
                  $chShort = ['bulletin' => 'Bul', 'av_projection' => 'AV', 'cm' => 'CM'][$ch];
                ?>
                  <td class="px-0.5 py-1 text-center relative">
                    <?php if ($canManage): ?>
                      <template x-if="channels.hasOwnProperty('<?php echo $ch; ?>')">
                        <div class="relative">
                          <button @click="$root.activeChDrop === itemId + '-<?php echo $ch; ?>' ? $root.activeChDrop = null : $root.activeChDrop = itemId + '-<?php echo $ch; ?>'"
                                  :class="$root.chColor(channels['<?php echo $ch; ?>'])"
                                  class="inline-flex items-center justify-center w-7 h-6 rounded text-[10px] font-bold cursor-pointer hover:ring-2 hover:ring-slate-300 dark:hover:ring-slate-500 transition"
                                  x-text="$root.chAbbr(channels['<?php echo $ch; ?>'])"></button>
                          <div x-show="$root.activeChDrop === itemId + '-<?php echo $ch; ?>'"
                               @click.outside="$root.activeChDrop = null" x-transition
                               class="absolute z-30 mt-0.5 left-1/2 -translate-x-1/2 w-28 rounded-lg bg-white dark:bg-slate-800 shadow-lg ring-1 ring-slate-200 dark:ring-slate-700 py-1 text-xs">
                            <?php foreach (['pending', 'done'] as $st): ?>
                              <button @click="channels['<?php echo $ch; ?>'] = '<?php echo $st; ?>'; $root.updateInline(itemId, 'channel_status', {channel:'<?php echo $ch; ?>', status:'<?php echo $st; ?>'}); $root.activeChDrop = null"
                                      class="w-full text-left px-3 py-1.5 hover:bg-slate-50 dark:hover:bg-slate-700"
                                      :class="channels['<?php echo $ch; ?>'] === '<?php echo $st; ?>' && 'font-bold text-slate-900 dark:text-slate-100'">
                                <?php echo channel_status_label($st); ?>
                              </button>
                            <?php endforeach; ?>
                            <hr class="my-1 border-slate-200 dark:border-slate-700">
                            <button @click="delete channels['<?php echo $ch; ?>']; channels = {...channels}; $root.updateInline(itemId, 'channel_toggle', {channel:'<?php echo $ch; ?>', action:'remove'}); $root.activeChDrop = null"
                                    class="w-full text-left px-3 py-1.5 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                              <i class="fa-solid fa-xmark mr-1"></i> Remove
                            </button>
                          </div>
                        </div>
                      </template>
                      <template x-if="!channels.hasOwnProperty('<?php echo $ch; ?>')">
                        <button @click="channels['<?php echo $ch; ?>'] = 'pending'; channels = {...channels}; $root.updateInline(itemId, 'channel_toggle', {channel:'<?php echo $ch; ?>', action:'add'})"
                                class="text-slate-300 dark:text-slate-600 hover:text-amber-500 dark:hover:text-amber-400 text-[11px] cursor-pointer transition w-7 h-6 inline-flex items-center justify-center"
                                title="Add <?php echo channel_label($ch); ?>">--</button>
                      </template>
                    <?php else: ?>
                      <span class="text-[10px]" :class="channels.hasOwnProperty('<?php echo $ch; ?>') ? $root.chColor(channels['<?php echo $ch; ?>']) + ' font-bold' : 'text-slate-300 dark:text-slate-600'"
                            x-text="channels.hasOwnProperty('<?php echo $ch; ?>') ? $root.chAbbr(channels['<?php echo $ch; ?>']) : '--'"></span>
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>

                <!-- Language -->
                <td class="px-1 py-2 text-center text-[11px] text-slate-700 dark:text-slate-300">
                  <?php echo $item['language'] ? e($item['language']) : '<span class="text-slate-300 dark:text-slate-600">--</span>'; ?>
                </td>

                <!-- Format (content type badge) -->
                <td class="px-1 py-2 text-center">
                  <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[9px] font-semibold <?php echo $typeClass; ?>">
                    <?php echo ucfirst($item['content_type']); ?>
                  </span>
                </td>

                <!-- Caption Status -->
                <td class="px-1 py-2 text-center relative">
                  <?php if ($canManage): ?>
                    <button @click="$root.activeStDrop === itemId + '-caption' ? $root.activeStDrop = null : $root.activeStDrop = itemId + '-caption'"
                            :class="{
                              'bg-slate-100 dark:bg-slate-700 text-slate-500': captionStatus === 'na',
                              'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400': captionStatus === 'pending',
                              'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400': captionStatus === 'done'
                            }"
                            class="inline-flex items-center gap-0.5 rounded-full px-2 py-0.5 text-[10px] font-semibold cursor-pointer hover:ring-2 hover:ring-slate-300 dark:hover:ring-slate-500 transition">
                      <span x-text="{'na':'N/A','pending':'Pending','done':'Done'}[captionStatus] || captionStatus"></span>
                      <i class="fa-solid fa-caret-down text-[8px]"></i>
                    </button>
                    <div x-show="$root.activeStDrop === itemId + '-caption'"
                         @click.outside="$root.activeStDrop = null" x-transition
                         class="absolute z-30 mt-0.5 left-1/2 -translate-x-1/2 w-28 rounded-lg bg-white dark:bg-slate-800 shadow-lg ring-1 ring-slate-200 dark:ring-slate-700 py-1 text-xs">
                      <?php foreach (['na', 'pending', 'done'] as $st): ?>
                        <button @click="captionStatus = '<?php echo $st; ?>'; $root.updateInline(itemId, 'caption_status', {value:'<?php echo $st; ?>'}); $root.activeStDrop = null"
                                class="w-full text-left px-3 py-1.5 hover:bg-slate-50 dark:hover:bg-slate-700"
                                :class="captionStatus === '<?php echo $st; ?>' && 'font-bold text-slate-900 dark:text-slate-100'">
                          <?php echo ['na' => 'N/A', 'pending' => 'Pending', 'done' => 'Done'][$st]; ?>
                        </button>
                      <?php endforeach; ?>
                    </div>
                  <?php else: ?>
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold <?php echo match ($item['caption_status']) { 'pending' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400', 'done' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400', default => 'bg-slate-100 dark:bg-slate-700 text-slate-500' }; ?>">
                      <?php echo ['na' => 'N/A', 'pending' => 'Pending', 'done' => 'Done'][$item['caption_status']] ?? $item['caption_status']; ?>
                    </span>
                  <?php endif; ?>
                </td>

                <!-- Asset Link -->
                <td class="px-1 py-2 text-center">
                  <?php if ($item['asset_url']): ?>
                    <a href="<?php echo e($item['asset_url']); ?>" target="_blank" rel="noopener"
                       class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300" title="Open asset">
                      <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                    </a>
                  <?php else: ?>
                    <span class="text-slate-300 dark:text-slate-600">--</span>
                  <?php endif; ?>
                </td>

                <!-- Asset Status -->
                <td class="px-1 py-2 text-center relative">
                  <?php if ($canManage): ?>
                    <button @click="$root.activeStDrop === itemId + '-asset' ? $root.activeStDrop = null : $root.activeStDrop = itemId + '-asset'"
                            :class="{
                              'bg-slate-100 dark:bg-slate-700 text-slate-500': assetStatus === 'na',
                              'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400': assetStatus === 'pending',
                              'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400': assetStatus === 'in_progress',
                              'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-400': assetStatus === 'ready',
                              'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400': assetStatus === 'done'
                            }"
                            class="inline-flex items-center gap-0.5 rounded-full px-2 py-0.5 text-[10px] font-semibold cursor-pointer hover:ring-2 hover:ring-slate-300 dark:hover:ring-slate-500 transition">
                      <span x-text="{'na':'N/A','pending':'Pending','in_progress':'In Prog','ready':'Ready','done':'Done'}[assetStatus] || assetStatus"></span>
                      <i class="fa-solid fa-caret-down text-[8px]"></i>
                    </button>
                    <div x-show="$root.activeStDrop === itemId + '-asset'"
                         @click.outside="$root.activeStDrop = null" x-transition
                         class="absolute z-30 mt-0.5 left-1/2 -translate-x-1/2 w-28 rounded-lg bg-white dark:bg-slate-800 shadow-lg ring-1 ring-slate-200 dark:ring-slate-700 py-1 text-xs">
                      <?php foreach (['pending', 'in_progress', 'ready', 'done'] as $st): ?>
                        <button @click="assetStatus = '<?php echo $st; ?>'; $root.updateInline(itemId, 'asset_status', {value:'<?php echo $st; ?>'}); $root.activeStDrop = null"
                                class="w-full text-left px-3 py-1.5 hover:bg-slate-50 dark:hover:bg-slate-700"
                                :class="assetStatus === '<?php echo $st; ?>' && 'font-bold text-slate-900 dark:text-slate-100'">
                          <?php echo asset_status_label($st); ?>
                        </button>
                      <?php endforeach; ?>
                    </div>
                  <?php else: ?>
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold <?php echo asset_status_classes($item['asset_status']); ?>">
                      <?php echo asset_status_label($item['asset_status']); ?>
                    </span>
                  <?php endif; ?>
                </td>

                <!-- Design PIC -->
                <td class="px-1 py-2 text-center relative">
                  <?php if ($canManage): ?>
                    <button @click="$root.activePicDrop === itemId + '-asset_pic' ? $root.activePicDrop = null : $root.activePicDrop = itemId + '-asset_pic'"
                            class="text-[11px] hover:text-blue-600 dark:hover:text-blue-400 cursor-pointer truncate max-w-[85px] block mx-auto transition"
                            :class="assetPicName ? 'text-slate-700 dark:text-slate-300' : 'text-slate-400 dark:text-slate-500 italic'"
                            x-text="assetPicName || 'Unassigned'"></button>
                    <div x-show="$root.activePicDrop === itemId + '-asset_pic'"
                         @click.outside="$root.activePicDrop = null" x-transition
                         class="absolute z-30 mt-0.5 right-0 w-40 rounded-lg bg-white dark:bg-slate-800 shadow-lg ring-1 ring-slate-200 dark:ring-slate-700 py-1 text-xs max-h-48 overflow-y-auto">
                      <button @click="assetPic = 0; assetPicName = ''; $root.updateInline(itemId, 'asset_pic_user_id', {value: null}); $root.activePicDrop = null"
                              class="w-full text-left px-3 py-1.5 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-400 italic">
                        Unassigned
                      </button>
                      <?php foreach ($picUsers as $pu): ?>
                        <button @click="assetPic = <?php echo $pu['id']; ?>; assetPicName = <?php echo json_encode($pu['name']); ?>; $root.updateInline(itemId, 'asset_pic_user_id', {value: <?php echo $pu['id']; ?>}); $root.activePicDrop = null"
                                class="w-full text-left px-3 py-1.5 hover:bg-slate-50 dark:hover:bg-slate-700"
                                :class="assetPic == <?php echo $pu['id']; ?> && 'font-bold text-blue-600 dark:text-blue-400'">
                          <?php echo e($pu['name']); ?>
                        </button>
                      <?php endforeach; ?>
                    </div>
                  <?php else: ?>
                    <span class="text-[11px] <?php echo $item['asset_pic_name'] ? 'text-slate-700 dark:text-slate-300' : 'text-slate-400 italic'; ?>">
                      <?php echo $item['asset_pic_name'] ? e($item['asset_pic_name']) : '--'; ?>
                    </span>
                  <?php endif; ?>
                </td>

                <!-- SocMed PIC -->
                <td class="px-1 py-2 text-center relative">
                  <?php if ($canManage): ?>
                    <button @click="$root.activePicDrop === itemId + '-socmed_pic' ? $root.activePicDrop = null : $root.activePicDrop = itemId + '-socmed_pic'"
                            class="text-[11px] hover:text-blue-600 dark:hover:text-blue-400 cursor-pointer truncate max-w-[85px] block mx-auto transition"
                            :class="socmedPicName ? 'text-slate-700 dark:text-slate-300' : 'text-slate-400 dark:text-slate-500 italic'"
                            x-text="socmedPicName || 'Unassigned'"></button>
                    <div x-show="$root.activePicDrop === itemId + '-socmed_pic'"
                         @click.outside="$root.activePicDrop = null" x-transition
                         class="absolute z-30 mt-0.5 right-0 w-40 rounded-lg bg-white dark:bg-slate-800 shadow-lg ring-1 ring-slate-200 dark:ring-slate-700 py-1 text-xs max-h-48 overflow-y-auto">
                      <button @click="socmedPic = 0; socmedPicName = ''; $root.updateInline(itemId, 'socmed_pic_user_id', {value: null}); $root.activePicDrop = null"
                              class="w-full text-left px-3 py-1.5 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-400 italic">
                        Unassigned
                      </button>
                      <?php foreach ($picUsers as $pu): ?>
                        <button @click="socmedPic = <?php echo $pu['id']; ?>; socmedPicName = <?php echo json_encode($pu['name']); ?>; $root.updateInline(itemId, 'socmed_pic_user_id', {value: <?php echo $pu['id']; ?>}); $root.activePicDrop = null"
                                class="w-full text-left px-3 py-1.5 hover:bg-slate-50 dark:hover:bg-slate-700"
                                :class="socmedPic == <?php echo $pu['id']; ?> && 'font-bold text-blue-600 dark:text-blue-400'">
                          <?php echo e($pu['name']); ?>
                        </button>
                      <?php endforeach; ?>
                    </div>
                  <?php else: ?>
                    <span class="text-[11px] <?php echo $item['socmed_pic_name'] ? 'text-slate-700 dark:text-slate-300' : 'text-slate-400 italic'; ?>">
                      <?php echo $item['socmed_pic_name'] ? e($item['socmed_pic_name']) : '--'; ?>
                    </span>
                  <?php endif; ?>
                </td>

                <!-- Edit button -->
                <td class="px-1 py-2 text-center">
                  <?php if ($canManage): ?>
                    <button @click='openEditModal(<?php echo json_encode([
                        "id" => $item["id"],
                        "title" => $item["title"],
                        "content_type" => $item["content_type"],
                        "language" => $item["language"],
                        "promo_start_date" => $item["promo_start_date"] ?? "",
                        "promo_end_date" => $item["promo_end_date"] ?? "",
                        "default_publish_at" => $item["default_publish_at"] ? substr($item["default_publish_at"], 0, 16) : "",
                        "caption_brief" => $item["caption_brief"] ?? "",
                        "final_caption" => $item["final_caption"] ?? "",
                        "caption_status" => $item["caption_status"],
                        "asset_url" => $item["asset_url"] ?? "",
                        "asset_status" => $item["asset_status"],
                        "notes" => $item["notes"] ?? "",
                        "do_not_display" => (bool)$item["do_not_display"],
                        "asset_pic_user_id" => $item["asset_pic_user_id"] ?? "",
                        "socmed_pic_user_id" => $item["socmed_pic_user_id"] ?? "",
                        "media_request_id" => $item["media_request_id"] ?? "",
                        "channels" => $channels,
                    ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                            class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition" title="Edit all fields">
                      <i class="fa-solid fa-pen-to-square text-xs"></i>
                    </button>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Mobile Cards (same as list view) -->
      <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-700/50">
        <?php foreach ($items as $item): ?>
          <?php
            $channels = [];
            if ($item['channel_info']) {
                foreach (explode(',', $item['channel_info']) as $ch) {
                    [$chName, $chStatus] = explode(':', $ch);
                    $channels[] = ['name' => $chName, 'status' => $chStatus];
                }
            }
            $typeColors = [
                'poster' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400',
                'video' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
                'story' => 'bg-pink-100 dark:bg-pink-900/30 text-pink-700 dark:text-pink-400',
                'reel' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400',
            ];
            $typeClass = $typeColors[$item['content_type']] ?? 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400';
          ?>
          <div class="px-4 py-4">
            <div class="flex items-start justify-between mb-2">
              <div>
                <div class="font-medium text-slate-900 dark:text-slate-100"><?php echo e($item['title']); ?></div>
                <div class="flex items-center gap-2 mt-1">
                  <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold <?php echo $typeClass; ?>">
                    <?php echo ucfirst($item['content_type']); ?>
                  </span>
                  <?php if ($item['reference_no']): ?>
                    <a href="view.php?id=<?php echo $item['media_request_id']; ?>" class="text-xs text-blue-600 dark:text-blue-400 hover:underline"><?php echo e($item['reference_no']); ?></a>
                  <?php endif; ?>
                </div>
              </div>
              <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?php echo asset_status_classes($item['asset_status']); ?>">
                <?php echo asset_status_label($item['asset_status']); ?>
              </span>
            </div>
            <div class="flex flex-wrap gap-1 mb-2">
              <?php foreach ($channels as $ch): ?>
                <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-medium <?php echo channel_status_classes($ch['status']); ?>">
                  <?php echo channel_label($ch['name']); ?>
                </span>
              <?php endforeach; ?>
            </div>
            <div class="text-xs text-slate-500 dark:text-slate-400">
              <?php if ($item['promo_start_date']): ?>
                <?php echo date('M j', strtotime($item['promo_start_date'])); ?><?php if ($item['promo_end_date']): ?> - <?php echo date('M j', strtotime($item['promo_end_date'])); ?><?php endif; ?>
                &middot;
              <?php endif; ?>
              Created <?php echo date('M j, Y', strtotime($item['created_at'])); ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <div class="flex items-center justify-between border-t border-slate-200 dark:border-slate-700 px-4 py-3">
          <div class="text-xs text-slate-500 dark:text-slate-400">
            Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $perPage, $totalFiltered); ?> of <?php echo $totalFiltered; ?>
          </div>
          <div class="flex gap-1">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
              <a href="?<?php echo http_build_query(array_merge($queryParams, ['page' => $p])); ?>"
                 class="px-3 py-1 rounded text-sm <?php echo $p === $page ? 'bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'; ?>">
                <?php echo $p; ?>
              </a>
            <?php endfor; ?>
          </div>
        </div>
      <?php endif; ?>

    <?php endif; ?>
  </div>

  <?php else: ?>
  <!-- ===== CALENDAR VIEW ===== -->
  <div class="rounded-xl bg-white dark:bg-slate-800 p-4 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
    <div id="content-calendar" x-init="initCalendar()"></div>
  </div>
  <?php endif; ?>

  <!-- ===== CREATE / EDIT MODAL ===== -->
  <template x-if="showModal">
    <div class="fixed inset-0 z-50 flex items-start justify-center pt-8 sm:pt-16 px-4" @keydown.escape.window="showModal = false">
      <div class="fixed inset-0 bg-black/40" @click="showModal = false"></div>
      <div class="relative w-full max-w-2xl max-h-[85vh] overflow-y-auto rounded-2xl bg-white dark:bg-slate-800 shadow-2xl ring-1 ring-slate-200 dark:ring-slate-700 p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-lg font-bold text-slate-900 dark:text-slate-50" x-text="editItem ? 'Edit Content Item' : 'New Content Item'"></h2>
          <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <form method="POST" action="actions/save_content.php">
          <template x-if="editItem">
            <input type="hidden" name="content_item_id" :value="editItem.id">
          </template>

          <div class="grid gap-4 sm:grid-cols-2">
            <!-- Title -->
            <div class="sm:col-span-2">
              <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Title *</label>
              <input type="text" name="title" required x-model="form.title"
                     class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100">
            </div>
            <!-- Content Type -->
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Content Type</label>
              <select name="content_type" x-model="form.content_type"
                      class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100">
                <option value="poster">Poster</option>
                <option value="video">Video</option>
                <option value="story">Story</option>
                <option value="reel">Reel</option>
                <option value="article">Article</option>
                <option value="slide">Slide</option>
                <option value="other">Other</option>
              </select>
            </div>
            <!-- Language -->
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Language</label>
              <input type="text" name="language" x-model="form.language" placeholder="e.g. EN, BM, CN"
                     class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100">
            </div>
            <!-- Asset Status -->
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Asset Status</label>
              <select name="asset_status" x-model="form.asset_status"
                      class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100">
                <option value="na">N/A</option>
                <option value="pending">Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="ready">Ready</option>
                <option value="done">Done</option>
              </select>
            </div>
            <!-- Caption Status -->
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Caption Status</label>
              <select name="caption_status" x-model="form.caption_status"
                      class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100">
                <option value="na">N/A</option>
                <option value="pending">Pending</option>
                <option value="done">Done</option>
              </select>
            </div>
            <!-- Promo Start -->
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Promo Start</label>
              <input type="date" name="promo_start_date" x-model="form.promo_start_date"
                     class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100">
            </div>
            <!-- Promo End -->
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Promo End</label>
              <input type="date" name="promo_end_date" x-model="form.promo_end_date"
                     class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100">
            </div>
            <!-- Default Publish At -->
            <div class="sm:col-span-2">
              <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Default Publish DateTime</label>
              <input type="datetime-local" name="default_publish_at" x-model="form.default_publish_at"
                     class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100">
            </div>
            <!-- Caption Brief -->
            <div class="sm:col-span-2">
              <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Caption Brief</label>
              <textarea name="caption_brief" rows="2" x-model="form.caption_brief"
                        class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100"></textarea>
            </div>
            <!-- Final Caption -->
            <div class="sm:col-span-2">
              <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Final Caption</label>
              <textarea name="final_caption" rows="2" x-model="form.final_caption"
                        class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100"></textarea>
            </div>
            <!-- Asset URL -->
            <div class="sm:col-span-2">
              <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Asset URL (Drive link)</label>
              <input type="url" name="asset_url" x-model="form.asset_url" placeholder="https://..."
                     class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100">
            </div>
            <!-- Asset PIC -->
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Designer PIC</label>
              <select name="asset_pic_user_id" x-model="form.asset_pic_user_id"
                      class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100">
                <option value="">-- Unassigned --</option>
                <?php foreach ($picUsers as $pu): ?>
                  <option value="<?php echo $pu['id']; ?>"><?php echo e($pu['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <!-- SocMed PIC -->
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Social Media PIC</label>
              <select name="socmed_pic_user_id" x-model="form.socmed_pic_user_id"
                      class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100">
                <option value="">-- Unassigned --</option>
                <?php foreach ($picUsers as $pu): ?>
                  <option value="<?php echo $pu['id']; ?>"><?php echo e($pu['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <!-- Notes -->
            <div class="sm:col-span-2">
              <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Notes</label>
              <textarea name="notes" rows="2" x-model="form.notes"
                        class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100"></textarea>
            </div>
            <!-- Do Not Display -->
            <div class="sm:col-span-2">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="do_not_display" x-model="form.do_not_display"
                       class="h-4 w-4 rounded border-slate-300 dark:border-slate-600 text-red-600 focus:ring-red-500">
                <span class="text-sm text-slate-700 dark:text-slate-300">Do not display (hidden from public)</span>
              </label>
            </div>
          </div>

          <!-- Channels Section -->
          <div class="mt-6 pt-4 border-t border-slate-200 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100 mb-3">Publishing Channels</h3>
            <div class="grid gap-3 sm:grid-cols-2">
              <?php
                $allChannels = ['facebook','instagram','telegram','tiktok','youtube','bulletin','av_projection','cm'];
                foreach ($allChannels as $ch):
              ?>
                <div class="flex items-start gap-3 rounded-lg border border-slate-200 dark:border-slate-700 p-3">
                  <label class="flex items-center gap-2 cursor-pointer shrink-0 mt-1">
                    <input type="checkbox" name="channels[]" value="<?php echo $ch; ?>"
                           :checked="form.channels.includes('<?php echo $ch; ?>')"
                           @change="toggleChannel('<?php echo $ch; ?>', $event.target.checked)"
                           class="h-4 w-4 rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm font-medium text-slate-900 dark:text-slate-100"><?php echo channel_label($ch); ?></span>
                  </label>
                  <template x-if="form.channels.includes('<?php echo $ch; ?>')">
                    <div class="flex-1 space-y-2">
                      <select :name="'channel_status[<?php echo $ch; ?>]'" x-model="form.channel_statuses['<?php echo $ch; ?>']"
                              class="w-full rounded border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 px-2 py-1 text-xs text-slate-900 dark:text-slate-100">
                        <option value="pending">Pending</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="posted">Posted</option>
                        <option value="done">Done</option>
                      </select>
                      <input type="datetime-local" :name="'channel_publish[<?php echo $ch; ?>]'" x-model="form.channel_publish['<?php echo $ch; ?>']"
                             class="w-full rounded border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 px-2 py-1 text-xs text-slate-900 dark:text-slate-100"
                             placeholder="Publish date/time">
                    </div>
                  </template>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Submit -->
          <div class="mt-6 flex gap-3 justify-end">
            <button type="button" @click="showModal = false" class="rounded-lg border border-slate-300 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">Cancel</button>
            <button type="submit" class="rounded-lg bg-slate-900 dark:bg-slate-100 px-6 py-2 text-sm font-semibold text-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-slate-200" x-text="editItem ? 'Save Changes' : 'Create'"></button>
          </div>
        </form>
      </div>
    </div>
  </template>

</main>

<!-- Spreadsheet CSS -->
<style>
  .spreadsheet-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
  .spreadsheet-table { border-collapse: separate; border-spacing: 0; font-size: 0.75rem; }
  .spreadsheet-table th, .spreadsheet-table td { white-space: nowrap; }

  /* Sticky first column */
  .spreadsheet-table th:first-child,
  .spreadsheet-table td:first-child {
    position: sticky; left: 0; z-index: 10;
    box-shadow: 2px 0 4px -2px rgba(0,0,0,0.08);
  }

  /* Column group header colors */
  .col-group-sticky { background: rgb(248 250 252); }
  .col-group-social { background: rgb(239 246 255); }
  .col-group-church { background: rgb(255 251 235); }
  .col-group-details { background: rgb(236 253 245); }

  .dark .col-group-sticky { background: rgb(15 23 42); }
  .dark .col-group-social { background: rgba(59, 130, 246, 0.06); }
  .dark .col-group-church { background: rgba(245, 158, 11, 0.06); }
  .dark .col-group-details { background: rgba(16, 185, 129, 0.06); }

  /* Row hover — override sticky column bg */
  .spreadsheet-table tbody tr:hover td {
    background-color: rgb(248 250 252) !important;
  }
  .dark .spreadsheet-table tbody tr:hover td {
    background-color: rgba(51, 65, 85, 0.2) !important;
  }
</style>

<!-- FullCalendar CDN (only for calendar view) -->
<?php if ($activeView === 'calendar'): ?>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<style>
  .dark .fc {
    --fc-border-color: rgb(51 65 85);
    --fc-page-bg-color: rgb(30 41 59);
    --fc-neutral-bg-color: rgb(15 23 42);
    --fc-list-event-hover-bg-color: rgb(51 65 85);
    --fc-today-bg-color: rgba(59, 130, 246, 0.08);
    --fc-neutral-text-color: rgb(148 163 184);
  }
  .dark .fc .fc-col-header-cell-cushion,
  .dark .fc .fc-daygrid-day-number,
  .dark .fc .fc-list-day-text,
  .dark .fc .fc-list-day-side-text { color: rgb(226 232 240); }
  .dark .fc .fc-button-primary { background-color: rgb(51 65 85); border-color: rgb(71 85 105); color: rgb(226 232 240); }
  .dark .fc .fc-button-primary:hover { background-color: rgb(71 85 105); }
  .dark .fc .fc-button-primary:not(:disabled).fc-button-active,
  .dark .fc .fc-button-primary:not(:disabled):active { background-color: rgb(30 58 138); border-color: rgb(59 130 246); }
  .dark .fc .fc-toolbar-title { color: rgb(248 250 252); }
  .dark .fc .fc-list-event-title a { color: rgb(226 232 240); }
  .fc .fc-event { cursor: pointer; border-radius: 4px; font-size: 0.8rem; padding: 1px 4px; }
  .fc .fc-daygrid-event-dot { display: none; }
</style>
<?php endif; ?>

<script>
function contentPage() {
  return {
    showModal: false,
    editItem: null,

    // Spreadsheet inline editing state
    activeChDrop: null,
    activeStDrop: null,
    activePicDrop: null,

    // Channel status helpers
    chAbbr(status) {
      return { na: '--', pending: 'P', scheduled: 'S', posted: 'X', done: 'D' }[status] || '?';
    },
    chColor(status) {
      return {
        na: 'bg-slate-100 dark:bg-slate-700 text-slate-400',
        pending: 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
        scheduled: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
        posted: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
        done: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
      }[status] || 'bg-slate-100 text-slate-400';
    },

    // AJAX inline update
    async updateInline(itemId, fieldType, payload) {
      try {
        const resp = await fetch('api/update_content_inline.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ content_item_id: itemId, field_type: fieldType, ...payload })
        });
        const data = await resp.json();
        if (!resp.ok || !data.success) {
          console.error('Inline update failed:', data.error || 'Unknown error');
        }
      } catch (err) {
        console.error('Inline update error:', err);
      }
    },

    form: {
      title: '',
      content_type: 'poster',
      language: '',
      asset_status: 'pending',
      caption_status: 'na',
      promo_start_date: '',
      promo_end_date: '',
      default_publish_at: '',
      caption_brief: '',
      final_caption: '',
      asset_url: '',
      notes: '',
      do_not_display: false,
      asset_pic_user_id: '',
      socmed_pic_user_id: '',
      channels: [],
      channel_statuses: {},
      channel_publish: {},
    },
    calendar: null,

    openCreateModal() {
      this.editItem = null;
      this.form = {
        title: '', content_type: 'poster', language: '', asset_status: 'pending',
        caption_status: 'na', promo_start_date: '', promo_end_date: '',
        default_publish_at: '', caption_brief: '', final_caption: '',
        asset_url: '', notes: '', do_not_display: false,
        asset_pic_user_id: '', socmed_pic_user_id: '',
        channels: [], channel_statuses: {}, channel_publish: {},
      };
      this.showModal = true;
    },

    openEditModal(item) {
      this.editItem = item;
      const chs = (item.channels || []).map(c => c.name);
      const statuses = {};
      const publish = {};
      (item.channels || []).forEach(c => {
        statuses[c.name] = c.status || 'pending';
        publish[c.name] = c.publish_at || '';
      });
      this.form = {
        title: item.title || '',
        content_type: item.content_type || 'poster',
        language: item.language || '',
        asset_status: item.asset_status || 'pending',
        caption_status: item.caption_status || 'na',
        promo_start_date: item.promo_start_date || '',
        promo_end_date: item.promo_end_date || '',
        default_publish_at: item.default_publish_at || '',
        caption_brief: item.caption_brief || '',
        final_caption: item.final_caption || '',
        asset_url: item.asset_url || '',
        notes: item.notes || '',
        do_not_display: item.do_not_display || false,
        asset_pic_user_id: item.asset_pic_user_id || '',
        socmed_pic_user_id: item.socmed_pic_user_id || '',
        channels: chs,
        channel_statuses: statuses,
        channel_publish: publish,
      };
      this.showModal = true;
    },

    toggleChannel(ch, checked) {
      if (checked) {
        if (!this.form.channels.includes(ch)) this.form.channels.push(ch);
        if (!this.form.channel_statuses[ch]) this.form.channel_statuses[ch] = 'pending';
      } else {
        this.form.channels = this.form.channels.filter(c => c !== ch);
      }
    },

    initCalendar() {
      const calEl = document.getElementById('content-calendar');
      if (!calEl) return;

      this.calendar = new FullCalendar.Calendar(calEl, {
        initialView: window.innerWidth < 768 ? 'listWeek' : 'dayGridMonth',
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        buttonText: { today: 'Today', month: 'Month', week: 'Week', list: 'List' },
        height: 'auto',
        navLinks: true,
        nowIndicator: true,
        dayMaxEvents: 4,
        events: (info, successCallback, failureCallback) => {
          const params = new URLSearchParams({ start: info.startStr, end: info.endStr });
          fetch('api/content_events.php?' + params.toString())
            .then(r => r.json())
            .then(data => successCallback(data))
            .catch(err => { console.error('Content calendar fetch error:', err); failureCallback(err); });
        },
        eventClick: (info) => {
          info.jsEvent.preventDefault();
          if (info.event.url) window.location = info.event.url;
        },
        windowResize: () => {
          if (window.innerWidth < 768) this.calendar.changeView('listWeek');
        }
      });
      this.calendar.render();
    }
  };
}
</script>

<?php include __DIR__ . "/partials/footer.php"; ?>
