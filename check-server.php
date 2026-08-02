<?php

/**
 * EDAMS server health check — run once, then DELETE this file.
 *
 * Usage (SSH):
 *   php check-server.php
 *
 * Or open briefly in browser, then remove immediately.
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$ok = true;
$lines = [];

function line(string $msg, bool $pass = true): void
{
    global $ok, $lines;
    $lines[] = ($pass ? '[OK]  ' : '[FAIL] ').$msg;
    if (! $pass) {
        $ok = false;
    }
}

line('PHP version: '.PHP_VERSION, version_compare(PHP_VERSION, '8.3.0', '>='));
line('SAPI: '.PHP_SAPI);

$required = [
    'mbstring', 'openssl', 'pdo', 'pdo_mysql', 'tokenizer', 'xml',
    'ctype', 'json', 'bcmath', 'fileinfo', 'curl', 'filter',
];

foreach ($required as $ext) {
    line("ext-{$ext}", extension_loaded($ext));
}

$base = __DIR__;
line('artisan exists', is_file($base.'/artisan'));
line('vendor/autoload.php exists', is_file($base.'/vendor/autoload.php'));
line('.env exists', is_file($base.'/.env'));
line('bootstrap/app.php exists', is_file($base.'/bootstrap/app.php'));
line('storage writable', is_writable($base.'/storage'));
line('bootstrap/cache writable', is_writable($base.'/bootstrap/cache'));

$cacheFiles = glob($base.'/bootstrap/cache/*.php') ?: [];
line('bootstrap/cache PHP files: '.count($cacheFiles).(count($cacheFiles) ? ' (clear if artisan fails)' : ''));

if (is_file($base.'/.env')) {
    $env = file_get_contents($base.'/.env') ?: '';
    line('APP_KEY set', (bool) preg_match('/^APP_KEY=base64:.+/m', $env));
    if (preg_match('/^APP_URL=(.+)$/m', $env, $m)) {
        line('APP_URL='.trim($m[1]));
    }
}

echo implode(PHP_EOL, $lines).PHP_EOL.PHP_EOL;

if (! is_file($base.'/vendor/autoload.php')) {
    echo "NEXT: run composer install --no-dev --optimize-autoloader".PHP_EOL;
    exit(1);
}

try {
    require $base.'/vendor/autoload.php';
    $app = require $base.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    echo "[OK]  Laravel bootstrap succeeded".PHP_EOL;
    echo "[OK]  App name: ".config('app.name').PHP_EOL;
    echo PHP_EOL."Artisan should work. Try: php artisan --version".PHP_EOL;
} catch (Throwable $e) {
    echo "[FAIL] Laravel bootstrap error (THIS is the real problem):".PHP_EOL;
    echo get_class($e).': '.$e->getMessage().PHP_EOL;
    echo $e->getFile().':'.$e->getLine().PHP_EOL;
    echo PHP_EOL."First stack frames:".PHP_EOL;
    foreach (array_slice($e->getTrace(), 0, 8) as $i => $frame) {
        $file = $frame['file'] ?? '?';
        $lineNo = $frame['line'] ?? '?';
        $fn = ($frame['class'] ?? '').($frame['type'] ?? '').($frame['function'] ?? '');
        echo "  #{$i} {$file}:{$lineNo} {$fn}".PHP_EOL;
    }
    exit(1);
}

exit($ok ? 0 : 1);
