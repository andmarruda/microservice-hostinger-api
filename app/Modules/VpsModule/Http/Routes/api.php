<?php

use App\Modules\VpsModule\Http\Controllers\VpsController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'api/v1', 'as' => 'api.v1.', 'middleware' => ['throttle:global', 'throttle:authenticated']], function () {
    Route::group(['prefix' => 'vps', 'as' => 'vps.', 'middleware' => 'throttle:writes'], function () {
        Route::post('/{vpsId}/start', [VpsController::class, 'start'])->name('start');
        Route::post('/{vpsId}/stop', [VpsController::class, 'stop'])->name('stop');
        Route::post('/{vpsId}/reboot', [VpsController::class, 'reboot'])->name('reboot');
    });
});
