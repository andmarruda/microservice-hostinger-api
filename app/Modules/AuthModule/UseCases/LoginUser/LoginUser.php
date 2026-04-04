<?php

namespace App\Modules\AuthModule\UseCases\LoginUser;

use App\Modules\AuthModule\Ports\Repositories\AuthRepositoryInterface;
use App\Modules\AuthModule\Ports\Services\AuditLoggerInterface;

class LoginUser
{
    public function __construct(
        private AuthRepositoryInterface $auth,
        private AuditLoggerInterface $auditLogger,
    ) {}

    public function execute(string $email, string $password, bool $issueToken = false, ?string $ipAddress = null, ?string $userAgent = null): LoginUserResult
    {
        $user = $this->auth->findByCredentials($email, $password);

        if (!$user) {
            $this->auditLogger->logLoginFailed($email, $ipAddress, $userAgent);
            return LoginUserResult::invalidCredentials();
        }

        $token = null;

        if ($issueToken) {
            $token = $user->createToken('api-token')->plainTextToken;
        }

        $this->auditLogger->logLoginSucceeded($user, $issueToken ? 'token' : 'session', $ipAddress, $userAgent);

        return LoginUserResult::success($user, $token);
    }
}
