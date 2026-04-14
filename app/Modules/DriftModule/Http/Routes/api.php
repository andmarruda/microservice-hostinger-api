<?php

use App\Modules\DriftModule\Http\Controllers\DriftController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'api/v1', 'as' => 'api.v1.', 'middleware' => ['auth:sanctum', 'throttle:global', 'throttle:authenticated']], function () {
    Route::group(['prefix' => 'drift', 'as' => 'drift.'], function () {
        Route::get('/', [DriftController::class, 'index'])->name('index');
        Route::post('/{reportId}/resolve', [DriftController::class, 'resolve'])->name('resolve');
        Route::post('/{reportId}/dismiss', [DriftController::class, 'dismiss'])->name('dismiss');
    });
});
