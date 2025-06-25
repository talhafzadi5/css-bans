<?php

// Minimal Laravel bootstrap test
echo "=== SIMPLE PHP TEST ===\n";

try {
    // Basic dependency check
    $vendorPath = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($vendorPath)) {
        throw new Exception("Vendor autoload not found at: $vendorPath");
    }
    
    echo "✓ Vendor autoload found\n";
    
    // Load autoloader
    require_once $vendorPath;
    echo "✓ Autoloader loaded\n";
    
    // Check Laravel availability
    if (!class_exists('Illuminate\\Foundation\\Application')) {
        throw new Exception("Laravel Application class not found");
    }
    
    echo "✓ Laravel classes available\n";
    
    // Try to bootstrap Laravel
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    echo "✓ Laravel application created\n";
    
    // Simple response
    echo "✓ Laravel is working!\n";
    echo "PHP Version: " . phpversion() . "\n";
    echo "Laravel Version: " . $app->version() . "\n";
    
} catch (Throwable $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    
    if (method_exists($e, 'getPrevious') && $e->getPrevious()) {
        echo "Previous: " . $e->getPrevious()->getMessage() . "\n";
    }
}

echo "=== END TEST ===\n"; 