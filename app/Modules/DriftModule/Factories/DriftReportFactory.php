<?php

namespace App\Modules\DriftModule\Factories;

use App\Modules\DriftModule\Models\DriftReport;
use Illuminate\Database\Eloquent\Factories\Factory;

class DriftReportFactory extends Factory
{
    protected $model = DriftReport::class;

    public function definition(): array
    {
        return [
            'drift_type'  => $this->faker->randomElement(['orphan_grant', 'missing_grant', 'stale_permission']),
            'severity'    => $this->faker->randomElement(['low', 'medium', 'high']),
            'vps_id'      => 'vps-' . $this->faker->uuid(),
            'user_id'     => null,
            'details'     => ['description' => 'Drift detected.'],
            'status'      => 'open',
            'detected_at' => now(),
            'resolved_at' => null,
            'resolved_by' => null,
        ];
    }

    public function open(): static
    {
        return $this->state(['status' => 'open']);
    }

    public function resolved(): static
    {
        return $this->state(['status' => 'resolved', 'resolved_at' => now()]);
    }

    public function dismissed(): static
    {
        return $this->state(['status' => 'dismissed']);
    }

    public function highSeverity(): static
    {
        return $this->state(['severity' => 'high']);
    }

    public function forVps(string $vpsId): static
    {
        return $this->state(['vps_id' => $vpsId]);
    }
}
