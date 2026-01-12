<?php
session_start();
require_once '../config/database.php';
require_once '../generate_marksheet.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || !hasRole('student')) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$course_id = $_GET['course_id'] ?? null;

if (!$course_id) {
    header('Location: dashboard.php');
    exit;
}

// Verify student is enrolled in this course
$enrollment = getRow("
    SELECT * FROM student_enrollments 
    WHERE user_id = ? AND course_id = ?
", [$user_id, $course_id]);

if (!$enrollment) {
    header('Location: dashboard.php');
    exit;
}

// Check if course is completed
if ($enrollment['status'] !== 'completed') {
    header('Location: dashboard.php?error=course_not_completed');
    exit;
}

try {
    // Initialize marksheet generator
    $generator = new MarksheetGenerator();
    
    // Generate or get existing marksheet
    $result = $generator->generateMarksheet($user_id, $course_id);
    
    if ($result['success']) {
        // Download the marksheet
        $generator->downloadMarksheet($result['file_path']);
    } else {
        // Redirect with error
        header('Location: dashboard.php?error=marksheet_generation_failed');
        exit;
    }
    
} catch (Exception $e) {
    // Log error and redirect
    header('Location: dashboard.php?error=marksheet_generation_failed');
    exit;
}
?>
