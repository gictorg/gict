<?php
/**
 * Free Email Helper for GICT
 * Uses PHP's built-in mail() function - no external services required
 */

// Email configuration
define('GICT_EMAIL_FROM', 'noreply@gict.edu.in');
define('GICT_EMAIL_FROM_NAME', 'GICT Institute');
define('GICT_WEBSITE_URL', 'http://gict.edu.in');

/**
 * Send password reset email
 */
function sendPasswordResetEmail($userEmail, $userName, $resetLink) {
    $subject = "Password Reset Request - GICT Institute";
    
    $message = "
    <html>
    <head>
        <title>Password Reset Request</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #667eea; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .button { display: inline-block; background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔐 Password Reset Request</h1>
                <p>GICT Institute</p>
            </div>
            
            <div class='content'>
                <p>Hello <strong>$userName</strong>,</p>
                
                <p>We received a request to reset your password for your GICT Institute account (username-based reset).</p>
                
                <p>If you made this request, please click the button below to reset your password:</p>
                
                <div style='text-align: center;'>
                    <a href='$resetLink' class='button'>Reset My Password</a>
                </div>
                
                <p>Or copy and paste this link into your browser:</p>
                <p style='word-break: break-all; background: #f0f0f0; padding: 10px; border-radius: 3px; font-family: monospace;'>$resetLink</p>
                
                <div class='warning'>
                    <strong>⚠️ Important:</strong>
                    <ul>
                        <li>This link will expire in 1 hour</li>
                        <li>If you didn't request this, please ignore this email</li>
                        <li>Your password will remain unchanged</li>
                    </ul>
                </div>
                
                <p>For security reasons, this link can only be used once.</p>
                
                <p>If you have any questions, please contact our support team.</p>
                
                <p>Best regards,<br>
                <strong>GICT Institute Team</strong></p>
            </div>
            
            <div class='footer'>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>&copy; " . date('Y') . " GICT Institute. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Email headers for HTML email
    $headers = array(
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . GICT_EMAIL_FROM_NAME . ' <' . GICT_EMAIL_FROM . '>',
        'Reply-To: ' . GICT_EMAIL_FROM,
        'X-Mailer: PHP/' . phpversion()
    );
    
    // Send email using PHP's built-in mail() function
    $result = mail($userEmail, $subject, $message, implode("\r\n", $headers));
    
    if ($result) {
        error_log("Password reset email sent successfully to: $userEmail");
        return true;
    } else {
        // Get detailed error information
        $error = error_get_last();
        error_log("Failed to send password reset email to: $userEmail. Error: " . ($error['message'] ?? 'Unknown error'));
        
        // Check common email configuration issues
        if (!function_exists('mail')) {
            error_log("mail() function not available");
        }
        
        $sendmail_path = ini_get('sendmail_path');
        if (empty($sendmail_path)) {
            error_log("sendmail_path not configured in php.ini");
        }
        
        return false;
    }
}

/**
 * Send welcome email to new users
 */
function sendWelcomeEmail($userEmail, $userName, $username, $userType) {
    $subject = "Welcome to GICT Institute - Account Created Successfully";
    
    $message = "
    <html>
    <head>
        <title>Welcome to GICT Institute</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #28a745; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .credentials { background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 Welcome to GICT Institute!</h1>
                <p>Your account has been created successfully</p>
            </div>
            
            <div class='content'>
                <p>Hello <strong>$userName</strong>,</p>
                
                <p>Welcome to GICT Institute! Your account has been created successfully.</p>
                
                <div class='credentials'>
                    <h3>Your Login Credentials:</h3>
                    <p><strong>Username:</strong> $username</p>
                    <p><strong>Account Type:</strong> " . ucfirst($userType) . "</p>
                    <p><strong>Login URL:</strong> <a href='" . GICT_WEBSITE_URL . "/login.php'>" . GICT_WEBSITE_URL . "/login.php</a></p>
                </div>
                
                <p>You can now access your personalized dashboard and explore all the features available to " . strtolower($userType) . "s.</p>
                
                <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
                
                <p>Best regards,<br>
                <strong>GICT Institute Team</strong></p>
            </div>
            
            <div class='footer'>
                <p>&copy; " . date('Y') . " GICT Institute. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Email headers
    $headers = array(
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . GICT_EMAIL_FROM_NAME . ' <' . GICT_EMAIL_FROM . '>',
        'Reply-To: ' . GICT_EMAIL_FROM,
        'X-Mailer: PHP/' . phpversion()
    );
    
    return mail($userEmail, $subject, $message, implode("\r\n", $headers));
}

/**
 * Test email functionality
 */
function testEmailFunctionality() {
    $testEmail = 'test@example.com';
    $testSubject = 'GICT Email Test';
    $testMessage = 'This is a test email from GICT Institute to verify email functionality.';
    
    $headers = array(
        'MIME-Version: 1.0',
        'Content-type: text/plain; charset=UTF-8',
        'From: ' . GICT_EMAIL_FROM_NAME . ' <' . GICT_EMAIL_FROM . '>',
        'X-Mailer: PHP/' . phpversion()
    );
    
    $result = mail($testEmail, $testSubject, $testMessage, implode("\r\n", $headers));
    
    if ($result) {
        return "✓ Email functionality is working (test email sent to $testEmail)";
    } else {
        return "❌ Email functionality failed. Check your server's mail configuration.";
    }
}

/**
 * Get email configuration status
 */
function getEmailConfigStatus() {
    $status = array();
    
    // Check if mail() function exists
    $status['mail_function'] = function_exists('mail') ? '✓ Available' : '❌ Not available';
    
    // Check if sendmail path is configured
    $sendmail_path = ini_get('sendmail_path');
    $status['sendmail_path'] = $sendmail_path ? "✓ Configured: $sendmail_path" : '❌ Not configured';
    
    // Check SMTP settings
    $smtp_host = ini_get('SMTP');
    $status['smtp_host'] = $smtp_host ? "✓ SMTP Host: $smtp_host" : '❌ SMTP not configured';
    
    // Check if we can send emails
    $status['test_result'] = testEmailFunctionality();
    
    return $status;
}
?>
