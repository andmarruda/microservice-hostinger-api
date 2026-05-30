<?php

namespace Database\Seeders;

use App\Modules\AuthModule\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
                'email_verified_at' => now(),
                'is_manager'        => true,
            ]
        );

        if (! $user->hasRole('admin')) {
            $user->assignRole('admin');
        }
    }
}
