<?php
/**
 * Debug Upload Issue
 * Test the exact upload scenario that's failing
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/imgbb_helper.php';

echo "🔍 Debug Upload Issue\n";
echo "=====================\n\n";

// Test 1: Check if the issue is with file size limit
echo "📏 File Size Test:\n";
$test_file = 'assets/images/logo.png';
if (file_exists($test_file)) {
    $file_size = filesize($test_file);
    $file_size_kb = round($file_size / 1024, 2);
    
    echo "   Test file: $test_file\n";
    echo "   File size: {$file_size_kb} KB\n";
    echo "   Size limit check: " . ($file_size <= 102400 ? '✅ Under 100KB' : '❌ Over 100KB') . "\n";
    
    // Test upload with the same size
    echo "\n   🔄 Testing upload with current file...\n";
    $result = smartUpload($test_file, 'debug_test_' . time());
    
    if ($result && $result['success']) {
        echo "   ✅ Upload successful!\n";
        echo "      Storage: {$result['storage_type']}\n";
        echo "      URL: {$result['url']}\n";
    } else {
        echo "   ❌ Upload failed!\n";
        echo "      Last error: " . (error_get_last()['message'] ?? 'Unknown error') . "\n";
    }
}

echo "\n\n";

// Test 2: Check if the issue is with the specific upload function
echo "🔧 Function Test:\n";

// Simulate the exact scenario from add-student.php
$username = 'test_user_' . time();
$file_info = [
    'tmp_name' => $test_file,
    'name' => 'test_image.png',
    'size' => filesize($test_file),
    'error' => 0
];

echo "   Simulating file upload for user: $username\n";
echo "   File info: " . json_encode($file_info) . "\n\n";

// Check file size limit (same as in add-student.php)
if ($file_info['size'] <= 100 * 1024) {
    echo "   ✅ File size check passed\n";
    
    // Try upload (same as in add-student.php)
    $imgbb_result = smartUpload(
        $file_info['tmp_name'], 
        $username . '_profile'
    );
    
    if ($imgbb_result && $imgbb_result['success']) {
        echo "   ✅ ImgBB upload successful!\n";
        echo "      URL: {$imgbb_result['url']}\n";
        echo "      ID: {$imgbb_result['id']}\n";
        echo "      Storage: {$imgbb_result['storage_type']}\n";
    } else {
        echo "   ❌ ImgBB upload failed!\n";
        echo "      Error: " . (error_get_last()['message'] ?? 'Unknown error') . "\n";
        
        // Try to get more details
        echo "\n   🔍 Debugging upload failure...\n";
        
        // Test direct ImgBB upload
        $direct_result = uploadToImgBB($file_info['tmp_name'], $username . '_profile');
        if ($direct_result && $direct_result['success']) {
            echo "   ✅ Direct ImgBB upload works!\n";
            echo "      This suggests the issue is in smartUpload function\n";
        } else {
            echo "   ❌ Direct ImgBB upload also failed!\n";
        }
    }
} else {
    echo "   ❌ File size check failed - file is too large\n";
}

echo "\n\n";

// Test 3: Check if the issue is with the database helper functions
echo "🗄️  Database Helper Test:\n";
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    if ($pdo) {
        echo "   ✅ Database connection successful\n";
        
        // Test if we can insert data
        $test_sql = "SELECT 1 as test";
        $test_result = getRow($test_sql, []);
        if ($test_result) {
            echo "   ✅ Database helper functions working\n";
        } else {
            echo "   ❌ Database helper functions not working\n";
        }
    } else {
        echo "   ❌ Database connection failed\n";
    }
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n\n";

// Test 4: Check PHP configuration
echo "⚙️  PHP Configuration:\n";
echo "   upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "   post_max_size: " . ini_get('post_max_size') . "\n";
echo "   max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "   memory_limit: " . ini_get('memory_limit') . "\n";
echo "   file_uploads: " . (ini_get('file_uploads') ? 'Enabled' : 'Disabled') . "\n";

echo "\n\n";

// Test 5: Check if the issue is with the specific error handling
echo "🚨 Error Handling Test:\n";

// Test with a non-existent file to see error handling
$fake_file = 'non_existent_file.jpg';
echo "   Testing with non-existent file: $fake_file\n";

$fake_result = smartUpload($fake_file, 'fake_test');
if ($fake_result === false) {
    echo "   ✅ Error handling working (correctly returned false)\n";
} else {
    echo "   ❌ Error handling not working (should return false)\n";
}

echo "\n✨ Debug test completed!\n";
echo "\n📝 Summary:\n";
echo "   If ImgBB upload works in this test but fails in forms:\n";
echo "   1. Check form file handling\n";
echo "   2. Check file size validation\n";
echo "   3. Check error message display\n";
echo "   4. Check if the issue is in the specific form logic\n";
?>
