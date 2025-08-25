<?php
/**
 * Database Configuration for GICT Institute Franchise Model
 * Updated to use gict_franchise database
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'gict_franchise');  // Updated to use new franchise database
define('DB_USER', 'root');
define('DB_PASS', 'root_pass');
define('DB_CHARSET', 'utf8mb4');

// ImgBB API Configuration
define('IMGBB_API_KEY', '3acdbb8d9ce98d6f3ff4e61a5902c75a');
define('IMGBB_EXPIRATION', 0); // 0 = never expire

// Application Configuration
define('SITE_NAME', 'GICT Institute');
define('SITE_URL', 'http://localhost/gict');
define('UPLOAD_MAX_SIZE', 200 * 1024); // 200KB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png']);

// PDO Database Connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

/**
 * Test database connection
 */
function testDBConnection() {
    global $pdo;
    try {
        $pdo->query("SELECT 1");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get a single row from database
 */
function getRow($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get multiple rows from database
 */
function getRows($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Insert data into database
 */
function insertData($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update data in database
 */
function updateData($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete data from database
 */
function deleteData($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Execute raw SQL query
 */
function executeQuery($sql) {
    global $pdo;
    try {
        return $pdo->exec($sql);
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get database statistics
 */
function getDatabaseStats() {
    $stats = [];
    
    // Get table counts
    $tables = ['institutes', 'users', 'courses', 'sub_courses', 'student_enrollments', 'payments', 'student_documents'];
    
    foreach ($tables as $table) {
        $result = getRow("SELECT COUNT(*) as count FROM $table");
        $stats[$table] = $result ? $result['count'] : 0;
    }
    
    return $stats;
}

/**
 * Check if database exists
 */
function databaseExists($dbName) {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbName'");
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Create database if it doesn't exist
 */
function createDatabase($dbName) {
    global $pdo;
    try {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        return true;
    } catch (PDOException $e) {
        error_log("Database Creation Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get database connection status
 */
function getConnectionStatus() {
    global $pdo;
    try {
        $pdo->query("SELECT 1");
        return [
            'status' => 'connected',
            'database' => DB_NAME,
            'host' => DB_HOST,
            'charset' => DB_CHARSET
        ];
    } catch (PDOException $e) {
        return [
            'status' => 'error',
            'message' => $e->getMessage(),
            'database' => DB_NAME,
            'host' => DB_HOST
        ];
    }
}

// Test connection on load
if (!testDBConnection()) {
    error_log("Warning: Database connection failed during initialization");
}
?>
