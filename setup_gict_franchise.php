<?php
/**
 * GICT Franchise Database Setup Script
 * This script helps you set up the new gict_franchise database
 */

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>GICT Franchise Database Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #667eea;
        }
        .header h1 {
            color: #667eea;
            margin: 0;
        }
        .step {
            background: #f8f9fa;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .step h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .code {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            margin: 10px 0;
            border: 1px solid #dee2e6;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover {
            background: #5a67d8;
        }
        .credentials {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .credentials h4 {
            margin: 0 0 10px 0;
            color: #1976d2;
        }
        .credentials ul {
            margin: 0;
            padding-left: 20px;
        }
        .credentials li {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🏢 GICT Franchise Database Setup</h1>
            <p>Complete setup guide for the new franchise model database</p>
        </div>";

// Check if database configuration exists
if (!file_exists('config/database.php')) {
    echo "<div class='error'>
        <h3>❌ Configuration File Missing</h3>
        <p>The database configuration file (config/database.php) is missing. Please ensure it exists.</p>
    </div>";
    exit;
}

// Include database configuration
require_once 'config/database.php';

echo "<div class='step'>
    <h3>📋 Setup Overview</h3>
    <p>This script will help you set up the new <strong>gict_franchise</strong> database for the GICT Institute Franchise Model.</p>
    <ul>
        <li>✅ Create new database</li>
        <li>✅ Set up all franchise tables</li>
        <li>✅ Insert sample data</li>
        <li>✅ Verify configuration</li>
    </ul>
</div>";

// Step 1: Check current database connection
echo "<div class='step'>
    <h3>🔍 Step 1: Database Connection Check</h3>";

$connection_status = getConnectionStatus();
if ($connection_status['status'] === 'connected') {
    echo "<div class='success'>
        <strong>✅ Database Connected Successfully!</strong><br>
        Database: {$connection_status['database']}<br>
        Host: {$connection_status['host']}<br>
        Charset: {$connection_status['charset']}
    </div>";
} else {
    echo "<div class='error'>
        <strong>❌ Database Connection Failed!</strong><br>
        Error: {$connection_status['message']}<br>
        Please check your database configuration in config/database.php
    </div>";
    echo "<div class='warning'>
        <strong>💡 Troubleshooting Tips:</strong>
        <ul>
            <li>Ensure MySQL server is running</li>
            <li>Check database credentials in config/database.php</li>
            <li>Verify database 'gict_franchise' exists</li>
            <li>Check user permissions</li>
        </ul>
    </div>";
}

echo "</div>";

// Step 2: Database creation instructions
echo "<div class='step'>
    <h3>🗄️ Step 2: Create Database</h3>
    <p>You need to create the <strong>gict_franchise</strong> database. Choose one of these methods:</p>
    
    <h4>Method 1: Using phpMyAdmin</h4>
    <ol>
        <li>Open phpMyAdmin</li>
        <li>Click 'New' to create a new database</li>
        <li>Enter database name: <strong>gict_franchise</strong></li>
        <li>Select collation: <strong>utf8mb4_unicode_ci</strong></li>
        <li>Click 'Create'</li>
    </ol>
    
    <h4>Method 2: Using MySQL Command Line</h4>
    <div class='code'>
        mysql -u root -p<br>
        CREATE DATABASE gict_franchise CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    </div>
    
    <h4>Method 3: Using the SQL Script</h4>
    <p>Run the provided SQL script: <strong>create_gict_franchise_db.sql</strong></p>
    <a href='create_gict_franchise_db.sql' class='btn' download>📥 Download SQL Script</a>
</div>";

// Step 3: Import database structure
echo "<div class='step'>
    <h3>📊 Step 3: Import Database Structure</h3>
    <p>After creating the database, import the structure and sample data:</p>
    
    <h4>Option A: Using phpMyAdmin</h4>
    <ol>
        <li>Select the <strong>gict_franchise</strong> database</li>
        <li>Go to 'Import' tab</li>
        <li>Choose file: <strong>create_gict_franchise_db.sql</strong></li>
        <li>Click 'Go' to import</li>
    </ol>
    
    <h4>Option B: Using MySQL Command Line</h4>
    <div class='code'>
        mysql -u root -p gict_franchise < create_gict_franchise_db.sql
    </div>
    
    <h4>Option C: Using Migration Script</h4>
    <p>If you have existing data to migrate, use the migration script:</p>
    <a href='migrate_to_franchise.php' class='btn'>🔄 Run Migration Script</a>
</div>";

// Step 4: Verify setup
echo "<div class='step'>
    <h3>✅ Step 4: Verify Setup</h3>";

if ($connection_status['status'] === 'connected') {
    // Check if tables exist
    $tables = ['institutes', 'users', 'courses', 'sub_courses', 'student_enrollments', 'payments', 'student_documents'];
    $missing_tables = [];
    
    foreach ($tables as $table) {
        $result = getRow("SHOW TABLES LIKE '$table'");
        if (!$result) {
            $missing_tables[] = $table;
        }
    }
    
    if (empty($missing_tables)) {
        echo "<div class='success'>
            <strong>✅ All Tables Created Successfully!</strong><br>
            Database structure is complete.
        </div>";
        
        // Show database statistics
        $stats = getDatabaseStats();
        echo "<div class='credentials'>
            <h4>📈 Database Statistics</h4>
            <ul>";
        foreach ($stats as $table => $count) {
            echo "<li><strong>" . ucfirst(str_replace('_', ' ', $table)) . ":</strong> $count records</li>";
        }
        echo "</ul></div>";
        
    } else {
        echo "<div class='error'>
            <strong>❌ Missing Tables:</strong><br>
            " . implode(', ', $missing_tables) . "<br><br>
            Please import the database structure first.
        </div>";
    }
} else {
    echo "<div class='warning'>
        <strong>⚠️ Cannot verify tables - database connection failed</strong><br>
        Please fix the database connection first.
    </div>";
}

echo "</div>";

// Step 5: Demo credentials
echo "<div class='step'>
    <h3>🔑 Step 5: Demo Credentials</h3>
    <div class='credentials'>
        <h4>Super Admin</h4>
        <ul>
            <li><strong>Username:</strong> superadmin</li>
            <li><strong>Password:</strong> password</li>
            <li><strong>Access:</strong> Global system access</li>
        </ul>
        
        <h4>Institute Admins</h4>
        <ul>
            <li><strong>Main Branch:</strong> admin / password</li>
            <li><strong>North Branch:</strong> admin_north / password</li>
            <li><strong>Tailoring:</strong> admin_tailoring / password</li>
        </ul>
        
        <h4>Students</h4>
        <ul>
            <li><strong>Main Branch:</strong> student1 / password</li>
            <li><strong>North Branch:</strong> student3 / password</li>
            <li><strong>Tailoring:</strong> student4 / password</li>
        </ul>
    </div>
</div>";

// Step 6: Next steps
echo "<div class='step'>
    <h3>🚀 Step 6: Next Steps</h3>
    <ol>
        <li><strong>Test Login:</strong> Try logging in with the demo credentials</li>
        <li><strong>Explore Dashboards:</strong> Test all user roles and dashboards</li>
        <li><strong>Add Institutes:</strong> Use super admin to add new institutes</li>
        <li><strong>Customize:</strong> Update institute information and branding</li>
        <li><strong>Add Courses:</strong> Create courses and sub-courses for each institute</li>
        <li><strong>Enroll Students:</strong> Add students and enroll them in courses</li>
    </ol>
    
    <div style='text-align: center; margin-top: 20px;'>
        <a href='login.php' class='btn'>🔐 Go to Login</a>
        <a href='index.php' class='btn'>🏠 Go to Homepage</a>
    </div>
</div>";

// Step 7: Troubleshooting
echo "<div class='step'>
    <h3>🔧 Troubleshooting</h3>
    <h4>Common Issues:</h4>
    <ul>
        <li><strong>Database Connection Failed:</strong> Check MySQL server and credentials</li>
        <li><strong>Tables Missing:</strong> Import the SQL script properly</li>
        <li><strong>Permission Denied:</strong> Check database user permissions</li>
        <li><strong>Login Issues:</strong> Verify demo credentials are correct</li>
    </ul>
    
    <h4>Support Files:</h4>
    <ul>
        <li><a href='franchise_model_guide.txt'>📖 Complete Documentation</a></li>
        <li><a href='create_gict_franchise_db.sql'>🗄️ Database Structure</a></li>
        <li><a href='migrate_to_franchise.php'>🔄 Migration Script</a></li>
    </ul>
</div>";

echo "</div></body></html>";
?>
