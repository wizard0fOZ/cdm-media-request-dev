<?php
// admin/partials/header.php
// Usage:
// $pageTitle = "Admin Dashboard | CDM";
// include __DIR__ . "/partials/header.php";

if (!isset($pageTitle)) $pageTitle = "Admin | CDM Media Request System";

// Load env (for APP_ENV) if available
$configPath = __DIR__ . '/../../includes/config.php';
if (file_exists($configPath)) {
  require_once $configPath;
}
$appEnv = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? 'production';
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

  <!-- Tailwind (dev). For production, replace with compiled CSS -->
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

<body class="min-h-screen flex flex-col bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-slate-100 transition-colors duration-200">
<header class="sticky top-0 z-40 bg-white/80 dark:bg-slate-800/80 backdrop-blur border-b border-slate-200 dark:border-slate-700">
  <div class="mx-auto max-w-7xl px-4 py-3 flex items-center justify-between">

    <!-- Brand -->
    <a href="index.php" class="flex items-center gap-3">
      <i class="fa-solid fa-shield-halved text-2xl text-slate-700 dark:text-slate-300"></i>
      <div>
        <div class="font-extrabold leading-tight text-slate-900 dark:text-slate-50">
          CDM Media Admin
        </div>
        <div class="text-xs text-slate-500 dark:text-slate-400">
          Request Management
        </div>
      </div>
    </a>

    <!-- Actions -->
    <nav class="flex items-center gap-2 sm:gap-3">
      <a
        href="index.php"
        class="hidden sm:inline-flex items-center gap-2 rounded-lg px-3 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
      >
        <i class="fa-solid fa-table-list"></i>
        Dashboard
      </a>

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
        href="logout.php"
        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors"
      >
        <i class="fa-solid fa-right-from-bracket"></i>
        <span class="hidden sm:inline">Logout</span>
      </a>
    </nav>
  </div>
</header>

<?php if ($appEnv === 'development'): ?>
  <div class="bg-amber-400 text-black">
    <div class="mx-auto max-w-7xl px-4 py-2 text-xs sm:text-sm font-semibold tracking-wide flex items-center gap-2">
      <i class="fa-solid fa-triangle-exclamation"></i>
      <span>DEVELOPMENT ENVIRONMENT</span>
    </div>
  </div>
<?php endif; ?>
