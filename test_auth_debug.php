<?php
// Simple authentication debug file
session_start();

echo "<h2>Session Debug Information</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session Data:\n";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Authentication Test</h2>";

// Include session manager
require_once 'includes/session_manager.php';

echo "isLoggedIn(): " . (isLoggedIn() ? 'true' : 'false') . "<br>";
echo "isStudent(): " . (isStudent() ? 'true' : 'false') . "<br>";
echo "isAdmin(): " . (isAdmin() ? 'true' : 'false') . "<br>";

if (isLoggedIn()) {
    echo "<h3>User Information</h3>";
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
    echo "Username: " . $_SESSION['username'] . "<br>";
    echo "User Type: " . $_SESSION['user_type'] . "<br>";
    echo "Full Name: " . $_SESSION['full_name'] . "<br>";
    
    echo "<h3>Dashboard URL</h3>";
    echo "Dashboard URL: " . getDashboardUrl() . "<br>";
}

echo "<h3>Test Links</h3>";
echo '<a href="login.php">Go to Login</a><br>';
echo '<a href="student/dashboard.php">Go to Student Dashboard</a><br>';
echo '<a href="dashboard.php">Go to Admin Dashboard</a><br>';
echo '<a href="logout.php">Logout</a><br>';
?>
