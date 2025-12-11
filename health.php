<?php
/**
 * Health Check Endpoint for GICT Application
 * 
 * This endpoint is used by Kubernetes liveness/readiness probes
 * and load balancers to check application health.
 */

header('Content-Type: application/json');

// Start output buffering
ob_start();

$health = [
    'status' => 'healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
];

$allHealthy = true;

// Check 1: PHP is working
$health['checks']['php'] = [
    'status' => 'ok',
    'version' => PHP_VERSION,
    'message' => 'PHP is running'
];

// Check 2: Database connection
try {
    require_once 'config/database.php';
    $db = getDBConnection();
    
    if ($db) {
        // Test query
        $stmt = $db->query("SELECT 1 as test");
        $result = $stmt->fetch();
        
        if ($result && $result['test'] == 1) {
            $health['checks']['database'] = [
                'status' => 'ok',
                'message' => 'Database connection successful',
                'host' => DB_HOST,
                'database' => DB_NAME
            ];
        } else {
            $health['checks']['database'] = [
                'status' => 'error',
                'message' => 'Database query test failed'
            ];
            $allHealthy = false;
        }
    } else {
        $health['checks']['database'] = [
            'status' => 'error',
            'message' => 'Database connection failed'
        ];
        $allHealthy = false;
    }
} catch (Exception $e) {
    $health['checks']['database'] = [
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ];
    $allHealthy = false;
}

// Check 3: Critical directories are writable
$writableDirs = [
    'uploads' => 'uploads/',
    'generated_marksheets' => 'assets/generated_marksheets/'
];

foreach ($writableDirs as $name => $path) {
    if (is_dir($path)) {
        if (is_writable($path)) {
            $health['checks']['directory_' . $name] = [
                'status' => 'ok',
                'message' => "Directory $name is writable"
            ];
        } else {
            $health['checks']['directory_' . $name] = [
                'status' => 'warning',
                'message' => "Directory $name is not writable"
            ];
            // Don't mark as unhealthy, just warning
        }
    } else {
        $health['checks']['directory_' . $name] = [
            'status' => 'warning',
            'message' => "Directory $name does not exist"
        ];
    }
}

// Check 4: Required PHP extensions
$requiredExtensions = ['pdo', 'pdo_mysql', 'gd', 'mbstring', 'curl'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

if (empty($missingExtensions)) {
    $health['checks']['php_extensions'] = [
        'status' => 'ok',
        'message' => 'All required PHP extensions are loaded'
    ];
} else {
    $health['checks']['php_extensions'] = [
        'status' => 'error',
        'message' => 'Missing PHP extensions: ' . implode(', ', $missingExtensions),
        'missing' => $missingExtensions
    ];
    $allHealthy = false;
}

// Check 5: Session functionality
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['health_check'] = time();
    $health['checks']['sessions'] = [
        'status' => 'ok',
        'message' => 'Session functionality is working'
    ];
    session_destroy();
} catch (Exception $e) {
    $health['checks']['sessions'] = [
        'status' => 'error',
        'message' => 'Session error: ' . $e->getMessage()
    ];
    $allHealthy = false;
}

// Set overall status
if (!$allHealthy) {
    $health['status'] = 'unhealthy';
    http_response_code(503); // Service Unavailable
} else {
    http_response_code(200); // OK
}

// Clear any output before sending JSON
ob_clean();

echo json_encode($health, JSON_PRETTY_PRINT);
exit();
