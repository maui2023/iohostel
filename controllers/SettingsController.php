<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

class SettingsController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Change password with old password verification
    public function changePassword($userId, $oldPassword, $newPassword) {
        try {
            // Verify old password
            $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($oldPassword, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            
            return ['success' => true, 'message' => 'Password changed successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error changing password: ' . $e->getMessage()];
        }
    }
    
    // Submit password reset request
    public function submitResetRequest($userId, $phone) {
        try {
            // Verify phone number belongs to user
            $stmt = $this->db->prepare("SELECT phone FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || $user['phone'] !== $phone) {
                return ['success' => false, 'message' => 'Phone number does not match your account'];
            }
            
            // Check if there's already a pending request
            $stmt = $this->db->prepare("SELECT id FROM password_reset_requests WHERE user_id = ? AND status = 'pending'");
            $stmt->execute([$userId]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'You already have a pending reset request'];
            }
            
            // Create reset request
            $stmt = $this->db->prepare("INSERT INTO password_reset_requests (user_id, phone) VALUES (?, ?)");
            $stmt->execute([$userId, $phone]);
            
            return ['success' => true, 'message' => 'Password reset request submitted successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error submitting request: ' . $e->getMessage()];
        }
    }
    
    // Get password reset requests (for admin/superadmin)
    public function getResetRequests($userRole, $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Build query based on user role
            if ($userRole === 'superadmin') {
                // Superadmin can see all requests
                $query = "SELECT prr.*, u.name, u.email, u.role 
                         FROM password_reset_requests prr 
                         JOIN users u ON prr.user_id = u.id 
                         ORDER BY prr.requested_at DESC 
                         LIMIT ? OFFSET ?";
                $params = [$limit, $offset];
            } else {
                // Admin can only see guard and parent requests
                $query = "SELECT prr.*, u.name, u.email, u.role 
                         FROM password_reset_requests prr 
                         JOIN users u ON prr.user_id = u.id 
                         WHERE u.role IN ('guard', 'parent') 
                         ORDER BY prr.requested_at DESC 
                         LIMIT ? OFFSET ?";
                $params = [$limit, $offset];
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Get total count of reset requests
    public function getResetRequestsCount($userRole) {
        try {
            if ($userRole === 'superadmin') {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM password_reset_requests");
                $stmt->execute();
            } else {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM password_reset_requests prr JOIN users u ON prr.user_id = u.id WHERE u.role IN ('guard', 'parent')");
                $stmt->execute();
            }
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }
    
    // Accept reset request and generate new password
    public function acceptResetRequest($requestId, $processedBy) {
        try {
            // Generate new password
            $newPassword = $this->generateRandomPassword();
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Update request status
            $stmt = $this->db->prepare("UPDATE password_reset_requests SET status = 'accepted', new_password = ?, processed_at = NOW(), processed_by = ? WHERE id = ?");
            $stmt->execute([$newPassword, $processedBy, $requestId]);
            
            // Get user ID from request
            $stmt = $this->db->prepare("SELECT user_id FROM password_reset_requests WHERE id = ?");
            $stmt->execute([$requestId]);
            $userId = $stmt->fetchColumn();
            
            // Update user password
            $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            
            $this->db->commit();
            
            return ['success' => true, 'password' => $newPassword];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Error accepting request: ' . $e->getMessage()];
        }
    }
    
    // Reject reset request
    public function rejectResetRequest($requestId, $processedBy) {
        try {
            $stmt = $this->db->prepare("UPDATE password_reset_requests SET status = 'rejected', processed_at = NOW(), processed_by = ? WHERE id = ?");
            $stmt->execute([$processedBy, $requestId]);
            
            return ['success' => true, 'message' => 'Request rejected successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error rejecting request: ' . $e->getMessage()];
        }
    }
    
    // Generate random password
    private function generateRandomPassword($length = 8) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $password;
    }
    
    // Get user details for WhatsApp message
    public function getUserForWhatsApp($requestId) {
        try {
            $stmt = $this->db->prepare("
                SELECT prr.new_password, u.name, u.phone 
                FROM password_reset_requests prr 
                JOIN users u ON prr.user_id = u.id 
                WHERE prr.id = ? AND prr.status = 'accepted'
            ");
            $stmt->execute([$requestId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
}