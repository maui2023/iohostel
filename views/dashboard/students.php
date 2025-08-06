<?php
// Check if user is logged in and has appropriate permissions
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: ' . $_ENV['BASE_URL'] . '?page=login');
    exit();
}

require_once 'controllers/StudentController.php';
$studentController = new StudentController();

// Handle search and pagination
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['p'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

// Get students data
$students = $studentController->getAllStudents($search, $limit, $offset);
$totalStudents = $studentController->getTotalStudentsCount($search);
$totalPages = ceil($totalStudents / $limit);

// Handle status update
if ($_POST['action'] === 'update_status' && isset($_POST['student_id'], $_POST['status'])) {
    $result = $studentController->updateStudentStatus($_POST['student_id'], $_POST['status']);
    if ($result) {
        $_SESSION['success_message'] = 'Student status updated successfully!';
    } else {
        $_SESSION['error_message'] = 'Failed to update student status.';
    }
    header('Location: ' . $_ENV['BASE_URL'] . '?page=students');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students Management - Asrama Testing</title>
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
                    <h1 class="h2"><i class="bi bi-mortarboard"></i> Students Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?php echo $_ENV['BASE_URL']; ?>?page=superadmin-dashboard" class="btn btn-outline-secondary me-2">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                        <a href="<?php echo $_ENV['BASE_URL']; ?>?page=add-student" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Add Student
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
                        <h6 class="m-0 font-weight-bold text-primary">Search Students</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="">
                            <input type="hidden" name="page" value="students">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="search" 
                                               placeholder="Search by name, student ID, email, phone, or parent name..." 
                                               value="<?php echo htmlspecialchars($search); ?>">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="bi bi-search"></i> Search
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <?php if ($search): ?>
                                        <a href="<?php echo $_ENV['BASE_URL']; ?>?page=students" class="btn btn-outline-secondary">
                                            <i class="bi bi-x-circle"></i> Clear Search
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Students Table -->
                <div class="card shadow">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">All Students (<?php echo $totalStudents; ?> total)</h6>
                        <div class="text-muted small">
                            Showing <?php echo min($offset + 1, $totalStudents); ?>-<?php echo min($offset + $limit, $totalStudents); ?> of <?php echo $totalStudents; ?> entries
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($students)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-mortarboard text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2">No students found.</p>
                                <?php if ($search): ?>
                                    <p class="text-muted">Try adjusting your search criteria.</p>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="studentsTable">
                                    <thead class="table-primary">
                                        <tr>
                                            <th>#</th>
                                            <th>Student</th>
                                            <th>Student No</th>
                                            <th>Class</th>
                                            <th>Parent</th>
                                            <th>Status</th>
                                            <th>QR Code</th>
                                            <th>Registered</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $index => $student): ?>
                                            <tr>
                                                <td><?php echo $offset + $index + 1; ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                            <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                                                        </div>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($student['name']); ?></strong>
                                                            <?php if ($student['email']): ?>
                                                                <div class="small text-muted"><?php echo htmlspecialchars($student['email']); ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($student['student_no']); ?></span>
                                                </td>
                                                <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                                                <td>
                                                    <?php if ($student['parent_name']): ?>
                                                        <div class="small">
                                                            <strong><?php echo htmlspecialchars($student['parent_name']); ?></strong>
                                                            <?php if ($student['parent_phone']): ?>
                                                                <div class="text-muted"><?php echo htmlspecialchars($student['parent_phone']); ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">No parent assigned</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($student['status'] === 'active'): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="/helpers/qr.php?token=<?php echo urlencode($student['qr_token']); ?>" class="btn btn-sm btn-outline-primary" download="qr-<?php echo htmlspecialchars($student['student_no']); ?>.png">
                                                        <i class="bi bi-qr-code"></i> Download
                                                    </a>
                                                </td>

                                                <td class="small text-muted">
                                                    <?php echo date('M j, Y', strtotime($student['created_at'])); ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button type="button" class="btn btn-outline-info" 
                                                                onclick="viewStudent(<?php echo $student['id']; ?>)" 
                                                                title="View Details">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-warning" 
                                                                onclick="editStudent(<?php echo $student['id']; ?>)" 
                                                                title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-<?php echo $student['status'] === 'active' ? 'danger' : 'success'; ?>" 
                                                                onclick="toggleStatus(<?php echo $student['id']; ?>, '<?php echo $student['status'] === 'active' ? 'inactive' : 'active'; ?>')" 
                                                                title="<?php echo $student['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                                            <i class="bi bi-<?php echo $student['status'] === 'active' ? 'x-circle' : 'check-circle'; ?>"></i>
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
                                <nav aria-label="Students pagination" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=students&p=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                                    <i class="bi bi-chevron-left"></i> Previous
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=students&p=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=students&p=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
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
        <input type="hidden" name="student_id" id="statusStudentId">
        <input type="hidden" name="status" id="statusValue">
    </form>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    
    <script>
        function toggleStatus(studentId, newStatus) {
            if (confirm('Are you sure you want to ' + (newStatus === 'active' ? 'activate' : 'deactivate') + ' this student?')) {
                document.getElementById('statusStudentId').value = studentId;
                document.getElementById('statusValue').value = newStatus;
                document.getElementById('statusForm').submit();
            }
        }
        
        function viewStudent(studentId) {
            // Implement view student details functionality
            window.location.href = '<?php echo $_ENV['BASE_URL']; ?>?page=student-details&id=' + studentId;
        }
        
        function editStudent(studentId) {
            // Implement edit student functionality
            window.location.href = '<?php echo $_ENV['BASE_URL']; ?>?page=edit-student&id=' + studentId;
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
            color: #007bff;
        }
        
        .pagination .page-item.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
        }
        
        .table td {
            vertical-align: middle;
        }
    </style>
</body>
</html>