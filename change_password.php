<?php
require_once 'includes/session_manager.php';
require_once 'config/database.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

$currentPassword = $input['currentPassword'] ?? '';
$newPassword = $input['newPassword'] ?? '';

// Validate input
if (empty($currentPassword) || empty($newPassword)) {
    echo json_encode(['success' => false, 'message' => 'Current password and new password are required']);
    exit;
}

// Validate new password length
if (strlen($newPassword) < 8) {
    echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters long']);
    exit;
}

// Additional password strength validation
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $newPassword)) {
    echo json_encode(['success' => false, 'message' => 'Password must contain at least one lowercase letter, one uppercase letter, and one number']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Get current user's password hash
    $sql = "SELECT password FROM users WHERE id = ? AND status = 'active'";
    $user = getRow($sql, [$user_id]);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found or inactive']);
        exit;
    }
    
    // Verify current password
    if (!password_verify($currentPassword, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit;
    }
    
    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update password in database
    $updateSql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
    $result = updateData($updateSql, [$hashedPassword, $user_id]);
    
    if ($result !== false) {
        // Log password change (optional)
        
        echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update password in database']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred while changing password']);
}
?>
