<?php

namespace App\Modules\VpsModule\Ports\Repositories;

interface VpsRepositoryInterface
{
    public function userHasAccess(int $userId, string $vpsId): bool;

    public function findById(string $vpsId): ?object;

    public function findAllForUser(int $userId): array;
}
