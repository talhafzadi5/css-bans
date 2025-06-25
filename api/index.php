<?php
// Fast bootstrap for Vercel serverless
$startTime = microtime(true);

// Optimize PHP settings for serverless
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 50);
ini_set('max_input_time', 30);
ini_set('default_socket_timeout', 10);

// Enable output buffering for better performance
if (!ob_get_level()) {
    ob_start();
}

// Quick dependency check
$vendorPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($vendorPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Application not built. Run: composer install']);
    exit(1);
}

// Set environment for faster loading
$_ENV['APP_ENV'] = $_ENV['APP_ENV'] ?? 'production';
$_ENV['APP_DEBUG'] = 'false';

// Disable unnecessary Laravel features for faster boot
$_ENV['LOG_LEVEL'] = $_ENV['LOG_LEVEL'] ?? 'error';
$_ENV['SESSION_DRIVER'] = $_ENV['SESSION_DRIVER'] ?? 'cookie';
$_ENV['CACHE_DRIVER'] = $_ENV['CACHE_DRIVER'] ?? 'array';

try {
    // Load the application with timeout protection
    $timeout = 45; // Leave 15 seconds buffer
    set_time_limit($timeout);
    
    // Forward to Laravel
    require $vendorPath;
    require __DIR__ . '/../public/index.php';
    
} catch (Throwable $e) {
    // Log and return structured error
    error_log('Laravel Boot Error: ' . $e->getMessage());
    
    http_response_code(500);
    
    if ($_ENV['APP_DEBUG'] === 'true') {
        echo json_encode([
            'error' => 'Application Error',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'boot_time' => round((microtime(true) - $startTime) * 1000, 2) . 'ms'
        ], JSON_PRETTY_PRINT);
    } else {
        echo json_encode(['error' => 'Internal Server Error']);
    }
    exit(1);
}
