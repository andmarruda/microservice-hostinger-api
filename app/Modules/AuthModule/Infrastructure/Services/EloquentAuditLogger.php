<?php

namespace App\Modules\AuthModule\Infrastructure\Services;

use App\Modules\AuthModule\Models\AuthAuditLog;
use App\Modules\AuthModule\Models\Invitation;
use App\Modules\AuthModule\Models\User;
use App\Modules\AuthModule\Ports\Services\AuditLoggerInterface;

class EloquentAuditLogger implements AuditLoggerInterface
{
    public function logInvitationCreated(Invitation $invitation, User $inviter, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        AuthAuditLog::create([
            'action' => 'invitation_created',
            'actor_id' => $inviter->id,
            'actor_email' => $inviter->email,
            'target_email' => $invitation->email,
            'invitation_id' => $invitation->id,
            'resource_scope' => $invitation->resource_scope,
            'metadata' => [
                'expires_at' => $invitation->expires_at->toIso8601String(),
            ],
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }

    public function logInvitationAccepted(Invitation $invitation, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        AuthAuditLog::create([
            'action' => 'invitation_accepted',
            'actor_id' => null,
            'actor_email' => $invitation->email,
            'target_email' => $invitation->email,
            'invitation_id' => $invitation->id,
            'resource_scope' => $invitation->resource_scope,
            'metadata' => [
                'accepted_at' => $invitation->accepted_at?->toIso8601String(),
            ],
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }

    public function logUserRegistered(User $user, Invitation $invitation, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        AuthAuditLog::create([
            'action' => 'user_registered',
            'actor_id' => $user->id,
            'actor_email' => $user->email,
            'target_email' => $user->email,
            'invitation_id' => $invitation->id,
            'resource_scope' => $invitation->resource_scope,
            'metadata' => [
                'user_id' => $user->id,
                'user_name' => $user->name,
            ],
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }

    public function logLoginSucceeded(User $user, string $method, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        AuthAuditLog::create([
            'action' => 'login_succeeded',
            'actor_id' => $user->id,
            'actor_email' => $user->email,
            'target_email' => null,
            'invitation_id' => null,
            'resource_scope' => null,
            'metadata' => ['method' => $method],
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }

    public function logLoginFailed(string $email, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        AuthAuditLog::create([
            'action' => 'login_failed',
            'actor_id' => null,
            'actor_email' => $email,
            'target_email' => null,
            'invitation_id' => null,
            'resource_scope' => null,
            'metadata' => [],
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }

    public function logLogout(User $user, string $method, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        AuthAuditLog::create([
            'action' => 'logout',
            'actor_id' => $user->id,
            'actor_email' => $user->email,
            'target_email' => null,
            'invitation_id' => null,
            'resource_scope' => null,
            'metadata' => ['method' => $method],
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }
}
