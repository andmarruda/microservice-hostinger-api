<?php

namespace App\Modules\ObservabilityModule\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'data' => [
                'status'    => 'ok',
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function ready(): JsonResponse
    {
        $checks  = [];
        $healthy = true;

        // Database check
        try {
            DB::connection()->getPdo();
            $checks['database'] = 'ok';
        } catch (\Throwable) {
            $checks['database'] = 'error';
            $healthy = false;
        }

        // Cache check
        try {
            $key = 'health:probe:' . time();
            Cache::put($key, 1, 5);
            Cache::forget($key);
            $checks['cache'] = 'ok';
        } catch (\Throwable) {
            $checks['cache'] = 'error';
            $healthy = false;
        }

        return response()->json(
            ['data' => ['status' => $healthy ? 'ok' : 'degraded', 'checks' => $checks]],
            $healthy ? 200 : 503,
        );
    }

    public function queue(Request $request): JsonResponse
    {
        // ADR-013: root auth required
        if (!$request->user()->hasRole('root')) {
            return ApiResponse::error('Forbidden.', 403);
        }

        try {
            $pending     = DB::table('jobs')->count();
            $failed      = DB::table('failed_jobs')->count();
            $lastFailed  = DB::table('failed_jobs')->max('failed_at');

            return response()->json([
                'data' => [
                    'status'          => 'ok',
                    'pending'         => $pending,
                    'failed'          => $failed,
                    'last_failed_at'  => $lastFailed,
                ],
            ]);
        } catch (\Throwable) {
            return response()->json([
                'data' => ['status' => 'error', 'message' => 'Unable to query queue tables.'],
            ], 503);
        }
    }
}
