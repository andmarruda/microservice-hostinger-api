<?php

use App\Modules\SecurityResourceModule\Http\Controllers\FirewallController;
use App\Modules\SecurityResourceModule\Http\Controllers\SnapshotController;
use App\Modules\SecurityResourceModule\Http\Controllers\SshKeyController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'api/v1', 'as' => 'api.v1.', 'middleware' => ['throttle:global', 'throttle:authenticated']], function () {
    Route::group(['prefix' => 'vps', 'as' => 'security.vps.', 'middleware' => 'throttle:writes'], function () {
        Route::post('/{vpsId}/firewall/rules', [FirewallController::class, 'store'])->name('firewall.store');
        Route::delete('/{vpsId}/firewall/rules/{ruleId}', [FirewallController::class, 'destroy'])->name('firewall.destroy');

        Route::post('/{vpsId}/ssh-keys', [SshKeyController::class, 'store'])->name('ssh-keys.store');
        Route::delete('/{vpsId}/ssh-keys/{keyId}', [SshKeyController::class, 'destroy'])->name('ssh-keys.destroy');

        Route::post('/{vpsId}/snapshots', [SnapshotController::class, 'store'])->name('snapshots.store');
        Route::delete('/{vpsId}/snapshots/{snapshotId}', [SnapshotController::class, 'destroy'])->name('snapshots.destroy');
    });
});
