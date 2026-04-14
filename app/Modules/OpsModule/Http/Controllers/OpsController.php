<?php

namespace App\Modules\OpsModule\Http\Controllers;

use App\Infrastructure\Cache\InstrumentedCache;
use App\Infrastructure\Quota\HostingerQuotaTracker;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class OpsController extends Controller
{
    private const TRACKED_CACHE_KEYS = [
        'hostinger:vps:list:all',
        'hostinger:vps:os-templates',
        'hostinger:vps:datacenters',
    ];

    public function __construct(private HostingerQuotaTracker $quota) {}

    public function quotaStats(Request $request): JsonResponse
    {
        if (!$request->user()->hasRole('root')) {
            return ApiResponse::error('Forbidden.', 403);
        }

        $total     = $this->quota->getToday();
        $warnAt    = $this->quota->getWarningThreshold();
        $hardLimit = $this->quota->getHardLimit();

        return ApiResponse::success([
            'date'        => date('Y-m-d'),
            'total'       => $total,
            'by_resource' => $this->quota->getTodayByResource(),  // GAP 8
            'warn_at'     => $warnAt,
            'hard_limit'  => $hardLimit > 0 ? $hardLimit : 'disabled',
            'percent'     => $warnAt > 0 ? round($total / $warnAt * 100, 1) : 0,
            'status'      => match (true) {
                $hardLimit > 0 && $total >= $hardLimit => 'exceeded',
                $total >= $warnAt                      => 'warning',
                default                                => 'ok',
            },
        ]);
    }

    public function cacheStats(Request $request): JsonResponse
    {
        if (!$request->user()->hasRole('root')) {
            return ApiResponse::error('Forbidden.', 403);
        }

        $stats = [];
        foreach (self::TRACKED_CACHE_KEYS as $key) {
            $stats[$key] = InstrumentedCache::getStats($key);
        }

        return ApiResponse::success(['keys' => $stats]);
    }

    public function dbStats(Request $request): JsonResponse
    {
        if (!$request->user()->hasRole('root')) {
            return ApiResponse::error('Forbidden.', 403);
        }

        try {
            $tables = [
                'infra_audit_logs'     => [
                    'rows'      => DB::table('infra_audit_logs')->count(),
                    'retention' => env('AUDIT_LOG_RETENTION_DAYS', 365) . ' days',
                ],
                'auth_audit_logs'      => [
                    'rows'      => DB::table('auth_audit_logs')->count(),
                    'retention' => env('AUTH_LOG_RETENTION_DAYS', 365) . ' days',
                ],
                'drift_reports'        => [
                    'rows'      => DB::table('drift_reports')->count(),
                    'retention' => env('DRIFT_REPORT_RETENTION_DAYS', 90) . ' days',
                ],
                'access_reviews'       => [
                    'rows'      => DB::table('access_reviews')->count(),
                    'retention' => env('ACCESS_REVIEW_RETENTION_DAYS', 730) . ' days',
                ],
                'vps_access_grants'    => ['rows' => DB::table('vps_access_grants')->count()],
                'security_permissions' => ['rows' => DB::table('security_permissions')->count()],
                'jobs'                 => ['rows' => DB::table('jobs')->count()],
                'failed_jobs'          => [
                    'rows'      => DB::table('failed_jobs')->count(),
                    'retention' => env('FAILED_JOB_RETENTION_DAYS', 30) . ' days',
                ],
                'permission_approvals' => ['rows' => DB::table('permission_approvals')->count()],
            ];
        } catch (\Throwable) {
            return ApiResponse::error('Failed to query database statistics.', 500);
        }

        return ApiResponse::success(['tables' => $tables]);
    }
}
