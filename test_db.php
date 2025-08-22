<?php
/**
 * Database Test File for GICT Application
 * 
 * This file tests the database connection and shows database status
 */

// Include database configuration
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>GICT Database Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>GICT Database Connection Test</h1>";

// Test database connection
echo "<h2>Database Connection Status</h2>";
if (testDBConnection()) {
    echo "<div class='status success'>✓ Database connection successful!</div>";
} else {
    echo "<div class='status error'>❌ Database connection failed!</div>";
    echo "<div class='status info'>Please check your MySQL server and database configuration.</div>";
    echo "<p><strong>Common issues:</strong></p>";
    echo "<ul>";
    echo "<li>MySQL server is not running</li>";
    echo "<li>Database credentials are incorrect</li>";
    echo "<li>Database 'gict_db' doesn't exist</li>";
    echo "<li>MySQL user doesn't have proper permissions</li>";
    echo "</ul>";
    echo "<p><strong>To fix:</strong></p>";
    echo "<ol>";
    echo "<li>Start MySQL server: <code>brew services start mysql</code> (macOS) or <code>sudo systemctl start mysql</code> (Linux)</li>";
    echo "<li>Create database: <code>mysql -u root -p -e \"CREATE DATABASE gict_db;\"</code></li>";
    echo "<li>Run setup script: <code>php setup_database.php</code></li>";
    echo "</ol>";
    echo "</body></html>";
    exit;
}

// Get database information
try {
    $pdo = getDBConnection();
    
    // Get database version
    $version = $pdo->query('SELECT VERSION() as version')->fetch();
    echo "<div class='status info'>MySQL Version: " . $version['version'] . "</div>";
    
    // Get database size
    $size = $pdo->query("SELECT 
        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'DB Size in MB'
        FROM information_schema.tables 
        WHERE table_schema = '" . DB_NAME . "'")->fetch();
    echo "<div class='status info'>Database Size: " . $size['DB Size in MB'] . " MB</div>";
    
    // Get table information
    echo "<h2>Database Tables</h2>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll();
    
    if (count($tables) > 0) {
        echo "<table>";
        echo "<tr><th>Table Name</th><th>Records</th><th>Status</th></tr>";
        
        foreach ($tables as $table) {
            $tableName = $table['Tables_in_' . DB_NAME];
            $count = $pdo->query("SELECT COUNT(*) as count FROM `$tableName`")->fetch();
            echo "<tr>";
            echo "<td>$tableName</td>";
            echo "<td>" . $count['count'] . "</td>";
            echo "<td>✓ Active</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='status info'>No tables found. Run <code>php setup_database.php</code> to create tables.</div>";
    }
    
    // Show sample data from users table
    if ($pdo->query("SHOW TABLES LIKE 'users'")->rowCount() > 0) {
        echo "<h2>Sample Users</h2>";
        $users = $pdo->query("SELECT username, user_type, email, full_name, created_at FROM users LIMIT 5")->fetchAll();
        
        if (count($users) > 0) {
            echo "<table>";
            echo "<tr><th>Username</th><th>Type</th><th>Email</th><th>Full Name</th><th>Created</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($user['username']) . "</td>";
                echo "<td>" . htmlspecialchars($user['user_type']) . "</td>";
                echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
                echo "<td>" . htmlspecialchars($user['created_at']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
} catch (PDOException $e) {
    echo "<div class='status error'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<h2>Next Steps</h2>";
echo "<div class='status info'>";
echo "<p><strong>Your GICT application is now connected to MySQL!</strong></p>";
echo "<p>You can:</p>";
echo "<ul>";
echo "<li>Update the login system to use database authentication</li>";
echo "<li>Store course information in the database</li>";
echo "<li>Manage student enrollments and certificates</li>";
echo "<li>Track payments and transactions</li>";
echo "<li>Store news, events, and gallery data</li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";
?>
