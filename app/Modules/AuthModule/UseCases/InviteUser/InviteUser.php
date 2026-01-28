<?php

namespace App\Modules\AuthModule\UseCases\InviteUser;

use App\Modules\AuthModule\Models\Invitation;
use App\Modules\AuthModule\Models\User;
use App\Modules\AuthModule\Ports\Repositories\InvitationRepositoryInterface;
use App\Modules\AuthModule\Ports\Repositories\UserRepositoryInterface;
use Illuminate\Support\Str;

class InviteUser
{
    public function __construct(
        private InvitationRepositoryInterface $invitations,
        private UserRepositoryInterface $users,
    ) {}

    public function execute(User $inviter, string $email): InviteUserResult
    {
        if (!$inviter->isManager()) {
            return InviteUserResult::forbidden();
        }

        if ($this->users->emailExists($email)) {
            return InviteUserResult::emailAlreadyRegistered();
        }

        $invitation = $this->invitations->create([
            'email' => $email,
            'token' => Str::random(64),
            'invited_by' => $inviter->id,
            'expires_at' => now()->addDays(7),
        ]);

        return InviteUserResult::success($invitation);
    }
}
