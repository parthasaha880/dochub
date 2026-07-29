<?php

use App\Modules\Users\Http\Controllers\Api\V1\PermissionController;
use App\Modules\Users\Http\Controllers\Api\V1\RoleController;
use App\Modules\Users\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function (): void {
    Route::apiResource('users', UserController::class);

    Route::get('roles/options/list', [RoleController::class, 'options']);
    Route::apiResource('roles', RoleController::class);

    Route::get('permissions/options/list', [PermissionController::class, 'options']);
    Route::get('permissions/groups', [PermissionController::class, 'groups']);
    Route::get('permissions/grouped', [PermissionController::class, 'grouped']);
    Route::apiResource('permissions', PermissionController::class);
});
