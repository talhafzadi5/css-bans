<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceHelper
{
    /**
     * Cache database query results with optimized expiration
     */
    public static function cacheQuery(string $key, callable $callback, int $minutes = 60)
    {
        try {
            return Cache::remember($key, $minutes * 60, $callback);
        } catch (\Exception $e) {
            Log::warning('Cache operation failed: ' . $e->getMessage());
            return $callback();
        }
    }

    /**
     * Optimize database connections for serverless
     */
    public static function optimizeConnections()
    {
        try {
            // Set connection timeouts
            DB::statement('SET SESSION wait_timeout = 28800');
            DB::statement('SET SESSION interactive_timeout = 28800');
        } catch (\Exception $e) {
            Log::warning('Failed to optimize DB connections: ' . $e->getMessage());
        }
    }

    /**
     * Preload critical data for faster access
     */
    public static function preloadCriticalData()
    {
        // Implement any critical data preloading here
        // For example: user permissions, common settings, etc.
    }

    /**
     * Clean up expired cache entries
     */
    public static function cleanupCache()
    {
        try {
            // Only run cleanup occasionally to avoid performance impact
            if (rand(1, 100) <= 5) { // 5% chance
                Cache::flush();
            }
        } catch (\Exception $e) {
            Log::warning('Cache cleanup failed: ' . $e->getMessage());
        }
    }

    /**
     * Get cached Steam profile with optimized caching
     */
    public static function getCachedSteamProfile($steamId)
    {
        $cacheKey = 'steam_profile_' . $steamId;
        
        return self::cacheQuery($cacheKey, function() use ($steamId) {
            return CommonHelper::steamProfile((object)['player_steamid' => $steamId]);
        }, 1440); // Cache for 24 hours
    }
} 