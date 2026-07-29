<?php

use App\Modules\Search\Http\Controllers\Api\V1\SearchController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('search/documents', [SearchController::class, 'documents']);
    Route::get('search/facets', [SearchController::class, 'facets']);

    Route::get('search/saved', [SearchController::class, 'indexSaved']);
    Route::post('search/saved', [SearchController::class, 'storeSaved']);
    Route::get('search/saved/{saved}', [SearchController::class, 'showSaved']);
    Route::put('search/saved/{saved}', [SearchController::class, 'updateSaved']);
    Route::delete('search/saved/{saved}', [SearchController::class, 'destroySaved']);
});
