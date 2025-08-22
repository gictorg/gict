<?php
/**
 * Update Student Names
 * Update student names to be more professional and descriptive
 */

require_once 'config/database.php';

echo "✏️  Updating Student Names\n";
echo "==========================\n\n";

// Student name updates
$student_updates = [
    [
        'username' => 'student1',
        'new_name' => 'Aarav Singh',
        'new_qualification' => 'Mobile App Development'
    ],
    [
        'username' => 'student2',
        'new_name' => 'Zara Khan',
        'new_qualification' => 'Game Development'
    ],
    [
        'username' => 'student3',
        'new_name' => 'Vivaan Patel',
        'new_qualification' => 'Blockchain Technology'
    ],
    [
        'username' => 'student4',
        'new_name' => 'Kiara Sharma',
        'new_qualification' => 'Internet of Things (IoT)'
    ]
];

echo "🎯 Updating " . count($student_updates) . " student names...\n\n";

$success_count = 0;
$error_count = 0;

foreach ($student_updates as $update) {
    echo "👤 Updating: {$update['username']}\n";
    
    try {
        // Update student name and qualification
        $updateSql = "UPDATE users SET full_name = ?, qualification = ? WHERE username = ?";
        $result = updateData($updateSql, [
            $update['new_name'], 
            $update['new_qualification'], 
            $update['username']
        ]);
        
        if ($result > 0) {
            echo "   ✅ Updated to: {$update['new_name']} - {$update['new_qualification']}\n";
            $success_count++;
        } else {
            echo "   ⚠️  No changes made\n";
            $success_count++; // Still count as success
        }
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $error_count++;
    }
    
    echo "\n";
}

echo "🎉 Student Name Update Complete!\n";
echo "================================\n";
echo "✅ Successfully updated: {$success_count} students\n";
echo "❌ Failed: {$error_count} students\n";
echo "📊 Total processed: " . count($student_updates) . " students\n\n";

echo "✨ Student names have been updated to be more professional!\n";
?>
