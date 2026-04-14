<?php

namespace App\Modules\OpsModule;

use Illuminate\Support\ServiceProvider;

class OpsModuleServiceProvider extends ServiceProvider
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
