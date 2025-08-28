<?php
// Test faculty login and dashboard access
echo "<h1>Faculty Dashboard Test</h1>";

// Include session manager
require_once 'includes/session_manager.php';

echo "<h2>Session Status</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "isLoggedIn(): " . (isLoggedIn() ? 'true' : 'false') . "<br>";
echo "isFaculty(): " . (isFaculty() ? 'true' : 'false') . "<br>";

if (isLoggedIn()) {
    echo "<h2>Current User</h2>";
    echo "<pre>" . print_r($_SESSION, true) . "</pre>";
    
    echo "<h2>Dashboard URL</h2>";
    echo "Dashboard URL: " . getDashboardUrl() . "<br>";
    
    echo "<h2>Test Links</h2>";
    echo '<a href="faculty/dashboard.php">Go to Faculty Dashboard</a><br>';
    echo '<a href="login.php">Go to Login</a><br>';
} else {
    echo "<h2>Not Logged In</h2>";
    echo '<a href="login.php">Go to Login</a><br>';
    echo '<p>Use faculty credentials to test:</p>';
    echo '<ul>';
    echo '<li>Username: faculty1</li>';
    echo '<li>Password: password</li>';
    echo '</ul>';
}

echo "<h2>Database Test</h2>";
try {
    require_once 'config/database.php';
    
    // Test database connection
    if (testDBConnection()) {
        echo "✅ Database connection successful<br>";
        
        // Check for faculty users
        $facultySql = "SELECT id, username, full_name, email, user_type_id, status FROM users WHERE user_type_id = 3";
        $facultyUsers = getRows($facultySql);
        
        echo "✅ Found " . count($facultyUsers) . " faculty users:<br>";
        foreach ($facultyUsers as $faculty) {
            echo "- " . $faculty['username'] . " (" . $faculty['full_name'] . ") - " . $faculty['status'] . "<br>";
        }
        
        // Check for courses
        $coursesSql = "SELECT COUNT(*) as count FROM courses WHERE status = 'active'";
        $coursesResult = getRow($coursesSql);
        echo "✅ Active courses: " . ($coursesResult['count'] ?? 0) . "<br>";
        
        // Check for enrollments
        $enrollmentsSql = "SELECT COUNT(*) as count FROM student_enrollments WHERE status = 'enrolled'";
        $enrollmentsResult = getRow($enrollmentsSql);
        echo "✅ Active enrollments: " . ($enrollmentsResult['count'] ?? 0) . "<br>";
        
    } else {
        echo "❌ Database connection failed<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}
?>
