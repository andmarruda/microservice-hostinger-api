<?php

namespace App\Modules\AuthModule;

use App\Modules\AuthModule\Infrastructure\Persistence\EloquentInvitationRepository;
use App\Modules\AuthModule\Infrastructure\Persistence\EloquentUserRepository;
use App\Modules\AuthModule\Ports\Repositories\InvitationRepositoryInterface;
use App\Modules\AuthModule\Ports\Repositories\UserRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AuthModuleServiceProvider extends ServiceProvider
{
    public array $bindings = [
        InvitationRepositoryInterface::class => EloquentInvitationRepository::class,
        UserRepositoryInterface::class => EloquentUserRepository::class,
    ];

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
