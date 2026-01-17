<?php

namespace App\Modules\AuthModule\Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Faker\Factory as FakerFactory;
use Tests\TestCase;
use App\Modules\AuthModule\Models\{
    User,
    Invitation
};

/**
 * @covers \App\Modules\AuthModule\Controllers\InvitationController
 * Test the InvitationController functionalities.
 * 
 * @package App\Modules\AuthModule\Tests\Feature
 * @Author Anderson Arruda < andmarruda@gmail.com >
 */
class InvitationControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test inviting a user.
     * 
     * @return void
     */
    public function test_invite_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/invitations/create', []);

        $response->assertStatus(201);
    }
}