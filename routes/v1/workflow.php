<?php

use App\Modules\Workflow\Http\Controllers\Api\V1\WorkflowController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('workflows/inbox', [WorkflowController::class, 'inbox']);
    Route::get('workflows/instances', [WorkflowController::class, 'instances']);
    Route::get('workflows/stats', [WorkflowController::class, 'stats']);
    Route::get('workflows/recent', [WorkflowController::class, 'recent']);
    Route::get('workflows/documents/{document}/status', [WorkflowController::class, 'documentStatus']);
    Route::post('workflows/submit', [WorkflowController::class, 'submit']);
    Route::get('workflows/instances/{instance}', [WorkflowController::class, 'showInstance']);
    Route::post('workflows/instances/{instance}/approve', [WorkflowController::class, 'approve']);
    Route::post('workflows/instances/{instance}/reject', [WorkflowController::class, 'reject']);
    Route::post('workflows/instances/{instance}/return', [WorkflowController::class, 'returnInstance']);
    Route::post('workflows/instances/{instance}/cancel', [WorkflowController::class, 'cancel']);

    Route::apiResource('workflows', WorkflowController::class);
});
