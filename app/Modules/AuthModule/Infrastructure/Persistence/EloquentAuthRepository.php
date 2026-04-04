<?php

namespace App\Modules\AuthModule\Infrastructure\Persistence;

use App\Modules\AuthModule\Models\User;
use App\Modules\AuthModule\Ports\Repositories\AuthRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class EloquentAuthRepository implements AuthRepositoryInterface
{
    public function findByCredentials(string $email, string $password): ?User
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }
}
