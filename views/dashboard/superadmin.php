<?php
// Prevent direct access
if (!defined('HOSTEL_APP')) {
    exit('Direct access not permitted');
}

// Check authentication and role
if (!isAuthenticated() || !hasRole('superadmin')) {
    header('Location: ' . $_ENV['BASE_URL'] . '?page=login');
    exit;
}

require_once __DIR__ . '/../../controllers/SuperadminController.php';
$controller = new SuperadminController();

$currentUserName = getCurrentUserName();
$pageTitle = 'Superadmin Dashboard';

// Handle AJAX requests
if (isset($_GET['action']) && $_GET['action'] === 'get_user' && isset($_GET['user_id'])) {
    $userId = intval($_GET['user_id']);
    $user = $controller->getUserById($userId);
    
    header('Content-Type: application/json');
    if ($user) {
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
    exit;
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_admin':
            $result = $controller->createAdmin($_POST);
            if ($result['success']) {
                $message = $result['message'];
                $messageType = 'success';
            } else {
                $message = implode(', ', $result['errors']);
                $messageType = 'danger';
            }
            break;
            
        case 'create_guard':
            $result = $controller->createGuard($_POST);
            if ($result['success']) {
                $message = $result['message'];
                $messageType = 'success';
            } else {
                $message = implode(', ', $result['errors']);
                $messageType = 'danger';
            }
            break;
            
        case 'update_status':
            $userId = $_POST['user_id'] ?? 0;
            $status = $_POST['status'] ?? '';
            $result = $controller->updateUserStatus($userId, $status);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'danger';
            break;
            
        case 'update_user':
            $userId = intval($_POST['user_id']);
            $result = $controller->updateUser($userId, $_POST);
            if ($result['success']) {
                $message = $result['message'];
                $messageType = 'success';
            } else {
                $message = implode(', ', $result['errors']);
                $messageType = 'danger';
            }
            break;
    }
}

// Get system statistics
$stats = $controller->getSystemStats();

// Get pagination and search parameters
$page = isset($_GET['users_page']) ? max(1, intval($_GET['users_page'])) : 1;
$search = isset($_GET['users_search']) ? trim($_GET['users_search']) : '';
$limit = 10;

// Get users data with pagination and search
$users = $controller->getAllUsers(null, $search, $page, $limit);
$totalUsers = $controller->getUsersCount(null, $search);
$totalPages = ceil($totalUsers / $limit);

include __DIR__ . '/../layouts/header.php';
?>

<div class="content-wrapper">
    <!-- Sidebar -->
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content with-sidebar">
        <div class="container-fluid">
        <main class="px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Superadmin Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Alert Messages -->
            <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Admins
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['total_admins'] ?? 0; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-person-gear fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Active Admins
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['active_admins'] ?? 0; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-check-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Total Guards
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['total_guards'] ?? 0; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-shield-shaded fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Active Guards
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['active_guards'] ?? 0; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-shield-check fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Users Management Table -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Admin & Guard Management</h6>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createAdminModal">
                            <i class="bi bi-plus-circle"></i> Add Admin
                        </button>
                        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#createGuardModal">
                            <i class="bi bi-plus-circle"></i> Add Guard
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Search Form -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <form method="GET" action="" class="d-flex">
                                <input type="hidden" name="page" value="superadmin-dashboard">
                                <input type="text" class="form-control" name="users_search" 
                                       placeholder="Search by name or phone..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit" class="btn btn-outline-primary ms-2">
                                    <i class="bi bi-search"></i>
                                </button>
                                <?php if (!empty($search)): ?>
                                <a href="?page=superadmin-dashboard" class="btn btn-outline-secondary ms-1">
                                    <i class="bi bi-x-circle"></i>
                                </a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-muted">
                                Showing <?php echo count($users); ?> of <?php echo $totalUsers; ?> users
                            </small>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered" id="usersTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <?php echo !empty($search) ? 'No users found matching your search.' : 'No users found.'; ?>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'primary' : 'info'; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <?php if ($user['status'] === 'active'): ?>
                                            <button type="button" class="btn btn-sm btn-warning" 
                                                    onclick="updateUserStatus(<?php echo $user['id']; ?>, 'inactive')">
                                                <i class="bi bi-pause-circle"></i> Deactivate
                                            </button>
                                            <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-success" 
                                                    onclick="updateUserStatus(<?php echo $user['id']; ?>, 'active')">
                                                <i class="bi bi-play-circle"></i> Activate
                                            </button>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="editUser(<?php echo $user['id']; ?>)">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Users pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=superadmin-dashboard&users_page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&users_search=' . urlencode($search) : ''; ?>">
                                    Previous
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=superadmin-dashboard&users_page=<?php echo $i; ?><?php echo !empty($search) ? '&users_search=' . urlencode($search) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=superadmin-dashboard&users_page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&users_search=' . urlencode($search) : ''; ?>">
                                    Next
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Parents Management Section -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-success">Parents Management</h6>
                    <a href="<?php echo $_ENV['BASE_URL']; ?>?page=parents" class="btn btn-success btn-sm">
                        <i class="bi bi-person-check"></i> View All Parents
                    </a>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">Manage parent accounts and their associated students.</p>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card border-left-success h-100">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Total Parents
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $stats['total_parents'] ?? 0; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-people fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-left-info h-100">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Active Parents
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $stats['active_parents'] ?? 0; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-person-check fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-left-warning h-100">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Students Linked
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $stats['students_with_parents'] ?? 0; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-person-hearts fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Students Management Section -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-info">Students Management</h6>
                    <a href="<?php echo $_ENV['BASE_URL']; ?>?page=students" class="btn btn-info btn-sm">
                        <i class="bi bi-mortarboard"></i> View All Students
                    </a>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">Manage student records, QR codes, and parent associations.</p>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card border-left-primary h-100">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Students
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $stats['total_students'] ?? 0; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-mortarboard fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-left-success h-100">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Active Students
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $stats['active_students'] ?? 0; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-person-check fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-left-info h-100">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Currently Inside
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $stats['students_inside'] ?? 0; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-house-check fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-left-warning h-100">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Currently Outside
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $stats['students_outside'] ?? 0; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-box-arrow-right fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        </div>
    </div>
</div>

<!-- Create Admin Modal -->
<div class="modal fade" id="createAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Admin Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_admin">
                    
                    <div class="mb-3">
                        <label for="admin_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="admin_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="admin_email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_phone" class="form-label">Phone Number (Optional)</label>
                        <input type="tel" class="form-control" id="admin_phone" name="phone">
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="admin_password" name="password" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Guard Modal -->
<div class="modal fade" id="createGuardModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Guard Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_guard">
                    
                    <div class="mb-3">
                        <label for="guard_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="guard_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="guard_email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="guard_email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="guard_phone" class="form-label">Phone Number (Optional)</label>
                        <input type="tel" class="form-control" id="guard_phone" name="phone">
                    </div>
                    
                    <div class="mb-3">
                        <label for="guard_password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="guard_password" name="password" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Create Guard</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Status Update Form (Hidden) -->
<form id="statusUpdateForm" method="POST" action="" style="display: none;">
    <input type="hidden" name="action" value="update_status">
    <input type="hidden" name="user_id" id="status_user_id">
    <input type="hidden" name="status" id="status_value">
</form>

<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.text-xs {
    font-size: 0.7rem;
}

.text-gray-300 {
    color: #dddfeb !important;
}

.text-gray-800 {
    color: #5a5c69 !important;
}

.shadow {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
}
</style>

<script>
function updateUserStatus(userId, status) {
    if (confirm('Are you sure you want to ' + (status === 'active' ? 'activate' : 'deactivate') + ' this user?')) {
        document.getElementById('status_user_id').value = userId;
        document.getElementById('status_value').value = status;
        document.getElementById('statusUpdateForm').submit();
    }
}

function editUser(userId) {
    // This would open an edit modal - for now just alert
    alert('Edit functionality will be implemented in the next phase. User ID: ' + userId);
}

// Initialize DataTable if available
document.addEventListener('DOMContentLoaded', function() {
    if (typeof DataTable !== 'undefined') {
        new DataTable('#usersTable');
    }
});
</script></div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" id="editUserForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="edit_phone" name="phone">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">New Password (Leave blank to keep current)</label>
                        <input type="password" class="form-control" id="edit_password" name="password" minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Function to update user status
function updateUserStatus(userId, status) {
    if (confirm('Are you sure you want to ' + (status === 'active' ? 'activate' : 'deactivate') + ' this user?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="user_id" value="${userId}">
            <input type="hidden" name="status" value="${status}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Function to edit user
function editUser(userId) {
    // Fetch user data via AJAX
    fetch('?page=superadmin-dashboard&action=get_user&user_id=' + userId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.user;
                document.getElementById('edit_user_id').value = user.id;
                document.getElementById('edit_name').value = user.name;
                document.getElementById('edit_email').value = user.email;
                document.getElementById('edit_phone').value = user.phone || '';
                document.getElementById('edit_password').value = '';
                
                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
                modal.show();
            } else {
                alert('Error loading user data: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading user data');
        });
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>