    <!-- Footer -->
    <footer style="background: var(--gradient-dark); color: var(--color-white); padding: var(--spacing-2xl) 0;">
        <div class="container">
            <div class="row">
                <div class="col-12 col-md-4" style="padding: var(--spacing-md);">
                    <h4 style="color: var(--color-gold); margin-bottom: var(--spacing-md);">ApertureStudios</h4>
                    <p style="color: var(--color-beige); margin-bottom: var(--spacing-md);">
                        Capturing life's most precious moments with cinematic excellence and artistic vision.
                    </p>
                    <div style="display: flex; gap: var(--spacing-sm); margin-top: var(--spacing-md);">
                        <a href="#" style="color: var(--color-gold); font-size: 24px; transition: var(--transition-base);">
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a href="#" style="color: var(--color-gold); font-size: 24px; transition: var(--transition-base);">
                            <i class="bi bi-instagram"></i>
                        </a>
                        <a href="#" style="color: var(--color-gold); font-size: 24px; transition: var(--transition-base);">
                            <i class="bi bi-youtube"></i>
                        </a>
                        <a href="#" style="color: var(--color-gold); font-size: 24px; transition: var(--transition-base);">
                            <i class="bi bi-twitter"></i>
                        </a>
                    </div>
                </div>

                <div class="col-12 col-md-2" style="padding: var(--spacing-md);">
                    <h5 style="color: var(--color-gold); margin-bottom: var(--spacing-md);">Quick Links</h5>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: var(--spacing-xs);">
                            <a href="<?php echo SITE_URL; ?>/index.php" style="color: var(--color-beige);">Home</a>
                        </li>
                        <li style="margin-bottom: var(--spacing-xs);">
                            <a href="<?php echo SITE_URL; ?>/index.php#services" style="color: var(--color-beige);">Services</a>
                        </li>
                        <li style="margin-bottom: var(--spacing-xs);">
                            <a href="<?php echo SITE_URL; ?>/index.php#portfolio" style="color: var(--color-beige);">Portfolio</a>
                        </li>
                        <li style="margin-bottom: var(--spacing-xs);">
                            <a href="<?php echo SITE_URL; ?>/index.php#pricing" style="color: var(--color-beige);">Pricing</a>
                        </li>
                    </ul>
                </div>

                <div class="col-12 col-md-2" style="padding: var(--spacing-md);">
                    <h5 style="color: var(--color-gold); margin-bottom: var(--spacing-md);">Legal</h5>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: var(--spacing-xs);">
                            <a href="#" onclick="openModal('termsModal'); return false;" style="color: var(--color-beige);">Terms & Conditions</a>
                        </li>
                        <li style="margin-bottom: var(--spacing-xs);">
                            <a href="#" onclick="openModal('privacyModal'); return false;" style="color: var(--color-beige);">Privacy Policy</a>
                        </li>
                        <li style="margin-bottom: var(--spacing-xs);">
                            <a href="#" onclick="openModal('refundModal'); return false;" style="color: var(--color-beige);">Refund Policy</a>
                        </li>
                    </ul>
                </div>

                <div class="col-12 col-md-4" style="padding: var(--spacing-md);">
                    <h5 style="color: var(--color-gold); margin-bottom: var(--spacing-md);">Newsletter</h5>
                    <p style="color: var(--color-beige); margin-bottom: var(--spacing-md);">
                        Subscribe to get updates on our latest work and offers.
                    </p>
                    <form id="newsletterForm" style="display: flex; gap: var(--spacing-sm);">
                        <input type="email" placeholder="Your email" required
                               style="flex: 1; padding: 12px; border-radius: var(--radius-md); border: none;">
                        <button type="submit" class="btn btn-primary">Subscribe</button>
                    </form>
                </div>
            </div>

            <div style="border-top: 1px solid rgba(200, 169, 81, 0.2); margin-top: var(--spacing-xl); padding-top: var(--spacing-md); text-align: center;">
                <p style="color: var(--color-beige); margin: 0;">
                    &copy; <?php echo date('Y'); ?> ApertureStudios. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    <!-- Modals -->
    <?php include __DIR__ . '/modals.php'; ?>

    <!-- Scripts -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>

    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
