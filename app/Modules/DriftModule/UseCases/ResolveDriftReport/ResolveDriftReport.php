<?php

namespace App\Modules\DriftModule\UseCases\ResolveDriftReport;

use App\Modules\AuthModule\Models\User;
use App\Modules\DriftModule\Models\DriftReport;

class ResolveDriftReport
{
    public function execute(User $actor, int $reportId): ResolveDriftReportResult
    {
        if (!$actor->hasRole('root')) {
            return ResolveDriftReportResult::forbidden();
        }

        $report = DriftReport::find($reportId);

        if (!$report) {
            return ResolveDriftReportResult::notFound();
        }

        if ($report->status !== 'open') {
            return ResolveDriftReportResult::alreadyClosed($report->status);
        }

        $report->update([
            'status'      => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $actor->id,
        ]);

        return ResolveDriftReportResult::success();
    }
}
