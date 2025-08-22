<?php
/**
 * Session Manager for GICT Institute
 * Handles session initialization, user authentication, and security
 */

// Prevent multiple session starts
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    
    session_start();
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

/**
 * Get current user data from session
 * @return array|null
 */
function getCurrentUserFromSession() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'user_type' => $_SESSION['user_type'] ?? null
    ];
}

/**
 * Check if user has specific role
 * @param string $role
 * @return bool
 */
function hasRole($role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    return $_SESSION['user_type'] === $role;
}

/**
 * Check if user is admin
 * @return bool
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Check if user is student
 * @return bool
 */
function isStudent() {
    return hasRole('student');
}

/**
 * Check if user is faculty
 * @return bool
 */
function isFaculty() {
    return hasRole('faculty');
}

/**
 * Get user display name
 * @return string
 */
function getUserDisplayName() {
    if (!isLoggedIn()) {
        return '';
    }
    
    if (isset($_SESSION['full_name']) && !empty($_SESSION['full_name'])) {
        return $_SESSION['full_name'];
    }
    
    if (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
        return $_SESSION['username'];
    }
    
    return 'User';
}

/**
 * Get dashboard URL based on user type
 * @return string
 */
function getDashboardUrl() {
    if (!isLoggedIn()) {
        return 'login.php';
    }
    
    switch ($_SESSION['user_type']) {
        case 'admin':
            return 'dashboard.php';
        case 'student':
        case 'faculty':
            return 'student/dashboard.php';
        default:
            return 'login.php';
    }
}

/**
 * Secure logout function
 */
function secureLogout() {
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to homepage
    header('Location: index.php');
    exit();
}

/**
 * Regenerate session ID for security
 */
function regenerateSession() {
    if (isLoggedIn()) {
        session_regenerate_id(true);
    }
}

/**
 * Set session timeout (30 minutes)
 */
function setSessionTimeout() {
    $timeout = 30 * 60; // 30 minutes
    
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        secureLogout();
    }
    
    $_SESSION['last_activity'] = time();
}

/**
 * Validate session data integrity
 */
function validateSession() {
    if (isLoggedIn()) {
        // Check if required session variables exist
        $required_vars = ['user_id', 'user_type', 'username'];
        foreach ($required_vars as $var) {
            if (!isset($_SESSION[$var]) || empty($_SESSION[$var])) {
                secureLogout();
                return false;
            }
        }
        
        // Check session timeout
        setSessionTimeout();
        
        // Regenerate session ID periodically
        if (isset($_SESSION['last_regeneration']) && (time() - $_SESSION['last_regeneration'] > 300)) {
            regenerateSession();
            $_SESSION['last_regeneration'] = time();
        } elseif (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        }
        
        return true;
    }
    
    return false;
}

// Validate session on every request
validateSession();
?>
