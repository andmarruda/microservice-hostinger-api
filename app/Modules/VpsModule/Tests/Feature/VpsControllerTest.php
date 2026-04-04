<?php

namespace App\Modules\VpsModule\Tests\Feature;

use App\Modules\AuthModule\Models\User;
use App\Modules\VpsModule\Models\VpsAccessGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VpsControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $vpsId = 'vps-abc-123';

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            '*/start' => Http::response([], 200),
            '*/stop' => Http::response([], 200),
            '*/restart' => Http::response([], 200),
        ]);
    }

    public function test_authenticated_user_with_access_can_start_vps(): void
    {
        $user = $this->userWithAccess();

        $response = $this->actingAs($user)->postJson("/vps/{$this->vpsId}/start");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['vps_id', 'correlation_id']]);
    }

    public function test_authenticated_user_with_access_can_stop_vps(): void
    {
        $user = $this->userWithAccess();

        $response = $this->actingAs($user)->postJson("/vps/{$this->vpsId}/stop");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['vps_id', 'correlation_id']]);
    }

    public function test_authenticated_user_with_access_can_reboot_vps(): void
    {
        $user = $this->userWithAccess();

        $response = $this->actingAs($user)->postJson("/vps/{$this->vpsId}/reboot");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['vps_id', 'correlation_id']]);
    }

    public function test_unauthenticated_user_cannot_start_vps(): void
    {
        $response = $this->postJson("/vps/{$this->vpsId}/start");

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_stop_vps(): void
    {
        $response = $this->postJson("/vps/{$this->vpsId}/stop");

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_reboot_vps(): void
    {
        $response = $this->postJson("/vps/{$this->vpsId}/reboot");

        $response->assertStatus(401);
    }

    public function test_user_without_access_grant_gets_forbidden_on_start(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson("/vps/{$this->vpsId}/start");

        $response->assertStatus(403);
    }

    public function test_user_without_access_grant_gets_forbidden_on_stop(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson("/vps/{$this->vpsId}/stop");

        $response->assertStatus(403);
    }

    public function test_user_without_access_grant_gets_forbidden_on_reboot(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson("/vps/{$this->vpsId}/reboot");

        $response->assertStatus(403);
    }

    public function test_start_creates_audit_log_on_success(): void
    {
        $user = $this->userWithAccess();

        $this->actingAs($user)->postJson("/vps/{$this->vpsId}/start");

        $this->assertDatabaseHas('infra_audit_logs', [
            'action' => 'vps_start',
            'actor_id' => $user->id,
            'vps_id' => $this->vpsId,
            'resource_type' => 'vps',
            'outcome' => 'success',
        ]);
    }

    public function test_stop_creates_audit_log_on_success(): void
    {
        $user = $this->userWithAccess();

        $this->actingAs($user)->postJson("/vps/{$this->vpsId}/stop");

        $this->assertDatabaseHas('infra_audit_logs', [
            'action' => 'vps_stop',
            'actor_id' => $user->id,
            'vps_id' => $this->vpsId,
            'outcome' => 'success',
        ]);
    }

    public function test_reboot_creates_audit_log_on_success(): void
    {
        $user = $this->userWithAccess();

        $this->actingAs($user)->postJson("/vps/{$this->vpsId}/reboot");

        $this->assertDatabaseHas('infra_audit_logs', [
            'action' => 'vps_reboot',
            'actor_id' => $user->id,
            'vps_id' => $this->vpsId,
            'outcome' => 'success',
        ]);
    }

    public function test_start_returns_correlation_id_in_response(): void
    {
        $user = $this->userWithAccess();

        $response = $this->actingAs($user)->postJson("/vps/{$this->vpsId}/start");

        $correlationId = $response->json('data.correlation_id');
        $this->assertNotNull($correlationId);
        $this->assertNotEmpty($correlationId);
    }

    private function userWithAccess(): User
    {
        $user = User::factory()->create();

        VpsAccessGrant::factory()->forUser($user->id)->forVps($this->vpsId)->create();

        return $user;
    }
}
