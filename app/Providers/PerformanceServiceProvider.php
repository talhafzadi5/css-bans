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
        // Optimize memory and garbage collection
        ini_set('memory_limit', '512M');
        ini_set('opcache.enable', '1');
        
        if (function_exists('gc_enable')) {
            gc_enable();
        }

        // Only optimize DB on actual usage to avoid cold start delays
        $this->app->resolving('db', function () {
            static $optimized = false;
            if (!$optimized) {
                try {
                    PerformanceHelper::optimizeConnections();
                } catch (\Exception $e) {
                    // Fail silently
                }
                $optimized = true;
            }
        });

        // Defer heavy preloading to avoid timeout
        $this->app->terminating(function () {
            // Only run cleanup occasionally
            if (rand(1, 100) <= 5) {
                PerformanceHelper::cleanupCache();
            }
        });
    }
} 