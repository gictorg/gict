<?php
require_once 'includes/session_manager.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . getDashboardUrl());
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Get user with institute information
        $user = getRow("
            SELECT u.*, i.name as institute_name, i.slug as institute_slug 
            FROM users u 
            LEFT JOIN institutes i ON u.institute_id = i.id 
            WHERE u.username = ? AND u.status = 'active'
        ", [$username]);
        
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['institute_id'] = $user['institute_id'];
            $_SESSION['institute_name'] = $user['institute_name'];
            $_SESSION['institute_slug'] = $user['institute_slug'];
            
            // Redirect to appropriate dashboard
            header('Location: ' . getDashboardUrl());
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GICT Institute</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        
        .login-header {
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #333;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #666;
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .login-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
        }
        
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        
        .back-home {
            margin-top: 20px;
        }
        
        .back-home a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-home a:hover {
            text-decoration: underline;
        }
        
        .demo-credentials {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 0.85rem;
            color: #6b7280;
        }
        
        .demo-credentials h4 {
            margin: 0 0 10px 0;
            color: #374151;
            font-size: 0.9rem;
        }
        
        .demo-credentials ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .demo-credentials li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1><i class="fas fa-graduation-cap"></i> GICT Institute</h1>
                <p>Franchise Management System</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="demo-credentials">
                <h4><i class="fas fa-info-circle"></i> Demo Credentials:</h4>
                <ul>
                    <li><strong>Super Admin:</strong> superadmin / password</li>
                    <li><strong>Main Branch Admin:</strong> admin_main / password</li>
                    <li><strong>North Branch Admin:</strong> admin_north / password</li>
                    <li><strong>Tailoring Admin:</strong> admin_tailoring / password</li>
                    <li><strong>Student:</strong> student1 / password</li>
                </ul>
            </div>
            
            <div class="back-home">
                <a href="index.php"><i class="fas fa-home"></i> Back to Homepage</a>
            </div>
        </div>
    </div>
</body>
</html> 