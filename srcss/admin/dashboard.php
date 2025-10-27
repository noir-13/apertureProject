<?php
$page_title = "Admin Dashboard";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$db = getDB();

// Get statistics
$stats = [];

// Total users
$result = $db->query("SELECT COUNT(*) as count FROM users WHERE role != 'admin' AND account_status = 'active'")->fetch();
$stats['total_users'] = $result['count'];

// Total bookings
$result = $db->query("SELECT COUNT(*) as count FROM bookings")->fetch();
$stats['total_bookings'] = $result['count'];

// Pending bookings
$result = $db->query("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'pending'")->fetch();
$stats['pending_bookings'] = $result['count'];

// This month revenue
$result = $db->query("
    SELECT COALESCE(SUM(amount), 0) as revenue
    FROM payments
    WHERE payment_status = 'completed'
    AND MONTH(payment_date) = MONTH(CURRENT_DATE())
    AND YEAR(payment_date) = YEAR(CURRENT_DATE())
")->fetch();
$stats['monthly_revenue'] = $result['revenue'];

// Total revenue
$result = $db->query("
    SELECT COALESCE(SUM(amount), 0) as revenue
    FROM payments
    WHERE payment_status = 'completed'
")->fetch();
$stats['total_revenue'] = $result['revenue'];

// Pending refunds
$result = $db->query("SELECT COUNT(*) as count FROM refund_requests WHERE request_status = 'pending'")->fetch();
$stats['pending_refunds'] = $result['count'];

// Recent bookings
$recent_bookings = $db->query("
    SELECT b.*, s.service_name, u.full_name, u.email
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN users u ON b.user_id = u.user_id
    ORDER BY b.created_at DESC
    LIMIT 10
")->fetchAll();

// Upcoming events
$upcoming_events = $db->query("
    SELECT b.*, s.service_name, u.full_name, u.email
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN users u ON b.user_id = u.user_id
    WHERE b.booking_date >= CURDATE()
    AND b.booking_status = 'confirmed'
    ORDER BY b.booking_date ASC
    LIMIT 5
")->fetchAll();

// Monthly revenue chart data (last 6 months)
$monthly_data = $db->query("
    SELECT
        DATE_FORMAT(payment_date, '%Y-%m') as month,
        SUM(amount) as revenue
    FROM payments
    WHERE payment_status = 'completed'
    AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
    ORDER BY month ASC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.admin-dashboard {
    min-height: 100vh;
    background: #f4f6f9;
    padding-top: 80px;
}

.admin-header {
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
    transition: var(--transition-base);
    border-left: 4px solid;
    height: 100%;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.stat-card.gold { border-color: var(--color-gold); }
.stat-card.blue { border-color: #17a2b8; }
.stat-card.green { border-color: #28a745; }
.stat-card.orange { border-color: #ffc107; }
.stat-card.red { border-color: #dc3545; }
.stat-card.purple { border-color: #6f42c1; }

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    margin-bottom: var(--spacing-md);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    font-family: var(--font-heading);
    line-height: 1;
    margin-bottom: var(--spacing-xs);
}

.stat-label {
    color: #666;
    font-size: 14px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: var(--color-beige);
    padding: var(--spacing-md);
    text-align: left;
    font-weight: 600;
    color: var(--color-charcoal);
    border-bottom: 2px solid var(--color-gold);
}

.data-table td {
    padding: var(--spacing-md);
    border-bottom: 1px solid #eee;
}

.data-table tr:hover {
    background: #f8f9fa;
}
</style>

<div class="admin-dashboard">
    <div class="admin-header">
        <div class="container">
            <h1>Admin Dashboard</h1>
            <p style="color: var(--color-beige); font-size: 1.1rem;">
                Welcome back! Here's what's happening today.
            </p>
        </div>
    </div>

    <div class="container">
        <!-- Statistics Cards -->
        <div class="row" style="margin-bottom: var(--spacing-xl);">
            <div class="col-12 col-md-4" style="margin-bottom: var(--spacing-md);">
                <div class="stat-card blue">
                    <div class="stat-icon" style="background: rgba(23, 162, 184, 0.1); color: #17a2b8;">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stat-number" style="color: #17a2b8;"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-label">Total Clients</div>
                </div>
            </div>

            <div class="col-12 col-md-4" style="margin-bottom: var(--spacing-md);">
                <div class="stat-card green">
                    <div class="stat-icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="stat-number" style="color: #28a745;"><?php echo $stats['total_bookings']; ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
            </div>

            <div class="col-12 col-md-4" style="margin-bottom: var(--spacing-md);">
                <div class="stat-card orange">
                    <div class="stat-icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="stat-number" style="color: #ffc107;"><?php echo $stats['pending_bookings']; ?></div>
                    <div class="stat-label">Pending Approvals</div>
                </div>
            </div>

            <div class="col-12 col-md-4" style="margin-bottom: var(--spacing-md);">
                <div class="stat-card gold">
                    <div class="stat-icon" style="background: rgba(200, 169, 81, 0.1); color: var(--color-gold);">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div class="stat-number" style="color: var(--color-gold);">
                        <?php echo formatCurrency($stats['monthly_revenue']); ?>
                    </div>
                    <div class="stat-label">This Month Revenue</div>
                </div>
            </div>

            <div class="col-12 col-md-4" style="margin-bottom: var(--spacing-md);">
                <div class="stat-card purple">
                    <div class="stat-icon" style="background: rgba(111, 66, 193, 0.1); color: #6f42c1;">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <div class="stat-number" style="color: #6f42c1;">
                        <?php echo formatCurrency($stats['total_revenue']); ?>
                    </div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>

            <div class="col-12 col-md-4" style="margin-bottom: var(--spacing-md);">
                <div class="stat-card red">
                    <div class="stat-icon" style="background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                        <i class="bi bi-arrow-return-left"></i>
                    </div>
                    <div class="stat-number" style="color: #dc3545;"><?php echo $stats['pending_refunds']; ?></div>
                    <div class="stat-label">Pending Refunds</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Bookings -->
            <div class="col-12 col-md-8" style="margin-bottom: var(--spacing-lg);">
                <div class="card">
                    <div class="card-header">
                        <h3 style="margin: 0; display: flex; justify-content: space-between; align-items: center;">
                            <span><i class="bi bi-list-ul"></i> Recent Bookings</span>
                            <a href="<?php echo SITE_URL; ?>/admin/bookings.php" class="btn btn-sm btn-outline">View All</a>
                        </h3>
                    </div>
                    <div class="card-body" style="padding: 0; overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Client</th>
                                    <th>Service</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_bookings as $booking): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong></td>
                                        <td>
                                            <div><?php echo htmlspecialchars($booking['full_name']); ?></div>
                                            <small style="color: #666;"><?php echo htmlspecialchars($booking['email']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                        <td><?php echo formatDate($booking['booking_date'], 'M j'); ?></td>
                                        <td><strong><?php echo formatCurrency($booking['total_amount']); ?></strong></td>
                                        <td>
                                            <span class="badge badge-<?php echo getStatusBadgeClass($booking['booking_status']); ?>">
                                                <?php echo ucfirst($booking['booking_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo SITE_URL; ?>/admin/bookings.php?id=<?php echo $booking['booking_id']; ?>"
                                               class="btn btn-sm btn-outline">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Upcoming Events & Quick Actions -->
            <div class="col-12 col-md-4">
                <div class="card" style="margin-bottom: var(--spacing-lg);">
                    <div class="card-header">
                        <h4 style="margin: 0;"><i class="bi bi-calendar-event"></i> Upcoming Events</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcoming_events)): ?>
                            <p style="text-align: center; color: #999; padding: var(--spacing-lg);">
                                No upcoming events
                            </p>
                        <?php else: ?>
                            <?php foreach ($upcoming_events as $event): ?>
                                <div style="padding: var(--spacing-sm); margin-bottom: var(--spacing-sm); border-left: 3px solid var(--color-gold); background: #f8f9fa; border-radius: var(--radius-sm);">
                                    <div style="font-weight: 600; margin-bottom: var(--spacing-xs);">
                                        <?php echo htmlspecialchars($event['service_name']); ?>
                                    </div>
                                    <div style="font-size: 13px; color: #666;">
                                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($event['full_name']); ?>
                                    </div>
                                    <div style="font-size: 13px; color: #666;">
                                        <i class="bi bi-calendar3"></i> <?php echo formatDate($event['booking_date']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4 style="margin: 0;"><i class="bi bi-lightning"></i> Quick Actions</h4>
                    </div>
                    <div class="card-body">
                        <a href="<?php echo SITE_URL; ?>/admin/bookings.php?filter=pending" class="btn btn-primary btn-block" style="margin-bottom: var(--spacing-sm);">
                            <i class="bi bi-check-circle"></i> Review Pending Bookings
                        </a>
                        <a href="<?php echo SITE_URL; ?>/admin/refunds.php" class="btn btn-secondary btn-block" style="margin-bottom: var(--spacing-sm);">
                            <i class="bi bi-arrow-return-left"></i> Manage Refunds
                        </a>
                        <a href="<?php echo SITE_URL; ?>/admin/users.php" class="btn btn-outline btn-block" style="margin-bottom: var(--spacing-sm);">
                            <i class="bi bi-people"></i> Manage Users
                        </a>
                        <a href="<?php echo SITE_URL; ?>/admin/reports.php" class="btn btn-outline btn-block">
                            <i class="bi bi-graph-up"></i> View Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
