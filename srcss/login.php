<?php
$page_title = "Login";
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
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $errors[] = 'Email and password are required.';
    } elseif (!validateEmail($email)) {
        $errors[] = 'Invalid email format.';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND account_status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && verifyPassword($password, $user['password_hash'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                // Log activity
                logActivity($user['user_id'], 'login', 'User logged in');

                // Redirect to appropriate dashboard
                $redirect_url = $_SESSION['redirect_after_login'] ?? null;
                unset($_SESSION['redirect_after_login']);

                if ($redirect_url) {
                    redirect($redirect_url);
                } elseif ($user['role'] === 'admin') {
                    redirect(SITE_URL . '/admin/dashboard.php');
                } else {
                    redirect(SITE_URL . '/user/dashboard.php');
                }
            } else {
                $errors[] = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $errors[] = 'An error occurred. Please try again.';
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
    max-width: 1000px;
    width: 100%;
    display: flex;
}

.auth-image {
    flex: 1;
    background: linear-gradient(135deg, rgba(200, 169, 81, 0.9) 0%, rgba(13, 13, 13, 0.9) 100%),
                url('<?php echo SITE_URL; ?>/assets/images/login-bg.jpg');
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
    flex: 1;
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

.auth-tagline {
    font-size: 1.2rem;
    color: var(--color-beige);
    margin-bottom: var(--spacing-md);
}

@media (max-width: 768px) {
    .auth-card {
        flex-direction: column;
    }

    .auth-image {
        min-height: 200px;
        padding: var(--spacing-lg);
    }
}
</style>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-image">
            <div class="auth-logo">ApertureStudios</div>
            <h2 class="auth-tagline">Welcome Back</h2>
            <p>Login to manage your bookings, view past events, and book new sessions with ease.</p>
        </div>

        <div class="auth-form">
            <h2 style="margin-bottom: var(--spacing-sm);">Login</h2>
            <p style="color: #666; margin-bottom: var(--spacing-lg);">Enter your credentials to access your account</p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control"
                           placeholder="your@email.com" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control"
                           placeholder="Enter your password" required>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg);">
                    <label class="form-check">
                        <input type="checkbox" name="remember" class="form-check-input">
                        <span class="form-check-label">Remember me</span>
                    </label>
                    <a href="<?php echo SITE_URL; ?>/forgot-password.php" style="font-size: 14px;">
                        Forgot Password?
                    </a>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </button>
            </form>

            <div style="text-align: center; margin-top: var(--spacing-lg); padding-top: var(--spacing-lg); border-top: 1px solid #ddd;">
                <p>Don't have an account?
                    <a href="<?php echo SITE_URL; ?>/register.php" style="font-weight: 600;">Register Now</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
