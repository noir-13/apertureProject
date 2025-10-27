<?php

/**
 * Application Configuration
 * ApertureStudios Videography Appointment System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Manila');

// Site URLs and Paths
define('SITE_URL', 'http://localhost/aperturestudios/src');
define('SITE_NAME', 'ApertureStudios');
define('BASE_PATH', __DIR__ . '/..');

// Directory paths
define('UPLOAD_DIR', BASE_PATH . '/uploads/');
define('PROFILE_UPLOAD_DIR', UPLOAD_DIR . 'profiles/');
define('PAYMENT_PROOF_DIR', UPLOAD_DIR . 'payments/');
define('PORTFOLIO_DIR', UPLOAD_DIR . 'portfolio/');

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'noreply@aperturestudios.com');
define('SMTP_FROM_NAME', 'ApertureStudios');

// Application Settings
define('DOWNPAYMENT_PERCENTAGE', 30); // 30% downpayment required
define('BOOKING_ADVANCE_DAYS', 14); // Minimum days in advance for booking
define('CANCELLATION_PERIOD_DAYS', 7); // Days before event to request cancellation
define('ITEMS_PER_PAGE', 10);

// Security
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// File Upload Limits
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif']);
define('ALLOWED_DOCUMENT_TYPES', ['application/pdf', 'image/jpeg', 'image/png']);

// Payment Methods
define('PAYMENT_METHODS', [
    'gcash' => 'GCash',
    'bank_transfer' => 'Bank Transfer',
    'credit_card' => 'Credit Card',
    'paymaya' => 'PayMaya',
    'cash' => 'Cash'
]);

// Booking Status
define('BOOKING_STATUS', [
    'pending' => 'Pending Approval',
    'confirmed' => 'Confirmed',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
    'rejected' => 'Rejected'
]);

// Payment Status
define('PAYMENT_STATUS', [
    'unpaid' => 'Unpaid',
    'downpayment_paid' => 'Downpayment Paid',
    'fully_paid' => 'Fully Paid'
]);

// Auto-create directories if they don't exist
$directories = [
    UPLOAD_DIR,
    PROFILE_UPLOAD_DIR,
    PAYMENT_PROOF_DIR,
    PORTFOLIO_DIR
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Include database connection
require_once __DIR__ . '/database.php';
