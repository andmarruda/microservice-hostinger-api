<?php

namespace Tests\Feature;

use App\Modules\AuthModule\Models\User;
use App\Modules\SecurityResourceModule\Models\SecurityPermission;
use App\Modules\VpsModule\Models\VpsAccessGrant;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    // ── Admin: create user ────────────────────────────────────────────────────

    public function test_admin_can_create_user_directly(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/users', [
            'name'                  => 'New Engineer',
            'email'                 => 'eng@example.com',
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role'                  => 'user',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['email' => 'eng@example.com']);

        $user = User::where('email', 'eng@example.com')->first();
        $this->assertTrue($user->hasRole('user'));
    }

    public function test_regular_user_cannot_create_user(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->regularUser()->create();

        $response = $this->actingAs($user)->post('/users', [
            'name'                  => 'New Engineer',
            'email'                 => 'eng@example.com',
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role'                  => 'user',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'eng@example.com']);
    }

    public function test_unauthenticated_cannot_create_user(): void
    {
        $response = $this->post('/users', [
            'name'  => 'Hacker',
            'email' => 'hack@example.com',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_admin_create_user_sends_welcome_email(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/users', [
            'name'                  => 'Welcome User',
            'email'                 => 'welcome@example.com',
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role'                  => 'user',
        ]);

        Mail::assertSent(\App\Modules\AuthModule\Infrastructure\Mail\WelcomeMail::class, function ($mail) {
            return $mail->hasTo('welcome@example.com');
        });
    }

    public function test_cannot_create_user_with_duplicate_email(): void
    {
        Mail::fake();
        $admin  = User::factory()->admin()->create();
        $existing = User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->actingAs($admin)->post('/users', [
            'name'                  => 'Duplicate',
            'email'                 => 'existing@example.com',
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role'                  => 'user',
        ]);

        $response->assertSessionHasErrors('email');
    }

    // ── Admin: invite user ────────────────────────────────────────────────────

    public function test_admin_can_invite_user(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/users/invite', [
            'email' => 'invited@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('invitations', ['email' => 'invited@example.com']);
    }

    public function test_regular_user_cannot_invite(): void
    {
        $user = User::factory()->regularUser()->create();

        $response = $this->actingAs($user)->post('/users/invite', [
            'email' => 'invited@example.com',
        ]);

        $response->assertForbidden();
    }

    // ── Admin: list and show users ────────────────────────────────────────────

    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/users');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Users/Index'));
    }

    public function test_regular_user_cannot_list_users(): void
    {
        $user = User::factory()->regularUser()->create();

        $response = $this->actingAs($user)->get('/users');

        $response->assertForbidden();
    }

    public function test_admin_can_view_user_detail(): void
    {
        $admin  = User::factory()->admin()->create();
        $target = User::factory()->regularUser()->create();

        $response = $this->actingAs($admin)->get("/users/{$target->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Users/Show'));
    }

    // ── Admin: delete user ────────────────────────────────────────────────────

    public function test_admin_can_delete_regular_user(): void
    {
        $admin  = User::factory()->admin()->create();
        $target = User::factory()->regularUser()->create();

        $response = $this->actingAs($admin)->delete("/users/{$target->id}");

        $response->assertRedirect('/users');
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_admin_cannot_delete_last_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->delete("/users/{$admin->id}");

        $response->assertSessionHasErrors('user');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_delete_user_also_removes_vps_grants(): void
    {
        $admin  = User::factory()->admin()->create();
        $target = User::factory()->regularUser()->create();

        VpsAccessGrant::create([
            'user_id'    => $target->id,
            'vps_id'     => 'vps-123',
            'granted_by' => $admin->id,
            'granted_at' => now(),
        ]);

        $this->actingAs($admin)->delete("/users/{$target->id}");

        $this->assertDatabaseMissing('vps_access_grants', ['user_id' => $target->id]);
    }
}
