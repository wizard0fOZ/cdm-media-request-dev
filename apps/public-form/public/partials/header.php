<?php
// public/partials/header.php
// Usage:
// $pageTitle = "CDMMedia Request System";
// $activePage = "home"; // home | request | thankyou
// include __DIR__ . "/partials/header.php";

if (!isset($pageTitle)) $pageTitle = "CDMMedia Request System";
if (!isset($activePage)) $activePage = "";
?>
<!doctype html>
<html lang="en"
      x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }"
      :class="{ 'dark': darkMode }"
      x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo htmlspecialchars($pageTitle); ?></title>

  <!-- Favicon -->
  <link rel="icon" href="assets/favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="assets/favicon.ico" type="image/x-icon">

  <!-- Tailwind (dev). For production, replace with <link rel="stylesheet" href="assets/app.css"> -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class'
    }
  </script>

  <!-- Font Awesome -->
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
  />

  <!-- Alpine -->
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <script defer src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>

  <style>
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(6px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in { animation: fadeIn .6s ease-out both; }
    @media (prefers-reduced-motion: reduce) {
      .animate-fade-in { animation: none; }
      * { transition: none !important; }
    }
  </style>
</head>

<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-slate-100 transition-colors duration-200">
<header class="sticky top-0 z-40 bg-white/80 dark:bg-slate-800/80 backdrop-blur border-b border-slate-200 dark:border-slate-700">
  <div class="mx-auto max-w-6xl px-4 py-3 flex items-center justify-between">

    <!-- Brand -->
    <a href="index.php" class="flex items-center gap-3">
      <img
        src="assets/media_logo.png"
        alt="Church of the Divine Mercy"
        class="h-10 w-auto object-contain"
      />
      <div class="hidden sm:block">
        <div class="font-extrabold leading-tight text-slate-900 dark:text-slate-50">
          Media Request System
        </div>
        <div class="text-xs text-slate-500 dark:text-slate-400">
          Church of the Divine Mercy
        </div>
      </div>
    </a>

    <!-- Actions -->
    <nav class="flex items-center gap-2 sm:gap-3">
      <?php if ($activePage === "home"): ?>
        <button
          type="button"
          onclick="(function(){ var el=document.getElementById('guidelines'); if(el) el.scrollIntoView({behavior:'smooth', block:'start'}); })();"
          class="hidden sm:inline-flex items-center gap-2 rounded-lg px-3 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700"
        >
          <i class="fa-solid fa-circle-info"></i>
          Guidelines
        </button>
      <?php endif; ?>

      <!-- Dark Mode Toggle -->
      <button
        type="button"
        @click="darkMode = !darkMode"
        class="inline-flex items-center justify-center rounded-lg w-10 h-10 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
        :title="darkMode ? 'Switch to light mode' : 'Switch to dark mode'"
      >
        <i x-show="!darkMode" class="fa-solid fa-moon text-lg"></i>
        <i x-show="darkMode" class="fa-solid fa-sun text-lg"></i>
      </button>

      <a
        href="request.php"
        class="inline-flex items-center gap-2 rounded-lg bg-slate-900 dark:bg-slate-100 px-4 py-2 text-white dark:text-slate-900 font-semibold hover:bg-slate-800 dark:hover:bg-slate-200 transition-colors"
      >
        <i class="fa-solid fa-paper-plane"></i>
        <span class="hidden sm:inline">Submit Request</span>
      </a>
    </nav>
  </div>
</header>
