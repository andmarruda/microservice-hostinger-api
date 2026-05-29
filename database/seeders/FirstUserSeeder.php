<?php

namespace Database\Seeders;

use App\Modules\AuthModule\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

class FirstUserSeeder extends Seeder
{
    public function run(): void
    {
        $email    = config('seeder.first_user_email');
        $password = config('seeder.first_user_password');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'              => 'Bootstrap Admin',
                'password'          => Hash::make($password),
                'email_verified_at' => null,
                'is_manager'        => true,
            ]
        );

        $permission = Permission::firstOrCreate([
            'name'       => 'Manage.Invite.user',
            'guard_name' => 'web',
        ]);

        $user->givePermissionTo($permission);
    }
}
