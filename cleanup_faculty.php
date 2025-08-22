<?php
/**
 * Cleanup Faculty Without Images
 * Remove faculty members who don't have profile images
 */

require_once 'config/database.php';

echo "🧹 Cleaning Up Faculty Without Images\n";
echo "====================================\n\n";

// Get all faculty members
$faculty_sql = "SELECT id, username, full_name, profile_image FROM users WHERE user_type = 'faculty'";
$faculty_members = getRows($faculty_sql);

if (empty($faculty_members)) {
    echo "❌ No faculty members found!\n";
    exit(1);
}

echo "📚 Current Faculty Status:\n";
echo "==========================\n";

$to_remove = [];
$to_keep = [];

foreach ($faculty_members as $faculty) {
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
    
    echo "{$image_status} {$faculty['full_name']} ({$faculty['username']})\n";
    echo "   Image: " . ($faculty['profile_image'] ?: 'No Image') . "\n";
    if ($storage_type) {
        echo "   Storage: {$storage_type}\n";
    }
    
    if (empty($faculty['profile_image'])) {
        $to_remove[] = $faculty;
        echo "   ❌ Will be REMOVED\n";
    } else {
        $to_keep[] = $faculty;
        echo "   ✅ Will be KEPT\n";
    }
    echo "\n";
}

echo "📊 Summary:\n";
echo "===========\n";
echo "Total Faculty: " . count($faculty_members) . "\n";
echo "To Keep: " . count($to_keep) . "\n";
echo "To Remove: " . count($to_remove) . "\n\n";

if (empty($to_remove)) {
    echo "✅ All faculty members have images. No cleanup needed!\n";
    exit(0);
}

echo "🗑️  Removing Faculty Without Images:\n";
echo "===================================\n";

$removed_count = 0;
$error_count = 0;

foreach ($to_remove as $faculty) {
    echo "🗑️  Removing: {$faculty['full_name']} ({$faculty['username']})\n";
    
    try {
        // Delete the faculty member
        $deleteSql = "DELETE FROM users WHERE id = ?";
        $result = deleteData($deleteSql, [$faculty['id']]);
        
        if ($result > 0) {
            echo "   ✅ Successfully removed!\n";
            $removed_count++;
        } else {
            echo "   ❌ Failed to remove!\n";
            $error_count++;
        }
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $error_count++;
    }
    
    echo "\n";
}

echo "🎉 Cleanup Complete!\n";
echo "===================\n";
echo "✅ Successfully removed: {$removed_count} faculty members\n";
echo "❌ Failed to remove: {$error_count} faculty members\n";
echo "📚 Remaining faculty: " . count($to_keep) . "\n\n";

echo "✨ Faculty cleanup completed successfully!\n";
?>
