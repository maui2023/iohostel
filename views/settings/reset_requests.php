<?php
// Prevent direct access
if (!defined('HOSTEL_APP')) {
    exit('Direct access not permitted');
}

// Check authentication and role
if (!isAuthenticated() || !in_array(getCurrentUserRole(), ['admin', 'superadmin'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '?page=login');
    exit;
}

require_once __DIR__ . '/../../controllers/SettingsController.php';

$user = [
    'id' => getCurrentUserId(),
    'role' => getCurrentUserRole(),
    'name' => getCurrentUserName()
];

$settingsController = new SettingsController();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'accept_request':
            $result = $settingsController->acceptResetRequest($_POST['request_id'], $user['id']);
            echo json_encode($result);
            exit;
            
        case 'reject_request':
            $result = $settingsController->rejectResetRequest($_POST['request_id'], $user['id']);
            echo json_encode($result);
            exit;
            
        case 'get_whatsapp_data':
            $data = $settingsController->getUserForWhatsApp($_POST['request_id']);
            if ($data) {
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Request not found or not accepted']);
            }
            exit;
    }
}

// Get pagination parameters
$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limit = 10;

// Get reset requests
$requests = $settingsController->getResetRequests($user['role'], $page, $limit);
$totalRequests = $settingsController->getResetRequestsCount($user['role']);
$totalPages = ceil($totalRequests / $limit);

$pageTitle = 'Password Reset Requests';

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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Password Reset Requests</h3>
                    <a href="/?page=settings" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Settings
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($requests)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No password reset requests found</h5>
                            <p class="text-muted">When users request password resets, they will appear here.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Role</th>
                                        <th>Phone</th>
                                        <th>Requested At</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requests as $request): ?>
                                        <tr id="request-<?= $request['id'] ?>">
                                            <td>
                                                <strong><?= htmlspecialchars($request['name']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($request['email']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $request['role'] === 'admin' ? 'primary' : ($request['role'] === 'guard' ? 'success' : 'info') ?>">
                                                    <?= ucfirst($request['role']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($request['phone']) ?></td>
                                            <td><?= date('M j, Y g:i A', strtotime($request['requested_at'])) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $request['status'] === 'pending' ? 'warning' : ($request['status'] === 'accepted' ? 'success' : 'danger') ?>">
                                                    <?= ucfirst($request['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($request['status'] === 'pending'): ?>
                                                    <button class="btn btn-sm btn-success me-1" onclick="acceptRequest(<?= $request['id'] ?>)">
                                                        <i class="fas fa-check"></i> Accept
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="rejectRequest(<?= $request['id'] ?>)">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                <?php elseif ($request['status'] === 'accepted'): ?>
                                                    <button class="btn btn-sm btn-info" onclick="sendWhatsApp(<?= $request['id'] ?>)">
                                                        <i class="fab fa-whatsapp"></i> Send Password
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">No actions available</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Reset requests pagination">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=password-reset-requests&p=<?= $page - 1 ?>">
                                                <i class="fas fa-chevron-left"></i> Previous
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=password-reset-requests&p=<?= $i ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=password-reset-requests&p=<?= $page + 1 ?>">
                                                Next <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</main>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 mb-0">Processing request...</p>
            </div>
        </div>
    </div>
</div>

<script>
function acceptRequest(requestId) {
    if (!confirm('Are you sure you want to accept this password reset request? A new password will be generated.')) {
        return;
    }
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=accept_request&request_id=${requestId}`
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            alert('Request accepted successfully! New password generated.');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        hideLoading();
        alert('Error processing request: ' + error.message);
    });
}

function rejectRequest(requestId) {
    if (!confirm('Are you sure you want to reject this password reset request?')) {
        return;
    }
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=reject_request&request_id=${requestId}`
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            alert('Request rejected successfully.');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        hideLoading();
        alert('Error processing request: ' + error.message);
    });
}

function sendWhatsApp(requestId) {
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_whatsapp_data&request_id=${requestId}`
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            const userData = data.data;
            const message = `Hello ${userData.name},\n\nYour password reset request has been approved.\n\nYour new password is: ${userData.new_password}\n\nPlease login and change your password immediately for security.\n\nBest regards,\nHostel Management System`;
            const whatsappUrl = `https://wa.me/6${userData.phone.replace(/[^0-9]/g, '')}?text=${encodeURIComponent(message)}`;
            
            window.open(whatsappUrl, '_blank');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        hideLoading();
        alert('Error getting WhatsApp data: ' + error.message);
    });
}

function showLoading() {
    const modal = new bootstrap.Modal(document.getElementById('loadingModal'));
    modal.show();
}

function hideLoading() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('loadingModal'));
    if (modal) {
        modal.hide();
    }
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>