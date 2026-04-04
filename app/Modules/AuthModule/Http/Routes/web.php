<?php

use Illuminate\Support\Facades\Route;
use App\Modules\AuthModule\Http\Controllers\{
    InvitationController,
    UserController,
    AuthController
};

Route::group(['prefix' => 'api/v1', 'as' => 'api.v1.'], function () {
    Route::group(['prefix' => 'auth', 'as' => 'auth.'], function () {
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
    });

    Route::group(['prefix' => 'invitations', 'as' => 'invitations.'], function () {
        Route::post('/create', [InvitationController::class, 'inviteUser'])->name('create');
        Route::post('/accept', [InvitationController::class, 'acceptInvitation'])->name('accept');
    });

    Route::group(['prefix' => 'users', 'as' => 'users.'], function () {
        Route::post('/register', [UserController::class, 'register'])->name('register');
    });
});
