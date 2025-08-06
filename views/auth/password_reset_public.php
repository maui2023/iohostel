<?php
// Prevent direct access
if (!defined('HOSTEL_APP')) {
    exit('Direct access not permitted');
}

require_once __DIR__ . '/../../controllers/SettingsController.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if ($email && $phone) {
        $settingsController = new SettingsController();
        // Find user by email and phone
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND phone = ?");
        $stmt->execute([$email, $phone]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $result = $settingsController->submitResetRequest($user['id'], $phone);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'danger';
        } else {
            $message = 'No user found with that email and phone number.';
            $messageType = 'danger';
        }
    } else {
        $message = 'Please enter both email and phone number.';
        $messageType = 'danger';
    }
}
$pageTitle = 'Request Password Reset';
$hostelName = $_ENV['HOSTEL_NAME'] ?? 'Hostel Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars($hostelName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= $_ENV['BASE_URL'] ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="login-card mt-5">
                        <div class="text-center mb-4">
                            <div class="login-logo mb-3">
                                <i class="bi bi-key display-4 text-warning"></i>
                            </div>
                            <h2 class="fw-bold text-dark">Request Password Reset</h2>
                            <p class="text-muted">Enter your registered email and phone number to request a password reset.</p>
                        </div>
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        <form method="POST" class="mt-3">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required placeholder="Enter your email">
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required placeholder="Enter your phone number">
                            </div>
                            <button type="submit" class="btn btn-warning w-100">Request Password Reset</button>
                        </form>
                        <div class="text-center mt-3">
                            <a href="<?= $_ENV['BASE_URL'] ?>?page=login" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Back to Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>