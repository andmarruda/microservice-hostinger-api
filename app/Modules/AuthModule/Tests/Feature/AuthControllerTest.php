<?php

namespace App\Modules\AuthModule\Tests\Feature;

use App\Modules\AuthModule\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('secret'),
        ]);

        $response = $this->postJson('/auth/login', [
            'email' => 'user@example.com',
            'password' => 'secret',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'name', 'email']]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('secret'),
        ]);

        $response = $this->postJson('/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_fails_with_unknown_email(): void
    {
        $response = $this->postJson('/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_with_token_mode_returns_token(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('secret'),
        ]);

        $response = $this->postJson('/auth/login', [
            'email' => 'user@example.com',
            'password' => 'secret',
            'token_mode' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'name', 'email', 'token']]);
    }

    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson('/auth/login', []);

        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/auth/logout');

        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson('/auth/logout');

        $response->assertStatus(401);
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_me_requires_authentication(): void
    {
        $response = $this->getJson('/auth/me');

        $response->assertStatus(401);
    }

    public function test_login_creates_audit_log_on_success(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('secret'),
        ]);

        $this->postJson('/auth/login', [
            'email' => 'user@example.com',
            'password' => 'secret',
        ]);

        $this->assertDatabaseHas('auth_audit_logs', [
            'action' => 'login_succeeded',
            'actor_email' => 'user@example.com',
        ]);
    }

    public function test_login_creates_audit_log_on_failure(): void
    {
        $this->postJson('/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'wrong',
        ]);

        $this->assertDatabaseHas('auth_audit_logs', [
            'action' => 'login_failed',
            'actor_email' => 'nobody@example.com',
        ]);
    }

    public function test_logout_creates_audit_log(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/auth/logout');

        $this->assertDatabaseHas('auth_audit_logs', [
            'action' => 'logout',
            'actor_id' => $user->id,
        ]);
    }
}
