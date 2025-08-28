# User ID Generation System for GICT

## Overview

The GICT application now includes an automated user ID generation system that creates unique identifiers for students, faculty, and admin users. This system ensures that each user gets a unique, meaningful ID that follows a consistent format.

## User ID Format

The system generates user IDs in the following format:

```
[user_type_prefix][current_year][3-digit_sequence_number]
```

### User Type Prefixes

- **f** = Faculty/Staff
- **s** = Student  
- **a** = Admin

### Examples

- `f2025001` = First faculty member created in 2025
- `s2025001` = First student enrolled in 2025
- `a2025001` = First admin created in 2025
- `f2025002` = Second faculty member created in 2025
- `s2025002` = Second student enrolled in 2025

## ImgBB Image Naming Convention

All images uploaded to ImgBB follow a consistent naming pattern:

```
[username]_[type].[extension]
```

### Naming Examples

- **Student Profile Image**: `s2025001_profile.jpg`
- **Student Marksheet**: `s2025001_marksheet.pdf`
- **Student Aadhaar Card**: `s2025001_aadhaar.jpg`
- **Faculty Profile Image**: `f2025001_profile.png`
- **Admin Profile Image**: `a2025001_profile.jpeg`

### Benefits of Consistent Naming

1. **Easy Identification**: Quickly identify image ownership and purpose
2. **Better Organization**: ImgBB dashboard shows organized file names
3. **Consistent Structure**: All uploads follow the same pattern
4. **Professional Appearance**: Clean, organized file management

## How It Works

### 1. Automatic Generation

When creating a new user (student or faculty), the system:

1. Determines the user type (student, faculty, or admin)
2. Gets the current year
3. Queries the database to find the maximum ID for that user type
4. Increments the maximum ID by 1
5. Formats the sequence number with leading zeros (3 digits)
6. Combines: `[prefix][year][formatted_number]`

### 2. Database Integration

The system integrates with the existing `users` table:

- **Students**: `user_type_id = 2`
- **Faculty**: `user_type_id = 3`  
- **Admin**: `user_type_id = 1`

### 3. Collision Prevention

The system includes safety checks to prevent duplicate IDs:

- Checks if the generated ID already exists
- Automatically increments if a collision is detected
- Logs any unexpected duplicates for investigation

### 4. Image Upload Process

When uploading images:

1. **File Validation**: Checks file size, type, and format
2. **Name Generation**: Creates name in format `username_type`
3. **ImgBB Upload**: Uploads to ImgBB with formatted name
4. **URL Storage**: Stores only ImgBB URLs in database
5. **No Local Storage**: Images are never saved locally

## Implementation Files

### Core Helper Files
- `includes/user_id_generator.php` - Main functions for ID generation
- `includes/imgbb_helper.php` - Image upload to ImgBB with naming convention

### Updated Admin Files
- `admin/staff.php` - Faculty creation with auto-generated IDs
- `admin/students.php` - Student creation with auto-generated IDs

### Test Files
- `test_user_id_generator.php` - Test script to verify ID generation
- `test_imgbb_only.php` - Test script to verify ImgBB functionality
- `test_imgbb_naming.php` - Test script to verify naming convention
- `cleanup_local_images.php` - Script to check existing local files

## Functions Available

### User ID Generation

#### `generateUniqueUserId($user_type)`
Generates a unique user ID for the specified user type.

**Parameters:**
- `$user_type` (string): 'faculty', 'student', or 'admin'

**Returns:**
- Generated user ID string (e.g., 'f2025001')
- `false` on failure

**Example:**
```php
$faculty_id = generateUniqueUserId('faculty');
// Returns: 'f2025001'
```

#### `getNextUserIdNumber($user_type)`
Gets the next available ID number for a user type.

**Parameters:**
- `$user_type` (string): 'faculty', 'student', or 'admin'

**Returns:**
- Next available ID number (integer)
- `false` on failure

**Example:**
```php
$next_number = getNextUserIdNumber('student');
// Returns: 2 (if student with ID 1 already exists)
```

#### `validateUserIdFormat($user_id)`
Validates if a user ID follows the correct format.

**Parameters:**
- `$user_id` (string): User ID to validate

**Returns:**
- `true` if valid, `false` otherwise

**Example:**
```php
$is_valid = validateUserIdFormat('f2025001');
// Returns: true
```

#### `parseUserId($user_id)`
Extracts information from a user ID.

**Parameters:**
- `$user_id` (string): User ID to parse

**Returns:**
- Array with 'type', 'year', and 'number' keys
- `false` if invalid

**Example:**
```php
$parsed = parseUserId('f2025001');
// Returns: ['type' => 'faculty', 'year' => 2025, 'number' => 1]
```

### Image Upload Functions

#### `smartUpload($file_path, $name)`
Uploads image to ImgBB with proper naming convention.

**Parameters:**
- `$file_path` (string): Local file path
- `$name` (string): Name in format 'username_type'

**Returns:**
- Upload result array with ImgBB URL and metadata
- `false` on failure

**Example:**
```php
$result = smartUpload($temp_file, 's2025001_profile');
// Returns: ['success' => true, 'url' => 'https://i.ibb.co/...', ...]
```

#### `generateImgBBName($username, $type, $extension)`
Generates proper name for ImgBB uploads.

**Parameters:**
- `$username` (string): User ID (e.g., 's2025001')
- `$type` (string): File type (e.g., 'profile', 'marksheet')
- `$extension` (string): File extension (e.g., 'jpg', 'pdf')

**Returns:**
- Formatted name string

**Example:**
```php
$name = generateImgBBName('s2025001', 'profile', 'jpg');
// Returns: 's2025001_profile.jpg'
```

#### `isImgBBUrl($url)`
Validates if a URL is from ImgBB.

**Parameters:**
- `$url` (string): URL to validate

**Returns:**
- `true` if valid ImgBB URL, `false` otherwise

**Example:**
```php
$is_valid = isImgBBUrl('https://i.ibb.co/abc123/image.jpg');
// Returns: true
```

## Database Schema Requirements

The system requires the following database structure:

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,  -- This will store the generated user ID
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    user_type_id INT NOT NULL,  -- 1=admin, 2=student, 3=faculty
    profile_image VARCHAR(500),  -- Stores ImgBB URLs only
    -- ... other fields
);

CREATE TABLE student_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    document_type ENUM('profile', 'marksheet', 'aadhaar', 'pan', 'other') NOT NULL,
    file_path VARCHAR(500) NOT NULL,  -- Stores ImgBB URLs only
    imgbb_id VARCHAR(255),
    -- ... other fields
);
```

## Benefits

1. **Unique Identification**: Each user gets a guaranteed unique ID
2. **Meaningful Format**: IDs contain information about user type and creation year
3. **Automatic Management**: No manual ID assignment required
4. **Scalable**: Supports up to 999 users per type per year
5. **Consistent**: Follows the same pattern across all user types
6. **Year-based**: Easy to identify when users were created
7. **Organized Images**: All ImgBB uploads use consistent naming
8. **No Local Storage**: Images are stored only in ImgBB cloud
9. **Professional Management**: Clean, organized file structure

## Usage Examples

### Creating a New Faculty Member

```php
require_once 'includes/user_id_generator.php';
require_once 'includes/imgbb_helper.php';

// Generate unique user ID
$faculty_id = generateUniqueUserId('faculty');

if ($faculty_id) {
    // Upload profile image to ImgBB
    $imgbb_result = smartUpload($profile_file['tmp_name'], $faculty_id . '_profile');
    
    if ($imgbb_result && $imgbb_result['success']) {
        $profile_image_url = $imgbb_result['url'];
        
        // Insert into database
        $sql = "INSERT INTO users (username, password, full_name, user_type_id, profile_image, ...) 
                VALUES (?, ?, ?, 3, ?, ...)";
        $result = insertData($sql, [$faculty_id, $hashed_password, $full_name, $profile_image_url, ...]);
        
        if ($result) {
            echo "Faculty created with ID: $faculty_id";
            echo "Profile image: <a href='$profile_image_url'>View Image</a>";
        }
    }
}
```

### Creating a New Student

```php
require_once 'includes/user_id_generator.php';
require_once 'includes/imgbb_helper.php';

// Generate unique user ID
$student_id = generateUniqueUserId('student');

if ($student_id) {
    // Upload profile image to ImgBB
    $profile_result = smartUpload($profile_file['tmp_name'], $student_id . '_profile');
    
    // Upload marksheet to ImgBB
    $marksheet_result = smartUpload($marksheet_file['tmp_name'], $student_id . '_marksheet');
    
    if ($profile_result && $marksheet_result) {
        $profile_image_url = $profile_result['url'];
        $marksheet_url = $marksheet_result['url'];
        
        // Insert into database
        $sql = "INSERT INTO users (username, password, full_name, user_type_id, profile_image, ...) 
                VALUES (?, ?, ?, 2, ?, ...)";
        $result = insertData($sql, [$student_id, $hashed_password, $full_name, $profile_image_url, ...]);
        
        if ($result) {
            echo "Student enrolled with ID: $student_id";
            echo "Profile image: <a href='$profile_image_url'>View Image</a>";
            echo "Marksheet: <a href='$marksheet_url'>View Document</a>";
        }
    }
}
```

## Testing

Run the test scripts to verify functionality:

```bash
# Test user ID generation
php test_user_id_generator.php

# Test ImgBB functionality
php test_imgbb_only.php

# Test naming convention
php test_imgbb_naming.php

# Check for existing local files
php cleanup_local_images.php
```

These scripts will test:
- ID generation for all user types
- ImgBB connection and uploads
- Naming convention compliance
- Database URL validation
- Local file cleanup recommendations

## Error Handling

The system includes comprehensive error handling:

- Database connection failures
- Invalid user types
- SQL execution errors
- Duplicate ID detection
- ImgBB upload failures
- File validation errors
- Logging of all errors for debugging

## Future Enhancements

Potential improvements for future versions:

1. **Multi-year Support**: Handle year transitions automatically
2. **Branch-specific IDs**: Include institute/branch identifiers
3. **Custom Prefixes**: Allow custom prefixes for different institutes
4. **ID Recycling**: Reuse IDs from deleted users
5. **Bulk Generation**: Generate multiple IDs at once for bulk imports
6. **Image Optimization**: Automatic image resizing and compression
7. **Batch Uploads**: Upload multiple images simultaneously
8. **Image Categories**: Organize images by type and purpose

## Support

For issues or questions about the user ID generation system:

1. Check the error logs for detailed error messages
2. Run the test scripts to verify functionality
3. Ensure database connectivity and proper table structure
4. Verify that the helper files are accessible
5. Check ImgBB API key configuration
6. Test image upload functionality

## Version History

- **v1.0** - Initial implementation with basic ID generation
- **v1.1** - Added ImgBB integration with consistent naming
- **v1.2** - Removed local storage fallback, ImgBB only
- Support for faculty, student, and admin user types
- Automatic year-based ID generation
- Collision detection and prevention
- Comprehensive validation and parsing functions
- Consistent ImgBB naming convention
- No local file storage
- Enhanced error logging and debugging
