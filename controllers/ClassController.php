<?php
require_once __DIR__ . '/../config/database.php';
class ClassController {
    private $db;
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    public function getAllClasses() {
        $stmt = $this->db->prepare("SELECT * FROM classes ORDER BY name ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function createClass($name, $description) {
        // Input validation
        $name = trim($name);
        $description = trim($description);
        if (empty($name)) {
            throw new InvalidArgumentException('Class name is required.');
        }
        if (strlen($name) > 100) {
            throw new InvalidArgumentException('Class name is too long.');
        }
        if (strlen($description) > 255) {
            throw new InvalidArgumentException('Description is too long.');
        }
        try {
            $stmt = $this->db->prepare("INSERT INTO classes (name, description) VALUES (:name, :description)");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            return $stmt->execute();
        } catch (PDOException $e) {
            // Log error securely (implement logging as needed)
            error_log('Class creation error: ' . $e->getMessage());
            return false;
        }
    }
    public function updateClass($id, $name, $description) {
        $name = trim($name);
        $description = trim($description);
        if (empty($name)) {
            throw new InvalidArgumentException('Class name is required.');
        }
        if (strlen($name) > 100) {
            throw new InvalidArgumentException('Class name is too long.');
        }
        if (strlen($description) > 255) {
            throw new InvalidArgumentException('Description is too long.');
        }
        try {
            $stmt = $this->db->prepare("UPDATE classes SET name = :name, description = :description WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Class update error: ' . $e->getMessage());
            return false;
        }
    }
}