<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Preserve Authorization header (Apache / CGI / FastCGI)
|--------------------------------------------------------------------------
| Some Apache setups strip Authorization, or expose it only as
| REDIRECT_HTTP_AUTHORIZATION after rewrite. Sanctum bearer tokens need this.
*/
foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION'] as $key) {
    if (! empty($_SERVER[$key])) {
        $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER[$key];
        break;
    }
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
