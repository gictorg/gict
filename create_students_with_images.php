<?php
/**
 * Bulk Student Creation Script with ImgBB Images
 * This script creates multiple students with profile images uploaded to ImgBB
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

// Sample student data with realistic names and details
$students_data = [
    [
        'username' => 'rahul_kumar',
        'full_name' => 'Rahul Kumar',
        'email' => 'rahul.kumar@gmail.com',
        'phone' => '9876543210',
        'address' => 'Delhi, India',
        'date_of_birth' => '2000-05-15',
        'gender' => 'male',
        'qualification' => '12th Standard',
        'joining_date' => '2024-01-15'
    ],
    [
        'username' => 'priya_sharma',
        'full_name' => 'Priya Sharma',
        'email' => 'priya.sharma@gmail.com',
        'phone' => '9876543211',
        'address' => 'Mumbai, India',
        'date_of_birth' => '1999-08-20',
        'gender' => 'female',
        'qualification' => '12th Standard',
        'joining_date' => '2024-01-20'
    ],
    [
        'username' => 'arjun_singh',
        'full_name' => 'Arjun Singh',
        'email' => 'arjun.singh@gmail.com',
        'phone' => '9876543212',
        'address' => 'Chennai, India',
        'date_of_birth' => '2001-03-12',
        'gender' => 'male',
        'qualification' => '12th Standard',
        'joining_date' => '2024-02-01'
    ],
    [
        'username' => 'kavya_patel',
        'full_name' => 'Kavya Patel',
        'email' => 'kavya.patel@gmail.com',
        'phone' => '9876543213',
        'address' => 'Bangalore, India',
        'date_of_birth' => '2002-07-18',
        'gender' => 'female',
        'qualification' => '12th Standard',
        'joining_date' => '2024-02-15'
    ],
    [
        'username' => 'vikram_verma',
        'full_name' => 'Vikram Verma',
        'email' => 'vikram.verma@gmail.com',
        'phone' => '9876543214',
        'address' => 'Hyderabad, India',
        'date_of_birth' => '2000-11-25',
        'gender' => 'male',
        'qualification' => '12th Standard',
        'joining_date' => '2024-03-01'
    ],
    [
        'username' => 'neha_gupta',
        'full_name' => 'Neha Gupta',
        'email' => 'neha.gupta@gmail.com',
        'phone' => '9876543215',
        'address' => 'Pune, India',
        'date_of_birth' => '2001-04-08',
        'gender' => 'female',
        'qualification' => '12th Standard',
        'joining_date' => '2024-03-10'
    ],
    [
        'username' => 'aditya_malhotra',
        'full_name' => 'Aditya Malhotra',
        'email' => 'aditya.malhotra@gmail.com',
        'phone' => '9876543216',
        'address' => 'Kolkata, India',
        'date_of_birth' => '2000-09-14',
        'gender' => 'male',
        'qualification' => '12th Standard',
        'joining_date' => '2024-03-20'
    ],
    [
        'username' => 'isha_reddy',
        'full_name' => 'Isha Reddy',
        'email' => 'isha.reddy@gmail.com',
        'phone' => '9876543217',
        'address' => 'Ahmedabad, India',
        'date_of_birth' => '2001-12-03',
        'gender' => 'female',
        'qualification' => '12th Standard',
        'joining_date' => '2024-04-01'
    ],
    [
        'username' => 'rajat_khanna',
        'full_name' => 'Rajat Khanna',
        'email' => 'rajat.khanna@gmail.com',
        'phone' => '9876543218',
        'address' => 'Jaipur, India',
        'date_of_birth' => '2000-06-22',
        'gender' => 'male',
        'qualification' => '12th Standard',
        'joining_date' => '2024-04-10'
    ],
    [
        'username' => 'ananya_tiwari',
        'full_name' => 'Ananya Tiwari',
        'email' => 'ananya.tiwari@gmail.com',
        'phone' => '9876543219',
        'address' => 'Lucknow, India',
        'date_of_birth' => '2001-01-30',
        'gender' => 'female',
        'qualification' => '12th Standard',
        'joining_date' => '2024-04-20'
    ]
];

echo "🎯 Creating " . count($students_data) . " students with ImgBB profile images...\n\n";

$success_count = 0;
$error_count = 0;

foreach ($students_data as $index => $student_data) {
    $student_number = $index + 1;
    echo "👤 Creating Student {$student_number}/" . count($students_data) . ": {$student_data['full_name']}\n";
    
    try {
        // Check if username already exists
        $checkSql = "SELECT id FROM users WHERE username = ?";
        $existingUser = getRow($checkSql, [$student_data['username']]);
        
        if ($existingUser) {
            echo "   ⚠️  Username '{$student_data['username']}' already exists, skipping...\n";
            $error_count++;
            continue;
        }
        
        // Create a simple profile image and upload to ImgBB
        $profile_image_url = '';
        
        // Create a simple colored square with initials as a profile image
        $initials = strtoupper(substr($student_data['full_name'], 0, 1));
        $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF)); // Random color
        
        // Create a simple SVG image with initials
        $svg_content = '<svg width="200" height="200" xmlns="http://www.w3.org/2000/svg">
            <rect width="200" height="200" fill="' . $color . '"/>
            <text x="100" y="120" font-family="Arial, sans-serif" font-size="80" font-weight="bold" text-anchor="middle" fill="white">' . $initials . '</text>
        </svg>';
        
        // Save SVG to temporary file
        $temp_file = sys_get_temp_dir() . '/temp_profile_' . $student_data['username'] . '.svg';
        file_put_contents($temp_file, $svg_content);
        
        // Upload SVG to ImgBB
        $imgbb_result = smartUpload(
            $temp_file, 
            'student_' . $student_data['username'] . '_' . time()
        );
        
        // Clean up temporary file
        unlink($temp_file);
        
        if ($imgbb_result && $imgbb_result['success']) {
            $profile_image_url = $imgbb_result['url'];
            echo "   ✅ Profile image uploaded to ImgBB: {$profile_image_url}\n";
        } else {
            echo "   ❌ Failed to upload profile image to ImgBB\n";
            $error_count++;
            continue;
        }
        
        // Generate default password
        $default_password = $student_data['username'] . '123';
        $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
        
        // Insert student into database
        $sql = "INSERT INTO users (username, password, email, full_name, user_type, phone, address, date_of_birth, gender, qualification, joining_date, profile_image, status) VALUES (?, ?, ?, ?, 'student', ?, ?, ?, ?, ?, ?, ?, 'active')";
        $result = insertData($sql, [
            $student_data['username'], 
            $hashed_password, 
            $student_data['email'], 
            $student_data['full_name'], 
            $student_data['phone'], 
            $student_data['address'], 
            $student_data['date_of_birth'], 
            $student_data['gender'], 
            $student_data['qualification'], 
            $student_data['joining_date'], 
            $profile_image_url
        ]);
        
        if ($result) {
            echo "   ✅ Student created successfully!\n";
            echo "      Username: {$student_data['username']}\n";
            echo "      Password: {$default_password}\n";
            echo "      Profile Image: {$profile_image_url}\n";
            $success_count++;
        } else {
            echo "   ❌ Failed to create student in database\n";
            $error_count++;
        }
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $error_count++;
    }
    
    echo "\n";
    
    // Small delay to avoid overwhelming the system
    usleep(300000); // 0.3 second
}

echo "🎉 Student Creation Complete!\n";
echo "================================\n";
echo "✅ Successfully created: {$success_count} students\n";
echo "❌ Failed: {$error_count} students\n";
echo "📊 Total processed: " . count($students_data) . " students\n\n";

if ($success_count > 0) {
    echo "🔗 Next Steps:\n";
    echo "1. Visit: http://localhost:8000/gallery.php to see students with ImgBB images\n";
    echo "2. Visit: http://localhost:8000/admin/students.php to manage students\n";
    echo "3. Visit: http://localhost:8000/admin/add-student.php to add more students\n";
    echo "4. Login with any student account (username: username123)\n\n";
    
    echo "📋 Sample Student Credentials:\n";
    foreach (array_slice($students_data, 0, 3) as $student) {
        echo "   {$student['username']} / {$student['username']}123\n";
    }
    echo "   ... and " . ($success_count - 3) . " more\n";
}

echo "\n✨ All done! Your GICT Institute now has students with ImgBB profile images!\n";
?>
