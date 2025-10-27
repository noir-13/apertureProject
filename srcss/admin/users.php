<?php
$page_title = "Manage Users";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$db = getDB();
$message = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id = (int)$_POST['user_id'];
    $action = $_POST['action'];

    try {
        if ($action === 'suspend') {
            $reason = sanitize($_POST['suspension_reason']);
            $stmt = $db->prepare("UPDATE users SET account_status = 'suspended', deletion_reason = ? WHERE user_id = ? AND role != 'admin'");
            $stmt->execute([$reason, $user_id]);

            createNotification($user_id, 'Account Suspended', "Your account has been suspended. Reason: {$reason}", 'account');
            logActivity(getCurrentUserId(), 'user_suspended', "Suspended user ID: {$user_id}");
            setFlashMessage('User suspended successfully.', 'success');

        } elseif ($action === 'activate') {
            $stmt = $db->prepare("UPDATE users SET account_status = 'active', deletion_reason = NULL WHERE user_id = ?");
            $stmt->execute([$user_id]);

            createNotification($user_id, 'Account Activated', 'Your account has been reactivated.', 'account');
            logActivity(getCurrentUserId(), 'user_activated', "Activated user ID: {$user_id}");
            setFlashMessage('User activated successfully.', 'success');

        } elseif ($action === 'delete') {
            $reason = sanitize($_POST['deletion_reason']);
            $stmt = $db->prepare("UPDATE users SET account_status = 'deleted', deletion_reason = ? WHERE user_id = ? AND role != 'admin'");
            $stmt->execute([$reason, $user_id]);

            createNotification($user_id, 'Account Deleted', "Your account has been deleted. Reason: {$reason}", 'account');
            logActivity(getCurrentUserId(), 'user_deleted', "Deleted user ID: {$user_id}");
            setFlashMessage('User deleted successfully.', 'warning');
        }

        redirect(SITE_URL . '/admin/users.php');

    } catch (PDOException $e) {
        error_log("User action error: " . $e->getMessage());
        $message = 'An error occurred. Please try again.';
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_clauses = ["role != 'admin'"];
$params = [];

if ($filter !== 'all') {
    if ($filter === 'suspended') {
        $where_clauses[] = "account_status = 'suspended'";
    } elseif ($filter === 'deleted') {
        $where_clauses[] = "account_status = 'deleted'";
    } elseif ($filter === 'active') {
        $where_clauses[] = "account_status = 'active'";
    } else {
        $where_clauses[] = "role = ?";
        $params[] = $filter;
    }
}

if (!empty($search)) {
    $where_clauses[] = "(full_name LIKE ? OR email LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

// Get users
$stmt = $db->prepare("
    SELECT u.*,
           COUNT(DISTINCT b.booking_id) as total_bookings,
           COALESCE(SUM(CASE WHEN b.booking_status = 'completed' THEN b.total_amount ELSE 0 END), 0) as total_spent
    FROM users u
    LEFT JOIN bookings b ON u.user_id = b.user_id
    {$where_sql}
    GROUP BY u.user_id
    ORDER BY u.created_at DESC
");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get statistics
$stats = [
    'all' => $db->query("SELECT COUNT(*) as count FROM users WHERE role != 'admin'")->fetch()['count'],
    'active' => $db->query("SELECT COUNT(*) as count FROM users WHERE role != 'admin' AND account_status = 'active'")->fetch()['count'],
    'client' => $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'client'")->fetch()['count'],
    'photographer' => $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'photographer'")->fetch()['count'],
    'suspended' => $db->query("SELECT COUNT(*) as count FROM users WHERE account_status = 'suspended'")->fetch()['count'],
    'deleted' => $db->query("SELECT COUNT(*) as count FROM users WHERE account_status = 'deleted'")->fetch()['count']
];

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.admin-container {
    min-height: 100vh;
    background: #f4f6f9;
    padding-top: 80px;
    padding-bottom: var(--spacing-2xl);
}

.filter-bar {
    background: var(--color-white);
    padding: var(--spacing-lg);
    border-radius: var(--radius-lg);
    margin-bottom: var(--spacing-lg);
    box-shadow: var(--shadow-md);
}

.filter-tabs {
    display: flex;
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-md);
    flex-wrap: wrap;
}

.filter-tab {
    padding: 10px 20px;
    border: 2px solid #ddd;
    border-radius: var(--radius-md);
    background: white;
    cursor: pointer;
    transition: var(--transition-base);
    text-decoration: none;
    color: var(--color-charcoal);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
}

.filter-tab:hover {
    border-color: var(--color-gold);
}

.filter-tab.active {
    background: var(--gradient-gold);
    border-color: var(--color-gold);
    color: var(--color-black);
}

.filter-tab .count {
    background: rgba(0,0,0,0.1);
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 600;
}

.user-card {
    background: var(--color-white);
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    margin-bottom: var(--spacing-md);
    box-shadow: var(--shadow-md);
    transition: var(--transition-base);
}

.user-card:hover {
    box-shadow: var(--shadow-lg);
}

.user-header {
    display: flex;
    gap: var(--spacing-md);
    align-items: start;
    margin-bottom: var(--spacing-md);
}

.user-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--color-gold);
}

.user-info {
    flex: 1;
}

.user-stats {
    display: flex;
    gap: var(--spacing-lg);
    padding: var(--spacing-md);
    background: var(--color-beige);
    border-radius: var(--radius-md);
    margin-bottom: var(--spacing-md);
}

.stat-item {
    text-align: center;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-gold);
}

.stat-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
}

.action-buttons {
    display: flex;
    gap: var(--spacing-sm);
    flex-wrap: wrap;
}
</style>

<div class="admin-container">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg);">
            <h1><i class="bi bi-people"></i> Manage Users</h1>
            <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="btn btn-outline">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="filter-tabs">
                <a href="?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    All Users <span class="count"><?php echo $stats['all']; ?></span>
                </a>
                <a href="?filter=active" class="filter-tab <?php echo $filter === 'active' ? 'active' : ''; ?>">
                    <i class="bi bi-check-circle"></i> Active <span class="count"><?php echo $stats['active']; ?></span>
                </a>
                <a href="?filter=client" class="filter-tab <?php echo $filter === 'client' ? 'active' : ''; ?>">
                    <i class="bi bi-person"></i> Clients <span class="count"><?php echo $stats['client']; ?></span>
                </a>
                <a href="?filter=photographer" class="filter-tab <?php echo $filter === 'photographer' ? 'active' : ''; ?>">
                    <i class="bi bi-camera"></i> Photographers <span class="count"><?php echo $stats['photographer']; ?></span>
                </a>
                <a href="?filter=suspended" class="filter-tab <?php echo $filter === 'suspended' ? 'active' : ''; ?>">
                    <i class="bi bi-pause-circle"></i> Suspended <span class="count"><?php echo $stats['suspended']; ?></span>
                </a>
                <a href="?filter=deleted" class="filter-tab <?php echo $filter === 'deleted' ? 'active' : ''; ?>">
                    <i class="bi bi-trash"></i> Deleted <span class="count"><?php echo $stats['deleted']; ?></span>
                </a>
            </div>

            <form method="GET" style="display: flex; gap: var(--spacing-sm);">
                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                <input type="text" name="search" class="form-control" placeholder="Search by name or email..."
                       value="<?php echo htmlspecialchars($search); ?>" style="flex: 1;">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Search
                </button>
                <?php if ($search): ?>
                    <a href="?filter=<?php echo $filter; ?>" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Users List -->
        <?php if (empty($users)): ?>
            <div class="card text-center" style="padding: var(--spacing-2xl);">
                <i class="bi bi-people" style="font-size: 80px; color: #ddd; margin-bottom: var(--spacing-md);"></i>
                <h3 style="color: #999;">No Users Found</h3>
            </div>
        <?php else: ?>
            <?php foreach ($users as $user): ?>
                <div class="user-card">
                    <div class="user-header">
                        <img src="<?php echo SITE_URL; ?>/uploads/profiles/<?php echo htmlspecialchars($user['profile_photo']); ?>"
                             alt="Avatar" class="user-avatar"
                             onerror="this.src='<?php echo SITE_URL; ?>/assets/images/default-avatar.png'">
                        <div class="user-info">
                            <h3 style="margin-bottom: var(--spacing-xs);">
                                <?php echo htmlspecialchars($user['full_name']); ?>
                            </h3>
                            <div style="display: flex; gap: var(--spacing-md); flex-wrap: wrap; margin-bottom: var(--spacing-xs);">
                                <span style="color: #666;">
                                    <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
                                </span>
                                <?php if ($user['phone']): ?>
                                    <span style="color: #666;">
                                        <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($user['phone']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div style="display: flex; gap: var(--spacing-sm);">
                                <span class="badge badge-primary"><?php echo ucfirst($user['role']); ?></span>
                                <span class="badge badge-<?php echo $user['account_status'] === 'active' ? 'success' : ($user['account_status'] === 'suspended' ? 'warning' : 'danger'); ?>">
                                    <?php echo ucfirst($user['account_status']); ?>
                                </span>
                                <?php if ($user['email_verified']): ?>
                                    <span class="badge badge-info" title="Email Verified">
                                        <i class="bi bi-patch-check"></i> Verified
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 12px; color: #999;">
                                Member since
                            </div>
                            <div style="font-weight: 600;">
                                <?php echo formatDate($user['created_at'], 'M j, Y'); ?>
                            </div>
                        </div>
                    </div>

                    <div class="user-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $user['total_bookings']; ?></div>
                            <div class="stat-label">Total Bookings</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo formatCurrency($user['total_spent']); ?></div>
                            <div class="stat-label">Total Spent</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo timeAgo($user['updated_at']); ?></div>
                            <div class="stat-label">Last Activity</div>
                        </div>
                    </div>

                    <?php if ($user['deletion_reason']): ?>
                        <div style="padding: var(--spacing-md); background: #fff3cd; border-left: 4px solid #ffc107; border-radius: var(--radius-sm); margin-bottom: var(--spacing-md);">
                            <strong>Reason:</strong> <?php echo htmlspecialchars($user['deletion_reason']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="action-buttons">
                        <?php if ($user['account_status'] === 'active'): ?>
                            <button onclick="showSuspendModal(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')"
                                    class="btn btn-sm" style="background: #ffc107; color: #000;">
                                <i class="bi bi-pause-circle"></i> Suspend
                            </button>
                            <button onclick="showDeleteModal(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')"
                                    class="btn btn-sm" style="background: #dc3545; color: white;">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        <?php elseif ($user['account_status'] === 'suspended'): ?>
                            <button onclick="activateUser(<?php echo $user['user_id']; ?>)"
                                    class="btn btn-sm" style="background: #28a745; color: white;">
                                <i class="bi bi-check-circle"></i> Activate
                            </button>
                            <button onclick="showDeleteModal(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')"
                                    class="btn btn-sm" style="background: #dc3545; color: white;">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        <?php elseif ($user['account_status'] === 'deleted'): ?>
                            <span style="color: #999; font-style: italic;">Account Deleted</span>
                        <?php endif; ?>

                        <a href="<?php echo SITE_URL; ?>/admin/user-details.php?id=<?php echo $user['user_id']; ?>"
                           class="btn btn-sm btn-outline">
                            <i class="bi bi-eye"></i> View Details
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Suspend Modal -->
<div id="suspendModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Suspend User Account</h3>
            <button class="modal-close" onclick="closeModal('suspendModal')">&times;</button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="action" value="suspend">
                <input type="hidden" name="user_id" id="suspend_user_id">
                <p>You are about to suspend <strong id="suspend_user_name"></strong>'s account.</p>
                <div class="form-group">
                    <label class="form-label">Suspension Reason *</label>
                    <textarea name="suspension_reason" class="form-control" rows="4"
                              placeholder="Enter reason for suspension..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('suspendModal')">Cancel</button>
                <button type="submit" class="btn" style="background: #ffc107; color: #000;">
                    <i class="bi bi-pause-circle"></i> Suspend User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Delete User Account</h3>
            <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" id="delete_user_id">
                <div class="alert alert-error">
                    <strong>Warning!</strong> This action will delete <strong id="delete_user_name"></strong>'s account.
                    Their data will be archived but they will no longer be able to access the system.
                </div>
                <div class="form-group">
                    <label class="form-label">Deletion Reason *</label>
                    <textarea name="deletion_reason" class="form-control" rows="4"
                              placeholder="Enter reason for deletion..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                <button type="submit" class="btn" style="background: #dc3545; color: white;">
                    <i class="bi bi-trash"></i> Delete User
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showSuspendModal(userId, userName) {
    document.getElementById('suspend_user_id').value = userId;
    document.getElementById('suspend_user_name').textContent = userName;
    openModal('suspendModal');
}

function showDeleteModal(userId, userName) {
    document.getElementById('delete_user_id').value = userId;
    document.getElementById('delete_user_name').textContent = userName;
    openModal('deleteModal');
}

function activateUser(userId) {
    if (confirm('Activate this user account?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="activate">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
