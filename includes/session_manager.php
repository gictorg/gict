<?php
// Start session with secure parameters
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/**
 * Validate user session and check if user is logged in
 */
function validateSession() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        return false;
    }
    
    // Check if user still exists in database
    $user = getRow("SELECT * FROM users WHERE id = ? AND status = 'active'", [$_SESSION['user_id']]);
    if (!$user) {
        return false;
    }
    
    // Update session with current user data
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['institute_id'] = $user['institute_id'];
    $_SESSION['full_name'] = $user['full_name'];
    
    return true;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return validateSession();
}

/**
 * Check if user is super admin
 */
function isSuperAdmin() {
    return isLoggedIn() && $_SESSION['user_type'] === 'super_admin';
}

/**
 * Check if user is admin (institute admin)
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['user_type'] === 'admin';
}

/**
 * Check if user is student
 */
function isStudent() {
    return isLoggedIn() && $_SESSION['user_type'] === 'student';
}

/**
 * Check if user is faculty
 */
function isFaculty() {
    return isLoggedIn() && $_SESSION['user_type'] === 'faculty';
}

/**
 * Get dashboard URL based on user type
 */
function getDashboardUrl() {
    if (!isLoggedIn()) {
        return '/login.php';
    }
    
    switch ($_SESSION['user_type']) {
        case 'super_admin':
            return '/super-admin/dashboard.php';
        case 'admin':
            return '/admin/dashboard.php';
        case 'student':
            return '/student/dashboard.php';
        case 'faculty':
            return '/faculty/dashboard.php';
        default:
            return '/login.php';
    }
}

/**
 * Get current user's institute ID
 */
function getCurrentInstituteId() {
    return $_SESSION['institute_id'] ?? null;
}

/**
 * Get current user's institute data
 */
function getCurrentInstitute() {
    $institute_id = getCurrentInstituteId();
    if (!$institute_id) {
        return null;
    }
    
    return getRow("SELECT * FROM institutes WHERE id = ? AND status = 'active'", [$institute_id]);
}

/**
 * Check if user has access to specific institute
 */
function hasInstituteAccess($institute_id) {
    if (isSuperAdmin()) {
        return true; // Super admin has access to all institutes
    }
    
    return getCurrentInstituteId() == $institute_id;
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
    header('Location: /index.php');
    exit;
}

/**
 * Get user data by ID
 */
function getUserById($user_id) {
    return getRow("SELECT * FROM users WHERE id = ?", [$user_id]);
}

/**
 * Get institute data by ID
 */
function getInstituteById($institute_id) {
    return getRow("SELECT * FROM institutes WHERE id = ?", [$institute_id]);
}

/**
 * Get all active institutes (for super admin)
 */
function getAllInstitutes() {
    return getRows("SELECT * FROM institutes WHERE status = 'active' ORDER BY name");
}

/**
 * Get users by institute
 */
function getUsersByInstitute($institute_id, $user_type = null) {
    $sql = "SELECT * FROM users WHERE institute_id = ?";
    $params = [$institute_id];
    
    if ($user_type) {
        $sql .= " AND user_type = ?";
        $params[] = $user_type;
    }
    
    $sql .= " ORDER BY full_name";
    return getRows($sql, $params);
}

/**
 * Get courses by institute
 */
function getCoursesByInstitute($institute_id) {
    return getRows("SELECT * FROM courses WHERE institute_id = ? AND status = 'active' ORDER BY name", [$institute_id]);
}

/**
 * Get sub-courses by course
 */
function getSubCoursesByCourse($course_id) {
    return getRows("SELECT * FROM sub_courses WHERE course_id = ? AND status = 'active' ORDER BY name", [$course_id]);
}

/**
 * Get student enrollments with sub-course details
 */
function getStudentEnrollments($user_id) {
    return getRows("
        SELECT se.*, sc.name as sub_course_name, sc.fee as sub_course_fee, 
               c.name as course_name, c.category
        FROM student_enrollments se
        JOIN sub_courses sc ON se.sub_course_id = sc.id
        JOIN courses c ON sc.course_id = c.id
        WHERE se.user_id = ?
        ORDER BY se.enrollment_date DESC
    ", [$user_id]);
}

/**
 * Get student payments with sub-course details
 */
function getStudentPayments($user_id) {
    return getRows("
        SELECT p.*, sc.name as sub_course_name, c.name as course_name
        FROM payments p
        JOIN sub_courses sc ON p.sub_course_id = sc.id
        JOIN courses c ON sc.course_id = c.id
        WHERE p.user_id = ?
        ORDER BY p.payment_date DESC
    ", [$user_id]);
}

/**
 * Check if user can access specific resource
 */
function canAccessResource($resource_type, $resource_id) {
    if (isSuperAdmin()) {
        return true; // Super admin can access everything
    }
    
    switch ($resource_type) {
        case 'institute':
            return hasInstituteAccess($resource_id);
        case 'user':
            $user = getUserById($resource_id);
            return $user && hasInstituteAccess($user['institute_id']);
        case 'course':
            $course = getRow("SELECT * FROM courses WHERE id = ?", [$resource_id]);
            return $course && hasInstituteAccess($course['institute_id']);
        case 'sub_course':
            $sub_course = getRow("
                SELECT c.institute_id FROM sub_courses sc 
                JOIN courses c ON sc.course_id = c.id 
                WHERE sc.id = ?
            ", [$resource_id]);
            return $sub_course && hasInstituteAccess($sub_course['institute_id']);
        default:
            return false;
    }
}

/**
 * Get user by username
 */
function getUserByUsername($username) {
    try {
        $sql = "SELECT id, username, user_type, full_name, email, phone, address, status, created_at FROM users WHERE username = ?";
        return getRow($sql, [$username]);
    } catch (Exception $e) {
        // Silent fail for security
        return false;
    }
}

/**
 * Update user profile
 */
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

/**
 * Change user password
 */
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
        // Silent fail for security
        return false;
    }
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    return $_SESSION['user_type'] === $role;
}

/**
 * Require specific role for access
 */
function requireRole($role) {
    if (!hasRole($role)) {
        header('Location: dashboard.php');
        exit();
    }
}

/**
 * Get current user info from database
 */
function getCurrentUser() {
    if (isLoggedIn()) {
        try {
            $sql = "SELECT id, username, user_type, full_name, email, phone, address, status FROM users WHERE id = ? AND status = 'active'";
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

/**
 * Require login (redirect if not logged in)
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}
