<?php
/**
 * Cloudinary Helper for GICT Institute
 * Handles image and file uploads to Cloudinary
 */

// Cloudinary Configuration
// Set these via environment variables or update directly
if (!defined('CLOUDINARY_CLOUD_NAME')) {
    define('CLOUDINARY_CLOUD_NAME', getenv('CLOUDINARY_CLOUD_NAME') ?: 'dgkyohg8p');
}
if (!defined('CLOUDINARY_API_KEY')) {
    define('CLOUDINARY_API_KEY', getenv('CLOUDINARY_API_KEY') ?: '882663432637246');
}
if (!defined('CLOUDINARY_API_SECRET')) {
    define('CLOUDINARY_API_SECRET', getenv('CLOUDINARY_API_SECRET') ?: 'ugWzEVh1EqjhX_IQ4YyGRV14nzA');
}

// Check if Cloudinary PHP SDK is available
if (!class_exists('Cloudinary\Cloudinary')) {
    // Try to load via Composer
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    }
}

/**
 * Get Cloudinary instance
 * @return \Cloudinary\Cloudinary|false Cloudinary instance or false on failure
 */
function getCloudinaryInstance() {
    try {
        if (!class_exists('Cloudinary\Cloudinary')) {
            return false;
        }

        if (empty(CLOUDINARY_CLOUD_NAME) || empty(CLOUDINARY_API_KEY) || empty(CLOUDINARY_API_SECRET)) {
            return false;
        }

        $cloudinary = new \Cloudinary\Cloudinary([
            'cloud' => [
                'cloud_name' => CLOUDINARY_CLOUD_NAME,
                'api_key' => CLOUDINARY_API_KEY,
                'api_secret' => CLOUDINARY_API_SECRET,
            ],
            'url' => [
                'secure' => true
            ]
        ]);

        return $cloudinary;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Upload file to Cloudinary
 * @param string $file_path Local file path
 * @param string $name Custom name/folder for the file
 * @param array $options Additional upload options
 * @return array|false Upload result or false on failure
 */
function uploadToCloudinary($file_path, $name = '', $options = []) {
    try {
        // Check if file exists
        if (!file_exists($file_path)) {
            return ['success' => false, 'error' => "File does not exist: " . $file_path];
        }

        // Get Cloudinary instance
        $cloudinary = getCloudinaryInstance();
        if (!$cloudinary) {
            $error = "Could not initialize Cloudinary. Check your credentials.";
            return ['success' => false, 'error' => $error];
        }

        // Prepare upload options
        $upload_options = [
            'folder' => 'gict_institute', // Base folder
            'use_filename' => true,
            'unique_filename' => true,
            'overwrite' => false,
            'resource_type' => 'auto', // Auto-detect image, video, raw, etc.
        ];

        // Add custom folder/name if provided
        if (!empty($name)) {
            // Clean the name for use as folder/filename (allow dots for microtime)
            $clean_name = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $name);
            $upload_options['public_id'] = 'gict_institute/' . $clean_name;
        }

        // Merge with custom options (custom options override defaults)
        $upload_options = array_merge($upload_options, $options);
        
        // If overwrite is true, disable unique_filename to allow overwriting
        if (isset($upload_options['overwrite']) && $upload_options['overwrite'] === true) {
            $upload_options['unique_filename'] = false;
        }

        // Upload file
        $result = $cloudinary->uploadApi()->upload($file_path, $upload_options);

        if ($result && isset($result['secure_url'])) {
            return [
                'success' => true,
                'url' => $result['secure_url'],
                'display_url' => $result['secure_url'],
                'public_id' => $result['public_id'] ?? '',
                'format' => $result['format'] ?? '',
                'width' => $result['width'] ?? null,
                'height' => $result['height'] ?? null,
                'bytes' => $result['bytes'] ?? 0,
                'storage_type' => 'cloudinary'
            ];
        } else {
            $error = "Upload succeeded but no URL returned";
            return ['success' => false, 'error' => $error];
        }

    } catch (\Cloudinary\Api\Exception\ApiError $e) {
        $error = "Cloudinary API error: " . $e->getMessage();
        return ['success' => false, 'error' => $error];
    } catch (Exception $e) {
        $error = "Upload failed: " . $e->getMessage();
        return ['success' => false, 'error' => $error];
    }
}

/**
 * Smart upload function (compatible with previous helper)
 * @param string $file_path Local file path
 * @param string $name Custom name for the file
 * @param array $options Additional upload options (e.g., ['overwrite' => true])
 * @return array|false Upload result or false on failure
 */
function smartUpload($file_path, $name = '', $options = []) {
    $result = uploadToCloudinary($file_path, $name, $options);
    
    if ($result && isset($result['success']) && $result['success']) {
        return $result;
    }
    
    $error_msg = isset($result['error']) ? $result['error'] : "Unknown error";
    
    return [
        'success' => false,
        'error' => $error_msg,
        'file_path' => $file_path
    ];
}

/**
 * Delete file from Cloudinary
 * @param string $public_id The Cloudinary public ID
 * @return bool Success status
 */
function deleteFromCloudinary($public_id) {
    if (empty($public_id)) {
        return false;
    }
    
    try {
        $cloudinary = getCloudinaryInstance();
        if (!$cloudinary) {
            return false;
        }

        $result = $cloudinary->uploadApi()->destroy($public_id);
        return isset($result['result']) && $result['result'] === 'ok';
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Extract public ID from Cloudinary URL
 * @param string $url The Cloudinary URL
 * @return string|false Public ID or false on failure
 */
function extractCloudinaryPublicId($url) {
    if (empty($url) || strpos($url, 'cloudinary.com') === false) {
        return false;
    }

    // Cloudinary URL format: https://res.cloudinary.com/cloud_name/image/upload/v1234567890/folder/filename.jpg
    // Extract the public_id part
    if (preg_match('/\/upload\/(?:v\d+\/)?(.+?)(?:\.[^.]+)?$/', $url, $matches)) {
        return $matches[1];
    }
    
    return false;
}

/**
 * Validate if a URL is from Cloudinary
 * @param string $url The URL to validate
 * @return bool True if it's a Cloudinary URL
 */
function isCloudinaryUrl($url) {
    return !empty($url) && strpos($url, 'cloudinary.com') !== false;
}

/**
 * Test Cloudinary connection
 * @return array Test result
 */
function testCloudinaryConnection() {
    try {
        $cloudinary = getCloudinaryInstance();
        if (!$cloudinary) {
            return [
                'success' => false,
                'message' => 'Could not initialize Cloudinary. Check your credentials (CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, CLOUDINARY_API_SECRET).',
                'details' => null
            ];
        }

        // Try to ping Cloudinary (list resources with limit 1)
        $result = $cloudinary->adminApi()->ping();
        
        return [
            'success' => true,
            'message' => 'Cloudinary connection successful',
            'details' => [
                'cloud_name' => CLOUDINARY_CLOUD_NAME
            ]
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Cloudinary connection failed: ' . $e->getMessage(),
            'details' => null
        ];
    }
}
?>

