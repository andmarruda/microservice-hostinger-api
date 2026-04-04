<?php

namespace App\Modules\AuthModule\Ports\Repositories;

use App\Modules\AuthModule\Models\User;

interface AuthRepositoryInterface
{
    public function findByCredentials(string $email, string $password): ?User;
}
