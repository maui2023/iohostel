<?php

// QR Code generation utilities

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\Label\Font\NotoSans;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Generate QR code for a student
 * @param int $studentId Student ID
 * @param string $studentName Student name for label
 * @return array Result with success status and file path or error message
 */
function generateStudentQR($studentId, $studentName) {
    try {
        // Load environment variables
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();
        
        $baseUrl = $_ENV['BASE_URL'] ?? 'http://localhost';
        $qrSavePath = $_ENV['QR_SAVE_PATH'] ?? 'public/qr/';
        $qrPrefix = $_ENV['QR_PREFIX'] ?? 'student_';
        
        // Create QR directory if it doesn't exist
        $qrDir = __DIR__ . '/../' . $qrSavePath;
        if (!is_dir($qrDir)) {
            mkdir($qrDir, 0755, true);
        }
        
        // Generate secure token for the student
        $token = generateSecureToken($studentId);
        
        // Create QR code data (URL that guard will scan)
        $qrData = $baseUrl . '/scan?code=' . $token;
        
        // Generate filename
        $filename = $qrPrefix . $studentId . '_' . time() . '.png';
        $filePath = $qrDir . $filename;
        
        // Build QR code
        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($qrData)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(300)
            ->margin(10)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->labelText($studentName)
            ->labelFont(new NotoSans(16))
            ->labelAlignment(LabelAlignment::Center)
            ->build();
        
        // Save QR code to file
        $result->saveToFile($filePath);
        
        // Update student record with QR code path and token
        $db = getDB();
        $stmt = $db->prepare("UPDATE students SET qr_code = ?, qr_token = ? WHERE id = ?");
        $stmt->execute([$qrSavePath . $filename, $token, $studentId]);
        
        return [
            'success' => true,
            'file_path' => $qrSavePath . $filename,
            'full_path' => $filePath,
            'token' => $token,
            'qr_data' => $qrData
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Generate secure token for QR code
 * @param int $studentId Student ID
 * @return string Secure token
 */
function generateSecureToken($studentId) {
    // Create a secure token that includes student ID but is not easily guessable
    $secret = $_ENV['SESSION_SECRET'] ?? 'default_secret';
    $timestamp = time();
    $random = bin2hex(random_bytes(8));
    
    // Create hash that includes student ID, timestamp, and random data
    $data = $studentId . '|' . $timestamp . '|' . $random;
    $hash = hash_hmac('sha256', $data, $secret);
    
    // Combine data and hash for the token
    $token = base64_encode($data . '|' . $hash);
    
    return $token;
}

/**
 * Verify and decode QR token
 * @param string $token QR token from scan
 * @return array|false Student data if valid, false if invalid
 */
function verifyQRToken($token) {
    try {
        $secret = $_ENV['SESSION_SECRET'] ?? 'default_secret';
        
        // Decode token
        $decoded = base64_decode($token);
        $parts = explode('|', $decoded);
        
        if (count($parts) !== 4) {
            return false;
        }
        
        list($studentId, $timestamp, $random, $hash) = $parts;
        
        // Verify hash
        $data = $studentId . '|' . $timestamp . '|' . $random;
        $expectedHash = hash_hmac('sha256', $data, $secret);
        
        if (!hash_equals($expectedHash, $hash)) {
            return false;
        }
        
        // Check if token is not too old (optional - 1 year expiry)
        $maxAge = 365 * 24 * 60 * 60; // 1 year in seconds
        if (time() - $timestamp > $maxAge) {
            return false;
        }
        
        // Get student data from database
        $db = getDB();
        $stmt = $db->prepare("
            SELECT s.*, u.name as parent_name, u.phone as parent_phone 
            FROM students s 
            JOIN users u ON s.parent_id = u.id 
            WHERE s.id = ? AND s.status = 'active'
        ");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch();
        
        if (!$student) {
            return false;
        }
        
        return $student;
        
    } catch (Exception $e) {
        error_log("QR Token verification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get QR code image as base64 for display
 * @param string $filePath Path to QR code file
 * @return string|false Base64 encoded image or false on error
 */
function getQRImageBase64($filePath) {
    $fullPath = __DIR__ . '/../' . $filePath;
    
    if (!file_exists($fullPath)) {
        return false;
    }
    
    $imageData = file_get_contents($fullPath);
    if ($imageData === false) {
        return false;
    }
    
    return 'data:image/png;base64,' . base64_encode($imageData);
}

/**
 * Delete QR code file
 * @param string $filePath Path to QR code file
 * @return bool Success status
 */
function deleteQRFile($filePath) {
    $fullPath = __DIR__ . '/../' . $filePath;
    
    if (file_exists($fullPath)) {
        return unlink($fullPath);
    }
    
    return true; // File doesn't exist, consider it deleted
}

/**
 * Regenerate QR code for student
 * @param int $studentId Student ID
 * @return array Result with success status
 */
function regenerateStudentQR($studentId) {
    try {
        // Get student data
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch();
        
        if (!$student) {
            return ['success' => false, 'error' => 'Student not found'];
        }
        
        // Delete old QR code file if exists
        if ($student['qr_code']) {
            deleteQRFile($student['qr_code']);
        }
        
        // Generate new QR code
        return generateStudentQR($studentId, $student['name']);
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Get student's current QR code info
 * @param int $studentId Student ID
 * @return array|false QR code info or false if not found
 */
function getStudentQRInfo($studentId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT qr_code, qr_token FROM students WHERE id = ?");
        $stmt->execute([$studentId]);
        $result = $stmt->fetch();
        
        if (!$result || !$result['qr_code']) {
            return false;
        }
        
        return [
            'qr_code_path' => $result['qr_code'],
            'qr_token' => $result['qr_token'],
            'qr_image_base64' => getQRImageBase64($result['qr_code']),
            'file_exists' => file_exists(__DIR__ . '/../' . $result['qr_code'])
        ];
        
    } catch (Exception $e) {
        error_log("Get QR info error: " . $e->getMessage());
        return false;
    }
}