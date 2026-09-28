<?php
/**
 * App Configuration
 * -----------------------------------------------------
 * Site-wide constants and settings.
 */

define('APP_NAME', 'City Care Hospital System');

// Dynamic BASE_URL to work with ngrok and different domains
if (isset($_SERVER['HTTP_HOST'])) {
    // Check for HTTPS (standard or forwarded headers)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    // Always include city_hospital path
    define('BASE_URL', $protocol . '://' . $host . '/city_hospital');
} else {
    define('BASE_URL', 'http://localhost/city_hospital');
}

// Where uploaded reports/prescriptions are stored on disk
define('UPLOAD_DIR_REPORTS', __DIR__ . '/../uploads/reports/');
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['application/pdf', 'image/jpeg', 'image/png']);

// Appointment slot default (used if a doctor schedule doesn't specify one)
define('DEFAULT_SLOT_MINUTES', 30);

// Start the session once, app-wide. Every protected page includes this file.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';
