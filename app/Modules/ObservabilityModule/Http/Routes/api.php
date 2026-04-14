<?php

use App\Modules\ObservabilityModule\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'api', 'as' => 'api.'], function () {
    // Public health endpoints — no auth, no rate limit (load balancer & uptime monitors)
    Route::get('/health',       [HealthController::class, 'status'])->name('health.status');
    Route::get('/health/ready', [HealthController::class, 'ready'])->name('health.ready');

    // Queue health requires root authentication (ADR-013)
    Route::middleware('auth:sanctum')
        ->get('/health/queue', [HealthController::class, 'queue'])->name('health.queue');
});
