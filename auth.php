<?php
session_start();

// Include database configuration
require_once 'config/database.php';

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

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
            $sql = "SELECT id, username, user_type, full_name, email, phone, address, status FROM users WHERE id = ? AND status = 'active'";
            $user = getRow($sql, [$_SESSION['user_id']]);
            
            if ($user) {
                error_log("getCurrentUser: Database lookup successful for user ID: " . $_SESSION['user_id']);
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
            } else {
                error_log("getCurrentUser: User not found in database for ID: " . $_SESSION['user_id']);
            }
        } catch (Exception $e) {
            error_log("Error getting current user: " . $e->getMessage());
        }
        
        // If database lookup fails, don't return session data - it might be corrupted
        error_log("getCurrentUser: Database lookup failed, not returning session data");
        return null;
    }
    error_log("getCurrentUser: User not logged in");
    return null;
}

// Function to get user by ID
function getUserById($userId) {
    try {
        $sql = "SELECT id, username, user_type, full_name, email, phone, address, status, created_at FROM users WHERE id = ?";
        return getRow($sql, [$userId]);
    } catch (Exception $e) {
        error_log("Error getting user by ID: " . $e->getMessage());
        return false;
    }
}

// Function to get user by username
function getUserByUsername($username) {
    try {
        $sql = "SELECT id, username, user_type, full_name, email, phone, address, status, created_at FROM users WHERE username = ?";
        return getRow($sql, [$username]);
    } catch (Exception $e) {
        error_log("Error getting user by username: " . $e->getMessage());
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
        error_log("Error updating user profile: " . $e->getMessage());
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
        
        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
        return updateData($sql, [$hashedPassword, $userId]);
    } catch (Exception $e) {
        error_log("Error changing password: " . $e->getMessage());
        return false;
    }
}

// Function to logout user
function logout() {
    // Log logout activity if user was logged in
    if (isset($_SESSION['user_id'])) {
        try {
            $logout_sql = "INSERT INTO user_logins (user_id, login_time, ip_address, status) VALUES (?, NOW(), ?, 'logout')";
            insertData($logout_sql, [$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
        } catch (Exception $e) {
            error_log("Error logging logout: " . $e->getMessage());
        }
    }
    
    // Destroy session
    session_destroy();
    header('Location: index.php');
    exit();
}

// Function to check if user has specific role
function hasRole($role) {
    $user = getCurrentUser();
    return $user && $user['type'] === $role;
}

// Function to check if user is admin
function isAdmin() {
    return hasRole('admin');
}

// Function to check if user is faculty
function isFaculty() {
    return hasRole('faculty');
}

// Function to check if user is student
function isStudent() {
    return hasRole('student');
}

// Function to require specific role
function requireRole($role) {
    if (!hasRole($role)) {
        header('Location: dashboard.php');
        exit();
    }
}
?> 