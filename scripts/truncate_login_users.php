<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$base = dirname(__DIR__);
require $base.'/vendor/autoload.php';

$app = require $base.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$pdo = DB::connection()->getPdo();
$driver = DB::connection()->getDriverName();

if (in_array($driver, ['mysql', 'mariadb'], true)) {
    $db = (string) DB::connection()->getDatabaseName();
    $stmt = $pdo->prepare(
        'SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME
         FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME = ?'
    );
    $stmt->execute([$db, 'login']);
    $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($fks !== []) {
        fwrite(STDERR, "Foreign keys reference `login`. Drop or truncate dependent rows first:\n");
        fwrite(STDERR, json_encode($fks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n");
        exit(1);
    }
} elseif ($driver !== 'sqlite') {
    fwrite(STDERR, "Aviso: driver `{$driver}` — no se comprueba information_schema; se intenta vaciar `login`.\n");
}

try {
    if (Schema::hasTable('login_remember_tokens')) {
        $pdo->exec('DELETE FROM `login_remember_tokens`');
    }
    if (Schema::hasTable('login')) {
        $pdo->exec('DELETE FROM `login`');
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $pdo->exec('ALTER TABLE `login` AUTO_INCREMENT = 1');
        } elseif ($driver === 'sqlite') {
            $pdo->exec("DELETE FROM sqlite_sequence WHERE name = 'login'");
        }
    }
    echo 'OK: `login` vaciada'.(in_array($driver, ['mysql', 'mariadb'], true) ? ' y AUTO_INCREMENT = 1' : ($driver === 'sqlite' ? ' y contador sqlite_sequence reiniciado' : '')).".\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: '.$e->getMessage()."\n");
    exit(1);
}
