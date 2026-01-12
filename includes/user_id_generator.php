<?php
/**
 * User ID Generator Helper for GICT Application
 * 
 * Generates unique user IDs in the format: [user_type_prefix][current_year][max_id_3digits]
 * - f = faculty
 * - s = student  
 * - a = admin
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Generate a unique user ID for the specified user type
 * 
 * @param string $user_type The user type ('faculty', 'student', 'admin')
 * @return string|false The generated user ID or false on failure
 */
function generateUniqueUserId($user_type) {
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            return false;
        }
        
        // Map user types to prefixes and user_type_id values
        $type_mapping = [
            'faculty' => ['prefix' => 'f', 'type_id' => 3],
            'student' => ['prefix' => 's', 'type_id' => 2],
            'admin' => ['prefix' => 'a', 'type_id' => 1]
        ];
        
        if (!isset($type_mapping[$user_type])) {
            return false;
        }
        
        $prefix = $type_mapping[$user_type]['prefix'];
        $type_id = $type_mapping[$user_type]['type_id'];
        $current_year = date('Y');
        
        // Get the maximum ID for this user type
        $sql = "SELECT MAX(id) as max_id FROM users WHERE user_type_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$type_id]);
        $result = $stmt->fetch();
        
        $max_id = $result['max_id'] ?? 0;
        $next_id = $max_id + 1;
        
        // Format the ID with leading zeros to ensure 3 digits
        $formatted_id = str_pad($next_id, 3, '0', STR_PAD_LEFT);
        
        // Generate the final user ID
        $user_id = $prefix . $current_year . $formatted_id;
        
        // Check if this user ID already exists (shouldn't happen, but safety check)
        $check_sql = "SELECT id FROM users WHERE username = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$user_id]);
        
        if ($check_stmt->fetch()) {
            // If somehow this ID exists, try the next one
            $next_id++;
            $formatted_id = str_pad($next_id, 3, '0', STR_PAD_LEFT);
            $user_id = $prefix . $current_year . $formatted_id;
        }
        
        return $user_id;
        
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get the next available ID number for a user type
 * 
 * @param string $user_type The user type ('faculty', 'student', 'admin')
 * @return int|false The next available ID number or false on failure
 */
function getNextUserIdNumber($user_type) {
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            return false;
        }
        
        $type_mapping = [
            'faculty' => ['type_id' => 3],
            'student' => ['type_id' => 2],
            'admin' => ['type_id' => 1]
        ];
        
        if (!isset($type_mapping[$user_type])) {
            return false;
        }
        
        $type_id = $type_mapping[$user_type]['type_id'];
        
        $sql = "SELECT MAX(id) as max_id FROM users WHERE user_type_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$type_id]);
        $result = $stmt->fetch();
        
        return ($result['max_id'] ?? 0) + 1;
        
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Validate if a user ID follows the correct format
 * 
 * @param string $user_id The user ID to validate
 * @return bool True if valid, false otherwise
 */
function validateUserIdFormat($user_id) {
    // Pattern: [f/s/a][4-digit-year][3-digit-number]
    $pattern = '/^[fsa]\d{7}$/';
    return preg_match($pattern, $user_id) === 1;
}

/**
 * Extract information from a user ID
 * 
 * @param string $user_id The user ID to parse
 * @return array|false Array with type, year, and number, or false if invalid
 */
function parseUserId($user_id) {
    if (!validateUserIdFormat($user_id)) {
        return false;
    }
    
    $type_prefix = substr($user_id, 0, 1);
    $year = substr($user_id, 1, 4);
    $number = substr($user_id, 5, 3);
    
    $type_mapping = [
        'f' => 'faculty',
        's' => 'student',
        'a' => 'admin'
    ];
    
    return [
        'type' => $type_mapping[$type_prefix] ?? 'unknown',
        'year' => intval($year),
        'number' => intval($number)
    ];
}
?>
