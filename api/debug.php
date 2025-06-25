<?php

// Debug endpoint for Vercel deployment
header('Content-Type: application/json');

$debug = [
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'current_directory' => getcwd(),
    'files_in_root' => [],
    'vendor_exists' => false,
    'autoloader_exists' => false,
    'bootstrap_exists' => false,
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
];

try {
    // Check current directory structure
    $debug['files_in_root'] = array_slice(scandir(__DIR__ . '/..'), 2, 20);
    
    // Check if vendor directory exists
    $debug['vendor_exists'] = is_dir(__DIR__ . '/../vendor');
    
    // Check if autoloader exists
    $debug['autoloader_exists'] = file_exists(__DIR__ . '/../vendor/autoload.php');
    
    // Check if bootstrap exists
    $debug['bootstrap_exists'] = file_exists(__DIR__ . '/../bootstrap/app.php');
    
    // Try to include autoloader
    if ($debug['autoloader_exists']) {
        require_once __DIR__ . '/../vendor/autoload.php';
        $debug['autoloader_loaded'] = true;
        
        // Check if Laravel classes are available
        $debug['laravel_available'] = class_exists('Illuminate\\Foundation\\Application');
    }
    
} catch (Exception $e) {
    $debug['error'] = $e->getMessage();
    $debug['trace'] = $e->getTraceAsString();
}

echo json_encode($debug, JSON_PRETTY_PRINT);
exit; 