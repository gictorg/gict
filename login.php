<?php
// Include session manager first
require_once 'includes/session_manager.php';

// Include database configuration
require_once 'config/database.php';

// Check if user is already logged in
if (isLoggedIn()) {
    $dashboard_url = getDashboardUrl();
    header('Location: ' . $dashboard_url);
    exit();
}

// Get error message from session (flash message)
$error_message = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = 'Please enter both username and password';
        header('Location: login.php');
        exit();
    } else {
        try {
            // Test database connection first
            if (!testDBConnection()) {
                $_SESSION['login_error'] = 'Database connection failed. Please check configuration.';
                header('Location: login.php');
                exit();
            } else {
                // Get user from database with user type
                $sql = "SELECT u.id, u.username, u.password, u.full_name, u.email, u.user_type_id 
                        FROM users u 
                        WHERE u.username = ? AND u.status = 'active'";
                $user = getRow($sql, [$username]);
                
                // Map user_type_id to user_type name
                if ($user) {
                    switch ($user['user_type_id']) {
                        case 1:
                            $user['user_type'] = 'admin';
                            break;
                        case 2:
                            $user['user_type'] = 'student';
                            break;
                        case 3:
                            $user['user_type'] = 'faculty';
                            break;
                        default:
                            $user['user_type'] = 'unknown';
                    }
                }
                
                if ($user) {
                    $passwordValid = password_verify($password, $user['password']);
                    
                    if ($passwordValid) {
                        // Login successful
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['user_type'] = $user['user_type'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['email'] = $user['email'];
                        
                        // Debug: Log what's being set in session
                        error_log("Login Debug - User data: " . print_r($user, true));
                        error_log("Login Debug - Session after login: " . print_r($_SESSION, true));
                        
                        // Log successful login (commented out as user_logins table doesn't exist in new schema)
                        // $login_sql = "INSERT INTO user_logins (user_id, login_time, ip_address) VALUES (?, NOW(), ?)";
                        // insertData($login_sql, [$user['id'], $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
                        
                        // Redirect to appropriate dashboard
                        $dashboard_url = getDashboardUrl();
                        header('Location: ' . $dashboard_url);
                        exit();
                    } else {
                        $_SESSION['login_error'] = 'Invalid password';
                        header('Location: login.php');
                        exit();
                    }
                } else {
                    $_SESSION['login_error'] = 'User not found or inactive';
                    header('Location: login.php');
                    exit();
                }
            }
        } catch (Exception $e) {
            $_SESSION['login_error'] = 'Login failed. Please try again.';
            header('Location: login.php');
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GICT</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Arial', sans-serif;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        
        .login-logo {
            width: 120px;
            height: 120px;
            margin: 0 auto 30px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: #667eea;
        }
        
        .login-title {
            color: #333;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: bold;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .login-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
        }
        
        .error-message {
            background: #ff6b6b;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <i class="fas fa-graduation-cap"></i>
        </div>
        
        <h1 class="login-title">Welcome to GICT</h1>
        <p style="color: #666; margin-bottom: 30px;">Please login to continue</p>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="login-btn">Login</button>
        </form>
        

    </div>
</body>
</html> 