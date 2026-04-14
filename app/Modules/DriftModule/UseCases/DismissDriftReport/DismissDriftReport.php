<?php

namespace App\Modules\DriftModule\UseCases\DismissDriftReport;

use App\Modules\AuthModule\Models\User;
use App\Modules\DriftModule\Models\DriftReport;

class DismissDriftReport
{
    public function execute(User $actor, int $reportId): DismissDriftReportResult
    {
        if (!$actor->hasRole('root')) {
            return DismissDriftReportResult::forbidden();
        }

        $report = DriftReport::find($reportId);

        if (!$report) {
            return DismissDriftReportResult::notFound();
        }

        if ($report->status !== 'open') {
            return DismissDriftReportResult::alreadyClosed($report->status);
        }

        $report->update([
            'status'      => 'dismissed',
            'resolved_at' => now(),
            'resolved_by' => $actor->id,
        ]);

        return DismissDriftReportResult::success();
    }
}
