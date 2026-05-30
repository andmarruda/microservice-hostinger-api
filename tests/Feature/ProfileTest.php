<?php

namespace Tests\Feature;

use App\Modules\AuthModule\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_authenticated_user_can_view_profile_page(): void
    {
        $user = User::factory()->regularUser()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Profile/Edit'));
    }

    public function test_unauthenticated_user_cannot_view_profile(): void
    {
        $response = $this->get('/profile');

        $response->assertRedirect('/login');
    }

    public function test_user_can_update_their_name(): void
    {
        $user = User::factory()->regularUser()->create(['name' => 'Old Name']);

        $response = $this->actingAs($user)->put('/profile', ['name' => 'New Name']);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name']);
    }

    public function test_name_update_requires_name_field(): void
    {
        $user = User::factory()->regularUser()->create();

        $response = $this->actingAs($user)->put('/profile', ['name' => '']);

        $response->assertSessionHasErrors('name');
    }

    public function test_user_can_change_their_password(): void
    {
        $user = User::factory()->regularUser()->create([
            'password' => Hash::make('OldPassword1!'),
        ]);

        $response = $this->actingAs($user)->put('/profile/password', [
            'current_password'      => 'OldPassword1!',
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword1!', $user->password));
    }

    public function test_password_change_fails_with_wrong_current_password(): void
    {
        $user = User::factory()->regularUser()->create([
            'password' => Hash::make('OldPassword1!'),
        ]);

        $response = $this->actingAs($user)->put('/profile/password', [
            'current_password'      => 'WrongPassword!',
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_password_change_requires_confirmation(): void
    {
        $user = User::factory()->regularUser()->create([
            'password' => Hash::make('OldPassword1!'),
        ]);

        $response = $this->actingAs($user)->put('/profile/password', [
            'current_password'      => 'OldPassword1!',
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'DifferentPassword!',
        ]);

        $response->assertSessionHasErrors('password');
    }
}
