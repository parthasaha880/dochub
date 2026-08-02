<?php

use App\Modules\Authentication\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::middleware(['throttle:auth'])->group(function (): void {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
    });

    Route::middleware(['auth:sanctum'])->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::put('me', [AuthController::class, 'updateProfile']);
        Route::get('me/avatar', [AuthController::class, 'avatar']);
        Route::post('me/avatar', [AuthController::class, 'uploadAvatar']);
        Route::delete('me/avatar', [AuthController::class, 'deleteAvatar']);
        Route::post('email/verification-notification', [AuthController::class, 'sendVerificationEmail'])
            ->middleware('throttle:6,1');
        Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');

        Route::get('login-activities', [AuthController::class, 'loginActivities']);
        Route::get('devices', [AuthController::class, 'devices']);
        Route::delete('devices/{device}', [AuthController::class, 'revokeDevice']);
        Route::post('logout-other-devices', [AuthController::class, 'logoutOtherDevices']);
        Route::get('sessions', [AuthController::class, 'sessions']);
        Route::delete('sessions/{session}', [AuthController::class, 'revokeSession']);
    });
});
