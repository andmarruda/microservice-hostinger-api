<?php

namespace App\Modules\PolicyModule\Factories;

use App\Modules\AuthModule\Models\User;
use App\Modules\PolicyModule\Models\EnforcementPolicy;
use App\Modules\PolicyModule\PolicyActions;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnforcementPolicyFactory extends Factory
{
    protected $model = EnforcementPolicy::class;

    public function definition(): array
    {
        return [
            'action'      => $this->faker->randomElement(PolicyActions::ALL),
            'scope_type'  => 'global',
            'scope_id'    => null,
            'effect'      => 'deny',
            'reason'      => 'Denied by policy.',
            'active_from' => null,
            'active_until' => null,
            'created_by'  => User::factory(),
        ];
    }

    public function forAction(string $action): static
    {
        return $this->state(['action' => $action]);
    }

    public function forVps(string $vpsId): static
    {
        return $this->state(['scope_type' => 'vps', 'scope_id' => $vpsId]);
    }

    public function forUser(int $userId): static
    {
        return $this->state(['scope_type' => 'user', 'scope_id' => (string) $userId]);
    }

    public function forRole(string $role): static
    {
        return $this->state(['scope_type' => 'role', 'scope_id' => $role]);
    }

    public function withWindow(\DateTimeInterface $from, \DateTimeInterface $until): static
    {
        return $this->state(['active_from' => $from, 'active_until' => $until]);
    }
}
