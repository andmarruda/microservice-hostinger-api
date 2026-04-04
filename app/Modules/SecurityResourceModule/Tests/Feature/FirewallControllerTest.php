<?php

namespace App\Modules\SecurityResourceModule\Tests\Feature;

use App\Modules\AuthModule\Models\User;
use App\Modules\SecurityResourceModule\Models\SecurityPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FirewallControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $vpsId = 'vps-abc-123';
    private string $ruleId = 'rule-456';

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            '*/rules*' => Http::response([], 200),
        ]);
    }

    public function test_authenticated_user_with_permission_can_add_firewall_rule(): void
    {
        $user = $this->userWithFirewallPermission();

        $response = $this->actingAs($user)->postJson("/vps/{$this->vpsId}/firewall/rules", [
            'protocol' => 'tcp',
            'port' => 80,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['vps_id', 'correlation_id']]);
    }

    public function test_authenticated_user_with_permission_can_remove_firewall_rule(): void
    {
        $user = $this->userWithFirewallPermission();

        $response = $this->actingAs($user)->deleteJson("/vps/{$this->vpsId}/firewall/rules/{$this->ruleId}", [
            'confirm_destructive' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['vps_id', 'correlation_id']]);
    }

    public function test_unauthenticated_user_cannot_add_firewall_rule(): void
    {
        $response = $this->postJson("/vps/{$this->vpsId}/firewall/rules", [
            'protocol' => 'tcp',
            'port' => 80,
        ]);

        $response->assertStatus(401);
    }

    public function test_user_without_firewall_permission_gets_forbidden(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson("/vps/{$this->vpsId}/firewall/rules", [
            'protocol' => 'tcp',
            'port' => 80,
        ]);

        $response->assertStatus(403);
    }

    public function test_add_rule_fails_validation_without_required_fields(): void
    {
        $user = $this->userWithFirewallPermission();

        $response = $this->actingAs($user)->postJson("/vps/{$this->vpsId}/firewall/rules", []);

        $response->assertStatus(422);
    }

    public function test_remove_rule_requires_confirm_destructive(): void
    {
        $user = $this->userWithFirewallPermission();

        $response = $this->actingAs($user)->deleteJson("/vps/{$this->vpsId}/firewall/rules/{$this->ruleId}", []);

        $response->assertStatus(422);
    }

    public function test_add_rule_creates_audit_log(): void
    {
        $user = $this->userWithFirewallPermission();

        $this->actingAs($user)->postJson("/vps/{$this->vpsId}/firewall/rules", [
            'protocol' => 'tcp',
            'port' => 80,
        ]);

        $this->assertDatabaseHas('infra_audit_logs', [
            'action' => 'firewall_rule_add',
            'actor_id' => $user->id,
            'vps_id' => $this->vpsId,
            'resource_type' => 'firewall',
            'outcome' => 'success',
        ]);
    }

    private function userWithFirewallPermission(): User
    {
        $user = User::factory()->create();

        SecurityPermission::factory()->withFirewall()->create([
            'user_id' => $user->id,
            'vps_id' => $this->vpsId,
            'granted_by' => $user->id,
        ]);

        return $user;
    }
}
