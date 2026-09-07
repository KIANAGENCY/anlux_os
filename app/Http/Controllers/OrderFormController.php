<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AnluxVaultService;
use App\Services\EquipoEntregaResolver;
use App\Services\FolioSequenceService;
use App\Services\OrdenEditLockService;
use App\Services\OrdenPolicyService;
use App\Services\PdfCondicionesService;
use App\Services\RegistrarOrdenService;
use App\Services\SersopCatalogService;
use App\Support\AnluxAuthContext;
use App\Support\MaterialesOrdenClassifier;
use App\Support\OrderStatus;
use App\Support\TipoServicioCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class OrderFormController extends Controller
{
    public function __construct(
        private readonly AnluxVaultService $vault,
        private readonly OrdenPolicyService $policy,
        private readonly SersopCatalogService $sersopCatalog,
        private readonly PdfCondicionesService $pdfCondiciones,
        private readonly OrdenEditLockService $editLocks,
        private readonly RegistrarOrdenService $registrarOrden,
        private readonly FolioSequenceService $folios,
        private readonly EquipoEntregaResolver $entregaResolver
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        return $this->renderForm($request, 0);
    }

    public function edit(Request $request, int $id): View|RedirectResponse
    {
        return $this->renderForm($request, $id);
    }

    /** @return list<string> */
    private function parseObservacionesItems(?string $stored): array
    {
        $stored = trim((string) $stored);
        if ($stored === '') {
            return [];
        }
        $parts = preg_split('/,\s*(?=\d+\.\s)/', $stored) ?: [];
        $out = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p === '') {
                continue;
            }
            if (preg_match('/^\d+\.\s*(.*)$/s', $p, $m)) {
                $out[] = $m[1];
            } else {
                $out[] = $p;
            }
        }

        return $out;
    }

    private function renderForm(Request $request, int $idEditar): View|RedirectResponse
    {
        $user = AnluxAuthContext::currentUser();
        abort_unless($user !== null, 403);

        if ($request->routeIs('orden_servicio.create') && $request->filled('id')) {
            $idEditar = (int) $request->query('id', 0);
        }
        $vistaDesdeTablaOrdenes = $request->query('ref') === 'ordenes';

        $serviciosSersopActivos = $this->sersopCatalog->active();

        $cab = [];
        $ordenExistenteJson = null;
        $folioActual = '';

        if ($idEditar > 0) {
            $row = DB::selectOne('SELECT * FROM orden_servicio_c WHERE id_orden_c = ?', [$idEditar]);
            if (! $row) {
                return redirect()->route('ordenes.index');
            }
            if (! $this->policy->userCanAccessOrder($user, $idEditar)) {
                return redirect()->route('ordenes.index');
            }
            $ordenEntregadaSoloLectura = OrderStatus::isEntregado((string) ($row->estatus ?? ''));
            if (! $ordenEntregadaSoloLectura) {
                if ($this->editLocks->tableExists()) {
                    $lockResult = $this->editLocks->acquire($idEditar, $user);
                } else {
                    $lockResult = ['acquired' => true];
                }
                if (! $lockResult['acquired']) {
                    $holder = (string) ($lockResult['holder_nombre'] ?? 'otro usuario');

                    return redirect()
                        ->route('ordenes.index')
                        ->with('error', 'Esta orden está en edición por '.$holder.'. Espera a que termine o pídele que cierre el formulario.');
                }
                // Quien abre la orden queda en Involucrados (si no es el mismo del último registro).
                try {
                    $nombreApertura = AnluxAuthContext::nombreTecnicoSesionActual($user);
                    if ($nombreApertura !== '') {
                        $this->registrarOrden->registrarInvolucradoSiCambio(
                            $idEditar,
                            (string) ($row->estatus ?? 'Edición'),
                            $nombreApertura
                        );
                    }
                } catch (\Throwable $e) {
                    report($e);
                }
            }
            $cab = (array) $row;
            $cab['nombre_cliente'] = $this->vault->nombreClienteReveal($cab['nombre_cliente'] ?? null);
            $cab['cliente_recibido'] = $this->vault->nombreClienteReveal($cab['cliente_recibido'] ?? null);
            $cab['telefono'] = $this->vault->telefonoReveal($cab['telefono'] ?? null);
            $cab['direccion'] = $this->vault->direccionReveal($cab['direccion'] ?? null);
            $cab['correo'] = $this->vault->correoReveal($cab['correo'] ?? null);
            $cab['poblacion'] = $this->vault->poblacionReveal($cab['poblacion'] ?? null);
            $folioActual = (string) ($cab['folio'] ?? '');

            $t = DB::selectOne('SELECT * FROM orden_servicio_t WHERE id_orden_c = ? ORDER BY id_trabajo ASC LIMIT 1', [$idEditar]);
            $tArr = $t ? (array) $t : null;

            $equiposDb = DB::select('SELECT * FROM equipos_orden WHERE id_orden_c = ? ORDER BY id_equipo ASC', [$idEditar]);
            $entregasResueltas = $this->entregaResolver->resolveAll(
                $idEditar,
                $equiposDb,
                array_merge($cab, [
                    'recibido_cliente' => $tArr['recibido_cliente'] ?? null,
                    'entregado_por_tecnico' => $tArr['entregado_por_tecnico'] ?? null,
                    'firma_c_r' => $tArr['firma_c_r'] ?? null,
                    'firma_t_e' => $tArr['firma_t_e'] ?? null,
                ])
            );

            $trabajosDb = [];
            $materialesDb = [];
            $anticiposDb = [];
            $abonoSaldoDb = 0.0;
            $idTr = 0;
            if ($tArr && ! empty($tArr['id_trabajo'])) {
                $idTr = (int) $tArr['id_trabajo'];
                $trabajosDb = DB::select(
                    'SELECT clave, descripcion, importe, ticket, id_equipo FROM trabajos_orden WHERE id_trabajo = ? ORDER BY id_trabajo_detalle ASC',
                    [$idTr]
                );
                $materialesRaw = DB::select('SELECT vale, codigo, cantidad, descripcion, anticipo, precio_unitario, importe, ticket, id_equipo FROM materiales_orden WHERE id_trabajo = ? ORDER BY id_material ASC', [$idTr]);
                foreach ($materialesRaw as $materialRaw) {
                    $materialArr = (array) $materialRaw;
                    $anticipoMaterial = (float) ($materialArr['anticipo'] ?? 0);
                    if (class_exists(MaterialesOrdenClassifier::class)) {
                        $tipoFila = MaterialesOrdenClassifier::classify($materialArr);
                        if ($tipoFila === 'abono') {
                            $abonoSaldoDb += $anticipoMaterial;
                        } elseif ($tipoFila === 'anticipo') {
                            $anticiposDb[] = MaterialesOrdenClassifier::toAnticipoPayload($materialArr);
                        } else {
                            if ($anticipoMaterial > 0.009) {
                                $anticiposDb[] = MaterialesOrdenClassifier::toAnticipoPayload($materialArr);
                            }
                            $materialArr['anticipo'] = 0;
                            $materialesDb[] = $materialArr;
                        }
                        continue;
                    }
                    // Fallback si falta el helper en el servidor.
                    $descUp = mb_strtoupper(trim((string) ($materialArr['descripcion'] ?? '')), 'UTF-8');
                    $ticketUp = mb_strtoupper(trim((string) ($materialArr['ticket'] ?? '')), 'UTF-8');
                    if (
                        in_array($descUp, ['SALDO LIQUIDADO', 'ABONO SALDO'], true)
                        || in_array($ticketUp, ['SALDO LIQUIDADO', 'ABONO SALDO', 'ABONO SALDO PENDIENTE', 'PAGO SALDO PENDIENTE'], true)
                    ) {
                        $abonoSaldoDb += $anticipoMaterial;
                    } elseif ($anticipoMaterial > 0.009) {
                        $anticiposDb[] = [
                            'folio' => (string) ($materialArr['vale'] ?? ''),
                            'descripcion' => $descUp === 'ANTICIPO' ? 'ANTICIPO' : (string) ($materialArr['descripcion'] ?? ''),
                            'monto' => $anticipoMaterial,
                            'ticket' => (string) ($materialArr['ticket'] ?? ''),
                            'id_equipo' => $materialArr['id_equipo'] ?? null,
                        ];
                    } else {
                        $materialesDb[] = $materialArr;
                    }
                }
            }

            $equiposOut = [];
            foreach ($equiposDb as $idx => $eq) {
                $e = (array) $eq;
                $entrega = $entregasResueltas[$idx + 1] ?? [];
                $desc = $e['descripcion_falla'] ?? ($e['descripcion'] ?? '');
                $equiposOut[] = [
                    'id_equipo' => $e['id_equipo'] ?? null,
                    'marca' => $e['marca'] ?? '',
                    'modelo' => $e['modelo'] ?? '',
                    'serie' => $e['serie'] ?? '',
                    'clave' => $e['clave'] ?? '',
                    'tipo_servicio' => $e['tipo_servicio'] ?? '',
                    'descripcion_falla' => $desc,
                    'acciones' => $e['acciones'] ?? 0,
                    'entrega_receptor_tipo' => $entrega['receptor_tipo'] ?? null,
                    'entrega_recibido_cliente' => $entrega['receptor'] ?? null,
                    'entrega_fecha' => $entrega['fecha_entrega'] ?? null,
                    'entrega_tecnico' => $entrega['tecnico'] ?? null,
                ];
            }

            $payload = [
                'id_orden_c' => $idEditar,
                'cab' => $cab,
                't' => $tArr,
                'equipos' => $equiposOut,
                'trabajos' => array_map(fn ($x) => (array) $x, $trabajosDb),
                'materiales' => array_map(fn ($x) => (array) $x, $materialesDb),
                'anticipos' => $anticiposDb,
                'abono_saldo' => $abonoSaldoDb,
                'observaciones_items' => $this->parseObservacionesItems($cab['observaciones'] ?? ''),
                'firmas' => [
                    'firma_c_e' => $this->vault->firmaRutaReveal($cab['firma_c_e'] ?? null),
                    'firma_t_r' => $this->vault->firmaRutaReveal($cab['firma_t_r'] ?? null),
                    'firma_c_r' => ($tArr && isset($tArr['firma_c_r'])) ? $this->vault->firmaRutaReveal($tArr['firma_c_r']) : null,
                    'firma_t_e' => ($tArr && isset($tArr['firma_t_e'])) ? $this->vault->firmaRutaReveal($tArr['firma_t_e']) : null,
                    'firma_c_salida_temp' => $this->vault->firmaRutaReveal($cab['firma_c_salida_temp'] ?? null),
                    'firma_t_salida_temp' => $this->vault->firmaRutaReveal($cab['firma_t_salida_temp'] ?? null),
                ],
                'salida_temporal' => [
                    'activa' => (int) ($cab['salida_temporal_activa'] ?? 0) === 1,
                    'fecha_salida' => $cab['fecha_salida_temporal'] ?? null,
                    'fecha_regreso' => $cab['fecha_regreso_temporal'] ?? null,
                    'motivo' => (string) ($cab['motivo_salida_temporal'] ?? ''),
                ],
            ];
            if ($tArr) {
                $entregasCompletadas = array_filter(
                    $entregasResueltas,
                    static fn (array $entrega): bool => (int) ($entrega['acciones'] ?? 0) === 2
                );
                $resumenReceptores = $this->entregaResolver->resumenReceptores($entregasCompletadas);
                $payload['t']['recibido_cliente'] = $resumenReceptores !== ''
                    ? $resumenReceptores
                    : $this->vault->nombreClienteReveal($tArr['recibido_cliente'] ?? null);
            }
            $ordenExistenteJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        } else {
            try {
                $folioActual = $this->folios->peekNextFolio((int) date('Y'));
            } catch (\Exception) {
                $folioActual = 'OS-'.date('Y').'-001';
            }
        }

        $mostrarDiagVault = (string) $request->query('verificar_vault', '') === '1';
        $vaultDiagSim = null;
        $vaultDiagOrdenDb = null;
        if ($mostrarDiagVault) {
            $telEj = '5512345678';
            $dirEj = 'Calle de prueba 123, Centro';
            $telS = $this->vault->telefonoSeal($telEj);
            $dirS = $this->vault->direccionSeal($dirEj);
            $vaultDiagSim = [
                'secreto_env' => $this->vault->telefonoSecretBin() !== null,
                'tel_es_v1' => $this->vault->isSealed($telS),
                'tel_muestra' => substr($telS, 0, 36).(strlen($telS) > 36 ? '…' : ''),
                'tel_roundtrip_ok' => $this->vault->telefonoReveal($telS) === $telEj,
                'dir_es_v1' => $this->vault->isSealed($dirS),
                'dir_muestra' => substr($dirS, 0, 36).(strlen($dirS) > 36 ? '…' : ''),
                'dir_roundtrip_ok' => $this->vault->direccionReveal($dirS) === $dirEj,
            ];
            if ($idEditar > 0) {
                $vaultDiagOrdenDb = (array) DB::selectOne('SELECT telefono, direccion FROM orden_servicio_c WHERE id_orden_c = ?', [$idEditar]);
            }
        }

        $estatusFormValor = 'Recepcion';
        if ($idEditar > 0) {
            $estatusFormValor = (string) ($cab['estatus'] ?? 'Recepcion');
        }
        $estatusFormKey = mb_strtolower(trim($estatusFormValor), 'UTF-8');
        $estatusFormKeyCompact = preg_replace('/\s+/u', '', $estatusFormKey) ?? $estatusFormKey;
        $estatusFormMapa = [
            'rojo' => 'Recepcion',
            'recepción' => 'Recepcion',
            'recepcion' => 'Recepcion',
            'naranja' => 'En proceso',
            'en proceso' => 'En proceso',
            'enproceso' => 'En proceso',
            'proceso' => 'En proceso',
            'amarillo' => 'Terminado',
            'terminado' => 'Terminado',
            'verde' => 'Entregado',
            'entregado' => 'Entregado',
        ];
        $estatusFormValor = $estatusFormMapa[$estatusFormKey]
            ?? $estatusFormMapa[$estatusFormKeyCompact]
            ?? 'Recepcion';
        $estatusOrdenFlujo = ['Recepcion', 'En proceso', 'Terminado', 'Entregado'];
        $estatusOptionStyles = [
            'Recepcion' => 'background-color:#fee2e2;color:#991b1b;',
            'En proceso' => 'background-color:#ffedd5;color:#9a3412;',
            'Terminado' => 'background-color:#fef3c7;color:#92400e;',
            'Entregado' => 'background-color:#dcfce7;color:#166534;',
        ];
        $estatusFormIndice = array_search($estatusFormValor, $estatusOrdenFlujo, true);
        if ($estatusFormIndice === false) {
            $estatusFormIndice = 0;
        }

        $modoSoloCompletar = ($idEditar > 0 && $vistaDesdeTablaOrdenes);
        $soloLecturaEntregado = $idEditar > 0 && OrderStatus::isEntregado((string) ($cab['estatus'] ?? ''));
        // En solo lectura se precargan firmas para verlas; el JS bloquea edición.
        $firmasDeshabilitadas = false;
        $salidaTemporalActiva = $idEditar > 0 && (int) ($cab['salida_temporal_activa'] ?? 0) === 1;
        $motivoSalidaTemporal = $idEditar > 0 ? trim((string) ($cab['motivo_salida_temporal'] ?? '')) : '';
        $fechaSalidaTemporal = $idEditar > 0 ? (string) ($cab['fecha_salida_temporal'] ?? '') : '';
        $salidaTemporalIdEquipo = $idEditar > 0 && Schema::hasColumn('orden_servicio_c', 'salida_temporal_id_equipo')
            ? (int) ($cab['salida_temporal_id_equipo'] ?? 0)
            : 0;

        $registrarOrdenUrl = rtrim($request->root(), '/').'/api/ordenes/registrar';
        $reenviarOrdenUrl = $idEditar > 0
            ? rtrim($request->root(), '/').'/api/ordenes/'.((int) $idEditar).'/reenviar'
            : '';
        $salidaTemporalUrl = $idEditar > 0
            ? rtrim($request->root(), '/').'/api/ordenes/'.((int) $idEditar).'/salida-temporal'
            : '';
        $regresoTemporalUrl = $idEditar > 0
            ? rtrim($request->root(), '/').'/api/ordenes/'.((int) $idEditar).'/regreso-temporal'
            : '';

        $nombreTecnico = htmlspecialchars((string) (session('nombre_tecnico') ?? $user->nombre_tecnico ?? ''), ENT_QUOTES, 'UTF-8');

        $pageTitle = $soloLecturaEntregado
            ? 'Orden entregada (solo lectura) - Anlux'
            : ($modoSoloCompletar ? 'Completar orden - Anlux' : 'Orden de Servicio Técnico - Anlux');
        $pageHeadExtra = '
    <script>
        window.ANLUX_ORDEN_FIRMAS_DESHABILITADAS = '.(! empty($firmasDeshabilitadas) ? 'true' : 'false').';
        window.ANLUX_ORDEN_SOLO_LECTURA = '.($soloLecturaEntregado ? 'true' : 'false').';
        window.ANLUX_ORDEN_MODO_COMPLETAR = '.($modoSoloCompletar ? 'true' : 'false').';
        window.ANLUX_REGISTRAR_ORDEN_URL = '.json_encode($registrarOrdenUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE).';
        window.ANLUX_REENVIAR_URL = '.json_encode($reenviarOrdenUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE).';
        window.ANLUX_SALIDA_TEMPORAL_URL = '.json_encode($salidaTemporalUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE).';
        window.ANLUX_REGRESO_TEMPORAL_URL = '.json_encode($regresoTemporalUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE).';
        window.ANLUX_SALIDA_TEMPORAL_ACTIVA = '.($salidaTemporalActiva ? 'true' : 'false').';
        window.ANLUX_SERVICIOS_SERSOP = '.json_encode($serviciosSersopActivos, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE).';
        window.ANLUX_TIPOS_SERVICIO = '.json_encode(TipoServicioCatalog::values(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE).';
    </script>
';

        $tiposServicio = TipoServicioCatalog::values();

        $condicionesEntregaLineas = $this->pdfCondiciones->linesForDisplay();

        $modoReact = 'nueva';
        if ($soloLecturaEntregado) {
            $modoReact = 'solo_lectura';
        } elseif ($modoSoloCompletar) {
            $modoReact = 'completar';
        } elseif ($idEditar > 0) {
            $modoReact = 'editar';
        }

        $root = rtrim($request->root(), '/');
        $ordenPayload = null;
        if ($ordenExistenteJson !== null) {
            $decoded = json_decode($ordenExistenteJson, true);
            $ordenPayload = is_array($decoded) ? $decoded : null;
        }

        $reactPageProps = [
            'meta' => [
                'id_orden_c' => $idEditar > 0 ? $idEditar : 0,
                'modo' => $modoReact,
                'folio_preview' => (string) $folioActual,
                'nombre_tecnico' => html_entity_decode((string) $nombreTecnico, ENT_QUOTES, 'UTF-8'),
                'firmas_deshabilitadas' => (bool) $firmasDeshabilitadas,
                'csrf' => (string) csrf_token(),
            ],
            'urls' => [
                'registrar' => $registrarOrdenUrl,
                'reenviar' => $reenviarOrdenUrl,
                'salida_temporal' => $salidaTemporalUrl,
                'regreso_temporal' => $regresoTemporalUrl,
                'lock_heartbeat' => $idEditar > 0 ? $root.'/api/ordenes/'.$idEditar.'/lock/heartbeat' : '',
                'lock_release' => $idEditar > 0 ? $root.'/api/ordenes/'.$idEditar.'/lock/release' : '',
                'ordenes_index' => $root.'/ordenes',
                'pdf' => $idEditar > 0 ? $root.'/pdf/orden/'.$idEditar : $root.'/pdf/orden/{id}',
            ],
            'catalogs' => [
                'tipos_servicio' => array_values($tiposServicio),
                'servicios_sersop' => array_values($serviciosSersopActivos),
                'condiciones_entrega' => array_values($condicionesEntregaLineas),
                'estatus_flujo' => array_values($estatusOrdenFlujo),
            ],
            'flags' => [
                'salida_temporal_activa' => (bool) $salidaTemporalActiva,
                'motivo_salida_temporal' => (string) $motivoSalidaTemporal,
                'fecha_salida_temporal' => (string) $fechaSalidaTemporal,
                'salida_temporal_id_equipo' => $salidaTemporalIdEquipo,
            ],
            'orden' => $ordenPayload,
        ];

        return view('orders.orden_page', compact(
            'nombreTecnico',
            'idEditar',
            'vistaDesdeTablaOrdenes',
            'cab',
            'ordenExistenteJson',
            'folioActual',
            'serviciosSersopActivos',
            'mostrarDiagVault',
            'vaultDiagSim',
            'vaultDiagOrdenDb',
            'estatusFormValor',
            'estatusOptionStyles',
            'estatusOrdenFlujo',
            'estatusFormIndice',
            'modoSoloCompletar',
            'firmasDeshabilitadas',
            'soloLecturaEntregado',
            'salidaTemporalActiva',
            'motivoSalidaTemporal',
            'fechaSalidaTemporal',
            'registrarOrdenUrl',
            'pageTitle',
            'pageHeadExtra',
            'condicionesEntregaLineas',
            'tiposServicio',
            'reactPageProps'
        ));
    }
}
