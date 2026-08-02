<?php

use App\Modules\Authentication\Http\Controllers\Api\V1\OtpBookController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('otp-book', [OtpBookController::class, 'index']);
});
