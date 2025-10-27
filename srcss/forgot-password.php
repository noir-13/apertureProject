<?php
$page_title = "Forgot Password";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/user/dashboard.php');
}

$errors = [];
$success = false;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);

    if (empty($email)) {
        $errors[] = 'Email address is required.';
    } elseif (!validateEmail($email)) {
        $errors[] = 'Invalid email format.';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT user_id, full_name FROM users WHERE email = ? AND account_status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate reset token
                $reset_token = generateToken();
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Update user with reset token
                $stmt = $db->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE user_id = ?");
                $stmt->execute([$reset_token, $expiry, $user['user_id']]);

                // Send reset email (placeholder)
                $reset_link = SITE_URL . '/reset-password.php?token=' . $reset_token;
                // sendEmail($email, 'Password Reset Request', "Click here to reset: " . $reset_link);

                $success = true;
            } else {
                // Don't reveal if email exists or not (security)
                $success = true;
            }
        } catch (PDOException $e) {
            error_log("Password reset error: " . $e->getMessage());
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
    padding: var(--spacing-2xl);
    box-shadow: var(--shadow-xl);
    max-width: 500px;
    width: 100%;
    text-align: center;
}

.auth-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: var(--gradient-gold);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto var(--spacing-lg);
    font-size: 40px;
    color: var(--color-black);
}
</style>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-icon">
            <i class="bi bi-key"></i>
        </div>

        <h2 style="margin-bottom: var(--spacing-sm);">Forgot Password?</h2>
        <p style="color: #666; margin-bottom: var(--spacing-lg);">
            No worries! Enter your email address and we'll send you instructions to reset your password.
        </p>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i>
                <strong>Check your email!</strong><br>
                If an account exists with that email, we've sent password reset instructions.
            </div>
            <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-primary btn-block">
                Back to Login
            </a>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group" style="text-align: left;">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control"
                           placeholder="your@email.com" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <i class="bi bi-envelope"></i> Send Reset Link
                </button>
            </form>

            <div style="margin-top: var(--spacing-lg); padding-top: var(--spacing-lg); border-top: 1px solid #ddd;">
                <a href="<?php echo SITE_URL; ?>/login.php" style="color: var(--color-gold);">
                    <i class="bi bi-arrow-left"></i> Back to Login
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
