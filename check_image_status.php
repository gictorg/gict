<?php
/**
 * Check Image Status - Complete Overview
 * Shows the status of all faculty and student images
 */

require_once 'config/database.php';

echo "📊 GICT Institute - Complete Image Status Report\n";
echo "===============================================\n\n";

// Check Faculty Images
echo "👨‍🏫 FACULTY IMAGES STATUS\n";
echo "========================\n";

$faculty_sql = "SELECT username, full_name, profile_image, status FROM users WHERE user_type = 'faculty' ORDER BY full_name";
$faculty_members = getRows($faculty_sql);

if (!empty($faculty_members)) {
    foreach ($faculty_members as $faculty) {
        $status_icon = $faculty['status'] === 'active' ? '✅' : '❌';
        $image_status = !empty($faculty['profile_image']) ? '🖼️' : '❌';
        $storage_type = '';
        
        if (!empty($faculty['profile_image'])) {
            if (strpos($faculty['profile_image'], 'i.ibb.co') !== false) {
                $storage_type = 'ImgBB';
            } elseif (strpos($faculty['profile_image'], 'uploads/') !== false) {
                $storage_type = 'Local';
            } else {
                $storage_type = 'Other';
            }
        }
        
        echo "{$status_icon} {$faculty['full_name']} ({$faculty['username']})\n";
        echo "   Status: {$faculty['status']}\n";
        echo "   Image: {$image_status} " . ($faculty['profile_image'] ?: 'No Image') . "\n";
        if ($storage_type) {
            echo "   Storage: {$storage_type}\n";
        }
        echo "\n";
    }
} else {
    echo "❌ No faculty members found!\n";
}

echo "\n";

// Check Student Images
echo "👤 STUDENT IMAGES STATUS\n";
echo "========================\n";

$students_sql = "SELECT username, full_name, profile_image, status FROM users WHERE user_type = 'student' ORDER BY full_name";
$students = getRows($students_sql);

if (!empty($students)) {
    foreach ($students as $student) {
        $status_icon = $student['status'] === 'active' ? '✅' : '❌';
        $image_status = !empty($student['profile_image']) ? '🖼️' : '❌';
        $storage_type = '';
        
        if (!empty($student['profile_image'])) {
            if (strpos($student['profile_image'], 'i.ibb.co') !== false) {
                $storage_type = 'ImgBB';
            } elseif (strpos($student['profile_image'], 'uploads/') !== false) {
                $storage_type = 'Local';
            } else {
                $storage_type = 'Other';
            }
        }
        
        echo "{$status_icon} {$student['full_name']} ({$student['username']})\n";
        echo "   Status: {$student['status']}\n";
        echo "   Image: {$image_status} " . ($student['profile_image'] ?: 'No Image') . "\n";
        if ($storage_type) {
            echo "   Storage: {$storage_type}\n";
        }
        echo "\n";
    }
} else {
    echo "❌ No students found!\n";
}

echo "\n";

// Summary Statistics
echo "📈 SUMMARY STATISTICS\n";
echo "====================\n";

$total_faculty = count($faculty_members);
$active_faculty = count(array_filter($faculty_members, fn($f) => $f['status'] === 'active'));
$faculty_with_images = count(array_filter($faculty_members, fn($f) => !empty($f['profile_image'])));

$total_students = count($students);
$active_students = count(array_filter($students, fn($s) => $s['status'] === 'active'));
$students_with_images = count(array_filter($students, fn($s) => !empty($s['profile_image'])));

echo "👨‍🏫 Faculty:\n";
echo "   Total: {$total_faculty}\n";
echo "   Active: {$active_faculty}\n";
echo "   With Images: {$faculty_with_images}\n";
echo "   Image Coverage: " . round(($faculty_with_images / $total_faculty) * 100, 1) . "%\n\n";

echo "👤 Students:\n";
echo "   Total: {$total_students}\n";
echo "   Active: {$active_students}\n";
echo "   With Images: {$students_with_images}\n";
echo "   Image Coverage: " . round(($students_with_images / $total_students) * 100, 1) . "%\n\n";

echo "🎯 Overall Image Coverage: " . round((($faculty_with_images + $students_with_images) / ($total_faculty + $total_students)) * 100, 1) . "%\n";

echo "\n🔗 View Your Results:\n";
echo "1. Homepage: http://localhost:8000/index.php\n";
echo "2. Gallery: http://localhost:8000/gallery.php\n";
echo "3. Admin Dashboard: http://localhost:8000/dashboard.php\n";
echo "4. Staff Management: http://localhost:8000/admin/staff.php\n";
echo "5. Student Management: http://localhost:8000/admin/students.php\n\n";

echo "✨ All images should now be properly uploaded and visible!\n";
?>
