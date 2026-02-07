<?php
declare(strict_types=1);

session_start();

// Destroy the session
$_SESSION = [];
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
