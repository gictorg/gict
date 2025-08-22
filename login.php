<?php
session_start();

// Include database configuration
require_once 'config/database.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] === 'admin') {
        header('Location: dashboard.php');
    } else {
        header('Location: student/dashboard.php');
    }
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password';
    } else {
        try {
            // Test database connection first
            if (!testDBConnection()) {
                $error_message = 'Database connection failed. Please check configuration.';
                error_log("Login: Database connection failed");
            } else {
                // Get user from database
                $sql = "SELECT id, username, password, user_type, full_name, email FROM users WHERE username = ? AND status = 'active'";
                $user = getRow($sql, [$username]);
                
                // Debug logging
                error_log("Login attempt for username: " . $username);
                error_log("User found: " . ($user ? 'Yes' : 'No'));
                
                if ($user) {
                    error_log("User data: " . json_encode($user));
                    $passwordValid = password_verify($password, $user['password']);
                    error_log("Password valid: " . ($passwordValid ? 'Yes' : 'No'));
                    
                    if ($passwordValid) {
                        // Login successful
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['user_type'] = $user['user_type'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['email'] = $user['email'];
                        
                        error_log("Session set: " . json_encode($_SESSION));
                        
                        // Log successful login
                        $login_sql = "INSERT INTO user_logins (user_id, login_time, ip_address) VALUES (?, NOW(), ?)";
                        insertData($login_sql, [$user['id'], $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
                        
                        error_log("Redirecting to dashboard...");
                        
                        // Debug: Check if headers were already sent
                        if (headers_sent($file, $line)) {
                            error_log("Headers already sent in $file on line $line");
                            $redirect_url = $user['user_type'] === 'admin' ? 'dashboard.php' : 'student/dashboard.php';
                            echo "<script>window.location.href = '{$redirect_url}';</script>";
                            echo "<p>Redirecting to dashboard... <a href='{$redirect_url}'>Click here if not redirected automatically</a></p>";
                            exit();
                        } else {
                            // Redirect based on user type
                            if ($user['user_type'] === 'admin') {
                                header('Location: dashboard.php');
                            } else {
                                header('Location: student/dashboard.php');
                            }
                            exit();
                        }
                    } else {
                        $error_message = 'Invalid password';
                        error_log("Login: Invalid password for user: " . $username);
                    }
                } else {
                    $error_message = 'User not found or inactive';
                    error_log("Login: User not found or inactive: " . $username);
                }
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error_message = 'Login failed. Please try again.';
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
        
        <?php if ($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
            <div style="background: #e3f2fd; color: #1976d2; padding: 10px; border-radius: 5px; margin-bottom: 20px; font-size: 14px;">
                <strong>Debug:</strong> Form submitted. Processing login...
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
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