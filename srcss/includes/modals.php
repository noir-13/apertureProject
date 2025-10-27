<!-- Modal Styles -->
<style>
.modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.8);
    animation: fadeIn 0.3s ease;
}

.modal-content {
    background: var(--color-white);
    margin: 5% auto;
    padding: 0;
    border-radius: var(--radius-lg);
    width: 90%;
    max-width: 700px;
    box-shadow: var(--shadow-xl);
    animation: fadeInUp 0.3s ease;
}

.modal-header {
    padding: var(--spacing-lg);
    border-bottom: 2px solid var(--color-gold);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: var(--color-black);
    font-family: var(--font-heading);
}

.modal-close {
    color: var(--color-charcoal);
    font-size: 32px;
    font-weight: bold;
    cursor: pointer;
    background: none;
    border: none;
    transition: var(--transition-base);
}

.modal-close:hover {
    color: var(--color-gold);
}

.modal-body {
    padding: var(--spacing-lg);
    max-height: 60vh;
    overflow-y: auto;
    color: var(--color-charcoal);
    line-height: 1.8;
}

.modal-footer {
    padding: var(--spacing-md) var(--spacing-lg);
    border-top: 1px solid #ddd;
    text-align: right;
}
</style>

<!-- Terms & Conditions Modal -->
<div id="termsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Terms & Conditions</h3>
            <button class="modal-close" onclick="closeModal('termsModal')">&times;</button>
        </div>
        <div class="modal-body">
            <h4>1. Booking Agreement</h4>
            <p>By making a booking with ApertureStudios, you agree to the following terms and conditions. All bookings require a non-refundable downpayment of <?php echo DOWNPAYMENT_PERCENTAGE; ?>% of the total service fee.</p>

            <h4>2. Payment Terms</h4>
            <p>A downpayment of <?php echo DOWNPAYMENT_PERCENTAGE; ?>% is required upon booking confirmation. The remaining balance must be paid at least 3 days before the event date. Failure to complete payment may result in service cancellation.</p>

            <h4>3. Booking Confirmation</h4>
            <p>All bookings are subject to admin approval. Once approved, you will receive a confirmation email with booking details and payment instructions. Bookings must be made at least <?php echo BOOKING_ADVANCE_DAYS; ?> days in advance.</p>

            <h4>4. Cancellation Policy</h4>
            <p>Cancellations must be requested at least <?php echo CANCELLATION_PERIOD_DAYS; ?> days before the scheduled event date. Downpayments are non-refundable. In case of emergency cancellations, please contact us immediately.</p>

            <h4>5. Service Delivery</h4>
            <p>All edited photos and videos will be delivered within 4-8 weeks after the event date, depending on the package selected. Rush delivery options are available for an additional fee.</p>

            <h4>6. Copyright & Usage Rights</h4>
            <p>ApertureStudios retains all copyrights to the photos and videos. Clients receive personal usage rights. We reserve the right to use images for portfolio, marketing, and promotional purposes unless otherwise agreed in writing.</p>

            <h4>7. Weather & Unforeseen Circumstances</h4>
            <p>We will make every effort to accommodate rescheduling due to weather or unforeseen circumstances. If rescheduling is not possible, you may apply your downpayment to a future booking within one year.</p>

            <h4>8. Client Responsibilities</h4>
            <p>Clients must provide accurate event details, location information, and timeline. Any changes to the event schedule must be communicated at least 48 hours in advance.</p>

            <h4>9. Liability</h4>
            <p>ApertureStudios is not responsible for missed moments due to client-related delays, venue restrictions, or technical failures beyond our control. We maintain backup equipment to minimize technical issues.</p>

            <h4>10. Agreement</h4>
            <p>By completing your booking, you acknowledge that you have read, understood, and agree to these terms and conditions.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('termsModal')">Close</button>
        </div>
    </div>
</div>

<!-- Privacy Policy Modal -->
<div id="privacyModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Privacy Policy</h3>
            <button class="modal-close" onclick="closeModal('privacyModal')">&times;</button>
        </div>
        <div class="modal-body">
            <h4>1. Information We Collect</h4>
            <p>We collect personal information including your name, email address, phone number, event details, and payment information when you book our services. This information is used solely for service delivery and communication purposes.</p>

            <h4>2. How We Use Your Information</h4>
            <p>Your information is used to:</p>
            <ul>
                <li>Process and confirm your bookings</li>
                <li>Communicate about your event and service details</li>
                <li>Send booking confirmations and receipts</li>
                <li>Provide customer support</li>
                <li>Improve our services</li>
            </ul>

            <h4>3. Data Security</h4>
            <p>We implement industry-standard security measures to protect your personal information. All payment information is processed through secure payment gateways and is never stored on our servers.</p>

            <h4>4. Information Sharing</h4>
            <p>We do not sell, trade, or share your personal information with third parties except:</p>
            <ul>
                <li>When required by law</li>
                <li>To process payments through our payment partners</li>
                <li>With your explicit consent</li>
            </ul>

            <h4>5. Cookies & Tracking</h4>
            <p>Our website uses cookies to enhance user experience and analyze site traffic. You can disable cookies in your browser settings, though some features may not function properly.</p>

            <h4>6. Photo & Video Usage</h4>
            <p>Images and videos from your event may be used in our portfolio, website, and social media for marketing purposes. If you prefer your media not be used publicly, please notify us in writing.</p>

            <h4>7. Data Retention</h4>
            <p>We retain your booking information and event details for a minimum of 2 years for business records and potential future bookings. You may request data deletion after this period.</p>

            <h4>8. Your Rights</h4>
            <p>You have the right to:</p>
            <ul>
                <li>Access your personal data</li>
                <li>Request corrections to your data</li>
                <li>Request deletion of your data</li>
                <li>Opt-out of marketing communications</li>
            </ul>

            <h4>9. Contact Us</h4>
            <p>For privacy-related questions or requests, please contact us at privacy@aperturestudios.com</p>

            <h4>10. Policy Updates</h4>
            <p>We may update this privacy policy periodically. Changes will be posted on our website with the updated effective date.</p>

            <p><strong>Last Updated:</strong> <?php echo date('F Y'); ?></p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('privacyModal')">Close</button>
        </div>
    </div>
</div>

<!-- Refund Policy Modal -->
<div id="refundModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Refund Policy</h3>
            <button class="modal-close" onclick="closeModal('refundModal')">&times;</button>
        </div>
        <div class="modal-body">
            <h4>1. Non-Refundable Downpayment</h4>
            <p>The <?php echo DOWNPAYMENT_PERCENTAGE; ?>% downpayment required to confirm your booking is non-refundable. This downpayment secures your date and compensates for our commitment to your event.</p>

            <h4>2. Cancellation Before Event</h4>
            <p>If you cancel your booking at least <?php echo CANCELLATION_PERIOD_DAYS; ?> days before the event date:</p>
            <ul>
                <li>Downpayment is non-refundable</li>
                <li>Any additional payments made will be refunded within 14 business days</li>
                <li>Cancellations must be submitted in writing through your account dashboard</li>
            </ul>

            <h4>3. Late Cancellations</h4>
            <p>Cancellations made less than <?php echo CANCELLATION_PERIOD_DAYS; ?> days before the event:</p>
            <ul>
                <li>No refund will be issued</li>
                <li>You may reschedule to a new date within 6 months, subject to availability</li>
                <li>One-time rescheduling fee of ₱2,000 will apply</li>
            </ul>

            <h4>4. Service Not Provided</h4>
            <p>In the rare event that ApertureStudios cannot fulfill the service due to unforeseen circumstances:</p>
            <ul>
                <li>Full refund of all payments made</li>
                <li>We will make every effort to provide a qualified replacement photographer/videographer</li>
            </ul>

            <h4>5. Weather & Force Majeure</h4>
            <p>For outdoor events affected by severe weather or other force majeure events:</p>
            <ul>
                <li>Free rescheduling to a new date within one year</li>
                <li>No refund of downpayment</li>
                <li>Mutual agreement required for alternative arrangements</li>
            </ul>

            <h4>6. Refund Request Process</h4>
            <p>To request a refund:</p>
            <ol>
                <li>Log in to your account</li>
                <li>Navigate to "My Bookings"</li>
                <li>Select the booking and click "Request Refund"</li>
                <li>Provide reason for refund request</li>
                <li>Admin will review within 3-5 business days</li>
            </ol>

            <h4>7. Refund Processing Time</h4>
            <p>Approved refunds will be processed within 14 business days and returned to the original payment method. Bank processing times may vary.</p>

            <h4>8. Partial Service Delivery</h4>
            <p>If we are unable to complete the full service as booked (e.g., equipment failure, shortened coverage time):</p>
            <ul>
                <li>Partial refund proportional to service not delivered</li>
                <li>Discount on future bookings</li>
                <li>Case-by-case evaluation by management</li>
            </ul>

            <h4>9. Dissatisfaction with Final Product</h4>
            <p>We strive for excellence in all deliverables. If you are unsatisfied with the final edited product:</p>
            <ul>
                <li>Request revisions within 30 days of delivery</li>
                <li>We offer one round of minor revisions at no charge</li>
                <li>Refunds are not provided for stylistic preferences</li>
            </ul>

            <h4>10. Contact for Refund Inquiries</h4>
            <p>For questions about refunds, please contact our customer service at refunds@aperturestudios.com or through your account dashboard.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('refundModal')">Close</button>
        </div>
    </div>
</div>

<!-- Booking Confirmation Modal -->
<div id="bookingConfirmModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirm Your Booking</h3>
            <button class="modal-close" onclick="closeModal('bookingConfirmModal')">&times;</button>
        </div>
        <div class="modal-body" id="bookingConfirmContent">
            <!-- Content will be populated dynamically -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('bookingConfirmModal')">Cancel</button>
            <button class="btn btn-primary" onclick="submitBooking()">Confirm Booking</button>
        </div>
    </div>
</div>

<!-- Generic Confirmation Modal -->
<div id="confirmModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="confirmModalTitle">Confirm Action</h3>
            <button class="modal-close" onclick="closeModal('confirmModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p id="confirmModalMessage">Are you sure you want to proceed?</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('confirmModal')">Cancel</button>
            <button class="btn btn-primary" id="confirmModalButton" onclick="confirmAction()">Confirm</button>
        </div>
    </div>
</div>

<script>
// Modal functions
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Confirmation modal with callback
let confirmCallback = null;

function showConfirmModal(title, message, callback) {
    document.getElementById('confirmModalTitle').textContent = title;
    document.getElementById('confirmModalMessage').textContent = message;
    confirmCallback = callback;
    openModal('confirmModal');
}

function confirmAction() {
    if (confirmCallback && typeof confirmCallback === 'function') {
        confirmCallback();
    }
    closeModal('confirmModal');
    confirmCallback = null;
}
</script>
