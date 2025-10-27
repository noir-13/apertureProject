<?php
$page_title = "Manage Bookings";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$db = getDB();
$success_message = '';
$error_message = '';

// Handle booking actions (approve, reject, etc.)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $booking_id = (int)$_POST['booking_id'];
    $action = $_POST['action'];

    try {
        $db->beginTransaction();

        if ($action === 'approve') {
            $stmt = $db->prepare("UPDATE bookings SET booking_status = 'confirmed' WHERE booking_id = ?");
            $stmt->execute([$booking_id]);

            // Get booking details for notification
            $booking = $db->query("SELECT b.*, u.full_name, u.email FROM bookings b JOIN users u ON b.user_id = u.user_id WHERE b.booking_id = $booking_id")->fetch();

            // Notify user
            createNotification(
                $booking['user_id'],
                'Booking Approved!',
                "Your booking (Ref: {$booking['booking_reference']}) has been approved. Please proceed with payment.",
                'booking',
                $booking_id
            );

            logActivity(getCurrentUserId(), 'booking_approved', "Approved booking {$booking['booking_reference']}");
            $success_message = 'Booking approved successfully!';

        } elseif ($action === 'reject') {
            $rejection_reason = sanitize($_POST['rejection_reason']);
            $stmt = $db->prepare("UPDATE bookings SET booking_status = 'rejected', rejection_reason = ? WHERE booking_id = ?");
            $stmt->execute([$rejection_reason, $booking_id]);

            $booking = $db->query("SELECT b.*, u.full_name FROM bookings b JOIN users u ON b.user_id = u.user_id WHERE b.booking_id = $booking_id")->fetch();

            createNotification(
                $booking['user_id'],
                'Booking Rejected',
                "Your booking (Ref: {$booking['booking_reference']}) has been rejected. Reason: {$rejection_reason}",
                'booking',
                $booking_id
            );

            logActivity(getCurrentUserId(), 'booking_rejected', "Rejected booking {$booking['booking_reference']}");
            $success_message = 'Booking rejected.';

        } elseif ($action === 'complete') {
            $stmt = $db->prepare("UPDATE bookings SET booking_status = 'completed' WHERE booking_id = ?");
            $stmt->execute([$booking_id]);

            $booking = $db->query("SELECT * FROM bookings WHERE booking_id = $booking_id")->fetch();
            createNotification(
                $booking['user_id'],
                'Event Completed',
                "Your booking (Ref: {$booking['booking_reference']}) has been marked as completed. Thank you!",
                'booking',
                $booking_id
            );

            $success_message = 'Booking marked as completed.';

        } elseif ($action === 'assign_photographer') {
            $photographer_id = (int)$_POST['photographer_id'];
            $stmt = $db->prepare("UPDATE bookings SET photographer_assigned = ? WHERE booking_id = ?");
            $stmt->execute([$photographer_id, $booking_id]);
            $success_message = 'Photographer assigned successfully.';

        } elseif ($action === 'add_notes') {
            $admin_notes = sanitize($_POST['admin_notes']);
            $stmt = $db->prepare("UPDATE bookings SET admin_notes = ? WHERE booking_id = ?");
            $stmt->execute([$admin_notes, $booking_id]);
            $success_message = 'Notes added successfully.';
        }

        $db->commit();
        setFlashMessage($success_message, 'success');
        redirect(SITE_URL . '/admin/bookings.php');

    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Booking action error: " . $e->getMessage());
        $error_message = 'An error occurred. Please try again.';
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_clauses = [];
$params = [];

if ($filter !== 'all') {
    $where_clauses[] = "b.booking_status = ?";
    $params[] = $filter;
}

if (!empty($search)) {
    $where_clauses[] = "(b.booking_reference LIKE ? OR u.full_name LIKE ? OR u.email LIKE ? OR s.service_name LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Get all bookings
$stmt = $db->prepare("
    SELECT b.*, s.service_name, s.duration_hours, u.full_name, u.email, u.phone,
           p.full_name as photographer_name
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN users u ON b.user_id = u.user_id
    LEFT JOIN users p ON b.photographer_assigned = p.user_id
    {$where_sql}
    ORDER BY b.created_at DESC
");
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Get photographers for assignment
$photographers = $db->query("SELECT user_id, full_name FROM users WHERE role = 'photographer' AND account_status = 'active'")->fetchAll();

// Get booking statistics
$stats = [
    'all' => $db->query("SELECT COUNT(*) as count FROM bookings")->fetch()['count'],
    'pending' => $db->query("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'pending'")->fetch()['count'],
    'confirmed' => $db->query("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'confirmed'")->fetch()['count'],
    'completed' => $db->query("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'completed'")->fetch()['count'],
    'cancelled' => $db->query("SELECT COUNT(*) as count FROM bookings WHERE booking_status IN ('cancelled', 'rejected')")->fetch()['count']
];

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.admin-container {
    min-height: 100vh;
    background: #f4f6f9;
    padding-top: 80px;
    padding-bottom: var(--spacing-2xl);
}

.filter-bar {
    background: var(--color-white);
    padding: var(--spacing-lg);
    border-radius: var(--radius-lg);
    margin-bottom: var(--spacing-lg);
    box-shadow: var(--shadow-md);
}

.filter-tabs {
    display: flex;
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-md);
    flex-wrap: wrap;
}

.filter-tab {
    padding: 10px 20px;
    border: 2px solid #ddd;
    border-radius: var(--radius-md);
    background: white;
    cursor: pointer;
    transition: var(--transition-base);
    text-decoration: none;
    color: var(--color-charcoal);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
}

.filter-tab:hover {
    border-color: var(--color-gold);
}

.filter-tab.active {
    background: var(--gradient-gold);
    border-color: var(--color-gold);
    color: var(--color-black);
}

.filter-tab .count {
    background: rgba(0,0,0,0.1);
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 600;
}

.search-box {
    display: flex;
    gap: var(--spacing-sm);
}

.booking-table {
    background: var(--color-white);
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-md);
}

.table-responsive {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table thead {
    background: var(--gradient-dark);
    color: var(--color-white);
}

.data-table th {
    padding: var(--spacing-md);
    text-align: left;
    font-weight: 600;
    white-space: nowrap;
}

.data-table td {
    padding: var(--spacing-md);
    border-bottom: 1px solid #eee;
    vertical-align: top;
}

.data-table tr:hover {
    background: #f8f9fa;
}

.data-table tr:last-child td {
    border-bottom: none;
}

.action-buttons {
    display: flex;
    gap: var(--spacing-xs);
    flex-wrap: wrap;
}

.booking-detail-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.8);
    z-index: 10000;
    overflow-y: auto;
    padding: var(--spacing-lg);
}

.booking-detail-modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content-large {
    background: var(--color-white);
    border-radius: var(--radius-xl);
    padding: var(--spacing-xl);
    max-width: 900px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
}
</style>

<div class="admin-container">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg);">
            <h1><i class="bi bi-calendar-check"></i> Manage Bookings</h1>
            <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="btn btn-outline">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="filter-tabs">
                <a href="?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    All Bookings <span class="count"><?php echo $stats['all']; ?></span>
                </a>
                <a href="?filter=pending" class="filter-tab <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                    <i class="bi bi-clock"></i> Pending <span class="count"><?php echo $stats['pending']; ?></span>
                </a>
                <a href="?filter=confirmed" class="filter-tab <?php echo $filter === 'confirmed' ? 'active' : ''; ?>">
                    <i class="bi bi-check-circle"></i> Confirmed <span class="count"><?php echo $stats['confirmed']; ?></span>
                </a>
                <a href="?filter=completed" class="filter-tab <?php echo $filter === 'completed' ? 'active' : ''; ?>">
                    <i class="bi bi-flag"></i> Completed <span class="count"><?php echo $stats['completed']; ?></span>
                </a>
                <a href="?filter=cancelled" class="filter-tab <?php echo $filter === 'cancelled' ? 'active' : ''; ?>">
                    <i class="bi bi-x-circle"></i> Cancelled <span class="count"><?php echo $stats['cancelled']; ?></span>
                </a>
            </div>

            <form method="GET" class="search-box">
                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                <input type="text" name="search" class="form-control" placeholder="Search by reference, client, email, service..."
                       value="<?php echo htmlspecialchars($search); ?>" style="flex: 1;">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Search
                </button>
                <?php if ($search): ?>
                    <a href="?filter=<?php echo $filter; ?>" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Bookings Table -->
        <div class="booking-table">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Client Info</th>
                            <th>Service</th>
                            <th>Event Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: var(--spacing-2xl); color: #999;">
                                    <i class="bi bi-inbox" style="font-size: 48px; display: block; margin-bottom: var(--spacing-md);"></i>
                                    No bookings found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong>
                                        <div style="font-size: 11px; color: #999;">
                                            <?php echo formatDateTime($booking['created_at'], 'M j, g:i A'); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div><strong><?php echo htmlspecialchars($booking['full_name']); ?></strong></div>
                                        <div style="font-size: 13px; color: #666;">
                                            <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($booking['email']); ?>
                                        </div>
                                        <?php if ($booking['phone']): ?>
                                            <div style="font-size: 13px; color: #666;">
                                                <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($booking['phone']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><strong><?php echo htmlspecialchars($booking['service_name']); ?></strong></div>
                                        <div style="font-size: 12px; color: #666;">
                                            <?php echo $booking['duration_hours']; ?> hours
                                        </div>
                                        <?php if ($booking['photographer_name']): ?>
                                            <div style="font-size: 12px; color: var(--color-gold); margin-top: 4px;">
                                                <i class="bi bi-camera"></i> <?php echo htmlspecialchars($booking['photographer_name']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><strong><?php echo formatDate($booking['booking_date']); ?></strong></div>
                                        <div style="font-size: 12px; color: #666;">
                                            <?php echo date('g:i A', strtotime($booking['booking_time'])); ?>
                                        </div>
                                        <div style="font-size: 12px; color: #666;">
                                            <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars(substr($booking['event_location'], 0, 20)) . '...'; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: var(--color-gold);">
                                            <?php echo formatCurrency($booking['total_amount']); ?>
                                        </div>
                                        <div style="font-size: 11px; color: #666;">
                                            Down: <?php echo formatCurrency($booking['downpayment_amount']); ?>
                                        </div>
                                        <div style="font-size: 11px; color: #666;">
                                            Bal: <?php echo formatCurrency($booking['remaining_balance']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo getStatusBadgeClass($booking['booking_status']); ?>">
                                            <?php echo ucfirst($booking['booking_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo getPaymentStatusBadgeClass($booking['payment_status']); ?>" style="font-size: 10px;">
                                            <?php echo str_replace('_', ' ', ucfirst($booking['payment_status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button onclick="viewBooking(<?php echo $booking['booking_id']; ?>)"
                                                    class="btn btn-sm btn-primary" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <?php if ($booking['booking_status'] === 'pending'): ?>
                                                <button onclick="approveBooking(<?php echo $booking['booking_id']; ?>)"
                                                        class="btn btn-sm" style="background: #28a745; color: white;" title="Approve">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                                <button onclick="showRejectModal(<?php echo $booking['booking_id']; ?>)"
                                                        class="btn btn-sm" style="background: #dc3545; color: white;" title="Reject">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($booking['booking_status'] === 'confirmed'): ?>
                                                <button onclick="completeBooking(<?php echo $booking['booking_id']; ?>)"
                                                        class="btn btn-sm btn-secondary" title="Mark Complete">
                                                    <i class="bi bi-flag"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Reject Booking</h3>
            <button class="modal-close" onclick="closeModal('rejectModal')">&times;</button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="booking_id" id="reject_booking_id">
                <div class="form-group">
                    <label class="form-label">Rejection Reason *</label>
                    <textarea name="rejection_reason" class="form-control" rows="4"
                              placeholder="Please provide a reason for rejection..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('rejectModal')">Cancel</button>
                <button type="submit" class="btn" style="background: #dc3545; color: white;">
                    <i class="bi bi-x-circle"></i> Reject Booking
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function viewBooking(bookingId) {
    window.location.href = '?id=' + bookingId;
}

function approveBooking(bookingId) {
    if (confirm('Are you sure you want to approve this booking?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="booking_id" value="${bookingId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function showRejectModal(bookingId) {
    document.getElementById('reject_booking_id').value = bookingId;
    openModal('rejectModal');
}

function completeBooking(bookingId) {
    if (confirm('Mark this booking as completed?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="complete">
            <input type="hidden" name="booking_id" value="${bookingId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
