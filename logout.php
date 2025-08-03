<?php
// Logout functionality for Hostel Check-In/Out System

// Define application constant
define('HOSTEL_APP', true);

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Include helper functions
require_once __DIR__ . '/helpers/auth.php';

// Perform logout
logoutUser();

// Clear any output buffers
if (ob_get_level()) {
    ob_end_clean();
}

// Redirect to login page
header('Location: ' . $_ENV['BASE_URL'] . '?page=login', true, 302);
exit();
?>