<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    require __DIR__.'/v1/auth.php';
    require __DIR__.'/v1/organization.php';
    require __DIR__.'/v1/users.php';
    require __DIR__.'/v1/documents.php';
    require __DIR__.'/v1/archive.php';
    require __DIR__.'/v1/workflow.php';
    require __DIR__.'/v1/dashboard.php';
    require __DIR__.'/v1/search.php';
    require __DIR__.'/v1/operations.php';
    require __DIR__.'/v1/otp.php';
});
