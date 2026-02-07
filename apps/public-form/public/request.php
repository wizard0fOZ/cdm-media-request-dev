<?php
declare(strict_types=1);

session_start();
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pageTitle = "Submit Request | CDM";
$activePage = "request";
include __DIR__ . "/partials/header.php";
?>

<main class="bg-slate-50 dark:bg-slate-900">
  <div class="mx-auto max-w-5xl px-4 py-10" x-data="mrForm()" x-init="init()">

    <!-- Page Title -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-50">Submit a Request</h1>
      <p class="mt-1 text-slate-600 dark:text-slate-400 text-sm">
        Please complete the form carefully.
      </p>
    </div>

    <!-- Global alerts -->
    <template x-if="banner.message">
      <div class="mb-6 rounded-lg border px-4 py-3 text-sm"
           :class="banner.type === 'error' ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/50 dark:text-red-300' : 'border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-900/50 dark:text-green-300'">
        <div class="flex items-start gap-2">
          <i class="fa-solid" :class="banner.type === 'error' ? 'fa-triangle-exclamation' : 'fa-circle-check'"></i>
          <div x-text="banner.message"></div>
        </div>
      </div>
    </template>

    <!-- Debug box (toggle) -->
    <div class="mb-6 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4 text-sm" x-show="debug.enabled">
      <div class="flex items-center justify-between">
        <div class="font-semibold text-slate-800 dark:text-slate-200">Debug</div>
        <button type="button"
                class="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-1.5 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-600"
                @click="debug.enabled=false">
          Hide
        </button>
      </div>

      <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div class="rounded-lg bg-slate-50 dark:bg-slate-900 p-3">
          <div class="text-xs text-slate-500 dark:text-slate-400">Lookups URL</div>
          <div class="mt-1 font-mono text-xs break-all text-slate-700 dark:text-slate-300" x-text="debug.lookupsUrl"></div>
        </div>

        <div class="rounded-lg bg-slate-50 dark:bg-slate-900 p-3">
          <div class="text-xs text-slate-500 dark:text-slate-400">Rooms loaded</div>
          <div class="mt-1 font-semibold text-slate-800 dark:text-slate-200">
            <span x-text="lookups.rooms.length"></span>
          </div>
          <div class="mt-1 text-xs text-slate-500 dark:text-slate-400" x-show="debug.lastStatus">
            HTTP: <span class="font-mono" x-text="debug.lastStatus"></span>
          </div>
        </div>

        <div class="rounded-lg bg-slate-50 dark:bg-slate-900 p-3 sm:col-span-2" x-show="debug.lastError">
          <div class="text-xs text-slate-500 dark:text-slate-400">Last error</div>
          <div class="mt-1 font-mono text-xs text-red-700 dark:text-red-400 break-all" x-text="debug.lastError"></div>
        </div>

        <div class="rounded-lg bg-slate-50 dark:bg-slate-900 p-3 sm:col-span-2" x-show="debug.lastJson">
          <div class="text-xs text-slate-500 dark:text-slate-400">Last response JSON (truncated)</div>
          <pre class="mt-1 max-h-48 overflow-auto rounded-lg bg-white dark:bg-slate-800 p-3 text-xs text-slate-700 dark:text-slate-300"
               x-text="debug.lastJson"></pre>
        </div>
      </div>
    </div>

    <!-- Progress Bar -->
    <div class="mb-8 rounded-xl bg-white dark:bg-slate-800 p-4 sm:p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
      <div class="flex items-center justify-between mb-4">
        <div class="text-sm font-medium text-slate-600 dark:text-slate-400">
          Step <span class="text-slate-900 dark:text-slate-100" x-text="step + 1"></span> of 5
        </div>
        <!-- Start Fresh button -->
        <button type="button"
                x-show="hasSavedDraft"
                @click="if(confirm('Clear all saved data and start a new request?')) { clearStorage(); location.reload(); }"
                class="text-xs text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 underline">
          Start Fresh
        </button>
      </div>

      <!-- Progress bar track -->
      <div class="relative">
        <!-- Background line -->
        <div class="absolute top-4 left-0 right-0 h-0.5 bg-slate-200 dark:bg-slate-700"></div>
        <!-- Progress line (filled) -->
        <div class="absolute top-4 left-0 h-0.5 bg-slate-900 dark:bg-slate-100 transition-all duration-300"
             :style="'width: ' + (step / 4 * 100) + '%'"></div>

        <!-- Step circles -->
        <div class="relative flex justify-between">
          <template x-for="(s, idx) in steps" :key="idx">
            <div class="flex flex-col items-center">
              <!-- Circle -->
              <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold transition-colors"
                   :class="idx < step ? 'bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900' :
                           (idx === step ? 'bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 ring-4 ring-slate-200 dark:ring-slate-700' :
                           'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400')">
                <template x-if="idx < step">
                  <i class="fa-solid fa-check text-xs"></i>
                </template>
                <template x-if="idx >= step">
                  <span x-text="idx + 1"></span>
                </template>
              </div>
              <!-- Label (hidden on mobile, visible on sm+) -->
              <div class="hidden sm:block mt-2 text-xs text-center max-w-[80px]"
                   :class="idx <= step ? 'text-slate-900 dark:text-slate-100 font-medium' : 'text-slate-500 dark:text-slate-400'"
                   x-text="s"></div>
            </div>
          </template>
        </div>
      </div>

      <!-- Mobile: Current step label -->
      <div class="sm:hidden mt-3 text-center text-sm font-medium text-slate-900 dark:text-slate-100" x-text="steps[step]"></div>

      <!-- Late flag preview -->
      <template x-if="step >= 2 && computed.isLate !== null">
        <div class="mt-4 rounded-lg border px-4 py-3 text-sm"
             :class="computed.isLate ? 'border-amber-200 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300' : 'border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-700 dark:text-slate-300'">
          <div class="flex items-start gap-2">
            <i class="fa-solid" :class="computed.isLate ? 'fa-clock' : 'fa-circle-info'"></i>
            <div>
              <div class="font-semibold">
                <span x-text="computed.isLate ? 'Late submission' : 'Lead time check'"></span>
              </div>
              <div>
                <span x-text="computed.isLate ? 'This request is within 14 days of the event. It will be flagged for internal review.' : 'This request is not late based on the event start date.'"></span>
                <span class="ml-1 text-xs text-slate-500 dark:text-slate-400" x-text="computed.leadDays !== null ? '(Lead days: ' + computed.leadDays + ')' : ''"></span>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- FORM -->
    <form @submit.prevent="submit()" class="space-y-6">

      <!-- Honeypot -->
      <input type="text" x-model="form.hp" class="hidden" tabindex="-1" autocomplete="off" />

      <!-- Steps (partials) -->
      <section x-show="step === 0">
        <?php include __DIR__ . "/steps/step0.php"; ?>
      </section>

      <section x-show="step === 1">
        <?php include __DIR__ . "/steps/step1.php"; ?>
      </section>

      <section x-show="step === 2">
        <?php include __DIR__ . "/steps/step2.php"; ?>
      </section>

      <section x-show="step === 3">
        <?php include __DIR__ . "/steps/step3.php"; ?>
      </section>

      <section x-show="step === 4">
        <?php include __DIR__ . "/steps/step5.php"; ?>
      </section>

      <!-- Navigation -->
      <div class="flex items-center justify-between">
        <button type="button"
                class="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors"
                @click="prevStep()"
                :disabled="step === 0 || isSubmitting"
                :class="(step === 0 || isSubmitting) ? 'opacity-50 cursor-not-allowed' : ''">
          Back
        </button>

        <div class="flex items-center gap-2">
          <button type="button"
                  class="rounded-lg bg-slate-900 dark:bg-slate-100 px-4 py-2 text-sm font-semibold text-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-slate-200 transition-colors"
                  x-show="step < 4"
                  @click="nextStep()"
                  :disabled="isSubmitting"
                  :class="isSubmitting ? 'opacity-50 cursor-not-allowed' : ''">
            Next
          </button>

          <button type="submit"
                  class="rounded-lg bg-slate-900 dark:bg-slate-100 px-5 py-2.5 text-sm font-semibold text-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-slate-200 transition-colors"
                  x-show="step === 4"
                  :disabled="isSubmitting"
                  :class="isSubmitting ? 'opacity-50 cursor-not-allowed' : ''">
            <span x-show="!isSubmitting">Submit Request</span>
            <span x-show="isSubmitting"><i class="fa-solid fa-spinner fa-spin"></i> Submitting...</span>
          </button>
        </div>
      </div>

    </form>
  </div>
</main>

<script>
function mrForm() {
  const STORAGE_KEY = 'cdm_media_request_draft';

  return {
    step: 0,
    isSubmitting: false,
    hasSavedDraft: false,

    steps: ['Approvals','Requestor','Event','Services','Review'],

    banner: { type: '', message: '' },

    debug: {
      enabled: false,
      lookupsUrl: '',
      lastStatus: '',
      lastError: '',
      lastJson: ''
    },

    lookups: {
      rooms: [],
      equipmentByRoom: {}
    },

    platformOptions: [
      { value: 'bulletin', label: 'Bulletin announcement', description: 'Printed bulletin announcement' },
      { value: 'before_mass', label: 'In Church', description: 'Announcement slide before Mass & during announcements' },
      { value: 'social_media', label: 'Social Media', description: 'Facebook, Instagram, Telegram' }
    ],

    form: {
      hp: '',
      csrf: <?php echo json_encode($_SESSION['csrf_token']); ?>,

      // Step 0
      has_required_approvals: '',

      // Step 1
      requestor_name: '',
      ministry: '',
      contact_no: '',
      email: '',

      // Step 2
      event_name: '',
      event_description: '',
      event_location_note: '',
      schedule_type: 'custom_list',

      // single
      single_date: '',
      single_start_time: '',
      single_end_time: '',

      // recurring
      recur_start_date: '',
      recur_end_date: '',
      recur_pattern: '',
      recur_day: '',
      recur_start_time: '',
      recur_end_time: '',

      // custom list
      custom_occurrences: [{ date: '', start_time: '', end_time: '' }],

      schedule_notes: '',

      // Step 3
      services: [],
      av_room_ids: [],
      av_items: [], // {room_id, equipment_id, quantity, note}
      rehearsal_date: '',
      rehearsal_start_time: '',
      rehearsal_end_time: '',
      av_note: '',

      media_description: '',
      media_platforms: [],
      media_platform_other_label: '',
      promo_start_date: '',
      promo_end_date: '',
      caption_details: '',
      media_note: '',

      photo_note: '',

      // Step 4
      reference_url: '',
      reference_note: '',

      // Step 5
      confirmed: false
    },

    computed: {
      isLate: null,
      leadDays: null,
      eventStartDate: null
    },

    errors: {},

    async init() {
      // Build absolute-ish URL for debugging (helps catch path issues)
      const base = window.location.pathname.split('/').slice(0, -1).join('/');
      this.debug.lookupsUrl = `${window.location.origin}${base}/api/lookups.php`;

      // Restore saved draft if exists
      this.loadFromStorage();

      await this.loadLookups();
      this.recomputeLate();

      // Auto-save form changes (debounced)
      this.$watch('form', () => this.saveToStorage(), { deep: true });
      this.$watch('step', () => this.saveToStorage());

      // Log once
      console.log('[MR] init', {
        lookupsUrl: this.debug.lookupsUrl,
        rooms: this.lookups.rooms.length,
        restoredDraft: !!localStorage.getItem(STORAGE_KEY)
      });
    },

    // LocalStorage persistence
    saveToStorage() {
      try {
        const data = {
          step: this.step,
          form: { ...this.form, csrf: undefined, hp: undefined } // Don't store CSRF or honeypot
        };
        localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
        this.hasSavedDraft = true;
      } catch (e) {
        console.warn('[MR] Could not save to localStorage', e);
      }
    },

    loadFromStorage() {
      try {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (!saved) return;

        const data = JSON.parse(saved);
        if (data.step != null) this.step = data.step;
        if (data.form) {
          // Preserve CSRF token from server, merge rest
          const csrf = this.form.csrf;
          Object.assign(this.form, data.form);
          this.form.csrf = csrf;
          this.form.hp = ''; // Reset honeypot
        }

        this.hasSavedDraft = true;
        console.log('[MR] Restored draft from localStorage');
      } catch (e) {
        console.warn('[MR] Could not load from localStorage', e);
        localStorage.removeItem(STORAGE_KEY);
      }
    },

    clearStorage() {
      try {
        localStorage.removeItem(STORAGE_KEY);
        this.hasSavedDraft = false;
        console.log('[MR] Cleared draft from localStorage');
      } catch (e) {
        console.warn('[MR] Could not clear localStorage', e);
      }
    },

    async loadLookups() {
      this.debug.lastError = '';
      this.debug.lastStatus = '';
      this.debug.lastJson = '';

      // Use RELATIVE path for actual fetch (most reliable if hosted in subfolder)
      const url = './api/lookups.php';

      try {
        console.log('[MR] loading lookups from', url);

        const res = await fetch(url, {
          method: 'GET',
          credentials: 'same-origin',
          headers: { 'Accept': 'application/json' }
        });

        this.debug.lastStatus = String(res.status);

        const text = await res.text(); // read raw first so we can debug invalid JSON
        this.debug.lastJson = text.slice(0, 2000);

        let data;
        try {
          data = JSON.parse(text);
        } catch (jsonErr) {
          throw new Error('Lookups response is not valid JSON. Check PHP errors or output. Raw: ' + text.slice(0, 200));
        }

        if (!data.ok) {
          throw new Error(data.error || 'Lookup failed');
        }

        this.lookups.rooms = Array.isArray(data.rooms) ? data.rooms : [];
        this.lookups.equipmentByRoom = data.equipmentByRoom || {};

        console.log('[MR] lookups loaded', {
          rooms: this.lookups.rooms.length,
          equipmentRooms: Object.keys(this.lookups.equipmentByRoom || {}).length
        });

      } catch (e) {
        console.error('[MR] lookups error', e);
        this.debug.lastError = (e && e.message) ? e.message : String(e);

        this.banner = {
          type: 'error',
          message: 'Unable to load rooms/equipment. Open Debug box to check lookups URL, HTTP status, and response.'
        };
      }
    },

    // Navigation
    prevStep() {
      this.banner = { type: '', message: '' };
      if (this.step > 0) {
        this.step--;
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    },

    nextStep() {
      this.banner = { type: '', message: '' };
      this.errors = {};

      if (!this.validateStep(this.step)) {
        this.banner = { type: 'error', message: 'Please fix the highlighted fields before continuing.' };
        return;
      }
      if (this.step < 4) {
        this.step++;
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    },

    // Helpers
    hasService(type) {
      return this.form.services.includes(type);
    },

    onScheduleTypeChange() {
      this.errors = {};
      this.recomputeLate();
    },

    onServicesChange() {
      this.errors.services = '';

      if (!this.hasService('av')) {
        this.form.av_room_ids = [];
        this.form.av_items = [];
        this.form.rehearsal_date = '';
        this.form.rehearsal_start_time = '';
        this.form.rehearsal_end_time = '';
        this.form.av_note = '';
      }

      if (!this.hasService('media')) {
        this.form.media_description = '';
        this.form.media_platforms = [];
        this.form.media_platform_other_label = '';
        this.form.promo_start_date = '';
        this.form.promo_end_date = '';
        this.form.caption_details = '';
        this.form.media_note = '';
      }

      if (!this.hasService('photo')) {
        this.form.photo_note = '';
      }
    },

    // Custom occurrence controls
    addOccurrence() {
      this.form.custom_occurrences.push({ date: '', start_time: '', end_time: '' });
    },

    removeOccurrence(i) {
      this.form.custom_occurrences.splice(i, 1);
      if (this.form.custom_occurrences.length === 0) {
        this.form.custom_occurrences.push({ date: '', start_time: '', end_time: '' });
      }
      this.recomputeLate();
    },

    // AV helpers
    roomName(roomId) {
      const r = this.lookups.rooms.find(x => String(x.id) === String(roomId));
      return r ? r.name : 'Room';
    },

    roomEquipment(roomId) {
      return (this.lookups.equipmentByRoom[String(roomId)] || []);
    },

    syncAvItems() {
      const roomSet = new Set(this.form.av_room_ids.map(String));
      this.form.av_items = this.form.av_items.filter(it => roomSet.has(String(it.room_id)));
    },

    findAvItem(roomId, equipmentId) {
      return this.form.av_items.find(it =>
        String(it.room_id) === String(roomId) &&
        String(it.equipment_id) === String(equipmentId)
      );
    },

    getAvQty(roomId, equipmentId) {
      const it = this.findAvItem(roomId, equipmentId);
      return it ? it.quantity : 0;
    },

    setAvQty(roomId, equipmentId, value) {
      const qty = Math.max(0, parseInt(value || '0', 10));
      const existing = this.findAvItem(roomId, equipmentId);

      if (qty === 0) {
        if (existing) {
          this.form.av_items = this.form.av_items.filter(x =>
            !(String(x.room_id) === String(roomId) && String(x.equipment_id) === String(equipmentId))
          );
        }
        return;
      }

      if (!existing) {
        this.form.av_items.push({
          room_id: Number(roomId),
          equipment_id: Number(equipmentId),
          quantity: qty,
          note: ''
        });
      } else {
        existing.quantity = qty;
      }
    },

    getAvNote(roomId, equipmentId) {
      const it = this.findAvItem(roomId, equipmentId);
      return it ? (it.note || '') : '';
    },

    setAvNote(roomId, equipmentId, note) {
      const it = this.findAvItem(roomId, equipmentId);
      if (it) it.note = String(note || '');
    },

    // Equipment selection helpers
    isEquipmentSelected(roomId, equipmentId) {
      return !!this.findAvItem(roomId, equipmentId);
    },

    toggleEquipment(roomId, equipmentId, availableQty = 1) {
      const existing = this.findAvItem(roomId, equipmentId);
      if (existing) {
        // Remove it
        this.form.av_items = this.form.av_items.filter(x =>
          !(String(x.room_id) === String(roomId) && String(x.equipment_id) === String(equipmentId))
        );
      } else {
        // Add it with quantity 1 (user can adjust if multiple available)
        this.form.av_items.push({
          room_id: Number(roomId),
          equipment_id: Number(equipmentId),
          quantity: 1,
          note: ''
        });
      }
    },

    getEquipmentQty(roomId, equipmentId) {
      const item = this.findAvItem(roomId, equipmentId);
      return item ? item.quantity : 1;
    },

    setEquipmentQty(roomId, equipmentId, qty) {
      const item = this.findAvItem(roomId, equipmentId);
      if (item) {
        item.quantity = parseInt(qty, 10) || 1;
      }
    },

    // Late calculation
    recomputeLate() {
      const today = new Date();
      today.setHours(0,0,0,0);

      let startDateStr = null;

      if (this.form.schedule_type === 'recurring') {
        startDateStr = this.form.recur_start_date || null;
      } else if (this.form.schedule_type === 'custom_list') {
        const dates = this.form.custom_occurrences
          .map(o => o.date)
          .filter(Boolean)
          .sort();
        startDateStr = dates.length ? dates[0] : null;
      }

      this.computed.eventStartDate = startDateStr;

      if (!startDateStr) {
        this.computed.isLate = null;
        this.computed.leadDays = null;
        return;
      }

      const eventStart = new Date(startDateStr + 'T00:00:00');
      const diffMs = eventStart.getTime() - today.getTime();
      const leadDays = Math.floor(diffMs / 86400000);

      this.computed.leadDays = leadDays;
      this.computed.isLate = leadDays < 14;
    },

    // Summary helpers (used in step5 later)
    scheduleSummary() {
      if (this.form.schedule_type === 'recurring') {
        const sd = this.form.recur_start_date || '-';
        const ed = this.form.recur_end_date || '-';
        const pat = this.form.recur_pattern || '-';
        const day = this.form.recur_day || '-';
        const t = (this.form.recur_start_time || this.form.recur_end_time)
          ? ` ${this.form.recur_start_time || ''} - ${this.form.recur_end_time || ''}`
          : '';
        return `Recurring: ${pat} on ${day}, ${sd} to ${ed}${t}`;
      }

      const dates = this.form.custom_occurrences.map(o => o.date).filter(Boolean);
      return `Custom: ${dates.length ? dates.join(', ') : '-'}`;
    },

    selectedRoomNames() {
      return this.form.av_room_ids.map(id => this.roomName(id)).join(', ');
    },

    avItemsSummary() {
      const items = this.form.av_items.filter(it => (it.quantity || 0) > 0);
      if (!items.length) return '';

      const nameByEq = {};
      for (const roomId in (this.lookups.equipmentByRoom || {})) {
        for (const eq of (this.lookups.equipmentByRoom[roomId] || [])) {
          nameByEq[String(eq.equipment_id)] = eq.name;
        }
      }

      return items
        .map(it => `${nameByEq[String(it.equipment_id)] || 'Equipment'} x${it.quantity} [${this.roomName(it.room_id)}]`)
        .join(', ');
    },

    rehearsalSummary() {
      if (!this.form.rehearsal_date) return '-';
      const st = this.form.rehearsal_start_time || '';
      const et = this.form.rehearsal_end_time || '';
      const t = (st || et) ? ` ${st} - ${et}` : '';
      return `${this.form.rehearsal_date}${t}`;
    },

    mediaPlatformsSummary() {
      if (!this.form.media_platforms.length) return '-';
      const labels = {
        'bulletin': 'Bulletin',
        'before_mass': 'Before Mass',
        'social_media': 'Social Media'
      };
      return this.form.media_platforms.map(p => labels[p] || p).join(', ');
    },

    // Map UI platform options to backend-compatible values
    mapPlatformsForBackend(platforms) {
      const result = [];
      for (const p of platforms) {
        if (p === 'social_media') {
          // Social media expands to FB, Insta, Telegram
          result.push({ platform: 'facebook', other_label: null });
          result.push({ platform: 'instagram', other_label: null });
          result.push({ platform: 'telegram', other_label: null });
        } else if (p === 'bulletin') {
          result.push({ platform: 'other', other_label: 'Bulletin' });
        } else if (p === 'before_mass') {
          result.push({ platform: 'other', other_label: 'Before Mass' });
        }
      }
      return result;
    },

    promoSummary() {
      const a = this.form.promo_start_date || '-';
      const b = this.form.promo_end_date || '-';
      if (a === '-' && b === '-') return '-';
      return `${a} to ${b}`;
    },

    photoSummary() {
      return this.form.photo_note ? 'Notes provided' : 'Same as event schedule';
    },

    // Validation (basic scaffold, steps can refine later)
    validateStep(step) {
      let ok = true;
      const e = {};

      if (step === 0) {
        if (!this.form.has_required_approvals) {
          e.has_required_approvals = 'Please select Yes or No.';
          ok = false;
        }
        if (this.form.has_required_approvals === 'no') {
          e.has_required_approvals = 'You cannot proceed without required approvals.';
          ok = false;
        }
      }

      if (step === 1) {
        if (!this.form.requestor_name) { e.requestor_name = 'Name is required.'; ok = false; }
        if (!this.form.contact_no) { e.contact_no = 'Contact number is required.'; ok = false; }
        if (!this.form.email) { e.email = 'Email is required.'; ok = false; }

        if (this.form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) {
          e.email = 'Please enter a valid email address.';
          ok = false;
        }
      }

      if (step === 2) {
        if (!this.form.event_name) { e.event_name = 'Event name is required.'; ok = false; }
        if (!this.form.schedule_type) { e.schedule_type = 'Please select a schedule type.'; ok = false; }

        if (this.form.schedule_type === 'recurring') {
          if (!this.form.recur_start_date) { e.recur_start_date = 'Start date is required.'; ok = false; }
          if (!this.form.recur_end_date) { e.recur_end_date = 'End date is required.'; ok = false; }
          if (this.form.recur_start_date && this.form.recur_end_date && this.form.recur_end_date < this.form.recur_start_date) {
            e.recur_end_date = 'End date cannot be earlier than start date.';
            ok = false;
          }
          if (!this.form.recur_pattern) { e.recur_pattern = 'Recurrence pattern is required.'; ok = false; }
          if (!this.form.recur_day) { e.recur_day = 'Day of week is required.'; ok = false; }
        }

        if (this.form.schedule_type === 'custom_list') {
          const dates = this.form.custom_occurrences.map(o => o.date).filter(Boolean);
          if (!dates.length) { e.custom_list = 'Please add at least one date.'; ok = false; }
        }

        this.recomputeLate();
      }

      if (step === 3) {
        if (!this.form.services.length) { e.services = 'Please select at least one service.'; ok = false; }

        if (this.hasService('av')) {
          if (!this.form.av_room_ids.length) {
            e.av_rooms = 'Please select at least one room for AV support.';
            ok = false;
          }
        }

        if (this.hasService('media')) {
          if (!this.form.media_description) {
            e.media_description = 'Please describe what you need for poster/video.';
            ok = false;
          }
        }

        // Validate reference URL if provided
        if (this.form.reference_url) {
          try { new URL(this.form.reference_url); }
          catch (_) { e.reference_url = 'Please enter a valid URL.'; ok = false; }
        }
      }

      if (step === 4) {
        if (!this.form.confirmed) {
          e.confirmed = 'Please confirm before submitting.';
          ok = false;
        }
      }

      this.errors = e;
      return ok;
    },

    // Submit
    async submit() {
      this.banner = { type: '', message: '' };
      this.errors = {};

      if (!this.validateStep(4)) {
        this.banner = { type: 'error', message: 'Please confirm before submitting.' };
        return;
      }

      if (this.form.hp) {
        this.banner = { type: 'error', message: 'Submission blocked.' };
        return;
      }

      this.isSubmitting = true;

      try {
        const payload = this.buildPayload();

        const res = await fetch('./api/submit_request.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': this.form.csrf
          },
          credentials: 'same-origin',
          body: JSON.stringify(payload)
        });

        const text = await res.text();
        let data;
        try { data = JSON.parse(text); }
        catch (_) { throw new Error('Submit response not JSON. Raw: ' + text.slice(0, 200)); }

        if (!data.ok) {
          this.isSubmitting = false;
          this.banner = { type: 'error', message: data.error || 'Unable to submit request.' };
          return;
        }

        // Clear saved draft on successful submission
        this.clearStorage();

        const ref = encodeURIComponent(data.reference_no || '');
        window.location.href = `thank_you.php?ref=${ref}`;

      } catch (e) {
        console.error('[MR] submit error', e);
        this.isSubmitting = false;
        this.banner = { type: 'error', message: 'Network or server error. Please try again.' };
      }
    },

    buildPayload() {
      // schedule object
      const schedule = { type: this.form.schedule_type, notes: this.form.schedule_notes || null };

      if (this.form.schedule_type === 'recurring') {
        schedule.start_date = this.form.recur_start_date;
        schedule.end_date = this.form.recur_end_date;
        schedule.start_time = this.form.recur_start_time || null;
        schedule.end_time = this.form.recur_end_time || null;
        schedule.recurrence_pattern = this.form.recur_pattern;
        schedule.recurrence_days_of_week = this.form.recur_day;
        schedule.recurrence_interval = 1;
      }

      if (this.form.schedule_type === 'custom_list') {
        schedule.occurrences = this.form.custom_occurrences
          .filter(o => o.date)
          .map(o => ({
            date: o.date,
            start_time: o.start_time || null,
            end_time: o.end_time || null
          }));
      }

      return {
        csrf: this.form.csrf,

        requestor: {
          name: this.form.requestor_name,
          ministry: this.form.ministry || null,
          contact_no: this.form.contact_no,
          email: this.form.email,
          has_required_approvals: this.form.has_required_approvals === 'yes'
        },

        event: {
          name: this.form.event_name,
          description: this.form.event_description || null,
          location_note: this.form.event_location_note || null
        },

        schedule,

        computed: {
          is_late: this.computed.isLate === true,
          lead_days: this.computed.leadDays
        },

        services: this.form.services,

        av: this.hasService('av') ? {
          room_ids: this.form.av_room_ids.map(x => Number(x)),
          items: this.form.av_items
            .filter(it => (it.quantity || 0) > 0)
            .map(it => ({
              room_id: it.room_id,
              equipment_id: it.equipment_id,
              quantity: it.quantity,
              note: it.note || null
            })),
          rehearsal_date: this.form.rehearsal_date || null,
          rehearsal_start_time: this.form.rehearsal_start_time || null,
          rehearsal_end_time: this.form.rehearsal_end_time || null,
          note: this.form.av_note || null
        } : null,

        media: this.hasService('media') ? {
          description: this.form.media_description || null,
          promo_start_date: this.form.promo_start_date || null,
          promo_end_date: this.form.promo_end_date || null,
          caption_details: this.form.caption_details || null,
          note: this.form.media_note || null,
          platforms: this.mapPlatformsForBackend(this.form.media_platforms)
        } : null,

        photo: this.hasService('photo') ? {
          needed_date: null,
          start_time: null,
          end_time: null,
          note: this.form.photo_note || null
        } : null,

        references: {
          url: this.form.reference_url || null,
          note: this.form.reference_note || null
        }
      };
    }
  };
}
</script>

<?php include __DIR__ . "/partials/footer.php"; ?>
