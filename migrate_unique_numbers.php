<?php
require_once 'config/database.php';
try {
    $pdo = getDBConnection();
    // Add marksheet_no to student_enrollments
    $pdo->exec("ALTER TABLE student_enrollments ADD COLUMN marksheet_no VARCHAR(20) UNIQUE AFTER sub_course_id");
    echo "Successfully updated student_enrollments table.\n";
} catch (Exception $e) {
    echo "Note: student_enrollments update might have already been applied or failed: " . $e->getMessage() . "\n";
}
