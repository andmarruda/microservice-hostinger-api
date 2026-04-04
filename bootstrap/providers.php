<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Modules\AuthModule\AuthModuleServiceProvider::class,
    App\Infrastructure\Audit\InfraAuditServiceProvider::class,
    App\Modules\VpsModule\VpsModuleServiceProvider::class,
];
