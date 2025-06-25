<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class FastResponse
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip heavy operations for static/asset requests
        if ($this->isStaticRequest($request)) {
            return $next($request);
        }

        // Optimize database connections
        $this->optimizeDatabaseConnection();

        // Start response early for better perceived performance
        if (!headers_sent()) {
            header('X-Powered-By: Laravel-Vercel');
        }

        $response = $next($request);

        // Add performance headers
        if ($response instanceof \Illuminate\Http\Response) {
            $response->header('Cache-Control', 'no-cache, private');
            $response->header('X-Frame-Options', 'SAMEORIGIN');
        }

        return $response;
    }

    /**
     * Check if this is a static/asset request
     */
    private function isStaticRequest(Request $request): bool
    {
        $path = $request->path();
        
        return str_contains($path, '.css') ||
               str_contains($path, '.js') ||
               str_contains($path, '.png') ||
               str_contains($path, '.jpg') ||
               str_contains($path, '.jpeg') ||
               str_contains($path, '.gif') ||
               str_contains($path, '.svg') ||
               str_contains($path, '.ico') ||
               str_contains($path, '.woff') ||
               str_contains($path, '.ttf');
    }

    /**
     * Optimize database connection for serverless
     */
    private function optimizeDatabaseConnection(): void
    {
        try {
            // Only optimize if we have database configuration
            if (config('database.default') && !app()->runningInConsole()) {
                DB::statement('SET SESSION wait_timeout = 28800');
                DB::statement('SET SESSION interactive_timeout = 28800');
                
                // Disable query logging in production for performance
                if (app()->environment('production')) {
                    DB::disableQueryLog();
                }
            }
        } catch (\Exception $e) {
            // Fail silently for database optimization errors
            // but log them for debugging
            if (app()->environment('local')) {
                \Log::debug('DB optimization failed: ' . $e->getMessage());
            }
        }
    }
} 