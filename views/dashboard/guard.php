<?php
// Prevent direct access
if (!defined('HOSTEL_APP')) {
    exit('Direct access not permitted');
}

// Check authentication and role
if (!isAuthenticated() || !hasRole('guard')) {
    header('Location: ' . $_ENV['BASE_URL'] . '?page=login');
    exit;
}

$userName = getCurrentUserName();
$hostelName = $_ENV['HOSTEL_NAME'] ?? 'Hostel Management System';

// Get current date and time
$currentDate = date('Y-m-d');
$currentTime = date('H:i:s');
$currentDateTime = date('F j, Y - g:i A');

// Mock data for guard dashboard (replace with actual database queries)
$todayCheckIns = 45;
$todayCheckOuts = 38;
$currentlyInside = 127;
$totalStudents = 165;
$pendingApprovals = 3;
$emergencyContacts = 2;

// Recent activities (mock data)
$recentActivities = [
    ['time' => '14:30', 'student' => 'John Doe', 'action' => 'Check-in', 'room' => 'A-101'],
    ['time' => '14:15', 'student' => 'Jane Smith', 'action' => 'Check-out', 'room' => 'B-205'],
    ['time' => '13:45', 'student' => 'Mike Johnson', 'action' => 'Check-in', 'room' => 'C-303'],
    ['time' => '13:30', 'student' => 'Sarah Wilson', 'action' => 'Check-out', 'room' => 'A-150'],
    ['time' => '13:00', 'student' => 'David Brown', 'action' => 'Check-in', 'room' => 'B-220']
];

include_once 'views/layouts/header.php';
?>

<div class="content-wrapper">
    <!-- Sidebar -->
    <?php include_once 'views/layouts/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content with-sidebar">
        <div class="container-fluid">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-1">Security Guard Dashboard</h1>
                        <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($userName); ?>!</p>
                    </div>
                    <div class="text-end">
                        <div class="badge bg-success fs-6 mb-1"><?php echo $currentDateTime; ?></div>
                        <div class="small text-muted">Guard on Duty</div>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-success bg-gradient rounded-circle p-3">
                                            <i class="bi bi-box-arrow-in-right text-white fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="fw-bold text-success fs-2 mb-0"><?php echo $todayCheckIns; ?></div>
                                        <div class="text-muted small">Today's Check-ins</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-warning bg-gradient rounded-circle p-3">
                                            <i class="bi bi-box-arrow-right text-white fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="fw-bold text-warning fs-2 mb-0"><?php echo $todayCheckOuts; ?></div>
                                        <div class="text-muted small">Today's Check-outs</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-primary bg-gradient rounded-circle p-3">
                                            <i class="bi bi-people text-white fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="fw-bold text-primary fs-2 mb-0"><?php echo $currentlyInside; ?></div>
                                        <div class="text-muted small">Currently Inside</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-info bg-gradient rounded-circle p-3">
                                            <i class="bi bi-person-check text-white fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="fw-bold text-info fs-2 mb-0"><?php echo $totalStudents; ?></div>
                                        <div class="text-muted small">Total Students</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-transparent border-0 pb-0">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-lightning-charge text-primary me-2"></i>
                                    Quick Actions
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-lg-2 col-md-4 col-sm-6">
                                        <a href="?page=scanner" class="btn btn-primary w-100 py-3">
                                            <i class="bi bi-qr-code-scan fs-4 d-block mb-2"></i>
                                            Scan QR
                                        </a>
                                    </div>
                                    <div class="col-lg-2 col-md-4 col-sm-6">
                                        <a href="?page=student-checkin" class="btn btn-success w-100 py-3">
                                            <i class="bi bi-box-arrow-in-right fs-4 d-block mb-2"></i>
                                            Student Check-in
                                        </a>
                                    </div>
                                    <div class="col-lg-2 col-md-4 col-sm-6">
                                        <a href="?page=student-checkout" class="btn btn-warning w-100 py-3">
                                            <i class="bi bi-box-arrow-right fs-4 d-block mb-2"></i>
                                            Student Check-out
                                        </a>
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-sm-6">
                                        <a href="?page=visitor-log" class="btn btn-info w-100 py-3">
                                            <i class="bi bi-person-plus fs-4 d-block mb-2"></i>
                                            Visitor Entry
                                        </a>
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-sm-6">
                                        <a href="?page=emergency" class="btn btn-danger w-100 py-3">
                                            <i class="bi bi-exclamation-triangle fs-4 d-block mb-2"></i>
                                            Emergency Alert
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activities and Alerts -->
                <div class="row">
                    <!-- Recent Activities -->
                    <div class="col-lg-8 mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-transparent border-0 pb-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-clock-history text-primary me-2"></i>
                                        Recent Activities
                                    </h5>
                                    <a href="?page=activity-log" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Time</th>
                                                <th>Student</th>
                                                <th>Action</th>
                                                <th>Room</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentActivities as $activity): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-light text-dark"><?php echo $activity['time']; ?></span>
                                                </td>
                                                <td><?php echo htmlspecialchars($activity['student']); ?></td>
                                                <td>
                                                    <?php if ($activity['action'] === 'Check-in'): ?>
                                                        <span class="badge bg-success">
                                                            <i class="bi bi-box-arrow-in-right me-1"></i>Check-in
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">
                                                            <i class="bi bi-box-arrow-right me-1"></i>Check-out
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($activity['room']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Alerts and Notifications -->
                    <div class="col-lg-4 mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-transparent border-0 pb-0">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-bell text-warning me-2"></i>
                                    Alerts & Notifications
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Pending Approvals -->
                                <?php if ($pendingApprovals > 0): ?>
                                <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <div>
                                        <strong><?php echo $pendingApprovals; ?> pending approval(s)</strong><br>
                                        <small>Late entry requests waiting for approval</small>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Emergency Contacts -->
                                <?php if ($emergencyContacts > 0): ?>
                                <div class="alert alert-danger d-flex align-items-center mb-3" role="alert">
                                    <i class="bi bi-telephone me-2"></i>
                                    <div>
                                        <strong><?php echo $emergencyContacts; ?> emergency contact(s)</strong><br>
                                        <small>Parents requesting immediate contact</small>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- System Status -->
                                <div class="alert alert-success d-flex align-items-center mb-3" role="alert">
                                    <i class="bi bi-check-circle me-2"></i>
                                    <div>
                                        <strong>All systems operational</strong><br>
                                        <small>QR scanner and database online</small>
                                    </div>
                                </div>
                                
                                <!-- Quick Links -->
                                <div class="d-grid gap-2">
                                    <a href="?page=scanner" class="btn btn-success btn-sm">
                                        <i class="bi bi-qr-code-scan me-1"></i>Scan QR
                                    </a>
                                    <a href="?page=reports" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-file-earmark-text me-1"></i>Generate Report
                                    </a>
                                    <a href="?page=settings" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-gear me-1"></i>Settings
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
    </div>
</div>

<style>
.main-content {
    padding: 2rem;
    background-color: #f8f9fa;
    min-height: calc(100vh - 60px);
}

.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

.btn {
    transition: all 0.2s ease-in-out;
}

.btn:hover {
    transform: translateY(-1px);
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.badge {
    font-size: 0.75rem;
}

@media (max-width: 768px) {
    .main-content {
        padding: 1rem;
    }
    
    .fs-2 {
        font-size: 1.5rem !important;
    }
}
</style>

<?php include_once 'views/layouts/footer.php'; ?>