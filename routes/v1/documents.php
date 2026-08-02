<?php

use App\Modules\Documents\Http\Controllers\Api\V1\DocumentController;
use App\Modules\Documents\Http\Controllers\Api\V1\FolderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function (): void {
    Route::get('folders/tree', [FolderController::class, 'tree']);
    Route::post('folders', [FolderController::class, 'store']);
    Route::put('folders/{folder}', [FolderController::class, 'update']);
    Route::post('folders/{folder}/rename', [FolderController::class, 'rename']);
    Route::post('folders/{folder}/lock', [FolderController::class, 'lock']);
    Route::post('folders/{folder}/unlock', [FolderController::class, 'unlock']);
    Route::post('folders/{folder}/hide', [FolderController::class, 'hide']);
    Route::post('folders/{folder}/unhide', [FolderController::class, 'unhide']);
    Route::delete('folders/{folder}', [FolderController::class, 'destroy']);

    Route::get('documents/trash', [DocumentController::class, 'trash']);
    Route::post('documents/bulk-upload', [DocumentController::class, 'bulkUpload']);
    Route::post('documents/{document}/replace', [DocumentController::class, 'replace']);
    Route::post('documents/{document}/rename', [DocumentController::class, 'rename']);
    Route::post('documents/{document}/move', [DocumentController::class, 'move']);
    Route::post('documents/{document}/copy', [DocumentController::class, 'copy']);
    Route::post('documents/{document}/check-out', [DocumentController::class, 'checkOut']);
    Route::post('documents/{document}/check-in', [DocumentController::class, 'checkIn']);
    Route::get('documents/{document}/download', [DocumentController::class, 'download']);
    Route::get('documents/{document}/preview', [DocumentController::class, 'preview']);
    Route::post('documents/{document}/restore', [DocumentController::class, 'restore']);
    Route::delete('documents/{document}/force', [DocumentController::class, 'forceDestroy']);

    Route::apiResource('documents', DocumentController::class);
});
