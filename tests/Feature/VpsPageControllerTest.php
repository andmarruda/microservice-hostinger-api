<?php

namespace Tests\Feature;

use App\Modules\AuthModule\Models\User;
use App\Modules\SecurityResourceModule\Models\SecurityPermission;
use App\Modules\VpsModule\Models\VpsAccessGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VpsPageControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $vpsId = 'vps-web-1';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config(['services.hostinger.base_url' => 'https://developers.hostinger.com']);
    }

    public function test_web_start_stop_and_reboot_call_hostinger_endpoints(): void
    {
        Http::fake([
            '*/start' => Http::response([], 200),
            '*/stop' => Http::response([], 200),
            '*/restart' => Http::response([], 200),
        ]);

        $user = $this->userWithAccess();

        $this->actingAs($user)->post("/vps/{$this->vpsId}/start")->assertRedirect();
        $this->actingAs($user)->post("/vps/{$this->vpsId}/stop")->assertRedirect();
        $this->actingAs($user)->post("/vps/{$this->vpsId}/reboot")->assertRedirect();

        Http::assertSent(fn ($request) => str_ends_with($request->url(), "/virtual-machines/{$this->vpsId}/start"));
        Http::assertSent(fn ($request) => str_ends_with($request->url(), "/virtual-machines/{$this->vpsId}/stop"));
        Http::assertSent(fn ($request) => str_ends_with($request->url(), "/virtual-machines/{$this->vpsId}/restart"));
    }

    public function test_user_can_save_local_vps_display_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put("/vps/{$this->vpsId}/name", ['display_name' => 'Anderson dev VPS'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('vps_profiles', [
            'vps_id' => $this->vpsId,
            'display_name' => 'Anderson dev VPS',
            'updated_by' => $user->id,
        ]);
    }

    public function test_user_with_access_can_change_vps_password(): void
    {
        Http::fake(['*/root-password' => Http::response([], 200)]);

        $user = $this->userWithAccess();

        $this->actingAs($user)
            ->put("/vps/{$this->vpsId}/password", [
                'password' => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Http::assertSent(fn ($request) => $request->method() === 'PUT'
            && str_ends_with($request->url(), "/virtual-machines/{$this->vpsId}/root-password")
            && $request['password'] === 'NewPassword1!');
    }

    public function test_user_with_ssh_permission_can_add_and_remove_ssh_keys(): void
    {
        Http::fake([
            '*/public-keys' => Http::response([], 200),
            '*/public-keys/key-1' => Http::response([], 200),
        ]);

        $user = $this->userWithAccess();
        SecurityPermission::create([
            'user_id' => $user->id,
            'vps_id' => $this->vpsId,
            'can_manage_ssh_keys' => true,
            'granted_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post("/vps/{$this->vpsId}/ssh-keys", [
                'key_name' => 'anderson-laptop',
                'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAITestKey',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($user)
            ->post("/vps/{$this->vpsId}/ssh-keys/key-1/remove")
            ->assertRedirect()
            ->assertSessionHas('success');

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && str_ends_with($request->url(), '/api/vps/v1/public-keys')
            && $request['name'] === 'anderson-laptop');

        Http::assertSent(fn ($request) => $request->method() === 'DELETE'
            && str_ends_with($request->url(), '/api/vps/v1/public-keys/key-1'));
    }

    public function test_vps_show_page_renders_ssh_keys_from_hostinger_api(): void
    {
        Http::fake([
            "*/virtual-machines/{$this->vpsId}" => Http::response([
                'id' => $this->vpsId,
                'hostname' => 'srv674296.hstgr.cloud',
                'state' => 'running',
                'ipv4' => [['address' => '147.93.15.156']],
                'plan' => 'KVM 2',
                'template' => ['name' => 'Debian 12'],
            ], 200),
            "*/virtual-machines/{$this->vpsId}/metrics*" => Http::response([], 200),
            "*/virtual-machines/{$this->vpsId}/actions" => Http::response(['data' => []], 200),
            "*/virtual-machines/{$this->vpsId}/backups" => Http::response(['data' => []], 200),
            '*/api/vps/v1/public-keys' => Http::response([
                'data' => [
                    [
                        'id' => 'key-abc-789',
                        'name' => 'anderson-laptop',
                        'fingerprint' => 'SHA256:xyzABC123def456',
                        'created_at' => '2026-06-10T00:00:00Z',
                    ],
                ],
            ], 200),
        ]);

        $user = $this->userWithAccess();

        $response = $this->actingAs($user)->get("/vps/{$this->vpsId}");

        $response->assertInertia(fn ($page) => $page
            ->component('Vps/Show')
            ->where('sshKeys.0.id', 'key-abc-789')
            ->where('sshKeys.0.name', 'anderson-laptop')
            ->where('sshKeys.0.fingerprint', 'SHA256:xyzABC123def456')
        );

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/api/vps/v1/public-keys'));
    }

    public function test_vps_show_page_shows_empty_ssh_keys_when_api_returns_none(): void
    {
        Http::fake([
            "*/virtual-machines/{$this->vpsId}" => Http::response([
                'id' => $this->vpsId,
                'hostname' => 'srv674296.hstgr.cloud',
                'state' => 'running',
            ], 200),
            "*/virtual-machines/{$this->vpsId}/metrics*" => Http::response([], 200),
            "*/virtual-machines/{$this->vpsId}/actions" => Http::response(['data' => []], 200),
            "*/virtual-machines/{$this->vpsId}/backups" => Http::response(['data' => []], 200),
            '*/api/vps/v1/public-keys' => Http::response(['data' => []], 200),
        ]);

        $user = $this->userWithAccess();

        $response = $this->actingAs($user)->get("/vps/{$this->vpsId}");

        $response->assertInertia(fn ($page) => $page
            ->component('Vps/Show')
            ->where('sshKeys', [])
        );
    }

    public function test_vps_show_page_passes_ssh_keys_error_when_hostinger_returns_403(): void
    {
        Http::fake([
            "*/virtual-machines/{$this->vpsId}" => Http::response([
                'id' => $this->vpsId,
                'hostname' => 'srv674296.hstgr.cloud',
                'state' => 'running',
            ], 200),
            "*/virtual-machines/{$this->vpsId}/metrics*" => Http::response([], 200),
            "*/virtual-machines/{$this->vpsId}/actions" => Http::response(['data' => []], 200),
            "*/virtual-machines/{$this->vpsId}/backups" => Http::response(['data' => []], 200),
            '*/api/vps/v1/public-keys' => Http::response(['message' => 'Forbidden'], 403),
        ]);

        $user = $this->userWithAccess();

        $response = $this->actingAs($user)->get("/vps/{$this->vpsId}");

        $response->assertInertia(fn ($page) => $page
            ->component('Vps/Show')
            ->where('sshKeys', [])
            ->whereNot('resourceErrors.sshKeys', null)
        );
    }

    private function userWithAccess(): User
    {
        $user = User::factory()->create();

        VpsAccessGrant::factory()->forUser($user->id)->forVps($this->vpsId)->create();

        return $user;
    }
}
