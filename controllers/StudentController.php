<?php
require_once 'config/database.php';

class StudentController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get all students with search and pagination
     */
    public function getAllStudents($search = '', $limit = 15, $offset = 0) {
        try {
            $sql = "
                SELECT 
                    s.id,
                    s.name,
                    s.student_id,
                    s.email,
                    s.phone,
                    s.emergency_contact,
                    s.status,
                    s.current_status,
                    s.qr_code,
                    s.created_at,
                    p.name as parent_name,
                    p.email as parent_email,
                    p.phone as parent_phone
                FROM students s
                LEFT JOIN parents p ON s.parent_id = p.id
            ";
            
            $params = [];
            
            if (!empty($search)) {
                $sql .= " WHERE (
                    s.name LIKE :search OR 
                    s.student_id LIKE :search OR 
                    s.email LIKE :search OR 
                    s.phone LIKE :search OR
                    s.emergency_contact LIKE :search OR
                    p.name LIKE :search OR
                    p.email LIKE :search OR
                    p.phone LIKE :search
                )";
                $params[':search'] = '%' . $search . '%';
            }
            
            $sql .= " ORDER BY s.created_at DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching students: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total count of students for pagination
     */
    public function getTotalStudentsCount($search = '') {
        try {
            $sql = "SELECT COUNT(*) as total FROM students s";
            $params = [];
            
            if (!empty($search)) {
                $sql .= " LEFT JOIN parents p ON s.parent_id = p.id
                         WHERE (
                            s.name LIKE :search OR 
                            s.student_id LIKE :search OR 
                            s.email LIKE :search OR 
                            s.phone LIKE :search OR
                            s.emergency_contact LIKE :search OR
                            p.name LIKE :search OR
                            p.email LIKE :search OR
                            p.phone LIKE :search
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
            error_log("Error counting students: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Update student status
     */
    public function updateStudentStatus($studentId, $status) {
        try {
            $sql = "UPDATE students SET status = :status WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $studentId, PDO::PARAM_INT);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Error updating student status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get student by ID
     */
    public function getStudentById($id) {
        try {
            $sql = "
                SELECT 
                    s.*,
                    p.name as parent_name,
                    p.email as parent_email,
                    p.phone as parent_phone
                FROM students s
                LEFT JOIN parents p ON s.parent_id = p.id
                WHERE s.id = :id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching student: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get student statistics for dashboard
     */
    public function getStudentStats() {
        try {
            $stats = [];
            
            // Total students
            $sql = "SELECT COUNT(*) as total FROM students";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['total_students'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Active students
            $sql = "SELECT COUNT(*) as total FROM students WHERE status = 'active'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['active_students'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Students currently inside
            $sql = "SELECT COUNT(*) as total FROM students WHERE current_status = 'inside'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['students_inside'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Students currently outside (based on latest log entry)
            $sql = "
                SELECT COUNT(*) as total FROM students s
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
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['outside'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            return $stats;
            
        } catch (PDOException $e) {
            error_log("Error fetching student stats: " . $e->getMessage());
            return [
                'total_students' => 0,
                'active_students' => 0,
                'students_inside' => 0,
                'outside' => 0
            ];
        }
    }
    
    /**
     * Create new student
     */
    public function createStudent($data) {
        try {
            // Generate unique QR token for the student
            $qrToken = hash('sha256', uniqid($data['ic_no'], true));
            
            $stmt = $this->db->prepare("
                INSERT INTO students (name, ic_no, parent_id, qr_token, status) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $data['name'],
                $data['ic_no'],
                $data['parent_id'],
                $qrToken,
                $data['status'] ?? 'active'
            ]);
            
            if ($result) {
                $studentId = $this->db->lastInsertId();
                // Generate QR code file
                $this->generateQRCode($studentId, $qrToken);
                return $studentId;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error creating student: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update student information
     */
    public function updateStudent($id, $data) {
        try {
            $sql = "
                UPDATE students 
                SET name = :name, student_id = :student_id, email = :email, 
                    phone = :phone, emergency_contact = :emergency_contact, 
                    parent_id = :parent_id, updated_at = NOW()
                WHERE id = :id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':student_id', $data['student_id']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':phone', $data['phone']);
            $stmt->bindParam(':emergency_contact', $data['emergency_contact']);
            $stmt->bindParam(':parent_id', $data['parent_id'], PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Error updating student: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate QR code for student
     */
    public function generateQRCode($studentId) {
        try {
            // Generate unique QR code
            $qrCode = 'STU_' . $studentId . '_' . time() . '_' . bin2hex(random_bytes(8));
            
            $sql = "UPDATE students SET qr_code = :qr_code WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':qr_code', $qrCode);
            $stmt->bindParam(':id', $studentId, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                return $qrCode;
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Error generating QR code: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update student location status
     */
    public function updateStudentLocation($studentId, $status) {
        try {
            $sql = "UPDATE students SET current_status = :status WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $studentId, PDO::PARAM_INT);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Error updating student location: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get student by QR code
     */
    public function getStudentByQRCode($qrCode) {
        try {
            $sql = "
                SELECT 
                    s.*,
                    p.name as parent_name,
                    p.phone as parent_phone
                FROM students s
                LEFT JOIN parents p ON s.parent_id = p.id
                WHERE s.qr_code = :qr_code
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':qr_code', $qrCode);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching student by QR code: " . $e->getMessage());
            return null;
        }
    }
}
?>