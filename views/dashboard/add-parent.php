<?php
// Prevent direct access
if (!defined('HOSTEL_APP')) {
    exit('Direct access not permitted');
}

// Only allow admin or superadmin
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'superadmin'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '?page=login');
    exit();
}
$pageTitle = 'Add Parent';
include __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../../controllers/ParentController.php';
$parentController = new ParentController();
$editParentData = null;
$parentCreateResult = '';
$whatsappBtnHtml = '';
// Handle status toggle
if (isset($_GET['action']) && $_GET['action'] === 'update_status' && isset($_GET['parent_id'], $_GET['status'])) {
    $updateResult = $parentController->updateParentStatus((int)$_GET['parent_id'], $_GET['status']);
    if ($updateResult['success']) {
        $parentCreateResult = '<div class="alert alert-success">' . $updateResult['message'] . '</div>';
    } else {
        $parentCreateResult = '<div class="alert alert-danger">' . implode('<br>', $updateResult['errors']) . '</div>';
    }
}
// Handle edit form display and update
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['parent_id'])) {
    $editParentData = $parentController->getParentById((int)$_GET['parent_id']);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'updateParent') {
    $updateResult = $parentController->updateParent($_POST);
    if ($updateResult['success']) {
        $parentCreateResult = '<div class="alert alert-success">' . $updateResult['message'] . '</div>';
    } else {
        $parentCreateResult = '<div class="alert alert-danger">' . implode('<br>', $updateResult['errors']) . '</div>';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'createParent') {
    $result = $parentController->createParent($_POST);
    if ($result['success']) {
        $parentCreateResult = '<div class="alert alert-success">' . $result['message'] . '</div>';
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $password = $result['generated_password'];
        $msg = urlencode(
            "Welcome to Hostel System!\nLogin: " . $_ENV['BASE_URL'] . "?page=login-parent\nEmail/Phone: " . ($email ?: $phone) . "\nTemporary Password: " . $password . "\nPlease change your password after login."
        );
        $waUrl = 'https://wa.me/' . preg_replace('/\D/', '', $phone) . '?text=' . $msg;
        $whatsappBtnHtml = '<a href="' . $waUrl . '" target="_blank" class="btn btn-success"><i class="bi bi-whatsapp"></i> Send via WhatsApp</a>';
        // Reset form fields
        $_POST = [];
    } else {
        $errorMsg = '';
        if (!empty($result['errors'])) {
            foreach ($result['errors'] as $err) $errorMsg .= $err . '<br>';
        } else {
            $errorMsg = 'Failed to create parent.';
        }
        $parentCreateResult = '<div class="alert alert-danger">' . $errorMsg . '</div>';
    }
}
// Handle search and pagination
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['p'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;
// Get parents data
$parents = $parentController->getAllParents($search, $limit, $offset);
$totalParents = $parentController->getTotalParentsCount($search);
$totalPages = ceil($totalParents / $limit);
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="bi bi-person-plus"></i> Add Parent</h2>
        <a href="<?php echo $_ENV['BASE_URL']; ?>?page=parents" class="btn btn-outline-success"><i class="bi bi-people"></i> Manage Parents</a>
    </div>
    <form id="addParentForm" class="card p-4 shadow-sm mb-4" method="POST" action="">
        <input type="hidden" name="action" value="<?php echo $editParentData ? 'updateParent' : 'createParent'; ?>">
        <?php if ($editParentData): ?>
            <input type="hidden" name="parent_id" value="<?php echo $editParentData['id']; ?>">
        <?php endif; ?>
        <div class="mb-3">
            <label for="parentName" class="form-label">Name</label>
            <input type="text" class="form-control" id="parentName" name="name" required value="<?php echo htmlspecialchars($editParentData['name'] ?? ($_POST['name'] ?? '')); ?>">
        </div>
        <div class="mb-3">
            <label for="parentEmail" class="form-label">Email</label>
            <input type="email" class="form-control" id="parentEmail" name="email" required value="<?php echo htmlspecialchars($editParentData['email'] ?? ($_POST['email'] ?? '')); ?>">
        </div>
        <div class="mb-3">
            <label for="parentPhone" class="form-label">Phone</label>
            <input type="text" class="form-control" id="parentPhone" name="phone" required value="<?php echo htmlspecialchars($editParentData['phone'] ?? ($_POST['phone'] ?? '')); ?>">
        </div>
        <input type="hidden" name="password" id="parentPassword" value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>">
        <button type="submit" class="btn btn-primary"><?php echo $editParentData ? 'Update Parent' : 'Create Parent'; ?></button>
        <?php if ($editParentData): ?>
            <a href="?page=add-parent" class="btn btn-secondary ms-2">Cancel</a>
        <?php endif; ?>
    </form>
    <div id="parentCreateResult" class="mt-3"><?php echo $parentCreateResult; ?></div>
    <div id="whatsappBtnContainer" class="mt-2"><?php echo $whatsappBtnHtml; ?></div>
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-success">All Parents (<?php echo $totalParents; ?> total)</h6>
            <div class="text-muted small">
                Showing <?php echo min($offset + 1, $totalParents); ?>-<?php echo min($offset + $limit, $totalParents); ?> of <?php echo $totalParents; ?> entries
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="mb-3">
                <input type="hidden" name="page" value="add-parent">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Search by name, email, phone, or student name..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-success" type="submit"><i class="bi bi-search"></i> Search</button>
                    <?php if ($search): ?>
                        <a href="<?php echo $_ENV['BASE_URL']; ?>?page=add-parent" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Clear</a>
                    <?php endif; ?>
                </div>
            </form>
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
                                            <button type="button" class="btn btn-outline-warning" onclick="editParent(<?php echo $parent['id']; ?>)" title="Edit"><i class="bi bi-pencil"></i></button>
                                            <button type="button" class="btn btn-outline-<?php echo $parent['status'] === 'active' ? 'danger' : 'success'; ?>" onclick="toggleStatus(<?php echo $parent['id']; ?>, '<?php echo $parent['status'] === 'active' ? 'disabled' : 'active'; ?>')" title="<?php echo $parent['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>"><i class="bi bi-<?php echo $parent['status'] === 'active' ? 'x-circle' : 'check-circle'; ?>"></i></button>
                                            <button type="button" class="btn btn-outline-primary" onclick="assignStudent(<?php echo $parent['id']; ?>)" title="Assign Student"><i class="bi bi-person-plus"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=add-parent&search=<?php echo urlencode($search); ?>&p=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
function generatePassword(length = 8) {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    let pass = '';
    for (let i = 0; i < length; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
    return pass;
}
document.addEventListener('DOMContentLoaded', function() {
    var passwordInput = document.getElementById('parentPassword');
    if (passwordInput && !passwordInput.value) {
        passwordInput.value = generatePassword();
    }
});
function editParent(id) {
    window.location.href = '?page=add-parent&action=edit&parent_id=' + id;
}
function toggleStatus(id, status) {
    if (confirm('Are you sure you want to ' + (status === 'active' ? 'activate' : 'deactivate') + ' this parent?')) {
        window.location.href = '?page=add-parent&action=update_status&parent_id=' + id + '&status=' + status;
    }
}
function assignStudent(id) {
    alert('Assign Student to Parent: ' + id);
    // Implement modal or redirect for assigning students
}
</script>
<?php include __DIR__ . '/../layouts/footer.php'; ?>