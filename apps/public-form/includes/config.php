<?php
declare(strict_types=1);

// Load Composer autoloader if available (for PHPMailer and phpdotenv)
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;

    // Load environment variables from .env file
    $envPath = __DIR__ . '/..';
    if (file_exists($envPath . '/.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable($envPath);
        $dotenv->load();
    }
}

// Database configuration - use .env values or fallback to defaults
define('DB_HOST', $_ENV['DB_HOST'] ?? 'mysql');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'appdb');
define('DB_USER', $_ENV['DB_USER'] ?? 'appuser');
define('DB_PASS', $_ENV['DB_PASS'] ?? 'apppass123');
define('DB_PORT', (int)($_ENV['DB_PORT'] ?? 3306));
