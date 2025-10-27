<?php
$page_title = "Refund Management";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$db = getDB();

// Handle refund actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $refund_id = (int)$_POST['refund_id'];
    $action = $_POST['action'];

    try {
        $db->beginTransaction();

        // Get refund details
        $refund = $db->query("
            SELECT r.*, b.booking_reference, b.user_id, u.full_name, u.email
            FROM refund_requests r
            JOIN bookings b ON r.booking_id = b.booking_id
            JOIN users u ON r.user_id = u.user_id
            WHERE r.refund_id = $refund_id
        ")->fetch();

        if ($action === 'approve') {
            $admin_response = sanitize($_POST['admin_response']);

            // Update refund request
            $stmt = $db->prepare("
                UPDATE refund_requests
                SET request_status = 'approved', admin_response = ?, processed_by = ?, processed_at = NOW()
                WHERE refund_id = ?
            ");
            $stmt->execute([$admin_response, getCurrentUserId(), $refund_id]);

            // Update payment record
            $stmt = $db->prepare("
                UPDATE payments
                SET payment_status = 'refunded', refund_amount = ?, refund_date = NOW()
                WHERE payment_id = ?
            ");
            $stmt->execute([$refund['refund_amount'], $refund['payment_id']]);

            // Update booking status
            $db->query("UPDATE bookings SET booking_status = 'cancelled' WHERE booking_id = {$refund['booking_id']}");

            // Notify user
            createNotification(
                $refund['user_id'],
                'Refund Approved',
                "Your refund request for booking {$refund['booking_reference']} has been approved. Amount: " . formatCurrency($refund['refund_amount']),
                'refund',
                $refund_id
            );

            logActivity(getCurrentUserId(), 'refund_approved', "Approved refund #{$refund_id}");
            setFlashMessage('Refund approved successfully!', 'success');

        } elseif ($action === 'reject') {
            $admin_response = sanitize($_POST['admin_response']);

            $stmt = $db->prepare("
                UPDATE refund_requests
                SET request_status = 'rejected', admin_response = ?, processed_by = ?, processed_at = NOW()
                WHERE refund_id = ?
            ");
            $stmt->execute([$admin_response, getCurrentUserId(), $refund_id]);

            createNotification(
                $refund['user_id'],
                'Refund Rejected',
                "Your refund request for booking {$refund['booking_reference']} has been rejected. Reason: {$admin_response}",
                'refund',
                $refund_id
            );

            logActivity(getCurrentUserId(), 'refund_rejected', "Rejected refund #{$refund_id}");
            setFlashMessage('Refund rejected.', 'warning');
        }

        $db->commit();
        redirect(SITE_URL . '/admin/refunds.php');

    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Refund action error: " . $e->getMessage());
        setFlashMessage('An error occurred. Please try again.', 'error');
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'pending';

// Build query
$where_clause = $filter === 'all' ? '' : "WHERE r.request_status = '$filter'";

// Get refund requests
$refunds = $db->query("
    SELECT r.*, b.booking_reference, b.booking_date, b.total_amount, b.downpayment_amount,
           s.service_name, u.full_name, u.email, u.phone,
           p.full_name as processor_name
    FROM refund_requests r
    JOIN bookings b ON r.booking_id = b.booking_id
    JOIN services s ON b.service_id = s.service_id
    JOIN users u ON r.user_id = u.user_id
    LEFT JOIN users p ON r.processed_by = p.user_id
    {$where_clause}
    ORDER BY r.created_at DESC
")->fetchAll();

// Get statistics
$stats = [
    'all' => $db->query("SELECT COUNT(*) as count FROM refund_requests")->fetch()['count'],
    'pending' => $db->query("SELECT COUNT(*) as count FROM refund_requests WHERE request_status = 'pending'")->fetch()['count'],
    'approved' => $db->query("SELECT COUNT(*) as count FROM refund_requests WHERE request_status = 'approved'")->fetch()['count'],
    'rejected' => $db->query("SELECT COUNT(*) as count FROM refund_requests WHERE request_status = 'rejected'")->fetch()['count']
];

// Calculate total refund amounts
$refund_stats = $db->query("
    SELECT
        COALESCE(SUM(CASE WHEN request_status = 'approved' THEN refund_amount ELSE 0 END), 0) as total_refunded,
        COALESCE(SUM(CASE WHEN request_status = 'pending' THEN refund_amount ELSE 0 END), 0) as pending_amount
    FROM refund_requests
")->fetch();

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.admin-container {
    min-height: 100vh;
    background: #f4f6f9;
    padding-top: 80px;
    padding-bottom: var(--spacing-2xl);
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
}

.stat-card {
    background: var(--color-white);
    padding: var(--spacing-lg);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    border-left: 4px solid;
}

.stat-card.red { border-color: #dc3545; }
.stat-card.orange { border-color: #ffc107; }
.stat-card.green { border-color: #28a745; }

.refund-card {
    background: var(--color-white);
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    margin-bottom: var(--spacing-md);
    box-shadow: var(--shadow-md);
    border-left: 4px solid;
}

.refund-card.pending { border-color: #ffc107; }
.refund-card.approved { border-color: #28a745; }
.refund-card.rejected { border-color: #dc3545; }

.filter-tabs {
    display: flex;
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-lg);
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
}

.filter-tab.active {
    background: var(--gradient-gold);
    border-color: var(--color-gold);
    color: var(--color-black);
}
</style>

<div class="admin-container">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg);">
            <h1><i class="bi bi-arrow-return-left"></i> Refund Management</h1>
            <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="btn btn-outline">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Statistics -->
        <div class="stats-row">
            <div class="stat-card red">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 2rem; font-weight: 700; color: #dc3545; margin-bottom: var(--spacing-xs);">
                            <?php echo formatCurrency($refund_stats['total_refunded']); ?>
                        </div>
                        <div style="color: #666; font-weight: 500;">Total Refunded</div>
                    </div>
                    <i class="bi bi-cash-coin" style="font-size: 48px; color: rgba(220, 53, 69, 0.2);"></i>
                </div>
            </div>

            <div class="stat-card orange">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 2rem; font-weight: 700; color: #ffc107; margin-bottom: var(--spacing-xs);">
                            <?php echo formatCurrency($refund_stats['pending_amount']); ?>
                        </div>
                        <div style="color: #666; font-weight: 500;">Pending Refunds</div>
                    </div>
                    <i class="bi bi-hourglass-split" style="font-size: 48px; color: rgba(255, 193, 7, 0.2);"></i>
                </div>
            </div>

            <div class="stat-card green">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 2rem; font-weight: 700; color: #28a745; margin-bottom: var(--spacing-xs);">
                            <?php echo $stats['approved']; ?>
                        </div>
                        <div style="color: #666; font-weight: 500;">Approved Requests</div>
                    </div>
                    <i class="bi bi-check-circle" style="font-size: 48px; color: rgba(40, 167, 69, 0.2);"></i>
                </div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="?filter=pending" class="filter-tab <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                Pending (<?php echo $stats['pending']; ?>)
            </a>
            <a href="?filter=approved" class="filter-tab <?php echo $filter === 'approved' ? 'active' : ''; ?>">
                Approved (<?php echo $stats['approved']; ?>)
            </a>
            <a href="?filter=rejected" class="filter-tab <?php echo $filter === 'rejected' ? 'active' : ''; ?>">
                Rejected (<?php echo $stats['rejected']; ?>)
            </a>
            <a href="?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                All (<?php echo $stats['all']; ?>)
            </a>
        </div>

        <!-- Refund Requests -->
        <?php if (empty($refunds)): ?>
            <div class="card text-center" style="padding: var(--spacing-2xl);">
                <i class="bi bi-inbox" style="font-size: 80px; color: #ddd; margin-bottom: var(--spacing-md);"></i>
                <h3 style="color: #999;">No Refund Requests</h3>
            </div>
        <?php else: ?>
            <?php foreach ($refunds as $refund): ?>
                <div class="refund-card <?php echo $refund['request_status']; ?>">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: var(--spacing-md);">
                        <div>
                            <h3 style="margin-bottom: var(--spacing-xs);">
                                Refund Request #<?php echo $refund['refund_id']; ?>
                            </h3>
                            <div style="display: flex; gap: var(--spacing-md); flex-wrap: wrap;">
                                <span style="color: #666;">
                                    <strong>Booking:</strong> <?php echo htmlspecialchars($refund['booking_reference']); ?>
                                </span>
                                <span style="color: #666;">
                                    <strong>Service:</strong> <?php echo htmlspecialchars($refund['service_name']); ?>
                                </span>
                            </div>
                        </div>
                        <span class="badge badge-<?php echo $refund['request_status'] === 'pending' ? 'warning' : ($refund['request_status'] === 'approved' ? 'success' : 'danger'); ?>" style="font-size: 14px; padding: 8px 16px;">
                            <?php echo ucfirst($refund['request_status']); ?>
                        </span>
                    </div>

                    <div class="row" style="margin-bottom: var(--spacing-md);">
                        <div class="col-12 col-md-4">
                            <div style="padding: var(--spacing-md); background: #f8f9fa; border-radius: var(--radius-md);">
                                <div style="font-size: 12px; color: #666; margin-bottom: var(--spacing-xs);">Client</div>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($refund['full_name']); ?></div>
                                <div style="font-size: 13px; color: #666;">
                                    <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($refund['email']); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div style="padding: var(--spacing-md); background: #f8f9fa; border-radius: var(--radius-md);">
                                <div style="font-size: 12px; color: #666; margin-bottom: var(--spacing-xs);">Booking Date</div>
                                <div style="font-weight: 600;"><?php echo formatDate($refund['booking_date']); ?></div>
                                <div style="font-size: 13px; color: #666;">
                                    Requested: <?php echo formatDate($refund['created_at'], 'M j, Y'); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div style="padding: var(--spacing-md); background: #fff3cd; border-radius: var(--radius-md);">
                                <div style="font-size: 12px; color: #666; margin-bottom: var(--spacing-xs);">Refund Amount</div>
                                <div style="font-weight: 700; font-size: 1.5rem; color: #ffc107;">
                                    <?php echo formatCurrency($refund['refund_amount']); ?>
                                </div>
                                <div style="font-size: 11px; color: #666;">
                                    Total: <?php echo formatCurrency($refund['total_amount']); ?> |
                                    Down: <?php echo formatCurrency($refund['downpayment_amount']); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="padding: var(--spacing-md); background: #e3f2fd; border-left: 4px solid #2196F3; border-radius: var(--radius-sm); margin-bottom: var(--spacing-md);">
                        <strong style="display: block; margin-bottom: var(--spacing-xs);">
                            <i class="bi bi-chat-left-quote"></i> Client's Reason:
                        </strong>
                        <div style="color: #555;"><?php echo nl2br(htmlspecialchars($refund['reason'])); ?></div>
                    </div>

                    <?php if ($refund['admin_response']): ?>
                        <div style="padding: var(--spacing-md); background: #f8f9fa; border-left: 4px solid var(--color-gold); border-radius: var(--radius-sm); margin-bottom: var(--spacing-md);">
                            <strong style="display: block; margin-bottom: var(--spacing-xs);">
                                <i class="bi bi-reply"></i> Admin Response:
                            </strong>
                            <div style="color: #555; margin-bottom: var(--spacing-xs);">
                                <?php echo nl2br(htmlspecialchars($refund['admin_response'])); ?>
                            </div>
                            <?php if ($refund['processor_name']): ?>
                                <div style="font-size: 12px; color: #666;">
                                    Processed by: <?php echo htmlspecialchars($refund['processor_name']); ?> on
                                    <?php echo formatDateTime($refund['processed_at']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($refund['request_status'] === 'pending'): ?>
                        <div style="display: flex; gap: var(--spacing-sm); flex-wrap: wrap;">
                            <button onclick="showApproveModal(<?php echo $refund['refund_id']; ?>, '<?php echo htmlspecialchars($refund['full_name']); ?>', <?php echo $refund['refund_amount']; ?>)"
                                    class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Approve Refund
                            </button>
                            <button onclick="showRejectModal(<?php echo $refund['refund_id']; ?>, '<?php echo htmlspecialchars($refund['full_name']); ?>')"
                                    class="btn" style="background: #dc3545; color: white;">
                                <i class="bi bi-x-circle"></i> Reject Refund
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Approve Modal -->
<div id="approveModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Approve Refund Request</h3>
            <button class="modal-close" onclick="closeModal('approveModal')">&times;</button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="refund_id" id="approve_refund_id">
                <div class="alert alert-success">
                    You are about to approve a refund of <strong id="approve_amount"></strong> for <strong id="approve_client"></strong>.
                </div>
                <div class="form-group">
                    <label class="form-label">Admin Response/Notes</label>
                    <textarea name="admin_response" class="form-control" rows="3"
                              placeholder="Optional notes for the client..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('approveModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Approve & Process Refund
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Reject Refund Request</h3>
            <button class="modal-close" onclick="closeModal('rejectModal')">&times;</button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="refund_id" id="reject_refund_id">
                <p>You are about to reject the refund request from <strong id="reject_client"></strong>.</p>
                <div class="form-group">
                    <label class="form-label">Rejection Reason *</label>
                    <textarea name="admin_response" class="form-control" rows="4"
                              placeholder="Explain why the refund is being rejected..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('rejectModal')">Cancel</button>
                <button type="submit" class="btn" style="background: #dc3545; color: white;">
                    <i class="bi bi-x-circle"></i> Reject Refund
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showApproveModal(refundId, clientName, amount) {
    document.getElementById('approve_refund_id').value = refundId;
    document.getElementById('approve_client').textContent = clientName;
    document.getElementById('approve_amount').textContent = formatCurrency(amount);
    openModal('approveModal');
}

function showRejectModal(refundId, clientName) {
    document.getElementById('reject_refund_id').value = refundId;
    document.getElementById('reject_client').textContent = clientName;
    openModal('rejectModal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
