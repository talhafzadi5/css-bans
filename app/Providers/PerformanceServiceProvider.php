<?php

namespace App\Providers;

use App\Helpers\PerformanceHelper;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;

class PerformanceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Only optimize in production environment
        if (app()->environment('production')) {
            $this->optimizeForServerless();
        }
    }

    /**
     * Apply serverless optimizations
     */
    private function optimizeForServerless(): void
    {
        // Optimize database connections on first query
        DB::listen(function ($query) {
            static $optimized = false;
            if (!$optimized) {
                PerformanceHelper::optimizeConnections();
                $optimized = true;
            }
        });

        // Preload critical data
        $this->app->booted(function () {
            PerformanceHelper::preloadCriticalData();
        });

        // Optimize memory usage
        ini_set('memory_limit', '512M');
        
        // Optimize garbage collection
        if (function_exists('gc_enable')) {
            gc_enable();
        }
    }
} 