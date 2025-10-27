<?php
$page_title = "Home";
require_once __DIR__ . '/includes/header.php';

// Fetch services
$db = getDB();
$services = $db->query("SELECT * FROM services WHERE is_active = TRUE ORDER BY base_price ASC")->fetchAll();

// Fetch portfolio
$portfolio = $db->query("SELECT * FROM portfolio WHERE is_active = TRUE ORDER BY display_order ASC, portfolio_id DESC LIMIT 6")->fetchAll();

// Fetch testimonials
$testimonials = $db->query("SELECT * FROM testimonials WHERE is_approved = TRUE ORDER BY is_featured DESC, testimonial_id DESC LIMIT 6")->fetchAll();
?>

<!-- Hero Section -->
<section class="hero">
    <video class="hero-video" autoplay muted loop playsinline>
        <source src="<?php echo SITE_URL; ?>/assets/images/hero-video.mp4" type="video/mp4">
        <!-- Fallback for browsers that don't support video -->
    </video>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1 class="hero-title">Capturing Timeless Moments</h1>
        <p class="hero-subtitle">Premium Videography & Photography Services</p>
        <div class="hero-buttons">
            <a href="<?php echo SITE_URL; ?>/register.php" class="btn btn-primary btn-lg">Book Now</a>
            <a href="#services" class="btn btn-white btn-lg">Explore Services</a>
            <a href="#portfolio" class="btn btn-outline btn-lg">View Portfolio</a>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="section" id="about">
    <div class="container">
        <div class="section-title">
            <h2>About ApertureStudios</h2>
        </div>
        <p class="section-subtitle">
            With over a decade of experience, we specialize in capturing life's most precious moments
            with cinematic excellence and artistic vision. Our team of passionate videographers and photographers
            are dedicated to telling your unique story.
        </p>
        <div class="row" style="margin-top: var(--spacing-xl);">
            <div class="col-12 col-md-4">
                <div class="card text-center" style="height: 100%;">
                    <i class="bi bi-camera-reels" style="font-size: 48px; color: var(--color-gold); margin-bottom: var(--spacing-md);"></i>
                    <h4>Professional Equipment</h4>
                    <p>State-of-the-art cameras, lenses, and drone technology for stunning visuals.</p>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card text-center" style="height: 100%;">
                    <i class="bi bi-trophy" style="font-size: 48px; color: var(--color-gold); margin-bottom: var(--spacing-md);"></i>
                    <h4>Award-Winning Team</h4>
                    <p>Experienced professionals with international recognition and accolades.</p>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card text-center" style="height: 100%;">
                    <i class="bi bi-heart" style="font-size: 48px; color: var(--color-gold); margin-bottom: var(--spacing-md);"></i>
                    <h4>Personalized Service</h4>
                    <p>Tailored packages and dedicated attention to bring your vision to life.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="section section-light" id="services">
    <div class="container">
        <div class="section-title">
            <h2>Our Services</h2>
        </div>
        <p class="section-subtitle">
            From intimate gatherings to grand celebrations, we offer comprehensive photography
            and videography services tailored to your needs.
        </p>
        <div class="row" style="margin-top: var(--spacing-xl);">
            <?php foreach ($services as $service): ?>
                <?php $features = json_decode($service['features'], true); ?>
                <div class="col-12 col-md-6" style="margin-bottom: var(--spacing-lg);">
                    <div class="card" style="height: 100%; border-left: 4px solid var(--color-gold);">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: var(--spacing-md);">
                            <h3 style="margin: 0;"><?php echo htmlspecialchars($service['service_name']); ?></h3>
                            <span class="badge badge-primary" style="font-size: 16px; padding: 8px 16px;">
                                <?php echo formatCurrency($service['base_price']); ?>
                            </span>
                        </div>
                        <p style="color: #666; margin-bottom: var(--spacing-md);">
                            <?php echo htmlspecialchars($service['description']); ?>
                        </p>
                        <div style="margin-bottom: var(--spacing-md);">
                            <strong style="color: var(--color-charcoal);">
                                <i class="bi bi-clock"></i> Duration: <?php echo $service['duration_hours']; ?> hours
                            </strong>
                        </div>
                        <?php if ($features): ?>
                            <h5 style="margin-top: var(--spacing-md); margin-bottom: var(--spacing-sm);">Package Includes:</h5>
                            <ul style="list-style: none; padding: 0;">
                                <?php foreach ($features as $feature): ?>
                                    <li style="padding: 6px 0; color: #555;">
                                        <i class="bi bi-check-circle-fill" style="color: var(--color-gold); margin-right: 8px;"></i>
                                        <?php echo htmlspecialchars($feature); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <a href="<?php echo SITE_URL; ?>/register.php" class="btn btn-primary" style="margin-top: var(--spacing-md);">
                            Book This Service
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Portfolio Section -->
<section class="section section-dark" id="portfolio">
    <div class="container">
        <div class="section-title">
            <h2 style="color: var(--color-white);">Our Portfolio</h2>
        </div>
        <p class="section-subtitle" style="color: var(--color-beige);">
            Explore our finest work and see how we transform moments into lasting memories.
        </p>
        <div class="row" style="margin-top: var(--spacing-xl);">
            <?php foreach ($portfolio as $item): ?>
                <div class="col-12 col-md-4" style="margin-bottom: var(--spacing-lg);">
                    <div class="card card-glass" style="padding: 0; overflow: hidden; height: 100%;">
                        <div style="position: relative; height: 250px; overflow: hidden;">
                            <img src="<?php echo SITE_URL; ?>/uploads/<?php echo htmlspecialchars($item['image_url']); ?>"
                                 alt="<?php echo htmlspecialchars($item['title']); ?>"
                                 style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;"
                                 onmouseover="this.style.transform='scale(1.1)'"
                                 onmouseout="this.style.transform='scale(1)'">
                            <?php if ($item['video_url']): ?>
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                    <i class="bi bi-play-circle-fill" style="font-size: 64px; color: var(--color-gold); opacity: 0.9;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="padding: var(--spacing-md);">
                            <span class="badge badge-primary" style="margin-bottom: var(--spacing-sm);">
                                <?php echo ucfirst($item['category']); ?>
                            </span>
                            <h4 style="color: var(--color-white); margin-bottom: var(--spacing-sm);">
                                <?php echo htmlspecialchars($item['title']); ?>
                            </h4>
                            <p style="color: var(--color-beige); font-size: 14px;">
                                <?php echo htmlspecialchars($item['description']); ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Pricing Section -->
<section class="section" id="pricing">
    <div class="container">
        <div class="section-title">
            <h2>Pricing Plans</h2>
        </div>
        <p class="section-subtitle">
            Transparent pricing with flexible packages to suit every budget and occasion.
        </p>
        <div class="row" style="margin-top: var(--spacing-xl); justify-content: center;">
            <?php foreach ($services as $index => $service): ?>
                <div class="col-12 col-md-3" style="margin-bottom: var(--spacing-lg);">
                    <div class="card <?php echo $index === 0 ? 'card-dark' : ''; ?>"
                         style="height: 100%; text-align: center; position: relative; <?php echo $index === 0 ? 'border: 3px solid var(--color-gold);' : ''; ?>">
                        <?php if ($index === 0): ?>
                            <div style="position: absolute; top: -15px; left: 50%; transform: translateX(-50%); background: var(--gradient-gold); padding: 6px 20px; border-radius: 20px; font-weight: 600; font-size: 12px;">
                                MOST POPULAR
                            </div>
                        <?php endif; ?>
                        <h3 style="margin-top: var(--spacing-md);"><?php echo htmlspecialchars($service['service_name']); ?></h3>
                        <div style="margin: var(--spacing-lg) 0;">
                            <div style="font-size: 3rem; font-weight: 700; color: var(--color-gold); font-family: var(--font-heading);">
                                <?php echo formatCurrency($service['base_price']); ?>
                            </div>
                            <div style="color: #999; margin-top: var(--spacing-xs);">
                                <?php echo $service['duration_hours']; ?> hours coverage
                            </div>
                        </div>
                        <a href="<?php echo SITE_URL; ?>/register.php" class="btn btn-primary btn-block">
                            Choose Plan
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align: center; margin-top: var(--spacing-xl); padding: var(--spacing-lg); background: var(--color-beige); border-radius: var(--radius-lg);">
            <h4 style="margin-bottom: var(--spacing-md);">
                <i class="bi bi-info-circle" style="color: var(--color-gold);"></i>
                Booking Information
            </h4>
            <div class="row">
                <div class="col-12 col-md-4">
                    <strong>Downpayment Required:</strong><br>
                    <?php echo DOWNPAYMENT_PERCENTAGE; ?>% of total amount
                </div>
                <div class="col-12 col-md-4">
                    <strong>Book in Advance:</strong><br>
                    At least <?php echo BOOKING_ADVANCE_DAYS; ?> days before event
                </div>
                <div class="col-12 col-md-4">
                    <strong>Delivery Time:</strong><br>
                    4-8 weeks after event
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="section section-light" id="testimonials">
    <div class="container">
        <div class="section-title">
            <h2>Client Testimonials</h2>
        </div>
        <p class="section-subtitle">
            Don't just take our word for it - hear what our satisfied clients have to say.
        </p>
        <div class="row" style="margin-top: var(--spacing-xl);">
            <?php foreach ($testimonials as $testimonial): ?>
                <div class="col-12 col-md-4" style="margin-bottom: var(--spacing-lg);">
                    <div class="card" style="height: 100%;">
                        <div style="display: flex; align-items: center; margin-bottom: var(--spacing-md);">
                            <img src="<?php echo SITE_URL; ?>/uploads/<?php echo htmlspecialchars($testimonial['client_photo']); ?>"
                                 alt="<?php echo htmlspecialchars($testimonial['client_name']); ?>"
                                 style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; margin-right: var(--spacing-md); border: 3px solid var(--color-gold);"
                                 onerror="this.src='<?php echo SITE_URL; ?>/assets/images/default-avatar.png'">
                            <div>
                                <h5 style="margin: 0;"><?php echo htmlspecialchars($testimonial['client_name']); ?></h5>
                                <div style="color: var(--color-gold);">
                                    <?php for ($i = 0; $i < $testimonial['rating']; $i++): ?>
                                        <i class="bi bi-star-fill"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                        <p style="font-style: italic; color: #555; line-height: 1.8;">
                            "<?php echo htmlspecialchars($testimonial['review_text']); ?>"
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="section section-dark" id="contact">
    <div class="container">
        <div class="section-title">
            <h2 style="color: var(--color-white);">Get In Touch</h2>
        </div>
        <p class="section-subtitle" style="color: var(--color-beige);">
            Have questions? We'd love to hear from you. Send us a message and we'll respond as soon as possible.
        </p>
        <div class="row" style="margin-top: var(--spacing-xl);">
            <div class="col-12 col-md-6">
                <div class="card card-glass">
                    <h4 style="color: var(--color-white); margin-bottom: var(--spacing-md);">Contact Information</h4>
                    <div style="color: var(--color-beige); line-height: 2;">
                        <p>
                            <i class="bi bi-envelope" style="color: var(--color-gold); margin-right: 10px;"></i>
                            <strong>Email:</strong> contact@aperturestudios.com
                        </p>
                        <p>
                            <i class="bi bi-telephone" style="color: var(--color-gold); margin-right: 10px;"></i>
                            <strong>Phone:</strong> +63 917 123 4567
                        </p>
                        <p>
                            <i class="bi bi-geo-alt" style="color: var(--color-gold); margin-right: 10px;"></i>
                            <strong>Location:</strong> Manila, Philippines
                        </p>
                        <p>
                            <i class="bi bi-clock" style="color: var(--color-gold); margin-right: 10px;"></i>
                            <strong>Hours:</strong> Mon-Sat, 9AM-6PM
                        </p>
                    </div>
                    <div style="margin-top: var(--spacing-lg);">
                        <h5 style="color: var(--color-white); margin-bottom: var(--spacing-md);">Follow Us</h5>
                        <div style="display: flex; gap: var(--spacing-md);">
                            <a href="#" style="color: var(--color-gold); font-size: 28px;">
                                <i class="bi bi-facebook"></i>
                            </a>
                            <a href="#" style="color: var(--color-gold); font-size: 28px;">
                                <i class="bi bi-instagram"></i>
                            </a>
                            <a href="#" style="color: var(--color-gold); font-size: 28px;">
                                <i class="bi bi-youtube"></i>
                            </a>
                            <a href="#" style="color: var(--color-gold); font-size: 28px;">
                                <i class="bi bi-twitter"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="card card-glass">
                    <h4 style="color: var(--color-white); margin-bottom: var(--spacing-md);">Send Us a Message</h4>
                    <form id="contactForm" action="<?php echo SITE_URL; ?>/contact-submit.php" method="POST">
                        <div class="form-group">
                            <label class="form-label" style="color: var(--color-beige);">Your Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="color: var(--color-beige);">Email Address</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="color: var(--color-beige);">Subject</label>
                            <input type="text" name="subject" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="color: var(--color-beige);">Message</label>
                            <textarea name="message" class="form-control" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
