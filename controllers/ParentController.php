<?php
require_once 'config/database.php';

class ParentController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get all parents with search and pagination
     */
    public function getAllParents($search = '', $limit = 15, $offset = 0) {
        try {
            $sql = "
                SELECT 
                    p.id,
                    p.name,
                    p.email,
                    p.phone,
                    p.status,
                    p.created_at,
                    GROUP_CONCAT(
                        JSON_OBJECT(
                            'id', s.id,
                            'name', s.name,
                            'student_id', s.student_id
                        )
                    ) as students_json
                FROM parents p
                LEFT JOIN students s ON p.id = s.parent_id
            ";
            
            $params = [];
            
            if (!empty($search)) {
                $sql .= " WHERE (
                    p.name LIKE :search OR 
                    p.email LIKE :search OR 
                    p.phone LIKE :search OR
                    s.name LIKE :search OR
                    s.student_id LIKE :search
                )";
                $params[':search'] = '%' . $search . '%';
            }
            
            $sql .= " GROUP BY p.id ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $parents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Process students data
            foreach ($parents as &$parent) {
                if ($parent['students_json']) {
                    $students = [];
                    $studentObjects = explode(',', $parent['students_json']);
                    foreach ($studentObjects as $studentJson) {
                        $student = json_decode($studentJson, true);
                        if ($student) {
                            $students[] = $student;
                        }
                    }
                    $parent['students'] = $students;
                } else {
                    $parent['students'] = [];
                }
                unset($parent['students_json']);
            }
            
            return $parents;
            
        } catch (PDOException $e) {
            error_log("Error fetching parents: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total count of parents for pagination
     */
    public function getTotalParentsCount($search = '') {
        try {
            $sql = "SELECT COUNT(DISTINCT p.id) as total FROM parents p";
            $params = [];
            
            if (!empty($search)) {
                $sql .= " LEFT JOIN students s ON p.id = s.parent_id
                         WHERE (
                            p.name LIKE :search OR 
                            p.email LIKE :search OR 
                            p.phone LIKE :search OR
                            s.name LIKE :search OR
                            s.student_id LIKE :search
                         )";
                $params[':search'] = '%' . $search . '%';
            }
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int) $result['total'];
            
        } catch (PDOException $e) {
            error_log("Error counting parents: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Update parent status
     */
    public function updateParentStatus($parentId, $status) {
        try {
            $sql = "UPDATE parents SET status = :status WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $parentId, PDO::PARAM_INT);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Error updating parent status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get parent by ID
     */
    public function getParentById($id) {
        try {
            $sql = "
                SELECT 
                    p.*,
                    GROUP_CONCAT(
                        JSON_OBJECT(
                            'id', s.id,
                            'name', s.name,
                            'student_id', s.student_id,
                            'status', s.status
                        )
                    ) as students_json
                FROM parents p
                LEFT JOIN students s ON p.id = s.parent_id
                WHERE p.id = :id
                GROUP BY p.id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $parent = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($parent) {
                // Process students data
                if ($parent['students_json']) {
                    $students = [];
                    $studentObjects = explode(',', $parent['students_json']);
                    foreach ($studentObjects as $studentJson) {
                        $student = json_decode($studentJson, true);
                        if ($student) {
                            $students[] = $student;
                        }
                    }
                    $parent['students'] = $students;
                } else {
                    $parent['students'] = [];
                }
                unset($parent['students_json']);
            }
            
            return $parent;
            
        } catch (PDOException $e) {
            error_log("Error fetching parent: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get parent statistics for dashboard
     */
    public function getParentStats() {
        try {
            $stats = [];
            
            // Total parents
            $sql = "SELECT COUNT(*) as total FROM parents";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['total_parents'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Active parents
            $sql = "SELECT COUNT(*) as total FROM parents WHERE status = 'active'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['active_parents'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Students with parents
            $sql = "SELECT COUNT(*) as total FROM students WHERE parent_id IS NOT NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['students_with_parents'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            return $stats;
            
        } catch (PDOException $e) {
            error_log("Error fetching parent stats: " . $e->getMessage());
            return [
                'total_parents' => 0,
                'active_parents' => 0,
                'students_with_parents' => 0
            ];
        }
    }
    
    /**
     * Create new parent
     */
    public function createParent($data) {
        try {
            $sql = "
                INSERT INTO parents (name, email, phone, password, status, created_at) 
                VALUES (:name, :email, :phone, :password, 'active', NOW())
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':phone', $data['phone']);
            $stmt->bindParam(':password', password_hash($data['password'], PASSWORD_DEFAULT));
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Error creating parent: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update parent information
     */
    public function updateParent($id, $data) {
        try {
            $sql = "
                UPDATE parents 
                SET name = :name, email = :email, phone = :phone, updated_at = NOW()
                WHERE id = :id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':phone', $data['phone']);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Error updating parent: " . $e->getMessage());
            return false;
        }
    }
}
?>