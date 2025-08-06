<?php
// Prevent direct access
if (!defined('HOSTEL_APP')) {
    exit('Direct access not permitted');
}

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'superadmin'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '?page=login');
    exit();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../controllers/StudentController.php';
require_once __DIR__ . '/../../controllers/ParentController.php';
require_once __DIR__ . '/../../controllers/ClassController.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

$conn = Database::getInstance()->getConnection();

$studentController = new StudentController($conn);
$parentController = new ParentController($conn);
$classController = new ClassController($conn);

$parents = $parentController->getAllParents('', 1000, 0);
$classes = $classController->getAllClasses();

$studentCreateResult = '';
$errorMessages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'createStudent') {
    file_put_contents(__DIR__ . '/upload_debug.log', 'POST received at ' . date('c') . "\n" . print_r($_FILES, true) . "\n", FILE_APPEND);
    if (function_exists('fflush')) { fflush(fopen('php://stderr', 'w')); }
    $data = [
        'name' => $_POST['name'] ?? '',
        'student_no' => $_POST['student_no'] ?? '',
        'class_id' => isset($_POST['class_id']) && $_POST['class_id'] !== '' ? intval($_POST['class_id']) : null,
        'gender' => $_POST['gender'] ?? '',
        'religion' => $_POST['religion'] ?? '',
        'race' => $_POST['race'] ?? '',
        'parent_id' => isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? intval($_POST['parent_id']) : null
    ];
    $pictureFile = isset($_FILES['picture']) ? ['picture' => $_FILES['picture']] : null;

    // Basic validation
    if ($data['name'] === '' || $data['student_no'] === '' || $data['class_id'] === null || $data['parent_id'] === null) {
        $errorMessages[] = 'All required fields must be filled.';
    }

    if (empty($errorMessages)) {
        $result = $studentController->createStudent($data, $pictureFile);
        if ($result['success']) {
            $studentCreateResult = '<div class="alert alert-success">Student created successfully!</div>';
        } else {
            if (isset($result['errors'])) {
                foreach ($result['errors'] as $field => $message) {
                    $errorMessages[] = $message;
                }
            } else {
                $errorMessages[] = 'Failed to create student due to an unknown error.';
            }
        }
    }
}
$students = $studentController->getStudents('', 1000, 0); // Load students on page load and after creation
$pageTitle = 'Add Student';
include __DIR__ . '/../layouts/header.php';
?>

<div class="content-wrapper">
    <?php include_once __DIR__ . '/../layouts/sidebar.php'; ?>
    <div class="main-content with-sidebar">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2><i class="bi bi-person-plus"></i> Add Student</h2>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addStudentModal"><i class="bi bi-plus-circle"></i> Add Student</button>
            </div>

            <?php if (!empty($errorMessages)): ?>
                <div class="card border-danger mb-3">
                    <div class="card-body text-danger">
                        <?php foreach ($errorMessages as $msg): ?>
                            <div><?php echo htmlspecialchars($msg); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php echo $studentCreateResult; ?>

            <!-- Modal Popup Form -->
            <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-lg">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="addStudentModalLabel">Add Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <form id="addStudentForm" action="<?php echo $_ENV['BASE_URL']; ?>?page=add-student" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="createStudent">
                        <div class="mb-3">
                            <label for="studentName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="studentName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="studentNo" class="form-label">Student No</label>
                            <input type="text" class="form-control" id="studentNo" name="student_no" required>
                        </div>
                        <div class="mb-3">
                            <label for="studentPicture" class="form-label">Picture</label>
                            <input type="file" class="form-control" id="studentPicture" name="picture" accept="image/*" capture="environment" required>
                        </div>
                        <div class="mb-3">
                            <label for="studentClass" class="form-label">Class</label>
                            <select class="form-select" id="studentClass" name="class_id" required>
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="studentGender" class="form-label">Gender</label>
                            <select class="form-select" id="studentGender" name="gender">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="studentReligion" class="form-label">Religion</label>
                            <input type="text" class="form-control" id="studentReligion" name="religion">
                        </div>
                        <div class="mb-3">
                            <label for="studentRace" class="form-label">Race</label>
                            <input type="text" class="form-control" id="studentRace" name="race">
                        </div>
                        <div class="mb-3">
                            <label for="studentParent" class="form-label">Assign to Parent</label>
                            <select class="form-select" id="studentParent" name="parent_id" required>
                                <option value="">Select Parent</option>
                                <?php foreach ($parents as $parent): ?>
                                    <option value="<?php echo $parent['id']; ?>"><?php echo htmlspecialchars($parent['name'] . ' (' . $parent['email'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn btn-primary">Save Student</button>
                        </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>

            <h3 class="mt-4">Existing Students</h3>
            <div class="card shadow mt-3">
                <div class="card-body">
                    <!-- Search and Filter -->
                    <form method="GET" class="row g-3 mb-3 align-items-end">
                        <input type="hidden" name="page" value="add-student">
                        <div class="col-md-3">
                            <label class="form-label">Name / Student No</label>
                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Search name or student no">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Class</label>
                            <select class="form-select" name="class_id">
                                <option value="">All</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>" <?php if (isset($_GET['class_id']) && $_GET['class_id'] == $class['id']) echo 'selected'; ?>><?php echo htmlspecialchars($class['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender">
                                <option value="">All</option>
                                <option value="Male" <?php if (isset($_GET['gender']) && $_GET['gender'] == 'Male') echo 'selected'; ?>>Male</option>
                                <option value="Female" <?php if (isset($_GET['gender']) && $_GET['gender'] == 'Female') echo 'selected'; ?>>Female</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Search</button>
                        </div>
                    </form>
                    <?php
                    // Pagination and Filtering Logic
                    $search = $_GET['search'] ?? '';
                    $filterClass = $_GET['class_id'] ?? '';
                    $filterGender = $_GET['gender'] ?? '';
                    $page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
                    $perPage = 10;
                    $offset = ($page - 1) * $perPage;
                    $students = $studentController->getStudents($search, $perPage, $offset, $filterClass, $filterGender, 'DESC');
                    $totalStudents = $studentController->countStudents($search, $filterClass, $filterGender);
                    $totalPages = ceil($totalStudents / $perPage);
                    ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-primary">
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Student No</th>
                                    <th>Parent</th>
                                    <th>Picture</th>
                                    <th>QR</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($students)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No students found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($students as $index => $student): ?>
                                        <tr>
                                            <td><?php echo $offset + $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['student_no']); ?></td>
                                            <td><?php echo htmlspecialchars($student['parent_name']); ?></td>
                                            <td><?php if (!empty($student['picture'])): ?><img src="<?php echo $_ENV['BASE_URL'] . htmlspecialchars($student['picture']); ?>" alt="Student Picture" width="40" height="40" style="object-fit:cover;border-radius:50%;"><?php endif; ?></td>
                                            <td><?php if (!empty($student['qr_token'])): 
                                                $qrResult = Builder::create()
                                                    ->writer(new PngWriter())
                                                    ->data($student['qr_token'])
                                                    ->encoding(new Encoding('UTF-8'))
                                                    ->errorCorrectionLevel(ErrorCorrectionLevel::High)
                                                    ->size(120)
                                                    ->margin(2)
                                                    ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
                                                    ->build();
                                                $qrBase64 = base64_encode($qrResult->getString());
                                                echo '<img src="data:image/png;base64,' . $qrBase64 . '" alt="QR Code" width="40" height="40">';
                                            endif; ?></td>
                                            <td><?php if ($student['status'] === 'inactive'): ?><span class="badge bg-secondary">Disabled</span><?php else: ?><span class="badge bg-success">Active</span><?php endif; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editStudentModal<?php echo $student['id']; ?>"><i class="bi bi-pencil"></i> Edit</button>
                                                <?php if ($student['status'] === 'inactive'): ?>
                                                    <button class="btn btn-sm btn-secondary" disabled>Disabled</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <!-- Edit Modal for each student -->
                                        <div class="modal fade" id="editStudentModal<?php echo $student['id']; ?>" tabindex="-1" aria-labelledby="editStudentModalLabel<?php echo $student['id']; ?>" aria-hidden="true">
                                          <div class="modal-dialog">
                                            <div class="modal-content">
                                              <div class="modal-header">
                                                <h5 class="modal-title" id="editStudentModalLabel<?php echo $student['id']; ?>">Edit Student</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                              </div>
                                              <form method="POST" action="<?php echo $_ENV['BASE_URL']; ?>?page=add-student" enctype="multipart/form-data">
                                                <input type="hidden" name="action" value="editStudent">
                                                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                <div class="modal-body">
                                                  <div class="mb-3">
                                                    <center>
                                                    <?php if (!empty($student['picture'])): ?>
                                                      <img src="<?php echo $_ENV['BASE_URL'] . htmlspecialchars($student['picture']); ?>" alt="Current Picture" width="160" height="160" style="object-fit:cover;border-radius:50%;margin-bottom:8px;">
                                                    <?php endif; ?>
                                                    </center>
                                                    <input type="file" class="form-control mt-2" name="picture" accept="image/*">
                                                    <small class="text-muted">Leave blank to keep current picture.</small>
                                                  </div>
                                                  <div class="mb-3">
                                                    <label class="form-label">Name</label>
                                                    <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($student['name']); ?>" required>
                                                  </div>
                                                  <div class="mb-3">
                                                    <label class="form-label">Student No</label>
                                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['student_no']); ?>" disabled>
                                                  </div>
                                                  <div class="mb-3">
                                                    <label class="form-label">Gender</label>
                                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['gender']); ?>" disabled>
                                                  </div>
                                                  <div class="mb-3">
                                                    <label class="form-label">Parent</label>
                                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['parent_name']); ?>" disabled>
                                                  </div>
                                                  <div class="mb-3">
                                                    <label class="form-label">Class</label>
                                                    <select class="form-select" name="class_id" required>
                                                      <option value="">Select Class</option>
                                                      <?php foreach ($classes as $class): ?>
                                                        <option value="<?php echo $class['id']; ?>" <?php if ($student['class_id'] == $class['id']) echo 'selected'; ?>><?php echo htmlspecialchars($class['name']); ?></option>
                                                      <?php endforeach; ?>
                                                    </select>
                                                  </div>
                                                  <div class="mb-3">
                                                    <label class="form-label">Status</label>
                                                    <select class="form-select" name="status">
                                                      <option value="active" <?php if ($student['status'] === 'active') echo 'selected'; ?>>Active</option>
                                                      <option value="inactive" <?php if ($student['status'] === 'inactive') echo 'selected'; ?>>Disabled</option>
                                                    </select>
                                                  </div>
                                                </div>
                                                <div class="modal-footer">
                                                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                  <button type="submit" class="btn btn-primary">Save Changes</button>
                                                </div>
                                              </form>
                                            </div>
                                          </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <nav>
                      <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                          <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                            <a class="page-link" href="?page=add-student&search=<?php echo urlencode($search); ?>&class_id=<?php echo urlencode($filterClass); ?>&gender=<?php echo urlencode($filterGender); ?>&p=<?php echo $i; ?>"><?php echo $i; ?></a>
                          </li>
                        <?php endfor; ?>
                      </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Clear form after successful submission
    <?php if ($studentCreateResult): ?>
    document.getElementById('addStudentForm').reset();
    <?php endif; ?>
});
</script>
