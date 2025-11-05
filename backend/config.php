<?php
/**
 * Configuration File
 * Contains database credentials and application constants
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'pg_finder');
define('DB_USER', 'root');
define('DB_PASS', ''); // Empty password for XAMPP default
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('SITE_NAME', 'PG/Flat Finder');
define('BASE_URL', 'http://localhost/pg-finder');
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');
define('UPLOAD_URL', BASE_URL . '/uploads/');

// File Upload Settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg', 'image/webp']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);
define('MAX_IMAGES_PER_LISTING', 5);

// Session Configuration
define('SESSION_LIFETIME', 3600 * 24); // 24 hours

// Pagination
define('LISTINGS_PER_PAGE', 12);

// Security
define('PASSWORD_MIN_LENGTH', 6);

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
