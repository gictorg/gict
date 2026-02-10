<?php
require_once 'includes/session_manager.php';

/**
 * Secure Certificate Template Server
 * Only serves the template if the user is viewing a valid certificate
 */

// Basic security check: Referer check (useful but not foolproof)
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$is_allowed_referer = (strpos($referer, 'certificate.php') !== false);

// Better check: Session variable set by certificate.php
$is_viewing_cert = isset($_SESSION['viewing_certificate']) && $_SESSION['viewing_certificate'] === true;

// Even better: Token check if provided in URL
$token = $_GET['t'] ?? '';
$is_token_valid = false;
if ($token && isset($_SESSION['cert_image_tokens'][$token])) {
    if ($_SESSION['cert_image_tokens'][$token] > time()) {
        $is_token_valid = true;
    }
}

if ($is_allowed_referer || $is_viewing_cert || $is_token_valid) {
    define('SECURE_ACCESS', true);
    require_once 'includes/cert_template_data.php';

    if (isset($template_data)) {
        header('Content-Type: image/jpeg');
        // Prevent caching for added security
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo base64_decode($template_data);
        exit;
    }
}

// If no access, return 403 or a blank image
header('HTTP/1.1 403 Forbidden');
echo "Access denied.";
exit;
