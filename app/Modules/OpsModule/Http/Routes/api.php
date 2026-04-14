<?php

use App\Modules\OpsModule\Http\Controllers\OpsController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'api/v1', 'as' => 'api.v1.', 'middleware' => ['auth:sanctum', 'throttle:global', 'throttle:authenticated']], function () {
    Route::group(['prefix' => 'ops', 'as' => 'ops.'], function () {
        Route::get('/quota',       [OpsController::class, 'quotaStats'])->name('quota');
        Route::get('/cache-stats', [OpsController::class, 'cacheStats'])->name('cache-stats');
        Route::get('/db-stats',    [OpsController::class, 'dbStats'])->name('db-stats');
    });
});
