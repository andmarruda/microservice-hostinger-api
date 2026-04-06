<?php

namespace App\Modules\HostingerProxyModule\Tests\Feature;

use App\Modules\AuthModule\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DomainsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            '*/domains/v1/availability*' => Http::response(['available' => true], 200),
            '*/domains/v1/forwarding' => Http::response([], 200),
            '*/domains/v1/portfolio' => Http::response([], 200),
            '*/domains/v1/whois' => Http::response([], 200),
        ]);
    }

    public function test_authenticated_user_with_permission_can_check_domain_availability(): void
    {
        $user = $this->userWithPermission('Domains.Availability.validate');

        $response = $this->actingAs($user)->getJson('/api/v1/domains/availability?domain=example.com');

        $response->assertStatus(200)->assertJsonStructure(['data']);
    }

    public function test_availability_requires_domain_parameter(): void
    {
        $user = $this->userWithPermission('Domains.Availability.validate');

        $response = $this->actingAs($user)->getJson('/api/v1/domains/availability');

        $response->assertStatus(422);
    }

    public function test_unauthenticated_user_cannot_check_availability(): void
    {
        $response = $this->getJson('/api/v1/domains/availability?domain=example.com');

        $response->assertStatus(401);
    }

    public function test_user_without_permission_gets_forbidden_on_portfolio(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/domains/portfolio');

        $response->assertStatus(403);
    }

    public function test_user_with_portfolio_details_permission_can_access(): void
    {
        $user = $this->userWithPermission('Domains.Portfolio.Details');

        $response = $this->actingAs($user)->getJson('/api/v1/domains/portfolio');

        $response->assertStatus(200)->assertJsonStructure(['data']);
    }

    public function test_user_with_whois_read_permission_can_access(): void
    {
        $user = $this->userWithPermission('Domains.Whois.read');

        $response = $this->actingAs($user)->getJson('/api/v1/domains/whois');

        $response->assertStatus(200)->assertJsonStructure(['data']);
    }

    private function userWithPermission(string $permission): User
    {
        $user = User::factory()->create();
        $perm = Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        $user->givePermissionTo($perm);

        return $user;
    }
}
