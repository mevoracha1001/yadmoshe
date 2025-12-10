<?php
// Yad Moshe - Configuration File
// Copy this file to config.php and enter your actual credentials

// Twilio Configuration - ENTER YOUR ACTUAL CREDENTIALS HERE
define('TWILIO_USERNAME', 'YOUR_TWILIO_ACCOUNT_SID');
define('TWILIO_PASSWORD', 'YOUR_TWILIO_AUTH_TOKEN');

// Twilio API endpoint
define('TWILIO_API_URL', 'https://api.twilio.com/2010-04-04');

// Sending configuration
define('MIN_DELAY_SECONDS', 1);
define('MAX_DELAY_SECONDS', 2);
define('MIN_BATCH_SIZE', 100);
define('MAX_BATCH_SIZE', 200);
define('MIN_BATCH_PAUSE_MINUTES', 1);
define('MAX_BATCH_PAUSE_MINUTES', 2);

// Concurrent sending configuration
define('MAX_CONCURRENT_REQUESTS', 25); // Maximum concurrent SMS requests
define('CONCURRENT_BATCH_SIZE', 100);  // Process contacts in batches for concurrent sending
define('REQUEST_TIMEOUT', 30);         // cURL timeout in seconds

// File paths
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('LOG_DIR', __DIR__ . '/logs/');
define('TEMP_DIR', __DIR__ . '/temp/');
define('IMAGES_DIR', __DIR__ . '/uploads/images/');
// Calculate base URL for public file access
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$basePath = rtrim($scriptDir, '/');
define('BASE_URL', $protocol . '://' . $host . $basePath);

// Create directories if they don't exist
if (!file_exists(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
if (!file_exists(LOG_DIR)) mkdir(LOG_DIR, 0755, true);
if (!file_exists(TEMP_DIR)) mkdir(TEMP_DIR, 0755, true);
if (!file_exists(IMAGES_DIR)) mkdir(IMAGES_DIR, 0755, true);

// Session configuration
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);







