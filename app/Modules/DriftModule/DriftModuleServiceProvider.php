<?php

namespace App\Modules\DriftModule;

use Illuminate\Support\ServiceProvider;

class DriftModuleServiceProvider extends ServiceProvider
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
