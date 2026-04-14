<?php

namespace App\Modules\DriftModule\Http\Controllers;

use App\Modules\DriftModule\UseCases\DismissDriftReport\DismissDriftReport;
use App\Modules\DriftModule\UseCases\ListDriftReports\ListDriftReports;
use App\Modules\DriftModule\UseCases\ResolveDriftReport\ResolveDriftReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DriftController extends Controller
{
    public function __construct(
        private ListDriftReports $listDriftReports,
        private ResolveDriftReport $resolveDriftReport,
        private DismissDriftReport $dismissDriftReport,
    ) {}

    public function index(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $filters = $request->only(['status', 'severity']);
        $result  = $this->listDriftReports->execute($request->user(), $filters);

        if (!$result->success) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json(['data' => $result->reports]);
    }

    public function resolve(Request $request, int $reportId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->resolveDriftReport->execute($request->user(), $reportId);

        if (!$result->success) {
            return match ($result->error) {
                'forbidden'     => response()->json(['message' => 'Forbidden.'], 403),
                'not_found'     => response()->json(['message' => 'Drift report not found.'], 404),
                'already_closed' => response()->json([
                    'message' => 'Report is already closed.',
                    'status'  => $result->currentStatus,
                ], 409),
                default         => response()->json(['message' => 'Unexpected error.'], 500),
            };
        }

        return response()->json(['data' => ['resolved' => true]]);
    }

    public function dismiss(Request $request, int $reportId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->dismissDriftReport->execute($request->user(), $reportId);

        if (!$result->success) {
            return match ($result->error) {
                'forbidden'     => response()->json(['message' => 'Forbidden.'], 403),
                'not_found'     => response()->json(['message' => 'Drift report not found.'], 404),
                'already_closed' => response()->json([
                    'message' => 'Report is already closed.',
                    'status'  => $result->currentStatus,
                ], 409),
                default         => response()->json(['message' => 'Unexpected error.'], 500),
            };
        }

        return response()->json(['data' => ['dismissed' => true]]);
    }
}
