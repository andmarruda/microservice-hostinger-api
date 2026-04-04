<?php

namespace App\Modules\SecurityResourceModule\Factories;

use App\Modules\AuthModule\Models\User;
use App\Modules\SecurityResourceModule\Models\SecurityPermission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\SecurityResourceModule\Models\SecurityPermission>
 */
class SecurityPermissionFactory extends Factory
{
    protected $model = SecurityPermission::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'vps_id' => Str::uuid()->toString(),
            'can_manage_firewall' => false,
            'can_manage_ssh_keys' => false,
            'can_manage_snapshots' => false,
            'granted_by' => User::factory(),
        ];
    }

    public function withFirewall(): static
    {
        return $this->state(fn (array $attributes) => [
            'can_manage_firewall' => true,
        ]);
    }

    public function withSshKeys(): static
    {
        return $this->state(fn (array $attributes) => [
            'can_manage_ssh_keys' => true,
        ]);
    }

    public function withSnapshots(): static
    {
        return $this->state(fn (array $attributes) => [
            'can_manage_snapshots' => true,
        ]);
    }

    public function withAllPermissions(): static
    {
        return $this->state(fn (array $attributes) => [
            'can_manage_firewall' => true,
            'can_manage_ssh_keys' => true,
            'can_manage_snapshots' => true,
        ]);
    }
}
