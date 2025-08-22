<?php
/**
 * Example Document Upload Script
 * Demonstrates the flexible naming convention for different document types
 */

require_once 'config/database.php';
require_once 'includes/imgbb_helper.php';

echo "📚 Example Document Upload with Flexible Naming Convention\n";
echo "==========================================================\n\n";

// Example user ID (you can change this to any existing user ID)
$user_id = 40; // Example: brijendra

// Check if user exists
$user = getRow("SELECT username, full_name FROM users WHERE id = ?", [$user_id]);
if (!$user) {
    echo "❌ User with ID {$user_id} not found. Please use an existing user ID.\n";
    exit;
}

echo "👤 Uploading documents for: {$user['full_name']} (ID: {$user_id})\n";
echo "📁 Document naming convention: {user_id}_{document_type}.{extension}\n\n";

// Example document types and their descriptions
$document_types = [
    'profile' => 'Profile Picture',
    'marksheet' => 'Educational Marksheet',
    'aadhaar' => 'Aadhaar Card',
    'pan' => 'PAN Card',
    'driving_license' => 'Driving License',
    'passport' => 'Passport',
    'visa' => 'Visa Document',
    'certificate' => 'Professional Certificate',
    'id_proof' => 'Government ID Proof',
    'address_proof' => 'Address Proof Document'
];

echo "📋 Supported Document Types:\n";
foreach ($document_types as $type => $description) {
    echo "   • {$type} - {$description}\n";
}

echo "\n🔍 Example File Names for User ID {$user_id}:\n";
foreach ($document_types as $type => $description) {
    echo "   • {$user_id}_{$type}.jpg\n";
    echo "   • {$user_id}_{$type}.png\n";
    echo "   • {$user_id}_{$type}.pdf\n";
}

echo "\n💡 Benefits of This Naming Convention:\n";
echo "   ✅ Unique identification for each document\n";
echo "   ✅ Easy to track which document belongs to which user\n";
echo "   ✅ Supports any document type (just change the type parameter)\n";
echo "   ✅ No naming conflicts between users\n";
echo "   ✅ Scalable for any number of users and document types\n";
echo "   ✅ Easy to organize and search documents\n";

echo "\n🚀 To upload a document, use:\n";
echo "   uploadImageToImgBB(\$file_path, \$user_id, 'document_type');\n\n";

echo "📝 Example Usage:\n";
echo "   // Upload profile picture\n";
echo "   uploadImageToImgBB('profile.jpg', {$user_id}, 'profile');\n\n";
echo "   // Upload PAN card\n";
echo "   uploadImageToImgBB('pan_card.jpg', {$user_id}, 'pan');\n\n";
echo "   // Upload driving license\n";
echo "   uploadImageToImgBB('license.pdf', {$user_id}, 'driving_license');\n\n";

echo "🎯 The system automatically creates the filename: {user_id}_{document_type}.{extension}\n";
echo "   So 'pan_card.jpg' becomes '{$user_id}_pan.jpg' on ImgBB\n";
?>
