<?php
/**
 * Upload Student Images to ImgBB
 * Upload all student images to ImgBB with proper names and update database
 */

require_once 'config/database.php';
require_once 'includes/imgbb_helper.php';

echo "🖼️  Uploading Student Images to ImgBB\n";
echo "=====================================\n\n";

// Test ImgBB connection
echo "🔍 Testing ImgBB Connection...\n";
$status = testImgBBConnection();

if (!$status['ready_for_upload']) {
    echo "❌ ImgBB not ready. Please check configuration.\n";
    exit(1);
}

echo "✅ ImgBB connection successful!\n\n";

// Student data with proper names and corresponding images
$student_data = [
    [
        'username' => 'rahul_kumar',
        'full_name' => 'Rahul Kumar',
        'image_file' => 'assets/images/1.jpg',
        'new_name' => 'Rahul Kumar - Computer Science Student'
    ],
    [
        'username' => 'priya_sharma',
        'full_name' => 'Priya Sharma',
        'image_file' => 'assets/images/2.jpg',
        'new_name' => 'Priya Sharma - Web Development Student'
    ],
    [
        'username' => 'arjun_singh',
        'full_name' => 'Arjun Singh',
        'image_file' => 'assets/images/3.jpeg',
        'new_name' => 'Arjun Singh - Data Science Student'
    ],
    [
        'username' => 'kavya_patel',
        'full_name' => 'Kavya Patel',
        'image_file' => 'assets/images/4.jpeg',
        'new_name' => 'Kavya Patel - AI Student'
    ],
    [
        'username' => 'vikram_verma',
        'full_name' => 'Vikram Verma',
        'image_file' => 'assets/images/5.jpg',
        'new_name' => 'Vikram Verma - Cybersecurity Student'
    ],
    [
        'username' => 'neha_gupta',
        'full_name' => 'Neha Gupta',
        'image_file' => 'assets/images/6.jpeg',
        'new_name' => 'Neha Gupta - Machine Learning Student'
    ],
    [
        'username' => 'aditya_malhotra',
        'full_name' => 'Aditya Malhotra',
        'image_file' => 'assets/images/1.jpg',
        'new_name' => 'Aditya Malhotra - Software Engineering Student'
    ],
    [
        'username' => 'isha_reddy',
        'full_name' => 'Isha Reddy',
        'image_file' => 'assets/images/2.jpg',
        'new_name' => 'Isha Reddy - Digital Marketing Student'
    ],
    [
        'username' => 'rajat_khanna',
        'full_name' => 'Rajat Khanna',
        'image_file' => 'assets/images/3.jpeg',
        'new_name' => 'Rajat Khanna - Cloud Computing Student'
    ],
    [
        'username' => 'ananya_tiwari',
        'full_name' => 'Ananya Tiwari',
        'image_file' => 'assets/images/4.jpeg',
        'new_name' => 'Ananya Tiwari - UI/UX Student'
    ],
    [
        'username' => 'student1',
        'full_name' => 'Aarav Singh',
        'image_file' => 'assets/images/5.jpg',
        'new_name' => 'Aarav Singh - Mobile App Development Student'
    ],
    [
        'username' => 'student2',
        'full_name' => 'Zara Khan',
        'image_file' => 'assets/images/6.jpeg',
        'new_name' => 'Zara Khan - Game Development Student'
    ],
    [
        'username' => 'student3',
        'full_name' => 'Vivaan Patel',
        'image_file' => 'assets/images/1.jpg',
        'new_name' => 'Vivaan Patel - Blockchain Student'
    ],
    [
        'username' => 'student4',
        'full_name' => 'Kiara Sharma',
        'image_file' => 'assets/images/2.jpg',
        'new_name' => 'Kiara Sharma - IoT Student'
    ]
];

echo "🎯 Processing " . count($student_data) . " students...\n\n";

$success_count = 0;
$error_count = 0;

foreach ($student_data as $index => $student) {
    $student_number = $index + 1;
    echo "👤 Processing Student {$student_number}/" . count($student_data) . ": {$student['full_name']}\n";
    
    try {
        // Check if student exists
        $checkSql = "SELECT id, profile_image FROM users WHERE username = ?";
        $existingUser = getRow($checkSql, [$student['username']]);
        
        if (!$existingUser) {
            echo "   ❌ Student not found in database, skipping...\n";
            $error_count++;
            continue;
        }
        
        // Check if image file exists
        if (!file_exists($student['image_file'])) {
            echo "   ❌ Image file not found: {$student['image_file']}\n";
            $error_count++;
            continue;
        }
        
        // Get file info
        $file_size = filesize($student['image_file']);
        echo "   📁 File: {$student['image_file']} (" . round($file_size / 1024, 2) . "KB)\n";
        
        // Upload to ImgBB
        echo "   🔄 Uploading to ImgBB...\n";
        $imgbb_result = uploadToImgBB(
            $student['image_file'], 
            $student['new_name']
        );
        
        if ($imgbb_result && $imgbb_result['success']) {
            echo "   ✅ ImgBB upload successful!\n";
            echo "      URL: {$imgbb_result['url']}\n";
            
            // Update database with new ImgBB URL
            $updateSql = "UPDATE users SET profile_image = ? WHERE username = ?";
            $result = updateData($updateSql, [$imgbb_result['url'], $student['username']]);
            
            if ($result > 0) {
                echo "   ✅ Database updated successfully!\n";
                $success_count++;
            } else {
                echo "   ⚠️  No database changes made\n";
                $success_count++; // Still count as success
            }
        } else {
            echo "   ❌ ImgBB upload failed!\n";
            $error_count++;
        }
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $error_count++;
    }
    
    echo "\n";
    usleep(500000); // 0.5 second delay to avoid overwhelming ImgBB
}

echo "🎉 Student Image Upload Complete!\n";
echo "================================\n";
echo "✅ Successfully uploaded: {$success_count} students\n";
echo "❌ Failed: {$error_count} students\n";
echo "📊 Total processed: " . count($student_data) . " students\n\n";

if ($success_count > 0) {
    echo "🔗 View Your Results:\n";
    echo "1. Homepage: http://localhost:8000/index.php\n";
    echo "2. Gallery: http://localhost:8000/gallery.php\n";
    echo "3. Admin Dashboard: http://localhost:8000/dashboard.php\n";
    echo "4. Student Management: http://localhost:8000/admin/students.php\n\n";
    
    echo "✨ All student images are now stored on ImgBB with proper names!\n";
} else {
    echo "❌ No students were successfully uploaded. Please check the errors above.\n";
}
?>
