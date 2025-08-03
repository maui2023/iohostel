<?php
// Prevent direct access
if (!defined('HOSTEL_APP')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access not permitted');
}

// Get current user info if logged in
$currentUser = isAuthenticated() ? [
    'id' => getCurrentUserId(),
    'role' => getCurrentUserRole(),
    'name' => getCurrentUserName()
] : null;

// Get hostel name from environment
$hostelName = $_ENV['HOSTEL_NAME'] ?? 'Hostel Management';

// Get current page for active navigation
$currentPage = $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($hostelName) ?> - Check-in/Check-out Management System">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?><?= htmlspecialchars($hostelName) ?></title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= $_ENV['BASE_URL'] ?>/assets/css/style.css" rel="stylesheet">
    
    <!-- CSRF Token for AJAX requests -->
    <meta name="csrf-token" content="<?= generateCSRFToken() ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= $_ENV['BASE_URL'] ?>/assets/img/favicon.ico">
</head>
<body class="<?= $currentUser ? 'authenticated' : 'guest' ?>">
    
    <!-- Sidebar Toggle Button for Desktop -->
    <?php if ($currentUser): ?>
    <button class="sidebar-toggle" id="sidebarToggle" type="button">
        <i class="bi bi-list"></i>
    </button>
    <?php endif; ?>
    
    <!-- Navigation Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <!-- Brand -->
            <a class="navbar-brand d-flex align-items-center" href="<?= $_ENV['BASE_URL'] ?>">
                <i class="bi bi-building me-2"></i>
                <span class="fw-bold"><?= htmlspecialchars($hostelName) ?></span>
            </a>
            
            <?php if ($currentUser): ?>
                <!-- Mobile Toggle Button -->
                <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <!-- Desktop Navigation -->
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <!-- Role-based navigation items will be added by sidebar -->
                    </ul>
                    
                    <!-- Right side items -->
                    <ul class="navbar-nav">
                        <!-- Real-time clock -->
                        <li class="nav-item">
                            <span class="navbar-text me-3">
                                <i class="bi bi-clock me-1"></i>
                                <span id="current-time"></span>
                            </span>
                        </li>
                        
                        <!-- User dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i>
                                <span class="d-none d-md-inline"><?= htmlspecialchars($currentUser['name']) ?></span>
                                <span class="badge bg-secondary ms-2"><?= ucfirst($currentUser['role']) ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><h6 class="dropdown-header"><?= htmlspecialchars($currentUser['name']) ?></h6></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= $_ENV['BASE_URL'] ?>?page=profile"><i class="bi bi-person me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="<?= $_ENV['BASE_URL'] ?>?page=settings"><i class="bi bi-gear me-2"></i>Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?= $_ENV['BASE_URL'] ?>?page=logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            <?php else: ?>
                <!-- Guest navigation -->
                <div class="navbar-nav">
                    <a class="nav-link" href="<?= $_ENV['BASE_URL'] ?>"><i class="bi bi-box-arrow-in-right me-1"></i>Login</a>
                </div>
            <?php endif; ?>
        </div>
    </nav>
    
    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
        <?php if ($currentUser): ?>
            <!-- Include sidebar for authenticated users -->
            <?php include __DIR__ . '/sidebar.php'; ?>
        <?php endif; ?>
        
        <!-- Page Content -->
        <main class="main-content <?= $currentUser ? 'with-sidebar' : 'without-sidebar' ?>">
            <!-- Alert container for notifications -->
            <div id="alert-container" class="container-fluid mt-3"></div>
            
            <!-- Page content will be inserted here -->