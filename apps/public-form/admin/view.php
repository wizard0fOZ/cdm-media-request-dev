<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_admin_auth();

require_once __DIR__ . '/../includes/db.php';

// Helper function for safe output
function e(?string $s): string {
  return $s === null ? '' : htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// Get request ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  header('Location: index.php');
  exit;
}

// Check for success message
$success = isset($_GET['success']) ? $_GET['success'] : '';

// Fetch main request
$stmt = $pdo->prepare("SELECT * FROM media_requests WHERE id = :id");
$stmt->execute([':id' => $id]);
$request = $stmt->fetch();

if (!$request) {
  header('Location: index.php');
  exit;
}

// Fetch event schedule
$stmt = $pdo->prepare("SELECT * FROM event_schedules WHERE media_request_id = :id");
$stmt->execute([':id' => $id]);
$schedule = $stmt->fetch();

// Fetch occurrences (for custom_list schedule)
$occurrences = [];
if ($schedule && $schedule['schedule_type'] === 'custom_list') {
  $stmt = $pdo->prepare("
    SELECT * FROM event_occurrences
    WHERE event_schedule_id = :schedule_id
    ORDER BY occurrence_date
  ");
  $stmt->execute([':schedule_id' => $schedule['id']]);
  $occurrences = $stmt->fetchAll();
}

// Fetch services
$stmt = $pdo->prepare("SELECT type FROM request_types WHERE media_request_id = :id");
$stmt->execute([':id' => $id]);
$services = array_column($stmt->fetchAll(), 'type');

// Fetch AV details if av service selected
$avDetails = null;
$avRooms = [];
$avItems = [];
if (in_array('av', $services)) {
  $stmt = $pdo->prepare("SELECT * FROM av_details WHERE media_request_id = :id");
  $stmt->execute([':id' => $id]);
  $avDetails = $stmt->fetch();

  $stmt = $pdo->prepare("
    SELECT r.name
    FROM request_rooms rr
    JOIN rooms r ON rr.room_id = r.id
    WHERE rr.media_request_id = :id
  ");
  $stmt->execute([':id' => $id]);
  $avRooms = array_column($stmt->fetchAll(), 'name');

  $stmt = $pdo->prepare("
    SELECT e.name, ai.quantity, ai.note, r.name as room_name
    FROM av_items ai
    JOIN equipment e ON ai.equipment_id = e.id
    LEFT JOIN rooms r ON ai.room_id = r.id
    WHERE ai.media_request_id = :id
  ");
  $stmt->execute([':id' => $id]);
  $avItems = $stmt->fetchAll();
}

// Fetch media details if media service selected
$mediaDetails = null;
$mediaPlatforms = [];
if (in_array('media', $services)) {
  $stmt = $pdo->prepare("SELECT * FROM media_details WHERE media_request_id = :id");
  $stmt->execute([':id' => $id]);
  $mediaDetails = $stmt->fetch();

  $stmt = $pdo->prepare("
    SELECT platform, platform_other_label
    FROM media_platforms
    WHERE media_request_id = :id
  ");
  $stmt->execute([':id' => $id]);
  $mediaPlatforms = $stmt->fetchAll();
}

// Fetch photo details if photo service selected
$photoDetails = null;
if (in_array('photo', $services)) {
  $stmt = $pdo->prepare("SELECT * FROM photo_details WHERE media_request_id = :id");
  $stmt->execute([':id' => $id]);
  $photoDetails = $stmt->fetch();
}

$pageTitle = "Request " . e($request['reference_no']) . " | CDM Admin";
include __DIR__ . "/partials/header.php";
?>

<main class="flex-grow mx-auto max-w-4xl px-4 py-8 w-full">

  <!-- Back Button -->
  <div class="mb-6">
    <a
      href="index.php"
      class="inline-flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100"
    >
      <i class="fa-solid fa-arrow-left"></i>
      Back to Dashboard
    </a>
  </div>

  <!-- Success Message -->
  <?php if ($success === '1'): ?>
    <div class="mb-6 rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/30 px-4 py-3">
      <div class="flex items-center gap-2 text-sm text-green-700 dark:text-green-300">
        <i class="fa-solid fa-check-circle"></i>
        <span>Request status updated successfully</span>
      </div>
    </div>
  <?php endif; ?>

  <!-- Header Card -->
  <div class="mb-6 rounded-xl bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-50"><?php echo e($request['event_name']); ?></h1>
        <div class="mt-2 flex flex-wrap items-center gap-2">
          <span class="font-mono text-sm font-semibold text-slate-600 dark:text-slate-400">
            <?php echo e($request['reference_no']); ?>
          </span>
          <?php if ($request['is_late']): ?>
            <span class="inline-flex items-center rounded-full bg-amber-100 dark:bg-amber-900/30 px-2.5 py-1 text-xs font-medium text-amber-700 dark:text-amber-400">
              <i class="fa-solid fa-exclamation-triangle text-xs mr-1"></i>
              Late Submission (<?php echo $request['lead_days']; ?> days notice)
            </span>
          <?php endif; ?>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <?php
        $statusColors = [
          'pending' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
          'approved' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
          'rejected' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'
        ];
        $statusColor = $statusColors[$request['request_status']] ?? 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300';
        ?>
        <span class="inline-flex items-center rounded-full px-3 py-1.5 text-sm font-semibold <?php echo $statusColor; ?>">
          <?php echo ucfirst($request['request_status']); ?>
        </span>
      </div>
    </div>
  </div>

  <!-- Requestor Information -->
  <div class="mb-6 rounded-xl bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
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
        <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Ministry / Organization</div>
        <div class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100"><?php echo e($request['ministry']) ?: '-'; ?></div>
      </div>
      <div>
        <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Email</div>
        <div class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100">
          <a href="mailto:<?php echo e($request['email']); ?>" class="text-blue-600 dark:text-blue-400 hover:underline">
            <?php echo e($request['email']); ?>
          </a>
        </div>
      </div>
      <div>
        <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Phone</div>
        <div class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100">
          <?php echo e($request['contact_no']); ?>
        </div>
      </div>
      <div class="sm:col-span-2">
        <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Submitted On</div>
        <div class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100">
          <?php echo date('F j, Y \a\t g:i A', strtotime($request['submitted_at'])); ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Event Details -->
  <div class="mb-6 rounded-xl bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
    <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-slate-900 dark:text-slate-50">
      <i class="fa-solid fa-calendar-day text-slate-400 dark:text-slate-500"></i>
      Event Details
    </h2>
    <div class="space-y-3">
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
    </div>
  </div>

  <!-- Schedule -->
  <?php if ($schedule): ?>
    <div class="mb-6 rounded-xl bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
      <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-slate-900 dark:text-slate-50">
        <i class="fa-solid fa-clock text-slate-400 dark:text-slate-500"></i>
        Schedule
      </h2>

      <?php if ($schedule['schedule_type'] === 'recurring'): ?>
        <!-- Recurring Event -->
        <div class="rounded-lg bg-slate-50 dark:bg-slate-900 p-4">
          <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-3">Recurring Event</div>
          <div class="grid gap-3 sm:grid-cols-2">
            <div>
              <span class="text-xs text-slate-500 dark:text-slate-400">Pattern:</span>
              <span class="ml-1 text-sm font-medium text-slate-900 dark:text-slate-100"><?php echo ucfirst(e($schedule['recurrence_pattern'])); ?></span>
            </div>
            <div>
              <span class="text-xs text-slate-500 dark:text-slate-400">Day:</span>
              <span class="ml-1 text-sm font-medium text-slate-900 dark:text-slate-100"><?php echo e($schedule['recurrence_days_of_week']); ?></span>
            </div>
            <div>
              <span class="text-xs text-slate-500 dark:text-slate-400">Date Range:</span>
              <span class="ml-1 text-sm font-medium text-slate-900 dark:text-slate-100">
                <?php echo e($schedule['start_date']) . ' to ' . e($schedule['end_date']); ?>
              </span>
            </div>
            <div>
              <span class="text-xs text-slate-500 dark:text-slate-400">Time:</span>
              <span class="ml-1 text-sm font-medium text-slate-900 dark:text-slate-100">
                <?php echo e($schedule['start_time']) . ' - ' . e($schedule['end_time']); ?>
              </span>
            </div>
          </div>
        </div>

      <?php elseif ($schedule['schedule_type'] === 'custom_list'): ?>
        <!-- Custom List of Dates -->
        <div class="rounded-lg bg-slate-50 dark:bg-slate-900 p-4">
          <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-3">Specific Dates</div>
          <div class="space-y-2">
            <?php foreach ($occurrences as $occ): ?>
              <div class="flex items-center gap-2 text-sm">
                <i class="fa-solid fa-circle text-[6px] text-slate-400 dark:text-slate-500"></i>
                <span class="font-medium text-slate-900 dark:text-slate-100"><?php echo e($occ['occurrence_date']); ?></span>
                <span class="text-slate-500 dark:text-slate-400">
                  <?php echo ($occ['start_time'] ?: '-') . ' - ' . ($occ['end_time'] ?: '-'); ?>
                </span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($schedule['notes']): ?>
        <div class="mt-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-3">
          <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Schedule Notes</div>
          <div class="text-sm text-slate-700 dark:text-slate-300"><?php echo nl2br(e($schedule['notes'])); ?></div>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- Services -->
  <div class="mb-6 rounded-xl bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
    <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-slate-900 dark:text-slate-50">
      <i class="fa-solid fa-cogs text-slate-400 dark:text-slate-500"></i>
      Services Requested
    </h2>

    <div class="space-y-4">
      <!-- AV Support -->
      <?php if (in_array('av', $services) && $avDetails): ?>
        <div class="rounded-lg border border-purple-200 dark:border-purple-800 bg-purple-50 dark:bg-purple-900/30 p-4">
          <div class="flex items-center gap-2 mb-3">
            <i class="fa-solid fa-headphones text-purple-600 dark:text-purple-400"></i>
            <span class="font-semibold text-purple-900 dark:text-purple-300">AV Support</span>
          </div>
          <div class="space-y-3 text-sm">
            <?php if (!empty($avRooms)): ?>
              <div>
                <span class="text-purple-700 dark:text-purple-400">Rooms:</span>
                <span class="ml-1 font-medium text-slate-900 dark:text-slate-100"><?php echo implode(', ', $avRooms); ?></span>
              </div>
            <?php endif; ?>
            <?php if (!empty($avItems)): ?>
              <div>
                <span class="text-purple-700 dark:text-purple-400">Equipment:</span>
                <ul class="mt-1 space-y-1">
                  <?php foreach ($avItems as $item): ?>
                    <li class="ml-4 text-slate-700 dark:text-slate-300">
                      • <?php echo e($item['name']); ?> (Qty: <?php echo $item['quantity']; ?>)
                      <?php if ($item['room_name']): ?>
                        - <?php echo e($item['room_name']); ?>
                      <?php endif; ?>
                      <?php if ($item['note']): ?>
                        <span class="text-xs text-slate-500 dark:text-slate-400"> - <?php echo e($item['note']); ?></span>
                      <?php endif; ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>
            <?php if ($avDetails['rehearsal_date']): ?>
              <div>
                <span class="text-purple-700 dark:text-purple-400">Rehearsal:</span>
                <span class="ml-1 text-slate-700 dark:text-slate-300">
                  <?php echo e($avDetails['rehearsal_date']); ?>
                  <?php if ($avDetails['rehearsal_start_time']): ?>
                    at <?php echo e($avDetails['rehearsal_start_time']) . ' - ' . e($avDetails['rehearsal_end_time']); ?>
                  <?php endif; ?>
                </span>
              </div>
            <?php endif; ?>
            <?php if ($avDetails['note']): ?>
              <div>
                <span class="text-purple-700 dark:text-purple-400">Notes:</span>
                <span class="ml-1 text-slate-700 dark:text-slate-300"><?php echo nl2br(e($avDetails['note'])); ?></span>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Media -->
      <?php if (in_array('media', $services) && $mediaDetails): ?>
        <div class="rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/30 p-4">
          <div class="flex items-center gap-2 mb-3">
            <i class="fa-solid fa-photo-film text-green-600 dark:text-green-400"></i>
            <span class="font-semibold text-green-900 dark:text-green-300">Poster / Video Design</span>
          </div>
          <div class="space-y-3 text-sm">
            <?php if ($mediaDetails['description']): ?>
              <div>
                <span class="text-green-700 dark:text-green-400">Description:</span>
                <span class="ml-1 text-slate-700 dark:text-slate-300"><?php echo nl2br(e($mediaDetails['description'])); ?></span>
              </div>
            <?php endif; ?>
            <?php if (!empty($mediaPlatforms)): ?>
              <div>
                <span class="text-green-700 dark:text-green-400">Platforms:</span>
                <div class="mt-1 flex flex-wrap gap-1">
                  <?php foreach ($mediaPlatforms as $platform): ?>
                    <span class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900/50 px-2 py-0.5 text-xs font-medium text-green-800 dark:text-green-300">
                      <?php
                      echo ucfirst($platform['platform']);
                      if ($platform['platform'] === 'other' && $platform['platform_other_label']) {
                        echo ' (' . e($platform['platform_other_label']) . ')';
                      }
                      ?>
                    </span>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
            <?php if ($mediaDetails['promo_start_date'] || $mediaDetails['promo_end_date']): ?>
              <div>
                <span class="text-green-700 dark:text-green-400">Promotion Period:</span>
                <span class="ml-1 text-slate-700 dark:text-slate-300">
                  <?php echo ($mediaDetails['promo_start_date'] ?: '-') . ' to ' . ($mediaDetails['promo_end_date'] ?: '-'); ?>
                </span>
              </div>
            <?php endif; ?>
            <?php if ($mediaDetails['caption_details']): ?>
              <div>
                <span class="text-green-700 dark:text-green-400">Caption/Details:</span>
                <span class="ml-1 text-slate-700 dark:text-slate-300"><?php echo nl2br(e($mediaDetails['caption_details'])); ?></span>
              </div>
            <?php endif; ?>
            <?php if ($mediaDetails['note']): ?>
              <div>
                <span class="text-green-700 dark:text-green-400">Notes:</span>
                <span class="ml-1 text-slate-700 dark:text-slate-300"><?php echo nl2br(e($mediaDetails['note'])); ?></span>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Photography -->
      <?php if (in_array('photo', $services) && $photoDetails): ?>
        <div class="rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/30 p-4">
          <div class="flex items-center gap-2 mb-3">
            <i class="fa-solid fa-camera text-blue-600 dark:text-blue-400"></i>
            <span class="font-semibold text-blue-900 dark:text-blue-300">Photography</span>
          </div>
          <div class="space-y-3 text-sm">
            <?php if ($photoDetails['needed_date']): ?>
              <div>
                <span class="text-blue-700 dark:text-blue-400">Date:</span>
                <span class="ml-1 text-slate-700 dark:text-slate-300">
                  <?php echo e($photoDetails['needed_date']); ?>
                  <?php if ($photoDetails['start_time']): ?>
                    at <?php echo e($photoDetails['start_time']) . ' - ' . e($photoDetails['end_time']); ?>
                  <?php endif; ?>
                </span>
              </div>
            <?php endif; ?>
            <?php if ($photoDetails['note']): ?>
              <div>
                <span class="text-blue-700 dark:text-blue-400">Notes:</span>
                <span class="ml-1 text-slate-700 dark:text-slate-300"><?php echo nl2br(e($photoDetails['note'])); ?></span>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- References -->
  <?php if ($request['reference_url'] || $request['reference_note']): ?>
    <div class="mb-6 rounded-xl bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
      <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-slate-900 dark:text-slate-50">
        <i class="fa-solid fa-link text-slate-400 dark:text-slate-500"></i>
        References
      </h2>
      <div class="space-y-3 text-sm">
        <?php if ($request['reference_url']): ?>
          <div>
            <span class="text-slate-500 dark:text-slate-400">Link:</span>
            <a href="<?php echo e($request['reference_url']); ?>" target="_blank" rel="noopener noreferrer" class="ml-1 font-medium text-blue-600 dark:text-blue-400 hover:underline break-all">
              <?php echo e($request['reference_url']); ?>
              <i class="fa-solid fa-external-link-alt text-xs ml-1"></i>
            </a>
          </div>
        <?php endif; ?>
        <?php if ($request['reference_note']): ?>
          <div>
            <span class="text-slate-500 dark:text-slate-400">Notes:</span>
            <span class="ml-1 text-slate-700 dark:text-slate-300"><?php echo nl2br(e($request['reference_note'])); ?></span>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- Actions -->
  <?php if ($request['request_status'] === 'pending'): ?>
    <div class="mb-6 rounded-xl bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700" x-data="{ showRejectModal: false }">
      <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-slate-900 dark:text-slate-50">
        <i class="fa-solid fa-gavel text-slate-400 dark:text-slate-500"></i>
        Actions
      </h2>
      <div class="flex flex-col sm:flex-row gap-3">
        <form method="POST" action="actions/approve.php" class="flex-1">
          <input type="hidden" name="id" value="<?php echo $id; ?>" />
          <button
            type="submit"
            class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-green-600 dark:bg-green-500 px-6 py-3 font-semibold text-white hover:bg-green-700 dark:hover:bg-green-600 transition-colors"
            onclick="return confirm('Are you sure you want to approve this request?');"
          >
            <i class="fa-solid fa-check-circle"></i>
            Approve Request
          </button>
        </form>

        <button
          type="button"
          @click="showRejectModal = true"
          class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 dark:bg-red-500 px-6 py-3 font-semibold text-white hover:bg-red-700 dark:hover:bg-red-600 transition-colors"
        >
          <i class="fa-solid fa-times-circle"></i>
          Reject Request
        </button>
      </div>

      <!-- Rejection Modal -->
      <div
        x-show="showRejectModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @keydown.escape.window="showRejectModal = false"
      >
        <!-- Backdrop -->
        <div
          class="absolute inset-0 bg-black/50"
          @click="showRejectModal = false"
        ></div>

        <!-- Modal Content -->
        <div
          class="relative w-full max-w-md rounded-xl bg-white dark:bg-slate-800 p-6 shadow-xl ring-1 ring-slate-200 dark:ring-slate-700"
          x-show="showRejectModal"
          x-transition:enter="transition ease-out duration-200"
          x-transition:enter-start="opacity-0 scale-95"
          x-transition:enter-end="opacity-100 scale-100"
          x-transition:leave="transition ease-in duration-150"
          x-transition:leave-start="opacity-100 scale-100"
          x-transition:leave-end="opacity-0 scale-95"
        >
          <h3 class="text-lg font-bold text-slate-900 dark:text-slate-50 mb-4">
            <i class="fa-solid fa-times-circle text-red-500 mr-2"></i>
            Reject Request
          </h3>

          <form method="POST" action="actions/reject.php" x-data="{ reason: '' }">
            <input type="hidden" name="id" value="<?php echo $id; ?>" />

            <div class="mb-4">
              <label for="rejection_reason" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                Rejection Reason <span class="text-red-500">*</span>
              </label>
              <textarea
                id="rejection_reason"
                name="rejection_reason"
                rows="4"
                x-model="reason"
                required
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-900 dark:focus:ring-slate-100"
                placeholder="Please provide a reason for rejecting this request..."
              ></textarea>
              <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                This reason will be sent to the requestor via email.
              </p>
            </div>

            <div class="flex gap-3">
              <button
                type="button"
                @click="showRejectModal = false"
                class="flex-1 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="reason.trim() === ''"
                class="flex-1 rounded-lg bg-red-600 dark:bg-red-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700 dark:hover:bg-red-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Confirm Rejection
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Rejection Reason (if rejected) -->
  <?php if ($request['request_status'] === 'rejected' && !empty($request['rejection_reason'])): ?>
    <div class="mb-6 rounded-xl bg-red-50 dark:bg-red-900/20 p-6 shadow-sm ring-1 ring-red-200 dark:ring-red-800">
      <h2 class="mb-3 flex items-center gap-2 text-lg font-bold text-red-900 dark:text-red-300">
        <i class="fa-solid fa-ban text-red-500 dark:text-red-400"></i>
        Rejection Reason
      </h2>
      <p class="text-sm text-red-800 dark:text-red-200"><?php echo nl2br(e($request['rejection_reason'])); ?></p>
    </div>
  <?php endif; ?>

</main>

<?php include __DIR__ . "/partials/footer.php"; ?>
