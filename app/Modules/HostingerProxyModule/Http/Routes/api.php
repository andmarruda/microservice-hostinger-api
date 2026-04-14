<?php

use App\Modules\HostingerProxyModule\Http\Controllers\BillingController;
use App\Modules\HostingerProxyModule\Http\Controllers\DnsController;
use App\Modules\HostingerProxyModule\Http\Controllers\DomainsController;
use App\Modules\HostingerProxyModule\Http\Controllers\HostingController;
use App\Modules\HostingerProxyModule\Http\Controllers\OrdersController;
use App\Modules\HostingerProxyModule\Http\Controllers\ReachController;
use App\Modules\HostingerProxyModule\Http\Controllers\VpsReadController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'api/v1', 'as' => 'api.v1.', 'middleware' => ['auth:sanctum', 'throttle:global', 'throttle:authenticated']], function () {

    Route::group(['prefix' => 'billing', 'as' => 'billing.'], function () {
        Route::get('/catalog', [BillingController::class, 'catalog'])->name('catalog');
    });

    Route::group(['prefix' => 'orders', 'as' => 'orders.'], function () {
        Route::get('/payment-methods', [OrdersController::class, 'paymentMethods'])->name('payment-methods');
        Route::get('/subscriptions', [OrdersController::class, 'subscriptions'])->name('subscriptions');
    });

    Route::group(['prefix' => 'domains', 'as' => 'domains.'], function () {
        Route::get('/availability', [DomainsController::class, 'availability'])->name('availability');
        Route::get('/forwarding', [DomainsController::class, 'forwarding'])->name('forwarding');
        Route::get('/portfolio', [DomainsController::class, 'portfolio'])->name('portfolio');
        Route::get('/whois', [DomainsController::class, 'whois'])->name('whois');
    });

    Route::group(['prefix' => 'dns', 'as' => 'dns.'], function () {
        Route::get('/zones/{domain}', [DnsController::class, 'zone'])->name('zone');
        Route::get('/zones/{domain}/snapshots', [DnsController::class, 'snapshots'])->name('snapshots');
    });

    Route::group(['prefix' => 'hosting', 'as' => 'hosting.'], function () {
        Route::get('/datacenters', [HostingController::class, 'datacenters'])->name('datacenters');
    });

    Route::group(['prefix' => 'reach', 'as' => 'reach.'], function () {
        Route::get('/contacts', [ReachController::class, 'contacts'])->name('contacts');
        Route::get('/segments', [ReachController::class, 'segments'])->name('segments');
    });

    Route::group(['prefix' => 'vps', 'as' => 'proxy.vps.'], function () {
        Route::get('/', [VpsReadController::class, 'index'])->name('index');
        Route::get('/os-templates', [VpsReadController::class, 'osTemplates'])->name('os-templates');
        Route::get('/data-centers', [VpsReadController::class, 'datacenters'])->name('data-centers');
        Route::get('/{vpsId}', [VpsReadController::class, 'show'])->name('show');
        Route::get('/{vpsId}/metrics', [VpsReadController::class, 'metrics'])->name('metrics');
        Route::get('/{vpsId}/actions', [VpsReadController::class, 'actions'])->name('actions');
        Route::get('/{vpsId}/backups', [VpsReadController::class, 'backups'])->name('backups');
        Route::get('/{vpsId}/firewall', [VpsReadController::class, 'firewall'])->name('firewall');
        Route::get('/{vpsId}/public-keys', [VpsReadController::class, 'sshKeys'])->name('public-keys');
        Route::get('/{vpsId}/snapshots', [VpsReadController::class, 'snapshots'])->name('snapshots');
        Route::get('/{vpsId}/post-install-scripts', [VpsReadController::class, 'postInstallScripts'])->name('post-install-scripts');
    });
});
