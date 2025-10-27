<?php
$page_title = "Register";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect(SITE_URL . '/admin/dashboard.php');
    } else {
        redirect(SITE_URL . '/user/dashboard.php');
    }
}

$errors = [];
$success = false;
$form_data = [
    'full_name' => '',
    'email' => '',
    'role' => 'client'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = sanitize($_POST['role']);
    $terms_accepted = isset($_POST['terms_accepted']);

    // Store form data for sticky form
    $form_data['full_name'] = $full_name;
    $form_data['email'] = $email;
    $form_data['role'] = $role;

    // Validation
    if (empty($full_name) || empty($email) || empty($password)) {
        $errors[] = 'All fields are required.';
    }

    if (!validateEmail($email)) {
        $errors[] = 'Invalid email format.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    $password_errors = validatePasswordStrength($password);
    if (!empty($password_errors)) {
        $errors = array_merge($errors, $password_errors);
    }

    if (!in_array($role, ['client', 'photographer'])) {
        $errors[] = 'Invalid role selected.';
    }

    if (!$terms_accepted) {
        $errors[] = 'You must accept the Terms & Conditions.';
    }

    // Check if email already exists
    if (empty($errors)) {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Email address already registered.';
            }
        } catch (PDOException $e) {
            error_log("Email check error: " . $e->getMessage());
            $errors[] = 'An error occurred. Please try again.';
        }
    }

    // Register user
    if (empty($errors)) {
        try {
            $password_hash = hashPassword($password);
            $verification_token = generateToken();

            $stmt = $db->prepare("INSERT INTO users (full_name, email, password_hash, role, verification_token) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$full_name, $email, $password_hash, $role, $verification_token]);

            $user_id = $db->lastInsertId();

            // Log activity
            logActivity($user_id, 'registration', 'New user registered');

            // Create notification
            createNotification($user_id, 'Welcome to ApertureStudios!', 'Your account has been created successfully. You can now book our services.', 'account');

            // Send welcome email (placeholder)
            // sendEmail($email, 'Welcome to ApertureStudios', 'Welcome email content...');

            $success = true;
            setFlashMessage('Registration successful! Please login to continue.', 'success');

            // Clear form data on success
            $form_data = ['full_name' => '', 'email' => '', 'role' => 'client'];
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $errors[] = 'An error occurred during registration. Please try again.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
.auth-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--spacing-xl) var(--spacing-md);
    background: var(--gradient-dark);
    margin-top: -80px;
}

.auth-card {
    background: var(--color-white);
    border-radius: var(--radius-xl);
    overflow: hidden;
    box-shadow: var(--shadow-xl);
    max-width: 1100px;
    width: 100%;
    display: flex;
}

.auth-image {
    flex: 0.8;
    background: linear-gradient(135deg, rgba(200, 169, 81, 0.9) 0%, rgba(13, 13, 13, 0.9) 100%),
                url('<?php echo SITE_URL; ?>/assets/images/register-bg.jpg');
    background-size: cover;
    background-position: center;
    padding: var(--spacing-2xl);
    display: flex;
    flex-direction: column;
    justify-content: center;
    color: var(--color-white);
    min-height: 500px;
}

.auth-form {
    flex: 1.2;
    padding: var(--spacing-2xl);
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.auth-logo {
    font-family: var(--font-heading);
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--color-white);
    margin-bottom: var(--spacing-md);
}

.password-requirements {
    font-size: 12px;
    color: #666;
    margin-top: var(--spacing-xs);
    padding: var(--spacing-sm);
    background: #f8f9fa;
    border-radius: var(--radius-sm);
}

.password-requirements ul {
    margin: var(--spacing-xs) 0 0 0;
    padding-left: 20px;
}

@media (max-width: 768px) {
    .auth-card {
        flex-direction: column;
    }

    .auth-image {
        min-height: 200px;
        padding: var(--spacing-lg);
    }

    .auth-form {
        padding: var(--spacing-lg);
    }
}
</style>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-image">
            <div class="auth-logo">ApertureStudios</div>
            <h2 style="color: var(--color-beige); font-size: 1.8rem; margin-bottom: var(--spacing-md);">
                Join Our Community
            </h2>
            <p style="margin-bottom: var(--spacing-md);">
                Create your account to access premium videography and photography services.
            </p>
            <ul style="list-style: none; padding: 0;">
                <li style="padding: var(--spacing-xs) 0;">
                    <i class="bi bi-check-circle-fill" style="color: var(--color-gold); margin-right: 10px;"></i>
                    Easy online booking system
                </li>
                <li style="padding: var(--spacing-xs) 0;">
                    <i class="bi bi-check-circle-fill" style="color: var(--color-gold); margin-right: 10px;"></i>
                    Track your bookings in real-time
                </li>
                <li style="padding: var(--spacing-xs) 0;">
                    <i class="bi bi-check-circle-fill" style="color: var(--color-gold); margin-right: 10px;"></i>
                    Secure payment processing
                </li>
                <li style="padding: var(--spacing-xs) 0;">
                    <i class="bi bi-check-circle-fill" style="color: var(--color-gold); margin-right: 10px;"></i>
                    Access to exclusive offers
                </li>
            </ul>
        </div>

        <div class="auth-form">
            <h2 style="margin-bottom: var(--spacing-sm);">Create Account</h2>
            <p style="color: #666; margin-bottom: var(--spacing-lg);">Fill in your details to get started</p>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i>
                    Registration successful! You can now <a href="<?php echo SITE_URL; ?>/login.php">login</a> to your account.
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST" action="" id="registerForm">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control"
                           placeholder="John Doe" value="<?php echo htmlspecialchars($form_data['full_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control"
                           placeholder="your@email.com" value="<?php echo htmlspecialchars($form_data['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" id="password"
                           placeholder="Create a strong password" required>
                    <div class="password-requirements">
                        <strong>Password must contain:</strong>
                        <ul>
                            <li>At least <?php echo PASSWORD_MIN_LENGTH; ?> characters</li>
                            <li>One uppercase letter (A-Z)</li>
                            <li>One lowercase letter (a-z)</li>
                            <li>One number (0-9)</li>
                            <li>One special character (!@#$%^&*)</li>
                        </ul>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" id="confirm_password"
                           placeholder="Re-enter your password" required>
                    <div id="password-match-message" style="margin-top: var(--spacing-xs); font-size: 14px;"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">I am a:</label>
                    <select name="role" class="form-control" required>
                        <option value="client" <?php echo $form_data['role'] === 'client' ? 'selected' : ''; ?>>
                            Client (Book Services)
                        </option>
                        <option value="photographer" <?php echo $form_data['role'] === 'photographer' ? 'selected' : ''; ?>>
                            Photographer/Videographer (Service Provider)
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="terms_accepted" class="form-check-input" required>
                        <span class="form-check-label">
                            I accept the
                            <a href="#" onclick="openModal('termsModal'); return false;">Terms & Conditions</a>
                            and
                            <a href="#" onclick="openModal('privacyModal'); return false;">Privacy Policy</a>
                        </span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <i class="bi bi-person-plus"></i> Create Account
                </button>
            </form>
            <?php endif; ?>

            <div style="text-align: center; margin-top: var(--spacing-lg); padding-top: var(--spacing-lg); border-top: 1px solid #ddd;">
                <p>Already have an account?
                    <a href="<?php echo SITE_URL; ?>/login.php" style="font-weight: 600;">Login Here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// Password match validation
document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const matchMessage = document.getElementById('password-match-message');

    function checkPasswordMatch() {
        if (confirmPassword.value === '') {
            matchMessage.textContent = '';
            confirmPassword.classList.remove('is-valid', 'is-invalid');
            return;
        }

        if (password.value === confirmPassword.value) {
            matchMessage.textContent = '✓ Passwords match';
            matchMessage.style.color = '#28a745';
            confirmPassword.classList.remove('is-invalid');
            confirmPassword.classList.add('is-valid');
        } else {
            matchMessage.textContent = '✗ Passwords do not match';
            matchMessage.style.color = '#dc3545';
            confirmPassword.classList.remove('is-valid');
            confirmPassword.classList.add('is-invalid');
        }
    }

    confirmPassword.addEventListener('input', checkPasswordMatch);
    password.addEventListener('input', checkPasswordMatch);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
