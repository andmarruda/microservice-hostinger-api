<?php

namespace App\Modules\VpsModule;

use App\Modules\VpsModule\Infrastructure\Persistence\EloquentVpsRepository;
use App\Modules\VpsModule\Infrastructure\Services\HttpHostingerApiClient;
use App\Modules\VpsModule\Ports\Repositories\VpsRepositoryInterface;
use App\Modules\VpsModule\Ports\Services\HostingerApiClientInterface;
use Illuminate\Support\ServiceProvider;

class VpsModuleServiceProvider extends ServiceProvider
{
    public array $bindings = [
        VpsRepositoryInterface::class => EloquentVpsRepository::class,
        HostingerApiClientInterface::class => HttpHostingerApiClient::class,
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
