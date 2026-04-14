<?php

namespace App\Modules\ObservabilityModule;

use Illuminate\Support\ServiceProvider;

class ObservabilityModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/Http/Routes/api.php');
    }
}
