<?php

namespace App\Modules\FrontendModule\Http\Controllers;

use App\Infrastructure\Cache\InstrumentedCache;
use App\Infrastructure\Quota\HostingerQuotaTracker;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OpsPageController extends Controller
{
    private const TRACKED_CACHE_KEYS = [
        'hostinger:vps:list:all',
        'hostinger:vps:os-templates',
        'hostinger:vps:datacenters',
    ];

    public function __construct(private HostingerQuotaTracker $quota) {}

    public function health(Request $request): Response
    {
        $checks = [];

        try {
            DB::connection()->getPdo();
            $checks['database'] = 'ok';
        } catch (\Throwable) {
            $checks['database'] = 'error';
        }

        try {
            $key = 'health:probe:' . time();
            Cache::put($key, 1, 5);
            Cache::forget($key);
            $checks['cache'] = 'ok';
        } catch (\Throwable) {
            $checks['cache'] = 'error';
        }

        $queuePending = DB::table('jobs')->count();
        $queueFailed  = DB::table('failed_jobs')->count();
        $lastFailed   = DB::table('failed_jobs')->max('failed_at');

        $checks['queue'] = $queueFailed > 0 ? 'degraded' : 'ok';

        return Inertia::render('Ops/Health', [
            'checks'       => $checks,
            'queuePending' => $queuePending,
            'queueFailed'  => $queueFailed,
            'lastFailedAt' => $lastFailed,
            'healthy'      => !in_array('error', $checks, true),
        ]);
    }

    public function quota(Request $request): Response
    {
        $total      = $this->quota->getToday();
        $warnAt     = $this->quota->getWarningThreshold();
        $hardLimit  = $this->quota->getHardLimit();
        $byResource = $this->quota->getTodayByResource();

        return Inertia::render('Ops/Quota', [
            'total'      => $total,
            'warnAt'     => $warnAt,
            'hardLimit'  => $hardLimit,
            'byResource' => $byResource,
            'percent'    => $warnAt > 0 ? round($total / $warnAt * 100, 1) : 0,
            'status'     => match (true) {
                $hardLimit > 0 && $total >= $hardLimit => 'exceeded',
                $total >= $warnAt                      => 'warning',
                default                                => 'ok',
            },
        ]);
    }

    public function cache(Request $request): Response
    {
        $stats = [];
        foreach (self::TRACKED_CACHE_KEYS as $key) {
            $stats[$key] = InstrumentedCache::getStats($key);
        }

        return Inertia::render('Ops/Cache', [
            'keys' => $stats,
        ]);
    }

    public function database(Request $request): Response
    {
        $tables = [
            'infra_audit_logs'     => ['rows' => DB::table('infra_audit_logs')->count(),  'retention' => env('AUDIT_LOG_RETENTION_DAYS', 365) . ' days'],
            'auth_audit_logs'      => ['rows' => DB::table('auth_audit_logs')->count(),   'retention' => env('AUTH_LOG_RETENTION_DAYS', 365) . ' days'],
            'drift_reports'        => ['rows' => DB::table('drift_reports')->count(),      'retention' => env('DRIFT_REPORT_RETENTION_DAYS', 90) . ' days'],
            'access_reviews'       => ['rows' => DB::table('access_reviews')->count(),    'retention' => env('ACCESS_REVIEW_RETENTION_DAYS', 730) . ' days'],
            'vps_access_grants'    => ['rows' => DB::table('vps_access_grants')->count()],
            'security_permissions' => ['rows' => DB::table('security_permissions')->count()],
            'jobs'                 => ['rows' => DB::table('jobs')->count()],
            'failed_jobs'          => ['rows' => DB::table('failed_jobs')->count(),       'retention' => env('FAILED_JOB_RETENTION_DAYS', 30) . ' days'],
            'permission_approvals' => ['rows' => DB::table('permission_approvals')->count()],
        ];

        return Inertia::render('Ops/Database', [
            'tables' => $tables,
        ]);
    }
}
