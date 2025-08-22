<?php
/**
 * Test ImgBB Upload Functionality
 * Debug why images are not uploading to ImgBB
 */

require_once 'includes/imgbb_helper.php';

echo "🔍 ImgBB Upload Test & Debug\n";
echo "============================\n\n";

// Test 1: Check ImgBB Configuration
echo "📋 Configuration Check:\n";
echo "   API Key: " . (defined('IMGBB_API_KEY') ? IMGBB_API_KEY : 'NOT DEFINED') . "\n";
echo "   Expiration: " . (defined('IMGBB_EXPIRATION') ? (IMGBB_EXPIRATION === 0 ? 'Never (Permanent)' : IMGBB_EXPIRATION . ' seconds') : 'NOT DEFINED') . "\n";
echo "   cURL Available: " . (function_exists('curl_init') ? 'Yes' : 'No') . "\n\n";

// Test 2: Test ImgBB Connection
echo "🔌 Testing ImgBB Connection:\n";
$connection_test = testImgBBConnection();

foreach ($connection_test as $key => $value) {
    if (is_bool($value)) {
        $status = $value ? '✅ Yes' : '❌ No';
    } else {
        $status = $value;
    }
    echo "   $key: $status\n";
}

echo "\n";

// Test 3: Test with a sample image
echo "🖼️  Testing Image Upload:\n";

// Check if we have any sample images
$sample_images = [
    'assets/images/logo.png',
    'assets/images/anjali.jpeg',
    'assets/images/brijendra.jpeg'
];

$test_image = null;
foreach ($sample_images as $img) {
    if (file_exists($img)) {
        $test_image = $img;
        break;
    }
}

if ($test_image) {
    echo "   Found test image: $test_image\n";
    echo "   File size: " . round(filesize($test_image) / 1024, 2) . " KB\n";
    echo "   File type: " . pathinfo($test_image, PATHINFO_EXTENSION) . "\n\n";
    
    echo "   🔄 Attempting ImgBB upload...\n";
    
    // Test direct ImgBB upload
    $result = uploadToImgBB($test_image, 'test_' . time());
    
    if ($result && $result['success']) {
        echo "   ✅ ImgBB upload successful!\n";
        echo "      URL: {$result['url']}\n";
        echo "      ID: {$result['id']}\n";
        echo "      Size: {$result['size']} bytes\n";
        echo "      Format: {$result['format']}\n";
    } else {
        echo "   ❌ ImgBB upload failed!\n";
        echo "      Error: " . (error_get_last()['message'] ?? 'Unknown error') . "\n";
    }
    
    echo "\n   🔄 Testing smartUpload function...\n";
    
    // Test smartUpload function
    $smart_result = smartUpload($test_image, 'smart_test_' . time());
    
    if ($smart_result && $smart_result['success']) {
        echo "   ✅ Smart upload successful!\n";
        echo "      Storage: {$smart_result['storage_type']}\n";
        echo "      URL: {$smart_result['url']}\n";
    } else {
        echo "   ❌ Smart upload failed!\n";
    }
    
} else {
    echo "   ❌ No sample images found for testing\n";
}

echo "\n🔧 Debug Information:\n";
echo "   PHP Version: " . PHP_VERSION . "\n";
echo "   Memory Limit: " . ini_get('memory_limit') . "\n";
echo "   Upload Max Filesize: " . ini_get('upload_max_filesize') . "\n";
echo "   Post Max Size: " . ini_get('post_max_size') . "\n";
echo "   Max Execution Time: " . ini_get('max_execution_time') . " seconds\n";
echo "   cURL Info: " . (function_exists('curl_version') ? json_encode(curl_version()) : 'Not available') . "\n";

echo "\n📝 Next Steps:\n";
echo "   1. Check if ImgBB API key is valid\n";
echo "   2. Verify cURL is working properly\n";
echo "   3. Check error logs for specific error messages\n";
echo "   4. Test with a smaller image file\n";
echo "   5. Verify network connectivity to ImgBB API\n";

echo "\n✨ Test completed!\n";
?>
