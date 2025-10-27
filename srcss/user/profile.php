<?php
$page_title = "Profile Settings";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$user_id = getCurrentUserId();
$user = getUserById($user_id);

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $full_name = sanitize($_POST['full_name']);
        $phone = sanitize($_POST['phone']);

        if (empty($full_name)) {
            $errors[] = 'Full name is required.';
        }

        if (empty($errors)) {
            try {
                $db = getDB();
                $stmt = $db->prepare("UPDATE users SET full_name = ?, phone = ? WHERE user_id = ?");
                $stmt->execute([$full_name, $phone, $user_id]);

                $_SESSION['full_name'] = $full_name;
                logActivity($user_id, 'profile_update', 'Updated profile information');
                setFlashMessage('Profile updated successfully!', 'success');
                $user = getUserById($user_id); // Refresh user data
            } catch (PDOException $e) {
                error_log("Profile update error: " . $e->getMessage());
                $errors[] = 'An error occurred. Please try again.';
            }
        }
    }

    elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (!verifyPassword($current_password, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        }

        if ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match.';
        }

        $password_errors = validatePasswordStrength($new_password);
        if (!empty($password_errors)) {
            $errors = array_merge($errors, $password_errors);
        }

        if (empty($errors)) {
            try {
                $db = getDB();
                $new_hash = hashPassword($new_password);
                $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                $stmt->execute([$new_hash, $user_id]);

                logActivity($user_id, 'password_change', 'Changed password');
                setFlashMessage('Password changed successfully!', 'success');
            } catch (PDOException $e) {
                error_log("Password change error: " . $e->getMessage());
                $errors[] = 'An error occurred. Please try again.';
            }
        }
    }

    elseif ($action === 'upload_photo') {
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $upload_result = uploadFile($_FILES['profile_photo'], PROFILE_UPLOAD_DIR, ALLOWED_IMAGE_TYPES);

            if ($upload_result['success']) {
                try {
                    $db = getDB();
                    $stmt = $db->prepare("UPDATE users SET profile_photo = ? WHERE user_id = ?");
                    $stmt->execute([$upload_result['filename'], $user_id]);

                    logActivity($user_id, 'photo_update', 'Updated profile photo');
                    setFlashMessage('Profile photo updated successfully!', 'success');
                    $user = getUserById($user_id);
                } catch (PDOException $e) {
                    error_log("Photo upload error: " . $e->getMessage());
                    $errors[] = 'An error occurred. Please try again.';
                }
            } else {
                $errors[] = $upload_result['error'];
            }
        } else {
            $errors[] = 'Please select a photo to upload.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.profile-container {
    min-height: 100vh;
    background: var(--color-beige);
    padding-top: 100px;
    padding-bottom: var(--spacing-2xl);
}

.profile-photo-section {
    text-align: center;
    padding: var(--spacing-xl);
    background: var(--gradient-dark);
    border-radius: var(--radius-lg);
    margin-bottom: var(--spacing-lg);
}

.profile-photo-upload {
    position: relative;
    display: inline-block;
}

.profile-photo-large {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 5px solid var(--color-gold);
    box-shadow: var(--shadow-lg);
}

.upload-overlay {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 50px;
    height: 50px;
    background: var(--gradient-gold);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--transition-base);
    box-shadow: var(--shadow-md);
}

.upload-overlay:hover {
    transform: scale(1.1);
}

.upload-overlay input[type="file"] {
    display: none;
}

.settings-section {
    background: var(--color-white);
    border-radius: var(--radius-lg);
    padding: var(--spacing-xl);
    margin-bottom: var(--spacing-lg);
    box-shadow: var(--shadow-md);
}

.danger-zone {
    border: 2px solid #dc3545;
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    background: #fff5f5;
}
</style>

<div class="profile-container">
    <div class="container">
        <div class="row">
            <div class="col-12 col-md-4">
                <!-- Profile Photo Section -->
                <div class="profile-photo-section">
                    <form method="POST" enctype="multipart/form-data" id="photoForm">
                        <input type="hidden" name="action" value="upload_photo">
                        <div class="profile-photo-upload">
                            <img src="<?php echo SITE_URL; ?>/uploads/profiles/<?php echo htmlspecialchars($user['profile_photo']); ?>"
                                 alt="Profile Photo"
                                 class="profile-photo-large"
                                 onerror="this.src='<?php echo SITE_URL; ?>/assets/images/default-avatar.png'">
                            <label class="upload-overlay" for="photoInput" title="Change photo">
                                <i class="bi bi-camera" style="font-size: 20px; color: var(--color-black);"></i>
                                <input type="file" id="photoInput" name="profile_photo" accept="image/*"
                                       onchange="document.getElementById('photoForm').submit()">
                            </label>
                        </div>
                    </form>
                    <h3 style="color: var(--color-white); margin-top: var(--spacing-md);">
                        <?php echo htmlspecialchars($user['full_name']); ?>
                    </h3>
                    <p style="color: var(--color-beige); font-size: 14px;">
                        <?php echo htmlspecialchars($user['email']); ?>
                    </p>
                    <span class="badge badge-success" style="margin-top: var(--spacing-sm);">
                        <?php echo ucfirst($user['role']); ?>
                    </span>
                </div>

                <!-- Account Info -->
                <div class="card">
                    <div class="card-header">
                        <h4 style="margin: 0;">Account Information</h4>
                    </div>
                    <div class="card-body">
                        <div style="margin-bottom: var(--spacing-md);">
                            <strong style="color: #666; display: block; font-size: 13px;">Account Status</strong>
                            <span class="badge badge-<?php echo $user['account_status'] === 'active' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($user['account_status']); ?>
                            </span>
                        </div>
                        <div style="margin-bottom: var(--spacing-md);">
                            <strong style="color: #666; display: block; font-size: 13px;">Member Since</strong>
                            <?php echo formatDate($user['created_at'], 'F j, Y'); ?>
                        </div>
                        <div>
                            <strong style="color: #666; display: block; font-size: 13px;">Last Updated</strong>
                            <?php echo formatDateTime($user['updated_at']); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-8">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <ul style="margin: 0; padding-left: 20px;">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Update Profile -->
                <div class="settings-section">
                    <h3 style="margin-bottom: var(--spacing-lg);">
                        <i class="bi bi-person-circle"></i> Update Profile
                    </h3>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control"
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control"
                                   value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            <small style="color: #666; font-size: 13px;">Email cannot be changed</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-control"
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                   placeholder="+63 917 123 4567">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Changes
                        </button>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="settings-section">
                    <h3 style="margin-bottom: var(--spacing-lg);">
                        <i class="bi bi-shield-lock"></i> Change Password
                    </h3>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="change_password">

                        <div class="form-group">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" id="new_password" required>
                            <div class="password-requirements" style="font-size: 12px; color: #666; margin-top: var(--spacing-xs); padding: var(--spacing-sm); background: #f8f9fa; border-radius: var(--radius-sm);">
                                Password must contain at least <?php echo PASSWORD_MIN_LENGTH; ?> characters, 1 uppercase, 1 lowercase, 1 number, and 1 special character.
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" id="confirm_password" required>
                            <div id="password-match-msg" style="margin-top: var(--spacing-xs); font-size: 14px;"></div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-key"></i> Change Password
                        </button>
                    </form>
                </div>

                <!-- Danger Zone -->
                <div class="danger-zone">
                    <h4 style="color: #dc3545; margin-bottom: var(--spacing-md);">
                        <i class="bi bi-exclamation-triangle"></i> Danger Zone
                    </h4>
                    <p style="color: #666; margin-bottom: var(--spacing-md);">
                        Deleting your account is permanent and cannot be undone. All your bookings and data will be archived but not accessible.
                    </p>
                    <button class="btn" style="background: #dc3545; color: white;"
                            onclick="showConfirmModal('Delete Account', 'Are you sure you want to delete your account? This action cannot be undone.', function() { alert('Account deletion requested'); })">
                        <i class="bi bi-trash"></i> Delete My Account
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password match validation
const newPassword = document.getElementById('new_password');
const confirmPassword = document.getElementById('confirm_password');
const matchMsg = document.getElementById('password-match-msg');

function checkPasswordMatch() {
    if (confirmPassword.value === '') {
        matchMsg.textContent = '';
        confirmPassword.classList.remove('is-valid', 'is-invalid');
        return;
    }

    if (newPassword.value === confirmPassword.value) {
        matchMsg.textContent = '✓ Passwords match';
        matchMsg.style.color = '#28a745';
        confirmPassword.classList.remove('is-invalid');
        confirmPassword.classList.add('is-valid');
    } else {
        matchMsg.textContent = '✗ Passwords do not match';
        matchMsg.style.color = '#dc3545';
        confirmPassword.classList.remove('is-valid');
        confirmPassword.classList.add('is-invalid');
    }
}

if (confirmPassword) {
    confirmPassword.addEventListener('input', checkPasswordMatch);
    newPassword.addEventListener('input', checkPasswordMatch);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
