<?php
session_start();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="./assets/camera.png" type="image/x-icon">
    <title>Booking</title>
</head>

<body>



    <section class="w-100 min-vh-100 bg-light d-flex justify-content-center align-items-center position-relative p-2 py-5 p-md-5" id="bookingForm">

        <div class="container">
            <div class="row">
                <div class="col d-flex justify-content-center align-items-center">
                    <form action="" method="POST" class="p-4 px-3 bg-white rounded" style="max-width: 800px;">

                        <div class="text-center ">
                            <h1 class=" display-4 m-0 serif">Book Your Shoot Today</h1>
                            <small class="text-muted">Capture your story with precision, creativity, and style. Fill out the form below to reserve your photography or videography session.
                                Our team will review your details and reach out promptly to confirm your booking and discuss the finer details of your shoot.
                            </small>
                        </div>

                        <!------------------------------------- Client's Information ------------------------------------>

                        <fieldset class="border rounded  p-3 mt-5">
                            <span class="serif" id="legend">Client Information</span>
                            <!-- First and Last Name  -->

                            <div class="mb-2 d-flex gap-2 flex-column flex-md-row ">
                                <div class="w-100">
                                    <label for="fname" class="form-label">First name<span class="text-danger">*</span></label>
                                    <input type="text" name="fname" id="fname" class="form-control text-muted" value="<?php echo ($_SESSION["firstName"] ?? '') ?>" required readonly>
                                </div>

                                <div class="w-100">
                                    <label for="lname" class="form-label">Last name<span class="text-danger">*</span></label>
                                    <input type="text" name="lname" id="lname" class="form-control text-muted" value="<?php echo ($_SESSION["lastName"] ?? '') ?>" readonly>
                                </div>
                            </div>

                            <!-- Email  -->

                            <div class="mb-2">
                                <label class="form-label" for="email">Email<span class="text-danger">*</span></label>
                                <input type="email" name="email" id="email" class="form-control text-muted" value="<?php echo ($_SESSION["email"] ?? '') ?>" readonly>
                            </div>

                            <!-- Contact Number -->

                            <div class="mb-2">
                                <label class="form-label" for="phone">Contact No.<span class="text-danger">*</span></label>
                                <input type="text" name="phone" id="phone" class="form-control" required>
                            </div>



                        </fieldset>


                        <!------------------------------------- Event's Information ------------------------------------>

                        <fieldset class="border rounded  p-3 mt-4">
                            <span class="serif" id="legend">Event Details</span>
                            <!-- First and Last Name  -->

                            <div class="mb-2 ">

                                <label for="eventType" class="form-label">Event Type<span class="text-danger">*</span></label>
                                <select name="eventType" id="eventType" class="form-select">
                                    <option selected disabled>--Select Event Type--</option>
                                    <option value="Weddings & Engagements">Weddings & Engagements</option>
                                    <option value="Corporate Events">Corporate Events</option>
                                    <option value="Birthdays & Celebrations">Birthdays & Celebrations</option>
                                    <option value="Creative Shoots">Creative Shoots</option>
                                    <option value="Behind the Lens (Videography)">Behind the Lens (Videography)</option>
                                </select>



                            </div>

                            <!-- Event Date & Time  -->
                            <div class="mb-2">
                                <label class="form-label" for="eventDate">Event Date & Time<span class="text-danger">*</span></label>
                                <input type="date" name="eventDate" id="eventDate" class="form-control text-muted" required>
                            </div>


                            <div class="mb-2 d-flex justify-content-center align-items-center gap-3 flex-column flex-md-row">
                                <div class="w-100">
                                    <label class="form-label" for="fromTime">From:<span class="text-danger">*</span></label>
                                    <input type="date" name="fromTime" id="fromTime" class="form-control text-muted" required>
                                </div>

                                <div class="w-100">
                                    <label class="form-label" for="toTime">To:<span class="text-danger">*</span></label>
                                    <input type="date" name="toTime" id="toTime" class="form-control text-muted" required>
                                </div>
                            </div>

                            <!-- Contact Number -->

                            <div class="mb-2">
                                <label class="form-label" for="location">Location (Full Address)<span class="text-danger">*</span></label>
                                <input type="text" name="location" id="location" class="form-control" required>
                            </div>

                            <div class="mb-2">
                                <label class="form-label" for="location">Landmark<span class="text-danger">*</span> </label>
                                <input type="text" name="location" id="location" class="form-control">
                            </div>
                        </fieldset>


                        <!------------------------------------- Service Selectionn ------------------------------------>

                        <fieldset class="border rounded  p-3 mt-4">
                            <span class="serif" id="legend">Service Selection</span>
                            <div class="mb-3">
                                <label for="package" class="form-label">Select Package</label>
                                <select id="package" class="form-select" required>
                                    <option value="" disabled selected>Choose a package</option>
                                    <option value="basic">Basic Package — ₱8,000</option>
                                    <option value="premium">Premium Package — ₱15,000</option>
                                    <option value="elite">Elite Package — ₱25,000</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="addons" class="form-label">Available Add-ons</label>
                                <select id="addons" class="form-select">
                                    <option selected disabled>Select a package first</option>
                                </select>
                            </div>
                        </fieldset>

                    </form>
                </div>
            </div>
        </div>


    </section>





    <?php include './includes/footer.php'; ?>
    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script src="./script.js"></script>
    <script>
        
    </script>
</body>

</html>
<!-- 
 <div class="modal fade" id="dataModal" data-bs-backdrop="static" tabindex="-1" aria-labelledby="dataModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="dataModalLabel">Terms & Privacy Policy</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <h6>Aperture Data Privacy Notice</h6>

                                    <p>At Aperture, we value your privacy and are committed to protecting your personal information. This notice explains how we collect, use, and safeguard data when you book a photography session through our platform. We comply with applicable laws like RA10173 or Data Privacy Act of 2012, collecting only what's necessary for your booking experience.</p>



                                    <h6>What Information We Collect</h6>

                                    <ul>
                                        <li><strong>Personal Details: </strong>Full name, email address, and phone number (for confirmations and coordination).</li>
                                        <li><strong>Booking Information: </strong>Service type, preferred date/time, location (e.g., via cascading dropdowns or autocomplete), number of subjects, and special notes.</li>
                                        <li><strong>Payment Data: </strong>Processed securely via third-party providers (e.g., Stripe)—we don't store full card details.</li>
                                        <li><strong>Technical Data: </strong>IP address, device type, and usage analytics (anonymized) to improve our site.</li>
                                    </ul>

                                    <p>We do not collect sensitive information like photos, IDs, or health data unless voluntarily provided in notes.</p>




                                    <h6>How We Use Your Information</h6>
                                    <p>Your data enables a seamless service:</p>
                                    <ul>
                                        <li><strong>Fulfill Bookings:</strong> Schedule sessions, match photographers, and send reminders/receipts.</li>
                                        <li><strong>Communications:</strong> Email/SMS confirmations, updates, and support (e.g., via PHPMailer).</li>
                                        <li><strong>Payments & Logistics:</strong> Process transactions and calculate fees (e.g., based on location).</li>
                                        <li><strong>Improvements:</strong> Anonymized analytics to enhance UX (e.g., form completion rates).</li>
                                    </ul>
                                    <p>We never sell your data. It may be shared with trusted partners (e.g., payment processors, email services) under strict agreements.</p>

                                    <h6>How We Protect and Store Your Data</h6>
                                    <ul>
                                        <li><strong>Security:</strong> All data is encrypted (HTTPS), stored securely in our database (e.g., MySQL with hashed elements), and access-controlled.</li>
                                        <li><strong>Retention:</strong> Kept only as needed (e.g., 2 years post-booking for records); deleted upon request or completion.</li>
                                        <li><strong>Cookies:</strong> We use essential cookies for sessions; optional analytics (e.g., Google) require consent.</li>
                                    </ul>
                                    <h6>Your Rights and Choices</h6>
                                    <ul>
                                        <li><strong>Access/Update/Delete:</strong> Contact us to view, correct, or erase your data (e.g., for one-time bookings).</li>
                                        <li><strong>Opt-Out:</strong> Unsubscribe from emails anytime; withdraw consent for non-essential processing.</li>
                                        <li><strong>Complaints:</strong> If concerned, reach out to us or your local data authority (e.g., NPC in the Philippines).</li>
                                    </ul>
                                    <p>For questions, email aperture.eventbookings@gmail.com or use our contact form. Last updated: September 2025. By using our site, you agree to this notice—review it anytime.</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div> -->