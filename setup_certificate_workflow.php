<?php
require_once 'config/database.php';

try {
    // Get PDO connection
    $pdo = getDBConnection();
    
    // Read and execute the certificate workflow SQL
    $sql = file_get_contents('database_certificate_workflow.sql');
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^(--|CREATE DATABASE|USE)/', $statement)) {
            try {
                $pdo->exec($statement);
                echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                // Skip if table already exists
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    echo "⚠ Skipped (already exists): " . substr($statement, 0, 50) . "...\n";
                } else {
                    echo "✗ Error: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "\n✅ Certificate workflow database setup completed successfully!\n";
    echo "\nNew tables created:\n";
    echo "- course_subjects (for tracking subjects in each course)\n";
    echo "- student_marks (for storing student marks)\n";
    echo "- certificates (for generated certificates)\n";
    echo "- faculty_courses (for assigning courses to faculty)\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up certificate workflow: " . $e->getMessage() . "\n";
}
?>
