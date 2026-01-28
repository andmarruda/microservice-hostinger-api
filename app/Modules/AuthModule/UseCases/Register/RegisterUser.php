<?php

namespace App\Modules\AuthModule\UseCases\Register;

use App\Modules\AuthModule\Ports\Repositories\InvitationRepositoryInterface;
use App\Modules\AuthModule\Ports\Repositories\UserRepositoryInterface;

class RegisterUser
{
    public function __construct(
        private InvitationRepositoryInterface $invitations,
        private UserRepositoryInterface $users,
    ) {}

    public function execute(string $token, string $name, string $password): RegisterUserResult
    {
        $invitation = $this->invitations->findByToken($token);

        if (!$invitation) {
            return RegisterUserResult::invitationNotFound();
        }

        if ($invitation->accepted_at !== null) {
            return RegisterUserResult::invitationAlreadyUsed();
        }

        if ($invitation->expires_at->isPast()) {
            return RegisterUserResult::invitationExpired();
        }

        $user = $this->users->create([
            'name' => $name,
            'email' => $invitation->email,
            'password' => $password,
        ]);

        $this->invitations->markAsAccepted($invitation);

        return RegisterUserResult::success($user);
    }
}
