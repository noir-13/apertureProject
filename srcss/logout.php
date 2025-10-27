<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    $user_id = getCurrentUserId();
    logActivity($user_id, 'logout', 'User logged out');

    // Destroy session
    session_unset();
    session_destroy();
}

setFlashMessage('You have been logged out successfully.', 'success');
redirect(SITE_URL . '/login.php');
