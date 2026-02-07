<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_admin_auth();

require_once __DIR__ . '/../includes/db.php';

// Helper function for safe output
function e(?string $s): string {
  return $s === null ? '' : htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// Fetch all requests with related data
$stmt = $pdo->prepare("
  SELECT
    mr.id,
    mr.reference_no,
    mr.event_name,
    mr.requestor_name,
    mr.ministry,
    mr.email,
    mr.contact_no,
    mr.request_status,
    mr.is_late,
    mr.lead_days,
    mr.submitted_at,
    es.start_date,
    es.end_date,
    GROUP_CONCAT(DISTINCT rt.type ORDER BY rt.type) as services
  FROM media_requests mr
  LEFT JOIN request_types rt ON mr.id = rt.media_request_id
  LEFT JOIN event_schedules es ON mr.id = es.media_request_id
  GROUP BY mr.id
  ORDER BY mr.submitted_at DESC
");
$stmt->execute();
$requests = $stmt->fetchAll();

// Count by status
$statusCounts = [
  'pending' => 0,
  'approved' => 0,
  'rejected' => 0,
  'total' => count($requests)
];

foreach ($requests as $req) {
  $status = $req['request_status'];
  if (isset($statusCounts[$status])) {
    $statusCounts[$status]++;
  }
}

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
  <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <!-- Total Requests -->
    <div class="rounded-xl bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-slate-600 dark:text-slate-400">Total Requests</p>
          <p class="mt-1 text-3xl font-bold text-slate-900 dark:text-slate-50"><?php echo $statusCounts['total']; ?></p>
        </div>
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-700">
          <i class="fa-solid fa-inbox text-xl text-slate-600 dark:text-slate-400"></i>
        </div>
      </div>
    </div>

    <!-- Pending -->
    <div class="rounded-xl bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-slate-600 dark:text-slate-400">Pending</p>
          <p class="mt-1 text-3xl font-bold text-amber-600 dark:text-amber-400"><?php echo $statusCounts['pending']; ?></p>
        </div>
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
          <i class="fa-solid fa-clock text-xl text-amber-600 dark:text-amber-400"></i>
        </div>
      </div>
    </div>

    <!-- Approved -->
    <div class="rounded-xl bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-slate-600 dark:text-slate-400">Approved</p>
          <p class="mt-1 text-3xl font-bold text-green-600 dark:text-green-400"><?php echo $statusCounts['approved']; ?></p>
        </div>
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
          <i class="fa-solid fa-check-circle text-xl text-green-600 dark:text-green-400"></i>
        </div>
      </div>
    </div>

    <!-- Rejected -->
    <div class="rounded-xl bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-slate-600 dark:text-slate-400">Rejected</p>
          <p class="mt-1 text-3xl font-bold text-red-600 dark:text-red-400"><?php echo $statusCounts['rejected']; ?></p>
        </div>
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
          <i class="fa-solid fa-times-circle text-xl text-red-600 dark:text-red-400"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- Requests Table -->
  <div class="rounded-xl bg-white dark:bg-slate-800 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
      <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-50">All Requests</h2>
    </div>

    <?php if (empty($requests)): ?>
      <!-- Empty State -->
      <div class="px-6 py-12 text-center">
        <div class="flex h-16 w-16 mx-auto items-center justify-center rounded-full bg-slate-100 dark:bg-slate-700 mb-4">
          <i class="fa-solid fa-inbox text-2xl text-slate-400 dark:text-slate-500"></i>
        </div>
        <p class="text-slate-600 dark:text-slate-400">No requests submitted yet</p>
      </div>
    <?php else: ?>
      <!-- Table (Desktop) -->
      <div class="hidden lg:block overflow-x-auto">
        <table class="w-full">
          <thead class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-600 dark:text-slate-400">Reference</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-600 dark:text-slate-400">Event</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-600 dark:text-slate-400">Requester</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-600 dark:text-slate-400">Ministry</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-600 dark:text-slate-400">Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-600 dark:text-slate-400">Services</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-600 dark:text-slate-400">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-600 dark:text-slate-400">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
            <?php foreach ($requests as $req): ?>
              <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <!-- Reference -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center gap-2">
                    <span class="font-mono text-sm font-semibold text-slate-900 dark:text-slate-100">
                      <?php echo e($req['reference_no']); ?>
                    </span>
                    <?php if ($req['is_late']): ?>
                      <span class="inline-flex items-center rounded-full bg-amber-100 dark:bg-amber-900/30 px-2 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-400" title="Late submission (less than 2 weeks notice)">
                        <i class="fa-solid fa-exclamation-triangle text-[10px] mr-1"></i>
                        Late
                      </span>
                    <?php endif; ?>
                  </div>
                </td>

                <!-- Event -->
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-slate-900 dark:text-slate-100 max-w-xs truncate">
                    <?php echo e($req['event_name']); ?>
                  </div>
                  <?php if ($req['start_date']): ?>
                    <div class="text-xs text-slate-500 dark:text-slate-400">
                      <?php echo e($req['start_date']); ?>
                    </div>
                  <?php endif; ?>
                </td>

                <!-- Requester -->
                <td class="px-6 py-4">
                  <div class="text-sm text-slate-900 dark:text-slate-100"><?php echo e($req['requestor_name']); ?></div>
                </td>

                <!-- Ministry -->
                <td class="px-6 py-4">
                  <div class="text-sm text-slate-600 dark:text-slate-400"><?php echo e($req['ministry']) ?: '-'; ?></div>
                </td>

                <!-- Submitted Date -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-slate-600 dark:text-slate-400">
                    <?php echo date('M j, Y', strtotime($req['submitted_at'])); ?>
                  </div>
                </td>

                <!-- Services -->
                <td class="px-6 py-4">
                  <div class="flex flex-wrap gap-1">
                    <?php
                    $services = $req['services'] ? explode(',', $req['services']) : [];
                    foreach ($services as $service):
                      $colors = [
                        'av' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400',
                        'media' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
                        'photo' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400'
                      ];
                      $color = $colors[$service] ?? 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300';
                      $labels = ['av' => 'AV', 'media' => 'Poster/Video', 'photo' => 'Photo'];
                      $label = $labels[$service] ?? ucfirst($service);
                    ?>
                      <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?php echo $color; ?>">
                        <?php echo $label; ?>
                      </span>
                    <?php endforeach; ?>
                  </div>
                </td>

                <!-- Status -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <?php
                  $statusColors = [
                    'pending' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
                    'approved' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
                    'rejected' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'
                  ];
                  $statusColor = $statusColors[$req['request_status']] ?? 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300';
                  ?>
                  <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?php echo $statusColor; ?>">
                    <?php echo ucfirst($req['request_status']); ?>
                  </span>
                </td>

                <!-- Actions -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <a
                    href="view.php?id=<?php echo $req['id']; ?>"
                    class="inline-flex items-center gap-1 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100"
                  >
                    <i class="fa-solid fa-eye"></i>
                    View
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Cards (Mobile) -->
      <div class="lg:hidden divide-y divide-slate-200 dark:divide-slate-700">
        <?php foreach ($requests as $req): ?>
          <div class="px-6 py-4">
            <div class="flex items-start justify-between mb-3">
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                  <span class="font-mono text-sm font-semibold text-slate-900 dark:text-slate-100">
                    <?php echo e($req['reference_no']); ?>
                  </span>
                  <?php if ($req['is_late']): ?>
                    <span class="inline-flex items-center rounded-full bg-amber-100 dark:bg-amber-900/30 px-2 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-400">
                      <i class="fa-solid fa-exclamation-triangle text-[10px] mr-1"></i>
                      Late
                    </span>
                  <?php endif; ?>
                </div>
                <div class="text-base font-medium text-slate-900 dark:text-slate-100">
                  <?php echo e($req['event_name']); ?>
                </div>
                <div class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                  <?php echo e($req['requestor_name']); ?>
                  <?php if ($req['ministry']): ?>
                    ¡¤ <?php echo e($req['ministry']); ?>
                  <?php endif; ?>
                </div>
              </div>
              <?php
              $statusColors = [
                'pending' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
                'approved' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
                'rejected' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'
              ];
              $statusColor = $statusColors[$req['request_status']] ?? 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300';
              ?>
              <span class="ml-2 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?php echo $statusColor; ?>">
                <?php echo ucfirst($req['request_status']); ?>
              </span>
            </div>

            <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400 mb-3">
              <span><?php echo date('M j, Y', strtotime($req['submitted_at'])); ?></span>
              <?php if ($req['start_date']): ?>
                <span>Event: <?php echo e($req['start_date']); ?></span>
              <?php endif; ?>
            </div>

            <div class="flex items-center justify-between">
              <div class="flex flex-wrap gap-1">
                <?php
                $services = $req['services'] ? explode(',', $req['services']) : [];
                foreach ($services as $service):
                  $colors = [
                    'av' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400',
                    'media' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
                    'photo' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400'
                  ];
                  $color = $colors[$service] ?? 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300';
                  $labels = ['av' => 'AV', 'media' => 'Poster/Video', 'photo' => 'Photo'];
                  $label = $labels[$service] ?? ucfirst($service);
                ?>
                  <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?php echo $color; ?>">
                    <?php echo $label; ?>
                  </span>
                <?php endforeach; ?>
              </div>

              <a
                href="view.php?id=<?php echo $req['id']; ?>"
                class="inline-flex items-center gap-1 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100"
              >
                <i class="fa-solid fa-eye"></i>
                View
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</main>

<?php include __DIR__ . "/partials/footer.php"; ?>
