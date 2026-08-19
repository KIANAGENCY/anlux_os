<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ExactoVaultService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SeguridadController extends Controller
{
    public function __construct(
        private readonly ExactoVaultService $vault
    ) {}

    private function ensureSecurityTable(): void
    {
        if (! Schema::hasTable('security_activity_log')) {
            throw new \RuntimeException('Falta la tabla security_activity_log. Ejecuta las migraciones pendientes.');
        }
    }

    /** @return array<string,array{label:string,explanation:string,category:string}> */
    private function eventCatalog(): array
    {
        return [
            'login_correcto' => [
                'label' => 'Login correcto',
                'explanation' => 'Credenciales validadas y sesión iniciada.',
                'category' => 'login',
            ],
            'login_password_incorrecto' => [
                'label' => 'Contraseña incorrecta',
                'explanation' => 'Intento fallido de autenticación por contraseña inválida.',
                'category' => 'login',
            ],
            'login_usuario_no_encontrado' => [
                'label' => 'Usuario no encontrado',
                'explanation' => 'Intento de acceso con un identificador inexistente.',
                'category' => 'login',
            ],
            'login_csrf_invalido' => [
                'label' => 'CSRF inválido',
                'explanation' => 'Token CSRF ausente o inválido en el login.',
                'category' => 'login',
            ],
            'login_throttle_bloqueo' => [
                'label' => 'Bloqueo por intentos',
                'explanation' => 'Límite de intentos de login superado.',
                'category' => 'login',
            ],
            'acceso_sin_sesion' => [
                'label' => 'Acceso sin sesión',
                'explanation' => 'Intento de acceder a recurso protegido sin sesión válida.',
                'category' => 'acceso',
            ],
            'firewall_bloqueo' => [
                'label' => 'Firewall bloqueó petición',
                'explanation' => 'Petición rechazada por reglas del firewall de aplicación.',
                'category' => 'firewall',
            ],
            'impersonacion_solicitud' => [
                'label' => 'Solicitud impersonación',
                'explanation' => 'Un administrador solicitó acceder a la cuenta de un técnico.',
                'category' => 'acceso',
            ],
            'impersonacion_aprobada' => [
                'label' => 'Impersonación aprobada',
                'explanation' => 'Un técnico autorizó el acceso del administrador a otra cuenta.',
                'category' => 'acceso',
            ],
            'impersonacion_rechazada' => [
                'label' => 'Impersonación rechazada',
                'explanation' => 'Un técnico rechazó el acceso del administrador a otra cuenta.',
                'category' => 'acceso',
            ],
            'impersonacion_inicio' => [
                'label' => 'Impersonación iniciada',
                'explanation' => 'El administrador entró a operar como técnico.',
                'category' => 'acceso',
            ],
            'impersonacion_fin' => [
                'label' => 'Impersonación finalizada',
                'explanation' => 'El administrador volvió a su cuenta.',
                'category' => 'acceso',
            ],
        ];
    }

    private function eventLabel(string $eventType): string
    {
        $cat = $this->eventCatalog();

        return $cat[$eventType]['label'] ?? ucwords(str_replace('_', ' ', $eventType));
    }

    private function eventExplanation(string $eventType): string
    {
        $cat = $this->eventCatalog();

        return $cat[$eventType]['explanation'] ?? 'Evento no catalogado: revisar origen y contexto.';
    }

    private function eventCategory(string $eventType): string
    {
        $cat = $this->eventCatalog();
        if (isset($cat[$eventType]['category'])) {
            return $cat[$eventType]['category'];
        }
        if (str_starts_with($eventType, 'login_')) {
            return 'login';
        }
        if (str_contains($eventType, 'firewall')) {
            return 'firewall';
        }
        if (str_contains($eventType, 'acceso') || str_contains($eventType, 'sesion')) {
            return 'acceso';
        }

        return 'otro';
    }

    private function formatLocalDateTime(mixed $value): string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }

        try {
            return Carbon::parse($raw)->timezone(config('app.timezone'))->format('d/m/Y H:i:s');
        } catch (\Throwable) {
            return $raw;
        }
    }

    private function detailsPreview(?string $json, int $maxLen = 140): string
    {
        $raw = trim((string) $json);
        if ($raw === '') {
            return '';
        }
        $arr = json_decode($raw, true);
        if (is_array($arr)) {
            $parts = [];
            foreach (['usuario', 'id_tecnico', 'motivo', 'http_code', 'code', 'path'] as $key) {
                if (array_key_exists($key, $arr) && $arr[$key] !== '' && $arr[$key] !== null) {
                    $parts[] = $key.': '.(is_scalar($arr[$key]) ? (string) $arr[$key] : json_encode($arr[$key], JSON_UNESCAPED_UNICODE));
                }
            }
            $line = $parts !== [] ? implode(' · ', $parts) : (json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
            if (mb_strlen($line, 'UTF-8') > $maxLen) {
                return mb_substr($line, 0, $maxLen - 3, 'UTF-8').'...';
            }

            return $line;
        }
        if (mb_strlen($raw, 'UTF-8') > $maxLen) {
            return mb_substr($raw, 0, $maxLen - 3, 'UTF-8').'...';
        }

        return $raw;
    }

    public function index(Request $request): View
    {
        $user = Auth::user();
        abort_unless($user, 403);
        $this->ensureSecurityTable();

        $totalOrdenes = (int) (DB::scalar('SELECT COUNT(*) FROM orden_servicio_c') ?? 0);
        $totalActividades = (int) (DB::scalar('SELECT COUNT(*) FROM security_activity_log') ?? 0);
        $actividad24h = (int) (DB::scalar('SELECT COUNT(*) FROM security_activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)') ?? 0);
        $alertas24h = (int) (DB::scalar("SELECT COUNT(*) FROM security_activity_log WHERE severity IN ('warning','critical') AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)") ?? 0);
        $bloqueos24h = (int) (DB::scalar("SELECT COUNT(*) FROM security_activity_log WHERE event_type = 'firewall_bloqueo' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)") ?? 0);
        $secretActivo = $this->vault->telefonoSecretBin() !== null;

        $eventType = trim((string) $request->query('event_type', ''));
        $severity = trim((string) $request->query('severity', ''));
        $ip = trim((string) $request->query('ip', ''));

        $where = ['1=1'];
        $params = [];
        if ($eventType !== '') {
            $where[] = 'event_type = ?';
            $params[] = $eventType;
        }
        if ($severity !== '') {
            $where[] = 'severity = ?';
            $params[] = $severity;
        }
        if ($ip !== '') {
            $where[] = 'ip LIKE ?';
            $params[] = $ip;
        }
        $whereSql = implode(' AND ', $where);
        $recentRaw = DB::select("SELECT * FROM security_activity_log WHERE {$whereSql} ORDER BY created_at DESC LIMIT 100", $params);
        $eventTypes = DB::select('SELECT DISTINCT event_type FROM security_activity_log ORDER BY event_type ASC');
        $suspiciousIps = DB::select(
            "SELECT ip, COUNT(*) AS eventos,
                    SUM(CASE WHEN event_type LIKE 'login_%' AND event_type <> 'login_correcto' THEN 1 ELSE 0 END) AS fallos_login,
                    SUM(CASE WHEN event_type = 'firewall_bloqueo' THEN 1 ELSE 0 END) AS bloqueos,
                    MAX(created_at) AS ultimo_evento
             FROM security_activity_log
             WHERE severity IN ('warning','critical')
               AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
               AND ip IS NOT NULL AND ip <> ''
             GROUP BY ip
             HAVING eventos >= 3 OR fallos_login >= 3 OR bloqueos >= 1
             ORDER BY eventos DESC, ultimo_evento DESC
             LIMIT 10"
        );
        $recent = array_map(function ($event): array {
            $ev = (array) $event;
            $type = (string) ($ev['event_type'] ?? '');
            $ev['event_label'] = $this->eventLabel($type);
            $ev['event_explanation'] = $this->eventExplanation($type);
            $ev['event_category'] = $this->eventCategory($type);
            $ev['details_preview'] = $this->detailsPreview(isset($ev['details']) ? (string) $ev['details'] : null);
            $ev['created_at'] = $this->formatLocalDateTime($ev['created_at'] ?? null);

            return $ev;
        }, $recentRaw);

        $suspiciousIps = array_map(function ($row): array {
            $r = (array) $row;
            $r['ultimo_evento'] = $this->formatLocalDateTime($r['ultimo_evento'] ?? null);

            return $r;
        }, $suspiciousIps);

        return view('admin.seguridad', [
            'pageTitle' => 'Seguridad / Actividad - Exacto',
            'nombreTecnico' => htmlspecialchars((string) (session('nombre_tecnico') ?? $user->nombre_tecnico ?? ''), ENT_QUOTES, 'UTF-8'),
            'nav_admin_activo' => 'seguridad_actividad',
            'totalOrdenes' => $totalOrdenes,
            'totalActividades' => $totalActividades,
            'actividad24h' => $actividad24h,
            'alertas24h' => $alertas24h,
            'bloqueos24h' => $bloqueos24h,
            'secretActivo' => $secretActivo,
            'eventType' => $eventType,
            'severity' => $severity,
            'ip' => $ip,
            'recent' => $recent,
            'eventTypes' => $eventTypes,
            'suspiciousIps' => $suspiciousIps,
        ]);
    }
}
