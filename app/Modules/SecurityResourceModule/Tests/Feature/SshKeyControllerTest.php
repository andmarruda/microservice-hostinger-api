<?php

namespace App\Modules\SecurityResourceModule\Tests\Feature;

use App\Modules\AuthModule\Models\User;
use App\Modules\SecurityResourceModule\Models\SecurityPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SshKeyControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $vpsId = 'vps-abc-123';
    private string $keyId = 'key-789';
    private string $validPublicKey = 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQC test@example.com';

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            '*/public-keys*' => Http::response([], 200),
        ]);
    }

    public function test_authenticated_user_with_permission_can_add_ssh_key(): void
    {
        $user = $this->userWithSshKeyPermission();

        $response = $this->actingAs($user)->postJson("/vps/{$this->vpsId}/ssh-keys", [
            'key_name' => 'my-laptop',
            'public_key' => $this->validPublicKey,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['vps_id', 'correlation_id']]);
    }

    public function test_authenticated_user_with_permission_can_remove_ssh_key(): void
    {
        $user = $this->userWithSshKeyPermission();

        $response = $this->actingAs($user)->deleteJson("/vps/{$this->vpsId}/ssh-keys/{$this->keyId}", [
            'confirm_destructive' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['vps_id', 'correlation_id']]);
    }

    public function test_unauthenticated_user_cannot_add_ssh_key(): void
    {
        $response = $this->postJson("/vps/{$this->vpsId}/ssh-keys", [
            'key_name' => 'my-laptop',
            'public_key' => $this->validPublicKey,
        ]);

        $response->assertStatus(401);
    }

    public function test_user_without_ssh_key_permission_gets_forbidden(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson("/vps/{$this->vpsId}/ssh-keys", [
            'key_name' => 'my-laptop',
            'public_key' => $this->validPublicKey,
        ]);

        $response->assertStatus(403);
    }

    public function test_add_ssh_key_fails_validation_without_public_key(): void
    {
        $user = $this->userWithSshKeyPermission();

        $response = $this->actingAs($user)->postJson("/vps/{$this->vpsId}/ssh-keys", [
            'key_name' => 'my-laptop',
        ]);

        $response->assertStatus(422);
    }

    public function test_remove_ssh_key_requires_confirm_destructive(): void
    {
        $user = $this->userWithSshKeyPermission();

        $response = $this->actingAs($user)->deleteJson("/vps/{$this->vpsId}/ssh-keys/{$this->keyId}", []);

        $response->assertStatus(422);
    }

    public function test_add_ssh_key_creates_audit_log(): void
    {
        $user = $this->userWithSshKeyPermission();

        $this->actingAs($user)->postJson("/vps/{$this->vpsId}/ssh-keys", [
            'key_name' => 'my-laptop',
            'public_key' => $this->validPublicKey,
        ]);

        $this->assertDatabaseHas('infra_audit_logs', [
            'action' => 'ssh_key_add',
            'actor_id' => $user->id,
            'vps_id' => $this->vpsId,
            'resource_type' => 'ssh_key',
            'outcome' => 'success',
        ]);
    }

    private function userWithSshKeyPermission(): User
    {
        $user = User::factory()->create();

        SecurityPermission::factory()->withSshKeys()->create([
            'user_id' => $user->id,
            'vps_id' => $this->vpsId,
            'granted_by' => $user->id,
        ]);

        return $user;
    }
}
