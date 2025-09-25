<?php
require_once 'config/database.php';

try {
    // Get PDO connection
    $pdo = getDBConnection();
    
    // Read and execute the payment workflow SQL
    $sql = file_get_contents('database_payment_workflow.sql');
    
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
    
    echo "\n✅ Payment workflow database setup completed successfully!\n";
    echo "\nNew features added:\n";
    echo "- payment_pending status for enrollments\n";
    echo "- Payment verification fields in payments table\n";
    echo "- Payment methods table\n";
    echo "- Payment verification tracking\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up payment workflow: " . $e->getMessage() . "\n";
}
?>
