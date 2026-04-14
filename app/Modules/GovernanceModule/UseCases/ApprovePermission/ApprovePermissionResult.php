<?php

namespace App\Modules\GovernanceModule\UseCases\ApprovePermission;

use App\Modules\GovernanceModule\Models\PermissionApproval;

class ApprovePermissionResult
{
    private function __construct(
        public readonly bool $success,
        public readonly string $error = '',
        public readonly ?PermissionApproval $approval = null,
    ) {}

    public static function success(PermissionApproval $approval): self
    {
        return new self(success: true, approval: $approval);
    }

    public static function forbidden(): self
    {
        return new self(success: false, error: 'forbidden');
    }

    public static function notFound(): self
    {
        return new self(success: false, error: 'not_found');
    }

    public static function selfApprove(): self
    {
        return new self(success: false, error: 'self_approve');
    }
}
