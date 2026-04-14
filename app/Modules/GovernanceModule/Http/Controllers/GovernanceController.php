<?php

namespace App\Modules\GovernanceModule\Http\Controllers;

use App\Modules\GovernanceModule\UseCases\ApprovePermission\ApprovePermission;
use App\Modules\GovernanceModule\UseCases\CreateAccessReview\CreateAccessReview;
use App\Modules\GovernanceModule\UseCases\DecideReviewItem\DecideReviewItem;
use App\Modules\GovernanceModule\UseCases\ExportAuditLogs\ExportAuditLogs;
use App\Modules\GovernanceModule\UseCases\ListAccessReviews\ListAccessReviews;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class GovernanceController extends Controller
{
    public function __construct(
        private ExportAuditLogs $exportAuditLogs,
        private ListAccessReviews $listAccessReviews,
        private CreateAccessReview $createAccessReview,
        private DecideReviewItem $decideReviewItem,
        private ApprovePermission $approvePermission,
    ) {}

    public function auditExport(Request $request): JsonResponse|Response
    {
        $request->validate([
            'from'     => ['sometimes', 'date'],
            'to'       => ['sometimes', 'date', 'after_or_equal:from'],
            'actor_id' => ['sometimes', 'integer'],
            'action'   => ['sometimes', 'string'],
            'vps_id'   => ['sometimes', 'string'],
            'format'   => ['sometimes', 'string', 'in:json,csv'],
        ]);

        $format = $request->input('format', 'json');
        $result = $this->exportAuditLogs->execute($request->user(), $request->only(['from', 'to', 'actor_id', 'action', 'vps_id']), $format);

        if (!$result->success) {
            return ApiResponse::error('Forbidden.', 403);
        }

        if ($format === 'csv') {
            return response($result->csv, 200, [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => 'attachment; filename="audit-export-' . now()->format('Y-m-d') . '.csv"',
            ]);
        }

        return ApiResponse::success($result->data);
    }

    public function indexAccessReviews(Request $request): JsonResponse
    {
        $result = $this->listAccessReviews->execute($request->user(), $request->only(['status']));

        if (!$result->success) {
            return ApiResponse::error('Forbidden.', 403);
        }

        return ApiResponse::success($result->reviews);
    }

    public function storeAccessReview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end'   => ['required', 'date', 'after:period_start'],
        ]);

        $result = $this->createAccessReview->execute($request->user(), $validated['period_start'], $validated['period_end']);

        if (!$result->success) {
            return ApiResponse::error('Forbidden.', 403);
        }

        return ApiResponse::success([
            'id'           => $result->review->id,
            'status'       => $result->review->status,
            'period_start' => $result->review->period_start,
            'period_end'   => $result->review->period_end,
        ], 201);
    }

    public function decideReviewItem(Request $request, int $reviewId, int $itemId): JsonResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'string', 'in:approved,revoked'],
        ]);

        $result = $this->decideReviewItem->execute($request->user(), $reviewId, $itemId, $validated['decision']);

        if (!$result->success) {
            return match ($result->error) {
                'forbidden'        => ApiResponse::error('Forbidden.', 403),
                'not_found'        => ApiResponse::error('Review item not found.', 404),
                'invalid_decision' => ApiResponse::error('Decision must be approved or revoked.', 422),
                default            => ApiResponse::error('Unexpected error.', 500),
            };
        }

        return ApiResponse::success([
            'id'         => $result->item->id,
            'decision'   => $result->item->decision,
            'decided_at' => $result->item->decided_at?->toIso8601String(),
            'decided_by' => $result->item->decided_by,
        ]);
    }

    public function approvePermission(Request $request, int $approvalId): JsonResponse
    {
        $result = $this->approvePermission->execute($request->user(), $approvalId);

        if (!$result->success) {
            return match ($result->error) {
                'forbidden'   => ApiResponse::error('Forbidden.', 403),
                'not_found'   => ApiResponse::error('Approval not found.', 404),
                'self_approve' => ApiResponse::error('Self-approval is not allowed.', 422),
                default       => ApiResponse::error('Unexpected error.', 500),
            };
        }

        return ApiResponse::success([
            'id'          => $result->approval->id,
            'status'      => $result->approval->status,
            'approved_by' => $result->approval->approved_by,
            'decided_at'  => $result->approval->decided_at?->toIso8601String(),
        ]);
    }
}
