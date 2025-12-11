<?php
/**
 * Simple Health Check Endpoint
 * 
 * Lightweight endpoint for Kubernetes liveness/readiness probes
 * Returns HTTP 200 if healthy, 503 if unhealthy
 */

// Suppress all output
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

$healthy = true;

// Quick database check
try {
    require_once 'config/database.php';
    $db = getDBConnection();
    if (!$db) {
        $healthy = false;
    } else {
        // Simple query test
        $db->query("SELECT 1")->fetch();
    }
} catch (Exception $e) {
    $healthy = false;
}

// Clear output
ob_clean();

if ($healthy) {
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
} else {
    http_response_code(503);
    echo json_encode(['status' => 'error']);
}

exit();
