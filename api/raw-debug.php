<?php
// Ultra-basic debug script for Vercel deployment issues
// This doesn't use any Laravel dependencies or external libraries

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== VERCEL PHP DEBUG ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Server Time: " . date('Y-m-d H:i:s') . "\n";
echo "Working Directory: " . getcwd() . "\n";
echo "Script Location: " . __FILE__ . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . "\n";

echo "\n=== DIRECTORY STRUCTURE ===\n";
$baseDir = dirname(__DIR__);
echo "Base Directory: $baseDir\n";

// Check if key files exist
$files = [
    'composer.json',
    'composer.lock', 
    'vendor/autoload.php',
    'bootstrap/app.php',
    'public/index.php',
    'artisan'
];

foreach ($files as $file) {
    $fullPath = $baseDir . '/' . $file;
    $exists = file_exists($fullPath) ? '✓' : '✗';
    echo "$exists $file\n";
}

echo "\n=== VENDOR DIRECTORY ===\n";
$vendorDir = $baseDir . '/vendor';
if (is_dir($vendorDir)) {
    echo "✓ Vendor directory exists\n";
    $vendorFiles = array_slice(scandir($vendorDir), 2, 10);
    echo "Sample contents: " . implode(', ', $vendorFiles) . "\n";
} else {
    echo "✗ Vendor directory missing\n";
}

echo "\n=== ENVIRONMENT VARIABLES ===\n";
$envVars = [
    'APP_ENV', 'APP_DEBUG', 'APP_KEY', 'DB_HOST', 'DB_DATABASE',
    'CACHE_DRIVER', 'SESSION_DRIVER'
];

foreach ($envVars as $var) {
    $value = $_ENV[$var] ?? getenv($var) ?: 'NOT SET';
    echo "$var: $value\n";
}

echo "\n=== PHP EXTENSIONS ===\n";
$extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext) ? '✓' : '✗';
    echo "$loaded $ext\n";
}

echo "\n=== AUTOLOADER TEST ===\n";
$autoloadPath = $baseDir . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    echo "✓ Autoloader file exists\n";
    try {
        require_once $autoloadPath;
        echo "✓ Autoloader loaded successfully\n";
        
        // Test if Laravel classes are available
        if (class_exists('Illuminate\\Foundation\\Application')) {
            echo "✓ Laravel classes available\n";
        } else {
            echo "✗ Laravel classes not found\n";
        }
    } catch (Exception $e) {
        echo "✗ Autoloader failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ Autoloader file missing\n";
}

echo "\n=== END DEBUG ===\n"; 