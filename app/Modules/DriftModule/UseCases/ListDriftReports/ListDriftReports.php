<?php

namespace App\Modules\DriftModule\UseCases\ListDriftReports;

use App\Modules\AuthModule\Models\User;
use App\Modules\DriftModule\Models\DriftReport;
use Illuminate\Database\Eloquent\Collection;

class ListDriftReports
{
    public function execute(User $actor, array $filters = []): ListDriftReportsResult
    {
        if (!$actor->hasRole('root')) {
            return ListDriftReportsResult::forbidden();
        }

        $query = DriftReport::query()->orderByDesc('detected_at');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        return ListDriftReportsResult::success($query->get());
    }
}
