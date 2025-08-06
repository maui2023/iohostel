<?php
// Prevent direct access
if (!defined('HOSTEL_APP')) {
    exit('Direct access not permitted');
}

// Check if already authenticated
if (isAuthenticated() && hasRole('guard')) {
    header('Location: ' . $_ENV['BASE_URL'] . '?page=dashboard');
    exit;
}

// Handle login form submission
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailOrPhone = trim($_POST['email_or_phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    // Validate CSRF token
    if (!verifyCSRFToken($csrfToken)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (empty($emailOrPhone) || empty($password)) {
        $error = 'Please enter both email/phone and password.';
    } else {
        // Authenticate user with database
        $userData = authenticateUser($emailOrPhone, $password);
        
        if ($userData) {
            // Login successful
            if (loginUser($userData) && hasRole('guard')) {
                $success = 'Login successful! Redirecting...';
                header('refresh:2;url=' . $_ENV['BASE_URL'] . '?page=dashboard');
            } else {
                $error = 'Login failed. Please try again.';
            }
        } else {
            $error = 'Invalid credentials or insufficient privileges.';
        }
    }
}

// Generate CSRF token
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guard Login - <?php echo $_ENV['HOSTEL_NAME'] ?? 'Hostel Management System'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #0dcaf0;
            --light-bg: rgba(255, 255, 255, 0.95);
            --dark-bg: rgba(0, 0, 0, 0.8);
            --border-radius: 15px;
            --shadow-light: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-heavy: 0 10px 25px rgba(0, 0, 0, 0.2);
            --transition: all 0.3s ease;
            --guard-gradient: linear-gradient(135deg, #fd7e14 0%, #e63946 100%);
        }
        
        body {
            background: linear-gradient(135deg, #fd7e14 0%, #e63946 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .login-card {
            background: var(--light-bg);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-heavy);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            width: 100%;
            max-width: 480px;
        }
        
        .login-header {
            background: linear-gradient(135deg, #fd7e14 0%, #e63946 100%);
            color: white;
            padding: 2.5rem 2rem 2rem;
            text-align: center;
            position: relative;
        }
        
        .login-header::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 20px;
            background: var(--light-bg);
            border-radius: 20px 20px 0 0;
        }
        
        .login-icon {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.8rem;
            backdrop-filter: blur(10px);
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-floating {
            margin-bottom: 1.25rem;
        }
        
        .form-control {
            border-radius: 12px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: var(--transition);
            background: rgba(255, 255, 255, 0.9);
        }
        
        .form-control:focus {
            border-color: #fd7e14;
            box-shadow: 0 0 0 0.2rem rgba(253, 126, 20, 0.25);
            background: white;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #fd7e14 0%, #e63946 100%);
            border: none;
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            font-size: 1.1rem;
            transition: var(--transition);
            width: 100%;
            margin-top: 1rem;
            color: white;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-light);
            background: linear-gradient(135deg, #e63946 0%, #fd7e14 100%);
            color: white;
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .alert-success {
            background: rgba(25, 135, 84, 0.1);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }
        
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .back-link a {
            color: #fd7e14;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .back-link a:hover {
            color: #e63946;
            transform: translateX(-3px);
        }
        
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }
        
        @media (max-width: 576px) {
            .login-wrapper {
                padding: 0.5rem;
            }
            
            .login-header {
                padding: 2rem 1.5rem 1.5rem;
            }
            
            .login-body {
                padding: 1.5rem;
            }
            
            .login-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
        }
        
        .input-group-text {
            background: transparent;
            border: none;
            color: #6c757d;
        }
        
        .password-toggle {
            cursor: pointer;
            transition: var(--transition);
        }
        
        .password-toggle:hover {
            color: #fd7e14;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h2 class="fw-bold mb-2">Security Guard Portal</h2>
                <p class="mb-0 opacity-90"><?php echo $_ENV['HOSTEL_NAME'] ?? 'Hostel Management System'; ?></p>
            </div>
            
            <div class="login-body">
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="loginForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    
                    <div class="form-floating">
                        <input type="text" class="form-control" id="email_or_phone" name="email_or_phone" 
                               placeholder="Email or Phone" required autocomplete="username">
                        <label for="email_or_phone">
                            <i class="bi bi-envelope me-2"></i>Email or Phone
                        </label>
                        <div class="invalid-feedback">
                            Please enter your email or phone number.
                        </div>
                    </div>
                    
                    <div class="form-floating">
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Password" required autocomplete="current-password">
                        <label for="password">
                            <i class="bi bi-lock me-2"></i>Password
                        </label>
                        <div class="invalid-feedback">
                            Please enter your password.
                        </div>
                        <div class="position-absolute top-50 end-0 translate-middle-y me-3">
                            <i class="bi bi-eye password-toggle" onclick="togglePassword()" id="toggleIcon"></i>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-login" id="loginBtn">
                        <span id="loginBtnText">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                        </span>
                    </button>
                </form>
                
                <div class="back-link">
                    <a href="<?php echo $_ENV['BASE_URL'] ?? '/'; ?>?page=login">
                        <i class="bi bi-arrow-left"></i>
                        Back to Role Selection
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Password toggle functionality
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'bi bi-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'bi bi-eye';
            }
        }
        
        // Form validation and submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const emailOrPhone = document.getElementById('email_or_phone');
            const password = document.getElementById('password');
            const loginBtn = document.getElementById('loginBtn');
            const loginBtnText = document.getElementById('loginBtnText');
            
            // Reset validation states
            emailOrPhone.classList.remove('is-invalid');
            password.classList.remove('is-invalid');
            
            let isValid = true;
            
            // Validate email or phone
            if (!emailOrPhone.value.trim()) {
                emailOrPhone.classList.add('is-invalid');
                isValid = false;
            }
            
            // Validate password
            if (!password.value.trim()) {
                password.classList.add('is-invalid');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            loginBtn.disabled = true;
            loginBtnText.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Signing In...';
        });
        
        // Auto-focus on first input
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email_or_phone').focus();
        });
        
        // Real-time validation feedback
        document.getElementById('email_or_phone').addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
            }
        });
        
        document.getElementById('password').addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
            }
        });
    </script>
</body>
</html>