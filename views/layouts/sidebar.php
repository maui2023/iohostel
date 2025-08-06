<?php
// Prevent direct access
if (!defined('HOSTEL_APP')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access not permitted');
}

// Get current user info
$currentUserRole = getCurrentUserRole();
$currentPage = $_GET['page'] ?? 'dashboard';

// Define navigation items based on role
$navigationItems = [];

switch ($currentUserRole) {
    case 'superadmin':
        $navigationItems = [
            ['icon' => 'speedometer2', 'label' => 'Dashboard', 'page' => 'superadmin-dashboard'],
            ['icon' => 'people', 'label' => 'User Management', 'page' => 'superadmin-dashboard'],
            ['icon' => 'person-check', 'label' => 'All Parents', 'page' => 'parents'],
            ['icon' => 'mortarboard', 'label' => 'All Students', 'page' => 'students'],
            ['icon' => 'clock-history', 'label' => 'All Logs', 'page' => 'logs'],
            ['icon' => 'bar-chart', 'label' => 'Reports', 'page' => 'reports'],
            ['icon' => 'gear', 'label' => 'System Settings', 'page' => 'system-settings'],
        ];
        break;
        
    case 'admin':
        $navigationItems = [
            ['icon' => 'speedometer2', 'label' => 'Dashboard', 'page' => 'dashboard'],
            ['icon' => 'person-plus', 'label' => 'Add Parent', 'page' => 'add-parent'],
            ['icon' => 'person-plus', 'label' => 'Add Student', 'page' => 'add-student'],
            ['icon' => 'collection', 'label' => 'Manage Classes', 'page' => 'classes'],
            ['icon' => 'clock-history', 'label' => 'Check-in/out Logs', 'page' => 'logs'],
            ['icon' => 'chat-dots', 'label' => 'WhatsApp Messages', 'page' => 'messages'],
            ['icon' => 'file-earmark-text', 'label' => 'Reports', 'page' => 'reports'],
            ['icon' => 'gear', 'label' => 'Settings', 'page' => 'settings'],
        ];
        break;
        
    case 'guard':
        $navigationItems = [
            ['icon' => 'speedometer2', 'label' => 'Dashboard', 'page' => 'dashboard'],
            ['icon' => 'qr-code-scan', 'label' => 'QR Scanner', 'page' => 'scanner'],
            ['icon' => 'list-check', 'label' => 'Manual Check-in/out', 'page' => 'manual-checkin'],
            ['icon' => 'clock-history', 'label' => 'Today\'s Logs', 'page' => 'logs'],
            ['icon' => 'people', 'label' => 'Student List', 'page' => 'students'],
            ['icon' => 'gear', 'label' => 'Settings', 'page' => 'settings'],
        ];
        break;
        
    case 'parent':
        $navigationItems = [
            ['icon' => 'speedometer2', 'label' => 'Dashboard', 'page' => 'dashboard'],
            ['icon' => 'person', 'label' => 'My Children', 'page' => 'children'],
            ['icon' => 'clock-history', 'label' => 'Activity Logs', 'page' => 'logs'],
            ['icon' => 'qr-code', 'label' => 'QR Codes', 'page' => 'qr-codes'],
            ['icon' => 'gear', 'label' => 'Settings', 'page' => 'settings'],
        ];
        break;
}
?>

<!-- Sidebar for Desktop -->
<aside class="sidebar d-none d-lg-block">
    <div class="sidebar-content">
        <div class="sidebar-header">
            <h6 class="text-muted text-uppercase fw-bold mb-3">
                <i class="bi bi-person-badge me-2"></i>
                <?= ucfirst($currentUserRole) ?> Panel
            </h6>
        </div>
        
        <nav class="sidebar-nav">
            <ul class="nav flex-column">
                <?php foreach ($navigationItems as $item): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === $item['page'] ? 'active' : '' ?>" 
                           href="<?= $_ENV['BASE_URL'] ?>?page=<?= $item['page'] ?>">
                            <i class="bi bi-<?= $item['icon'] ?> me-2"></i>
                            <?= $item['label'] ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        
        <!-- Quick Actions -->
        <div class="sidebar-footer mt-auto">
            <div class="quick-actions">
                <h6 class="text-muted text-uppercase fw-bold mb-2 small">Quick Actions</h6>
                <?php if ($currentUserRole === 'guard'): ?>
                    <button class="btn btn-success btn-sm w-100 mb-2" onclick="location.href='<?= $_ENV['BASE_URL'] ?>?page=scanner'">
                        <i class="bi bi-qr-code-scan me-1"></i> Scan QR
                    </button>
                <?php elseif ($currentUserRole === 'admin'): ?>
                    <button class="btn btn-primary btn-sm w-100 mb-2" onclick="location.href='<?= $_ENV['BASE_URL'] ?>?page=add-parent'">
                        <i class="bi bi-person-plus me-1"></i> Add Parent
                    </button>
                    <button class="btn btn-primary btn-sm w-100 mb-2" onclick="location.href='<?= $_ENV['BASE_URL'] ?>?page=add-student'">
                        <i class="bi bi-person-plus me-1"></i> Add Student
                    </button>
                <?php elseif ($currentUserRole === 'superadmin'): ?>
                    <button class="btn btn-warning btn-sm w-100 mb-2" onclick="location.href='<?= $_ENV['BASE_URL'] ?>?page=system-status'">
                        <i class="bi bi-gear me-1"></i> System Status
                    </button>
                <?php endif; ?>
                <button class="btn btn-outline-secondary btn-sm w-100" onclick="location.href='<?= $_ENV['BASE_URL'] ?>?page=logout'">
                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                </button>
            </div>
        </div>
    </div>
</aside>

<!-- Mobile Sidebar (Offcanvas) -->
<div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="sidebarLabel">
            <i class="bi bi-person-badge me-2"></i>
            <?= ucfirst($currentUserRole) ?> Panel
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    
    <div class="offcanvas-body">
        <nav class="mobile-nav">
            <ul class="nav flex-column">
                <?php foreach ($navigationItems as $item): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === $item['page'] ? 'active' : '' ?>" 
                           href="<?= $_ENV['BASE_URL'] ?>?page=<?= $item['page'] ?>" 
                           data-bs-dismiss="offcanvas">
                            <i class="bi bi-<?= $item['icon'] ?> me-2"></i>
                            <?= $item['label'] ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        
        <!-- Mobile Quick Actions -->
        <div class="mt-4">
            <h6 class="text-muted text-uppercase fw-bold mb-2 small">Quick Actions</h6>
            
            <?php if ($currentUserRole === 'guard'): ?>
                <button class="btn btn-success btn-sm w-100 mb-2" onclick="location.href='<?= $_ENV['BASE_URL'] ?>?page=scanner'">
                    <i class="bi bi-qr-code-scan me-1"></i> Scan QR
                </button>
            <?php elseif ($currentUserRole === 'admin'): ?>
                <button class="btn btn-primary btn-sm w-100 mb-2" onclick="location.href='<?= $_ENV['BASE_URL'] ?>?page=add-student'">
                    <i class="bi bi-person-plus me-1"></i> Add Student
                </button>
            <?php elseif ($currentUserRole === 'superadmin'): ?>
                <button class="btn btn-warning btn-sm w-100 mb-2" onclick="location.href='<?= $_ENV['BASE_URL'] ?>?page=system-status'">
                    <i class="bi bi-gear me-1"></i> System Status
                </button>
            <?php endif; ?>
            
            <button class="btn btn-outline-secondary btn-sm w-100" onclick="location.href='<?= $_ENV['BASE_URL'] ?>?page=logout'">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </button>
        </div>
    </div>
</div>