<?php

namespace App\Modules\HostingerProxyModule;

use App\Modules\HostingerProxyModule\Infrastructure\Services\HttpHostingerProxyClient;
use App\Modules\HostingerProxyModule\Ports\Services\HostingerProxyClientInterface;
use Illuminate\Support\ServiceProvider;

class HostingerProxyModuleServiceProvider extends ServiceProvider
{
    public array $bindings = [
        HostingerProxyClientInterface::class => HttpHostingerProxyClient::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/Http/Routes/api.php');
    }
}
