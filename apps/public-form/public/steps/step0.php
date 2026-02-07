<div class="rounded-xl bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">

  <!-- Step Title -->
  <div class="mb-6">
    <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-50">Required Approvals</h2>
    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
      Before submitting a media request, please confirm you have obtained the necessary approvals from your ministry head or leadership, the parish priest (if applicable), and confirmed any required room bookings.
    </p>
  </div>

  <!-- Question -->
  <div class="rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 p-5">
    <label class="mb-3 block text-sm font-semibold text-slate-800 dark:text-slate-200">
      Have you obtained the required approvals from your ministry head or leadership, priest approval, and room bookings?
      <span class="text-red-600 dark:text-red-400">*</span>
    </label>

    <div class="space-y-3">
      <!-- Yes Option -->
      <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 p-4 transition hover:border-slate-400 dark:hover:border-slate-500 hover:bg-slate-50 dark:hover:bg-slate-700"
             :class="form.has_required_approvals === 'yes' ? 'border-slate-900 dark:border-slate-100 bg-slate-100 dark:bg-slate-700 ring-2 ring-slate-900 dark:ring-slate-100' : ''">
        <input type="radio"
               x-model="form.has_required_approvals"
               value="yes"
               class="mt-0.5 h-4 w-4 text-slate-900 dark:text-slate-100 focus:ring-slate-900 dark:focus:ring-slate-100"
               @change="errors.has_required_approvals = ''" />
        <div class="flex-1">
          <div class="font-semibold text-slate-900 dark:text-slate-50">Yes, I have the required approvals</div>
          <div class="mt-1 text-sm text-slate-600 dark:text-slate-400">
            I have confirmed with my ministry head or leadership, obtained necessary priest approvals, and secured required room bookings to proceed with this request.
          </div>
        </div>
      </label>

      <!-- No Option -->
      <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 p-4 transition hover:border-slate-400 dark:hover:border-slate-500 hover:bg-slate-50 dark:hover:bg-slate-700"
             :class="form.has_required_approvals === 'no' ? 'border-red-600 dark:border-red-500 bg-red-50 dark:bg-red-900/30 ring-2 ring-red-600 dark:ring-red-500' : ''">
        <input type="radio"
               x-model="form.has_required_approvals"
               value="no"
               class="mt-0.5 h-4 w-4 text-red-600 dark:text-red-500 focus:ring-red-600 dark:focus:ring-red-500"
               @change="errors.has_required_approvals = ''" />
        <div class="flex-1">
          <div class="font-semibold text-slate-900 dark:text-slate-50">No, I have not obtained approvals yet</div>
          <div class="mt-1 text-sm text-slate-600 dark:text-slate-400">
            Please obtain the necessary approvals before proceeding with this request.
          </div>
        </div>
      </label>
    </div>

    <!-- Error Message -->
    <div x-show="errors.has_required_approvals" class="mt-3 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/30 px-4 py-3 text-sm text-red-700 dark:text-red-300">
      <div class="flex items-start gap-2">
        <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
        <span x-text="errors.has_required_approvals"></span>
      </div>
    </div>
  </div>

  <!-- Info Box -->
  <div class="mt-4 rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/30 px-4 py-3 text-sm text-blue-800 dark:text-blue-300">
    <div class="flex items-start gap-2">
      <i class="fa-solid fa-circle-info mt-0.5"></i>
      <div>
        <strong>Why do we need this?</strong> Ensuring proper approvals helps maintain clear communication and accountability across all ministries.
      </div>
    </div>
  </div>

</div>
