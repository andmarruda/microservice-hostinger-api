<?php

namespace App\Modules\PolicyModule\Tests\Feature;

use App\Modules\AuthModule\Models\User;
use App\Modules\PolicyModule\Factories\EnforcementPolicyFactory;
use App\Modules\PolicyModule\Models\EnforcementPolicy;
use App\Modules\PolicyModule\PolicyActions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PolicyControllerTest extends TestCase
{
    use RefreshDatabase;

    private function rootUser(): User
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'root', 'guard_name' => 'web']);
        $user->assignRole($role);
        return $user;
    }

    // ── GET /api/v1/policies ──────────────────────────────────────────────────

    public function test_index_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/policies');
        $response->assertStatus(401);
    }

    public function test_index_returns_403_for_non_root(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/policies');
        $response->assertStatus(403);
    }

    public function test_index_returns_policies_for_root(): void
    {
        $root = $this->rootUser();
        EnforcementPolicyFactory::new()->forAction(PolicyActions::VPS_START)->create(['created_by' => $root->id]);

        Sanctum::actingAs($root);
        $response = $this->getJson('/api/v1/policies');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => [['id', 'action', 'scope_type', 'effect', 'creator']]]);
    }

    // ── POST /api/v1/policies ─────────────────────────────────────────────────

    public function test_store_returns_401_when_unauthenticated(): void
    {
        $response = $this->postJson('/api/v1/policies', []);
        $response->assertStatus(401);
    }

    public function test_store_returns_403_for_non_root(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/v1/policies', [
            'action'     => PolicyActions::VPS_START,
            'scope_type' => 'global',
        ]);

        $response->assertStatus(403);
    }

    public function test_store_creates_policy_for_root(): void
    {
        $root = $this->rootUser();
        Sanctum::actingAs($root);

        $response = $this->postJson('/api/v1/policies', [
            'action'     => PolicyActions::VPS_START,
            'scope_type' => 'global',
            'reason'     => 'Maintenance window.',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['data' => ['id']]);
        $this->assertDatabaseHas('enforcement_policies', ['action' => PolicyActions::VPS_START]);
    }

    public function test_store_returns_422_for_invalid_action(): void
    {
        $root = $this->rootUser();
        Sanctum::actingAs($root);

        $response = $this->postJson('/api/v1/policies', [
            'action'     => 'vps.explode',
            'scope_type' => 'global',
        ]);

        $response->assertStatus(422);
    }

    public function test_store_returns_422_for_missing_action(): void
    {
        $root = $this->rootUser();
        Sanctum::actingAs($root);

        $response = $this->postJson('/api/v1/policies', [
            'scope_type' => 'global',
        ]);

        $response->assertStatus(422);
    }

    // ── DELETE /api/v1/policies/{id} ──────────────────────────────────────────

    public function test_destroy_returns_401_when_unauthenticated(): void
    {
        $response = $this->deleteJson('/api/v1/policies/1');
        $response->assertStatus(401);
    }

    public function test_destroy_returns_403_for_non_root(): void
    {
        $root   = $this->rootUser();
        $policy = EnforcementPolicyFactory::new()->forAction(PolicyActions::VPS_STOP)->create(['created_by' => $root->id]);

        Sanctum::actingAs(User::factory()->create());

        $response = $this->deleteJson("/api/v1/policies/{$policy->id}");
        $response->assertStatus(403);
    }

    public function test_destroy_deletes_policy_for_root(): void
    {
        $root   = $this->rootUser();
        $policy = EnforcementPolicyFactory::new()->forAction(PolicyActions::VPS_STOP)->create(['created_by' => $root->id]);

        Sanctum::actingAs($root);

        $response = $this->deleteJson("/api/v1/policies/{$policy->id}");

        $response->assertStatus(200);
        $response->assertJson(['data' => ['deleted' => true]]);
        $this->assertDatabaseMissing('enforcement_policies', ['id' => $policy->id]);
    }

    public function test_destroy_returns_404_for_non_existent_policy(): void
    {
        $root = $this->rootUser();
        Sanctum::actingAs($root);

        $response = $this->deleteJson('/api/v1/policies/9999');
        $response->assertStatus(404);
    }
}
