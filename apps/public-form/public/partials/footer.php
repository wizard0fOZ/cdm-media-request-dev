<?php
// public/partials/footer.php
?>
<footer class="mt-12 border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
  <div class="mx-auto max-w-6xl px-4 py-10 grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

    <!-- CDM (clickable to church website) -->
    <div class="flex items-start gap-3">
      <a href="https://divinemercy.my/" target="_blank" rel="noopener noreferrer" class="shrink-0">
        <img
          src="assets/cdm_logo_300dpi.png"
          alt="Church of the Divine Mercy"
          class="h-12 w-auto"
        />
      </a>
      <div class="text-sm text-slate-600 dark:text-slate-400">
        <div class="font-semibold text-slate-800 dark:text-slate-200">
        Church of the Divine Mercy
        </div>
        <div>Shah Alam, Malaysia</div>
        <div class="mt-2">
        </div>
      </div>
    </div>

    <!-- Media (not clickable) -->
    <div class="flex items-center gap-3 lg:justify-center">
    <img
        src="assets/media_logo.png"
        alt="Media Ministry"
        class="h-10 w-auto object-contain"
    />
    <div class="text-sm font-semibold text-slate-800 dark:text-slate-200">
        CDM Media Ministry
    </div>
    </div>

    <!-- Contact -->
    <div class="text-sm lg:text-right">
      <div class="font-semibold text-slate-800 dark:text-slate-200">Contact</div>
      <a
        href="mailto:media@divinemercy.my"
        class="underline text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100"
      >
        media@divinemercy.my
      </a>
      <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">
        Response time depends on volunteer availability.
      </div>

      <!-- Social links -->
    <div class="mt-4 text-xs font-semibold text-slate-500 dark:text-slate-400 lg:text-right">Follow us</div>
    <div class="mt-4 flex items-center gap-4 lg:justify-end">
        <a href="https://divinemercy.my/" target="_blank" rel="noopener noreferrer" class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100" title="Website">
          <i class="fa-solid fa-globe text-lg"></i>
        </a>
        <a href="https://t.me/DivineMercyMY" target="_blank" rel="noopener noreferrer" class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100" title="Telegram">
          <i class="fa-brands fa-telegram text-lg"></i>
        </a>
        <a href="https://www.youtube.com/@DivineMercyMY" target="_blank" rel="noopener noreferrer" class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100" title="YouTube">
          <i class="fa-brands fa-youtube text-lg"></i>
        </a>
        <a href="https://www.instagram.com/DivineMercyMY" target="_blank" rel="noopener noreferrer" class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100" title="Instagram">
          <i class="fa-brands fa-instagram text-lg"></i>
        </a>
        <a href="https://www.facebook.com/DivineMercyMY" target="_blank" rel="noopener noreferrer" class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100" title="Facebook">
          <i class="fa-brands fa-facebook text-lg"></i>
        </a>
        <a href="https://cdmshahalam.smugmug.com/" target="_blank" rel="noopener noreferrer" class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100" title="SmugMug Gallery">
          <i class="fa-solid fa-image text-lg"></i>
        </a>
      </div>
    </div>
  </div>

<div class="border-t border-slate-200 dark:border-slate-700 py-4 text-center text-xs text-slate-500 dark:text-slate-400">
  Copyright © <?php echo date('Y'); ?> CDM Media Ministry, Media Request System
  <span class="mx-2">•</span>
  <a href="/admin/" class="hover:text-slate-700 dark:hover:text-slate-300 transition-colors">Admin</a>
</div>
</footer>

</body>
</html>
