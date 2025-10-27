<?php
/**
 * Helper Functions
 * ApertureStudios Videography Appointment System
 */

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Generate booking reference
 */
function generateBookingReference() {
    return 'APT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Generate payment reference
 */
function generatePaymentReference() {
    return 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 */
function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

/**
 * Require admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/user/dashboard.php');
        exit;
    }
}

/**
 * Redirect to URL
 */
function redirect($url, $permanent = false) {
    if ($permanent) {
        header("HTTP/1.1 301 Moved Permanently");
    }
    header("Location: " . $url);
    exit;
}

/**
 * Set flash message
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type // success, error, warning, info
    ];
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

/**
 * Format date
 */
function formatDate($date, $format = 'F j, Y') {
    return date($format, strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime($datetime, $format = 'F j, Y g:i A') {
    return date($format, strtotime($datetime));
}

/**
 * Time ago format
 */
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return formatDate($datetime);
    }
}

/**
 * Upload file
 */
function uploadFile($file, $destination_dir, $allowed_types, $max_size = MAX_FILE_SIZE) {
    $errors = [];

    // Check if file was uploaded
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'error' => 'No file uploaded'];
    }

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload error'];
    }

    // Check file size
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'File size exceeds limit'];
    }

    // Check file type
    $file_type = mime_content_type($file['tmp_name']);
    if (!in_array($file_type, $allowed_types)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $destination = $destination_dir . $filename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'filename' => $filename, 'path' => $destination];
    } else {
        return ['success' => false, 'error' => 'Failed to save file'];
    }
}

/**
 * Calculate downpayment
 */
function calculateDownpayment($total_amount) {
    return $total_amount * (DOWNPAYMENT_PERCENTAGE / 100);
}

/**
 * Get booking status badge class
 */
function getStatusBadgeClass($status) {
    $classes = [
        'pending' => 'warning',
        'confirmed' => 'success',
        'completed' => 'info',
        'cancelled' => 'secondary',
        'rejected' => 'danger'
    ];
    return $classes[$status] ?? 'secondary';
}

/**
 * Get payment status badge class
 */
function getPaymentStatusBadgeClass($status) {
    $classes = [
        'unpaid' => 'danger',
        'downpayment_paid' => 'warning',
        'fully_paid' => 'success',
        'pending' => 'warning',
        'completed' => 'success',
        'failed' => 'danger',
        'refunded' => 'info'
    ];
    return $classes[$status] ?? 'secondary';
}

/**
 * Log activity
 */
function logActivity($user_id, $action_type, $description) {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action_type, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id,
            $action_type,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (PDOException $e) {
        error_log("Activity log error: " . $e->getMessage());
    }
}

/**
 * Create notification
 */
function createNotification($user_id, $title, $message, $type, $related_id = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, notification_type, related_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $message, $type, $related_id]);
        return true;
    } catch (PDOException $e) {
        error_log("Notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send email notification (placeholder - requires PHPMailer)
 */
function sendEmail($to, $subject, $body, $altBody = '') {
    // This will be implemented with PHPMailer
    // For now, return true
    return true;
}

/**
 * Check if date is available for booking
 */
function isDateAvailable($date, $exclude_booking_id = null) {
    try {
        $db = getDB();
        $sql = "SELECT COUNT(*) as count FROM bookings
                WHERE booking_date = ?
                AND booking_status IN ('pending', 'confirmed')";

        $params = [$date];

        if ($exclude_booking_id) {
            $sql .= " AND booking_id != ?";
            $params[] = $exclude_booking_id;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        // Allow maximum 3 bookings per day
        return $result['count'] < 3;
    } catch (PDOException $e) {
        error_log("Date availability check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate password strength
 */
function validatePasswordStrength($password) {
    $errors = [];

    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters long";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }

    return $errors;
}

/**
 * Get user by ID
 */
function getUserById($user_id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ? AND account_status != 'deleted'");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get user error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get service by ID
 */
function getServiceById($service_id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM services WHERE service_id = ? AND is_active = TRUE");
        $stmt->execute([$service_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get service error: " . $e->getMessage());
        return null;
    }
}
