<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Modules\AuthModule\AuthModuleServiceProvider::class,
    App\Modules\PermissionModule\PermissionModuleServiceProvider::class,
    App\Infrastructure\Audit\InfraAuditServiceProvider::class,
    App\Modules\VpsModule\VpsModuleServiceProvider::class,
    App\Modules\SecurityResourceModule\SecurityResourceModuleServiceProvider::class,
    App\Modules\HostingerProxyModule\HostingerProxyModuleServiceProvider::class,
    App\Modules\PolicyModule\PolicyModuleServiceProvider::class,
    App\Modules\DriftModule\DriftModuleServiceProvider::class,
    App\Modules\ObservabilityModule\ObservabilityModuleServiceProvider::class,
    App\Modules\GovernanceModule\GovernanceModuleServiceProvider::class,
    App\Modules\OpsModule\OpsModuleServiceProvider::class,
    App\Modules\FrontendModule\FrontendModuleServiceProvider::class,
];
