<?php

// Central routing system for Hostel Check-In/Out System

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

// Get the requested page from query parameter
$page = $_GET['page'] ?? '';
$requestMethod = $_SERVER['REQUEST_METHOD'];

// If no page specified and user is authenticated, redirect to appropriate dashboard
if (empty($page) && isAuthenticated()) {
    redirectToDashboard();
    exit;
}

// Route definitions based on page parameter
$routes = [
    // Authentication routes
    '' => ['view' => 'auth/login_selector'],
    'login' => ['view' => 'auth/login_selector'],
    'password-reset' => ['view' => 'auth/password_reset_public'],
    'login-superadmin' => ['view' => 'auth/login_superadmin'],
    'login-admin' => ['view' => 'auth/login_admin'],
    'login-guard' => ['view' => 'auth/login_guard'],
    'login-parent' => ['view' => 'auth/login_parent'],
    'logout' => ['action' => 'logout'],
    
    // Dashboard routes
    'dashboard' => ['view' => 'dashboard/dashboard', 'auth' => true],
    'superadmin-dashboard' => ['view' => 'dashboard/superadmin', 'auth' => 'superadmin'],
    
    // Test route for layout
    'test' => ['view' => 'dashboard/test'],
    'test_upload' => ['view' => 'dashboard/test_upload'],
    
    // Student management routes
    'students' => ['view' => 'dashboard/students', 'auth' => ['admin', 'superadmin']],
    'add-student' => ['view' => 'dashboard/add-student', 'auth' => ['admin', 'superadmin']],
    
    'classes' => ['view' => 'dashboard/classes', 'auth' => ['admin', 'superadmin']],
    'add-class' => ['view' => 'dashboard/add-class', 'auth' => ['admin', 'superadmin']],

    // Parent management routes
    'add-parent' => ['view' => 'dashboard/add-parent', 'auth' => ['admin', 'superadmin']],
    'parents' => ['view' => 'dashboard/parents', 'auth' => ['superadmin']],
    
    // Guard routes
    'scanner' => ['view' => 'scanner/qr', 'auth' => 'guard'],
    'manual-checkin' => ['view' => 'scanner/manual', 'auth' => 'guard'],
    
    // Logs and reports
    'logs' => ['view' => 'logs/list', 'auth' => true],
    'reports' => ['view' => 'reports/dashboard', 'auth' => ['admin', 'superadmin']],
    
    // Settings routes
    'settings' => ['view' => 'settings/index', 'auth' => true],
    'password-reset-requests' => ['view' => 'settings/reset_requests', 'auth' => ['admin', 'superadmin']],
    
    // Settings and profile
    'profile' => ['view' => 'profile/edit', 'auth' => true],
    'system-settings' => ['view' => 'settings/system', 'auth' => ['admin', 'superadmin']],

];

// Handle routing
function handleRoute($page, $routes) {
    global $superadminController; // Access the global controller instance

    // Handle POST requests for superadmin-dashboard
    if ($page === 'superadmin-dashboard' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create_user':
                    $result = $superadminController->createUser($_POST);
                    if ($result['success']) {
                        $_SESSION['success_message'] = $result['message'];
                    } else {
                        $_SESSION['error_message'] = implode('<br>', $result['errors']);
                    }
                    header('Location: ' . $_ENV['BASE_URL'] . '?page=superadmin-dashboard');
                    exit;
                case 'update_user_status':
                    $userId = $_POST['user_id'] ?? null;
                    $status = $_POST['status'] ?? null;
                    if ($userId && $status) {
                        $result = $superadminController->updateUserStatus($userId, $status);
                        if ($result['success']) {
                            $_SESSION['success_message'] = $result['message'];
                        } else {
                            $_SESSION['error_message'] = $result['message'];
                        }
                    }
                    header('Location: ' . $_ENV['BASE_URL'] . '?page=superadmin-dashboard');
                    exit;
                case 'delete_user':
                    $userId = $_POST['user_id'] ?? null;
                    if ($userId) {
                        $result = $superadminController->deleteUser($userId);
                        if ($result['success']) {
                            $_SESSION['success_message'] = $result['message'];
                        } else {
                            $_SESSION['error_message'] = $result['message'];
                        }
                    }
                    header('Location: ' . $_ENV['BASE_URL'] . '?page=superadmin-dashboard');
                    exit;
            }
        }
    }

    error_log("handleRoute invoked for page: " . $page . ", method: " . $_SERVER['REQUEST_METHOD']);
    
    if (isset($routes[$page])) {
        $currentRoute = $routes[$page];
        
        // Handle special actions
        if (isset($currentRoute['action'])) {
            switch ($currentRoute['action']) {
                case 'logout':
                    // Perform logout
                    logoutUser();
                    
                    // Clear any output buffers
                    if (ob_get_level()) {
                        ob_end_clean();
                    }
                    
                    // Redirect to login page
                    header('Location: ' . $_ENV['BASE_URL'] . '?page=login', true, 302);
                    exit();
                    break;
            }
        }
        
        // Check authentication if required
        if (isset($currentRoute['auth'])) {
            $authLog = 'isAuthenticated: ' . (isAuthenticated() ? 'true' : 'false');
            $role = getCurrentUserRole();
            $authLog .= ', role: ' . ($role ? $role : 'none');
            file_put_contents(__DIR__ . '/../views/dashboard/router_test.log', 'Auth check for page: ' . $page . ' - ' . $authLog . ' at ' . date('c') . "\n", FILE_APPEND);
            if ($currentRoute['auth'] === true) {
                // Any authenticated user
                if (!isAuthenticated()) {
                    header('Location: ' . $_ENV['BASE_URL'] . '?page=login');
                    exit;
                }
            } elseif (is_array($currentRoute['auth'])) {
                // Specific roles
                if (!isAuthenticated() || !in_array(getCurrentUserRole(), $currentRoute['auth'])) {
                    header('Location: ' . $_ENV['BASE_URL'] . '?page=login');
                    exit;
                }
            } elseif (is_string($currentRoute['auth'])) {
                // Single role
                if (!isAuthenticated() || !hasRole($currentRoute['auth'])) {
                    header('Location: ' . $_ENV['BASE_URL'] . '?page=login');
                    exit;
                }
            }
        }
        
        // Load view file
        $viewFile = __DIR__ . '/../views/' . $currentRoute['view'] . '.php';
        file_put_contents(__DIR__ . '/../views/dashboard/router_test.log', 'SESSION: ' . print_r($_SESSION, true) . ' at ' . date('c') . "\n", FILE_APPEND);
        file_put_contents(__DIR__ . '/../views/dashboard/router_test.log', 'Routing page: ' . $page . ', route: ' . print_r($currentRoute, true) . ' at ' . date('c') . "\n", FILE_APPEND);
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            show404();
        }
    } else {
        // Default behavior for empty page
        if (empty($page)) {
            if (isAuthenticated()) {
                redirectToDashboard();
            } else {
                include __DIR__ . '/../views/auth/login_selector.php';
            }
        } else {
            show404();
        }
    }
}

// 404 handler
function show404() {
    http_response_code(404);
    echo "<h1>404 - Page Not Found</h1>";
    echo "<p>The requested page could not be found.</p>";
    echo "<a href='/'>Go to Home</a>";
}

// Include controllers
require_once __DIR__ . '/../controllers/SuperadminController.php';

// Instantiate controllers
$superadminController = new SuperadminController();

// Execute routing
handleRoute($page, $routes);