<?php

namespace App\Modules\AuthModule\Tests\Unit;

use App\Modules\AuthModule\Models\Invitation;
use App\Modules\AuthModule\Ports\Repositories\InvitationRepositoryInterface;
use App\Modules\AuthModule\UseCases\AcceptInvitation\AcceptInvitation;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class AcceptInvitationTest extends TestCase
{
    private MockInterface $invitations;
    private AcceptInvitation $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invitations = Mockery::mock(InvitationRepositoryInterface::class);
        $this->useCase = new AcceptInvitation($this->invitations);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_accept_valid_invitation(): void
    {
        $token = 'valid-token';
        $invitation = $this->createValidInvitation();

        $this->invitations
            ->shouldReceive('findByToken')
            ->with($token)
            ->once()
            ->andReturn($invitation);

        $this->invitations
            ->shouldReceive('markAsAccepted')
            ->with($invitation)
            ->once();

        $result = $this->useCase->execute($token);

        $this->assertTrue($result->success);
        $this->assertNull($result->error);
        $this->assertSame($invitation, $result->invitation);
    }

    public function test_cannot_accept_invalid_token(): void
    {
        $token = 'invalid-token';

        $this->invitations
            ->shouldReceive('findByToken')
            ->with($token)
            ->once()
            ->andReturn(null);

        $this->invitations->shouldNotReceive('markAsAccepted');

        $result = $this->useCase->execute($token);

        $this->assertFalse($result->success);
        $this->assertEquals('not_found', $result->error);
        $this->assertNull($result->invitation);
    }

    public function test_cannot_accept_already_used_invitation(): void
    {
        $token = 'used-token';
        $invitation = $this->createUsedInvitation();

        $this->invitations
            ->shouldReceive('findByToken')
            ->with($token)
            ->once()
            ->andReturn($invitation);

        $this->invitations->shouldNotReceive('markAsAccepted');

        $result = $this->useCase->execute($token);

        $this->assertFalse($result->success);
        $this->assertEquals('already_used', $result->error);
        $this->assertNull($result->invitation);
    }

    public function test_cannot_accept_expired_invitation(): void
    {
        $token = 'expired-token';
        $invitation = $this->createExpiredInvitation();

        $this->invitations
            ->shouldReceive('findByToken')
            ->with($token)
            ->once()
            ->andReturn($invitation);

        $this->invitations->shouldNotReceive('markAsAccepted');

        $result = $this->useCase->execute($token);

        $this->assertFalse($result->success);
        $this->assertEquals('expired', $result->error);
        $this->assertNull($result->invitation);
    }

    private function createValidInvitation(): Invitation
    {
        $invitation = new Invitation();
        $invitation->id = 1;
        $invitation->email = 'user@example.com';
        $invitation->token = 'valid-token';
        $invitation->accepted_at = null;
        $invitation->expires_at = Carbon::now()->addDays(7);

        return $invitation;
    }

    private function createUsedInvitation(): Invitation
    {
        $invitation = new Invitation();
        $invitation->id = 2;
        $invitation->email = 'user@example.com';
        $invitation->token = 'used-token';
        $invitation->accepted_at = Carbon::now();
        $invitation->expires_at = Carbon::now()->addDays(7);

        return $invitation;
    }

    private function createExpiredInvitation(): Invitation
    {
        $invitation = new Invitation();
        $invitation->id = 3;
        $invitation->email = 'user@example.com';
        $invitation->token = 'expired-token';
        $invitation->accepted_at = null;
        $invitation->expires_at = Carbon::now()->subDay();

        return $invitation;
    }
}
