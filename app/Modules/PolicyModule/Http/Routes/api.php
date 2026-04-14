<?php

use App\Modules\PolicyModule\Http\Controllers\PolicyController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'api/v1', 'as' => 'api.v1.', 'middleware' => ['auth:sanctum', 'throttle:global', 'throttle:authenticated']], function () {
    Route::group(['prefix' => 'policies', 'as' => 'policies.'], function () {
        Route::get('/', [PolicyController::class, 'index'])->name('index');
        Route::post('/', [PolicyController::class, 'store'])->name('store');
        Route::delete('/{policyId}', [PolicyController::class, 'destroy'])->name('destroy');
    });
});
