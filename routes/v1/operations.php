<?php

use App\Modules\Audit\Http\Controllers\Api\V1\AuditLogController;
use App\Modules\Notifications\Http\Controllers\Api\V1\NotificationController;
use App\Modules\Reports\Http\Controllers\Api\V1\ReportController;
use App\Modules\Retention\Http\Controllers\Api\V1\RetentionController;
use App\Modules\Sharing\Http\Controllers\Api\V1\ShareController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('audit-logs', [AuditLogController::class, 'index']);

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead']);

    Route::get('shares', [ShareController::class, 'index']);
    Route::post('shares', [ShareController::class, 'store']);
    Route::post('shares/{share}/revoke', [ShareController::class, 'revoke']);

    Route::get('retention/policies', [RetentionController::class, 'index']);
    Route::post('retention/policies', [RetentionController::class, 'store']);
    Route::put('retention/policies/{policy}', [RetentionController::class, 'update']);
    Route::delete('retention/policies/{policy}', [RetentionController::class, 'destroy']);
    Route::post('retention/run', [RetentionController::class, 'run']);
    Route::get('retention/runs', [RetentionController::class, 'runs']);

    Route::get('reports/preview', [ReportController::class, 'preview']);
    Route::get('reports/export', [ReportController::class, 'export']);
});

Route::get('public/shares/{token}', [ShareController::class, 'publicShow']);
Route::get('public/shares/{token}/preview', [ShareController::class, 'publicPreview']);
Route::get('public/shares/{token}/download', [ShareController::class, 'publicDownload']);
