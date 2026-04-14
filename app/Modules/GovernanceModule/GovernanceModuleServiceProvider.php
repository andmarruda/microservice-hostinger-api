<?php

namespace App\Modules\GovernanceModule;

use Illuminate\Support\ServiceProvider;

class GovernanceModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/Http/Routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/Migrations');
    }
}
