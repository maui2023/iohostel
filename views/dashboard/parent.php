<?php
// Prevent direct access
if (!defined('HOSTEL_APP')) {
    exit('Direct access not permitted');
}

// Check authentication and role
if (!isAuthenticated() || !hasRole('parent')) {
    header('Location: ' . $_ENV['BASE_URL'] . '?page=login');
    exit;
}

$userName = getCurrentUserName();
$hostelName = $_ENV['HOSTEL_NAME'] ?? 'Hostel Management System';

// Get current date and time
$currentDate = date('Y-m-d');
$currentTime = date('H:i:s');
$currentDateTime = date('F j, Y - g:i A');

// Mock data for parent dashboard (replace with actual database queries)
$myChildren = [
    [
        'id' => 1,
        'name' => 'John Doe Jr.',
        'room' => 'A-101',
        'status' => 'inside',
        'last_activity' => '2024-01-15 14:30:00',
        'last_action' => 'Check-in'
    ],
    [
        'id' => 2,
        'name' => 'Jane Doe',
        'room' => 'B-205',
        'status' => 'outside',
        'last_activity' => '2024-01-15 13:45:00',
        'last_action' => 'Check-out'
    ]
];

// Recent activities for my children
$recentActivities = [
    ['time' => '14:30', 'child' => 'John Doe Jr.', 'action' => 'Check-in', 'room' => 'A-101'],
    ['time' => '13:45', 'child' => 'Jane Doe', 'action' => 'Check-out', 'room' => 'B-205'],
    ['time' => '08:15', 'child' => 'John Doe Jr.', 'action' => 'Check-out', 'room' => 'A-101'],
    ['time' => '07:30', 'child' => 'Jane Doe', 'action' => 'Check-in', 'room' => 'B-205'],
    ['time' => '22:00', 'child' => 'John Doe Jr.', 'action' => 'Check-in', 'room' => 'A-101']
];

// Statistics
$totalChildren = count($myChildren);
$childrenInside = count(array_filter($myChildren, function($child) { return $child['status'] === 'inside'; }));
$childrenOutside = count(array_filter($myChildren, function($child) { return $child['status'] === 'outside'; }));
$todayActivities = 8; // Mock data

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
                        <h1 class="h3 mb-1">Parent Dashboard</h1>
                        <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($userName); ?>!</p>
                    </div>
                    <div class="text-end">
                        <div class="badge bg-info fs-6 mb-1"><?php echo $currentDateTime; ?></div>
                        <div class="small text-muted">Parent Portal</div>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="row mb-4">
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
                                        <div class="fw-bold text-primary fs-2 mb-0"><?php echo $totalChildren; ?></div>
                                        <div class="text-muted small">My Children</div>
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
                                        <div class="bg-success bg-gradient rounded-circle p-3">
                                            <i class="bi bi-house-check text-white fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="fw-bold text-success fs-2 mb-0"><?php echo $childrenInside; ?></div>
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
                                        <div class="bg-warning bg-gradient rounded-circle p-3">
                                            <i class="bi bi-house-dash text-white fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="fw-bold text-warning fs-2 mb-0"><?php echo $childrenOutside; ?></div>
                                        <div class="text-muted small">Currently Outside</div>
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
                                            <i class="bi bi-clock-history text-white fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="fw-bold text-info fs-2 mb-0"><?php echo $todayActivities; ?></div>
                                        <div class="text-muted small">Today's Activities</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- My Children Status -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-transparent border-0 pb-0">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-people text-primary me-2"></i>
                                    My Children Status
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <?php foreach ($myChildren as $child): ?>
                                    <div class="col-md-6">
                                        <div class="card border-start border-4 border-<?php echo $child['status'] === 'inside' ? 'success' : 'warning'; ?> h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="card-title mb-0"><?php echo htmlspecialchars($child['name']); ?></h6>
                                                    <span class="badge bg-<?php echo $child['status'] === 'inside' ? 'success' : 'warning'; ?>">
                                                        <?php echo $child['status'] === 'inside' ? 'Inside' : 'Outside'; ?>
                                                    </span>
                                                </div>
                                                <p class="card-text small text-muted mb-2">
                                                    <i class="bi bi-door-open me-1"></i>Room: <?php echo htmlspecialchars($child['room']); ?>
                                                </p>
                                                <p class="card-text small">
                                                    <strong>Last Activity:</strong><br>
                                                    <span class="badge bg-light text-dark">
                                                        <?php echo $child['last_action']; ?>
                                                    </span>
                                                    <span class="text-muted ms-2">
                                                        <?php echo date('M j, g:i A', strtotime($child['last_activity'])); ?>
                                                    </span>
                                                </p>
                                                <div class="d-grid">
                                                    <a href="?page=child-details&id=<?php echo $child['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye me-1"></i>View Details
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activities and Quick Actions -->
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
                                    <a href="?page=activity-history" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Time</th>
                                                <th>Child</th>
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
                                                <td><?php echo htmlspecialchars($activity['child']); ?></td>
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
                    
                    <!-- Quick Actions and Notifications -->
                    <div class="col-lg-4 mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-transparent border-0 pb-0">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-lightning-charge text-warning me-2"></i>
                                    Quick Actions
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Quick Actions -->
                                <div class="d-grid gap-2 mb-4">
                                    <a href="?page=request-permission" class="btn btn-primary">
                                        <i class="bi bi-calendar-check me-2"></i>Request Permission
                                    </a>
                                    <a href="?page=emergency-contact" class="btn btn-danger">
                                        <i class="bi bi-telephone me-2"></i>Emergency Contact
                                    </a>
                                    <a href="?page=activity-history" class="btn btn-outline-info">
                                        <i class="bi bi-clock-history me-2"></i>View Full History
                                    </a>
                                    <a href="?page=notifications" class="btn btn-outline-secondary">
                                        <i class="bi bi-bell me-2"></i>Notifications
                                    </a>
                                </div>
                                
                                <!-- Notifications -->
                                <div class="border-top pt-3">
                                    <h6 class="text-muted mb-3">
                                        <i class="bi bi-bell me-1"></i>Recent Notifications
                                    </h6>
                                    
                                    <div class="alert alert-info d-flex align-items-center mb-2" role="alert">
                                        <i class="bi bi-info-circle me-2"></i>
                                        <div class="small">
                                            <strong>John Doe Jr.</strong> checked in at 2:30 PM
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-warning d-flex align-items-center mb-2" role="alert">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        <div class="small">
                                            <strong>Jane Doe</strong> checked out at 1:45 PM
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-success d-flex align-items-center mb-0" role="alert">
                                        <i class="bi bi-check-circle me-2"></i>
                                        <div class="small">
                                            All children are safe and accounted for
                                        </div>
                                    </div>
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

.border-start {
    border-left-width: 4px !important;
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