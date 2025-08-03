<?php

class SuperadminController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new admin account
     */
    public function createAdmin($data) {
        try {
            // Validate input
            $errors = $this->validateUserData($data, 'admin');
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }
            
            // Check if email already exists
            if ($this->emailExists($data['email'])) {
                return ['success' => false, 'errors' => ['email' => 'Email already exists']];
            }
            
            // Check if phone already exists (if provided)
            if (!empty($data['phone']) && $this->phoneExists($data['phone'])) {
                return ['success' => false, 'errors' => ['phone' => 'Phone number already exists']];
            }
            
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Insert admin user
            $sql = "INSERT INTO users (name, email, phone, password, role, status, created_at) 
                    VALUES (?, ?, ?, ?, 'admin', 'active', NOW())";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['name'],
                $data['email'],
                $data['phone'] ?? null,
                $hashedPassword
            ]);
            
            if ($result) {
                $userId = $this->db->lastInsertId();
                return [
                    'success' => true, 
                    'message' => 'Admin account created successfully',
                    'user_id' => $userId
                ];
            } else {
                return ['success' => false, 'errors' => ['general' => 'Failed to create admin account']];
            }
            
        } catch (PDOException $e) {
            error_log("Error creating admin: " . $e->getMessage());
            return ['success' => false, 'errors' => ['general' => 'Database error occurred']];
        }
    }
    
    /**
     * Create a new guard account
     */
    public function createGuard($data) {
        try {
            // Validate input
            $errors = $this->validateUserData($data, 'guard');
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }
            
            // Check if email already exists
            if ($this->emailExists($data['email'])) {
                return ['success' => false, 'errors' => ['email' => 'Email already exists']];
            }
            
            // Check if phone already exists (if provided)
            if (!empty($data['phone']) && $this->phoneExists($data['phone'])) {
                return ['success' => false, 'errors' => ['phone' => 'Phone number already exists']];
            }
            
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Insert guard user
            $sql = "INSERT INTO users (name, email, phone, password, role, status, created_at) 
                    VALUES (?, ?, ?, ?, 'guard', 'active', NOW())";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['name'],
                $data['email'],
                $data['phone'] ?? null,
                $hashedPassword
            ]);
            
            if ($result) {
                $userId = $this->db->lastInsertId();
                return [
                    'success' => true, 
                    'message' => 'Guard account created successfully',
                    'user_id' => $userId
                ];
            } else {
                return ['success' => false, 'errors' => ['general' => 'Failed to create guard account']];
            }
            
        } catch (PDOException $e) {
            error_log("Error creating guard: " . $e->getMessage());
            return ['success' => false, 'errors' => ['general' => 'Database error occurred']];
        }
    }
    
    /**
     * Get all admin and guard users with pagination and search
     */
    public function getAllUsers($role = null, $search = '', $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            $params = [];
            
            // Base query
            $sql = "SELECT id, name, email, phone, role, status, created_at 
                    FROM users 
                    WHERE role IN ('admin', 'guard')";
            
            // Add role filter
            if ($role) {
                $sql .= " AND role = ?";
                $params[] = $role;
            }
            
            // Add search filter
            if (!empty($search)) {
                $sql .= " AND (name LIKE ? OR phone LIKE ?)";
                $searchTerm = '%' . $search . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Add ordering and pagination
            $sql .= " ORDER BY role, name LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching users: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total count of admin and guard users for pagination
     */
    public function getUsersCount($role = null, $search = '') {
        try {
            $params = [];
            
            $sql = "SELECT COUNT(*) as total 
                    FROM users 
                    WHERE role IN ('admin', 'guard')";
            
            if ($role) {
                $sql .= " AND role = ?";
                $params[] = $role;
            }
            
            if (!empty($search)) {
                $sql .= " AND (name LIKE ? OR phone LIKE ?)";
                $searchTerm = '%' . $search . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
            
        } catch (PDOException $e) {
            error_log("Error counting users: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Update user status (activate/deactivate)
     */
    public function updateUserStatus($userId, $status) {
        try {
            $validStatuses = ['active', 'inactive'];
            if (!in_array($status, $validStatuses)) {
                return ['success' => false, 'message' => 'Invalid status'];
            }
            
            $sql = "UPDATE users SET status = ? WHERE id = ? AND role IN ('admin', 'guard')";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$status, $userId]);
            
            if ($result && $stmt->rowCount() > 0) {
                $action = $status === 'active' ? 'activated' : 'deactivated';
                return ['success' => true, 'message' => "User {$action} successfully"];
            } else {
                return ['success' => false, 'message' => 'User not found or no changes made'];
            }
            
        } catch (PDOException $e) {
            error_log("Error updating user status: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($userId) {
        try {
            $sql = "SELECT id, name, email, phone, role, status, created_at 
                    FROM users 
                    WHERE id = ? AND role IN ('admin', 'guard')";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update user information
     */
    public function updateUser($userId, $data) {
        try {
            // Validate input
            $errors = $this->validateUserData($data, null, $userId);
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }
            
            // Check if email already exists (excluding current user)
            if ($this->emailExists($data['email'], $userId)) {
                return ['success' => false, 'errors' => ['email' => 'Email already exists']];
            }
            
            // Check if phone already exists (excluding current user)
            if (!empty($data['phone']) && $this->phoneExists($data['phone'], $userId)) {
                return ['success' => false, 'errors' => ['phone' => 'Phone number already exists']];
            }
            
            // Prepare update query
            $sql = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ? AND role IN ('admin', 'guard')";
            $params = [$data['name'], $data['email'], $data['phone'] ?? null, $userId];
            
            // Add password update if provided
            if (!empty($data['password'])) {
                $sql = "UPDATE users SET name = ?, email = ?, phone = ?, password = ? WHERE id = ? AND role IN ('admin', 'guard')";
                $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                $params = [$data['name'], $data['email'], $data['phone'] ?? null, $hashedPassword, $userId];
            }
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                return ['success' => true, 'message' => 'User updated successfully'];
            } else {
                return ['success' => false, 'errors' => ['general' => 'Failed to update user']];
            }
            
        } catch (PDOException $e) {
            error_log("Error updating user: " . $e->getMessage());
            return ['success' => false, 'errors' => ['general' => 'Database error occurred']];
        }
    }
    
    /**
     * Get system statistics
     */
    public function getSystemStats() {
        try {
            $stats = [];
            
            // Total admins
            $stmt = $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
            $stats['total_admins'] = $stmt->fetchColumn();
            
            // Active admins
            $stmt = $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'");
            $stats['active_admins'] = $stmt->fetchColumn();
            
            // Total guards
            $stmt = $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'guard'");
            $stats['total_guards'] = $stmt->fetchColumn();
            
            // Active guards
            $stmt = $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'guard' AND status = 'active'");
            $stats['active_guards'] = $stmt->fetchColumn();
            
            // Total students
            $stmt = $this->db->query("SELECT COUNT(*) FROM students");
            $stats['total_students'] = $stmt->fetchColumn();
            
            // Active students
            $stmt = $this->db->query("SELECT COUNT(*) FROM students WHERE status = 'active'");
            $stats['active_students'] = $stmt->fetchColumn();
            
            // Students currently inside (based on latest log entry)
            $stmt = $this->db->query("
                SELECT COUNT(*) FROM students s
                LEFT JOIN (
                    SELECT student_id, action
                    FROM inout_logs il1
                    WHERE timestamp = (
                        SELECT MAX(timestamp)
                        FROM inout_logs il2
                        WHERE il2.student_id = il1.student_id
                    )
                ) latest_log ON s.id = latest_log.student_id
                WHERE s.status = 'active' AND latest_log.action = 'in'
            ");
            $stats['students_inside'] = $stmt->fetchColumn();
            
            // Students currently outside (based on latest log entry)
            $stmt = $this->db->query("
                SELECT COUNT(*) FROM students s
                LEFT JOIN (
                    SELECT student_id, action
                    FROM inout_logs il1
                    WHERE timestamp = (
                        SELECT MAX(timestamp)
                        FROM inout_logs il2
                        WHERE il2.student_id = il1.student_id
                    )
                ) latest_log ON s.id = latest_log.student_id
                WHERE s.status = 'active' AND (latest_log.action = 'out' OR latest_log.action IS NULL)
            ");
            $stats['students_outside'] = $stmt->fetchColumn();
            
            // Total parents
            $stmt = $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'parent'");
            $stats['total_parents'] = $stmt->fetchColumn();
            
            // Active parents
            $stmt = $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'parent' AND status = 'active'");
            $stats['active_parents'] = $stmt->fetchColumn();
            
            // Students with parents
            $stmt = $this->db->query("SELECT COUNT(*) FROM students WHERE parent_id IS NOT NULL");
            $stats['students_with_parents'] = $stmt->fetchColumn();
            
            return $stats;
            
        } catch (PDOException $e) {
            error_log("Error fetching system stats: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Validate user data
     */
    private function validateUserData($data, $role = null, $excludeUserId = null) {
        $errors = [];
        
        // Name validation
        if (empty($data['name']) || strlen(trim($data['name'])) < 2) {
            $errors['name'] = 'Name must be at least 2 characters long';
        }
        
        // Email validation
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email address is required';
        }
        
        // Phone validation (optional)
        if (!empty($data['phone']) && !preg_match('/^[0-9+\-\s()]+$/', $data['phone'])) {
            $errors['phone'] = 'Invalid phone number format';
        }
        
        // Password validation (required for new users)
        if ($excludeUserId === null) { // New user
            if (empty($data['password']) || strlen($data['password']) < 6) {
                $errors['password'] = 'Password must be at least 6 characters long';
            }
        } else { // Existing user - password is optional
            if (!empty($data['password']) && strlen($data['password']) < 6) {
                $errors['password'] = 'Password must be at least 6 characters long';
            }
        }
        
        return $errors;
    }
    
    /**
     * Check if email exists
     */
    private function emailExists($email, $excludeUserId = null) {
        try {
            $sql = "SELECT COUNT(*) FROM users WHERE email = ?";
            $params = [$email];
            
            if ($excludeUserId) {
                $sql .= " AND id != ?";
                $params[] = $excludeUserId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchColumn() > 0;
            
        } catch (PDOException $e) {
            error_log("Error checking email existence: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if phone exists
     */
    private function phoneExists($phone, $excludeUserId = null) {
        try {
            $sql = "SELECT COUNT(*) FROM users WHERE phone = ?";
            $params = [$phone];
            
            if ($excludeUserId) {
                $sql .= " AND id != ?";
                $params[] = $excludeUserId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchColumn() > 0;
            
        } catch (PDOException $e) {
            error_log("Error checking phone existence: " . $e->getMessage());
            return false;
        }
    }
}