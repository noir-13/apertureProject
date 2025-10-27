<?php
$page_title = "Reports & Analytics";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$db = getDB();

// Get date range from request
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-t'); // Last day of current month

// Revenue Statistics
$revenue_stats = $db->query("
    SELECT
        COALESCE(SUM(amount), 0) as total_revenue,
        COALESCE(SUM(CASE WHEN payment_type = 'downpayment' THEN amount ELSE 0 END), 0) as downpayments,
        COALESCE(SUM(CASE WHEN payment_type = 'remaining_balance' THEN amount ELSE 0 END), 0) as balance_payments,
        COALESCE(SUM(refund_amount), 0) as total_refunds,
        COUNT(*) as total_transactions
    FROM payments
    WHERE payment_status = 'completed'
    AND payment_date BETWEEN '{$start_date}' AND '{$end_date}'
")->fetch();

// Booking Statistics
$booking_stats = $db->query("
    SELECT
        COUNT(*) as total_bookings,
        SUM(CASE WHEN booking_status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN booking_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN booking_status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
        SUM(CASE WHEN booking_status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM bookings
    WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}'
")->fetch();

// Service Performance
$service_performance = $db->query("
    SELECT
        s.service_name,
        COUNT(b.booking_id) as booking_count,
        COALESCE(SUM(b.total_amount), 0) as total_revenue,
        COALESCE(AVG(b.total_amount), 0) as avg_booking_value
    FROM services s
    LEFT JOIN bookings b ON s.service_id = b.service_id
        AND b.created_at BETWEEN '{$start_date}' AND '{$end_date}'
        AND b.booking_status != 'cancelled'
    GROUP BY s.service_id, s.service_name
    ORDER BY booking_count DESC
")->fetchAll();

// Top Clients
$top_clients = $db->query("
    SELECT
        u.full_name,
        u.email,
        COUNT(b.booking_id) as booking_count,
        COALESCE(SUM(b.total_amount), 0) as total_spent
    FROM users u
    INNER JOIN bookings b ON u.user_id = b.user_id
    WHERE b.created_at BETWEEN '{$start_date}' AND '{$end_date}'
        AND b.booking_status IN ('confirmed', 'completed')
    GROUP BY u.user_id, u.full_name, u.email
    ORDER BY total_spent DESC
    LIMIT 10
")->fetchAll();

// Monthly Revenue Trend (last 6 months)
$monthly_trend = $db->query("
    SELECT
        DATE_FORMAT(payment_date, '%Y-%m') as month,
        DATE_FORMAT(payment_date, '%M %Y') as month_name,
        COALESCE(SUM(amount), 0) as revenue,
        COUNT(*) as transaction_count
    FROM payments
    WHERE payment_status = 'completed'
        AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
    ORDER BY month ASC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.admin-container {
    min-height: 100vh;
    background: #f4f6f9;
    padding-top: 80px;
    padding-bottom: var(--spacing-2xl);
}

.report-card {
    background: var(--color-white);
    border-radius: var(--radius-lg);
    padding: var(--spacing-xl);
    margin-bottom: var(--spacing-lg);
    box-shadow: var(--shadow-md);
}

.stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
}

.mini-stat {
    background: var(--gradient-dark);
    color: var(--color-white);
    padding: var(--spacing-lg);
    border-radius: var(--radius-lg);
    text-align: center;
}

.mini-stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-gold);
    margin-bottom: var(--spacing-xs);
}

.mini-stat-label {
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--color-beige);
}

.chart-container {
    position: relative;
    height: 300px;
    margin: var(--spacing-lg) 0;
}

.progress-bar {
    background: #e0e0e0;
    height: 24px;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: var(--spacing-sm);
}

.progress-fill {
    height: 100%;
    background: var(--gradient-gold);
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding: 0 var(--spacing-sm);
    font-size: 12px;
    font-weight: 600;
    transition: width 1s ease;
}

.date-filter {
    background: var(--color-white);
    padding: var(--spacing-lg);
    border-radius: var(--radius-lg);
    margin-bottom: var(--spacing-lg);
    box-shadow: var(--shadow-md);
}
</style>

<div class="admin-container">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg);">
            <h1><i class="bi bi-graph-up-arrow"></i> Reports & Analytics</h1>
            <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="btn btn-outline">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Date Range Filter -->
        <div class="date-filter">
            <form method="GET" style="display: flex; gap: var(--spacing-md); align-items: end; flex-wrap: wrap;">
                <div class="form-group" style="flex: 1; min-width: 200px; margin: 0;">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control"
                           value="<?php echo htmlspecialchars($start_date); ?>" required>
                </div>
                <div class="form-group" style="flex: 1; min-width: 200px; margin: 0;">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control"
                           value="<?php echo htmlspecialchars($end_date); ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-filter"></i> Filter
                </button>
                <a href="<?php echo SITE_URL; ?>/admin/reports.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>

        <div style="background: var(--color-beige); padding: var(--spacing-md); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg); text-align: center;">
            <strong>Showing data from:</strong>
            <?php echo formatDate($start_date); ?> to <?php echo formatDate($end_date); ?>
        </div>

        <!-- Revenue Overview -->
        <div class="report-card">
            <h2 style="margin-bottom: var(--spacing-lg); display: flex; align-items: center; gap: var(--spacing-sm);">
                <i class="bi bi-cash-stack" style="color: var(--color-gold);"></i>
                Revenue Overview
            </h2>

            <div class="stat-grid">
                <div class="mini-stat">
                    <div class="mini-stat-value"><?php echo formatCurrency($revenue_stats['total_revenue']); ?></div>
                    <div class="mini-stat-label">Total Revenue</div>
                </div>
                <div class="mini-stat">
                    <div class="mini-stat-value"><?php echo formatCurrency($revenue_stats['downpayments']); ?></div>
                    <div class="mini-stat-label">Downpayments</div>
                </div>
                <div class="mini-stat">
                    <div class="mini-stat-value"><?php echo formatCurrency($revenue_stats['balance_payments']); ?></div>
                    <div class="mini-stat-label">Balance Payments</div>
                </div>
                <div class="mini-stat">
                    <div class="mini-stat-value"><?php echo formatCurrency($revenue_stats['total_refunds']); ?></div>
                    <div class="mini-stat-label">Refunds</div>
                </div>
                <div class="mini-stat">
                    <div class="mini-stat-value"><?php echo $revenue_stats['total_transactions']; ?></div>
                    <div class="mini-stat-label">Transactions</div>
                </div>
            </div>
        </div>

        <!-- Booking Statistics -->
        <div class="report-card">
            <h2 style="margin-bottom: var(--spacing-lg); display: flex; align-items: center; gap: var(--spacing-sm);">
                <i class="bi bi-calendar-check" style="color: var(--color-gold);"></i>
                Booking Statistics
            </h2>

            <div class="stat-grid">
                <div class="mini-stat" style="background: var(--color-charcoal);">
                    <div class="mini-stat-value"><?php echo $booking_stats['total_bookings']; ?></div>
                    <div class="mini-stat-label">Total Bookings</div>
                </div>
                <div class="mini-stat" style="background: #ffc107;">
                    <div class="mini-stat-value" style="color: #000;"><?php echo $booking_stats['pending']; ?></div>
                    <div class="mini-stat-label" style="color: #000;">Pending</div>
                </div>
                <div class="mini-stat" style="background: #28a745;">
                    <div class="mini-stat-value" style="color: #fff;"><?php echo $booking_stats['confirmed']; ?></div>
                    <div class="mini-stat-label" style="color: #fff;">Confirmed</div>
                </div>
                <div class="mini-stat" style="background: #17a2b8;">
                    <div class="mini-stat-value" style="color: #fff;"><?php echo $booking_stats['completed']; ?></div>
                    <div class="mini-stat-label" style="color: #fff;">Completed</div>
                </div>
                <div class="mini-stat" style="background: #dc3545;">
                    <div class="mini-stat-value" style="color: #fff;"><?php echo $booking_stats['cancelled'] + $booking_stats['rejected']; ?></div>
                    <div class="mini-stat-label" style="color: #fff;">Cancelled/Rejected</div>
                </div>
            </div>
        </div>

        <!-- Service Performance -->
        <div class="report-card">
            <h2 style="margin-bottom: var(--spacing-lg); display: flex; align-items: center; gap: var(--spacing-sm);">
                <i class="bi bi-bar-chart" style="color: var(--color-gold);"></i>
                Service Performance
            </h2>

            <?php if (empty($service_performance) || array_sum(array_column($service_performance, 'booking_count')) === 0): ?>
                <p style="text-align: center; color: #999; padding: var(--spacing-xl);">No booking data for this period</p>
            <?php else: ?>
                <?php
                $max_bookings = max(array_column($service_performance, 'booking_count'));
                foreach ($service_performance as $service):
                    $percentage = $max_bookings > 0 ? ($service['booking_count'] / $max_bookings) * 100 : 0;
                ?>
                    <div style="margin-bottom: var(--spacing-lg);">
                        <div style="display: flex; justify-content: space-between; margin-bottom: var(--spacing-xs);">
                            <strong><?php echo htmlspecialchars($service['service_name']); ?></strong>
                            <span><?php echo $service['booking_count']; ?> bookings | <?php echo formatCurrency($service['total_revenue']); ?></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%;">
                                <?php echo number_format($percentage, 1); ?>%
                            </div>
                        </div>
                        <div style="font-size: 13px; color: #666;">
                            Average booking value: <?php echo formatCurrency($service['avg_booking_value']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Top Clients -->
        <div class="report-card">
            <h2 style="margin-bottom: var(--spacing-lg); display: flex; align-items: center; gap: var(--spacing-sm);">
                <i class="bi bi-trophy" style="color: var(--color-gold);"></i>
                Top Clients
            </h2>

            <?php if (empty($top_clients)): ?>
                <p style="text-align: center; color: #999; padding: var(--spacing-xl);">No client data for this period</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Client Name</th>
                                <th>Email</th>
                                <th>Bookings</th>
                                <th>Total Spent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_clients as $index => $client): ?>
                                <tr>
                                    <td>
                                        <?php if ($index === 0): ?>
                                            <span style="font-size: 24px; color: #FFD700;">🥇</span>
                                        <?php elseif ($index === 1): ?>
                                            <span style="font-size: 24px; color: #C0C0C0;">🥈</span>
                                        <?php elseif ($index === 2): ?>
                                            <span style="font-size: 24px; color: #CD7F32;">🥉</span>
                                        <?php else: ?>
                                            <strong>#<?php echo $index + 1; ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($client['full_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($client['email']); ?></td>
                                    <td><?php echo $client['booking_count']; ?></td>
                                    <td><strong style="color: var(--color-gold);"><?php echo formatCurrency($client['total_spent']); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Monthly Trend -->
        <div class="report-card">
            <h2 style="margin-bottom: var(--spacing-lg); display: flex; align-items: center; gap: var(--spacing-sm);">
                <i class="bi bi-graph-up" style="color: var(--color-gold);"></i>
                Revenue Trend (Last 6 Months)
            </h2>

            <?php if (empty($monthly_trend)): ?>
                <p style="text-align: center; color: #999; padding: var(--spacing-xl);">No revenue data available</p>
            <?php else: ?>
                <?php
                $max_revenue = max(array_column($monthly_trend, 'revenue'));
                foreach ($monthly_trend as $month):
                    $percentage = $max_revenue > 0 ? ($month['revenue'] / $max_revenue) * 100 : 0;
                ?>
                    <div style="margin-bottom: var(--spacing-md);">
                        <div style="display: flex; justify-content: space-between; margin-bottom: var(--spacing-xs);">
                            <strong><?php echo htmlspecialchars($month['month_name']); ?></strong>
                            <span><?php echo formatCurrency($month['revenue']); ?> (<?php echo $month['transaction_count']; ?> transactions)</span>
                        </div>
                        <div class="progress-bar" style="height: 30px;">
                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%;">
                                <?php if ($percentage > 15): ?>
                                    <?php echo formatCurrency($month['revenue']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Export Options -->
        <div class="report-card" style="text-align: center;">
            <h3 style="margin-bottom: var(--spacing-md);">Export Reports</h3>
            <p style="color: #666; margin-bottom: var(--spacing-lg);">
                Download reports for the selected date range
            </p>
            <div style="display: flex; gap: var(--spacing-md); justify-content: center; flex-wrap: wrap;">
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print Report
                </button>
                <button class="btn btn-secondary" onclick="alert('PDF export feature coming soon!')">
                    <i class="bi bi-file-pdf"></i> Export as PDF
                </button>
                <button class="btn btn-outline" onclick="alert('CSV export feature coming soon!')">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Export as CSV
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .navbar, .btn, .date-filter, .report-card:last-child {
        display: none !important;
    }

    .admin-container {
        padding-top: 0;
        background: white;
    }

    .report-card {
        page-break-inside: avoid;
        box-shadow: none;
        border: 1px solid #ddd;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
