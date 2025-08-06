<?php

require_once __DIR__ . '/../config/database.php';

class StudentController {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function createStudent($data, $file) {
        error_log("Entering createStudent method.");
        // Validate required fields
        $requiredFields = ['name', 'student_no', 'class_id', 'parent_id'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'errors' => [$field => ucfirst(str_replace('_', ' ', $field)) . ' is required.' ]];
            }
        }
        // Handle picture upload
        $picturePath = null;
        $uploadDir = __DIR__ . '/../public/assets/img/students/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        if (!isset($file['picture']) || !is_array($file['picture']) || $file['picture']['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => false, 'errors' => ['picture' => 'Picture is required.' ]];
        }
        if ($file['picture']['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            ];
            $errorMessage = $errorMessages[$file['picture']['error']] ?? 'Unknown upload error.';
            return ['success' => false, 'errors' => ['picture' => $errorMessage ]];
        }
        $filename = uniqid() . '_' . basename($file['picture']['name']);
        $targetPath = $uploadDir . $filename;
        error_log("Attempting to move uploaded file from " . $file['picture']['tmp_name'] . " to " . $targetPath);
        $moveResult = move_uploaded_file($file['picture']['tmp_name'], $targetPath);
        error_log("move_uploaded_file result: " . ($moveResult ? 'true' : 'false'));
        if (!$moveResult) {
            $lastError = error_get_last();
            error_log("Failed to move uploaded file to " . $targetPath . ". Error: " . ($lastError ? $lastError['message'] : 'Unknown error') . ". File details: name=" . $file['picture']['name'] . ", type=" . $file['picture']['type'] . ", tmp_name=" . $file['picture']['tmp_name'] . ", error=" . $file['picture']['error'] . ", size=" . $file['picture']['size']);
            return ['success' => false, 'errors' => ['picture' => 'Failed to upload picture.' ]];
        }
        $picturePath = '/assets/img/students/' . $filename;
        error_log("File moved successfully. Picture path: " . $picturePath);
        // Generate QR token (simple example)
        $qrToken = bin2hex(random_bytes(16));
        $sql = "INSERT INTO students (name, student_no, class_id, parent_id, gender, religion, race, picture, qr_token) VALUES (:name, :student_no, :class_id, :parent_id, :gender, :religion, :race, :picture, :qr_token)";
        try {
            $stmt = $this->conn->prepare($sql);
            // Bind parameters
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':student_no', $data['student_no']);
            $stmt->bindParam(':class_id', $data['class_id'], PDO::PARAM_INT);
            $stmt->bindParam(':parent_id', $data['parent_id'], PDO::PARAM_INT);
            $stmt->bindParam(':gender', $data['gender']);
            $stmt->bindParam(':religion', $data['religion']);
            $stmt->bindParam(':race', $data['race']);
            $stmt->bindParam(':picture', $picturePath);
            $stmt->bindParam(':qr_token', $qrToken);
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Student created successfully.', 'student_id' => $this->conn->lastInsertId()];
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("PDO Error: " . $errorInfo[2]);
                return ['success' => false, 'errors' => ['db_error' => 'Failed to create student. Please try again.' ]];
            }
        } catch (PDOException $e) {
            error_log("PDO Exception: " . $e->getMessage());
            return ['success' => false, 'errors' => ['db_error' => 'Failed to create student: ' . $e->getMessage() ]];
        }
    }

    public function getStudents($search = '', $limit = 10, $offset = 0, $classId = '', $gender = '', $order = 'DESC') {
        $query = "SELECT s.*, c.name as class_name, u.name as parent_name FROM students s LEFT JOIN classes c ON s.class_id = c.id LEFT JOIN users u ON s.parent_id = u.id WHERE u.role = 'parent'";
        $params = [];
        $conditions = [];
        if (!empty($search)) {
            $conditions[] = "(s.name LIKE :search OR s.student_no LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        if (!empty($classId)) {
            $conditions[] = "s.class_id = :class_id";
            $params[':class_id'] = $classId;
        }
        if (!empty($gender)) {
            $conditions[] = "s.gender = :gender";
            $params[':gender'] = $gender;
        }
        if ($conditions) {
            $query .= ' AND ' . implode(' AND ', $conditions);
        }
        $query .= " ORDER BY s.id " . ($order === 'ASC' ? 'ASC' : 'DESC');
        $query .= " LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStudentById($id) {
        $query = "SELECT s.*, c.name as class_name, u.name as parent_name FROM students s LEFT JOIN classes c ON s.class_id = c.id LEFT JOIN users u ON s.parent_id = u.id WHERE s.id = :id AND u.role = 'parent' LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data;
    }

    public function updateStudent($data, $file) {
        // Similar logic to createStudent but for update
        // ... (implementation for update)
    }

    public function deleteStudent($id) {
        $query = "DELETE FROM students WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function countStudents($search = '', $classId = '', $gender = '') {
        $query = "SELECT COUNT(*) FROM students s LEFT JOIN users u ON s.parent_id = u.id WHERE u.role = 'parent'";
        $params = [];
        $conditions = [];
        if (!empty($search)) {
            $conditions[] = "(s.name LIKE :search OR s.student_no LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        if (!empty($classId)) {
            $conditions[] = "s.class_id = :class_id";
            $params[':class_id'] = $classId;
        }
        if (!empty($gender)) {
            $conditions[] = "s.gender = :gender";
            $params[':gender'] = $gender;
        }
        if ($conditions) {
            $query .= ' AND ' . implode(' AND ', $conditions);
        }
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
}