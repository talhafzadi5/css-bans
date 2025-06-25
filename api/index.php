<?php

// Optimize for serverless environment
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 30);

// Ensure autoloader is available
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    http_response_code(500);
    echo 'Vendor directory not found. Please run: composer install';
    exit(1);
}

// Forward Vercel requests to public index.
require __DIR__ . '/../public/index.php';
