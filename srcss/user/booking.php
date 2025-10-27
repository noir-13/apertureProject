<?php
$page_title = "Book a Service";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$user_id = getCurrentUserId();
$user = getUserById($user_id);

$db = getDB();

// Get all active services
$services = $db->query("SELECT * FROM services WHERE is_active = TRUE ORDER BY base_price ASC")->fetchAll();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = (int)$_POST['service_id'];
    $booking_date = sanitize($_POST['booking_date']);
    $booking_time = sanitize($_POST['booking_time']);
    $event_location = sanitize($_POST['event_location']);
    $event_details = sanitize($_POST['event_details']);
    $payment_method = sanitize($_POST['payment_method']);

    // Validation
    if (empty($service_id) || empty($booking_date) || empty($booking_time) || empty($event_location)) {
        $errors[] = 'All required fields must be filled.';
    }

    // Validate service exists
    $service = getServiceById($service_id);
    if (!$service) {
        $errors[] = 'Invalid service selected.';
    }

    // Validate booking date (must be in future and at least X days in advance)
    $min_date = date('Y-m-d', strtotime('+' . BOOKING_ADVANCE_DAYS . ' days'));
    if ($booking_date < $min_date) {
        $errors[] = 'Bookings must be made at least ' . BOOKING_ADVANCE_DAYS . ' days in advance.';
    }

    // Check date availability
    if (!isDateAvailable($booking_date)) {
        $errors[] = 'Selected date is not available. Please choose another date.';
    }

    // Calculate amounts
    if (empty($errors) && $service) {
        $total_amount = $service['base_price'];
        $downpayment_amount = calculateDownpayment($total_amount);
        $remaining_balance = $total_amount - $downpayment_amount;

        try {
            $db->beginTransaction();

            // Generate booking reference
            $booking_reference = generateBookingReference();

            // Insert booking
            $stmt = $db->prepare("
                INSERT INTO bookings (
                    user_id, service_id, booking_reference, booking_date, booking_time,
                    event_location, event_details, total_amount, downpayment_amount,
                    remaining_balance, booking_status, payment_status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid')
            ");
            $stmt->execute([
                $user_id, $service_id, $booking_reference, $booking_date, $booking_time,
                $event_location, $event_details, $total_amount, $downpayment_amount, $remaining_balance
            ]);

            $booking_id = $db->lastInsertId();

            // Create notification for user
            createNotification(
                $user_id,
                'Booking Submitted',
                "Your booking (Ref: {$booking_reference}) has been submitted and is awaiting admin approval.",
                'booking',
                $booking_id
            );

            // Create notification for admin (get all admins)
            $admins = $db->query("SELECT user_id FROM users WHERE role = 'admin'")->fetchAll();
            foreach ($admins as $admin) {
                createNotification(
                    $admin['user_id'],
                    'New Booking Request',
                    "New booking request from {$user['full_name']} (Ref: {$booking_reference})",
                    'booking',
                    $booking_id
                );
            }

            // Log activity
            logActivity($user_id, 'booking_created', "Created booking {$booking_reference}");

            $db->commit();

            setFlashMessage('Booking submitted successfully! Please wait for admin approval.', 'success');
            redirect(SITE_URL . '/user/my-bookings.php?id=' . $booking_id);

        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Booking error: " . $e->getMessage());
            $errors[] = 'An error occurred while processing your booking. Please try again.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.booking-container {
    min-height: 100vh;
    background: var(--color-beige);
    padding-top: 100px;
    padding-bottom: var(--spacing-2xl);
}

.wizard-container {
    max-width: 900px;
    margin: 0 auto;
}

.wizard-steps {
    display: flex;
    justify-content: space-between;
    margin-bottom: var(--spacing-xl);
    position: relative;
}

.wizard-steps::before {
    content: '';
    position: absolute;
    top: 24px;
    left: 0;
    right: 0;
    height: 2px;
    background: #ddd;
    z-index: 0;
}

.wizard-step {
    flex: 1;
    text-align: center;
    position: relative;
    z-index: 1;
}

.wizard-step-circle {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #ddd;
    color: #666;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto var(--spacing-xs);
    font-weight: 700;
    transition: var(--transition-base);
}

.wizard-step.active .wizard-step-circle {
    background: var(--gradient-gold);
    color: var(--color-black);
    box-shadow: var(--shadow-gold);
}

.wizard-step.completed .wizard-step-circle {
    background: #28a745;
    color: white;
}

.wizard-step-label {
    font-size: 14px;
    font-weight: 500;
    color: #666;
}

.wizard-step.active .wizard-step-label {
    color: var(--color-gold);
}

.wizard-content {
    background: var(--color-white);
    border-radius: var(--radius-lg);
    padding: var(--spacing-xl);
    box-shadow: var(--shadow-lg);
    margin-bottom: var(--spacing-lg);
}

.step-section {
    display: none;
}

.step-section.active {
    display: block;
    animation: fadeInUp 0.4s ease;
}

.service-option {
    border: 2px solid #e0e0e0;
    border-radius: var(--radius-md);
    padding: var(--spacing-md);
    margin-bottom: var(--spacing-md);
    cursor: pointer;
    transition: var(--transition-base);
}

.service-option:hover {
    border-color: var(--color-gold);
    box-shadow: var(--shadow-md);
}

.service-option input[type="radio"] {
    display: none;
}

.service-option input[type="radio"]:checked + .service-content {
    border-left: 4px solid var(--color-gold);
}

.service-option input[type="radio"]:checked ~ * {
    color: var(--color-black);
}

.service-option input[type="radio"]:checked + * .service-price {
    color: var(--color-gold);
}

.booking-summary {
    background: var(--color-beige);
    border-radius: var(--radius-md);
    padding: var(--spacing-lg);
    margin-top: var(--spacing-lg);
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: var(--spacing-sm) 0;
    border-bottom: 1px solid #ddd;
}

.summary-row:last-child {
    border-bottom: none;
    font-weight: 700;
    font-size: 1.2rem;
    color: var(--color-gold);
}

.wizard-buttons {
    display: flex;
    justify-content: space-between;
    gap: var(--spacing-md);
    margin-top: var(--spacing-xl);
}

@media (max-width: 768px) {
    .wizard-steps {
        flex-wrap: wrap;
    }

    .wizard-step {
        flex: 0 0 50%;
        margin-bottom: var(--spacing-md);
    }

    .wizard-buttons {
        flex-direction: column;
    }
}
</style>

<div class="booking-container">
    <div class="container">
        <div class="wizard-container">
            <h1 style="text-align: center; margin-bottom: var(--spacing-xl);">Book Your Session</h1>

            <!-- Wizard Steps -->
            <div class="wizard-steps">
                <div class="wizard-step active" data-step="1">
                    <div class="wizard-step-circle">1</div>
                    <div class="wizard-step-label">Choose Service</div>
                </div>
                <div class="wizard-step" data-step="2">
                    <div class="wizard-step-circle">2</div>
                    <div class="wizard-step-label">Date & Time</div>
                </div>
                <div class="wizard-step" data-step="3">
                    <div class="wizard-step-circle">3</div>
                    <div class="wizard-step-label">Event Details</div>
                </div>
                <div class="wizard-step" data-step="4">
                    <div class="wizard-step-circle">4</div>
                    <div class="wizard-step-label">Review & Submit</div>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="bookingForm">
                <!-- Step 1: Choose Service -->
                <div class="wizard-content step-section active" id="step1">
                    <h3 style="margin-bottom: var(--spacing-lg);">Select Your Service</h3>

                    <?php foreach ($services as $service): ?>
                        <?php $features = json_decode($service['features'], true); ?>
                        <label class="service-option">
                            <input type="radio" name="service_id" value="<?php echo $service['service_id']; ?>"
                                   data-price="<?php echo $service['base_price']; ?>"
                                   data-name="<?php echo htmlspecialchars($service['service_name']); ?>"
                                   data-hours="<?php echo $service['duration_hours']; ?>" required>
                            <div class="service-content" style="display: flex; justify-content: space-between; align-items: start; gap: var(--spacing-md);">
                                <div style="flex: 1;">
                                    <h4 style="margin-bottom: var(--spacing-xs);">
                                        <?php echo htmlspecialchars($service['service_name']); ?>
                                    </h4>
                                    <p style="color: #666; font-size: 14px; margin-bottom: var(--spacing-sm);">
                                        <?php echo htmlspecialchars($service['description']); ?>
                                    </p>
                                    <div style="font-size: 13px; color: #555;">
                                        <i class="bi bi-clock"></i> <?php echo $service['duration_hours']; ?> hours coverage
                                    </div>
                                    <?php if ($features && count($features) > 0): ?>
                                        <ul style="margin-top: var(--spacing-sm); font-size: 13px; color: #555; padding-left: 20px;">
                                            <?php foreach (array_slice($features, 0, 3) as $feature): ?>
                                                <li><?php echo htmlspecialchars($feature); ?></li>
                                            <?php endforeach; ?>
                                            <?php if (count($features) > 3): ?>
                                                <li><em>and more...</em></li>
                                            <?php endif; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                                <div class="service-price" style="font-size: 1.5rem; font-weight: 700; color: var(--color-charcoal); white-space: nowrap;">
                                    <?php echo formatCurrency($service['base_price']); ?>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>

                <!-- Step 2: Date & Time -->
                <div class="wizard-content step-section" id="step2">
                    <h3 style="margin-bottom: var(--spacing-lg);">Choose Date & Time</h3>

                    <div class="alert alert-info" style="margin-bottom: var(--spacing-lg);">
                        <i class="bi bi-info-circle"></i>
                        Bookings must be made at least <strong><?php echo BOOKING_ADVANCE_DAYS; ?> days</strong> in advance.
                    </div>

                    <div class="row">
                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                <label class="form-label">Event Date</label>
                                <input type="date" name="booking_date" id="booking_date" class="form-control"
                                       min="<?php echo date('Y-m-d', strtotime('+' . BOOKING_ADVANCE_DAYS . ' days')); ?>" required>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                <label class="form-label">Event Time</label>
                                <input type="time" name="booking_time" id="booking_time" class="form-control" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Event Details -->
                <div class="wizard-content step-section" id="step3">
                    <h3 style="margin-bottom: var(--spacing-lg);">Event Details</h3>

                    <div class="form-group">
                        <label class="form-label">Event Location</label>
                        <input type="text" name="event_location" id="event_location" class="form-control"
                               placeholder="e.g., Manila Hotel, Intramuros, Manila" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Additional Details</label>
                        <textarea name="event_details" id="event_details" class="form-control" rows="6"
                                  placeholder="Tell us more about your event... (e.g., number of guests, special requests, theme, specific shots you want)"><?php echo isset($_POST['event_details']) ? htmlspecialchars($_POST['event_details']) : ''; ?></textarea>
                        <small style="color: #666; font-size: 13px;">
                            Optional but recommended for better service
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Preferred Payment Method</label>
                        <select name="payment_method" id="payment_method" class="form-control" required>
                            <option value="">Select payment method</option>
                            <?php foreach (PAYMENT_METHODS as $key => $label): ?>
                                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Step 4: Review & Submit -->
                <div class="wizard-content step-section" id="step4">
                    <h3 style="margin-bottom: var(--spacing-lg);">Review Your Booking</h3>

                    <div style="background: #f8f9fa; padding: var(--spacing-lg); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg);">
                        <h4 style="margin-bottom: var(--spacing-md);">Booking Summary</h4>

                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div style="margin-bottom: var(--spacing-md);">
                                    <strong style="color: #666; display: block; font-size: 13px;">Service</strong>
                                    <span id="review_service">-</span>
                                </div>
                                <div style="margin-bottom: var(--spacing-md);">
                                    <strong style="color: #666; display: block; font-size: 13px;">Date</strong>
                                    <span id="review_date">-</span>
                                </div>
                                <div style="margin-bottom: var(--spacing-md);">
                                    <strong style="color: #666; display: block; font-size: 13px;">Time</strong>
                                    <span id="review_time">-</span>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div style="margin-bottom: var(--spacing-md);">
                                    <strong style="color: #666; display: block; font-size: 13px;">Location</strong>
                                    <span id="review_location">-</span>
                                </div>
                                <div style="margin-bottom: var(--spacing-md);">
                                    <strong style="color: #666; display: block; font-size: 13px;">Payment Method</strong>
                                    <span id="review_payment">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="booking-summary">
                        <h4 style="margin-bottom: var(--spacing-md);">Payment Breakdown</h4>
                        <div class="summary-row">
                            <span>Service Fee</span>
                            <span id="review_total">₱0.00</span>
                        </div>
                        <div class="summary-row">
                            <span>Downpayment (<?php echo DOWNPAYMENT_PERCENTAGE; ?>%)</span>
                            <span id="review_downpayment">₱0.00</span>
                        </div>
                        <div class="summary-row">
                            <span>Remaining Balance</span>
                            <span id="review_balance">₱0.00</span>
                        </div>
                    </div>

                    <div class="alert alert-warning" style="margin-top: var(--spacing-lg);">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Important:</strong> Your booking will be pending until approved by our admin team.
                        You will receive an email notification once approved with payment instructions.
                    </div>

                    <label class="form-check" style="margin-top: var(--spacing-lg);">
                        <input type="checkbox" class="form-check-input" required>
                        <span class="form-check-label">
                            I have read and agree to the
                            <a href="#" onclick="openModal('termsModal'); return false;">Terms & Conditions</a> and
                            <a href="#" onclick="openModal('refundModal'); return false;">Refund Policy</a>
                        </span>
                    </label>
                </div>

                <!-- Navigation Buttons -->
                <div class="wizard-buttons">
                    <button type="button" class="btn btn-secondary btn-lg" id="prevBtn" style="display: none;">
                        <i class="bi bi-arrow-left"></i> Previous
                    </button>
                    <div style="flex: 1;"></div>
                    <button type="button" class="btn btn-primary btn-lg" id="nextBtn">
                        Next <i class="bi bi-arrow-right"></i>
                    </button>
                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" style="display: none;">
                        <i class="bi bi-check-circle"></i> Submit Booking
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentStep = 1;
const totalSteps = 4;

function showStep(step) {
    // Hide all steps
    document.querySelectorAll('.step-section').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.wizard-step').forEach(el => el.classList.remove('active', 'completed'));

    // Show current step
    document.getElementById('step' + step).classList.add('active');

    // Update wizard indicators
    for (let i = 1; i <= totalSteps; i++) {
        if (i < step) {
            document.querySelector(`.wizard-step[data-step="${i}"]`).classList.add('completed');
        } else if (i === step) {
            document.querySelector(`.wizard-step[data-step="${i}"]`).classList.add('active');
        }
    }

    // Update buttons
    document.getElementById('prevBtn').style.display = step === 1 ? 'none' : 'block';
    document.getElementById('nextBtn').style.display = step === totalSteps ? 'none' : 'block';
    document.getElementById('submitBtn').style.display = step === totalSteps ? 'block' : 'none';

    // Update review section if on last step
    if (step === 4) {
        updateReviewSection();
    }
}

function nextStep() {
    if (validateStep(currentStep)) {
        if (currentStep < totalSteps) {
            currentStep++;
            showStep(currentStep);
        }
    }
}

function prevStep() {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
    }
}

function validateStep(step) {
    if (step === 1) {
        const selected = document.querySelector('input[name="service_id"]:checked');
        if (!selected) {
            alert('Please select a service');
            return false;
        }
    } else if (step === 2) {
        const date = document.getElementById('booking_date').value;
        const time = document.getElementById('booking_time').value;
        if (!date || !time) {
            alert('Please select both date and time');
            return false;
        }
    } else if (step === 3) {
        const location = document.getElementById('event_location').value.trim();
        const payment = document.getElementById('payment_method').value;
        if (!location || !payment) {
            alert('Please fill in all required fields');
            return false;
        }
    }
    return true;
}

function updateReviewSection() {
    const service = document.querySelector('input[name="service_id"]:checked');
    const date = document.getElementById('booking_date').value;
    const time = document.getElementById('booking_time').value;
    const location = document.getElementById('event_location').value;
    const payment = document.getElementById('payment_method');

    if (service) {
        const price = parseFloat(service.dataset.price);
        const downpayment = price * (<?php echo DOWNPAYMENT_PERCENTAGE; ?> / 100);
        const balance = price - downpayment;

        document.getElementById('review_service').textContent = service.dataset.name;
        document.getElementById('review_total').textContent = formatCurrency(price);
        document.getElementById('review_downpayment').textContent = formatCurrency(downpayment);
        document.getElementById('review_balance').textContent = formatCurrency(balance);
    }

    document.getElementById('review_date').textContent = date ? new Date(date).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'}) : '-';
    document.getElementById('review_time').textContent = time ? formatTime(time) : '-';
    document.getElementById('review_location').textContent = location || '-';
    document.getElementById('review_payment').textContent = payment.options[payment.selectedIndex]?.text || '-';
}

function formatCurrency(amount) {
    return '₱' + amount.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

function formatTime(time) {
    const [hours, minutes] = time.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}

document.getElementById('nextBtn').addEventListener('click', nextStep);
document.getElementById('prevBtn').addEventListener('click', prevStep);

// Initialize
showStep(1);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
