<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ApertureStudios - Premium Videography and Photography Services">
    <meta name="keywords" content="videography, photography, wedding, events, corporate">
    <meta name="author" content="ApertureStudios">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>ApertureStudios</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo SITE_URL; ?>/assets/images/favicon.png">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar" id="mainNavbar">
        <div class="navbar-container">
            <a href="<?php echo SITE_URL; ?>/index.php" class="navbar-brand">ApertureStudios</a>

            <ul class="navbar-menu" id="navbarMenu">
                <?php if (!isLoggedIn()): ?>
                    <li><a href="<?php echo SITE_URL; ?>/index.php">Home</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/index.php#services">Services</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/index.php#portfolio">Portfolio</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/index.php#pricing">Pricing</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/index.php#contact">Contact</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/login.php">Login</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/register.php" class="btn btn-primary btn-sm">Book Now</a></li>
                <?php elseif (isAdmin()): ?>
                    <li><a href="<?php echo SITE_URL; ?>/admin/dashboard.php">Dashboard</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/admin/bookings.php">Bookings</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/admin/users.php">Users</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/admin/refunds.php">Refunds</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/admin/reports.php">Reports</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/logout.php" class="btn btn-outline btn-sm">Logout</a></li>
                <?php else: ?>
                    <li><a href="<?php echo SITE_URL; ?>/user/dashboard.php">Dashboard</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/user/booking.php">Book Now</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/user/my-bookings.php">My Bookings</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/user/profile.php">Profile</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/logout.php" class="btn btn-outline btn-sm">Logout</a></li>
                <?php endif; ?>
            </ul>

            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <i class="bi bi-list"></i>
            </button>
        </div>
    </nav>

    <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?> flash-message" style="position: fixed; top: 80px; right: 20px; z-index: 9999; max-width: 400px; animation: slideInRight 0.3s ease;">
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <script>
            setTimeout(() => {
                const flash = document.querySelector('.flash-message');
                if (flash) {
                    flash.style.animation = 'fadeOut 0.3s ease';
                    setTimeout(() => flash.remove(), 300);
                }
            }, 5000);
        </script>
    <?php endif; ?>
