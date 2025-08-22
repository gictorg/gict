<?php
/**
 * Master Script to Setup ImgBB Images for GICT Institute
 * This script runs both faculty and student image uploads
 */

echo "🚀 GICT Institute ImgBB Image Setup\n";
echo "====================================\n\n";

// Step 1: Upload Faculty Images
echo "📚 STEP 1: Uploading Faculty Images to ImgBB\n";
echo "--------------------------------------------\n";
include 'upload_faculty_images.php';

echo "\n\n";

// Step 2: Create Students with Images
echo "📚 STEP 2: Creating Students with ImgBB Images\n";
echo "----------------------------------------------\n";
include 'create_students_with_images.php';

echo "\n\n";

echo "🎉 SETUP COMPLETE!\n";
echo "==================\n";
echo "✅ Faculty images uploaded to ImgBB\n";
echo "✅ Students created with ImgBB images\n";
echo "✅ Database updated with all image URLs\n\n";

echo "🔗 View Your Results:\n";
echo "1. Homepage: http://localhost:8000/index.php\n";
echo "2. Gallery (Faculty & Students): http://localhost:8000/gallery.php\n";
echo "3. Admin Dashboard: http://localhost:8000/dashboard.php\n";
echo "4. Staff Management: http://localhost:8000/admin/staff.php\n";
echo "5. Student Management: http://localhost:8000/admin/students.php\n\n";

echo "🔑 Test Login Credentials:\n";
echo "Admin: admin / admin123\n";
echo "Faculty: sarita_patel / sarita_patel123\n";
echo "Student: rahul_kumar / rahul_kumar123\n\n";

echo "✨ Your GICT Institute is now fully set up with ImgBB image storage!\n";
?>
