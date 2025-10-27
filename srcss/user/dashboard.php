<?php
$page_title = "Client Dashboard";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$user_id = getCurrentUserId();
$user = getUserById($user_id);

$db = getDB();

// Get booking statistics
$stmt = $db->prepare("
    SELECT
        COUNT(*) as total_bookings,
        SUM(CASE WHEN booking_status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
        SUM(CASE WHEN booking_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
        SUM(CASE WHEN booking_status = 'completed' THEN 1 ELSE 0 END) as completed_bookings
    FROM bookings
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// Get upcoming bookings
$stmt = $db->prepare("
    SELECT b.*, s.service_name, s.duration_hours
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    WHERE b.user_id = ?
    AND b.booking_date >= CURDATE()
    AND b.booking_status IN ('pending', 'confirmed')
    ORDER BY b.booking_date ASC
    LIMIT 5
");
$stmt->execute([$user_id]);
$upcoming_bookings = $stmt->fetchAll();

// Get recent notifications
$stmt = $db->prepare("
    SELECT * FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

// Get unread notification count
$stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
$stmt->execute([$user_id]);
$unread_count = $stmt->fetch()['count'];

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.dashboard-container {
    min-height: 100vh;
    background: var(--color-beige);
    padding-top: 100px;
    padding-bottom: var(--spacing-2xl);
}

.dashboard-header {
    background: var(--gradient-dark);
    color: var(--color-white);
    padding: var(--spacing-xl) 0;
    margin-bottom: var(--spacing-xl);
}

.stat-card {
    background: var(--color-white);
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    box-shadow: var(--shadow-md);
    text-align: center;
    transition: var(--transition-base);
    border-left: 4px solid;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.stat-card.primary { border-color: var(--color-gold); }
.stat-card.warning { border-color: #ffc107; }
.stat-card.success { border-color: #28a745; }
.stat-card.info { border-color: #17a2b8; }

.stat-number {
    font-size: 3rem;
    font-weight: 700;
    font-family: var(--font-heading);
    color: var(--color-gold);
    line-height: 1;
    margin-bottom: var(--spacing-sm);
}

.stat-label {
    color: #666;
    font-weight: 500;
    text-transform: uppercase;
    font-size: 14px;
    letter-spacing: 0.5px;
}

.booking-item {
    background: var(--color-white);
    border-radius: var(--radius-md);
    padding: var(--spacing-md);
    margin-bottom: var(--spacing-md);
    border-left: 4px solid var(--color-gold);
    transition: var(--transition-base);
}

.booking-item:hover {
    box-shadow: var(--shadow-md);
    transform: translateX(4px);
}

.notification-item {
    background: var(--color-white);
    border-radius: var(--radius-md);
    padding: var(--spacing-md);
    margin-bottom: var(--spacing-sm);
    border-left: 3px solid #17a2b8;
    transition: var(--transition-base);
}

.notification-item:hover {
    background: #f8f9fa;
}

.notification-item.unread {
    background: #e3f2fd;
    border-left-color: var(--color-gold);
}
</style>

<div class="dashboard-container">
    <div class="dashboard-header">
        <div class="container">
            <h1 style="color: var(--color-white); margin-bottom: var(--spacing-sm);">
                Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!
            </h1>
            <p style="color: var(--color-beige); font-size: 1.1rem;">
                Manage your bookings and track your events all in one place
            </p>
        </div>
    </div>

    <div class="container">
        <!-- Statistics Cards -->
        <div class="row" style="margin-bottom: var(--spacing-xl);">
            <div class="col-12 col-md-3">
                <div class="stat-card primary">
                    <div class="stat-number"><?php echo $stats['total_bookings']; ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="stat-card warning">
                    <div class="stat-number"><?php echo $stats['pending_bookings']; ?></div>
                    <div class="stat-label">Pending Approval</div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="stat-card success">
                    <div class="stat-number"><?php echo $stats['confirmed_bookings']; ?></div>
                    <div class="stat-label">Confirmed</div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="stat-card info">
                    <div class="stat-number"><?php echo $stats['completed_bookings']; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Upcoming Bookings -->
            <div class="col-12 col-md-8" style="margin-bottom: var(--spacing-lg);">
                <div class="card">
                    <div class="card-header">
                        <h3 style="margin: 0;">
                            <i class="bi bi-calendar-check"></i> Upcoming Bookings
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcoming_bookings)): ?>
                            <div style="text-align: center; padding: var(--spacing-xl); color: #999;">
                                <i class="bi bi-calendar-x" style="font-size: 64px; margin-bottom: var(--spacing-md);"></i>
                                <p>No upcoming bookings</p>
                                <a href="<?php echo SITE_URL; ?>/user/booking.php" class="btn btn-primary">
                                    Book a Service
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($upcoming_bookings as $booking): ?>
                                <div class="booking-item">
                                    <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: var(--spacing-sm);">
                                        <div style="flex: 1;">
                                            <h4 style="margin-bottom: var(--spacing-xs);">
                                                <?php echo htmlspecialchars($booking['service_name']); ?>
                                            </h4>
                                            <div style="color: #666; font-size: 14px; margin-bottom: var(--spacing-xs);">
                                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($booking['event_location']); ?>
                                            </div>
                                            <div style="display: flex; gap: var(--spacing-md); flex-wrap: wrap;">
                                                <span style="font-size: 14px; color: #555;">
                                                    <i class="bi bi-calendar3"></i> <?php echo formatDate($booking['booking_date']); ?>
                                                </span>
                                                <span style="font-size: 14px; color: #555;">
                                                    <i class="bi bi-clock"></i> <?php echo date('g:i A', strtotime($booking['booking_time'])); ?>
                                                </span>
                                                <span style="font-size: 14px; color: #555;">
                                                    <i class="bi bi-hourglass"></i> <?php echo $booking['duration_hours']; ?> hours
                                                </span>
                                            </div>
                                        </div>
                                        <div style="text-align: right;">
                                            <span class="badge badge-<?php echo getStatusBadgeClass($booking['booking_status']); ?>" style="margin-bottom: var(--spacing-sm); display: block;">
                                                <?php echo ucfirst($booking['booking_status']); ?>
                                            </span>
                                            <div style="font-weight: 700; color: var(--color-gold); font-size: 1.2rem;">
                                                <?php echo formatCurrency($booking['total_amount']); ?>
                                            </div>
                                            <a href="<?php echo SITE_URL; ?>/user/my-bookings.php?id=<?php echo $booking['booking_id']; ?>"
                                               class="btn btn-sm btn-outline" style="margin-top: var(--spacing-xs);">
                                                View Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div style="text-align: center; margin-top: var(--spacing-md);">
                                <a href="<?php echo SITE_URL; ?>/user/my-bookings.php" class="btn btn-secondary">
                                    View All Bookings
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card" style="margin-top: var(--spacing-lg);">
                    <div class="card-header">
                        <h3 style="margin: 0;">
                            <i class="bi bi-lightning"></i> Quick Actions
                        </h3>
                    </div>
                    <div class="card-body">
                        <div style="display: flex; gap: var(--spacing-md); flex-wrap: wrap;">
                            <a href="<?php echo SITE_URL; ?>/user/booking.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> New Booking
                            </a>
                            <a href="<?php echo SITE_URL; ?>/user/my-bookings.php" class="btn btn-secondary">
                                <i class="bi bi-list-ul"></i> My Bookings
                            </a>
                            <a href="<?php echo SITE_URL; ?>/user/profile.php" class="btn btn-outline">
                                <i class="bi bi-person-gear"></i> Edit Profile
                            </a>
                            <a href="<?php echo SITE_URL; ?>/index.php#portfolio" class="btn btn-outline">
                                <i class="bi bi-images"></i> View Portfolio
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            <div class="col-12 col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 style="margin: 0; display: flex; justify-content: space-between; align-items: center;">
                            <span><i class="bi bi-bell"></i> Notifications</span>
                            <?php if ($unread_count > 0): ?>
                                <span class="badge badge-danger"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </h3>
                    </div>
                    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                        <?php if (empty($notifications)): ?>
                            <div style="text-align: center; padding: var(--spacing-lg); color: #999;">
                                <i class="bi bi-bell-slash" style="font-size: 48px; margin-bottom: var(--spacing-sm);"></i>
                                <p>No notifications</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: var(--spacing-xs);">
                                        <strong style="color: var(--color-charcoal); font-size: 14px;">
                                            <?php echo htmlspecialchars($notification['title']); ?>
                                        </strong>
                                        <?php if (!$notification['is_read']): ?>
                                            <span class="badge badge-primary" style="font-size: 10px;">NEW</span>
                                        <?php endif; ?>
                                    </div>
                                    <p style="font-size: 13px; color: #666; margin-bottom: var(--spacing-xs);">
                                        <?php echo htmlspecialchars($notification['message']); ?>
                                    </p>
                                    <div style="font-size: 11px; color: #999;">
                                        <i class="bi bi-clock"></i> <?php echo timeAgo($notification['created_at']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Account Info -->
                <div class="card" style="margin-top: var(--spacing-lg);">
                    <div class="card-header">
                        <h3 style="margin: 0;">
                            <i class="bi bi-person-circle"></i> Account Info
                        </h3>
                    </div>
                    <div class="card-body">
                        <div style="text-align: center; margin-bottom: var(--spacing-md);">
                            <img src="<?php echo SITE_URL; ?>/uploads/profiles/<?php echo htmlspecialchars($user['profile_photo']); ?>"
                                 alt="Profile"
                                 style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 4px solid var(--color-gold);"
                                 onerror="this.src='<?php echo SITE_URL; ?>/assets/images/default-avatar.png'">
                        </div>
                        <div style="text-align: center;">
                            <h4 style="margin-bottom: var(--spacing-xs);"><?php echo htmlspecialchars($user['full_name']); ?></h4>
                            <p style="color: #666; font-size: 14px; margin-bottom: var(--spacing-xs);">
                                <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
                            </p>
                            <?php if ($user['phone']): ?>
                                <p style="color: #666; font-size: 14px; margin-bottom: var(--spacing-xs);">
                                    <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($user['phone']); ?>
                                </p>
                            <?php endif; ?>
                            <span class="badge badge-success" style="margin-top: var(--spacing-sm);">
                                <?php echo ucfirst($user['account_status']); ?>
                            </span>
                        </div>
                        <div style="margin-top: var(--spacing-md); padding-top: var(--spacing-md); border-top: 1px solid #eee;">
                            <div style="font-size: 12px; color: #999; text-align: center;">
                                Member since <?php echo formatDate($user['created_at'], 'M Y'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
