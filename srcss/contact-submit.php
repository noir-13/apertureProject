<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message']);

    if (empty($name) || empty($email) || empty($message)) {
        setFlashMessage('All fields are required.', 'error');
        redirect(SITE_URL . '/index.php#contact');
    }

    if (!validateEmail($email)) {
        setFlashMessage('Invalid email address.', 'error');
        redirect(SITE_URL . '/index.php#contact');
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $subject, $message]);

        setFlashMessage('Thank you for contacting us! We will respond within 24 hours.', 'success');
        redirect(SITE_URL . '/index.php#contact');
    } catch (PDOException $e) {
        error_log("Contact form error: " . $e->getMessage());
        setFlashMessage('An error occurred. Please try again later.', 'error');
        redirect(SITE_URL . '/index.php#contact');
    }
} else {
    redirect(SITE_URL . '/index.php');
}
