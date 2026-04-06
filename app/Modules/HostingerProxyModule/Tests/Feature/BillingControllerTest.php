<?php

namespace App\Modules\HostingerProxyModule\Tests\Feature;

use App\Modules\AuthModule\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BillingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_with_permission_can_get_billing_catalog(): void
    {
        Http::fake(['*/billing/v1/catalog' => Http::response([['id' => 'plan-1', 'name' => 'Starter']], 200)]);

        $user = $this->userWithPermission('Billing.getCatalog');

        $response = $this->actingAs($user)->getJson('/api/v1/billing/catalog');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_unauthenticated_user_cannot_get_billing_catalog(): void
    {
        $response = $this->getJson('/api/v1/billing/catalog');

        $response->assertStatus(401);
    }

    public function test_user_without_permission_gets_forbidden(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/billing/catalog');

        $response->assertStatus(403);
    }

    public function test_hostinger_error_returns_502(): void
    {
        Http::fake(['*/billing/v1/catalog' => Http::response('Server Error', 500)]);

        $user = $this->userWithPermission('Billing.getCatalog');

        $response = $this->actingAs($user)->getJson('/api/v1/billing/catalog');

        $response->assertStatus(502);
    }

    private function userWithPermission(string $permission): User
    {
        $user = User::factory()->create();
        $perm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        $user->givePermissionTo($perm);

        return $user;
    }
}
