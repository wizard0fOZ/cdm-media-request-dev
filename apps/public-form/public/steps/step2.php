<div class="rounded-xl bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">

  <!-- Step Title -->
  <div class="mb-6">
    <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-50">Event Details & Schedule</h2>
    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
      Tell us about your event and when it will take place.
    </p>
  </div>

  <!-- Form Fields -->
  <div class="space-y-6">

    <!-- Event Name -->
    <div>
      <label for="event_name" class="mb-1.5 block text-sm font-semibold text-slate-800 dark:text-slate-200">
        Event Name <span class="text-red-600 dark:text-red-400">*</span>
      </label>
      <input type="text"
             id="event_name"
             x-model="form.event_name"
             @input="errors.event_name = ''"
             class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-4 py-2.5 text-slate-900 dark:text-slate-50 placeholder:text-slate-400 dark:placeholder:text-slate-500 transition focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-900 dark:focus:ring-slate-100"
             :class="errors.event_name ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : ''"
             placeholder="e.g., Sunday Worship Service, Youth Rally 2026" />
      <div x-show="errors.event_name" class="mt-1.5 flex items-start gap-1.5 text-sm text-red-600 dark:text-red-400">
        <i class="fa-solid fa-circle-exclamation mt-0.5 text-xs"></i>
        <span x-text="errors.event_name"></span>
      </div>
    </div>

    <!-- Event Description -->
    <div>
      <label for="event_description" class="mb-1.5 block text-sm font-semibold text-slate-800 dark:text-slate-200">
        Event Description <span class="text-red-600 dark:text-red-400">*</span>
      </label>
      <textarea id="event_description"
                x-model="form.event_description"
                @input="errors.event_description = ''"
                rows="4"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-4 py-2.5 text-slate-900 dark:text-slate-50 placeholder:text-slate-400 dark:placeholder:text-slate-500 transition focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-900 dark:focus:ring-slate-100"
                :class="errors.event_description ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : ''"
                placeholder="Provide a brief description of the event, its purpose, and any special requirements..."></textarea>
      <div x-show="errors.event_description" class="mt-1.5 flex items-start gap-1.5 text-sm text-red-600 dark:text-red-400">
        <i class="fa-solid fa-circle-exclamation mt-0.5 text-xs"></i>
        <span x-text="errors.event_description"></span>
      </div>
    </div>

    <!-- Location Note -->
    <div>
      <label for="event_location_note" class="mb-1.5 block text-sm font-semibold text-slate-800 dark:text-slate-200">
        Location / Venue Notes <span class="text-slate-500 dark:text-slate-400 text-xs font-normal">(Optional)</span>
      </label>
      <textarea id="event_location_note"
                x-model="form.event_location_note"
                rows="2"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-4 py-2.5 text-slate-900 dark:text-slate-50 placeholder:text-slate-400 dark:placeholder:text-slate-500 transition focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-900 dark:focus:ring-slate-100"
                placeholder="e.g., Main Sanctuary, Fellowship Hall, outdoor venue details..."></textarea>
      <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
        Any additional information about the venue or location.
      </p>
    </div>

    <!-- Schedule Type -->
    <div class="rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 p-5">
      <label class="mb-3 block text-sm font-semibold text-slate-800 dark:text-slate-200">
        Schedule Type <span class="text-red-600 dark:text-red-400">*</span>
      </label>

      <div class="space-y-2.5">
        <!-- Recurring Event -->
        <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-4 py-3 transition hover:border-slate-400 dark:hover:border-slate-500 hover:bg-slate-50 dark:hover:bg-slate-700"
               :class="form.schedule_type === 'recurring' ? 'border-slate-900 dark:border-slate-100 bg-slate-100 dark:bg-slate-700 ring-2 ring-slate-900 dark:ring-slate-100' : ''">
          <input type="radio"
                 x-model="form.schedule_type"
                 value="recurring"
                 @change="errors.schedule_type = ''"
                 class="h-4 w-4 text-slate-900 dark:text-slate-100 focus:ring-slate-900 dark:focus:ring-slate-100" />
          <div>
            <div class="font-semibold text-slate-900 dark:text-slate-50">Recurring Event</div>
            <div class="text-sm text-slate-600 dark:text-slate-400">Regular event on a specific day of the week</div>
          </div>
        </label>

        <!-- Specific Dates -->
        <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-4 py-3 transition hover:border-slate-400 dark:hover:border-slate-500 hover:bg-slate-50 dark:hover:bg-slate-700"
               :class="form.schedule_type === 'custom_list' ? 'border-slate-900 dark:border-slate-100 bg-slate-100 dark:bg-slate-700 ring-2 ring-slate-900 dark:ring-slate-100' : ''">
          <input type="radio"
                 x-model="form.schedule_type"
                 value="custom_list"
                 @change="errors.schedule_type = ''"
                 class="h-4 w-4 text-slate-900 dark:text-slate-100 focus:ring-slate-900 dark:focus:ring-slate-100" />
          <div>
            <div class="font-semibold text-slate-900 dark:text-slate-50">Specific Dates</div>
            <div class="text-sm text-slate-600 dark:text-slate-400">One or more specific dates with individual times</div>
          </div>
        </label>
      </div>

      <!-- Error Message -->
      <div x-show="errors.schedule_type" class="mt-3 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/30 px-4 py-3 text-sm text-red-700 dark:text-red-400">
        <div class="flex items-start gap-2">
          <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
          <span x-text="errors.schedule_type"></span>
        </div>
      </div>
    </div>

    <!-- RECURRING EVENT FIELDS -->
    <div x-show="form.schedule_type === 'recurring'" x-cloak class="rounded-lg border border-purple-200 dark:border-purple-800 bg-purple-50 dark:bg-purple-900/30 p-5">
      <div class="mb-4 flex items-center gap-2 text-sm font-semibold text-purple-900 dark:text-purple-300">
        <i class="fa-solid fa-calendar-week"></i>
        <span>Recurring Event Schedule</span>
      </div>

      <div class="space-y-4">
        <!-- Date Range -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="recur_start_date" class="mb-1.5 block text-sm font-semibold text-slate-800 dark:text-slate-200">
              Start Date <span class="text-red-600 dark:text-red-400">*</span>
            </label>
            <input type="date"
                   id="recur_start_date"
                   x-model="form.recur_start_date"
                   @input="errors.recur_start_date = ''; recomputeLate()"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-4 py-2.5 text-slate-900 dark:text-slate-50 transition focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-900 dark:focus:ring-slate-100"
                   :class="errors.recur_start_date ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : ''" />
            <div x-show="errors.recur_start_date" class="mt-1.5 flex items-start gap-1.5 text-sm text-red-600 dark:text-red-400">
              <i class="fa-solid fa-circle-exclamation mt-0.5 text-xs"></i>
              <span x-text="errors.recur_start_date"></span>
            </div>
          </div>

          <div>
            <label for="recur_end_date" class="mb-1.5 block text-sm font-semibold text-slate-800 dark:text-slate-200">
              End Date <span class="text-red-600 dark:text-red-400">*</span>
            </label>
            <input type="date"
                   id="recur_end_date"
                   x-model="form.recur_end_date"
                   @input="errors.recur_end_date = ''"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-4 py-2.5 text-slate-900 dark:text-slate-50 transition focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-900 dark:focus:ring-slate-100"
                   :class="errors.recur_end_date ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : ''" />
            <div x-show="errors.recur_end_date" class="mt-1.5 flex items-start gap-1.5 text-sm text-red-600 dark:text-red-400">
              <i class="fa-solid fa-circle-exclamation mt-0.5 text-xs"></i>
              <span x-text="errors.recur_end_date"></span>
            </div>
          </div>
        </div>

        <!-- Recurrence Pattern -->
        <div>
          <label for="recur_pattern" class="mb-1.5 block text-sm font-semibold text-slate-800 dark:text-slate-200">
            Recurrence Pattern <span class="text-red-600 dark:text-red-400">*</span>
          </label>
          <select id="recur_pattern"
                  x-model="form.recur_pattern"
                  @change="errors.recur_pattern = ''"
                  class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-4 py-2.5 text-slate-900 dark:text-slate-50 transition focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-900 dark:focus:ring-slate-100"
                  :class="errors.recur_pattern ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : ''">
            <option value="">Select pattern...</option>
            <option value="weekly">Weekly</option>
            <option value="biweekly">Bi-weekly (Every 2 weeks)</option>
            <option value="monthly">Monthly</option>
          </select>
          <div x-show="errors.recur_pattern" class="mt-1.5 flex items-start gap-1.5 text-sm text-red-600 dark:text-red-400">
            <i class="fa-solid fa-circle-exclamation mt-0.5 text-xs"></i>
            <span x-text="errors.recur_pattern"></span>
          </div>
          <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
            Choose how often the event repeats between the start and end dates.
          </p>
        </div>

        <!-- Day of Week -->
        <div>
          <label class="mb-2 block text-sm font-semibold text-slate-800 dark:text-slate-200">
            Repeat On <span class="text-red-600 dark:text-red-400">*</span>
          </label>
          <div class="grid grid-cols-4 gap-2 sm:grid-cols-7">
            <template x-for="dayObj in [
              {name: 'Sunday', code: 'Sun'},
              {name: 'Monday', code: 'Mon'},
              {name: 'Tuesday', code: 'Tue'},
              {name: 'Wednesday', code: 'Wed'},
              {name: 'Thursday', code: 'Thu'},
              {name: 'Friday', code: 'Fri'},
              {name: 'Saturday', code: 'Sat'}
            ]" :key="dayObj.code">
              <label class="flex cursor-pointer items-center justify-center rounded-lg border px-3 py-2 text-sm font-medium transition"
                     :class="form.recur_day === dayObj.code ? 'border-slate-900 dark:border-slate-100 bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900' : 'border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:border-slate-400 dark:hover:border-slate-500'">
                <input type="radio"
                       :value="dayObj.code"
                       x-model="form.recur_day"
                       @change="errors.recur_day = ''"
                       class="sr-only" />
                <span x-text="dayObj.name.substring(0, 3)"></span>
              </label>
            </template>
          </div>
          <div x-show="errors.recur_day" class="mt-1.5 flex items-start gap-1.5 text-sm text-red-600 dark:text-red-400">
            <i class="fa-solid fa-circle-exclamation mt-0.5 text-xs"></i>
            <span x-text="errors.recur_day"></span>
          </div>
          <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
            Select the day of the week when this recurring event takes place.
          </p>
        </div>

        <!-- Time Range -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="recur_start_time" class="mb-1.5 block text-sm font-semibold text-slate-800 dark:text-slate-200">
              Start Time <span class="text-slate-500 dark:text-slate-400 text-xs font-normal">(Optional)</span>
            </label>
            <input type="time"
                   id="recur_start_time"
                   x-model="form.recur_start_time"
                   @input="errors.recur_start_time = ''"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-4 py-2.5 text-slate-900 dark:text-slate-50 transition focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-900 dark:focus:ring-slate-100"
                   :class="errors.recur_start_time ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : ''" />
            <div x-show="errors.recur_start_time" class="mt-1.5 flex items-start gap-1.5 text-sm text-red-600 dark:text-red-400">
              <i class="fa-solid fa-circle-exclamation mt-0.5 text-xs"></i>
              <span x-text="errors.recur_start_time"></span>
            </div>
          </div>

          <div>
            <label for="recur_end_time" class="mb-1.5 block text-sm font-semibold text-slate-800 dark:text-slate-200">
              End Time <span class="text-slate-500 dark:text-slate-400 text-xs font-normal">(Optional)</span>
            </label>
            <input type="time"
                   id="recur_end_time"
                   x-model="form.recur_end_time"
                   @input="errors.recur_end_time = ''"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-4 py-2.5 text-slate-900 dark:text-slate-50 transition focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-900 dark:focus:ring-slate-100"
                   :class="errors.recur_end_time ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : ''" />
            <div x-show="errors.recur_end_time" class="mt-1.5 flex items-start gap-1.5 text-sm text-red-600 dark:text-red-400">
              <i class="fa-solid fa-circle-exclamation mt-0.5 text-xs"></i>
              <span x-text="errors.recur_end_time"></span>
            </div>
          </div>
        </div>
        <p class="text-xs text-slate-500 dark:text-slate-400">
          Leave times empty for all-day events or events without specific time constraints.
        </p>
      </div>
    </div>

    <!-- CUSTOM DATE LIST FIELDS -->
    <div x-show="form.schedule_type === 'custom_list'" x-cloak class="rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/30 p-5">
      <div class="mb-4 flex items-center justify-between">
        <div class="flex items-center gap-2 text-sm font-semibold text-green-900 dark:text-green-300">
          <i class="fa-solid fa-calendar-days"></i>
          <span>Event Dates</span>
        </div>
        <button type="button"
                @click="form.custom_occurrences.push({ date: '', start_time: '', end_time: '' })"
                class="rounded-lg bg-green-700 dark:bg-green-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-green-800 dark:hover:bg-green-700">
          <i class="fa-solid fa-plus mr-1"></i> Add Date
        </button>
      </div>

      <!-- Custom Dates List -->
      <div class="space-y-3">
        <template x-for="(customDate, index) in form.custom_occurrences" :key="index">
          <div class="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 p-4">
            <div class="mb-3 flex items-center justify-between">
              <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Date <span x-text="index + 1"></span></span>
              <button type="button"
                      @click="form.custom_occurrences.splice(index, 1); errors.custom_list = ''"
                      x-show="form.custom_occurrences.length > 1"
                      class="text-red-600 dark:text-red-400 transition hover:text-red-700 dark:hover:text-red-300">
                <i class="fa-solid fa-trash text-sm"></i>
              </button>
            </div>

            <div class="space-y-3">
              <!-- Date -->
              <div>
                <label :for="'custom_date_' + index" class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">
                  Date <span class="text-red-600 dark:text-red-400">*</span>
                </label>
                <input type="date"
                       :id="'custom_date_' + index"
                       x-model="customDate.date"
                       @input="errors.custom_list = ''; if (index === 0) recomputeLate()"
                       class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-50 transition focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-900 dark:focus:ring-slate-100" />
              </div>

              <!-- Time Range -->
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label :for="'custom_start_time_' + index" class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">
                    Start Time <span class="text-slate-500 dark:text-slate-400 font-normal">(Optional)</span>
                  </label>
                  <input type="time"
                         :id="'custom_start_time_' + index"
                         x-model="customDate.start_time"
                         @input="errors.custom_list = ''"
                         class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-50 transition focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-900 dark:focus:ring-slate-100" />
                </div>

                <div>
                  <label :for="'custom_end_time_' + index" class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">
                    End Time <span class="text-slate-500 dark:text-slate-400 font-normal">(Optional)</span>
                  </label>
                  <input type="time"
                         :id="'custom_end_time_' + index"
                         x-model="customDate.end_time"
                         @input="errors.custom_list = ''"
                         class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-50 transition focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-900 dark:focus:ring-slate-100" />
                </div>
              </div>
            </div>
          </div>
        </template>

        <!-- Empty State -->
        <div x-show="form.custom_occurrences.length === 0" class="rounded-lg border-2 border-dashed border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 p-6 text-center">
          <i class="fa-solid fa-calendar-plus mb-2 text-2xl text-slate-400 dark:text-slate-500"></i>
          <p class="text-sm text-slate-600 dark:text-slate-400">No dates added yet. Click "Add Date" to get started.</p>
        </div>

        <!-- Error Message -->
        <div x-show="errors.custom_list" class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/30 px-4 py-3 text-sm text-red-700 dark:text-red-400">
          <div class="flex items-start gap-2">
            <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
            <span x-text="errors.custom_list"></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Late Submission Warning -->
    <div x-show="isLate" x-cloak class="rounded-lg border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/30 px-4 py-3 text-sm text-amber-900 dark:text-amber-300">
      <div class="flex items-start gap-2">
        <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
        <div>
          <strong>Late Submission Notice:</strong> Your event is scheduled within
          <span x-text="leadDays"></span> days. Requests should be submitted at least 14 days in advance.
          We'll do our best to accommodate, but resources may be limited.
        </div>
      </div>
    </div>

  </div>

</div>
