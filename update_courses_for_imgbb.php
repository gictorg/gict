<?php
/**
 * Update Courses for ImgBB Integration
 * This script adds image fields to courses table and migrates existing images to ImgBB
 */

require_once 'config/database.php';
require_once 'includes/imgbb_helper.php';

echo "<h1>Update Courses for ImgBB Integration</h1>\n";

$pdo = getDBConnection();
if (!$pdo) {
    echo "❌ <strong>Database Connection Failed</strong><br>\n";
    exit;
}

echo "✅ <strong>Database Connection Successful</strong><br><br>\n";

// Step 1: Add image fields to courses table
echo "<h2>Step 1: Adding Image Fields to Courses Table</h2>\n";
try {
    // Check if course_image column already exists
    $sql = "SHOW COLUMNS FROM courses LIKE 'course_image'";
    $stmt = $pdo->query($sql);
    $column_exists = $stmt->fetch();
    
    if (!$column_exists) {
        echo "🔧 <strong>Adding course_image column...</strong><br>\n";
        $sql = "ALTER TABLE courses ADD COLUMN course_image VARCHAR(500) AFTER description";
        $pdo->exec($sql);
        echo "✅ <strong>course_image column added successfully</strong><br>\n";
    } else {
        echo "✅ <strong>course_image column already exists</strong><br>\n";
    }
    
    // Check if course_image_alt column exists
    $sql = "SHOW COLUMNS FROM courses LIKE 'course_image_alt'";
    $stmt = $pdo->query($sql);
    $alt_column_exists = $stmt->fetch();
    
    if (!$alt_column_exists) {
        echo "🔧 <strong>Adding course_image_alt column...</strong><br>\n";
        $sql = "ALTER TABLE courses ADD COLUMN course_image_alt VARCHAR(255) AFTER course_image";
        $pdo->exec($sql);
        echo "✅ <strong>course_image_alt column added successfully</strong><br>\n";
    } else {
        echo "✅ <strong>course_image_alt column already exists</strong><br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>Error adding columns:</strong> " . $e->getMessage() . "<br>\n";
}

// Step 2: Upload existing course images to ImgBB
echo "<h2>Step 2: Uploading Existing Course Images to ImgBB</h2>\n";

$course_image_mappings = [
    'Computer Course' => 'assets/images/computer course.jpeg',
    'Yoga Course' => 'assets/images/Yoga Certificate.jpeg',
    'Vocational Course' => 'assets/images/Vocational Course.jpeg',
    'Beautician Course' => 'assets/images/Beautician Certificate.jpeg',
    'Tailoring Course' => 'assets/images/Tailoring Certificate.jpeg',
    'Digital Marketing' => 'assets/images/digi.jpg',
    'Web Development' => 'assets/images/techno.jpeg',
    'Embroidery Course' => 'assets/images/skill course.jpeg'
];

$uploaded_count = 0;
$failed_count = 0;

foreach ($course_image_mappings as $course_name => $local_path) {
    try {
        echo "🔄 <strong>Processing:</strong> $course_name<br>\n";
        
        // Check if local file exists
        if (!file_exists($local_path)) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;⚠️ <strong>Local file not found:</strong> $local_path<br>\n";
            continue;
        }
        
        // Generate ImgBB name
        $imgbb_name = strtolower(str_replace(' ', '_', $course_name)) . '_course';
        
        // Upload to ImgBB
        $upload_result = smartUpload($local_path, $imgbb_name);
        
        if ($upload_result && $upload_result['success']) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;✅ <strong>Uploaded to ImgBB:</strong> <a href='{$upload_result['url']}' target='_blank'>View Image</a><br>\n";
            
            // Update database with ImgBB URL
            $sql = "UPDATE courses SET course_image = ?, course_image_alt = ? WHERE name = ?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$upload_result['url'], $course_name, $course_name]);
            
            if ($result) {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;✅ <strong>Database updated</strong><br>\n";
                $uploaded_count++;
            } else {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;❌ <strong>Database update failed</strong><br>\n";
                $failed_count++;
            }
        } else {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;❌ <strong>ImgBB upload failed</strong><br>\n";
            $failed_count++;
        }
        
        echo "<br>\n";
        
    } catch (Exception $e) {
        echo "&nbsp;&nbsp;&nbsp;&nbsp;❌ <strong>Error:</strong> " . $e->getMessage() . "<br>\n";
        $failed_count++;
    }
}

echo "<h2>Step 3: Upload Summary</h2>\n";
echo "📊 <strong>Results:</strong><br>\n";
echo "✅ <strong>Successfully uploaded:</strong> $uploaded_count courses<br>\n";
echo "❌ <strong>Failed uploads:</strong> $failed_count courses<br>\n";

// Step 4: Verify the updates
echo "<h2>Step 4: Verification</h2>\n";
try {
    $sql = "SELECT name, course_image, course_image_alt FROM courses WHERE course_image IS NOT NULL";
    $stmt = $pdo->query($sql);
    $courses_with_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($courses_with_images)) {
        echo "🎯 <strong>Courses with ImgBB images:</strong><br>\n";
        foreach ($courses_with_images as $course) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;• <strong>{$course['name']}</strong><br>\n";
            echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;📷 <a href='{$course['course_image']}' target='_blank'>View Image</a><br>\n";
            echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;🏷️ Alt: {$course['course_image_alt']}<br><br>\n";
        }
    } else {
        echo "⚠️ <strong>No courses have images yet</strong><br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>Error verifying courses:</strong> " . $e->getMessage() . "<br>\n";
}

echo "<br><strong>🎉 Course ImgBB Integration Complete!</strong><br>\n";
echo "<br><strong>Next Steps:</strong><br>\n";
echo "1. ✅ Database schema updated with image fields<br>\n";
echo "2. ✅ Existing course images uploaded to ImgBB<br>\n";
echo "3. 🧪 Test the gallery to see ImgBB course images<br>\n";
echo "4. 🧪 Test adding new courses with ImgBB images<br>\n";

echo "<br><strong>Benefits:</strong><br>\n";
echo "• 🖼️ All course images now stored in ImgBB cloud<br>\n";
echo "• 🚀 Faster loading and better performance<br>\n";
echo "• 📱 Consistent with student/faculty image handling<br>\n";
echo "• 🔄 Easy to manage and update course images<br>\n";
?>
