-- ApertureStudios Videography Appointment System Database Schema
-- Create Database
CREATE DATABASE IF NOT EXISTS aperturestudios_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE aperturestudios_db;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('client', 'photographer', 'admin') DEFAULT 'client',
    profile_photo VARCHAR(255) DEFAULT 'default-avatar.png',
    phone VARCHAR(20),
    account_status ENUM('active', 'suspended', 'deleted') DEFAULT 'active',
    deletion_reason TEXT NULL,
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(255),
    reset_token VARCHAR(255),
    reset_token_expiry DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (account_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Services Table
CREATE TABLE IF NOT EXISTS services (
    service_id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL,
    description TEXT,
    base_price DECIMAL(10, 2) NOT NULL,
    duration_hours INT DEFAULT 4,
    features JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bookings Table
CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_id INT NOT NULL,
    booking_reference VARCHAR(50) UNIQUE NOT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    event_location VARCHAR(255) NOT NULL,
    event_details TEXT,
    total_amount DECIMAL(10, 2) NOT NULL,
    downpayment_amount DECIMAL(10, 2) NOT NULL,
    remaining_balance DECIMAL(10, 2) NOT NULL,
    booking_status ENUM('pending', 'confirmed', 'completed', 'cancelled', 'rejected') DEFAULT 'pending',
    payment_status ENUM('unpaid', 'downpayment_paid', 'fully_paid') DEFAULT 'unpaid',
    admin_notes TEXT,
    rejection_reason TEXT,
    photographer_assigned INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE RESTRICT,
    FOREIGN KEY (photographer_assigned) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_booking_date (booking_date),
    INDEX idx_status (booking_status),
    INDEX idx_user (user_id),
    INDEX idx_reference (booking_reference)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payments Table
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    payment_reference VARCHAR(50) UNIQUE NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('gcash', 'bank_transfer', 'credit_card', 'cash', 'paymaya') NOT NULL,
    payment_type ENUM('downpayment', 'full_payment', 'remaining_balance', 'refund') NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(100),
    payment_proof VARCHAR(255),
    payment_date DATETIME,
    refund_amount DECIMAL(10, 2) DEFAULT 0.00,
    refund_date DATETIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_booking (booking_id),
    INDEX idx_status (payment_status),
    INDEX idx_reference (payment_reference)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Refund Requests Table
CREATE TABLE IF NOT EXISTS refund_requests (
    refund_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    payment_id INT NOT NULL,
    refund_amount DECIMAL(10, 2) NOT NULL,
    reason TEXT NOT NULL,
    request_status ENUM('pending', 'approved', 'rejected', 'processed') DEFAULT 'pending',
    admin_response TEXT,
    processed_by INT,
    processed_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (payment_id) REFERENCES payments(payment_id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_status (request_status),
    INDEX idx_booking (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    notification_type ENUM('booking', 'payment', 'refund', 'account', 'system') NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    related_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_type (notification_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Portfolio Table
CREATE TABLE IF NOT EXISTS portfolio (
    portfolio_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    image_url VARCHAR(255) NOT NULL,
    video_url VARCHAR(255),
    category ENUM('wedding', 'corporate', 'event', 'studio', 'other') NOT NULL,
    display_order INT DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_featured (is_featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Testimonials Table
CREATE TABLE IF NOT EXISTS testimonials (
    testimonial_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    client_name VARCHAR(100) NOT NULL,
    client_photo VARCHAR(255),
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT NOT NULL,
    booking_id INT,
    is_approved BOOLEAN DEFAULT FALSE,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE SET NULL,
    INDEX idx_approved (is_approved),
    INDEX idx_featured (is_featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contact Messages Table
CREATE TABLE IF NOT EXISTS contact_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(200),
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    replied BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Site Settings Table
CREATE TABLE IF NOT EXISTS site_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity Logs Table
CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action_type VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action_type),
    INDEX idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Newsletter Subscribers Table
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    subscriber_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) UNIQUE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at DATETIME,
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Default Admin User (password: Admin@123)
INSERT INTO users (full_name, email, password_hash, role, account_status, email_verified) VALUES
('System Administrator', 'admin@aperturestudios.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', TRUE);

-- Insert Default Services
INSERT INTO services (service_name, description, base_price, duration_hours, features, is_active) VALUES
('Wedding Coverage', 'Complete wedding day coverage with professional videography and photography services', 40000.00, 12, '["Full day coverage", "2 videographers", "1 photographer", "Highlight video", "Full ceremony & reception coverage", "Drone shots", "Same-day edit"]', TRUE),
('Corporate Event', 'Professional coverage for corporate events, conferences, and business gatherings', 25000.00, 8, '["Multi-camera setup", "Audio recording", "Event highlights", "Professional editing", "Branding integration"]', TRUE),
('Studio Photo Shoot', 'Professional studio photography sessions with lighting and backdrops', 7000.00, 3, '["Studio setup", "Professional lighting", "Multiple backdrops", "Wardrobe changes", "Edited photos", "Online gallery"]', TRUE),
('Private Event', 'Birthdays, anniversaries, and other special occasions', 15000.00, 6, '["Event coverage", "Highlights video", "Photo documentation", "Professional editing", "Same-day teaser"]', TRUE);

-- Insert Default Site Settings
INSERT INTO site_settings (setting_key, setting_value, setting_type) VALUES
('company_name', 'ApertureStudios', 'text'),
('company_email', 'contact@aperturestudios.com', 'text'),
('company_phone', '+63 917 123 4567', 'text'),
('company_address', 'Manila, Philippines', 'text'),
('downpayment_percentage', '30', 'number'),
('cancellation_period_days', '7', 'number'),
('booking_advance_days', '14', 'number'),
('site_maintenance', 'false', 'boolean');

-- Insert Sample Portfolio Items
INSERT INTO portfolio (title, description, image_url, video_url, category, is_featured, display_order) VALUES
('Elegant Wedding', 'Beautiful destination wedding at sunset', 'portfolio/wedding1.jpg', 'https://www.youtube.com/embed/sample1', 'wedding', TRUE, 1),
('Corporate Launch', 'Product launch event for tech company', 'portfolio/corporate1.jpg', 'https://www.youtube.com/embed/sample2', 'corporate', TRUE, 2),
('Birthday Celebration', 'Milestone birthday party highlights', 'portfolio/event1.jpg', NULL, 'event', FALSE, 3);

-- Insert Sample Testimonials
INSERT INTO testimonials (client_name, client_photo, rating, review_text, is_approved, is_featured) VALUES
('Maria Santos', 'testimonials/maria.jpg', 5, 'ApertureStudios captured our wedding perfectly! The team was professional, creative, and delivered beyond our expectations. Highly recommended!', TRUE, TRUE),
('John Reyes', 'testimonials/john.jpg', 5, 'Exceptional service for our corporate event. The video quality was outstanding and delivered on time.', TRUE, TRUE),
('Sarah Chen', 'testimonials/sarah.jpg', 5, 'Best investment for our special day. The attention to detail and artistic vision was remarkable.', TRUE, FALSE);
