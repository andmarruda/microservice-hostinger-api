<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ExpireInvitations;
use App\Modules\AuthModule\Factories\InvitationFactory;
use App\Modules\AuthModule\Models\Invitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpireInvitationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_expired_pending_invitations(): void
    {
        InvitationFactory::new()->create([
            'expires_at'  => now()->subDay(),
            'accepted_at' => null,
        ]);

        (new ExpireInvitations())->handle();

        $this->assertDatabaseCount('invitations', 0);
    }

    public function test_does_not_delete_accepted_invitations(): void
    {
        InvitationFactory::new()->create([
            'expires_at'  => now()->subDay(),
            'accepted_at' => now()->subHours(2),
        ]);

        (new ExpireInvitations())->handle();

        $this->assertDatabaseCount('invitations', 1);
    }

    public function test_does_not_delete_valid_pending_invitations(): void
    {
        InvitationFactory::new()->create([
            'expires_at'  => now()->addDay(),
            'accepted_at' => null,
        ]);

        (new ExpireInvitations())->handle();

        $this->assertDatabaseCount('invitations', 1);
    }

    public function test_deletes_only_expired_unaccepted_ones(): void
    {
        // expired + unaccepted → deleted
        InvitationFactory::new()->create([
            'expires_at'  => now()->subDay(),
            'accepted_at' => null,
        ]);
        // expired + accepted → kept
        InvitationFactory::new()->create([
            'expires_at'  => now()->subDay(),
            'accepted_at' => now()->subHours(1),
        ]);
        // pending → kept
        InvitationFactory::new()->create([
            'expires_at'  => now()->addDay(),
            'accepted_at' => null,
        ]);

        (new ExpireInvitations())->handle();

        $this->assertDatabaseCount('invitations', 2);
    }
}
