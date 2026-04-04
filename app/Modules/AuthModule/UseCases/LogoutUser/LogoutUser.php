<?php

namespace App\Modules\AuthModule\UseCases\LogoutUser;

use App\Modules\AuthModule\Models\User;
use App\Modules\AuthModule\Ports\Services\AuditLoggerInterface;

class LogoutUser
{
    public function __construct(
        private AuditLoggerInterface $auditLogger,
    ) {}

    public function execute(User $user, bool $revokeToken = false, ?string $ipAddress = null, ?string $userAgent = null): LogoutUserResult
    {
        if ($revokeToken) {
            $user->currentAccessToken()?->delete();
        }

        $this->auditLogger->logLogout($user, $revokeToken ? 'token' : 'session', $ipAddress, $userAgent);

        return LogoutUserResult::success();
    }
}
