<?php

namespace App\Modules\HostingerProxyModule\Tests\Feature;

use App\Modules\AuthModule\Models\User;
use App\Modules\VpsModule\Models\VpsAccessGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
        Cache::flush();
        config(['services.hostinger.base_url' => 'https://developers.hostinger.com']);

        Http::fake([
            'https://developers.hostinger.com/api/vps/v1/virtual-machines' => Http::response([['id' => 'vps-abc-123', 'name' => 'My VPS']], 200),
            "https://developers.hostinger.com/api/vps/v1/virtual-machines/{$this->vpsId}" => Http::response(['id' => 'vps-abc-123', 'name' => 'My VPS'], 200),
            'https://developers.hostinger.com/api/vps/v1/os-templates' => Http::response([['id' => 'ubuntu-22', 'name' => 'Ubuntu 22.04']], 200),
            'https://developers.hostinger.com/api/vps/v1/data-centers' => Http::response([['id' => 'eu-west', 'name' => 'EU West']], 200),
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

    public function test_user_with_access_can_get_vps_public_keys(): void
    {
        Http::fake([
            '*/api/vps/v1/public-keys' => Http::response([
                'data' => [
                    [
                        'uuid' => 'key-1',
                        'label' => 'Anderson laptop',
                        'finger_print' => 'SHA256:abc123',
                        'createdAt' => '2026-06-15T12:00:00Z',
                    ],
                ],
            ], 200),
        ]);

        $user = $this->userWithPermission('VPS.VirtualMachine.PublicKeys.read');
        VpsAccessGrant::factory()->forUser($user->id)->forVps($this->vpsId)->create();

        $response = $this->actingAs($user)->getJson("/api/v1/vps/{$this->vpsId}/public-keys");

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', 'key-1')
            ->assertJsonPath('data.0.name', 'Anderson laptop')
            ->assertJsonPath('data.0.fingerprint', 'SHA256:abc123');

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/api/vps/v1/public-keys'));
    }

    public function test_public_keys_uses_account_endpoint_for_registered_keys(): void
    {
        Http::fake([
            '*/api/vps/v1/public-keys' => Http::response([
                'data' => [
                    [
                        'id' => 'key-global-1',
                        'name' => 'Registered key',
                        'fingerprint' => 'SHA256:global123',
                    ],
                ],
            ], 200),
        ]);

        $user = $this->userWithPermission('VPS.VirtualMachine.PublicKeys.read');
        VpsAccessGrant::factory()->forUser($user->id)->forVps($this->vpsId)->create();

        $response = $this->actingAs($user)->getJson("/api/v1/vps/{$this->vpsId}/public-keys");

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', 'key-global-1')
            ->assertJsonPath('data.0.name', 'Registered key');

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/api/vps/v1/public-keys'));
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
            "*/virtual-machines/{$this->vpsId}/metrics*" => Http::response([
                'cpu_usage' => ['unit' => '%', 'usage' => ['100' => 10.5, '200' => 12.25]],
                'ram_usage' => ['unit' => 'bytes', 'usage' => ['100' => 1048576, '200' => 2097152]],
                'disk_space' => ['unit' => 'bytes', 'usage' => ['100' => 1073741824, '200' => 2147483648]],
                'incoming_traffic' => ['unit' => 'bytes', 'usage' => ['100' => 1000, '200' => 2000]],
                'outgoing_traffic' => ['unit' => 'bytes', 'usage' => ['100' => 3000, '200' => 4000]],
                'uptime' => ['unit' => 'seconds', 'usage' => ['100' => 50, '200' => 100]],
            ], 200),
        ]);

        $user = $this->userWithPermission('VPS.VirtualMachine.Manage.metrics');
        VpsAccessGrant::factory()->forUser($user->id)->forVps($this->vpsId)->create();

        $response = $this->actingAs($user)->getJson("/api/v1/vps/{$this->vpsId}/metrics");

        $response->assertStatus(200)
            ->assertJsonPath('data.cpu_usage', 12.25)
            ->assertJsonPath('data.memory_usage', 2)
            ->assertJsonPath('data.disk_usage', 2);

        Http::assertSent(fn ($request) => str_ends_with(parse_url($request->url(), PHP_URL_PATH), "/virtual-machines/{$this->vpsId}/metrics")
            && str_contains($request->url(), 'date_from=')
            && str_contains($request->url(), 'date_to='));
    }

    public function test_hostinger_forbidden_on_details_returns_forbidden_response(): void
    {
        $vpsId = 'vps-forbidden-details';

        Http::fake([
            "https://developers.hostinger.com/api/vps/v1/virtual-machines/{$vpsId}" => Http::response(['message' => 'Forbidden'], 403),
        ]);

        $user = $this->userWithPermission('VPS.VirtualMachine.Manage.details');
        VpsAccessGrant::factory()->forUser($user->id)->forVps($vpsId)->create();

        $this->actingAs($user)
            ->getJson("/api/v1/vps/{$vpsId}")
            ->assertStatus(403)
            ->assertJsonPath('message', 'Hostinger denied access to this resource.');
    }

    public function test_hostinger_forbidden_on_metrics_returns_forbidden_response(): void
    {
        $vpsId = 'vps-forbidden-metrics';

        Http::fake([
            "https://developers.hostinger.com/api/vps/v1/virtual-machines/{$vpsId}/metrics*" => Http::response(['message' => 'Forbidden'], 403),
        ]);

        $user = $this->userWithPermission('VPS.VirtualMachine.Manage.metrics');
        VpsAccessGrant::factory()->forUser($user->id)->forVps($vpsId)->create();

        $this->actingAs($user)
            ->getJson("/api/v1/vps/{$vpsId}/metrics")
            ->assertStatus(403)
            ->assertJsonPath('message', 'Hostinger denied access to this resource.');
    }

    public function test_hostinger_unauthorized_on_metrics_returns_token_error_response(): void
    {
        $vpsId = 'vps-unauthorized-metrics';

        Http::fake([
            "https://developers.hostinger.com/api/vps/v1/virtual-machines/{$vpsId}/metrics*" => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        $user = $this->userWithPermission('VPS.VirtualMachine.Manage.metrics');
        VpsAccessGrant::factory()->forUser($user->id)->forVps($vpsId)->create();

        $this->actingAs($user)
            ->getJson("/api/v1/vps/{$vpsId}/metrics")
            ->assertStatus(502)
            ->assertJsonPath('message', 'Hostinger rejected the configured API token.');
    }

    public function test_hostinger_forbidden_on_backups_returns_forbidden_response(): void
    {
        $vpsId = 'vps-forbidden-backups';

        Http::fake([
            "https://developers.hostinger.com/api/vps/v1/virtual-machines/{$vpsId}/backups" => Http::response(['message' => 'Forbidden'], 403),
        ]);

        $user = $this->userWithPermission('VPS.Backups.read');
        VpsAccessGrant::factory()->forUser($user->id)->forVps($vpsId)->create();

        $this->actingAs($user)
            ->getJson("/api/v1/vps/{$vpsId}/backups")
            ->assertStatus(403)
            ->assertJsonPath('message', 'Hostinger denied access to this resource.');
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
