<?php

use App\Modules\GovernanceModule\Http\Controllers\GovernanceController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'api/v1', 'as' => 'api.v1.', 'middleware' => ['auth:sanctum', 'throttle:global', 'throttle:authenticated']], function () {
    Route::group(['prefix' => 'governance', 'as' => 'governance.'], function () {
        Route::get('/audit-export',    [GovernanceController::class, 'auditExport'])->name('audit-export');
        Route::get('/access-reviews',  [GovernanceController::class, 'indexAccessReviews'])->name('access-reviews.index');
        Route::post('/access-reviews', [GovernanceController::class, 'storeAccessReview'])->name('access-reviews.store');
        Route::post('/access-reviews/{reviewId}/items/{itemId}', [GovernanceController::class, 'decideReviewItem'])->name('access-reviews.items.decide');
        Route::post('/approvals/{approvalId}/approve', [GovernanceController::class, 'approvePermission'])->name('approvals.approve');
    });
});
