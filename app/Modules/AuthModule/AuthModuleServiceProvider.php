<?php

namespace App\Modules\AuthModule;

use Illuminate\Support\ServiceProvider;

class AuthModuleServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/Http/Routes/web.php');
        $this->loadMigrationsFrom(__DIR__ . '/Migrations');
    }
}
