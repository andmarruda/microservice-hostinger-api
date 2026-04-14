<?php

use App\Modules\FrontendModule\Http\Controllers\AuthPageController;
use App\Modules\FrontendModule\Http\Controllers\BillingPageController;
use App\Modules\FrontendModule\Http\Controllers\DashboardController;
use App\Modules\FrontendModule\Http\Controllers\DnsPageController;
use App\Modules\FrontendModule\Http\Controllers\DomainPageController;
use App\Modules\FrontendModule\Http\Controllers\GovernancePageController;
use App\Modules\FrontendModule\Http\Controllers\OpsPageController;
use App\Modules\FrontendModule\Http\Controllers\VpsPageController;
use Illuminate\Support\Facades\Route;

// Public auth pages
Route::middleware('web')->group(function () {
    Route::get('/login',            [AuthPageController::class, 'loginForm'])->name('login');
    Route::post('/login',           [AuthPageController::class, 'login'])->name('login.submit');
    Route::get('/register/{token}', [AuthPageController::class, 'registerForm'])->name('register');
    Route::post('/register',        [AuthPageController::class, 'register'])->name('register.submit');
});

// Authenticated pages
Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/logout', [AuthPageController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // VPS
    Route::get('/vps',                   [VpsPageController::class, 'index'])->name('vps.index');
    Route::get('/vps/{id}',              [VpsPageController::class, 'show'])->name('vps.show');
    Route::get('/vps/{id}/firewall',     [VpsPageController::class, 'firewall'])->name('vps.firewall');
    Route::get('/vps/{id}/ssh-keys',     [VpsPageController::class, 'sshKeys'])->name('vps.ssh-keys');
    Route::get('/vps/{id}/snapshots',    [VpsPageController::class, 'snapshots'])->name('vps.snapshots');
    Route::post('/vps/{id}/start',       [VpsPageController::class, 'start'])->name('vps.start');
    Route::post('/vps/{id}/stop',        [VpsPageController::class, 'stop'])->name('vps.stop');
    Route::post('/vps/{id}/reboot',      [VpsPageController::class, 'reboot'])->name('vps.reboot');

    // Domains
    Route::get('/domains',       [DomainPageController::class, 'portfolio'])->name('domains.portfolio');
    Route::get('/domains/check', [DomainPageController::class, 'availability'])->name('domains.availability');

    // DNS
    Route::get('/dns/{domain}', [DnsPageController::class, 'zone'])->name('dns.zone');

    // Billing
    Route::get('/billing', [BillingPageController::class, 'index'])->name('billing.index');

    // Governance
    Route::get('/governance/reviews',                              [GovernancePageController::class, 'reviews'])->name('governance.reviews.index');
    Route::post('/governance/reviews',                             [GovernancePageController::class, 'storeReview'])->name('governance.reviews.store');
    Route::get('/governance/reviews/{id}',                         [GovernancePageController::class, 'reviewShow'])->name('governance.reviews.show');
    Route::post('/governance/reviews/{id}/items/{itemId}',         [GovernancePageController::class, 'decideItem'])->name('governance.reviews.decide');
    Route::get('/governance/audit',                                [GovernancePageController::class, 'audit'])->name('governance.audit');
    Route::post('/governance/audit/export',                        [GovernancePageController::class, 'auditDownload'])->name('governance.audit.export');
    Route::get('/governance/approvals',                            [GovernancePageController::class, 'approvals'])->name('governance.approvals.index');
    Route::post('/governance/approvals/{id}/approve',              [GovernancePageController::class, 'approve'])->name('governance.approvals.approve');

    // Ops — root only
    Route::middleware('role:root')->group(function () {
        Route::get('/ops/health',   [OpsPageController::class, 'health'])->name('ops.health');
        Route::get('/ops/quota',    [OpsPageController::class, 'quota'])->name('ops.quota');
        Route::get('/ops/cache',    [OpsPageController::class, 'cache'])->name('ops.cache');
        Route::get('/ops/database', [OpsPageController::class, 'database'])->name('ops.database');
    });
});
