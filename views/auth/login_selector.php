<?php
// Prevent direct access
if (!defined('HOSTEL_APP')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access not permitted');
}

// Set page title
$pageTitle = 'Login - Select Role';
$hostelName = $_ENV['HOSTEL_NAME'] ?? 'Hostel Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars($hostelName) ?></title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= $_ENV['BASE_URL'] ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="login-body">
    
    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="login-card">
                        <!-- Header -->
                        <div class="text-center mb-4">
                            <div class="login-logo mb-3">
                                <i class="bi bi-building display-4 text-primary"></i>
                            </div>
                            <h2 class="fw-bold text-dark"><?= htmlspecialchars($hostelName) ?></h2>
                            <p class="text-muted">Check-in/Check-out Management System</p>
                        </div>
                        
                        <!-- Role Selection -->
                        <div class="role-selection">
                            <h4 class="text-center mb-4">Select Your Role</h4>
                            
                            <div class="row g-3">
                                <!-- Superadmin Login -->
                                <div class="col-md-6">
                                    <a href="<?= $_ENV['BASE_URL'] ?>?page=login-superadmin" class="role-card">
                                        <div class="role-icon">
                                            <i class="bi bi-shield-check"></i>
                                        </div>
                                        <h5>Superadmin</h5>
                                        <p class="text-muted small">System administration and management</p>
                                    </a>
                                </div>
                                
                                <!-- Admin Login -->
                                <div class="col-md-6">
                                    <a href="<?= $_ENV['BASE_URL'] ?>?page=login-admin" class="role-card">
                                        <div class="role-icon">
                                            <i class="bi bi-person-gear"></i>
                                        </div>
                                        <h5>Admin</h5>
                                        <p class="text-muted small">Student and hostel management</p>
                                    </a>
                                </div>
                                
                                <!-- Guard Login -->
                                <div class="col-md-6">
                                    <a href="<?= $_ENV['BASE_URL'] ?>?page=login-guard" class="role-card">
                                        <div class="role-icon">
                                            <i class="bi bi-qr-code-scan"></i>
                                        </div>
                                        <h5>Guard</h5>
                                        <p class="text-muted small">QR scanning and check-in/out</p>
                                    </a>
                                </div>
                                
                                <!-- Parent Login -->
                                <div class="col-md-6">
                                    <a href="<?= $_ENV['BASE_URL'] ?>?page=login-parent" class="role-card">
                                        <div class="role-icon">
                                            <i class="bi bi-people"></i>
                                        </div>
                                        <h5>Parent</h5>
                                        <p class="text-muted small">View child's activity logs</p>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Test Layout Link -->
                        <div class="text-center mt-4">
                            <hr>
                            <p class="text-muted small mb-2">For testing purposes:</p>
                            <a href="<?= $_ENV['BASE_URL'] ?>?page=test" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-eye me-1"></i>
                                View Layout Test
                            </a>
                        </div>
                        
                        <!-- Footer -->
                        <div class="text-center mt-4">
                            <p class="text-muted small">
                                &copy; <?= date('Y') ?> <?= htmlspecialchars($hostelName) ?>. All rights reserved.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>