<?php

namespace App\Modules\PolicyModule;

use App\Modules\PolicyModule\Infrastructure\Services\DatabasePolicyEnforcer;
use App\Modules\PolicyModule\Ports\Services\PolicyEnforcerInterface;
use Illuminate\Support\ServiceProvider;

class PolicyModuleServiceProvider extends ServiceProvider
{
    public array $bindings = [
        PolicyEnforcerInterface::class => DatabasePolicyEnforcer::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/Http/Routes/api.php');
    }
}
