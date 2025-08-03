<?php
// Prevent direct access
if (!defined('HOSTEL_APP')) {
    exit('Direct access not permitted');
}

// Check authentication
if (!isAuthenticated()) {
    header('Location: ' . $_ENV['BASE_URL'] . '?page=login');
    exit;
}

require_once __DIR__ . '/../../controllers/SettingsController.php';

$user = [
    'id' => getCurrentUserId(),
    'role' => getCurrentUserRole(),
    'name' => getCurrentUserName(),
    'email' => $_SESSION['user_email'] ?? '',
    'phone' => $_SESSION['user_phone'] ?? ''
];
$settingsController = new SettingsController();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'change_password':
                $result = $settingsController->changePassword(
                    $user['id'],
                    $_POST['old_password'],
                    $_POST['new_password']
                );
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;
                
            case 'request_reset':
                $result = $settingsController->submitResetRequest(
                    $user['id'],
                    $_POST['phone']
                );
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;
        }
    }
}

$pageTitle = 'Settings';

// Include header
include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<!-- Main Content -->
<main class="main-content with-sidebar col-lg-11 ">
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Account Settings</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($message)): ?>
                        <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <!-- Change Password Section -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Change Password</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="changePasswordForm">
                                        <input type="hidden" name="action" value="change_password">
                                        
                                        <div class="mb-3">
                                            <label for="old_password" class="form-label">Current Password</label>
                                            <input type="password" class="form-control" id="old_password" name="old_password" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">Change Password</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Password Reset Request Section -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Request Password Reset</h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-3">
                                        <?php if ($user['role'] === 'superadmin'): ?>
                                            As a Superadmin, you can change your password directly above.
                                        <?php elseif ($user['role'] === 'admin'): ?>
                                            If you forgot your password, you can request a reset from the Superadmin.
                                        <?php else: ?>
                                            If you forgot your password, you can request a reset from the Admin.
                                        <?php endif; ?>
                                    </p>
                                    
                                    <?php if ($user['role'] !== 'superadmin'): ?>
                                        <form method="POST" id="resetRequestForm">
                                            <input type="hidden" name="action" value="request_reset">
                                            
                                            <div class="mb-3">
                                                <label for="phone" class="form-label">Phone Number (for verification)</label>
                                                <input type="tel" class="form-control" id="phone" name="phone" 
                                                       placeholder="0121234567" required>
                                                <div class="form-text">Enter your registered phone number to verify your identity.</div>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-warning">Request Password Reset</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Admin/Superadmin Password Reset Management -->
                    <?php if (in_array($user['role'], ['admin', 'superadmin'])): ?>
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Password Reset Requests Management</h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted mb-3">
                                            <?php if ($user['role'] === 'superadmin'): ?>
                                                Manage password reset requests from all users.
                                            <?php else: ?>
                                                Manage password reset requests from Guards and Parents.
                                            <?php endif; ?>
                                        </p>
                                        <a href="/?page=password-reset-requests" class="btn btn-info">
                                            <i class="fas fa-list"></i> View Reset Requests
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</main>

<script>
// Password confirmation validation
document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('New password and confirmation do not match!');
        return false;
    }
    
    if (newPassword.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long!');
        return false;
    }
});

// Reset request form validation
document.getElementById('resetRequestForm')?.addEventListener('submit', function(e) {
    const phone = document.getElementById('phone').value;
    
    if (!phone.trim()) {
        e.preventDefault();
        alert('Please enter your phone number!');
        return false;
    }
    
    if (!confirm('Are you sure you want to request a password reset? This will notify the administrator.')) {
        e.preventDefault();
        return false;
    }
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>