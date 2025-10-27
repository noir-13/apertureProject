<?php
$page_title = "My Bookings";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$user_id = getCurrentUserId();
$db = getDB();

// Get specific booking if ID provided
$selected_booking = null;
if (isset($_GET['id'])) {
    $booking_id = (int)$_GET['id'];
    $stmt = $db->prepare("
        SELECT b.*, s.service_name, s.duration_hours, s.features
        FROM bookings b
        JOIN services s ON b.service_id = s.service_id
        WHERE b.booking_id = ? AND b.user_id = ?
    ");
    $stmt->execute([$booking_id, $user_id]);
    $selected_booking = $stmt->fetch();
}

// Get all bookings
$stmt = $db->prepare("
    SELECT b.*, s.service_name, s.duration_hours
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    WHERE b.user_id = ?
    ORDER BY b.booking_date DESC, b.created_at DESC
");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.bookings-container {
    min-height: 100vh;
    background: var(--color-beige);
    padding-top: 100px;
    padding-bottom: var(--spacing-2xl);
}

.booking-card {
    background: var(--color-white);
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    margin-bottom: var(--spacing-md);
    border-left: 4px solid;
    transition: var(--transition-base);
    cursor: pointer;
}

.booking-card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateX(4px);
}

.booking-card.pending { border-color: #ffc107; }
.booking-card.confirmed { border-color: #28a745; }
.booking-card.completed { border-color: #17a2b8; }
.booking-card.cancelled { border-color: #6c757d; }
.booking-card.rejected { border-color: #dc3545; }

.booking-detail-modal {
    background: var(--color-white);
    border-radius: var(--radius-xl);
    padding: var(--spacing-xl);
    max-width: 800px;
    margin: 0 auto;
}

.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #ddd;
}

.timeline-item {
    position: relative;
    padding-bottom: var(--spacing-md);
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -25px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--color-gold);
    border: 2px solid white;
    box-shadow: 0 0 0 2px var(--color-gold);
}

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
    font-weight: 500;
}

.filter-tab:hover {
    border-color: var(--color-gold);
}

.filter-tab.active {
    background: var(--gradient-gold);
    border-color: var(--color-gold);
    color: var(--color-black);
}
</style>

<div class="bookings-container">
    <div class="container">
        <h1 style="margin-bottom: var(--spacing-lg);">My Bookings</h1>

        <!-- Filters -->
        <div class="filter-tabs">
            <button class="filter-tab active" onclick="filterBookings('all')">All Bookings</button>
            <button class="filter-tab" onclick="filterBookings('pending')">Pending</button>
            <button class="filter-tab" onclick="filterBookings('confirmed')">Confirmed</button>
            <button class="filter-tab" onclick="filterBookings('completed')">Completed</button>
            <button class="filter-tab" onclick="filterBookings('cancelled')">Cancelled/Rejected</button>
        </div>

        <div class="row">
            <!-- Bookings List -->
            <div class="col-12 <?php echo $selected_booking ? 'col-md-6' : ''; ?>">
                <?php if (empty($bookings)): ?>
                    <div class="card text-center" style="padding: var(--spacing-2xl);">
                        <i class="bi bi-calendar-x" style="font-size: 80px; color: #ddd; margin-bottom: var(--spacing-md);"></i>
                        <h3 style="color: #999; margin-bottom: var(--spacing-md);">No Bookings Yet</h3>
                        <p style="color: #666; margin-bottom: var(--spacing-lg);">
                            You haven't made any bookings yet. Start by booking one of our premium services.
                        </p>
                        <a href="<?php echo SITE_URL; ?>/user/booking.php" class="btn btn-primary btn-lg">
                            Book Your First Session
                        </a>
                    </div>
                <?php else: ?>
                    <div id="bookingsList">
                        <?php foreach ($bookings as $booking): ?>
                            <div class="booking-card <?php echo $booking['booking_status']; ?>"
                                 data-status="<?php echo $booking['booking_status']; ?>"
                                 onclick="viewBooking(<?php echo $booking['booking_id']; ?>)">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: var(--spacing-sm);">
                                    <div style="flex: 1;">
                                        <h4 style="margin-bottom: var(--spacing-xs);">
                                            <?php echo htmlspecialchars($booking['service_name']); ?>
                                        </h4>
                                        <div style="font-size: 13px; color: #666; margin-bottom: var(--spacing-xs);">
                                            Ref: <strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong>
                                        </div>
                                    </div>
                                    <span class="badge badge-<?php echo getStatusBadgeClass($booking['booking_status']); ?>">
                                        <?php echo ucfirst($booking['booking_status']); ?>
                                    </span>
                                </div>

                                <div style="display: flex; gap: var(--spacing-md); flex-wrap: wrap; margin-bottom: var(--spacing-sm);">
                                    <span style="font-size: 14px; color: #555;">
                                        <i class="bi bi-calendar3"></i> <?php echo formatDate($booking['booking_date']); ?>
                                    </span>
                                    <span style="font-size: 14px; color: #555;">
                                        <i class="bi bi-clock"></i> <?php echo date('g:i A', strtotime($booking['booking_time'])); ?>
                                    </span>
                                    <span style="font-size: 14px; color: #555;">
                                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars(substr($booking['event_location'], 0, 30)) . (strlen($booking['event_location']) > 30 ? '...' : ''); ?>
                                    </span>
                                </div>

                                <div style="display: flex; justify-content: space-between; align-items: center; padding-top: var(--spacing-sm); border-top: 1px solid #eee;">
                                    <div>
                                        <span class="badge badge-<?php echo getPaymentStatusBadgeClass($booking['payment_status']); ?>" style="font-size: 11px;">
                                            <?php echo str_replace('_', ' ', ucfirst($booking['payment_status'])); ?>
                                        </span>
                                    </div>
                                    <div style="font-weight: 700; color: var(--color-gold); font-size: 1.1rem;">
                                        <?php echo formatCurrency($booking['total_amount']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Booking Details -->
            <?php if ($selected_booking): ?>
            <div class="col-12 col-md-6">
                <div class="card" style="position: sticky; top: 100px;">
                    <div class="card-header">
                        <h3 style="margin: 0;">
                            <i class="bi bi-file-text"></i> Booking Details
                        </h3>
                    </div>
                    <div class="card-body">
                        <div style="text-align: center; padding: var(--spacing-md); background: var(--color-beige); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg);">
                            <h4 style="margin-bottom: var(--spacing-sm);">
                                <?php echo htmlspecialchars($selected_booking['service_name']); ?>
                            </h4>
                            <div style="font-size: 14px; color: #666; margin-bottom: var(--spacing-sm);">
                                Booking Reference: <strong><?php echo htmlspecialchars($selected_booking['booking_reference']); ?></strong>
                            </div>
                            <span class="badge badge-<?php echo getStatusBadgeClass($selected_booking['booking_status']); ?>" style="font-size: 14px; padding: 8px 16px;">
                                <?php echo ucfirst($selected_booking['booking_status']); ?>
                            </span>
                        </div>

                        <div style="margin-bottom: var(--spacing-lg);">
                            <h5 style="margin-bottom: var(--spacing-md); color: var(--color-gold);">
                                <i class="bi bi-calendar-event"></i> Event Information
                            </h5>
                            <div style="margin-bottom: var(--spacing-sm);">
                                <strong>Date:</strong> <?php echo formatDate($selected_booking['booking_date']); ?>
                            </div>
                            <div style="margin-bottom: var(--spacing-sm);">
                                <strong>Time:</strong> <?php echo date('g:i A', strtotime($selected_booking['booking_time'])); ?>
                            </div>
                            <div style="margin-bottom: var(--spacing-sm);">
                                <strong>Duration:</strong> <?php echo $selected_booking['duration_hours']; ?> hours
                            </div>
                            <div style="margin-bottom: var(--spacing-sm);">
                                <strong>Location:</strong> <?php echo htmlspecialchars($selected_booking['event_location']); ?>
                            </div>
                            <?php if ($selected_booking['event_details']): ?>
                                <div style="margin-top: var(--spacing-md); padding: var(--spacing-md); background: #f8f9fa; border-radius: var(--radius-sm);">
                                    <strong style="display: block; margin-bottom: var(--spacing-xs);">Additional Details:</strong>
                                    <?php echo nl2br(htmlspecialchars($selected_booking['event_details'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="margin-bottom: var(--spacing-lg);">
                            <h5 style="margin-bottom: var(--spacing-md); color: var(--color-gold);">
                                <i class="bi bi-credit-card"></i> Payment Information
                            </h5>
                            <div style="background: #f8f9fa; padding: var(--spacing-md); border-radius: var(--radius-md);">
                                <div style="display: flex; justify-content: space-between; margin-bottom: var(--spacing-sm);">
                                    <span>Total Amount:</span>
                                    <strong><?php echo formatCurrency($selected_booking['total_amount']); ?></strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: var(--spacing-sm);">
                                    <span>Downpayment (<?php echo DOWNPAYMENT_PERCENTAGE; ?>%):</span>
                                    <strong><?php echo formatCurrency($selected_booking['downpayment_amount']); ?></strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; padding-top: var(--spacing-sm); border-top: 2px solid #ddd;">
                                    <span>Remaining Balance:</span>
                                    <strong style="color: var(--color-gold); font-size: 1.2rem;">
                                        <?php echo formatCurrency($selected_booking['remaining_balance']); ?>
                                    </strong>
                                </div>
                            </div>
                            <div style="margin-top: var(--spacing-sm);">
                                <span class="badge badge-<?php echo getPaymentStatusBadgeClass($selected_booking['payment_status']); ?>">
                                    Payment: <?php echo str_replace('_', ' ', ucwords($selected_booking['payment_status'])); ?>
                                </span>
                            </div>
                        </div>

                        <?php if ($selected_booking['admin_notes']): ?>
                            <div style="margin-bottom: var(--spacing-lg); padding: var(--spacing-md); background: #fff3cd; border-left: 4px solid #ffc107; border-radius: var(--radius-sm);">
                                <strong style="display: block; margin-bottom: var(--spacing-xs);">
                                    <i class="bi bi-sticky"></i> Admin Notes:
                                </strong>
                                <?php echo nl2br(htmlspecialchars($selected_booking['admin_notes'])); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($selected_booking['rejection_reason']): ?>
                            <div style="margin-bottom: var(--spacing-lg); padding: var(--spacing-md); background: #f8d7da; border-left: 4px solid #dc3545; border-radius: var(--radius-sm);">
                                <strong style="display: block; margin-bottom: var(--spacing-xs); color: #721c24;">
                                    <i class="bi bi-x-circle"></i> Rejection Reason:
                                </strong>
                                <span style="color: #721c24;">
                                    <?php echo nl2br(htmlspecialchars($selected_booking['rejection_reason'])); ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <div style="display: flex; gap: var(--spacing-sm); flex-wrap: wrap;">
                            <?php if ($selected_booking['booking_status'] === 'confirmed'): ?>
                                <button class="btn btn-primary">
                                    <i class="bi bi-download"></i> Download Receipt
                                </button>
                            <?php endif; ?>

                            <?php if (in_array($selected_booking['booking_status'], ['pending', 'confirmed'])): ?>
                                <?php
                                $days_until = floor((strtotime($selected_booking['booking_date']) - time()) / 86400);
                                if ($days_until >= CANCELLATION_PERIOD_DAYS):
                                ?>
                                    <button class="btn btn-outline" onclick="requestCancellation(<?php echo $selected_booking['booking_id']; ?>)">
                                        <i class="bi bi-x-circle"></i> Request Cancellation
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <div style="margin-top: var(--spacing-lg); padding-top: var(--spacing-lg); border-top: 1px solid #ddd; font-size: 12px; color: #999;">
                            <div>Created: <?php echo formatDateTime($selected_booking['created_at']); ?></div>
                            <div>Last Updated: <?php echo formatDateTime($selected_booking['updated_at']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function filterBookings(status) {
    const cards = document.querySelectorAll('.booking-card');
    const tabs = document.querySelectorAll('.filter-tab');

    // Update active tab
    tabs.forEach(tab => tab.classList.remove('active'));
    event.target.classList.add('active');

    // Filter cards
    cards.forEach(card => {
        if (status === 'all') {
            card.style.display = 'block';
        } else if (status === 'cancelled') {
            const cardStatus = card.dataset.status;
            card.style.display = (cardStatus === 'cancelled' || cardStatus === 'rejected') ? 'block' : 'none';
        } else {
            card.style.display = card.dataset.status === status ? 'block' : 'none';
        }
    });
}

function viewBooking(bookingId) {
    window.location.href = '?id=' + bookingId;
}

function requestCancellation(bookingId) {
    showConfirmModal(
        'Request Cancellation',
        'Are you sure you want to request cancellation? The downpayment is non-refundable.',
        function() {
            // Here you would send an AJAX request or redirect to cancellation handler
            alert('Cancellation request submitted');
        }
    );
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
