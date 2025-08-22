<?php
/**
 * Upload All Images to New ImgBB Account
 * This script uploads all existing faculty and student images to the new ImgBB account
 * and updates the database with the new links.
 * 
 * Image Naming Convention: {user_id}_{document_type}.{extension}
 * Examples:
 * - Profile pictures: 9_profile.png, 40_profile.jpeg
 * - Marksheets: 9_marksheet.pdf, 40_marksheet.jpg
 * - Aadhaar cards: 9_aadhaar.jpg, 40_aadhaar.png
 * - PAN cards: 9_pan.jpg, 40_pan.png
 * - Driving licenses: 9_driving_license.jpg, 40_driving_license.png
 * - Any other documents: 9_visa.jpg, 40_passport.pdf, etc.
 */

require_once 'config/database.php';
require_once 'includes/imgbb_helper.php';

echo "🚀 Starting Image Upload to New ImgBB Account\n";
echo "==============================================\n\n";

// Faculty images to upload
$faculty_images = [
    'anand sir.png' => 'anand_sir',
    'brijendra.jpeg' => 'brijendra',
    'madhu.jpeg' => 'madhu',
    'mukesh.png' => 'mukesh',
    'sarita.png' => 'sarita',
    'tanu.jpeg' => 'tanu',
    'techno.jpeg' => 'techno'
];

// Student images to upload
$student_images = [
    '1.jpg' => 'student_1',
    '2.jpg' => 'student_2',
    '3.jpeg' => 'student_3',
    '4.jpeg' => 'student_4',
    '5.jpg' => 'student_5',
    '6.jpeg' => 'student_6',
    'anjali.jpeg' => 'anjali'
];

// Function to upload image to ImgBB with user ID naming convention
function uploadImageToImgBB($image_path, $user_id, $document_type) {
    if (!file_exists($image_path)) {
        echo "❌ Image not found: {$image_path}\n";
        return false;
    }
    
    // Get file extension
    $extension = pathinfo($image_path, PATHINFO_EXTENSION);
    
    // Create image name with user ID convention: {user_id}_{document_type}.{extension}
    // This supports any document type: profile, marksheet, aadhaar, pan, driving_license, etc.
    $image_name = $user_id . '_' . $document_type . '.' . $extension;
    
    $imgbb_result = smartUpload($image_path, $image_name);
    
    if ($imgbb_result && isset($imgbb_result['url'])) {
        echo "✅ {$image_name} uploaded successfully: {$imgbb_result['url']}\n";
        return $imgbb_result['url'];
    } else {
        echo "❌ Failed to upload {$image_name}\n";
        return false;
    }
}

// Function to update faculty profile image in database
function updateFacultyImage($username, $image_url) {
    $sql = "UPDATE users SET profile_image = ? WHERE username = ? AND user_type = 'faculty'";
    $result = updateData($sql, [$image_url, $username]);
    
    if ($result !== false) {
        echo "   📊 Database updated for faculty: {$username}\n";
        return true;
    } else {
        echo "   ❌ Failed to update database for faculty: {$username}\n";
        return false;
    }
}

// Function to update student profile image in database
function updateStudentImage($username, $image_url) {
    $sql = "UPDATE users SET profile_image = ? WHERE username = ? AND user_type = 'student'";
    $result = updateData($sql, [$image_url, $username]);
    
    if ($result !== false) {
        echo "   📊 Database updated for student: {$username}\n";
        return true;
    } else {
        echo "   ❌ Failed to update database for student: {$username}\n";
        return false;
    }
}

// Function to create faculty if they don't exist
function createFacultyIfNotExists($username, $full_name, $image_url) {
    // Check if faculty exists
    $check_sql = "SELECT id FROM users WHERE username = ? AND user_type = 'faculty'";
    $existing = getRow($check_sql, [$username]);
    
    if ($existing) {
        echo "   👤 Faculty already exists: {$username}\n";
        return $existing['id'];
    }
    
    // Create new faculty
    $insert_sql = "INSERT INTO users (username, full_name, email, password, user_type, qualification, experience_years, joining_date, profile_image, status) VALUES (?, ?, ?, ?, 'faculty', ?, ?, ?, ?, 'active')";
    
    $email = $username . '@gict.edu';
    $password = password_hash('password123', PASSWORD_DEFAULT);
    $qualification = 'Bachelor\'s Degree';
    $experience_years = rand(2, 8);
    $joining_date = date('Y-m-d', strtotime('-' . rand(1, 24) . ' months'));
    
    $result = insertData($insert_sql, [$username, $full_name, $email, $password, $qualification, $experience_years, $joining_date, $image_url]);
    
    if ($result !== false) {
        echo "   👤 New faculty created: {$username}\n";
        // Get the inserted user ID
        $user_id = getRow("SELECT id FROM users WHERE username = ?", [$username])['id'];
        return $user_id;
    } else {
        echo "   ❌ Failed to create faculty: {$username}\n";
        return false;
    }
}

// Function to create student if they don't exist
function createStudentIfNotExists($username, $full_name, $image_url) {
    // Check if student exists
    $check_sql = "SELECT id FROM users WHERE username = ? AND user_type = 'student'";
    $existing = getRow($check_sql, [$username]);
    
    if ($existing) {
        echo "   👤 Student already exists: {$username}\n";
        return $existing['id'];
    }
    
    // Create new student
    $insert_sql = "INSERT INTO users (username, full_name, email, password, user_type, date_of_birth, gender, qualification, joining_date, profile_image, status) VALUES (?, ?, ?, ?, 'student', ?, ?, ?, ?, ?, 'active')";
    
    $email = $username . '@gict.edu';
    $password = password_hash('password123', PASSWORD_DEFAULT);
    $date_of_birth = date('Y-m-d', strtotime('-' . rand(18, 25) . ' years'));
    $gender = rand(0, 1) ? 'Male' : 'Female';
    $qualification = '12th Pass';
    $joining_date = date('Y-m-d', strtotime('-' . rand(1, 12) . ' months'));
    
    $result = insertData($insert_sql, [$username, $full_name, $email, $password, $date_of_birth, $gender, $qualification, $joining_date, $image_url]);
    
    if ($result !== false) {
        echo "   👤 New student created: {$username}\n";
        // Get the inserted user ID
        $user_id = getRow("SELECT id FROM users WHERE username = ?", [$username])['id'];
        return $user_id;
    } else {
        return false;
    }
}

echo "📤 Uploading Faculty Images...\n";
echo "-------------------------------\n";

$faculty_uploads = [];
foreach ($faculty_images as $image_file => $username) {
    $image_path = "assets/images/{$image_file}";
    $full_name = ucwords(str_replace('_', ' ', $username));
    
    echo "\n🖼️ Processing: {$image_file} for {$full_name}\n";
    
    // First create faculty if they don't exist (without image)
    $user_id = createFacultyIfNotExists($username, $full_name, '');
    
    if ($user_id) {
        // Upload image to ImgBB with user ID naming convention
        $image_url = uploadImageToImgBB($image_path, $user_id, 'profile');
        
        if ($image_url) {
            $faculty_uploads[$username] = $image_url;
            
            // Update database with the new image URL
            updateFacultyImage($username, $image_url);
        }
    }
}

echo "\n📤 Uploading Student Images...\n";
echo "--------------------------------\n";

$student_uploads = [];
foreach ($student_images as $image_file => $username) {
    $image_path = "assets/images/{$image_file}";
    $full_name = ucwords(str_replace('_', ' ', $username));
    
    echo "\n🖼️ Processing: {$image_file} for {$full_name}\n";
    
    // First create student if they don't exist (without image)
    $user_id = createStudentIfNotExists($username, $full_name, '');
    
    if ($user_id) {
        // Upload image to ImgBB with user ID naming convention
        $image_url = uploadImageToImgBB($image_path, $user_id, 'profile');
        
        if ($image_url) {
            $student_uploads[$username] = $image_url;
            
            // Update database with the new image URL
            updateStudentImage($username, $image_url);
        }
    }
}

echo "\n📊 Upload Summary\n";
echo "==================\n";
echo "Faculty Images Uploaded: " . count($faculty_uploads) . "\n";
echo "Student Images Uploaded: " . count($student_uploads) . "\n";

if (!empty($faculty_uploads)) {
    echo "\n👨‍🏫 Faculty Images:\n";
    foreach ($faculty_uploads as $username => $url) {
        echo "   {$username}: {$url}\n";
    }
}

if (!empty($student_uploads)) {
    echo "\n👨‍🎓 Student Images:\n";
    foreach ($student_uploads as $username => $url) {
        echo "   {$username}: {$url}\n";
    }
}

echo "\n✅ All images have been uploaded to your new ImgBB account and the database has been updated!\n";
echo "🔗 You can now view these images on your homepage and admin dashboard.\n";
?>
