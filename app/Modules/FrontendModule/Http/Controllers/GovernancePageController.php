<?php

namespace App\Modules\FrontendModule\Http\Controllers;

use App\Modules\GovernanceModule\Models\AccessReview;
use App\Modules\GovernanceModule\Models\PermissionApproval;
use App\Modules\GovernanceModule\UseCases\ApprovePermission\ApprovePermission;
use App\Modules\GovernanceModule\UseCases\CreateAccessReview\CreateAccessReview;
use App\Modules\GovernanceModule\UseCases\DecideReviewItem\DecideReviewItem;
use App\Modules\GovernanceModule\UseCases\ExportAuditLogs\ExportAuditLogs;
use App\Modules\GovernanceModule\UseCases\ListAccessReviews\ListAccessReviews;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class GovernancePageController extends Controller
{
    public function __construct(
        private ListAccessReviews $listAccessReviews,
        private CreateAccessReview $createAccessReview,
        private DecideReviewItem $decideReviewItem,
        private ExportAuditLogs $exportAuditLogs,
        private ApprovePermission $approvePermission,
    ) {}

    public function reviews(Request $request): Response
    {
        $result = $this->listAccessReviews->execute($request->user(), $request->only(['status']));

        return Inertia::render('Governance/AccessReviews/Index', [
            'reviews' => $result->success ? $result->reviews->toArray() : [],
        ]);
    }

    public function storeReview(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end'   => ['required', 'date', 'after:period_start'],
        ]);

        $result = $this->createAccessReview->execute($request->user(), $validated['period_start'], $validated['period_end']);

        if (!$result->success) {
            return back()->with('error', 'Failed to create access review.');
        }

        return redirect()->route('governance.reviews.show', $result->review->id)
            ->with('success', 'Access review created.');
    }

    public function reviewShow(Request $request, int $id): Response
    {
        $review = AccessReview::with(['items.user', 'reviewer'])->findOrFail($id);

        return Inertia::render('Governance/AccessReviews/Show', [
            'review' => $review->toArray(),
            'items'  => $review->items->toArray(),
        ]);
    }

    public function decideItem(Request $request, int $id, int $itemId): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approved,revoked'],
        ]);

        $result = $this->decideReviewItem->execute($request->user(), $id, $itemId, $validated['decision']);

        if (!$result->success) {
            return back()->with('error', match ($result->error) {
                'forbidden'    => 'You do not have permission.',
                'not_found'    => 'Review item not found.',
                default        => 'Decision failed.',
            });
        }

        return back()->with('success', 'Decision recorded.');
    }

    public function audit(Request $request): Response
    {
        $filters = $request->only(['from', 'to', 'actor_id', 'action', 'vps_id']);
        $result  = $this->exportAuditLogs->execute($request->user(), $filters, 'json');

        return Inertia::render('Governance/AuditExport', [
            'logs'    => $result->success ? ($result->data ?? []) : [],
            'filters' => $filters,
        ]);
    }

    public function auditDownload(Request $request): HttpResponse|RedirectResponse
    {
        $request->validate([
            'from'     => ['sometimes', 'date'],
            'to'       => ['sometimes', 'date'],
            'actor_id' => ['sometimes', 'integer'],
            'action'   => ['sometimes', 'string'],
            'vps_id'   => ['sometimes', 'string'],
        ]);

        $result = $this->exportAuditLogs->execute(
            $request->user(),
            $request->only(['from', 'to', 'actor_id', 'action', 'vps_id']),
            'csv',
        );

        if (!$result->success) {
            return back()->with('error', 'Export failed: insufficient permissions.');
        }

        return response($result->csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="audit-export-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }

    public function approvals(Request $request): Response
    {
        if (!$request->user()->hasRole('root')) {
            abort(403);
        }

        $approvals = PermissionApproval::with(['requester', 'targetUser'])
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Governance/Approvals/Index', [
            'approvals' => $approvals->toArray(),
        ]);
    }

    public function approve(Request $request, int $id): RedirectResponse
    {
        $result = $this->approvePermission->execute($request->user(), $id);

        if (!$result->success) {
            return back()->with('error', match ($result->error) {
                'forbidden'    => 'You do not have permission.',
                'not_found'    => 'Approval not found.',
                'self_approve' => 'You cannot approve your own request.',
                default        => 'Approval failed.',
            });
        }

        return back()->with('success', 'Permission approved.');
    }
}
