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

$currentUserRole = getCurrentUserRole();

// Redirect superadmins to their specific dashboard
if ($currentUserRole === 'superadmin') {
    header('Location: ' . $_ENV['BASE_URL'] . '?page=superadmin-dashboard');
    exit;
}
$currentUserName = getCurrentUserName();
$pageTitle = 'Dashboard';

// Get database connection for statistics
try {
    $db = Database::getInstance()->getConnection();
    
    // Get statistics based on role
    $stats = [];
    
    if (in_array($currentUserRole, ['superadmin', 'admin'])) {
        // Total students
        $stmt = $db->query("SELECT COUNT(*) as total FROM students WHERE status = 'active'");
        $stats['total_students'] = $stmt->fetchColumn();
        
        // Total parents
        $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'parent' AND status = 'active'");
        $stats['total_parents'] = $stmt->fetchColumn();
        
        // Today's check-ins
        $stmt = $db->query("SELECT COUNT(*) as total FROM inout_logs WHERE action = 'in' AND DATE(timestamp) = CURDATE()");
        $stats['today_checkins'] = $stmt->fetchColumn();
        
        // Today's check-outs
        $stmt = $db->query("SELECT COUNT(*) as total FROM inout_logs WHERE action = 'out' AND DATE(timestamp) = CURDATE()");
        $stats['today_checkouts'] = $stmt->fetchColumn();
        
        // Students currently inside (checked in but not checked out)
        $stmt = $db->query("
            SELECT COUNT(DISTINCT s.id) as total 
            FROM students s 
            INNER JOIN inout_logs l1 ON s.id = l1.student_id 
            WHERE s.status = 'active' 
            AND l1.action = 'in' 
            AND l1.timestamp = (
                SELECT MAX(l2.timestamp) 
                FROM inout_logs l2 
                WHERE l2.student_id = s.id
            )
        ");
        $stats['students_inside'] = $stmt->fetchColumn();
        
        // Recent activities (last 10)
        $stmt = $db->query("
            SELECT l.action, l.timestamp, s.name as student_name, u.name as guard_name
            FROM inout_logs l
            INNER JOIN students s ON l.student_id = s.id
            LEFT JOIN users u ON l.guard_id = u.id
            ORDER BY l.timestamp DESC
            LIMIT 10
        ");
        $recent_activities = $stmt->fetchAll();
    }
    
    if ($currentUserRole === 'superadmin') {
        // Additional superadmin stats
        $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin' AND status = 'active'");
        $stats['total_admins'] = $stmt->fetchColumn();
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'guard' AND status = 'active'");
        $stats['total_guards'] = $stmt->fetchColumn();
    }
    
} catch (PDOException $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    $stats = [];
    $recent_activities = [];
}

// Include header
include __DIR__ . '/../layouts/header.php';
?>

<div class="content-wrapper">
    <!-- Sidebar -->
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content with-sidebar">

<style>
:root {
    --primary-color: #0d6efd;
    --success-color: #198754;
    --warning-color: #ffc107;
    --danger-color: #dc3545;
    --info-color: #0dcaf0;
    --dark-color: #212529;
    --light-color: #f8f9fa;
}

.dashboard-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.dashboard-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    margin: 0;
}

.activity-item {
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 8px;
    border-left: 4px solid;
}

.activity-in {
    background-color: #d1e7dd;
    border-left-color: var(--success-color);
}

.activity-out {
    background-color: #f8d7da;
    border-left-color: var(--danger-color);
}

.quick-action-btn {
    border-radius: 10px;
    padding: 15px;
    text-decoration: none;
    display: block;
    transition: all 0.2s ease;
    border: 2px solid transparent;
}

.quick-action-btn:hover {
    transform: translateY(-2px);
    text-decoration: none;
}

.welcome-section {
    background: linear-gradient(135deg, var(--primary-color), #0056b3);
    color: white;
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
}

@media (max-width: 768px) {
    .stat-number {
        font-size: 1.5rem;
    }
    
    .welcome-section {
        padding: 1.5rem;
    }
}
</style>

        <div class="container-fluid py-4">
    <!-- Welcome Section -->
    <div class="welcome-section">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-2">Welcome back, <?php echo htmlspecialchars($currentUserName); ?>!</h1>
                <p class="mb-0 opacity-90">
                    <?php if ($currentUserRole === 'superadmin'): ?>
                        You have full system access. Manage users, monitor activities, and configure system settings.
                    <?php elseif ($currentUserRole === 'admin'): ?>
                        Manage students, parents, and monitor check-in/out activities.
                    <?php endif; ?>
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="d-flex align-items-center justify-content-md-end">
                    <i class="bi bi-calendar-date me-2"></i>
                    <span><?php echo date('l, F j, Y'); ?></span>
                </div>
                <div class="d-flex align-items-center justify-content-md-end mt-1">
                    <i class="bi bi-clock me-2"></i>
                    <span id="current-time"><?php echo date('g:i A'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <?php if (in_array($currentUserRole, ['superadmin', 'admin'])): ?>
    <div class="row g-4 mb-4">
        <?php if ($currentUserRole === 'superadmin'): ?>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card dashboard-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon bg-primary me-3">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <h3 class="stat-number text-primary"><?php echo $stats['total_admins'] ?? 0; ?></h3>
                        <p class="stat-label">Admins</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card dashboard-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon bg-info me-3">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <h3 class="stat-number text-info"><?php echo $stats['total_guards'] ?? 0; ?></h3>
                        <p class="stat-label">Guards</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card dashboard-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon bg-success me-3">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <div>
                        <h3 class="stat-number text-success"><?php echo $stats['total_students'] ?? 0; ?></h3>
                        <p class="stat-label">Students</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card dashboard-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon bg-warning me-3">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <h3 class="stat-number text-warning"><?php echo $stats['total_parents'] ?? 0; ?></h3>
                        <p class="stat-label">Parents</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card dashboard-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon bg-success me-3">
                        <i class="bi bi-box-arrow-in-right"></i>
                    </div>
                    <div>
                        <h3 class="stat-number text-success"><?php echo $stats['today_checkins'] ?? 0; ?></h3>
                        <p class="stat-label">Today's Check-ins</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card dashboard-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon bg-danger me-3">
                        <i class="bi bi-box-arrow-right"></i>
                    </div>
                    <div>
                        <h3 class="stat-number text-danger"><?php echo $stats['today_checkouts'] ?? 0; ?></h3>
                        <p class="stat-label">Today's Check-outs</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Students Inside Alert -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info d-flex align-items-center" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i>
                <strong><?php echo $stats['students_inside'] ?? 0; ?></strong>&nbsp;students are currently inside the hostel.
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Quick Actions -->
        <div class="col-lg-4">
            <div class="card dashboard-card h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-lightning-charge me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-3">
                        <?php if (in_array($currentUserRole, ['superadmin', 'admin'])): ?>
                        <a href="<?php echo $_ENV['BASE_URL']; ?>?page=add-student" class="btn btn-primary quick-action-btn">
                            <i class="bi bi-person-plus me-2"></i>Add New Student
                        </a>
                        
                        <a href="<?php echo $_ENV['BASE_URL']; ?>?page=generate-qr" class="btn btn-success quick-action-btn">
                            <i class="bi bi-qr-code me-2"></i>Generate QR Codes
                        </a>
                        
                        <a href="<?php echo $_ENV['BASE_URL']; ?>?page=students" class="btn btn-info quick-action-btn">
                            <i class="bi bi-people me-2"></i>Manage Students
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($currentUserRole === 'superadmin'): ?>
                        <a href="<?php echo $_ENV['BASE_URL']; ?>?page=admins" class="btn btn-warning quick-action-btn">
                            <i class="bi bi-person-gear me-2"></i>Manage Admins
                        </a>
                        
                        <a href="<?php echo $_ENV['BASE_URL']; ?>?page=guards" class="btn btn-secondary quick-action-btn">
                            <i class="bi bi-shield-check me-2"></i>Manage Guards
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($currentUserRole === 'guard'): ?>
                        <a href="<?php echo $_ENV['BASE_URL']; ?>?page=scanner" class="btn btn-primary quick-action-btn">
                            <i class="bi bi-qr-code-scan me-2"></i>Scan QR
                        </a>
                        
                        <a href="<?php echo $_ENV['BASE_URL']; ?>?page=student-checkin" class="btn btn-success quick-action-btn">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Student Check-in
                        </a>
                        
                        <a href="<?php echo $_ENV['BASE_URL']; ?>?page=student-checkout" class="btn btn-warning quick-action-btn">
                            <i class="bi bi-box-arrow-right me-2"></i>Student Check-out
                        </a>
                        
                        <a href="<?php echo $_ENV['BASE_URL']; ?>?page=visitor-log" class="btn btn-info quick-action-btn">
                            <i class="bi bi-person-plus me-2"></i>Visitor Entry
                        </a>
                        <?php endif; ?>
                        
                        <a href="<?php echo $_ENV['BASE_URL']; ?>?page=logs" class="btn btn-outline-primary quick-action-btn">
                            <i class="bi bi-clock-history me-2"></i>View Activity Logs
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activities -->
        <?php if (in_array($currentUserRole, ['superadmin', 'admin']) && !empty($recent_activities)): ?>
        <div class="col-lg-8">
            <div class="card dashboard-card h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-activity me-2"></i>Recent Activities
                        </h5>
                        <a href="<?php echo $_ENV['BASE_URL']; ?>?page=logs" class="btn btn-sm btn-outline-primary">
                            View All <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="activity-list">
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item activity-<?php echo $activity['action']; ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong><?php echo htmlspecialchars($activity['student_name']); ?></strong>
                                    <span class="badge bg-<?php echo $activity['action'] === 'in' ? 'success' : 'danger'; ?> ms-2">
                                        <?php echo strtoupper($activity['action']); ?>
                                    </span>
                                    <?php if ($activity['guard_name']): ?>
                                    <div class="small text-muted mt-1">
                                        <i class="bi bi-person me-1"></i>by <?php echo htmlspecialchars($activity['guard_name']); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted">
                                    <?php echo date('M j, g:i A', strtotime($activity['timestamp'])); ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- System Status (Superadmin only) -->
        <?php if ($currentUserRole === 'superadmin'): ?>
        <div class="col-lg-4">
            <div class="card dashboard-card h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-gear me-2"></i>System Status
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="badge bg-success rounded-pill me-2"></div>
                        <span>Database Connection</span>
                        <i class="bi bi-check-circle-fill text-success ms-auto"></i>
                    </div>
                    
                    <div class="d-flex align-items-center mb-3">
                        <div class="badge bg-success rounded-pill me-2"></div>
                        <span>Authentication System</span>
                        <i class="bi bi-check-circle-fill text-success ms-auto"></i>
                    </div>
                    
                    <div class="d-flex align-items-center mb-3">
                        <div class="badge bg-success rounded-pill me-2"></div>
                        <span>QR Code Generator</span>
                        <i class="bi bi-check-circle-fill text-success ms-auto"></i>
                    </div>
                    
                    <div class="d-flex align-items-center">
                        <div class="badge bg-warning rounded-pill me-2"></div>
                        <span>WhatsApp Integration</span>
                        <i class="bi bi-exclamation-triangle-fill text-warning ms-auto"></i>
                    </div>
                    
                    <hr>
                    
                    <div class="text-center">
                        <a href="<?php echo $_ENV['BASE_URL']; ?>?page=settings" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-gear me-1"></i>System Settings
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

<script>
// Update current time every minute
function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
    document.getElementById('current-time').textContent = timeString;
}

// Update time immediately and then every minute
updateTime();
setInterval(updateTime, 60000);

// Add smooth animations
document.addEventListener('DOMContentLoaded', function() {
    // Animate cards on load
    const cards = document.querySelectorAll('.dashboard-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>

<?php
// Include footer
include __DIR__ . '/../layouts/footer.php';
?>