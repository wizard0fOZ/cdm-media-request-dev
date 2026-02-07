<!-- Step 4: References -->
<div class="space-y-6">

  <!-- Header -->
  <div class="rounded-xl bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/30 dark:to-orange-900/30 p-6 ring-1 ring-amber-200 dark:ring-amber-700">
    <div class="flex items-start gap-4">
      <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400">
        <i class="fa-solid fa-link text-xl"></i>
      </div>
      <div>
        <h2 class="text-lg font-bold text-slate-900 dark:text-slate-50">References & Additional Info</h2>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
          Share any helpful links or additional details for your request (optional)
        </p>
      </div>
    </div>
  </div>

  <!-- Reference URL -->
  <div class="rounded-xl bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
    <div class="space-y-5">

      <!-- URL Field -->
      <div>
        <label for="reference_url" class="mb-1.5 block text-sm font-semibold text-slate-800 dark:text-slate-200">
          Reference Link
          <span class="ml-1 text-xs font-normal text-slate-500 dark:text-slate-400">(optional)</span>
        </label>
        <p class="mb-2 text-xs text-slate-500 dark:text-slate-400">
          Share a link to design files, documents, or other reference materials (e.g., Canva, Google Drive, Dropbox)
        </p>
        <div class="relative">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
            <i class="fa-solid fa-globe text-slate-400 dark:text-slate-500"></i>
          </div>
          <input type="url"
                 id="reference_url"
                 x-model="form.reference_url"
                 placeholder="https://..."
                 class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 py-2.5 pl-10 pr-4 text-slate-900 dark:text-slate-100 transition placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:border-amber-500 dark:focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:focus:ring-amber-400" />
        </div>
      </div>

      <!-- Notes Field -->
      <div>
        <label for="reference_note" class="mb-1.5 block text-sm font-semibold text-slate-800 dark:text-slate-200">
          Additional Notes
          <span class="ml-1 text-xs font-normal text-slate-500 dark:text-slate-400">(optional)</span>
        </label>
        <p class="mb-2 text-xs text-slate-500 dark:text-slate-400">
          Any other details, special instructions, or context that would help us fulfill your request
        </p>
        <textarea id="reference_note"
                  x-model="form.reference_note"
                  rows="4"
                  placeholder="E.g., specific design preferences, timing considerations, contact person for coordination..."
                  class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-4 py-2.5 text-slate-900 dark:text-slate-100 transition placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:border-amber-500 dark:focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:focus:ring-amber-400"></textarea>
      </div>

    </div>
  </div>

  <!-- Info Note -->
  <div class="rounded-lg border border-amber-200 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/30 p-4">
    <div class="flex gap-3">
      <i class="fa-solid fa-lightbulb mt-0.5 text-amber-600 dark:text-amber-400"></i>
      <div class="text-sm text-amber-800 dark:text-amber-300">
        <p class="font-medium">Tips for helpful references:</p>
        <ul class="mt-1 list-inside list-disc space-y-0.5 text-amber-700 dark:text-amber-400">
          <li>Links to sample designs or inspiration images</li>
          <li>Event posters or flyers from previous years</li>
          <li>Specific branding guidelines or logos to use</li>
          <li>Contact details for event coordinators</li>
        </ul>
      </div>
    </div>
  </div>

</div>
