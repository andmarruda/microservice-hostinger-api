<?php

namespace App\Modules\HostingerProxyModule\Tests\Feature;

use App\Modules\AuthModule\Models\User;
use App\Modules\VpsModule\Models\VpsAccessGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class VpsReadControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $vpsId = 'vps-abc-123';

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            '*/virtual-machines' => Http::response([['id' => 'vps-abc-123', 'name' => 'My VPS']], 200),
            '*/virtual-machines/*' => Http::response(['id' => 'vps-abc-123', 'name' => 'My VPS'], 200),
            '*/os-templates' => Http::response([['id' => 'ubuntu-22', 'name' => 'Ubuntu 22.04']], 200),
            '*/data-centers' => Http::response([['id' => 'eu-west', 'name' => 'EU West']], 200),
        ]);
    }

    public function test_authenticated_user_with_permission_can_list_vps(): void
    {
        $user = $this->userWithPermission('VPS.VirtualMachine.Manage.read');
        $this->givePermission($user, 'Manage.Permissions.VPS.all');

        $response = $this->actingAs($user)->getJson('/api/v1/vps');

        $response->assertStatus(200)->assertJsonStructure(['data']);
    }

    public function test_scoped_user_sees_only_granted_vps(): void
    {
        $user = $this->userWithPermission('VPS.VirtualMachine.Manage.read');
        VpsAccessGrant::factory()->forUser($user->id)->forVps($this->vpsId)->create();

        $response = $this->actingAs($user)->getJson('/api/v1/vps');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->vpsId, $data[0]['id']);
    }

    public function test_unauthenticated_user_cannot_list_vps(): void
    {
        $response = $this->getJson('/api/v1/vps');

        $response->assertStatus(401);
    }

    public function test_user_without_permission_gets_forbidden_on_list(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/vps');

        $response->assertStatus(403);
    }

    public function test_user_with_access_grant_can_get_vps_details(): void
    {
        $user = $this->userWithPermission('VPS.VirtualMachine.Manage.details');
        VpsAccessGrant::factory()->forUser($user->id)->forVps($this->vpsId)->create();

        $response = $this->actingAs($user)->getJson("/api/v1/vps/{$this->vpsId}");

        $response->assertStatus(200)->assertJsonStructure(['data']);
    }

    public function test_user_without_access_grant_cannot_get_vps_details(): void
    {
        $user = $this->userWithPermission('VPS.VirtualMachine.Manage.details');

        $response = $this->actingAs($user)->getJson("/api/v1/vps/{$this->vpsId}");

        $response->assertStatus(403);
    }

    public function test_user_with_permission_can_get_os_templates(): void
    {
        $user = $this->userWithPermission('VPS.OSTemplates.read');

        $response = $this->actingAs($user)->getJson('/api/v1/vps/os-templates');

        $response->assertStatus(200)->assertJsonStructure(['data']);
    }

    public function test_user_with_permission_can_get_datacenters(): void
    {
        $user = $this->userWithPermission('VPS.DataCenters.list');

        $response = $this->actingAs($user)->getJson('/api/v1/vps/data-centers');

        $response->assertStatus(200)->assertJsonStructure(['data']);
    }

    public function test_user_with_permission_and_access_can_get_metrics(): void
    {
        Http::fake([
            "*/virtual-machines/{$this->vpsId}/metrics" => Http::response(['cpu' => 10], 200),
        ]);

        $user = $this->userWithPermission('VPS.VirtualMachine.Manage.metrics');
        VpsAccessGrant::factory()->forUser($user->id)->forVps($this->vpsId)->create();

        $response = $this->actingAs($user)->getJson("/api/v1/vps/{$this->vpsId}/metrics");

        $response->assertStatus(200)->assertJsonStructure(['data']);
    }

    private function userWithPermission(string $permission): User
    {
        $user = User::factory()->create();
        $this->givePermission($user, $permission);

        return $user;
    }

    private function givePermission(User $user, string $permission): void
    {
        $perm = Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        $user->givePermissionTo($perm);
    }
}
