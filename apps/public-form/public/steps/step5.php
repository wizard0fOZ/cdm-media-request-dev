<!-- Step 5: Review & Confirm -->
<div class="space-y-6">

  <!-- Header -->
  <div class="rounded-xl bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/30 dark:to-teal-900/30 p-6 ring-1 ring-emerald-200 dark:ring-emerald-700">
    <div class="flex items-start gap-4">
      <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400">
        <i class="fa-solid fa-clipboard-check text-xl"></i>
      </div>
      <div>
        <h2 class="text-lg font-bold text-slate-900 dark:text-slate-50">Review & Confirm</h2>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
          Please review all the information below before submitting your request
        </p>
      </div>
    </div>
  </div>

  <!-- Late Submission Warning -->
  <div x-show="isLate" class="rounded-xl border-2 border-amber-400 dark:border-amber-600 bg-amber-50 dark:bg-amber-900/30 p-5">
    <div class="flex items-start gap-3">
      <i class="fa-solid fa-triangle-exclamation mt-0.5 text-xl text-amber-600 dark:text-amber-400"></i>
      <div>
        <p class="font-semibold text-amber-800 dark:text-amber-300">Late Submission Notice</p>
        <p class="mt-1 text-sm text-amber-700 dark:text-amber-400">
          This request is being submitted less than 2 weeks before the event date.
          While we'll do our best to accommodate, some services may have limited availability.
        </p>
      </div>
    </div>
  </div>

  <!-- Event Information -->
  <div class="rounded-xl bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
    <h3 class="mb-4 flex items-center gap-2 text-base font-bold text-slate-900 dark:text-slate-50">
      <i class="fa-solid fa-calendar-day text-slate-400 dark:text-slate-500"></i>
      Event Information
    </h3>
    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Event Name</div>
        <div class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100" x-text="form.event_name || '-'"></div>
      </div>
      <div>
        <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Ministry / Organization</div>
        <div class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100" x-text="form.ministry || '-'"></div>
      </div>
      <div>
        <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Requester Name</div>
        <div class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100" x-text="form.requestor_name || '-'"></div>
      </div>
      <div>
        <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Contact</div>
        <div class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100">
          <span x-text="form.email || '-'"></span>
          <span x-show="form.contact_no" class="text-slate-500 dark:text-slate-400"> | </span>
          <span x-text="form.contact_no"></span>
        </div>
      </div>
    </div>
  </div>

  <!-- Schedule -->
  <div class="rounded-xl bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
    <h3 class="mb-4 flex items-center gap-2 text-base font-bold text-slate-900 dark:text-slate-50">
      <i class="fa-solid fa-clock text-slate-400 dark:text-slate-500"></i>
      Schedule
    </h3>

    <!-- Schedule Type & Details -->
    <div class="space-y-3">
      <template x-if="form.schedule_type === 'recurring'">
        <div class="rounded-lg bg-slate-50 dark:bg-slate-900 p-4">
          <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Recurring Event</div>
          <div class="mt-2 grid gap-3 sm:grid-cols-2">
            <div>
              <span class="text-xs text-slate-500 dark:text-slate-400">Pattern:</span>
              <span class="ml-1 text-sm font-medium text-slate-900 dark:text-slate-100" x-text="form.recur_pattern || '-'"></span>
            </div>
            <div>
              <span class="text-xs text-slate-500 dark:text-slate-400">Day:</span>
              <span class="ml-1 text-sm font-medium text-slate-900 dark:text-slate-100" x-text="form.recur_day || '-'"></span>
            </div>
            <div>
              <span class="text-xs text-slate-500 dark:text-slate-400">Date Range:</span>
              <span class="ml-1 text-sm font-medium text-slate-900 dark:text-slate-100" x-text="(form.recur_start_date || '-') + ' to ' + (form.recur_end_date || '-')"></span>
            </div>
            <div>
              <span class="text-xs text-slate-500 dark:text-slate-400">Time:</span>
              <span class="ml-1 text-sm font-medium text-slate-900 dark:text-slate-100" x-text="(form.recur_start_time || '-') + ' - ' + (form.recur_end_time || '-')"></span>
            </div>
          </div>
        </div>
      </template>

      <template x-if="form.schedule_type === 'custom_list'">
        <div class="rounded-lg bg-slate-50 dark:bg-slate-900 p-4">
          <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Specific Dates</div>
          <div class="mt-2 space-y-2">
            <template x-for="(occ, idx) in form.custom_occurrences.filter(o => o.date)" :key="idx">
              <div class="flex items-center gap-2 text-sm">
                <i class="fa-solid fa-circle text-[6px] text-slate-400 dark:text-slate-500"></i>
                <span class="font-medium text-slate-900 dark:text-slate-100" x-text="occ.date"></span>
                <span class="text-slate-500 dark:text-slate-400" x-text="(occ.start_time || '-') + ' - ' + (occ.end_time || '-')"></span>
              </div>
            </template>
          </div>
        </div>
      </template>

      <!-- Rehearsal -->
      <div x-show="form.has_rehearsal === 'yes'" class="rounded-lg bg-blue-50 dark:bg-blue-900/30 p-4">
        <div class="text-xs font-medium uppercase tracking-wide text-blue-600 dark:text-blue-400">Rehearsal</div>
        <div class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100" x-text="rehearsalSummary()"></div>
      </div>
    </div>
  </div>

  <!-- Services -->
  <div class="rounded-xl bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
    <h3 class="mb-4 flex items-center gap-2 text-base font-bold text-slate-900 dark:text-slate-50">
      <i class="fa-solid fa-cogs text-slate-400 dark:text-slate-500"></i>
      Services Requested
    </h3>

    <div class="space-y-4">
      <!-- AV Support -->
      <div x-show="hasService('av')" class="rounded-lg border border-purple-200 dark:border-purple-800 bg-purple-50 dark:bg-purple-900/30 p-4">
        <div class="flex items-center gap-2">
          <i class="fa-solid fa-headphones text-purple-600 dark:text-purple-400"></i>
          <span class="font-semibold text-purple-900 dark:text-purple-300">AV Support</span>
        </div>
        <div class="mt-3 space-y-2 text-sm">
          <div>
            <span class="text-purple-700 dark:text-purple-400">Rooms:</span>
            <span class="ml-1 font-medium text-slate-900 dark:text-slate-100" x-text="selectedRoomNames() || '-'"></span>
          </div>
          <div x-show="avItemsSummary()">
            <span class="text-purple-700 dark:text-purple-400">Equipment:</span>
            <span class="ml-1 font-medium text-slate-900 dark:text-slate-100" x-text="avItemsSummary()"></span>
          </div>
          <div x-show="form.av_note">
            <span class="text-purple-700 dark:text-purple-400">Notes:</span>
            <span class="ml-1 text-slate-700 dark:text-slate-300" x-text="form.av_note"></span>
          </div>
        </div>
      </div>

      <!-- Media -->
      <div x-show="hasService('media')" class="rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/30 p-4">
        <div class="flex items-center gap-2">
          <i class="fa-solid fa-photo-film text-green-600 dark:text-green-400"></i>
          <span class="font-semibold text-green-900 dark:text-green-300">Poster / Video Design</span>
        </div>
        <div class="mt-3 space-y-2 text-sm">
          <div x-show="form.media_description">
            <span class="text-green-700 dark:text-green-400">Description:</span>
            <span class="ml-1 text-slate-700 dark:text-slate-300" x-text="form.media_description"></span>
          </div>
          <div>
            <span class="text-green-700 dark:text-green-400">Platforms:</span>
            <span class="ml-1 font-medium text-slate-900 dark:text-slate-100" x-text="mediaPlatformsSummary()"></span>
          </div>
          <div x-show="form.promo_start_date || form.promo_end_date">
            <span class="text-green-700 dark:text-green-400">Promotion Period:</span>
            <span class="ml-1 font-medium text-slate-900 dark:text-slate-100" x-text="promoSummary()"></span>
          </div>
          <div x-show="form.caption_details">
            <span class="text-green-700 dark:text-green-400">Caption/Details:</span>
            <span class="ml-1 text-slate-700 dark:text-slate-300" x-text="form.caption_details"></span>
          </div>
          <div x-show="form.media_note">
            <span class="text-green-700 dark:text-green-400">Notes:</span>
            <span class="ml-1 text-slate-700 dark:text-slate-300" x-text="form.media_note"></span>
          </div>
        </div>
      </div>

      <!-- Photography -->
      <div x-show="hasService('photo')" class="rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/30 p-4">
        <div class="flex items-center gap-2">
          <i class="fa-solid fa-camera text-blue-600 dark:text-blue-400"></i>
          <span class="font-semibold text-blue-900 dark:text-blue-300">Photography</span>
        </div>
        <div class="mt-3 text-sm">
          <span class="text-blue-700 dark:text-blue-400">Schedule:</span>
          <span class="ml-1 font-medium text-slate-900 dark:text-slate-100">Same as event schedule</span>
          <div x-show="form.photo_note" class="mt-2">
            <span class="text-blue-700 dark:text-blue-400">Notes:</span>
            <span class="ml-1 text-slate-700 dark:text-slate-300" x-text="form.photo_note"></span>
          </div>
        </div>
      </div>

      <!-- No services selected -->
      <div x-show="!hasService('av') && !hasService('media') && !hasService('photo')"
           class="rounded-lg border-2 border-dashed border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 p-4 text-center text-sm text-slate-600 dark:text-slate-400">
        No services selected
      </div>
    </div>
  </div>

  <!-- References -->
  <div x-show="form.reference_url || form.reference_note" class="rounded-xl bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
    <h3 class="mb-4 flex items-center gap-2 text-base font-bold text-slate-900 dark:text-slate-50">
      <i class="fa-solid fa-link text-slate-400 dark:text-slate-500"></i>
      References
    </h3>
    <div class="space-y-2 text-sm">
      <div x-show="form.reference_url">
        <span class="text-slate-500 dark:text-slate-400">Link:</span>
        <a :href="form.reference_url" target="_blank" class="ml-1 font-medium text-blue-600 dark:text-blue-400 hover:underline" x-text="form.reference_url"></a>
      </div>
      <div x-show="form.reference_note">
        <span class="text-slate-500 dark:text-slate-400">Notes:</span>
        <span class="ml-1 text-slate-700 dark:text-slate-300" x-text="form.reference_note"></span>
      </div>
    </div>
  </div>

  <!-- Confirmation -->
  <div class="rounded-xl border-2 bg-white dark:bg-slate-800 p-6 shadow-sm"
       :class="errors.confirmed ? 'border-red-400 dark:border-red-500 ring-2 ring-red-100 dark:ring-red-900/30' : 'border-slate-200 dark:border-slate-700'">
    <label class="flex cursor-pointer items-start gap-4">
      <input type="checkbox"
             x-model="form.confirmed"
             @change="errors.confirmed = ''"
             class="mt-1 h-5 w-5 rounded border-slate-300 dark:border-slate-600 text-emerald-600 focus:ring-emerald-600" />
      <div>
        <div class="font-semibold text-slate-900 dark:text-slate-50">I confirm that the information above is correct</div>
        <div class="mt-1 text-sm text-slate-600 dark:text-slate-400">
          By checking this box, I acknowledge that I have reviewed all the details and am ready to submit this request.
        </div>
      </div>
    </label>
    <div x-show="errors.confirmed" class="mt-3 flex items-center gap-1.5 text-sm text-red-600 dark:text-red-400">
      <i class="fa-solid fa-circle-exclamation text-xs"></i>
      <span x-text="errors.confirmed"></span>
    </div>
  </div>

</div>
