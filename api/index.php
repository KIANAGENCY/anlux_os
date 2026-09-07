<?php

/**
 * Entrypoint serverless para Vercel (vercel-php).
 * Todas las rutas dinámicas llegan aquí.
 */
declare(strict_types=1);

$storage = '/tmp/storage';
$dirs = [
    $storage.'/app/public',
    $storage.'/framework/cache/data',
    $storage.'/framework/sessions',
    $storage.'/framework/views',
    $storage.'/logs',
    '/tmp/bootstrap/cache',
    '/tmp/views',
];

foreach ($dirs as $dir) {
    if (! is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}

require __DIR__.'/../public/index.php';
