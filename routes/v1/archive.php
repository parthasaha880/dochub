<?php

use App\Modules\Archive\Http\Controllers\Api\V1\ArchiveController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('archive')->group(function (): void {
    Route::get('stats', [ArchiveController::class, 'stats']);
    Route::get('lookup', [ArchiveController::class, 'lookup']);

    Route::get('locations/tree', [ArchiveController::class, 'locationTree']);
    Route::get('locations/{location}', [ArchiveController::class, 'showLocation']);
    Route::post('locations', [ArchiveController::class, 'storeLocation']);
    Route::put('locations/{location}', [ArchiveController::class, 'updateLocation']);
    Route::delete('locations/{location}', [ArchiveController::class, 'destroyLocation']);

    Route::get('categories/tree', [ArchiveController::class, 'categoryTree']);
    Route::post('categories', [ArchiveController::class, 'storeCategory']);
    Route::put('categories/{category}', [ArchiveController::class, 'updateCategory']);
    Route::delete('categories/{category}', [ArchiveController::class, 'destroyCategory']);

    Route::get('digital', [ArchiveController::class, 'digital']);
    Route::get('physical', [ArchiveController::class, 'physical']);
    Route::get('hybrid', [ArchiveController::class, 'hybrid']);

    Route::post('documents/{document}/assign-location', [ArchiveController::class, 'assignLocation']);
    Route::post('documents/{document}/archive', [ArchiveController::class, 'archiveDocument']);
});
