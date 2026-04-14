<?php

namespace App\Modules\FrontendModule;

use Illuminate\Support\ServiceProvider;

class FrontendModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/Http/Routes/web.php');
    }
}
