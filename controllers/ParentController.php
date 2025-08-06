<?php
require_once __DIR__ . '/../config/database.php';
class ParentController {
    private $db;
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function deleteParent($parentId) {
        // Start a transaction
        $this->db->beginTransaction();

        try {
            // Delete user roles first
            $stmt = $this->db->prepare('DELETE FROM user_roles WHERE user_id = ? AND role = "parent"');
            $stmt->execute([$parentId]);

            // Then delete the user
            $stmt = $this->db->prepare('DELETE FROM users WHERE id = ?');
            $ok = $stmt->execute([$parentId]);

            // Commit the transaction
            $this->db->commit();

            if ($ok) return ['success'=>true,'message'=>'Parent deleted successfully.'];
            return ['success'=>false,'errors'=>['Failed to delete parent']];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error deleting parent: " . $e->getMessage());
            return ['success'=>false,'errors'=>['Database error occurred']];
        }
    }

    // Restore getAllParents for add-parent.php compatibility
    public function getAllParents($search = '', $limit = 15, $offset = 0) {
        $sql = "SELECT u.*, GROUP_CONCAT(ur.role) as roles FROM users u JOIN user_roles ur ON u.id = ur.user_id WHERE ur.role = 'parent'";
        $params = [];
        if (!empty($search)) {
            $sql .= " AND (name LIKE :search OR email LIKE :search OR phone LIKE :search)";
            $params[':search'] = "%$search%";
        }
        $sql .= " GROUP BY u.id ORDER BY u.created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getTotalParentsCount($search = '') {
        $sql = "SELECT COUNT(DISTINCT u.id) as total FROM users u JOIN user_roles ur ON u.id = ur.user_id WHERE ur.role = 'parent'";
        $params = [];
        if (!empty($search)) {
            $sql .= " AND (name LIKE :search OR email LIKE :search OR phone LIKE :search)";
            $params[':search'] = "%$search%";
        }
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }
    public function createParent($data) {
        $errors = [];
        if (empty($data['name'])) $errors['name'] = 'Name is required.';
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Valid email is required.';
        if (empty($data['phone'])) $errors['phone'] = 'Phone is required.';
        if (empty($data['password'])) $errors['password'] = 'Password is required.';
        if (!empty($errors)) return ['success'=>false,'errors'=>$errors];
        // Check duplicate
        $stmt = $this->db->prepare('SELECT COUNT(u.id) FROM users u JOIN user_roles ur ON u.id = ur.user_id WHERE (u.email = ? OR u.phone = ?) AND ur.role = "parent"');
        $stmt->execute([$data['email'],$data['phone']]);
        if ($stmt->fetchColumn() > 0) return ['success'=>false,'errors'=>['Email or phone already exists for a parent']];
        
        $hashed = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Start a transaction
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare('INSERT INTO users (name,email,phone,password,status,created_at) VALUES (?,?,?,?,"active",NOW())');
            $ok = $stmt->execute([$data['name'],$data['email'],$data['phone'],$hashed]);
            
            if ($ok) {
                $userId = $this->db->lastInsertId();
                $stmtRole = $this->db->prepare('INSERT INTO user_roles (user_id, role) VALUES (?, "parent")');
                $stmtRole->execute([$userId]);
                $this->db->commit();
                return ['success'=>true,'message'=>'Parent created successfully','generated_password'=>$data['password']];
            } else {
                $this->db->rollBack();
                return ['success'=>false,'errors'=>['Failed to create parent']];
            }
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error creating parent: " . $e->getMessage());
            return ['success'=>false,'errors'=>['Database error occurred']];
        }
    }
    public function updateParentStatus($parentId, $status) {
        $errors = [];
        if (!in_array($status, ['active', 'disabled'])) {
            $errors[] = 'Invalid status.';
        }
        if (empty($parentId) || !is_numeric($parentId)) {
            $errors[] = 'Invalid parent ID.';
        }
        if ($errors) return ['success'=>false,'errors'=>$errors];
        $stmt = $this->db->prepare('UPDATE users SET status = ? WHERE id = ? AND id IN (SELECT user_id FROM user_roles WHERE role = "parent")');
        $ok = $stmt->execute([$status, $parentId]);
        if ($ok) return ['success'=>true,'message'=>'Parent status updated successfully.'];
        return ['success'=>false,'errors'=>['Failed to update status']];
    }
    public function getParentById($parentId) {
        $stmt = $this->db->prepare('SELECT u.*, GROUP_CONCAT(ur.role) as roles FROM users u JOIN user_roles ur ON u.id = ur.user_id WHERE u.id = ? AND ur.role = "parent" GROUP BY u.id');
        $stmt->execute([$parentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function updateParent($data) {
        $errors = [];
        if (empty($data['parent_id']) || !is_numeric($data['parent_id'])) $errors[] = 'Invalid parent ID.';
        if (empty($data['name'])) $errors[] = 'Name is required.';
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if (empty($data['phone'])) $errors[] = 'Phone is required.';
        if ($errors) return ['success'=>false,'errors'=>$errors];
        // Check for duplicate email/phone (excluding self)
        $stmt = $this->db->prepare('SELECT COUNT(u.id) FROM users u JOIN user_roles ur ON u.id = ur.user_id WHERE (u.email = ? OR u.phone = ?) AND u.id != ? AND ur.role = "parent"');
        $stmt->execute([$data['email'],$data['phone'],$data['parent_id']]);
        if ($stmt->fetchColumn() > 0) return ['success'=>false,'errors'=>['Email or phone already exists for a parent']];
        $stmt = $this->db->prepare('UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ? AND id IN (SELECT user_id FROM user_roles WHERE role = "parent")');
        $ok = $stmt->execute([$data['name'],$data['email'],$data['phone'],$data['parent_id']]);
        if ($ok) return ['success'=>true,'message'=>'Parent updated successfully.'];
        return ['success'=>false,'errors'=>['Failed to update parent']];
    }
}
