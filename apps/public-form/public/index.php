<?php
// public/index.php
$pageTitle = "CDMMedia Request System | CDM";
$activePage = "home";
include __DIR__ . "/partials/header.php";
?>

<main class="bg-gradient-to-b from-slate-100 to-slate-50 dark:from-slate-900 dark:to-slate-800">
  <div class="mx-auto max-w-6xl px-4 py-12">

    <!-- Hero -->
    <div class="text-center animate-fade-in">
      <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight text-slate-900 dark:text-slate-50">
        CDMMedia Request System
      </h1>
      <p class="mt-4 text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
        Submit poster, video, photography and AV support requests in one place.
      </p>

      <div class="mt-8 flex justify-center gap-3">
        <a
          href="request.php"
          class="inline-flex items-center gap-2 rounded-lg bg-slate-900 dark:bg-slate-100 px-6 py-3 text-white dark:text-slate-900 font-semibold shadow hover:bg-slate-800 dark:hover:bg-slate-200 transition-colors"
        >
          <i class="fa-solid fa-paper-plane"></i>
          Submit a Request
        </a>

        <a
          href="#guidelines"
          class="inline-flex items-center gap-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-6 py-3 text-slate-800 dark:text-slate-200 font-semibold hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors"
        >
          <i class="fa-solid fa-circle-info"></i>
          View Guidelines
        </a>
      </div>
    </div>

    <!-- Services -->
    <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="rounded-xl bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700 transition hover:-translate-y-0.5 hover:shadow-md">
        <div class="h-10 w-10 rounded-lg bg-purple-100 dark:bg-purple-900/50 grid place-items-center">
          <i class="fa-solid fa-bullhorn text-purple-700 dark:text-purple-400"></i>
        </div>
        <h3 class="mt-5 text-xl font-bold text-slate-900 dark:text-slate-50">Poster & Video</h3>
        <p class="mt-2 text-slate-600 dark:text-slate-400">
          Request promotional materials for your ministry events
        </p>
      </div>

      <div class="rounded-xl bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700 transition hover:-translate-y-0.5 hover:shadow-md">
        <div class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/50 grid place-items-center">
          <i class="fa-solid fa-headphones text-blue-700 dark:text-blue-400"></i>
        </div>
        <h3 class="mt-5 text-xl font-bold text-slate-900 dark:text-slate-50">AV Support</h3>
        <p class="mt-2 text-slate-600 dark:text-slate-400">
          Get audio-visual equipment and technical assistance
        </p>
      </div>

      <div class="rounded-xl bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700 transition hover:-translate-y-0.5 hover:shadow-md">
        <div class="h-10 w-10 rounded-lg bg-green-100 dark:bg-green-900/50 grid place-items-center">
          <i class="fa-solid fa-camera text-green-700 dark:text-green-400"></i>
        </div>
        <h3 class="mt-5 text-xl font-bold text-slate-900 dark:text-slate-50">Photography</h3>
        <p class="mt-2 text-slate-600 dark:text-slate-400">
          Photography coverage for your events, subject to availability
        </p>
      </div>
    </div>

    <!-- Important Guidelines -->
    <div id="guidelines" class="mt-10 rounded-xl bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
      <div class="flex items-center gap-2">
        <i class="fa-solid fa-thumbtack text-slate-700 dark:text-slate-300"></i>
        <h2 class="text-xl font-bold text-slate-900 dark:text-slate-50">Important Guidelines</h2>
      </div>

      <ul class="mt-4 space-y-2 text-slate-700 dark:text-slate-300">
        <li class="flex gap-2"><span class="mt-0.5 text-slate-500 dark:text-slate-400">✓</span> Ensure approvals and confirmations are obtained before submitting</li>
        <li class="flex gap-2"><span class="mt-0.5 text-slate-500 dark:text-slate-400">✓</span> Submit requests at least 14 days in advance where possible</li>
        <li class="flex gap-2"><span class="mt-0.5 text-slate-500 dark:text-slate-400">✓</span> A confirmation email with reference code will be sent after submission</li>
      </ul>
    </div>

    <!-- Detailed Guidelines -->
    <div class="mt-8 rounded-xl bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700" x-data="{ open: { poster: true, av: false } }">
      <h2 class="text-xl font-bold text-slate-900 dark:text-slate-50">Detailed Guidelines</h2>
      <p class="mt-1 text-slate-600 dark:text-slate-400">
        Please read before submitting. This helps avoid delays and misunderstandings.
      </p>

      <div class="mt-6 space-y-3">
        <!-- Poster -->
        <div class="rounded-lg border border-slate-200 dark:border-slate-700">
          <button
            type="button"
            class="w-full flex items-center justify-between px-4 py-3 text-left font-semibold text-slate-900 dark:text-slate-100"
            @click="open.poster = !open.poster"
          >
            <span class="flex items-center gap-2">
              <i class="fa-solid fa-bullhorn text-purple-700 dark:text-purple-400"></i>
              Poster Request Guidelines
            </span>
            <span class="text-slate-500 dark:text-slate-400" x-text="open.poster ? '−' : '+'"></span>
          </button>

          <div x-show="open.poster" x-collapse class="px-4 pb-4 text-slate-700 dark:text-slate-300 space-y-3">
            <p>
              The deadline for poster requests is <b class="text-slate-900 dark:text-slate-100">14 days before the release weekend</b>. Requests are encouraged to be made as early as possible to allow sufficient time for the review process.
            </p>
            <p>
              Please ensure that all necessary approvals and confirmations have been obtained before submitting this form (e.g. parish priest, room booking).
              Amendments due to incomplete or unconfirmed information may result in delays in releasing the poster.
              Therefore, it is important that the information provided is accurate, detailed, and clear.
            </p>
            <p>
              Requests and suggestions on design elements can be made, however, they may not be applied.
              Final or partial designs may be provided, however, their use or adoption will be at the discretion of the team.
            </p>
            <p>
              <b class="text-slate-900 dark:text-slate-100">For poster requests made after the deadline, the following will apply:</b>
            </p>
            <ul class="ml-4 list-disc space-y-1">
              <li>Bulletin & In Church - A black & white poster will be used</li>
              <li>Social Media - No promotion</li>
            </ul>
          </div>
        </div>

        <!-- AV -->
        <div class="rounded-lg border border-slate-200 dark:border-slate-700">
          <button
            type="button"
            class="w-full flex items-center justify-between px-4 py-3 text-left font-semibold text-slate-900 dark:text-slate-100"
            @click="open.av = !open.av"
          >
            <span class="flex items-center gap-2">
              <i class="fa-solid fa-headphones text-blue-700 dark:text-blue-400"></i>
              AV Support Request Guidelines
            </span>
            <span class="text-slate-500 dark:text-slate-400" x-text="open.av ? '−' : '+'"></span>
          </button>

          <div x-show="open.av" x-collapse class="px-4 pb-4 text-slate-700 dark:text-slate-300 space-y-3">
            <p>
              The deadline for AV support requests is <b class="text-slate-900 dark:text-slate-100">14 days before the event</b>. Requests are encouraged to be made as early as possible to allow sufficient time for the planning process.
              It is highly advised to have one person from your team be in contact with the AV team if your event requires any special requests, to check if the request is feasible.
            </p>
            <p>
              Please ensure that all necessary approvals and confirmations have been obtained before submitting this form (e.g. parish priest, room booking).
              Changes in requests due to incomplete or unconfirmed information may result in the inability to provide the necessary support.
              Therefore, it is important that the information provided is accurate, detailed, and clear.
            </p>
            <p>
              The presence of AV team members to manage the system throughout the stated event is not guaranteed and depends on their availability.
              Should the event require technical support during the event, please inform early to check on the AV team's availability.
              It is good practice to have someone from your own team assigned to handle this with the AV team's guidance.
            </p>
          </div>
        </div>

        <!-- Contact -->
        <div class="rounded-lg border border-slate-200 dark:border-slate-700 p-4">
          <p class="text-slate-700 dark:text-slate-300">
            For further information, assistance or clarification please email
            <a class="font-semibold text-slate-900 dark:text-slate-100 underline" href="mailto:media@divinemercy.my">media@divinemercy.my</a>
          </p>
        </div>
      </div>
    </div>

  </div>
</main>

<?php include __DIR__ . "/partials/footer.php"; ?>
