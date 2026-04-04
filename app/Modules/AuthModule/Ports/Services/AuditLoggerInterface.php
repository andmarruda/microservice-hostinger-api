<?php

namespace App\Modules\AuthModule\Ports\Services;

use App\Modules\AuthModule\Models\Invitation;
use App\Modules\AuthModule\Models\User;

interface AuditLoggerInterface
{
    public function logInvitationCreated(Invitation $invitation, User $inviter, ?string $ipAddress = null, ?string $userAgent = null): void;

    public function logInvitationAccepted(Invitation $invitation, ?string $ipAddress = null, ?string $userAgent = null): void;

    public function logUserRegistered(User $user, Invitation $invitation, ?string $ipAddress = null, ?string $userAgent = null): void;

    public function logLoginSucceeded(User $user, string $method, ?string $ipAddress = null, ?string $userAgent = null): void;

    public function logLoginFailed(string $email, ?string $ipAddress = null, ?string $userAgent = null): void;

    public function logLogout(User $user, string $method, ?string $ipAddress = null, ?string $userAgent = null): void;
}
