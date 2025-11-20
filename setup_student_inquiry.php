<?php
require_once 'config/database.php';

try {
    // Get PDO connection
    $pdo = getDBConnection();
    
    // Read and execute the student inquiry SQL
    $sql = file_get_contents('database_student_inquiry_update.sql');
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^(--|CREATE DATABASE|USE)/', $statement)) {
            try {
                $pdo->exec($statement);
                echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                // Skip if table already exists or column already exists
                if (strpos($e->getMessage(), 'already exists') !== false || 
                    strpos($e->getMessage(), 'Duplicate column') !== false) {
                    echo "⚠ Skipped (already exists): " . substr($statement, 0, 50) . "...\n";
                } else {
                    echo "✗ Error: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "\n✅ Student fields and inquiry system setup completed successfully!\n";
    echo "\nNew features added:\n";
    echo "- mother_name and father_name fields in users table\n";
    echo "- inquiries table for course inquiries\n";
    echo "- inquiry management system\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up student inquiry system: " . $e->getMessage() . "\n";
}
?>
