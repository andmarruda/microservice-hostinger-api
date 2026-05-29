<?php

namespace App\Modules\FrontendModule\Http\Controllers;

use App\Infrastructure\Quota\HostingerQuotaTracker;
use App\Modules\DriftModule\Models\DriftReport;
use App\Modules\GovernanceModule\Models\AccessReview;
use App\Modules\GovernanceModule\Models\PermissionApproval;
use App\Modules\HostingerProxyModule\UseCases\GetVpsList\GetVpsList;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private GetVpsList $getVpsList,
        private HostingerQuotaTracker $quota,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $vpsResult = $this->getVpsList->execute($user);
        $vpsCount  = $vpsResult->success ? count($vpsResult->data) : 0;
        $isRoot    = $user->hasRole('root');

        $openReviews      = $isRoot ? AccessReview::where('status', 'pending')->count() : null;
        $pendingApprovals = $isRoot ? PermissionApproval::where('status', 'pending')->count() : null;
        $openDriftReports = $isRoot ? DriftReport::where('status', 'open')->count() : null;

        $queuePending = $isRoot ? DB::table('jobs')->count() : null;
        $queueFailed  = $isRoot ? DB::table('failed_jobs')->count() : null;

        $quota = null;

        if ($isRoot) {
            $quotaTotal      = $this->quota->getToday();
            $quotaWarnAt     = $this->quota->getWarningThreshold();
            $quotaHardLimit  = $this->quota->getHardLimit();
            $quotaByResource = $this->quota->getTodayByResource();

            $quota = [
                'total'       => $quotaTotal,
                'warn_at'     => $quotaWarnAt,
                'hard_limit'  => $quotaHardLimit,
                'by_resource' => $quotaByResource,
                'percent'     => $quotaWarnAt > 0 ? round($quotaTotal / $quotaWarnAt * 100, 1) : 0,
                'status'      => match (true) {
                    $quotaHardLimit > 0 && $quotaTotal >= $quotaHardLimit => 'exceeded',
                    $quotaWarnAt > 0 && $quotaTotal >= $quotaWarnAt => 'warning',
                    default => 'ok',
                },
            ];
        }

        return Inertia::render('Dashboard', [
            'vpsCount'          => $vpsCount,
            'openReviews'       => $openReviews,
            'pendingApprovals'  => $pendingApprovals,
            'openDriftReports'  => $openDriftReports,
            'queuePending'      => $queuePending,
            'queueFailed'       => $queueFailed,
            'quota'             => $quota,
        ]);
    }
}
