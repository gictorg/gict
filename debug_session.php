<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Debug - GICT</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Session Debug Information</h1>
    
    <div class="debug-section info">
        <h3>Current Session Status</h3>
        <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
        <p><strong>Session Status:</strong> <?php echo session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive'; ?></p>
    </div>
    
    <div class="debug-section <?php echo !empty($_SESSION) ? 'success' : 'error'; ?>">
        <h3>Session Data</h3>
        <?php if (!empty($_SESSION)): ?>
            <pre><?php print_r($_SESSION); ?></pre>
        <?php else: ?>
            <p>No session data found.</p>
        <?php endif; ?>
    </div>
    
    <div class="debug-section info">
        <h3>Database Connection Test</h3>
        <?php
        if (file_exists('config/database.php')) {
            require_once 'config/database.php';
            if (testDBConnection()) {
                echo '<p class="success">✓ Database connection successful</p>';
                
                // Test user lookup if session has user_id
                if (isset($_SESSION['user_id'])) {
                    $sql = "SELECT id, username, user_type, full_name, status FROM users WHERE id = ?";
                    $user = getRow($sql, [$_SESSION['user_id']]);
                    if ($user) {
                        echo '<p class="success">✓ User found in database</p>';
                        echo '<pre>' . print_r($user, true) . '</pre>';
                    } else {
                        echo '<p class="error">❌ User not found in database</p>';
                    }
                }
            } else {
                echo '<p class="error">❌ Database connection failed</p>';
            }
        } else {
            echo '<p class="error">❌ Database config file not found</p>';
        }
        ?>
    </div>
    
    <div class="debug-section">
        <h3>Actions</h3>
        <p><a href="login.php">Go to Login</a></p>
        <p><a href="dashboard.php">Go to Dashboard</a></p>
        <p><a href="logout.php">Logout</a></p>
        <p><a href="debug_session.php">Refresh this page</a></p>
    </div>
</body>
</html>
