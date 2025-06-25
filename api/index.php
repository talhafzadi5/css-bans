<?php

// Optimize for serverless
ini_set('memory_limit', '1024M');

// Cache bootstrap files if they don't exist
if (!file_exists('/tmp/config.php')) {
    require_once __DIR__ . '/../bootstrap/app.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    
    try {
        // Generate optimized files
        \Illuminate\Support\Facades\Artisan::call('config:cache');
        \Illuminate\Support\Facades\Artisan::call('route:cache');
        \Illuminate\Support\Facades\Artisan::call('view:cache');
    } catch (Exception $e) {
        // Continue if caching fails
    }
}

// Forward Vercel requests to public index.
require __DIR__ . '/../public/index.php';
