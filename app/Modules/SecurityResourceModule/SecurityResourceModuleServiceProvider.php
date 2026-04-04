<?php

namespace App\Modules\SecurityResourceModule;

use App\Modules\SecurityResourceModule\Infrastructure\Services\EloquentSecurityPermissionService;
use App\Modules\SecurityResourceModule\Infrastructure\Services\HttpHostingerSecurityApiClient;
use App\Modules\SecurityResourceModule\Ports\Services\HostingerSecurityApiClientInterface;
use App\Modules\SecurityResourceModule\Ports\Services\SecurityPermissionInterface;
use Illuminate\Support\ServiceProvider;

class SecurityResourceModuleServiceProvider extends ServiceProvider
{
    public array $bindings = [
        SecurityPermissionInterface::class => EloquentSecurityPermissionService::class,
        HostingerSecurityApiClientInterface::class => HttpHostingerSecurityApiClient::class,
    ];

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
