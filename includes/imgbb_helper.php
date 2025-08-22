<?php
/**
 * ImgBB Helper for GICT Institute
 * Uses your API key: 3acdbb8d9ce98d6f3ff4e61a5902c75a
 * Images NEVER expire (0 = permanent)
 * Includes fallback to local storage if ImgBB fails
 */

// ImgBB Configuration
define('IMGBB_API_KEY', '3acdbb8d9ce98d6f3ff4e61a5902c75a');
define('IMGBB_EXPIRATION', 0); // 0 = Never expire

/**
 * Upload file to ImgBB with your API key
 * @param string $file_path Local file path
 * @param string $name Custom name for the file
 * @return array|false Upload result or false on failure
 */
function uploadToImgBB($file_path, $name = '') {
    try {
        // Check if file exists
        if (!file_exists($file_path)) {
            return false;
        }
        
        // Check file size (ImgBB limit is 32MB, we'll allow up to 200KB for better compatibility)
        $file_size = filesize($file_path);
        if ($file_size > 200 * 1024) {
            return false;
        }
        
        // Check if file type is supported (restricted to JPG, JPEG, PNG only)
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $supported_types = ['jpg', 'jpeg', 'png'];
        
        if (!in_array($extension, $supported_types)) {
            return false;
        }
        
        // Read file content and encode to base64
        $file_content = file_get_contents($file_path);
        if ($file_content === false) {
            return false;
        }
        
        $base64_image = base64_encode($file_content);
        
        // Prepare upload data with your API key and NO expiration
        $post_data = [
            'image' => $base64_image,
            'name' => $name ?: basename($file_path)
        ];
        
        // Only add expiration if it's not 0 (permanent)
        if (IMGBB_EXPIRATION > 0) {
            $post_data['expiration'] = IMGBB_EXPIRATION;
        }
        
        // Create cURL request to ImgBB with your API key
        $ch = curl_init();
        $url = 'https://api.imgbb.com/1/upload?key=' . IMGBB_API_KEY;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_USERAGENT, 'GICT-Institute/1.0');
        
        // Execute request
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            curl_close($ch);
            return false;
        }
        
        curl_close($ch);
        
        // Check response
        if ($http_code !== 200) {
            return false;
        }
        
        // Parse response
        $result = json_decode($response, true);
        
        if (!$result || !isset($result['success']) || !$result['success']) {
            return false;
        }
        
        $data = $result['data'];
        
        // Return success result
        return [
            'success' => true,
            'url' => $data['url'],
            'display_url' => $data['display_url'],
            'delete_url' => $data['delete_url'] ?? '',
            'id' => $data['id'],
            'title' => $data['title'],
            'size' => $data['size'],
            'width' => $data['width'] ?? null,
            'height' => $data['height'] ?? null,
            'format' => $data['extension'] ?? pathinfo($file_path, PATHINFO_EXTENSION),
            'expiration' => $data['expiration'] ?? 'Never',
            'time' => $data['time'] ?? time()
        ];
        
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Fallback function to store images locally if ImgBB fails
 * @param string $file_path Local file path
 * @param string $name Custom name for the file
 * @return array|false Upload result or false on failure
 */
function uploadToLocalStorage($file_path, $name = '') {
    try {
        // Check if file exists
        if (!file_exists($file_path)) {
            return false;
        }
        
        // Create uploads directory if it doesn't exist
        $uploads_dir = 'uploads/';
        if (!is_dir($uploads_dir)) {
            mkdir($uploads_dir, 0755, true);
        }
        
        $images_dir = $uploads_dir . 'images/';
        if (!is_dir($images_dir)) {
            mkdir($images_dir, 0755, true);
        }
        
        // Generate unique filename
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $filename = $name ?: 'image_' . time() . '_' . uniqid();
        $filename = $filename . '.' . $extension;
        $target_path = $images_dir . $filename;
        
        // Copy file to uploads directory
        if (copy($file_path, $target_path)) {
            return [
                'success' => true,
                'url' => $target_path,
                'display_url' => $target_path,
                'delete_url' => '',
                'id' => $filename,
                'title' => $name ?: 'Uploaded Image',
                'size' => filesize($target_path),
                'width' => null,
                'height' => null,
                'format' => $extension,
                'expiration' => 'Never',
                'time' => time(),
                'storage_type' => 'local'
            ];
        } else {
            return false;
        }
        
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Smart upload function that tries ImgBB first, falls back to local storage
 * @param string $file_path Local file path
 * @param string $name Custom name for the file
 * @return array|false Upload result or false on failure
 */
function smartUpload($file_path, $name = '') {
    // Try ImgBB first
    $result = uploadToImgBB($file_path, $name);
    
    if ($result && $result['success']) {
        $result['storage_type'] = 'imgbb';
        return $result;
    }
    
    // If ImgBB fails, fall back to local storage
    $result = uploadToLocalStorage($file_path, $name);
    
    if ($result && $result['success']) {
        $result['storage_type'] = 'local';
        return $result;
    }
    
    return false;
}

/**
 * Main upload function - always use smartUpload for reliability
 * @param string $file_path Local file path
 * @param string $name Custom name for the file
 * @return array|false Upload result or false on failure
 */
function uploadFile($file_path, $name = '') {
    return smartUpload($file_path, $name);
}

/**
 * Test ImgBB connection with your API key
 * @return array Test results
 */
function testImgBBConnection() {
    $results = [];
    
    // Test if cURL is available
    $results['curl_available'] = function_exists('curl_init');
    
    // Test API key configuration
    $results['api_key_configured'] = defined('IMGBB_API_KEY') && !empty(IMGBB_API_KEY);
    $results['api_key'] = IMGBB_API_KEY;
    $results['expiration'] = IMGBB_EXPIRATION === 0 ? 'Never (Permanent)' : IMGBB_EXPIRATION . ' seconds';
    
    // Test if we can make a simple request to ImgBB
    if ($results['curl_available'] && $results['api_key_configured']) {
        $ch = curl_init();
        $url = 'https://api.imgbb.com/1/upload?key=' . IMGBB_API_KEY;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'GICT-Institute/1.0');
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $results['api_connection'] = $http_code === 200 ? 'Connected' : "Failed (HTTP $http_code)";
        $results['api_accessible'] = $http_code === 200;
    } else {
        $results['api_connection'] = 'Not tested (cURL not available or API key not configured)';
        $results['api_accessible'] = false;
    }
    
    // Test file system access
    $results['file_system_ok'] = is_writable('uploads/') || is_writable(sys_get_temp_dir());
    
    // Overall status - now includes local storage fallback
    $results['ready_for_upload'] = $results['curl_available'] && $results['file_system_ok'];
    
    return $results;
}

/**
 * Get optimized image URL with size parameters
 * @param string $url Original ImgBB URL
 * @param int $width Desired width
 * @param int $height Desired height
 * @return string Optimized image URL
 */
function getOptimizedImgBBUrl($url, $width = null, $height = null) {
    if (!$width && !$height) {
        return $url;
    }
    
    // ImgBB supports URL parameters for resizing
    $params = [];
    if ($width) $params[] = "w=$width";
    if ($height) $params[] = "h=$height";
    
    if (!empty($params)) {
        return $url . '?' . implode('&', $params);
    }
    
    return $url;
}

/**
 * Check if file type is supported by ImgBB
 * @param string $file_path File path to check
 * @return bool True if supported
 */
function isImgBBSupported($file_path) {
    $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    $supported_types = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'webp', 'svg'];
    
    return in_array($extension, $supported_types);
}

/**
 * Get file size in human readable format
 * @param int $bytes File size in bytes
 * @return string Formatted file size
 */
function formatFileSize($bytes) {
    if ($bytes >= 1024 * 1024) {
        return round($bytes / (1024 * 1024), 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Get thumbnail URL for profile images
 * @param string $imgbb_url Original ImgBB URL
 * @param int $size Size for thumbnail (default 150x150)
 * @return string Thumbnail URL
 */
function getProfileThumbnail($imgbb_url, $size = 150) {
    if (empty($imgbb_url)) {
        return 'assets/images/default-profile.png'; // Default image
    }
    
    // Add thumbnail parameters to ImgBB URL
    return $imgbb_url . "?w={$size}&h={$size}&fit=crop";
}

/**
 * Get medium size URL for faculty images
 * @param string $imgbb_url Original ImgBB URL
 * @param int $width Width (default 300)
 * @param int $height Height (default 200)
 * @return string Medium size URL
 */
function getFacultyImage($imgbb_url, $width = 300, $height = 200) {
    if (empty($imgbb_url)) {
        return 'assets/images/default-faculty.png'; // Default image
    }
    
    // Add size parameters to ImgBB URL
    return $imgbb_url . "?w={$width}&h={$height}&fit=crop";
}
?>
