<?php
// Prevent direct access
if (!defined('HOSTEL_APP')) {
    exit('Direct access not permitted');
}

require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../config/database.php';

if (!isAuthenticated()) {
    header('Location: ' . $_ENV['BASE_URL'] . '?page=login');
    exit;
}

$userId = getCurrentUserId();
$userRole = getCurrentUserRole();

// Fetch user info
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare('SELECT u.*, p.picture, p.date_of_birth FROM users u LEFT JOIN profiles p ON u.id = p.user_id WHERE u.id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// For students, allow editing only by admin
$isStudent = ($user['role'] === 'student');
$canEdit = !$isStudent || ($isStudent && in_array($userRole, ['admin', 'superadmin']));

// Handle profile update
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $dateOfBirth = $_POST['date_of_birth'] ?? null;
    $picturePath = $user['picture'];

    // Handle picture upload
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION);
        $uploadDir = __DIR__ . '/../../public/assets/img/profiles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $filename = uniqid('profile_') . '.' . $ext;
        $target = '/assets/img/profiles/' . $filename;
        $fullPath = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['picture']['tmp_name'], $fullPath)) {
            $picturePath = $target;
        }
    }

    // Update users table
    $stmt = $db->prepare('UPDATE users SET name=?, email=?, phone=? WHERE id=?');
    $stmt->execute([$name, $email, $phone, $userId]);

    // Update or insert into profiles table
    $stmt = $db->prepare('SELECT id FROM profiles WHERE user_id=?');
    $stmt->execute([$userId]);
    if ($stmt->fetch()) {
        $stmt = $db->prepare('UPDATE profiles SET picture=?, date_of_birth=? WHERE user_id=?');
        $stmt->execute([$picturePath, $dateOfBirth, $userId]);
    } else {
        $stmt = $db->prepare('INSERT INTO profiles (user_id, picture, date_of_birth) VALUES (?, ?, ?)');
        $stmt->execute([$userId, $picturePath, $dateOfBirth]);
    }
    $message = 'Profile updated successfully!';
    // Refresh user info
    header('Location: ' . $_ENV['BASE_URL'] . '?page=profile&success=1');
    exit;
}

include __DIR__ . '/../layouts/header.php';
?>
<div class="container py-4">
    <h2 class="mb-4">Profile</h2>
    <?php if ($message || isset($_GET['success'])): ?>
        <div class="alert alert-success">Profile updated successfully!</div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <div class="row mb-3">
            <div class="col-md-3 text-center">
                <img src="<?= $user['picture'] ? $_ENV['BASE_URL'] . $user['picture'] : $_ENV['BASE_URL'] . '/assets/img/default-avatar.png' ?>" class="rounded-circle mb-2" width="120" height="120" alt="Profile Picture">
                <?php if ($canEdit): ?>
                    <input type="file" name="picture" class="form-control mt-2">
                <?php endif; ?>
            </div>
            <div class="col-md-9">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" <?= $canEdit ? '' : 'readonly' ?>>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" <?= $canEdit ? '' : 'readonly' ?>>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>" <?= $canEdit ? '' : 'readonly' ?>>
                </div>
                <div class="mb-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control" value="<?= htmlspecialchars($user['date_of_birth']) ?>" <?= $canEdit ? '' : 'readonly' ?>>
                </div>
                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <input type="text" class="form-control" value="<?= ucfirst($user['role']) ?>" readonly>
                </div>
                <?php if ($canEdit): ?>
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../layouts/footer.php'; ?>