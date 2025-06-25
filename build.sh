#!/bin/bash

# Laravel Build Script for Vercel Deployment
echo "🚀 Starting Laravel optimization for Vercel..."

# Ensure we're in the right directory
cd "$(dirname "$0")"

# Check if composer is available
if ! command -v composer &> /dev/null; then
    echo "❌ Composer not found. Installing dependencies with php composer.phar..."
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --quiet
    php -r "unlink('composer-setup.php');"
    alias composer='php composer.phar'
fi

# Install dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts

# Create necessary directories
echo "📁 Creating necessary directories..."
mkdir -p bootstrap/cache
mkdir -p storage/app
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs

# Set proper permissions (if not on Windows)
if [[ "$OSTYPE" != "msys" && "$OSTYPE" != "win32" ]]; then
    chmod -R 755 bootstrap/cache
    chmod -R 755 storage
fi

# Only run artisan commands if we have a working Laravel installation
if [ -f "artisan" ] && composer show laravel/framework > /dev/null 2>&1; then
    echo "⚡ Optimizing Laravel for production..."
    
    # Set environment variables for caching
    export APP_ENV=production
    export APP_DEBUG=false
    
    # Clear all caches first (with timeout protection)
    timeout 30s php artisan config:clear --quiet || true
    timeout 30s php artisan route:clear --quiet || true
    timeout 30s php artisan view:clear --quiet || true
    timeout 30s php artisan cache:clear --quiet || true
    
    # Generate optimized files for production (with timeout protection)
    echo "🔧 Generating production cache files..."
    timeout 45s php artisan config:cache --quiet || echo "⚠️  Config cache failed"
    timeout 45s php artisan route:cache --quiet || echo "⚠️  Route cache failed"  
    timeout 45s php artisan view:cache --quiet || echo "⚠️  View cache failed"
    
    # Create optimized autoloader files
    echo "📦 Creating optimized autoloader..."
    timeout 30s php artisan optimize --quiet || echo "⚠️  Optimization failed"
else
    echo "⚠️  Skipping artisan commands - Laravel not properly installed"
fi

# Optimize autoloader
echo "🎯 Optimizing autoloader..."
composer dump-autoload --optimize --classmap-authoritative --quiet

echo "✅ Build completed successfully!"

# Show what was generated
if [ -d "bootstrap/cache" ]; then
    echo "📊 Cache files generated:"
    ls -la bootstrap/cache/ 2>/dev/null || echo "No cache files found"
fi

echo "🎉 Ready for Vercel deployment!" 