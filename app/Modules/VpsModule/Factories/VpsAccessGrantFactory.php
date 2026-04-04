<?php

namespace App\Modules\VpsModule\Factories;

use App\Modules\AuthModule\Models\User;
use App\Modules\VpsModule\Models\VpsAccessGrant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\VpsModule\Models\VpsAccessGrant>
 */
class VpsAccessGrantFactory extends Factory
{
    protected $model = VpsAccessGrant::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'vps_id' => Str::uuid()->toString(),
            'granted_by' => User::factory(),
            'granted_at' => now(),
        ];
    }

    public function forUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }

    public function forVps(string $vpsId): static
    {
        return $this->state(fn (array $attributes) => [
            'vps_id' => $vpsId,
        ]);
    }
}
