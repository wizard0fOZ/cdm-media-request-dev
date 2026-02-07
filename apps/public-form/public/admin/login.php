<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

// If already logged in, redirect to dashboard
if (is_authenticated()) {
  header('Location: index.php');
  exit;
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $password = $_POST['password'] ?? '';

  if ($password === ADMIN_PASSWORD) {
    $_SESSION['admin_authenticated'] = true;
    header('Location: index.php');
    exit;
  } else {
    $error = 'Invalid password. Please try again.';
  }
}

$pageTitle = "Admin Login | CDM";
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

  <!-- Tailwind -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class'
    }
  </script>

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <!-- Alpine -->
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-slate-100 transition-colors duration-200 flex items-center justify-center min-h-screen">

  <!-- Dark Mode Toggle (fixed top-right) -->
  <button
    type="button"
    @click="darkMode = !darkMode"
    class="fixed top-4 right-4 inline-flex items-center justify-center rounded-lg w-10 h-10 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
    :title="darkMode ? 'Switch to light mode' : 'Switch to dark mode'"
  >
    <i x-show="!darkMode" class="fa-solid fa-moon text-lg"></i>
    <i x-show="darkMode" class="fa-solid fa-sun text-lg"></i>
  </button>

  <!-- Login Card -->
  <div class="w-full max-w-md px-4">
    <div class="rounded-xl bg-white dark:bg-slate-800 p-8 shadow-lg ring-1 ring-slate-200 dark:ring-slate-700">

      <!-- Header -->
      <div class="text-center mb-8">
        <div class="flex items-center justify-center mb-4">
          <div class="flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-700">
            <i class="fa-solid fa-shield-halved text-3xl text-slate-700 dark:text-slate-300"></i>
          </div>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-50">CDM Media Admin</h1>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Sign in to manage media requests</p>
      </div>

      <!-- Error Message -->
      <?php if ($error): ?>
        <div class="mb-6 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/30 px-4 py-3">
          <div class="flex items-center gap-2 text-sm text-red-700 dark:text-red-300">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
          </div>
        </div>
      <?php endif; ?>

      <!-- Login Form -->
      <form method="POST" action="login.php">
        <div class="mb-6" x-data="{ show: false }">
          <label for="password" class="mb-2 block text-sm font-semibold text-slate-800 dark:text-slate-200">
            Password
          </label>
          <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
              <i class="fa-solid fa-lock text-slate-400 dark:text-slate-500"></i>
            </div>
            <input
              :type="show ? 'text' : 'password'"
              id="password"
              name="password"
              required
              autofocus
              autocomplete="current-password"
              class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 py-2.5 pl-10 pr-10 text-slate-900 dark:text-slate-100 transition focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-900 dark:focus:ring-slate-100"
              placeholder="Enter admin password"
            />
            <button
              type="button"
              @click="show = !show"
              class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
            >
              <i x-show="!show" class="fa-solid fa-eye"></i>
              <i x-show="show" class="fa-solid fa-eye-slash"></i>
            </button>
          </div>
        </div>

        <button
          type="submit"
          class="w-full rounded-lg bg-slate-900 dark:bg-slate-100 px-4 py-3 font-semibold text-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-slate-200 transition-colors"
        >
          <i class="fa-solid fa-right-to-bracket mr-2"></i>
          Sign In
        </button>
      </form>
    </div>

    <!-- Back to Public Site -->
    <div class="mt-6 text-center">
      <a href="/" class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors">
        <i class="fa-solid fa-arrow-left mr-1"></i>
        Back to Media Request Form
      </a>
    </div>
  </div>

</body>
</html>
