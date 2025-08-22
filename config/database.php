<?php
/**
 * Database Configuration for GICT Application
 * 
 * This file contains database connection settings and helper functions
 * for the Global Institute of Computer Technology application.
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'gict_db');
define('DB_USER', 'root');
define('DB_PASS', 'root_pass');
define('DB_CHARSET', 'utf8mb4');

// Create database connection
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return false;
    }
}

// Test database connection
function testDBConnection() {
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT 1");
            return true;
        } catch (PDOException $e) {
            error_log("Database test failed: " . $e->getMessage());
            return false;
        }
    }
    return false;
}

// Close database connection
function closeDBConnection($pdo) {
    $pdo = null;
}

// Execute a query and return results
function executeQuery($sql, $params = []) {
    $pdo = getDBConnection();
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query execution failed: " . $e->getMessage());
        return false;
    }
}

// Get a single row
function getRow($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    if ($stmt) {
        return $stmt->fetch();
    }
    return false;
}

// Get multiple rows
function getRows($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    if ($stmt) {
        return $stmt->fetchAll();
    }
    return [];
}

// Insert data and return last insert ID
function insertData($sql, $params = []) {
    $pdo = getDBConnection();
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Insert failed: " . $e->getMessage());
        return false;
    }
}

// Update data and return affected rows
function updateData($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    if ($stmt) {
        return $stmt->rowCount();
    }
    return 0;
}

// Delete data and return affected rows
function deleteData($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    if ($stmt) {
        return $stmt->rowCount();
    }
    return 0;
}
?>
