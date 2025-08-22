<?php
/**
 * Upload Existing Faculty Images to ImgBB
 * This script uploads the existing faculty images to ImgBB and updates the database
 */

require_once 'config/database.php';
require_once 'includes/imgbb_helper.php';

// Test ImgBB connection first
echo "🔍 Testing ImgBB Connection...\n";
$status = testImgBBConnection();

if (!$status['ready_for_upload']) {
    echo "❌ ImgBB not ready. Please check configuration.\n";
    echo "cURL: " . ($status['curl_available'] ? '✅' : '❌') . "\n";
    echo "API Key: " . ($status['api_key_configured'] ? '✅' : '❌') . "\n";
    echo "API Connection: " . $status['api_connection'] . "\n";
    exit(1);
}

echo "✅ ImgBB connection successful!\n\n";

// Faculty data with existing images
$faculty_data = [
    [
        'username' => 'sarita_patel',
        'full_name' => 'Sarita Patel',
        'email' => 'sarita@gict.com',
        'phone' => '5555555551',
        'address' => 'Delhi, India',
        'date_of_birth' => '1985-05-15',
        'gender' => 'female',
        'qualification' => 'M.Tech Computer Science',
        'experience_years' => 8,
        'joining_date' => '2020-01-01',
        'image_path' => 'assets/images/sarita.png',
        'specialty' => 'Tally'
    ],
    [
        'username' => 'anand_sir',
        'full_name' => 'Anand Sir',
        'email' => 'anand@gict.com',
        'phone' => '5555555552',
        'address' => 'Mumbai, India',
        'date_of_birth' => '1983-08-20',
        'gender' => 'male',
        'qualification' => 'M.Tech Information Technology',
        'experience_years' => 10,
        'joining_date' => '2019-03-01',
        'image_path' => 'assets/images/anand sir.png',
        'specialty' => 'Web Development'
    ],
    [
        'username' => 'mukesh_gupta',
        'full_name' => 'Mukesh Gupta',
        'email' => 'mukesh@gict.com',
        'phone' => '5555555553',
        'address' => 'Chennai, India',
        'date_of_birth' => '1987-03-12',
        'gender' => 'male',
        'qualification' => 'B.Tech Computer Science',
        'experience_years' => 6,
        'joining_date' => '2021-01-01',
        'image_path' => 'assets/images/mukesh.png',
        'specialty' => 'Hardware Networking'
    ],
    [
        'username' => 'madhu_maam',
        'full_name' => 'Madhu Ma\'am',
        'email' => 'madhu@gict.com',
        'phone' => '5555555554',
        'address' => 'Bangalore, India',
        'date_of_birth' => '1989-07-18',
        'gender' => 'female',
        'qualification' => 'M.Tech Computer Science',
        'experience_years' => 5,
        'joining_date' => '2022-01-01',
        'image_path' => 'assets/images/madhu.jpeg',
        'specialty' => 'Beauty & Aesthetics'
    ],
    [
        'username' => 'tanu_tiwari',
        'full_name' => 'Tanu Tiwari',
        'email' => 'tanu@gict.com',
        'phone' => '5555555555',
        'address' => 'Hyderabad, India',
        'date_of_birth' => '1990-11-25',
        'gender' => 'female',
        'qualification' => 'Ph.D Computer Science',
        'experience_years' => 7,
        'joining_date' => '2020-06-01',
        'image_path' => 'assets/images/tanu.jpeg',
        'specialty' => 'Artificial Intelligence'
    ],
    [
        'username' => 'anjali_prajapati',
        'full_name' => 'Anjali Prajapati',
        'email' => 'anjali@gict.com',
        'phone' => '5555555556',
        'address' => 'Pune, India',
        'date_of_birth' => '1988-04-08',
        'gender' => 'female',
        'qualification' => 'M.Tech Information Technology',
        'experience_years' => 6,
        'joining_date' => '2021-03-01',
        'image_path' => 'assets/images/anjali.jpeg',
        'specialty' => 'Professional Courses'
    ]
];

echo "🎯 Processing " . count($faculty_data) . " faculty members...\n\n";

$success_count = 0;
$error_count = 0;

foreach ($faculty_data as $index => $faculty) {
    $faculty_number = $index + 1;
    echo "👨‍🏫 Processing Faculty {$faculty_number}/" . count($faculty_data) . ": {$faculty['full_name']}\n";
    
    try {
        // Check if username already exists
        $checkSql = "SELECT id FROM users WHERE username = ?";
        $existingUser = getRow($checkSql, [$faculty['username']]);
        
        if ($existingUser) {
            echo "   ⚠️  Username '{$faculty['username']}' already exists, updating profile image...\n";
            
            // Upload image to ImgBB
            if (file_exists($faculty['image_path'])) {
                $imgbb_result = smartUpload(
                    $faculty['image_path'], 
                    'faculty_' . $faculty['username'] . '_' . time()
                );
                
                if ($imgbb_result && $imgbb_result['success']) {
                    // Update existing faculty with new profile image
                    $updateSql = "UPDATE users SET profile_image = ? WHERE username = ?";
                    $result = updateData($updateSql, [$imgbb_result['url'], $faculty['username']]);
                    
                    if ($result) {
                        echo "   ✅ Profile image updated successfully!\n";
                        echo "      ImgBB URL: {$imgbb_result['url']}\n";
                        $success_count++;
                    } else {
                        echo "   ❌ Failed to update profile image in database\n";
                        $error_count++;
                    }
                } else {
                    echo "   ❌ Failed to upload image to ImgBB\n";
                    $error_count++;
                }
            } else {
                echo "   ❌ Image file not found: {$faculty['image_path']}\n";
                $error_count++;
            }
        } else {
            // Create new faculty member
            echo "   ➕ Creating new faculty member...\n";
            
            // Upload image to ImgBB
            $profile_image_url = '';
            if (file_exists($faculty['image_path'])) {
                $imgbb_result = smartUpload(
                    $faculty['image_path'], 
                    'faculty_' . $faculty['username'] . '_' . time()
                );
                
                if ($imgbb_result && $imgbb_result['success']) {
                    $profile_image_url = $imgbb_result['url'];
                } else {
                    echo "   ❌ Failed to upload image to ImgBB\n";
                    $error_count++;
                    continue;
                }
            } else {
                echo "   ❌ Image file not found: {$faculty['image_path']}\n";
                $error_count++;
                continue;
            }
            
            // Generate default password
            $default_password = $faculty['username'] . '123';
            $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
            
            // Insert faculty into database
            $sql = "INSERT INTO users (username, password, email, full_name, user_type, phone, address, date_of_birth, gender, qualification, experience_years, joining_date, profile_image, status) VALUES (?, ?, ?, ?, 'faculty', ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
            $result = insertData($sql, [
                $faculty['username'], 
                $hashed_password, 
                $faculty['email'], 
                $faculty['full_name'], 
                $faculty['phone'], 
                $faculty['address'], 
                $faculty['date_of_birth'], 
                $faculty['gender'], 
                $faculty['qualification'], 
                $faculty['experience_years'], 
                $faculty['joining_date'], 
                $profile_image_url
            ]);
            
            if ($result) {
                echo "   ✅ Faculty member created successfully!\n";
                echo "      Username: {$faculty['username']}\n";
                echo "      Password: {$default_password}\n";
                echo "      Profile Image: {$profile_image_url}\n";
                $success_count++;
            } else {
                echo "   ❌ Failed to create faculty member in database\n";
                $error_count++;
            }
        }
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $error_count++;
    }
    
    echo "\n";
    
    // Small delay to avoid overwhelming the system
    usleep(200000); // 0.2 second
}

echo "🎉 Faculty Image Upload Complete!\n";
echo "================================\n";
echo "✅ Successfully processed: {$success_count} faculty members\n";
echo "❌ Failed: {$error_count} faculty members\n";
echo "📊 Total processed: " . count($faculty_data) . " faculty members\n\n";

if ($success_count > 0) {
    echo "🔗 Next Steps:\n";
    echo "1. Visit: http://localhost:8000/gallery.php to see faculty with ImgBB images\n";
    echo "2. Visit: http://localhost:8000/admin/staff.php to manage faculty\n";
    echo "3. Login with any faculty account (username: username123)\n\n";
    
    echo "📋 Sample Faculty Credentials:\n";
    foreach (array_slice($faculty_data, 0, 3) as $faculty) {
        echo "   {$faculty['username']} / {$faculty['username']}123\n";
    }
    echo "   ... and " . ($success_count - 3) . " more\n";
}

echo "\n✨ All done! Your faculty images are now stored on ImgBB!\n";
?>
