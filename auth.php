<?php
// Include session manager
require_once 'includes/session_manager.php';

// Include database configuration
require_once 'config/database.php';

// Function to logout user
function logout() {
    // Log logout activity if user was logged in
    if (isset($_SESSION['user_id'])) {
        try {
            $logout_sql = "INSERT INTO user_logins (user_id, login_time, ip_address, status) VALUES (?, NOW(), ?, 'logout')";
            insertData($logout_sql, [$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
        } catch (Exception $e) {
            // Silent fail for security
        }
    }
    
    // Use secure logout from session manager
    secureLogout();
}


?> 