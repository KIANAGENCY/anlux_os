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

// SQLite embebida para demo en Vercel (MySQL externo sigue siendo lo ideal).
$seedDb = dirname(__DIR__).DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'vercel.sqlite';
$runtimeDb = '/tmp/anlux.sqlite';
if (is_file($seedDb)) {
    if (! is_file($runtimeDb) || (int) filesize($runtimeDb) < 1024) {
        @copy($seedDb, $runtimeDb);
    }
    putenv('DB_CONNECTION=sqlite');
    putenv('DB_DATABASE='.$runtimeDb);
    $_ENV['DB_CONNECTION'] = 'sqlite';
    $_ENV['DB_DATABASE'] = $runtimeDb;
    $_SERVER['DB_CONNECTION'] = 'sqlite';
    $_SERVER['DB_DATABASE'] = $runtimeDb;
}

require __DIR__.'/../public/index.php';
