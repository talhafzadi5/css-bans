<?php

// Lightweight warmup endpoint for Vercel functions
$startTime = microtime(true);

// Set minimal PHP configuration
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 10);

// Quick health check
$health = [
    'status' => 'ok',
    'timestamp' => time(),
    'php_version' => PHP_VERSION,
    'memory_usage' => memory_get_usage(true),
    'uptime_ms' => round((microtime(true) - $startTime) * 1000, 2)
];

// Optional: Test database connection if environment variables are set
if (!empty($_ENV['DB_HOST']) && !empty($_ENV['DB_DATABASE'])) {
    try {
        $pdo = new PDO(
            "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
            $_ENV['DB_USERNAME'] ?? '',
            $_ENV['DB_PASSWORD'] ?? '',
            [
                PDO::ATTR_TIMEOUT => 2,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]
        );
        
        $stmt = $pdo->query('SELECT 1');
        $health['database'] = 'connected';
        $pdo = null; // Close connection
        
    } catch (Exception $e) {
        $health['database'] = 'error: ' . $e->getMessage();
    }
}

// Return JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Access-Control-Allow-Origin: *');

echo json_encode($health, JSON_PRETTY_PRINT);

// Force output
if (ob_get_level()) {
    ob_end_flush();
}

exit; 