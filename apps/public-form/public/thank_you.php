<?php
// public/thank_you.php

$ref = $_GET['ref'] ?? '';
$ref = strtoupper(trim($ref));

// Basic format validation: MR-YYYY-XXXX
if (!preg_match('/^MR-\d{4}-\d{4}$/', $ref)) {
  $ref = null;
}

$pageTitle = "Request Submitted | CDM";
include __DIR__ . "/partials/header.php";
?>

<main class="bg-slate-50 dark:bg-slate-900">
  <div class="mx-auto max-w-lg px-4 py-16 text-center animate-[fadeIn_.5s_ease-out]">
    <style>
      @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
      @media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; } }
    </style>

    <!-- Success Icon -->
    <div class="relative mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/50">
      <div class="absolute inset-0 rounded-full bg-green-200 dark:bg-green-700 animate-ping opacity-60"></div>
      <div class="relative z-10">
        <!-- keep your SVG check icon here -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
      </div>
    </div>

    <h1 class="mt-6 text-2xl font-bold text-slate-900 dark:text-slate-50">
      Request Submitted
    </h1>

    <p class="mt-2 text-slate-600 dark:text-slate-400">
      Your media request has been received successfully.
    </p>

    <!-- Reference Code -->
    <?php if ($ref): ?>
      <div class="mt-6 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
        <div class="text-sm text-slate-500 dark:text-slate-400">Reference Code</div>
        <div class="mt-1 text-xl font-mono font-bold text-slate-900 dark:text-slate-50" id="refCode">
          <?php echo htmlspecialchars($ref); ?>
        </div>
        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
          Please keep this reference code for future correspondence.
        </p>

        <button
          type="button"
          onclick="copyRef()"
          class="mt-3 inline-flex items-center gap-2 rounded-md border border-slate-300 dark:border-slate-600 px-3 py-1.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
        >
          <i class="fa-regular fa-copy"></i> Copy Reference
        </button>
      </div>
    <?php endif; ?>

    <!-- What happens next -->
    <div class="mt-6 rounded-lg bg-slate-100 dark:bg-slate-800 p-4 text-left">
      <div class="flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-200">
        <i class="fa-solid fa-circle-info text-slate-600 dark:text-slate-400"></i>
        What happens next?
      </div>

      <ul class="mt-2 list-disc list-inside text-sm text-slate-700 dark:text-slate-300 space-y-1">
        <li>A confirmation email will be sent to your email address</li>
        <li>Our media team will review your request</li>
        <li>You will be contacted if further information is needed</li>
      </ul>
    </div>

    <!-- Actions -->
    <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
      <a
        href="request.php"
        class="inline-flex items-center justify-center rounded-lg bg-slate-900 dark:bg-slate-100 px-6 py-3 text-white dark:text-slate-900 font-semibold hover:bg-slate-800 dark:hover:bg-slate-200 transition-colors"
      >
        Submit Another Request
      </a>

      <a
        href="index.php"
        class="inline-flex items-center justify-center rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-6 py-3 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors"
      >
        Back to Home
      </a>
    </div>

    <!-- Contact -->
    <p class="mt-8 text-xs text-slate-500 dark:text-slate-400">
      For enquiries, please quote your reference code and email
      <a href="mailto:media@divinemercy.my" class="underline text-slate-600 dark:text-slate-300">media@divinemercy.my</a>
    </p>

  </div>
</main>

    <!-- Confetti Animation -->
    <canvas id="confetti" class="pointer-events-none fixed inset-0 z-50"></canvas>

    <script>
    (function () {
      const canvas = document.getElementById('confetti');
      if (!canvas) return;

      const ctx = canvas.getContext('2d');
      let w, h;
      function resize() {
        w = canvas.width = window.innerWidth;
        h = canvas.height = window.innerHeight;
      }
      window.addEventListener('resize', resize);
      resize();

      const colors = ['#16a34a', '#2563eb', '#f59e0b', '#ef4444', '#a855f7'];
      const particles = Array.from({ length: 120 }, () => ({
        x: w / 2,
        y: h * 0.25,
        vx: (Math.random() - 0.5) * 8,
        vy: Math.random() * -10 - 6,
        g: 0.25 + Math.random() * 0.15,
        size: 3 + Math.random() * 4,
        color: colors[Math.floor(Math.random() * colors.length)],
        life: 80 + Math.floor(Math.random() * 40)
      }));

      let frame = 0;
      function tick() {
        frame++;
        ctx.clearRect(0, 0, w, h);

        particles.forEach(p => {
          p.x += p.vx;
          p.y += p.vy;
          p.vy += p.g;
          p.life--;

          ctx.fillStyle = p.color;
          ctx.fillRect(p.x, p.y, p.size, p.size);
        });

        // stop after burst
        if (frame < 130) requestAnimationFrame(tick);
        else canvas.remove();
      }

      // Respect reduced motion
      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        canvas.remove();
        return;
      }
      tick();
    })();
    </script>

<script>
function copyRef() {
  const el = document.getElementById('refCode');
  if (!el) return;

  navigator.clipboard.writeText(el.textContent.trim())
    .then(() => alert('Reference code copied'))
    .catch(() => alert('Unable to copy reference'));
}
</script>

<?php include __DIR__ . "/partials/footer.php"; ?>
