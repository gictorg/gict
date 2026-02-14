<?php
require_once 'includes/session_manager.php';

/**
 * Secure Marksheet Template Server
 * Only serves the template if the user is viewing a valid marksheet
 */

// Basic security check: Session variable set by marksheet.php
$is_viewing = isset($_SESSION['marks_viewing']) && $_SESSION['marks_viewing'] === true;

// Token check if provided in URL
$token = $_GET['t'] ?? '';
$is_token_valid = false;
if ($token && isset($_SESSION['marks_image_tokens'][$token])) {
    if ($_SESSION['marks_image_tokens'][$token] > time()) {
        $is_token_valid = true;
    }
}

if ($is_viewing || $is_token_valid) {
    define('SECURE_ACCESS', true);
    // Path to the template image (JPG version of marksheet.pdf)
    $template_file = 'assets/templates/computer_marksheet.jpg';

    if (file_exists($template_file)) {
        header('Content-Type: image/jpeg');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        readfile($template_file);
        exit;
    } else {
        // Fallback or explain to user
        header('HTTP/1.1 404 Not Found');
        echo "Template image missing. Please convert marksheet.pdf to assets/templates/marksheet_template.jpg";
        exit;
    }
}

header('HTTP/1.1 403 Forbidden');
echo "Access denied.";
exit;
