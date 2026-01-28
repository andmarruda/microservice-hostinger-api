<?php

namespace App\Modules\AuthModule\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Modules\AuthModule\Models\User;
use App\Modules\AuthModule\Models\Invitation;

class InvitationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_invite_user(): void
    {
        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($manager)
            ->postJson('/invitations/create', [
                'email' => 'newuser@example.com',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'email', 'expires_at'],
            ]);

        $this->assertDatabaseHas('invitations', [
            'email' => 'newuser@example.com',
            'invited_by' => $manager->id,
        ]);
    }

    public function test_non_manager_cannot_invite_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/invitations/create', [
                'email' => 'newuser@example.com',
            ]);

        $response->assertStatus(403);
    }

    public function test_invite_requires_valid_email(): void
    {
        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($manager)
            ->postJson('/invitations/create', [
                'email' => 'invalid-email',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_cannot_invite_already_registered_email(): void
    {
        $manager = User::factory()->manager()->create();
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->actingAs($manager)
            ->postJson('/invitations/create', [
                'email' => 'existing@example.com',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_accept_invitation_with_valid_token(): void
    {
        $invitation = Invitation::factory()->create();

        $response = $this->postJson('/invitations/accept', [
            'token' => $invitation->token,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['email', 'accepted_at'],
            ]);

        $this->assertDatabaseHas('invitations', [
            'id' => $invitation->id,
            'accepted_at' => now(),
        ]);
    }

    public function test_cannot_accept_expired_invitation(): void
    {
        $invitation = Invitation::factory()->expired()->create();

        $response = $this->postJson('/invitations/accept', [
            'token' => $invitation->token,
        ]);

        $response->assertStatus(410);
    }

    public function test_cannot_accept_already_used_invitation(): void
    {
        $invitation = Invitation::factory()->accepted()->create();

        $response = $this->postJson('/invitations/accept', [
            'token' => $invitation->token,
        ]);

        $response->assertStatus(410);
    }

    public function test_cannot_accept_invalid_token(): void
    {
        $response = $this->postJson('/invitations/accept', [
            'token' => 'invalid-token-12345',
        ]);

        $response->assertStatus(404);
    }

    public function test_guest_cannot_create_invitation(): void
    {
        $response = $this->postJson('/invitations/create', [
            'email' => 'newuser@example.com',
        ]);

        $response->assertStatus(401);
    }
}