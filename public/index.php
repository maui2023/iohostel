<?php

// Main entry point for Hostel Check-In/Out System

// Define application constant
define('HOSTEL_APP', true);

// Error reporting for development (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Start session
session_start();

// Start output buffering
ob_start();

// Include helper functions
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/qr.php';
require_once __DIR__ . '/../helpers/whatsapp.php';

// Include the routing system
require_once __DIR__ . '/../routes/web.php';

// End output buffering and send output
ob_end_flush();