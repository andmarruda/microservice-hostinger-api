<?php

namespace App\Infrastructure\Audit;

use App\Infrastructure\Audit\Ports\InfraAuditLoggerInterface;
use Illuminate\Support\ServiceProvider;

class InfraAuditServiceProvider extends ServiceProvider
{
    public array $bindings = [
        InfraAuditLoggerInterface::class => EloquentInfraAuditLogger::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Migrations');
    }
}
