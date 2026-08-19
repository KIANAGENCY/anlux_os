<?php

declare(strict_types=1);

/**
 * Prueba envío WhatsApp Cloud API (sin PDF, sin Laravel queue).
 *
 * BORRAR cuando termines — expone capacidad de envío.
 *
 * Ping (¿llega a Meta?):
 *   wa_test_send.php?key=exacto99&mode=ping
 *
 * Reintentar orden fallida (sin SSH):
 *   docheck.php?key=exacto99&wa_requeue_run=7
 *
 * Texto libre (solo si el cliente escribió a tu número en las últimas 24 h):
 *   wa_test_send.php?key=exacto99&mode=text&to=526121684390&body=Prueba+Exacto
 *
 * Plantilla solo texto (falla si la plantilla exige DOCUMENT):
 *   wa_test_send.php?key=exacto99&mode=template&to=5216121684390&name=orden_recepcion&p1=OS-2026-020&p2=Recepcion
 *
 * Plantilla CON PDF (subida a Meta — recomendado para orden_recepcion):
 *   wa_test_send.php?key=exacto99&mode=template_doc&to=5216121684390&name=orden_recepcion&p1=OS-TEST&p2=Recepcion
 *
 * Dar de alta el número en Cloud API (PIN de verificación en 2 pasos, 6 dígitos):
 *   wa_test_send.php?key=exacto99&mode=register&pin=123456
 *
 * Suscribir la app al WABA (necesario para webhooks delivered/failed reales):
 *   wa_test_send.php?key=exacto99&mode=subscribe&waba_id=1984496095494792
 *   wa_test_send.php?key=exacto99&mode=subscriptions&waba_id=1984496095494792
 *
 * Verificar si un ID es Phone Number ID (GET + POST prueba):
 *   wa_test_send.php?key=exacto99&mode=verify_phone
 *   wa_test_send.php?key=exacto99&mode=verify_phone&phone_id=1964158600503621
 *   wa_test_send.php?key=exacto99&mode=verify_phone&waba_id=1964158600503621
 *   wa_test_send.php?key=exacto99&mode=verify_phone&compare=1537852524373963,1964158600503621,61590273249931
 *
 * Descubrir WABA + Phone Number ID reales del token (recomendado si verify falla):
 *   wa_test_send.php?key=exacto99&mode=discover
 *   wa_test_send.php?key=exacto99&mode=discover&business_id=1964158600503621
 */

const WA_TEST_KEY = 'exacto99';

if (($_GET['key'] ?? '') !== WA_TEST_KEY) {
    http_response_code(403);
    exit('Forbidden — usa ?key='.WA_TEST_KEY);
}

header('Content-Type: text/plain; charset=utf-8');

$root = dirname(__DIR__);
if (! is_file($root.'/vendor/autoload.php')) {
    exit("Falta vendor/autoload.php\n");
}

require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$mode = strtolower(trim((string) ($_GET['mode'] ?? 'ping')));
$token = trim((string) config('services.whatsapp.access_token', ''));
$phoneIdOverride = preg_replace('/\D+/', '', trim((string) ($_GET['phone_id'] ?? ''))) ?? '';
$phoneId = $phoneIdOverride !== '' ? $phoneIdOverride : trim((string) config('services.whatsapp.phone_number_id', ''));
$wabaId = preg_replace('/\D+/', '', trim((string) ($_GET['waba_id'] ?? ''))) ?? '';
$version = trim((string) config('services.whatsapp.graph_version', 'v20.0'));
$base = rtrim((string) config('services.whatsapp.base_url', 'https://graph.facebook.com'), '/');
$lang = trim((string) config('services.whatsapp.language', 'es_MX'));
$connectTimeout = max(10, (int) config('services.whatsapp.http_connect_timeout', 60));
$timeout = max(30, (int) config('services.whatsapp.http_timeout', 180));

echo "=== WhatsApp test send ===\n";
echo 'Modo: '.$mode."\n";
echo 'Phone ID: '.($phoneId !== '' ? $phoneId : '(vacío)')."\n";
echo 'Token: '.($token !== '' ? substr($token, 0, 6).'…' : '(vacío)')."\n\n";

if ($token === '') {
    exit("Config incompleta: falta WHATSAPP_ACCESS_TOKEN en .env\n");
}

$http = static fn () => Http::withToken($token)
    ->acceptJson()
    ->connectTimeout($connectTimeout)
    ->timeout($timeout);

$printSection = static function (string $title, callable $fn) use ($http): void {
    echo str_repeat('-', 60)."\n";
    echo $title."\n";
    echo str_repeat('-', 60)."\n";
    try {
        $fn($http);
    } catch (Throwable $e) {
        echo 'ERROR: '.$e->getMessage()."\n";
    }
    echo "\n";
};

if ($mode === 'discover') {
    $businessId = preg_replace('/\D+/', '', trim((string) ($_GET['business_id'] ?? '1964158600503621'))) ?? '';

    echo "=== Descubrir WhatsApp (token → WABA → numeros) ===\n";
    echo "API: {$base}/{$version}\n\n";

    $printSection('1) debug_token (permisos y app_id del token)', function ($http) use ($base, $version, $token) {
        $url = $base.'/'.$version.'/debug_token';
        $r = $http()->get($url, [
            'input_token' => $token,
            'access_token' => $token,
        ]);
        echo "GET {$url}\n";
        echo 'HTTP '.$r->status()."\n";
        $j = $r->json();
        echo json_encode($j, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
        $data = is_array($j) ? ($j['data'] ?? null) : null;
        if (! is_array($data)) {
            return;
        }
        echo "\nResumen:\n";
        echo '  type: '.($data['type'] ?? '?')."\n";
        echo '  app_id: '.($data['app_id'] ?? '?')."\n";
        echo '  is_valid: '.(isset($data['is_valid']) ? ($data['is_valid'] ? 'true' : 'false') : '?')."\n";
        $scopes = $data['scopes'] ?? [];
        if (is_array($scopes) && $scopes !== []) {
            echo '  scopes: '.implode(', ', $scopes)."\n";
        }
        $need = ['whatsapp_business_messaging', 'whatsapp_business_management'];
        foreach ($need as $s) {
            $ok = is_array($scopes) && in_array($s, $scopes, true);
            echo '  '.($ok ? '[OK]' : '[FALTA]').' '.$s."\n";
        }
        $granular = $data['granular_scopes'] ?? [];
        if (is_array($granular) && $granular !== []) {
            echo "\n  granular_scopes (target_ids = WABA u otros):\n";
            foreach ($granular as $g) {
                if (! is_array($g)) {
                    continue;
                }
                $ids = $g['target_ids'] ?? [];
                echo '    - '.($g['scope'] ?? '?').': '.(is_array($ids) ? implode(', ', $ids) : '')."\n";
            }
        }
        if ((string) ($data['app_id'] ?? '') === '1537852524373963') {
            echo "\n  → Token ligado a app CELULAR SOPORTE (1537852524373963). Debe ser la MISMA app\n";
            echo "    que en Meta > WhatsApp > API Setup, con WhatsApp producto agregado.\n";
        }
    });

    $printSection('2) /me (identidad del token)', function ($http) use ($base, $version) {
        $url = $base.'/'.$version.'/me';
        $r = $http()->get($url, ['fields' => 'id,name']);
        echo "GET {$url}?fields=id,name\n";
        echo 'HTTP '.$r->status()."\n";
        echo json_encode($r->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
    });

    $printSection('3) Negocios del usuario /me/businesses', function ($http) use ($base, $version) {
        $url = $base.'/'.$version.'/me/businesses';
        $r = $http()->get($url, ['fields' => 'id,name']);
        echo "GET {$url}\n";
        echo 'HTTP '.$r->status()."\n";
        $j = $r->json();
        echo json_encode($j, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
        if ($r->successful() && is_array($j['data'] ?? null)) {
            echo "\n  → Prueba discover&business_id=UNO_DE_ESTOS_IDS\n";
        }
    });

    if ($businessId !== '') {
        $printSection("4) WABA del negocio {$businessId} (/owned_whatsapp_business_accounts)", function ($http) use ($base, $version, $businessId) {
            $url = $base.'/'.$version.'/'.$businessId.'/owned_whatsapp_business_accounts';
            $r = $http()->get($url, ['fields' => 'id,name,account_review_status,currency']);
            echo "GET {$url}\n";
            echo 'HTTP '.$r->status()."\n";
            $j = $r->json();
            echo json_encode($j, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
            if (! $r->successful()) {
                echo "\n  → {$businessId} no es Business ID, o el token no tiene acceso.\n";
                echo "    No uses este ID como WHATSAPP_PHONE_NUMBER_ID.\n";

                return;
            }
            foreach (is_array($j['data'] ?? null) ? $j['data'] : [] as $waba) {
                if (! is_array($waba) || empty($waba['id'])) {
                    continue;
                }
                echo "\n  WABA id: ".$waba['id'].' name: '.($waba['name'] ?? '')."\n";
            }
        });

        $printSection("5) Numeros de cada WABA bajo negocio {$businessId}", function ($http) use ($base, $version, $businessId) {
            $wabaUrl = $base.'/'.$version.'/'.$businessId.'/owned_whatsapp_business_accounts';
            $wabaRes = $http()->get($wabaUrl, ['fields' => 'id,name']);
            if (! $wabaRes->successful()) {
                echo "No se pudo listar WABA (paso 4 fallo).\n";

                return;
            }
            $wabas = is_array($wabaRes->json()['data'] ?? null) ? $wabaRes->json()['data'] : [];
            if ($wabas === []) {
                echo "Sin WABA en este negocio.\n";

                return;
            }
            foreach ($wabas as $waba) {
                if (! is_array($waba) || empty($waba['id'])) {
                    continue;
                }
                $wabaId = (string) $waba['id'];
                $phonesUrl = $base.'/'.$version.'/'.$wabaId.'/phone_numbers';
                echo "\nWABA {$wabaId} (".($waba['name'] ?? '').")\n";
                echo "GET {$phonesUrl}\n";
                $pr = $http()->get($phonesUrl, [
                    'fields' => 'id,display_phone_number,verified_name,quality_rating,code_verification_status',
                ]);
                echo 'HTTP '.$pr->status()."\n";
                echo json_encode($pr->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
                if ($pr->successful()) {
                    echo "  *** Copia el «id» de cada numero a WHATSAPP_PHONE_NUMBER_ID ***\n";
                }
            }
        });
    }

    $printSection('6) Que tipo de objeto son tus IDs (diagnostico rapido)', function ($http) use ($base, $version) {
        $candidates = [
            '1537852524373963' => 'App ID (chat Meta)',
            '1964158600503621' => 'Posible Business / otro',
            '61590273249931' => 'En .env actual',
        ];
        foreach ($candidates as $id => $label) {
            $r = $http()->get($base.'/'.$version.'/'.$id, ['fields' => 'id,name,display_phone_number,verified_name,link']);
            echo "\n{$label} ({$id}): HTTP ".$r->status()."\n";
            $j = $r->json();
            if (isset($j['link'])) {
                echo "  tipo: App de Facebook\n";
            } elseif (isset($j['display_phone_number'])) {
                echo "  tipo: Phone Number ID — USA ESTE en .env\n";
                echo '  numero: '.$j['display_phone_number']."\n";
            } elseif (isset($j['name']) && ! isset($j['display_phone_number'])) {
                echo '  tipo: NO es numero WA (name='.($j['name'] ?? '').")\n";
            } else {
                echo '  '.json_encode($j, JSON_UNESCAPED_UNICODE)."\n";
            }
        }
    });

    echo "=== Conclusion de tu prueba verify_phone ===\n";
    echo "• 1537852524373963 = App «CELULAR SOPORTE» — nunca para /messages.\n";
    echo "• 1964158600503621 = objeto «EXACTO» sin campos de telefono — NO es Phone Number ID.\n";
    echo "• 61590273249931 = «SOPORTE_TOKEN» sin display_phone_number — tampoco es nodo de linea WA.\n";
    echo "• Los tres dan POST subcode 33 → token sin permiso de envio sobre esos objetos,\n";
    echo "  o ninguno es el Phone Number ID de la linea de soporte.\n\n";
    echo "Siguiente paso: sube este archivo, ejecuta mode=discover y pega la salida.\n";
    echo "En Meta: developers.facebook.com > tu app > WhatsApp > API Setup:\n";
    echo "  copia «Phone number ID» y genera token con whatsapp_business_messaging.\n";
    echo "  App debe estar en el mismo Business que la cuenta WhatsApp.\n";
    exit;
}

if ($mode === 'register') {
    $pin = preg_replace('/\D+/', '', trim((string) ($_GET['pin'] ?? ''))) ?? '';
    if ($phoneId === '') {
        exit("Falta WHATSAPP_PHONE_NUMBER_ID o &phone_id=\n");
    }
    if (strlen($pin) !== 6) {
        exit(
            "Falta &pin=XXXXXX (6 dígitos).\n".
            "Es el PIN de «Verificación en dos pasos» del número en WhatsApp Manager.\n".
            "Si no lo recuerdas: WhatsApp Manager → Número → Verificación en dos pasos → cambiar PIN.\n"
        );
    }

    $url = $base.'/'.$version.'/'.$phoneId.'/register';
    echo "POST {$url}\n";
    echo "Body: messaging_product=whatsapp, pin={$pin}\n\n";
    try {
        $r = $http()->asJson()->post($url, [
            'messaging_product' => 'whatsapp',
            'pin' => $pin,
        ]);
        echo 'HTTP '.$r->status()."\n";
        echo json_encode($r->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n\n";
        if ($r->successful()) {
            echo "[OK] Número registrado en Cloud API (o ya estaba registrado).\n";
            echo "Luego corre mode=subscribe con el WABA ID.\n";
        } else {
            $code = (int) (is_array($r->json()) ? ($r->json()['error']['code'] ?? 0) : 0);
            if ($code === 133005) {
                echo "→ PIN incorrecto (133005). Usa el PIN real de verificación en 2 pasos.\n";
            } elseif ($code === 133012) {
                echo "→ Ya está registrado con Cloud API (133012). Sigue con mode=subscribe.\n";
            } elseif ($code === 133016) {
                echo "→ Demasiados intentos. Espera y reintenta más tarde.\n";
            }
        }
    } catch (Throwable $e) {
        echo 'ERROR: '.$e->getMessage()."\n";
    }
    exit;
}

if ($mode === 'subscribe' || $mode === 'subscriptions') {
    if ($wabaId === '') {
        exit(
            "Falta &waba_id=...\n".
            "En Business Suite → Cuentas de WhatsApp → Exacto La paz → Identificador.\n".
            "Ejemplo: waba_id=1984496095494792\n"
        );
    }

    if ($mode === 'subscriptions') {
        $url = $base.'/'.$version.'/'.$wabaId.'/subscribed_apps';
        echo "GET {$url}\n\n";
        try {
            $r = $http()->get($url);
            echo 'HTTP '.$r->status()."\n";
            echo json_encode($r->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n\n";
            if ($r->successful()) {
                echo "[OK] Apps suscritas a este WABA. Debe aparecer tu app (la del webhook).\n";
                echo "Si la lista está vacía o no está tu app, corre mode=subscribe.\n";
            }
        } catch (Throwable $e) {
            echo 'ERROR: '.$e->getMessage()."\n";
        }
        exit;
    }

    $url = $base.'/'.$version.'/'.$wabaId.'/subscribed_apps';
    echo "POST {$url}\n";
    echo "Suscribe la app del ACCESS_TOKEN a los webhooks de este WABA.\n\n";
    try {
        $r = $http()->asJson()->post($url, []);
        echo 'HTTP '.$r->status()."\n";
        echo json_encode($r->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n\n";
        if ($r->successful()) {
            echo "[OK] App suscrita al WABA. Los statuses delivered/failed deberían llegar al webhook.\n";
            echo "Ahora reenvía una plantilla y revisa wa_analizar.php\n";
        } else {
            echo "→ Si falla, el token no tiene permiso sobre ese WABA o el waba_id es incorrecto.\n";
        }
    } catch (Throwable $e) {
        echo 'ERROR: '.$e->getMessage()."\n";
    }
    exit;
}

if ($mode === 'ping') {
    $url = $base.'/'.$version.'/'.$phoneId;
    echo "GET {$url}\n\n";
    try {
        echo "--- Prueba 1: sin fields (objeto completo) ---\n";
        $r = $http()->get($url);
        echo 'HTTP '.$r->status()."\n";
        $json = $r->json();
        echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";

        if ($r->successful()) {
            echo "\n[OK] Red y token OK. Meta reconoce este ID.\n";
            if (is_array($json) && isset($json['display_phone_number'])) {
                echo 'display_phone_number: '.$json['display_phone_number']."\n";
            }
            if (is_array($json) && isset($json['verified_name'])) {
                echo 'verified_name: '.$json['verified_name']."\n";
            }
        } elseif ($r->status() === 400 && is_array($json) && isset($json['error']['message'])) {
            $msg = (string) $json['error']['message'];
            echo "\n[RED OK] Meta respondio (no es bloqueo de hosting).\n";
            if (str_contains($msg, 'nonexisting field')) {
                echo "El error suele ser el parametro ?fields= en la prueba, NO la red.\n";
                echo "Prueba 2: solo field id...\n\n";
                $r2 = $http()->get($url, ['fields' => 'id']);
                echo 'HTTP '.$r2->status()."\n";
                echo json_encode($r2->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
            }
            if (str_contains($msg, 'does not exist') || str_contains($msg, 'Unsupported get')) {
                echo "\n[ATENCION] WHATSAPP_PHONE_NUMBER_ID incorrecto en .env\n";
                echo "Meta > WhatsApp > API Setup > Phone number ID (no confundir con Business account ID).\n";
            }
        } elseif (in_array($r->status(), [401, 403], true)) {
            echo "\n[RED OK] Llega a Meta. Renueva ACCESS_TOKEN o permisos whatsapp_business_messaging.\n";
        } else {
            echo "\nRevisa el JSON de error arriba.\n";
        }
    } catch (Throwable $e) {
        echo "ERROR: ".$e->getMessage()."\n";
        if (stripos($e->getMessage(), 'timed out') !== false) {
            echo "\n→ Timeout: hosting no llega a graph.facebook.com.\n";
        }
    }
    exit;
}

if ($mode === 'verify_phone') {
  $compareRaw = trim((string) ($_GET['compare'] ?? ''));
  $idsToTest = $compareRaw !== ''
      ? array_values(array_filter(array_map(
          static fn (string $p) => preg_replace('/\D+/', '', trim($p)) ?? '',
          explode(',', $compareRaw)
      )))
      : [$phoneId];

  if ($idsToTest === [] || $idsToTest[0] === '') {
      exit("Indica phone_id=NUM o compare=id1,id2,id3\n");
  }

  echo "=== Verificar Phone Number ID (envio POST /messages) ===\n";
  echo "Token: ".substr($token, 0, 6)."…\n";
  echo "API: {$base}/{$version}\n\n";

  if ($wabaId !== '') {
      $listUrl = $base.'/'.$version.'/'.$wabaId.'/phone_numbers';
      echo "--- Lista oficial (WABA) GET {$listUrl} ---\n";
      try {
          $lr = $http()->get($listUrl, ['fields' => 'id,display_phone_number,verified_name,quality_rating']);
          echo 'HTTP '.$lr->status()."\n";
          echo json_encode($lr->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n\n";
          if ($lr->successful()) {
              echo "→ El «id» de cada fila en data[] es el WHATSAPP_PHONE_NUMBER_ID correcto.\n\n";
          } elseif ($lr->status() === 400 || $lr->status() === 404) {
              echo "→ Si falla, {$wabaId} no es WABA ID o el token no tiene acceso a esa cuenta.\n\n";
          }
      } catch (Throwable $e) {
          echo 'ERROR: '.$e->getMessage()."\n\n";
      }
  }

  $testTo = preg_replace('/\D+/', '', trim((string) ($_GET['to'] ?? '526121684390'))) ?? '526121684390';
  $tplName = trim((string) ($_GET['name'] ?? config('services.whatsapp.templates.recepcion', 'orden_recepcion')));

  foreach ($idsToTest as $testId) {
      echo str_repeat('=', 60)."\n";
      echo "ID probado: {$testId}\n";
      echo str_repeat('=', 60)."\n";

      $nodeUrl = $base.'/'.$version.'/'.$testId;
      $messagesUrl = $nodeUrl.'/messages';
      $score = 0;
      $verdict = 'NO usar — no es Phone Number ID para enviar';

      // A) GET sin fields
      echo "\n[A] GET objeto (sin fields)\n";
      try {
          $g = $http()->get($nodeUrl);
          $gj = $g->json();
          echo 'HTTP '.$g->status()."\n";
          echo json_encode($gj, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
          if ($g->successful()) {
              $score += 1;
              if (is_array($gj) && isset($gj['display_phone_number'])) {
                  $score += 2;
                  echo "  + Tiene display_phone_number (buena señal de numero WA)\n";
              }
              if (is_array($gj) && isset($gj['verified_name'])) {
                  $score += 1;
              }
          }
      } catch (Throwable $e) {
          echo 'ERROR: '.$e->getMessage()."\n";
      }

      // B) GET campos de numero
      echo "\n[B] GET ?fields=id,display_phone_number,verified_name\n";
      try {
          $g2 = $http()->get($nodeUrl, [
              'fields' => 'id,display_phone_number,verified_name,quality_rating',
          ]);
          $g2j = $g2->json();
          echo 'HTTP '.$g2->status()."\n";
          if ($g2->successful()) {
              echo json_encode($g2j, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
              $score += 2;
          } else {
              echo json_encode($g2j, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
              if (is_array($g2j) && str_contains((string) ($g2j['error']['message'] ?? ''), 'nonexisting field')) {
                  echo "  → Probablemente NO es Phone Number ID (puede ser App ID u otro objeto).\n";
              }
          }
      } catch (Throwable $e) {
          echo 'ERROR: '.$e->getMessage()."\n";
      }

      // C) POST /messages (prueba real de envio)
      echo "\n[C] POST /messages (plantilla «{$tplName}» a {$testTo})\n";
      echo "    {$messagesUrl}\n";
      $payload = [
          'messaging_product' => 'whatsapp',
          'to' => $testTo,
          'type' => 'template',
          'template' => [
              'name' => $tplName,
              'language' => ['code' => $lang],
              'components' => [
                  [
                      'type' => 'body',
                      'parameters' => [
                          ['type' => 'text', 'text' => 'VERIFY-TEST'],
                          ['type' => 'text', 'text' => 'Recepcion'],
                      ],
                  ],
              ],
          ],
      ];
      try {
          $p = $http()->post($messagesUrl, $payload);
          $pj = $p->json();
          echo 'HTTP '.$p->status()."\n";
          echo json_encode($pj, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
          if ($p->successful() && isset($pj['messages'][0]['id'])) {
              $score += 10;
              $verdict = 'CORRECTO — usar este ID en WHATSAPP_PHONE_NUMBER_ID';
              echo "\n  *** wamid: ".$pj['messages'][0]['id']." ***\n";
              echo "  Revisa WhatsApp del {$testTo}. Si llego, este es el ID bueno.\n";
          } elseif (is_array($pj) && isset($pj['error'])) {
              $em = (string) ($pj['error']['message'] ?? '');
              $sub = (int) ($pj['error']['error_subcode'] ?? 0);
              if (str_contains($em, 'does not exist') || $sub === 33) {
                  $verdict = 'NO — ID incorrecto o token sin permiso sobre este numero';
              } elseif (str_contains(strtolower($em), 'template')) {
                  $verdict = 'ID puede ser OK — fallo plantilla (nombre/variables). Revisa plantilla en Meta.';
                  $score += 5;
              }
          }
      } catch (Throwable $e) {
          echo 'ERROR: '.$e->getMessage()."\n";
      }

      echo "\nPuntuacion interna: {$score}/16\n";
      echo "VEREDICTO: {$verdict}\n\n";
  }

  echo "=== Que poner en .env ===\n";
  echo "WHATSAPP_PHONE_NUMBER_ID=el_id_con_VEREDICTO_CORRECTO_y_wamid\n";
  echo "NO uses «Identificador de la app» (App ID).\n";
  echo "Si waba_id lista numeros, usa el id de data[], no el WABA id en PHONE_NUMBER_ID.\n";
  echo "\nURLs:\n";
  echo "  Un ID:  mode=verify_phone&phone_id=1964158600503621\n";
  echo "  Varios: mode=verify_phone&compare=1537852524373963,1964158600503621,61590273249931\n";
  echo "  + WABA: mode=verify_phone&waba_id=1964158600503621\n";
  echo "\n=== Fin ===\n";
  exit;
}

if ($phoneId === '') {
    exit("Falta phone_id en .env o en URL (?phone_id=)\n");
}

$to = preg_replace('/\D+/', '', trim((string) ($_GET['to'] ?? ''))) ?? '';
if ($to === '' && $mode !== 'ping') {
    exit("Falta &to=526121684390 (solo dígitos, con lada 52)\n");
}

$messagesUrl = $base.'/'.$version.'/'.$phoneId.'/messages';

if ($mode === 'text') {
    $body = trim((string) ($_GET['body'] ?? 'Prueba Exacto soporte'));
    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => $to,
        'type' => 'text',
        'text' => [
            'preview_url' => false,
            'body' => $body,
        ],
    ];
    echo "POST {$messagesUrl}\n";
    echo "Tipo: texto libre (requiere ventana 24 h — el cliente debe haberte escrito primero)\n\n";
} elseif ($mode === 'template' || $mode === 'template_doc') {
    $name = trim((string) ($_GET['name'] ?? config('services.whatsapp.templates.recepcion', 'orden_recepcion')));
    $p1 = trim((string) ($_GET['p1'] ?? 'OS-TEST-001'));
    $p2 = trim((string) ($_GET['p2'] ?? 'Recepción'));
    $components = [];

    if ($mode === 'template_doc') {
        $mediaUrl = $base.'/'.$version.'/'.$phoneId.'/media';
        // PDF mínimo válido para prueba de encabezado DOCUMENT.
        $pdfBinary = "%PDF-1.4\n"
            ."1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj\n"
            ."2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj\n"
            ."3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 300 144] /Contents 4 0 R /Resources<< /Font<< /F1 5 0 R >> >> >>endobj\n"
            ."4 0 obj<< /Length 44 >>stream\n"
            ."BT /F1 12 Tf 40 80 Td (Exacto WA test) Tj ET\n"
            ."endstream\nendobj\n"
            ."5 0 obj<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>endobj\n"
            ."xref\n0 6\n0000000000 65535 f \n"
            ."trailer<< /Size 6 /Root 1 0 R >>\nstartxref\n0\n%%EOF\n";
        $filename = 'prueba_exacto.pdf';

        echo "1) Subiendo PDF a Meta media...\n";
        echo "POST {$mediaUrl}\n";
        try {
            $up = $http()
                ->attach('file', $pdfBinary, $filename, ['Content-Type' => 'application/pdf'])
                ->post($mediaUrl, [
                    'messaging_product' => 'whatsapp',
                    'type' => 'application/pdf',
                ]);
        } catch (Throwable $e) {
            exit("ERROR subiendo PDF: ".$e->getMessage()."\n");
        }
        echo 'HTTP '.$up->status()."\n";
        $upJson = $up->json();
        echo json_encode($upJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n\n";
        $mediaId = is_array($upJson) ? trim((string) ($upJson['id'] ?? '')) : '';
        if ($mediaId === '') {
            exit("No se obtuvo media id. No se puede enviar plantilla con DOCUMENT.\n");
        }

        $components[] = [
            'type' => 'header',
            'parameters' => [[
                'type' => 'document',
                'document' => [
                    'id' => $mediaId,
                    'filename' => $filename,
                ],
            ]],
        ];
        echo "2) Enviando plantilla con media_id={$mediaId}\n";
    }

    $components[] = [
        'type' => 'body',
        'parameters' => [
            ['type' => 'text', 'text' => $p1],
            ['type' => 'text', 'text' => $p2],
        ],
    ];

    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => $to,
        'type' => 'template',
        'template' => [
            'name' => $name,
            'language' => ['code' => $lang],
            'components' => $components,
        ],
    ];
    echo "POST {$messagesUrl}\n";
    echo $mode === 'template_doc'
        ? "Tipo: plantilla «{$name}» CON PDF (upload media)\n\n"
        : "Tipo: plantilla «{$name}» solo body (sin PDF /media)\n\n";
} else {
    exit("Modo desconocido. Usa mode=ping | discover | verify_phone | register | subscribe | subscriptions | text | template | template_doc\n");
}

try {
    $r = $http()->post($messagesUrl, $payload);
    echo 'HTTP '.$r->status()."\n";
    $json = $r->json();
    echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n\n";

    if ($r->successful() && isset($json['messages'][0]['id'])) {
        echo "OK — wamid: ".$json['messages'][0]['id']."\n";
        echo "Revisa el WhatsApp del número {$to}.\n";
    } elseif (is_array($json) && isset($json['error'])) {
        $code = (int) ($json['error']['code'] ?? 0);
        $msg = (string) ($json['error']['message'] ?? '');
        echo "Meta rechazó: [{$code}] {$msg}\n";
        if ($mode === 'text' && ($code === 131047 || str_contains(strtolower($msg), '24 hour'))) {
            echo "\n→ Texto libre bloqueado: el cliente no te escribió en 24 h.\n";
            echo "  Para avisos de orden DEBES usar plantilla (mode=template).\n";
        }
        if (str_contains(strtolower($msg), 'template') || $code === 132000) {
            echo "\n→ La plantilla puede exigir encabezado PDF. Crea en Meta una plantilla\n";
            echo "  solo con body (2 variables) sin documento, p. ej. orden_recepcion_txt.\n";
        }
    }
} catch (Throwable $e) {
    echo "ERROR: ".$e->getMessage()."\n";
}

echo "\n=== Fin. BORRA public/wa_test_send.php ===\n";
