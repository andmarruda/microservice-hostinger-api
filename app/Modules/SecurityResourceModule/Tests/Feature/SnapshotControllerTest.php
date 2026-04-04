<?php

namespace App\Modules\SecurityResourceModule\Tests\Feature;

use App\Modules\AuthModule\Models\User;
use App\Modules\SecurityResourceModule\Models\SecurityPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SnapshotControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $vpsId = 'vps-abc-123';
    private string $snapshotId = 'snap-999';

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            '*/snapshots*' => Http::response([], 200),
        ]);
    }

    public function test_authenticated_user_with_permission_can_create_snapshot(): void
    {
        $user = $this->userWithSnapshotPermission();

        $response = $this->actingAs($user)->postJson("/api/v1/vps/{$this->vpsId}/snapshots", [
            'label' => 'pre-deploy-backup',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['vps_id', 'correlation_id']]);
    }

    public function test_authenticated_user_with_permission_can_delete_snapshot(): void
    {
        $user = $this->userWithSnapshotPermission();

        $response = $this->actingAs($user)->deleteJson("/api/v1/vps/{$this->vpsId}/snapshots/{$this->snapshotId}", [
            'confirm_destructive' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['vps_id', 'correlation_id']]);
    }

    public function test_unauthenticated_user_cannot_create_snapshot(): void
    {
        $response = $this->postJson("/api/v1/vps/{$this->vpsId}/snapshots", [
            'label' => 'pre-deploy-backup',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_without_snapshot_permission_gets_forbidden(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson("/api/v1/vps/{$this->vpsId}/snapshots", [
            'label' => 'pre-deploy-backup',
        ]);

        $response->assertStatus(403);
    }

    public function test_create_snapshot_requires_label(): void
    {
        $user = $this->userWithSnapshotPermission();

        $response = $this->actingAs($user)->postJson("/api/v1/vps/{$this->vpsId}/snapshots", []);

        $response->assertStatus(422);
    }

    public function test_delete_snapshot_requires_confirm_destructive(): void
    {
        $user = $this->userWithSnapshotPermission();

        $response = $this->actingAs($user)->deleteJson("/api/v1/vps/{$this->vpsId}/snapshots/{$this->snapshotId}", []);

        $response->assertStatus(422);
    }

    public function test_create_snapshot_creates_audit_log(): void
    {
        $user = $this->userWithSnapshotPermission();

        $this->actingAs($user)->postJson("/api/v1/vps/{$this->vpsId}/snapshots", [
            'label' => 'pre-deploy-backup',
        ]);

        $this->assertDatabaseHas('infra_audit_logs', [
            'action' => 'snapshot_create',
            'actor_id' => $user->id,
            'vps_id' => $this->vpsId,
            'resource_type' => 'snapshot',
            'outcome' => 'success',
        ]);
    }

    private function userWithSnapshotPermission(): User
    {
        $user = User::factory()->create();

        SecurityPermission::factory()->withSnapshots()->create([
            'user_id' => $user->id,
            'vps_id' => $this->vpsId,
            'granted_by' => $user->id,
        ]);

        return $user;
    }
}
