<?php
/**
 * Test Database Connection and Data
 * This script tests the database connection and shows what data exists
 */

require_once 'config/database.php';

echo "<h1>Database Connection and Data Test</h1>\n";

// Test 1: Database Connection
echo "<h2>Test 1: Database Connection</h2>\n";
$pdo = getDBConnection();
if ($pdo) {
    echo "✅ <strong>Database Connection:</strong> Successful<br>\n";
} else {
    echo "❌ <strong>Database Connection:</strong> Failed<br>\n";
    exit;
}

// Test 2: Check users table structure
echo "<h2>Test 2: Users Table Structure</h2>\n";
try {
    $sql = "DESCRIBE users";
    $stmt = $pdo->query($sql);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 <strong>Users Table Columns:</strong><br>\n";
    foreach ($columns as $column) {
        $name = $column['Field'];
        $type = $column['Type'];
        $null = $column['Null'];
        $key = $column['Key'];
        $default = $column['Default'];
        
        echo "&nbsp;&nbsp;&nbsp;&nbsp;• <strong>$name</strong> ($type)";
        if ($null === 'NO') echo " NOT NULL";
        if ($key === 'PRI') echo " PRIMARY KEY";
        if ($default !== null) echo " DEFAULT $default";
        echo "<br>\n";
    }
} catch (Exception $e) {
    echo "❌ <strong>Error describing users table:</strong> " . $e->getMessage() . "<br>\n";
}

// Test 3: Check what data exists in users table
echo "<h2>Test 3: Users Table Data</h2>\n";
try {
    $sql = "SELECT id, username, full_name, email, user_type, status, created_at FROM users ORDER BY id";
    $stmt = $pdo->query($sql);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "⚠️ <strong>No users found in database</strong><br>\n";
    } else {
        echo "👥 <strong>Found " . count($users) . " users:</strong><br>\n";
        foreach ($users as $user) {
            $id = $user['id'];
            $username = $user['username'];
            $full_name = $user['full_name'];
            $email = $user['email'];
            $user_type = $user['user_type'];
            $status = $user['status'];
            $created_at = $user['created_at'];
            
            echo "&nbsp;&nbsp;&nbsp;&nbsp;• <strong>ID $id:</strong> $username ($full_name) - $user_type - $status - $created_at<br>\n";
        }
    }
} catch (Exception $e) {
    echo "❌ <strong>Error querying users table:</strong> " . $e->getMessage() . "<br>\n";
}

// Test 4: Check specific user types
echo "<h2>Test 4: User Type Counts</h2>\n";
try {
    $sql = "SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type";
    $stmt = $pdo->query($sql);
    $type_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($type_counts)) {
        echo "⚠️ <strong>No user types found</strong><br>\n";
    } else {
        echo "📊 <strong>User Type Distribution:</strong><br>\n";
        foreach ($type_counts as $type_count) {
            $user_type = $type_count['user_type'];
            $count = $type_count['count'];
            echo "&nbsp;&nbsp;&nbsp;&nbsp;• <strong>$user_type:</strong> $count users<br>\n";
        }
    }
} catch (Exception $e) {
    echo "❌ <strong>Error counting user types:</strong> " . $e->getMessage() . "<br>\n";
}

// Test 5: Check if there are any students specifically
echo "<h2>Test 5: Student Data Check</h2>\n";
try {
    $sql = "SELECT COUNT(*) as count FROM users WHERE user_type = 'student'";
    $stmt = $pdo->query($sql);
    $student_count = $stmt->fetch();
    
    $count = $student_count['count'] ?? 0;
    echo "🎓 <strong>Students found:</strong> $count<br>\n";
    
    if ($count > 0) {
        $sql = "SELECT id, username, full_name, email, status FROM users WHERE user_type = 'student' LIMIT 5";
        $stmt = $pdo->query($sql);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "&nbsp;&nbsp;&nbsp;&nbsp;<strong>Sample students:</strong><br>\n";
        foreach ($students as $student) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;• {$student['username']} - {$student['full_name']} ({$student['status']})<br>\n";
        }
    }
} catch (Exception $e) {
    echo "❌ <strong>Error checking students:</strong> " . $e->getMessage() . "<br>\n";
}

// Test 6: Check if there are any faculty members
echo "<h2>Test 6: Faculty Data Check</h2>\n";
try {
    $sql = "SELECT COUNT(*) as count FROM users WHERE user_type = 'faculty'";
    $stmt = $pdo->query($sql);
    $faculty_count = $stmt->fetch();
    
    $count = $faculty_count['count'] ?? 0;
    echo "👨‍🏫 <strong>Faculty found:</strong> $count<br>\n";
    
    if ($count > 0) {
        $sql = "SELECT id, username, full_name, email, status FROM users WHERE user_type = 'faculty' LIMIT 5";
        $stmt = $pdo->query($sql);
        $faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "&nbsp;&nbsp;&nbsp;&nbsp;<strong>Sample faculty:</strong><br>\n";
        foreach ($faculty as $fac) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;• {$fac['username']} - {$fac['full_name']} ({$fac['status']})<br>\n";
        }
    }
} catch (Exception $e) {
    echo "❌ <strong>Error checking faculty:</strong> " . $e->getMessage() . "<br>\n";
}

// Test 7: Check database schema version
echo "<h2>Test 7: Database Schema Check</h2>\n";
try {
    // Check if user_types table exists (old schema)
    $sql = "SHOW TABLES LIKE 'user_types'";
    $stmt = $pdo->query($sql);
    $user_types_exists = $stmt->fetch();
    
    if ($user_types_exists) {
        echo "⚠️ <strong>Old Schema Detected:</strong> user_types table exists<br>\n";
        echo "&nbsp;&nbsp;&nbsp;&nbsp;This suggests the database is using the old schema<br>\n";
        echo "&nbsp;&nbsp;&nbsp;&nbsp;You may need to update the database schema<br>\n";
    } else {
        echo "✅ <strong>New Schema Detected:</strong> user_types table does not exist<br>\n";
        echo "&nbsp;&nbsp;&nbsp;&nbsp;This suggests the database is using the new schema<br>\n";
    }
    
    // Check if users table has user_type column
    $sql = "SHOW COLUMNS FROM users LIKE 'user_type'";
    $stmt = $pdo->query($sql);
    $user_type_column = $stmt->fetch();
    
    if ($user_type_column) {
        echo "✅ <strong>user_type column exists</strong><br>\n";
    } else {
        echo "❌ <strong>user_type column does not exist</strong><br>\n";
    }
    
    // Check if users table has user_type_id column
    $sql = "SHOW COLUMNS FROM users LIKE 'user_type_id'";
    $stmt = $pdo->query($sql);
    $user_type_id_column = $stmt->fetch();
    
    if ($user_type_id_column) {
        echo "⚠️ <strong>user_type_id column exists (old schema)</strong><br>\n";
    } else {
        echo "✅ <strong>user_type_id column does not exist (new schema)</strong><br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>Error checking schema:</strong> " . $e->getMessage() . "<br>\n";
}

// Test 8: Recommendations
echo "<h2>Test 8: Recommendations</h2>\n";
echo "🔧 <strong>Based on the test results:</strong><br>\n";

if (empty($users)) {
    echo "1. 📝 <strong>No users found:</strong> You need to create some users first<br>\n";
    echo "2. 🎯 <strong>Test user creation:</strong> Try adding a student or faculty member<br>\n";
} else {
    echo "1. ✅ <strong>Users exist:</strong> Database has data<br>\n";
    echo "2. 🔍 <strong>Check user types:</strong> Verify user_type values are correct<br>\n";
}

echo "3. 🗄️ <strong>Schema alignment:</strong> Ensure database schema matches the code<br>\n";
echo "4. 🧪 <strong>Test functionality:</strong> Try the admin features after fixing schema<br>\n";

echo "<br><strong>Next Steps:</strong><br>\n";
echo "1. Run this test to see what's in your database<br>\n";
echo "2. Check if the schema matches what the code expects<br>\n";
echo "3. Create test users if none exist<br>\n";
echo "4. Test the admin functionality<br>\n";
?>
