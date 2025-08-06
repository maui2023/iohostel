<?php

// Authentication and session management utilities

/**
 * Check if user is authenticated
 * @return bool
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_roles']) && is_array($_SESSION['user_roles']);
}

/**
 * Check if user has specific role
 * @param string $role Required role
 * @return bool
 */
function hasRole($requiredRole) {
    if (!isAuthenticated()) {
        return false;
    }
    
    $userRoles = $_SESSION['user_roles'];
    
    // Check if the user has the required role directly
    if (in_array($requiredRole, $userRoles)) {
        return true;
    }

    // Role hierarchy: superadmin can access admin functions, admin can access guard functions
    // This logic assumes a hierarchical access where higher roles implicitly grant lower role permissions.
    // If a user has 'superadmin', they can access 'admin' and 'guard' functions.
    // If a user has 'admin', they can access 'guard' functions.
    if (in_array('superadmin', $userRoles)) {
        return true; // Superadmin has access to everything
    }
    if (in_array('admin', $userRoles) && in_array($requiredRole, ['admin', 'guard'])) {
        return true; // Admin has access to admin and guard functions
    }
    if (in_array('guard', $userRoles) && $requiredRole === 'guard') {
        return true; // Guard has access to guard functions
    }

    return false;
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 * @return string|null
 */
function getCurrentUserRoles() {
    return $_SESSION['user_roles'] ?? [];
}

// For backward compatibility, if a single role is still expected in some places
function getCurrentUserRole() {
    $roles = getCurrentUserRoles();
    return !empty($roles) ? $roles[0] : null;
}

/**
 * Get current user name
 * @return string|null
 */
function getCurrentUserName() {
    return $_SESSION['user_name'] ?? null;
}

/**
 * Login user and create session
 * @param array $user User data from database
 * @return bool
 */
function loginUser($user) {
    if (!$user || !isset($user['id'])) {
        return false;
    }
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Set session data
    $_SESSION['user_id'] = $user['id'];
    // Fetch all roles for the user from the database
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT role FROM user_roles WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $_SESSION['user_roles'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // If no roles are found, assign a default or handle error
    if (empty($_SESSION['user_roles'])) {
        error_log("User ID " . $user['id'] . " has no roles assigned.");
        // Optionally, assign a 'default' role or redirect to an error page
        // For now, we'll ensure it's an empty array if no roles are found
        $_SESSION['user_roles'] = [];
    }
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_phone'] = $user['phone'] ?? null;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Logout user and destroy session
 */
function logoutUser() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Clear all session variables
    $_SESSION = [];
    
    // Get session cookie parameters
    $params = session_get_cookie_params();
    
    // Destroy the session cookie by setting it to expire in the past
    setcookie(
        session_name(),
        '',
        time() - 3600,
        $params['path'] ?: '/',
        $params['domain'] ?: '',
        $params['secure'],
        $params['httponly']
    );
    
    // Also try to unset the cookie in the current request
    if (isset($_COOKIE[session_name()])) {
        unset($_COOKIE[session_name()]);
    }
    
    // Destroy the session
    session_destroy();
    
    // Regenerate session ID to ensure clean state
    session_start();
    session_regenerate_id(true);
    session_destroy();
}

/**
 * Verify password against hash
 * @param string $password Plain text password
 * @param string $hash Hashed password from database
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Hash password for storage
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Generate secure random token
 * @param int $length Token length
 * @return string Random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Check if session has expired (optional security feature)
 * @param int $maxLifetime Maximum session lifetime in seconds (default: 2 hours)
 * @return bool
 */
function isSessionExpired($maxLifetime = 7200) {
    if (!isset($_SESSION['login_time'])) {
        return true;
    }
    
    return (time() - $_SESSION['login_time']) > $maxLifetime;
}

/**
 * Require authentication for a page
 * @param string|null $requiredRole Required role (optional)
 */
function requireAuth($requiredRole = null) {
    if (!isAuthenticated() || isSessionExpired()) {
        logoutUser();
        header('Location: /login');
        exit;
    }
    
    if ($requiredRole && !hasRole($requiredRole)) {
        header('Location: /login');
        exit;
    }
}

/**
 * Redirect user to appropriate dashboard based on role
 */
function redirectToDashboard() {
    $userRoles = (array)getCurrentUserRoles();
    
    // Prioritize redirection based on role hierarchy or common access patterns
    if (in_array('superadmin', $userRoles)) {
        header('Location: ?page=superadmin-dashboard');
    } elseif (in_array('admin', $userRoles)) {
        header('Location: ?page=dashboard');
    } elseif (in_array('guard', $userRoles)) {
        header('Location: ?page=guard-dashboard');
    } elseif (in_array('parent', $userRoles)) {
        header('Location: ?page=parent-dashboard');
    } else {
        header('Location: ?page=login');
    }
    exit;
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken(16);
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token Token to verify
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Authenticate user with email/phone and password
 * @param string $emailOrPhone Email or phone number
 * @param string $password Plain text password
 * @return array|false User data on success, false on failure
 */
function authenticateUser($emailOrPhone, $password) {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Prepare query to find user by email or phone
        $sql = "SELECT id, name, email, phone, password, status FROM users 
                WHERE (email = ? OR phone = ?) 
                AND status = 'active' 
                LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$emailOrPhone, $emailOrPhone]);
        
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Remove password from returned data
            unset($user['password']);
            return $user;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Authentication error: " . $e->getMessage());
        return false;
    }
}