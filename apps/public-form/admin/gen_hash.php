<?php
declare(strict_types=1);

// CLI-only helper to generate password hashes.
if (php_sapi_name() !== 'cli') {
  http_response_code(403);
  echo "Forbidden\n";
  exit(1);
}

$password = $argv[1] ?? '';

if ($password === '') {
  fwrite(STDOUT, "Enter password: ");
  $password = trim((string)fgets(STDIN));
}

if ($password === '') {
  fwrite(STDERR, "No password provided.\n");
  exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
fwrite(STDOUT, $hash . PHP_EOL);
