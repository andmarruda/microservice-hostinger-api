<?php

use App\Modules\FrontendModule\Http\Controllers\AuthPageController;
use App\Modules\FrontendModule\Http\Controllers\BillingPageController;
use App\Modules\FrontendModule\Http\Controllers\DashboardController;
use App\Modules\FrontendModule\Http\Controllers\DnsPageController;
use App\Modules\FrontendModule\Http\Controllers\DomainPageController;
use App\Modules\FrontendModule\Http\Controllers\GovernancePageController;
use App\Modules\FrontendModule\Http\Controllers\OpsPageController;
use App\Modules\FrontendModule\Http\Controllers\ProfileController;
use App\Modules\FrontendModule\Http\Controllers\UserAccessController;
use App\Modules\FrontendModule\Http\Controllers\UserManagementPageController;
use App\Modules\FrontendModule\Http\Controllers\VpsPageController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('/', [AuthPageController::class, 'home'])->name('home');

    Route::get('/login', [AuthPageController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthPageController::class, 'login'])->name('login.submit');

    Route::get('/forgot-password', [AuthPageController::class, 'forgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [AuthPageController::class, 'sendPasswordResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthPageController::class, 'resetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [AuthPageController::class, 'resetPassword'])->name('password.update');

    Route::get('/register/{token}', [AuthPageController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthPageController::class, 'register'])->name('register.submit');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/logout', [AuthPageController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/vps', [VpsPageController::class, 'index'])->name('vps.index');
    Route::get('/vps/{id}', [VpsPageController::class, 'show'])->name('vps.show');
    Route::get('/vps/{id}/firewall', [VpsPageController::class, 'firewall'])->name('vps.firewall');
    Route::get('/vps/{id}/ssh-keys', [VpsPageController::class, 'sshKeys'])->name('vps.ssh-keys');
    Route::get('/vps/{id}/snapshots', [VpsPageController::class, 'snapshots'])->name('vps.snapshots');
    Route::put('/vps/{id}/name', [VpsPageController::class, 'updateName'])->name('vps.name.update');
    Route::put('/vps/{id}/password', [VpsPageController::class, 'updatePassword'])->name('vps.password.update');
    Route::post('/vps/{id}/ssh-keys', [VpsPageController::class, 'storeSshKey'])->name('vps.ssh-keys.store');
    Route::post('/vps/{id}/ssh-keys/{keyId}/remove', [VpsPageController::class, 'destroySshKey'])->name('vps.ssh-keys.destroy');
    Route::post('/vps/{id}/start', [VpsPageController::class, 'start'])->name('vps.start');
    Route::post('/vps/{id}/stop', [VpsPageController::class, 'stop'])->name('vps.stop');
    Route::post('/vps/{id}/reboot', [VpsPageController::class, 'reboot'])->name('vps.reboot');

    Route::get('/domains', [DomainPageController::class, 'portfolio'])->name('domains.portfolio');
    Route::get('/domains/check', [DomainPageController::class, 'availability'])->name('domains.availability');

    Route::get('/dns/{domain}', [DnsPageController::class, 'zone'])->name('dns.zone');

    Route::get('/billing', [BillingPageController::class, 'index'])->name('billing.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/governance/reviews', [GovernancePageController::class, 'reviews'])->name('governance.reviews.index');
    Route::post('/governance/reviews', [GovernancePageController::class, 'storeReview'])->name('governance.reviews.store');
    Route::get('/governance/reviews/{id}', [GovernancePageController::class, 'reviewShow'])->name('governance.reviews.show');
    Route::post('/governance/reviews/{id}/items/{itemId}', [GovernancePageController::class, 'decideItem'])
        ->name('governance.reviews.decide');
    Route::get('/governance/audit', [GovernancePageController::class, 'audit'])->name('governance.audit');
    Route::post('/governance/audit/export', [GovernancePageController::class, 'auditDownload'])
        ->name('governance.audit.export');
    Route::get('/governance/approvals', [GovernancePageController::class, 'approvals'])
        ->name('governance.approvals.index');
    Route::post('/governance/approvals/{id}/approve', [GovernancePageController::class, 'approve'])
        ->name('governance.approvals.approve');

    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserManagementPageController::class, 'index'])->name('users.index');
        Route::post('/users', [UserManagementPageController::class, 'store'])->name('users.store');
        Route::get('/users/{id}', [UserManagementPageController::class, 'show'])->name('users.show');
        Route::delete('/users/{id}', [UserManagementPageController::class, 'destroy'])->name('users.destroy');

        Route::post('/users/{id}/vps-access', [UserAccessController::class, 'grant'])->name('users.vps.grant');
        Route::delete('/users/{id}/vps-access/{vpsId}', [UserAccessController::class, 'revoke'])->name('users.vps.revoke');
        Route::put('/users/{id}/vps-access/{vpsId}/permissions', [UserAccessController::class, 'updatePermissions'])->name('users.vps.permissions');

        Route::get('/ops/health', [OpsPageController::class, 'health'])->name('ops.health');
        Route::get('/ops/quota', [OpsPageController::class, 'quota'])->name('ops.quota');
        Route::get('/ops/cache', [OpsPageController::class, 'cache'])->name('ops.cache');
        Route::get('/ops/database', [OpsPageController::class, 'database'])->name('ops.database');
    });
});
