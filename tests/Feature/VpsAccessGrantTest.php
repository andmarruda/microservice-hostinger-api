<?php

namespace Tests\Feature;

use App\Modules\AuthModule\Models\User;
use App\Modules\SecurityResourceModule\Models\SecurityPermission;
use App\Modules\VpsModule\Models\VpsAccessGrant;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VpsAccessGrantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_grant_vps_access_to_user(): void
    {
        $admin  = User::factory()->admin()->create();
        $target = User::factory()->regularUser()->create();

        $response = $this->actingAs($admin)->post("/users/{$target->id}/vps-access", [
            'vps_id'               => 'vps-abc-123',
            'can_manage_firewall'  => true,
            'can_manage_ssh_keys'  => false,
            'can_manage_snapshots' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('vps_access_grants', [
            'user_id' => $target->id,
            'vps_id'  => 'vps-abc-123',
        ]);

        $this->assertDatabaseHas('security_permissions', [
            'user_id'             => $target->id,
            'vps_id'              => 'vps-abc-123',
            'can_manage_firewall' => true,
        ]);
    }

    public function test_admin_can_revoke_vps_access(): void
    {
        $admin  = User::factory()->admin()->create();
        $target = User::factory()->regularUser()->create();

        VpsAccessGrant::create([
            'user_id'    => $target->id,
            'vps_id'     => 'vps-abc-123',
            'granted_by' => $admin->id,
            'granted_at' => now(),
        ]);
        SecurityPermission::create([
            'user_id'              => $target->id,
            'vps_id'               => 'vps-abc-123',
            'granted_by'           => $admin->id,
            'can_manage_firewall'  => true,
            'can_manage_ssh_keys'  => false,
            'can_manage_snapshots' => false,
        ]);

        $response = $this->actingAs($admin)->delete("/users/{$target->id}/vps-access/vps-abc-123");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('vps_access_grants', ['user_id' => $target->id, 'vps_id' => 'vps-abc-123']);
        $this->assertDatabaseMissing('security_permissions', ['user_id' => $target->id, 'vps_id' => 'vps-abc-123']);
    }

    public function test_admin_can_update_vps_permissions(): void
    {
        $admin  = User::factory()->admin()->create();
        $target = User::factory()->regularUser()->create();

        VpsAccessGrant::create([
            'user_id'    => $target->id,
            'vps_id'     => 'vps-abc-123',
            'granted_by' => $admin->id,
            'granted_at' => now(),
        ]);
        SecurityPermission::create([
            'user_id'              => $target->id,
            'vps_id'               => 'vps-abc-123',
            'granted_by'           => $admin->id,
            'can_manage_firewall'  => false,
            'can_manage_ssh_keys'  => false,
            'can_manage_snapshots' => false,
        ]);

        $response = $this->actingAs($admin)->put("/users/{$target->id}/vps-access/vps-abc-123/permissions", [
            'can_manage_firewall'  => true,
            'can_manage_ssh_keys'  => true,
            'can_manage_snapshots' => false,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('security_permissions', [
            'user_id'             => $target->id,
            'vps_id'              => 'vps-abc-123',
            'can_manage_firewall' => true,
            'can_manage_ssh_keys' => true,
        ]);
    }

    public function test_regular_user_cannot_grant_vps_access(): void
    {
        $user   = User::factory()->regularUser()->create();
        $target = User::factory()->regularUser()->create();

        $response = $this->actingAs($user)->post("/users/{$target->id}/vps-access", [
            'vps_id' => 'vps-abc-123',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('vps_access_grants', ['user_id' => $target->id]);
    }

    public function test_regular_user_cannot_revoke_vps_access(): void
    {
        $admin  = User::factory()->admin()->create();
        $user   = User::factory()->regularUser()->create();
        $target = User::factory()->regularUser()->create();

        VpsAccessGrant::create([
            'user_id'    => $target->id,
            'vps_id'     => 'vps-xyz',
            'granted_by' => $admin->id,
            'granted_at' => now(),
        ]);

        $response = $this->actingAs($user)->delete("/users/{$target->id}/vps-access/vps-xyz");

        $response->assertForbidden();
        $this->assertDatabaseHas('vps_access_grants', ['user_id' => $target->id, 'vps_id' => 'vps-xyz']);
    }

    public function test_granting_same_vps_twice_updates_existing(): void
    {
        $admin  = User::factory()->admin()->create();
        $target = User::factory()->regularUser()->create();

        $this->actingAs($admin)->post("/users/{$target->id}/vps-access", [
            'vps_id'              => 'vps-abc-123',
            'can_manage_firewall' => false,
        ]);

        $this->actingAs($admin)->post("/users/{$target->id}/vps-access", [
            'vps_id'              => 'vps-abc-123',
            'can_manage_firewall' => true,
        ]);

        $this->assertCount(1, VpsAccessGrant::where('user_id', $target->id)->where('vps_id', 'vps-abc-123')->get());
    }
}
