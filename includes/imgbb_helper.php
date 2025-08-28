<?php
/**
 * ImgBB Helper for GICT Institute
 * Uses your API key: 3acdbb8d9ce98d6f3ff4e61a5902c75a
 * Images NEVER expire (0 = permanent)
 * ONLY uploads to ImgBB - no local storage fallback
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
            error_log("ImgBB Upload Error: File does not exist: " . $file_path);
            return false;
        }
        
        // Check file size (ImgBB limit is 32MB, we'll allow up to 200KB for better compatibility)
        $file_size = filesize($file_path);
        if ($file_size > 200 * 1024) {
            error_log("ImgBB Upload Error: File too large: " . $file_size . " bytes");
            return false;
        }
        
        // Check if file type is supported (restricted to JPG, JPEG, PNG only)
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $supported_types = ['jpg', 'jpeg', 'png'];
        
        if (!in_array($extension, $supported_types)) {
            error_log("ImgBB Upload Error: Unsupported file type: " . $extension);
            return false;
        }
        
        // Read file content and encode to base64
        $file_content = file_get_contents($file_path);
        if ($file_content === false) {
            error_log("ImgBB Upload Error: Cannot read file content");
            return false;
        }
        
        $base64_image = base64_encode($file_content);
        
        // Format the image name as username.format
        $formatted_name = '';
        if ($name) {
            // Extract username and file type from the name
            $name_parts = explode('_', $name);
            if (count($name_parts) >= 2) {
                $username = $name_parts[0];
                $file_type = end($name_parts);
                $formatted_name = $username . '.' . $extension;
            } else {
                $formatted_name = $name . '.' . $extension;
            }
        } else {
            $formatted_name = 'image_' . time() . '.' . $extension;
        }
        
        // Prepare upload data with your API key and NO expiration
        $post_data = [
            'image' => $base64_image,
            'name' => $formatted_name
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
            error_log("ImgBB Upload Error: cURL error: " . curl_error($ch));
            curl_close($ch);
            return false;
        }
        
        curl_close($ch);
        
        // Check response
        if ($http_code !== 200) {
            error_log("ImgBB Upload Error: HTTP " . $http_code . " - Response: " . $response);
            return false;
        }
        
        // Parse response
        $result = json_decode($response, true);
        
        if (!$result || !isset($result['success']) || !$result['success']) {
            error_log("ImgBB Upload Error: API response indicates failure: " . $response);
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
            'time' => $data['time'] ?? time(),
            'storage_type' => 'imgbb'
        ];
        
    } catch (Exception $e) {
        error_log("ImgBB Upload Error: Exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Smart upload function that ONLY uses ImgBB - no local storage fallback
 * @param string $file_path Local file path
 * @param string $name Custom name for the file (format: username_type)
 * @return array|false Upload result or false on failure
 */
function smartUpload($file_path, $name = '') {
    // Only try ImgBB - no fallback to local storage
    $result = uploadToImgBB($file_path, $name);
    
    if ($result && $result['success']) {
        return $result;
    }
    
    // Log the failure for debugging
    error_log("Smart Upload Failed: Could not upload to ImgBB for file: " . $file_path);
    return false;
}

/**
 * Main upload function - always use smartUpload for reliability
 * @param string $file_path Local file path
 * @param string $name Custom name for the file
 * @return array|false Upload result or false on failure
 */
function uploadImage($file_path, $name = '') {
    return smartUpload($file_path, $name);
}

/**
 * Delete image from ImgBB (if needed)
 * @param string $delete_url The delete URL from ImgBB
 * @return bool Success status
 */
function deleteFromImgBB($delete_url) {
    if (empty($delete_url)) {
        return false;
    }
    
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $delete_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $http_code === 200;
    } catch (Exception $e) {
        error_log("ImgBB Delete Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get image info from ImgBB URL
 * @param string $imgbb_url The ImgBB URL
 * @return array|false Image info or false on failure
 */
function getImgBBInfo($imgbb_url) {
    if (empty($imgbb_url) || !strpos($imgbb_url, 'imgbb.com')) {
        return false;
    }
    
    // Extract image ID from URL
    $parts = explode('/', $imgbb_url);
    $image_id = end($parts);
    
    if (empty($image_id)) {
        return false;
    }
    
    return [
        'id' => $image_id,
        'url' => $imgbb_url,
        'storage_type' => 'imgbb'
    ];
}

/**
 * Validate if a URL is from ImgBB
 * @param string $url The URL to validate
 * @return bool True if it's an ImgBB URL
 */
function isImgBBUrl($url) {
    return !empty($url) && strpos($url, 'imgbb.com') !== false;
}

/**
 * Generate a proper name for ImgBB uploads
 * @param string $username The username/user ID
 * @param string $type The type of file (profile, marksheet, aadhaar, etc.)
 * @param string $extension The file extension
 * @return string Formatted name for ImgBB
 */
function generateImgBBName($username, $type, $extension) {
    return $username . '_' . $type . '.' . $extension;
}

/**
 * Test ImgBB API connection
 * @return array Test result
 */
function testImgBBConnection() {
    $test_file = tempnam(sys_get_temp_dir(), 'imgbb_test');
    $test_content = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfC+SJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
    file_put_contents($test_file, $test_content);
    
    $result = uploadToImgBB($test_file, 'test_image');
    unlink($test_file);
    
    return [
        'success' => $result !== false,
        'message' => $result ? 'ImgBB connection successful' : 'ImgBB connection failed',
        'details' => $result
    ];
}
?>
