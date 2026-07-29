<?php

use App\Modules\Organization\Http\Controllers\Api\V1\BranchController;
use App\Modules\Organization\Http\Controllers\Api\V1\DepartmentController;
use App\Modules\Organization\Http\Controllers\Api\V1\DesignationController;
use App\Modules\Organization\Http\Controllers\Api\V1\EmployeeController;
use App\Modules\Organization\Http\Controllers\Api\V1\OfficeController;
use App\Modules\Organization\Http\Controllers\Api\V1\OrganizationController;
use App\Modules\Organization\Http\Controllers\Api\V1\SectionController;
use App\Modules\Organization\Http\Controllers\Api\V1\UnitController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function (): void {
    Route::get('organizations/options/list', [OrganizationController::class, 'options']);
    Route::get('organizations/{organization}/tree', [OrganizationController::class, 'tree']);
    Route::apiResource('organizations', OrganizationController::class);

    Route::get('branches/options/list', [BranchController::class, 'options']);
    Route::apiResource('branches', BranchController::class);

    Route::get('departments/options/list', [DepartmentController::class, 'options']);
    Route::apiResource('departments', DepartmentController::class);

    Route::get('sections/options/list', [SectionController::class, 'options']);
    Route::apiResource('sections', SectionController::class);

    Route::get('units/options/list', [UnitController::class, 'options']);
    Route::apiResource('units', UnitController::class);

    Route::get('offices/options/list', [OfficeController::class, 'options']);
    Route::apiResource('offices', OfficeController::class);

    Route::get('designations/options/list', [DesignationController::class, 'options']);
    Route::apiResource('designations', DesignationController::class);

    Route::get('employees/options/list', [EmployeeController::class, 'options']);
    Route::apiResource('employees', EmployeeController::class);
});
