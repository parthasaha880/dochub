<?php

use App\Modules\Dashboard\Http\Controllers\Api\V1\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('dashboard/summary', [DashboardController::class, 'summary']);
});
