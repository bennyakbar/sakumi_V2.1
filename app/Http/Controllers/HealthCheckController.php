<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthCheckController extends Controller
{
    public function live(): JsonResponse
    {
        try {
            DB::connection()->getPdo();
            return response()->json(['status' => 'ok']);
        } catch (\Throwable) {
            return response()->json(['status' => 'degraded'], 503);
        }
    }

    public function check(): JsonResponse
    {
        $checks = [];
        $overallStatus = 'ok';

        // Database
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $responseTime = round((microtime(true) - $start) * 1000, 2);
            $checks['database'] = ['status' => 'ok', 'response_time_ms' => $responseTime];
        } catch (\Throwable $e) {
            $checks['database'] = ['status' => 'error', 'message' => $e->getMessage()];
            $overallStatus = 'degraded';
        }

        // Storage
        $totalSpace = disk_total_space(storage_path());
        $freeSpace = disk_free_space(storage_path());
        $usagePercent = $totalSpace > 0 ? round((1 - $freeSpace / $totalSpace) * 100, 1) : 0;
        $storageStatus = $usagePercent >= 90 ? 'warning' : 'ok';
        $checks['storage'] = ['status' => $storageStatus, 'usage_percent' => $usagePercent];
        if ($storageStatus === 'warning' && $overallStatus === 'ok') {
            $overallStatus = 'warning';
        }

        // Queue (database driver)
        try {
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();

            $queueStatus = 'ok';
            if ($failedJobs > 50 || $pendingJobs > 100) {
                $queueStatus = 'error';
                $overallStatus = 'degraded';
            } elseif ($failedJobs > 10) {
                $queueStatus = 'warning';
                if ($overallStatus === 'ok') {
                    $overallStatus = 'warning';
                }
            }

            $checks['queue'] = [
                'status' => $queueStatus,
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
            ];
        } catch (\Throwable $e) {
            $checks['queue'] = ['status' => 'error', 'message' => $e->getMessage()];
            $overallStatus = 'degraded';
        }

        // Cache
        try {
            Cache::put('health_check', true, 10);
            $cacheResult = Cache::get('health_check');
            $checks['cache'] = ['status' => $cacheResult ? 'ok' : 'error'];
            if (!$cacheResult) {
                $overallStatus = 'degraded';
            }
        } catch (\Throwable $e) {
            $checks['cache'] = ['status' => 'error', 'message' => $e->getMessage()];
            $overallStatus = 'degraded';
        }

        $statusCode = $overallStatus === 'ok' ? 200 : 503;

        return response()->json([
            'status' => $overallStatus,
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $statusCode);
    }
}
