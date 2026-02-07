<?php
declare(strict_types=1);

session_start();

function require_admin_auth(): void {
  if (empty($_SESSION['admin_authenticated'])) {
    header('Location: login.php');
    exit;
  }
}

function is_authenticated(): bool {
  return !empty($_SESSION['admin_authenticated']);
}
