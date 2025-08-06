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

require_once __DIR__ . '/../../controllers/ClassController.php';
$classController = new ClassController();
$classCreateResult = '';
$classEditResult = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'createClass') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    if ($name !== '') {
        $result = $classController->createClass($name, $description);
        if ($result) {
            $classCreateResult = '<div class="alert alert-success">Class created successfully!</div>';
        } else {
            $classCreateResult = '<div class="alert alert-danger">Failed to create class.</div>';
        }
    } else {
        $classCreateResult = '<div class="alert alert-danger">Class name is required.</div>';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'editClass') {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    if ($id !== '' && $name !== '') {
        try {
            $result = $classController->updateClass($id, $name, $description);
            if ($result) {
                $classEditResult = '<div class="alert alert-success">Class updated successfully!</div>';
            } else {
                $classEditResult = '<div class="alert alert-danger">Failed to update class.</div>';
            }
        } catch (Exception $e) {
            $classEditResult = '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    } else {
        $classEditResult = '<div class="alert alert-danger">Class name is required.</div>';
    }
}
$classes = $classController->getAllClasses();
$pageTitle = 'Manage Classes';
include __DIR__ . '/../layouts/header.php';
?>
<div class="content-wrapper">
    <!-- Sidebar -->
    <?php include_once __DIR__ . '/../layouts/sidebar.php'; ?>
    <!-- Main Content -->
    <div class="main-content with-sidebar">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2><i class="bi bi-card-list"></i> Manage Classes</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">
                    <i class="bi bi-plus-square"></i> Add Class
                </button>
            </div>
            <?php echo $classCreateResult; ?>
            <div class="card shadow">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-primary">
                                <tr>
                                    <th>#</th>
                                    <th>Class Name</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($classes)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No classes found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($classes as $index => $class): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($class['name']); ?></td>
                                            <td><?php echo htmlspecialchars($class['description']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editClassModal<?php echo $class['id']; ?>">Edit</button>
                                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                                            </td>
                                        </tr>
                                        <!-- Edit Class Modal -->
                                        <div class="modal fade" id="editClassModal<?php echo $class['id']; ?>" tabindex="-1" aria-labelledby="editClassModalLabel<?php echo $class['id']; ?>" aria-hidden="true">
                                          <div class="modal-dialog">
                                            <div class="modal-content">
                                              <div class="modal-header">
                                                <h5 class="modal-title" id="editClassModalLabel<?php echo $class['id']; ?>">Edit Class</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                              </div>
                                              <div class="modal-body">
                                                <form action="" method="POST">
                                                    <input type="hidden" name="action" value="editClass">
                                                    <input type="hidden" name="id" value="<?php echo $class['id']; ?>">
                                                    <div class="mb-3">
                                                        <label for="editClassName<?php echo $class['id']; ?>" class="form-label">Class Name</label>
                                                        <input type="text" class="form-control" id="editClassName<?php echo $class['id']; ?>" name="name" value="<?php echo htmlspecialchars($class['name']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="editClassDescription<?php echo $class['id']; ?>" class="form-label">Description</label>
                                                        <textarea class="form-control" id="editClassDescription<?php echo $class['id']; ?>" name="description" rows="3"><?php echo htmlspecialchars($class['description']); ?></textarea>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                                    </div>
                                                </form>
                                              </div>
                                            </div>
                                          </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Add Class Modal -->
<div class="modal fade" id="addClassModal" tabindex="-1" aria-labelledby="addClassModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addClassModalLabel">Add New Class</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="addClassForm" action="" method="POST">
            <input type="hidden" name="action" value="createClass">
            <div class="mb-3">
                <label for="className" class="form-label">Class Name</label>
                <input type="text" class="form-control" id="className" name="name" required>
            </div>
            <div class="mb-3">
                <label for="classDescription" class="form-label">Description</label>
                <textarea class="form-control" id="classDescription" name="description" rows="3"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Save Class</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
<?php echo $classEditResult; ?>