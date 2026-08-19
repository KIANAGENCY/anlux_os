<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$search = $argv[1] ?? '6122885758';
$patterns = array_unique([$search, '52'.$search, preg_replace('/\D+/', '', $search)]);

echo "Buscando mensajes para: {$search}\n\n";

if (Schema::hasTable('order_whatsapp_notifications')) {
    echo "=== order_whatsapp_notifications ===\n";
    $rows = DB::table('order_whatsapp_notifications')
        ->where(function ($q) use ($patterns) {
            foreach ($patterns as $p) {
                if ($p !== '') {
                    $q->orWhere('telefono', 'like', '%'.$p.'%');
                }
            }
        })
        ->orderByDesc('id')
        ->limit(20)
        ->get();
    echo 'Total: '.$rows->count()."\n";
    foreach ($rows as $r) {
        echo json_encode([
            'id' => $r->id,
            'id_orden_c' => $r->id_orden_c,
            'folio' => $r->folio,
            'estatus' => $r->estatus,
            'telefono' => $r->telefono,
            'status' => $r->status,
            'template' => $r->template_name,
            'sent_at' => $r->sent_at,
            'delivered_at' => $r->delivered_at,
            'failed_at' => $r->failed_at,
            'message' => $r->message,
        ], JSON_UNESCAPED_UNICODE)."\n";
    }
} else {
    echo "Tabla order_whatsapp_notifications no existe\n";
}

echo "\n";

if (Schema::hasTable('wa_messages')) {
    echo "=== wa_messages ===\n";
    $rows = DB::table('wa_messages')
        ->where(function ($q) use ($patterns) {
            foreach ($patterns as $p) {
                if ($p !== '') {
                    $q->orWhere('wa_phone', 'like', '%'.$p.'%');
                }
            }
        })
        ->orderByDesc('id')
        ->limit(20)
        ->get();
    echo 'Total: '.$rows->count()."\n";
    foreach ($rows as $r) {
        echo json_encode([
            'id' => $r->id,
            'wa_phone' => $r->wa_phone,
            'direction' => $r->direction,
            'status' => $r->status,
            'body' => mb_substr((string) $r->body, 0, 100),
            'created_at' => $r->created_at,
            'error' => $r->error ?? null,
        ], JSON_UNESCAPED_UNICODE)."\n";
    }
}

echo "\n";

if (Schema::hasTable('login')) {
    echo "=== login (admin/tecnico celular hash) ===\n";
    $users = DB::table('login')->select('id_tecnico', 'correo', 'perfil', 'celular')->get();
    foreach ($users as $u) {
        echo json_encode((array) $u, JSON_UNESCAPED_UNICODE)."\n";
    }
}
