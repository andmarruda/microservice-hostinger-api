<?php

namespace App\Modules\AuthModule\UseCases\AcceptInvitation;

use App\Modules\AuthModule\Ports\Repositories\InvitationRepositoryInterface;

class AcceptInvitation
{
    public function __construct(
        private InvitationRepositoryInterface $invitations,
    ) {}

    public function execute(string $token): AcceptInvitationResult
    {
        $invitation = $this->invitations->findByToken($token);

        if (!$invitation) {
            return AcceptInvitationResult::notFound();
        }

        if ($invitation->accepted_at !== null) {
            return AcceptInvitationResult::alreadyUsed();
        }

        if ($invitation->expires_at->isPast()) {
            return AcceptInvitationResult::expired();
        }

        $this->invitations->markAsAccepted($invitation);

        return AcceptInvitationResult::success($invitation);
    }
}
