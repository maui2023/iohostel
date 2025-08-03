<?php
// Check if user is logged in and has appropriate permissions
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: ' . $_ENV['BASE_URL'] . '?page=login');
    exit();
}

require_once 'controllers/ParentController.php';
$parentController = new ParentController();

// Handle search and pagination
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['p'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

// Get parents data
$parents = $parentController->getAllParents($search, $limit, $offset);
$totalParents = $parentController->getTotalParentsCount($search);
$totalPages = ceil($totalParents / $limit);

// Handle status update
if ($_POST['action'] === 'update_status' && isset($_POST['parent_id'], $_POST['status'])) {
    $result = $parentController->updateParentStatus($_POST['parent_id'], $_POST['status']);
    if ($result) {
        $_SESSION['success_message'] = 'Parent status updated successfully!';
    } else {
        $_SESSION['error_message'] = 'Failed to update parent status.';
    }
    header('Location: ' . $_ENV['BASE_URL'] . '?page=parents');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parents Management - Asrama Testing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="bi bi-people"></i> Parents Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?php echo $_ENV['BASE_URL']; ?>?page=superadmin-dashboard" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Search and Filter Section -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-success">Search Parents</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="">
                            <input type="hidden" name="page" value="parents">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="search" 
                                               placeholder="Search by name, email, phone, or student name..." 
                                               value="<?php echo htmlspecialchars($search); ?>">
                                        <button class="btn btn-success" type="submit">
                                            <i class="bi bi-search"></i> Search
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <?php if ($search): ?>
                                        <a href="<?php echo $_ENV['BASE_URL']; ?>?page=parents" class="btn btn-outline-secondary">
                                            <i class="bi bi-x-circle"></i> Clear Search
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Parents Table -->
                <div class="card shadow">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-success">All Parents (<?php echo $totalParents; ?> total)</h6>
                        <div class="text-muted small">
                            Showing <?php echo min($offset + 1, $totalParents); ?>-<?php echo min($offset + $limit, $totalParents); ?> of <?php echo $totalParents; ?> entries
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($parents)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2">No parents found.</p>
                                <?php if ($search): ?>
                                    <p class="text-muted">Try adjusting your search criteria.</p>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="parentsTable">
                                    <thead class="table-success">
                                        <tr>
                                            <th>#</th>
                                            <th>Parent Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Students</th>
                                            <th>Status</th>
                                            <th>Registered</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($parents as $index => $parent): ?>
                                            <tr>
                                                <td><?php echo $offset + $index + 1; ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                            <?php echo strtoupper(substr($parent['name'], 0, 1)); ?>
                                                        </div>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($parent['name']); ?></strong>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($parent['email']); ?></td>
                                                <td><?php echo htmlspecialchars($parent['phone'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php if (!empty($parent['students'])): ?>
                                                        <div class="small">
                                                            <?php foreach ($parent['students'] as $student): ?>
                                                                <span class="badge bg-info me-1"><?php echo htmlspecialchars($student['name']); ?></span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">No students</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($parent['status'] === 'active'): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="small text-muted">
                                                    <?php echo date('M j, Y', strtotime($parent['created_at'])); ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button type="button" class="btn btn-outline-info" 
                                                                onclick="viewParent(<?php echo $parent['id']; ?>)" 
                                                                title="View Details">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-warning" 
                                                                onclick="editParent(<?php echo $parent['id']; ?>)" 
                                                                title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-<?php echo $parent['status'] === 'active' ? 'danger' : 'success'; ?>" 
                                                                onclick="toggleStatus(<?php echo $parent['id']; ?>, '<?php echo $parent['status'] === 'active' ? 'inactive' : 'active'; ?>')" 
                                                                title="<?php echo $parent['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                                            <i class="bi bi-<?php echo $parent['status'] === 'active' ? 'x-circle' : 'check-circle'; ?>"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                                <nav aria-label="Parents pagination" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=parents&p=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                                    <i class="bi bi-chevron-left"></i> Previous
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=parents&p=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=parents&p=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                                    Next <i class="bi bi-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Status Update Form -->
    <form id="statusForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="parent_id" id="statusParentId">
        <input type="hidden" name="status" id="statusValue">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        function toggleStatus(parentId, newStatus) {
            if (confirm('Are you sure you want to ' + (newStatus === 'active' ? 'activate' : 'deactivate') + ' this parent?')) {
                document.getElementById('statusParentId').value = parentId;
                document.getElementById('statusValue').value = newStatus;
                document.getElementById('statusForm').submit();
            }
        }
        
        function viewParent(parentId) {
            // Implement view parent details functionality
            alert('View parent details functionality to be implemented');
        }
        
        function editParent(parentId) {
            // Implement edit parent functionality
            alert('Edit parent functionality to be implemented');
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    </script>
    
    <style>
        .avatar-sm {
            width: 32px;
            height: 32px;
            font-size: 14px;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
        }
        
        .btn-group-sm > .btn {
            padding: 0.25rem 0.5rem;
        }
        
        .pagination .page-link {
            color: #28a745;
        }
        
        .pagination .page-item.active .page-link {
            background-color: #28a745;
            border-color: #28a745;
        }
    </style>
</body>
</html>