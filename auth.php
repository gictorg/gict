<?php
// Include session manager
require_once 'includes/session_manager.php';

// Include database configuration
require_once 'config/database.php';

// Function to require login (redirect if not logged in)
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Function to get current user info from database
function getCurrentUser() {
    if (isLoggedIn()) {
        try {
            $sql = "SELECT u.id, u.username, u.full_name, u.email, u.phone, u.address, u.status, ut.name as user_type 
                    FROM users u 
                    JOIN user_types ut ON u.user_type_id = ut.id 
                    WHERE u.id = ? AND u.status = 'active'";
            $user = getRow($sql, [$_SESSION['user_id']]);
            
            if ($user) {
                return [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'type' => $user['user_type'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'address' => $user['address'],
                    'status' => $user['status']
                ];
            }
        } catch (Exception $e) {
            // Silent fail for security
        }
        
        // If database lookup fails, don't return session data - it might be corrupted
        return null;
    }
    return null;
}

// Function to get user by ID
function getUserById($userId) {
    try {
        $sql = "SELECT id, username, user_type, full_name, email, phone, address, status, created_at FROM users WHERE id = ?";
        return getRow($sql, [$userId]);
    } catch (Exception $e) {
        // Silent fail for security
        return false;
    }
}

// Function to get user by username
function getUserByUsername($username) {
    try {
        $sql = "SELECT id, username, user_type, full_name, email, phone, address, status, created_at FROM users WHERE username = ?";
        return getRow($sql, [$username]);
    } catch (Exception $e) {
        // Silent fail for security
        return false;
    }
}

// Function to update user profile
function updateUserProfile($userId, $data) {
    try {
        $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?";
        $params = [$data['full_name'], $data['email'], $data['phone'], $data['address'], $userId];
        return updateData($sql, $params);
    } catch (Exception $e) {
        // Silent fail for security
        return false;
    }
}

// Function to change password
function changePassword($userId, $currentPassword, $newPassword) {
    try {
        // Verify current password
        $user = getUserById($userId);
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return false;
        }
        
        // Validate new password strength
        if (strlen($newPassword) < 8) {
            return false;
        }
        
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $newPassword)) {
            return false;
        }
        
        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
        return updateData($sql, [$hashedPassword, $userId]);
    } catch (Exception $e) {
        // Silent fail for security
        return false;
    }
}

// Function to logout user
function logout() {
    // Log logout activity if user was logged in (commented out as user_logins table doesn't exist)
    // if (isset($_SESSION['user_id'])) {
    //     try {
    //         $logout_sql = "INSERT INTO user_logins (user_id, login_time, ip_address, status) VALUES (?, NOW(), ?, 'logout')";
    //         insertData($logout_sql, [$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    //     } catch (Exception $e) {
    //         // Silent fail for security
    //     }
    // }
    
    // Use secure logout from session manager
    secureLogout();
}

// Function to require specific role
function requireRole($role) {
    if (!hasRole($role)) {
        header('Location: dashboard.php');
        exit();
    }
}
?> 