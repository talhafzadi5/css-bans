#!/bin/bash

# Laravel Build Script for Vercel Deployment
echo "Starting Laravel optimization for production..."

# Install dependencies
echo "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Clear and cache configuration
echo "Caching Laravel configurations..."
php artisan config:clear
php artisan config:cache

# Cache routes
echo "Caching routes..."
php artisan route:clear
php artisan route:cache

# Cache views
echo "Caching views..."
php artisan view:clear
php artisan view:cache

# Optimize autoloader
echo "Optimizing autoloader..."
composer dump-autoload --optimize

# Clear application cache
echo "Clearing application cache..."
php artisan cache:clear

echo "Build completed successfully!" 