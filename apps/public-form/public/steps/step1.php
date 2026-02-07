<div class="rounded-xl bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">

  <!-- Step Title -->
  <div class="mb-6">
    <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-50">Requestor Information</h2>
    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
      Please provide your contact details. We'll use this information to reach you regarding your request.
    </p>
  </div>

  <!-- Form Fields -->
  <div class="space-y-5">

    <!-- Requestor Name -->
    <div>
      <label for="requestor_name" class="mb-1.5 block text-sm font-semibold text-slate-800 dark:text-slate-200">
        Full Name <span class="text-red-600 dark:text-red-400">*</span>
      </label>
      <input type="text"
             id="requestor_name"
             x-model="form.requestor_name"
             @input="errors.requestor_name = ''"
             class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-4 py-2.5 text-slate-900 dark:text-slate-100 transition placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-900 dark:focus:ring-slate-100"
             :class="errors.requestor_name ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : ''"
             placeholder="Enter your full name" />
      <div x-show="errors.requestor_name" class="mt-1.5 flex items-start gap-1.5 text-sm text-red-600 dark:text-red-400">
        <i class="fa-solid fa-circle-exclamation mt-0.5 text-xs"></i>
        <span x-text="errors.requestor_name"></span>
      </div>
    </div>

    <!-- Ministry (Optional) -->
    <div>
      <label for="ministry" class="mb-1.5 block text-sm font-semibold text-slate-800 dark:text-slate-200">
        Ministry / Department <span class="text-slate-500 dark:text-slate-400 text-xs font-normal">(Optional)</span>
      </label>
      <input type="text"
             id="ministry"
             x-model="form.ministry"
             class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-4 py-2.5 text-slate-900 dark:text-slate-100 transition placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-900 dark:focus:ring-slate-100"
             placeholder="e.g., Youth Ministry, Choir, Catechism" />
      <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
        Ease management of your request by providing your ministry or department.
      </p>
    </div>

    <!-- Contact Number -->
    <div>
      <label for="contact_no" class="mb-1.5 block text-sm font-semibold text-slate-800 dark:text-slate-200">
        Contact Number <span class="text-red-600 dark:text-red-400">*</span>
      </label>
      <input type="tel"
             id="contact_no"
             x-model="form.contact_no"
             @input="errors.contact_no = ''"
             class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-4 py-2.5 text-slate-900 dark:text-slate-100 transition placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-900 dark:focus:ring-slate-100"
             :class="errors.contact_no ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : ''"
             placeholder="e.g., 012-3456789" />
      <div x-show="errors.contact_no" class="mt-1.5 flex items-start gap-1.5 text-sm text-red-600 dark:text-red-400">
        <i class="fa-solid fa-circle-exclamation mt-0.5 text-xs"></i>
        <span x-text="errors.contact_no"></span>
      </div>
    </div>

    <!-- Email -->
    <div>
      <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-800 dark:text-slate-200">
        Email Address <span class="text-red-600 dark:text-red-400">*</span>
      </label>
      <input type="email"
             id="email"
             x-model="form.email"
             @input="errors.email = ''"
             class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-4 py-2.5 text-slate-900 dark:text-slate-100 transition placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-900 dark:focus:ring-slate-100"
             :class="errors.email ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : ''"
             placeholder="your.email@example.com" />
      <div x-show="errors.email" class="mt-1.5 flex items-start gap-1.5 text-sm text-red-600 dark:text-red-400">
        <i class="fa-solid fa-circle-exclamation mt-0.5 text-xs"></i>
        <span x-text="errors.email"></span>
      </div>
      <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
        We'll send confirmation and updates to this email.
      </p>
    </div>

  </div>

  <!-- Info Box -->
  <div class="mt-6 rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/30 px-4 py-3 text-sm text-blue-800 dark:text-blue-300">
    <div class="flex items-start gap-2">
      <i class="fa-solid fa-shield-halved mt-0.5"></i>
      <div>
        <strong>Privacy note:</strong> Your contact information will only be used for this request and will not be shared with third parties.
      </div>
    </div>
  </div>

</div>
