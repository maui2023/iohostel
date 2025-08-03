<?php

// WhatsApp integration utilities

require_once __DIR__ . '/../config/database.php';

/**
 * Generate WhatsApp message link for parent notification
 * @param string $parentPhone Parent's phone number
 * @param string $studentName Student's name
 * @param string $action Check-in or Check-out
 * @param string $timestamp Time of action
 * @param string $loginLink Link for parent to view logs
 * @return string WhatsApp URL
 */
function generateWhatsAppLink($parentPhone, $studentName, $action, $timestamp, $loginLink = '') {
    // Load environment variables
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
    
    $hostelName = $_ENV['HOSTEL_NAME'] ?? 'Hostel';
    $template = $_ENV['WHATSAPP_TEMPLATE'] ?? 'Your child {STUDENT} has {ACTION} at {TIME}. View logs: {LINK}';
    
    // Clean phone number (remove spaces, dashes, etc.)
    $cleanPhone = cleanPhoneNumber($parentPhone);
    
    // Format timestamp
    $formattedTime = date('d/m/Y H:i', strtotime($timestamp));
    
    // Replace template variables
    $message = str_replace([
        '{STUDENT}',
        '{ACTION}',
        '{TIME}',
        '{HOSTEL}',
        '{LINK}'
    ], [
        $studentName,
        ucfirst($action),
        $formattedTime,
        $hostelName,
        $loginLink
    ], $template);
    
    // URL encode the message
    $encodedMessage = urlencode($message);
    
    // Generate WhatsApp link
    $whatsappUrl = "https://wa.me/{$cleanPhone}?text={$encodedMessage}";
    
    return $whatsappUrl;
}

/**
 * Clean phone number for WhatsApp format
 * @param string $phone Raw phone number
 * @return string Cleaned phone number with country code
 */
function cleanPhoneNumber($phone) {
    // Remove all non-numeric characters
    $cleaned = preg_replace('/[^0-9]/', '', $phone);
    
    // Add Malaysia country code if not present
    if (strlen($cleaned) === 10 && substr($cleaned, 0, 1) === '0') {
        // Remove leading 0 and add Malaysia code
        $cleaned = '6' . substr($cleaned, 1);
    } elseif (strlen($cleaned) === 9) {
        // Add Malaysia code
        $cleaned = '6' . $cleaned;
    } elseif (strlen($cleaned) === 11 && substr($cleaned, 0, 2) === '60') {
        // Already has Malaysia code
        $cleaned = $cleaned;
    } elseif (strlen($cleaned) === 12 && substr($cleaned, 0, 3) === '601') {
        // Has Malaysia code with extra 1
        $cleaned = '6' . substr($cleaned, 3);
    }
    
    return $cleaned;
}

/**
 * Generate parent login token and link
 * @param int $parentId Parent's user ID
 * @return array Token and login link
 */
function generateParentLoginLink($parentId) {
    try {
        $db = getDB();
        
        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expiryMinutes = $_ENV['TOKEN_EXPIRY_MINUTES'] ?? 10;
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryMinutes} minutes"));
        
        // Store token in database
        $stmt = $db->prepare("
            INSERT INTO parent_tokens (parent_id, token, expires_at) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$parentId, $token, $expiresAt]);
        
        // Generate login link
        $baseUrl = $_ENV['BASE_URL'] ?? 'http://localhost';
        $loginLink = $baseUrl . '/login/parent?token=' . $token;
        
        return [
            'success' => true,
            'token' => $token,
            'link' => $loginLink,
            'expires_at' => $expiresAt
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Verify parent login token
 * @param string $token Login token
 * @return array|false Parent data if valid, false if invalid
 */
function verifyParentToken($token) {
    try {
        $db = getDB();
        
        // Get token data
        $stmt = $db->prepare("
            SELECT pt.*, u.* 
            FROM parent_tokens pt
            JOIN users u ON pt.parent_id = u.id
            WHERE pt.token = ? AND pt.expires_at > NOW() AND pt.used = 0
        ");
        $stmt->execute([$token]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return false;
        }
        
        // Mark token as used
        $stmt = $db->prepare("UPDATE parent_tokens SET used = 1 WHERE token = ?");
        $stmt->execute([$token]);
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Parent token verification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send WhatsApp notification to parent
 * @param int $studentId Student ID
 * @param string $action Check-in or Check-out action
 * @param int $guardId Guard who performed the action
 * @return array Result with success status and WhatsApp link
 */
function sendParentNotification($studentId, $action, $guardId) {
    try {
        $db = getDB();
        
        // Get student and parent data
        $stmt = $db->prepare("
            SELECT s.name as student_name, u.name as parent_name, u.phone as parent_phone, u.id as parent_id
            FROM students s
            JOIN users u ON s.parent_id = u.id
            WHERE s.id = ? AND s.status = 'active' AND u.status = 'active'
        ");
        $stmt->execute([$studentId]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return [
                'success' => false,
                'error' => 'Student or parent not found'
            ];
        }
        
        // Generate parent login link
        $loginResult = generateParentLoginLink($data['parent_id']);
        if (!$loginResult['success']) {
            return $loginResult;
        }
        
        // Generate WhatsApp link
        $timestamp = date('Y-m-d H:i:s');
        $whatsappLink = generateWhatsAppLink(
            $data['parent_phone'],
            $data['student_name'],
            $action,
            $timestamp,
            $loginResult['link']
        );
        
        // Log the notification attempt
        $stmt = $db->prepare("
            INSERT INTO notification_logs (student_id, parent_id, guard_id, action, whatsapp_link, sent_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$studentId, $data['parent_id'], $guardId, $action, $whatsappLink]);
        
        return [
            'success' => true,
            'whatsapp_link' => $whatsappLink,
            'parent_name' => $data['parent_name'],
            'parent_phone' => $data['parent_phone'],
            'login_link' => $loginResult['link'],
            'token_expires' => $loginResult['expires_at']
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Clean up expired parent tokens
 * @return int Number of tokens cleaned up
 */
function cleanupExpiredTokens() {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM parent_tokens WHERE expires_at < NOW()");
        $stmt->execute();
        return $stmt->rowCount();
    } catch (Exception $e) {
        error_log("Token cleanup error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get WhatsApp message preview
 * @param string $studentName Student name
 * @param string $action Action (in/out)
 * @param string $timestamp Timestamp
 * @param string $loginLink Login link
 * @return string Formatted message
 */
function getWhatsAppMessagePreview($studentName, $action, $timestamp, $loginLink = '') {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
    
    $hostelName = $_ENV['HOSTEL_NAME'] ?? 'Hostel';
    $template = $_ENV['WHATSAPP_TEMPLATE'] ?? 'Your child {STUDENT} has {ACTION} at {TIME}. View logs: {LINK}';
    
    $formattedTime = date('d/m/Y H:i', strtotime($timestamp));
    
    return str_replace([
        '{STUDENT}',
        '{ACTION}',
        '{TIME}',
        '{HOSTEL}',
        '{LINK}'
    ], [
        $studentName,
        ucfirst($action),
        $formattedTime,
        $hostelName,
        $loginLink ?: '[Login Link]'
    ], $template);
}

/**
 * Validate phone number format
 * @param string $phone Phone number to validate
 * @return bool True if valid
 */
function isValidPhoneNumber($phone) {
    $cleaned = cleanPhoneNumber($phone);
    
    // Check if it's a valid Malaysian mobile number
    // Malaysian mobile numbers start with 60 followed by 1, then 8-9 digits
    return preg_match('/^6(01[0-9]{7,8}|011[0-9]{7,8}|012[0-9]{7,8}|013[0-9]{7,8}|014[0-9]{7,8}|015[0-9]{7,8}|016[0-9]{7,8}|017[0-9]{7,8}|018[0-9]{7,8}|019[0-9]{7,8})$/', $cleaned);
}