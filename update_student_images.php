<?php
/**
 * Update Student Images with Existing Files
 * Replace SVG images with actual image files (1.jpg, 2.jpg, etc.)
 */

require_once 'config/database.php';

echo "🖼️  Updating Student Images with Existing Files\n";
echo "==============================================\n\n";

// Available student images
$available_images = [
    'assets/images/1.jpg',
    'assets/images/2.jpg',
    'assets/images/3.jpeg',
    'assets/images/4.jpeg',
    'assets/images/5.jpg',
    'assets/images/6.jpeg'
];

echo "📁 Available Student Images:\n";
echo "============================\n";
foreach ($available_images as $image) {
    if (file_exists($image)) {
        $size = round(filesize($image) / 1024, 2);
        echo "✅ {$image} ({$size}KB)\n";
    } else {
        echo "❌ {$image} (File not found)\n";
    }
}
echo "\n";

// Get all students
$students_sql = "SELECT id, username, full_name, profile_image FROM users WHERE user_type = 'student' ORDER BY id";
$students = getRows($students_sql);

if (empty($students)) {
    echo "❌ No students found!\n";
    exit(1);
}

echo "👤 Found " . count($students) . " students to update\n\n";

$updated_count = 0;
$error_count = 0;

foreach ($students as $index => $student) {
    echo "👤 Processing: {$student['full_name']} ({$student['username']})\n";
    
    // Get the corresponding image file
    $image_index = $index % count($available_images);
    $image_path = $available_images[$image_index];
    
    if (!file_exists($image_path)) {
        echo "   ❌ Image file not found: {$image_path}\n";
        $error_count++;
        continue;
    }
    
    echo "   🖼️  Assigning image: {$image_path}\n";
    
    try {
        // Update the student's profile image
        $updateSql = "UPDATE users SET profile_image = ? WHERE id = ?";
        $result = updateData($updateSql, [$image_path, $student['id']]);
        
        if ($result > 0) {
            echo "   ✅ Image updated successfully!\n";
            $updated_count++;
        } else {
            echo "   ⚠️  No changes made (image might be the same)\n";
            $updated_count++; // Still count as success
        }
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $error_count++;
    }
    
    echo "\n";
}

echo "🎉 Student Image Update Complete!\n";
echo "================================\n";
echo "✅ Successfully updated: {$updated_count} students\n";
echo "❌ Failed: {$error_count} students\n";
echo "📊 Total processed: " . count($students) . " students\n\n";

echo "🔗 View Your Results:\n";
echo "1. Homepage: http://localhost:8000/index.php\n";
echo "2. Gallery: http://localhost:8000/gallery.php\n";
echo "3. Admin Dashboard: http://localhost:8000/dashboard.php\n";
echo "4. Student Management: http://localhost:8000/admin/students.php\n\n";

echo "✨ Student images have been updated with actual photo files!\n";
?>
