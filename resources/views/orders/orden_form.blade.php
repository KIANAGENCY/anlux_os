<body
    class="
      px-3 py-4 sm:p-6 lg:p-8
      bg-blue-50
    "
  >
    <div
      class="
        max-w-7xl w-full

        mx-auto p-4 sm:p-6 lg:p-8
        bg-white
        rounded-lg
        shadow-lg
      "
    >
        @php
            $nav_activo = 'orden_servicio';
        @endphp
        @include('partials.nav-app')

        @include('partials.header-flujo-tres')

        <?php if (!empty($soloLecturaEntregado)): ?>
        <div class="mb-4 rounded-lg border-2 border-emerald-500 bg-emerald-50 px-4 py-3 text-sm text-emerald-950 shadow-sm">
            <p class="font-bold text-base">
                <i class="fas fa-eye mr-2 text-emerald-700"></i>Orden entregada — solo lectura
            </p>
            <p class="mt-1">Puedes revisar toda la orden de punta a punta. No se puede editar ni guardar cambios.</p>
        </div>
        <?php endif; ?>

        <?php if (!empty($salidaTemporalActiva) && empty($soloLecturaEntregado)): ?>
        {{-- Colores INLINE: en servidor el CSS compilado a veces no trae bg-orange-* y el banner se ve blanco --}}
        <div id="bannerSalidaTemporal" class="mb-4 flex flex-col gap-3 rounded-lg px-4 py-3 text-sm shadow-sm sm:flex-row sm:items-center sm:justify-between" style="border:2px solid #fb923c;background:#fff7ed;color:#7c2d12;">
            <div class="flex-1 min-w-0">
                <p class="font-bold text-base" style="color:#9a3412;margin:0;">
                    <i class="fas fa-truck-loading mr-2" style="color:#ea580c;"></i>Equipo en salida temporal
                </p>
                <p class="mt-1" style="margin:0.35rem 0 0;color:#7c2d12;">
                    <?php if (!empty($fechaSalidaTemporal)): ?>
                        Salida: <strong><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($fechaSalidaTemporal)), ENT_QUOTES, 'UTF-8'); ?></strong>.
                    <?php endif; ?>
                    El estatus sigue en <strong>En proceso</strong>. Registra el regreso cuando el cliente vuelva a dejar el equipo.
                </p>
                <?php if (!empty($motivoSalidaTemporal)): ?>
                <p class="mt-2 text-xs sm:text-sm" style="margin:0.5rem 0 0;color:#7c2d12;"><strong>Motivo:</strong> <?php echo nl2br(e($motivoSalidaTemporal)); ?></p>
                <?php endif; ?>
            </div>
            <button type="button" id="btnRegresoTemporal" class="shrink-0 rounded-lg px-4 py-2.5 text-sm font-bold shadow-sm" style="border:2px solid #c2410c;background:#ea580c;color:#fff;">
                <i class="fas fa-undo mr-2"></i>Registrar regreso
            </button>
        </div>
        <?php endif; ?>

        <?php if (!empty($firmasDeshabilitadas) && empty($soloLecturaEntregado)): ?>
        <div id="wrapBannerFirmasDeshabilitadas" class="mb-4 hidden flex-col gap-3 rounded-lg border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700 sm:flex-row sm:items-center sm:justify-between">
            <p class="flex-1">
                <i class="fas fa-ban mr-2 text-slate-600"></i><strong>Firmas deshabilitadas:</strong> no se muestran los lienzos ni se requiere trazo. Al guardar se envian imagenes vacias (PNG transparente) solo para completar el envio al servidor.
            </p>
            <button type="button" id="btnVolverLlenarActivarFirmas" class="shrink-0 rounded-lg border-2 border-blue-600 bg-white px-4 py-2.5 text-sm font-bold text-blue-800 shadow-sm hover:bg-blue-50">
                <i class="fas fa-redo mr-2"></i>Volver a llenar campos y activar firmas
            </button>
        </div>
        <?php endif; ?>

        <?php if ($mostrarDiagVault && $vaultDiagSim !== null): ?>
        <div class="mb-6 rounded-lg border-2 border-amber-500 bg-amber-50 p-4 text-sm text-amber-950 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <p class="font-bold text-base text-amber-900">
                    <i class="fas fa-flask mr-2"></i>Prueba de guardado cifrado (telefono y domicilio)
                </p>
                @php
                    $qs = request()->query();
                    unset($qs['verificar_vault']);
                    $sinPrueba = request()->url() . ($qs ? ('?' . http_build_query($qs)) : '');
                @endphp
                <a href="{{ $sinPrueba }}" class="shrink-0 text-amber-800 underline hover:text-amber-950 text-xs font-semibold">Quitar panel</a>
            </div>
            <p class="mt-2 text-xs text-amber-900">
                Con <code class="rounded bg-amber-100 px-1">EXACTO_TELEFONO_SECRET</code> en <code class="rounded bg-amber-100 px-1">.env</code>, al guardar la orden el servidor cifra telefono y direccion (prefijo <code class="rounded bg-amber-100 px-1">v1:</code>), no hash irreversible. Sin secreto, se guardan en claro.
            </p>
            <ul class="mt-3 list-disc space-y-1 pl-5 text-xs sm:text-sm">
                <li><strong>Secreto en .env:</strong> <?php echo $vaultDiagSim['secreto_env'] ? 'si (activo cifrado en ordenes)' : 'no (telefono y domicilio se guardarian en texto plano al enviar el formulario)'; ?></li>
                <li><strong>Ejemplo telefono 5512345678  sellado:</strong> prefijo v1: <?php echo $vaultDiagSim['tel_es_v1'] ? 'si' : 'no'; ?>
                    · muestra: <code class="break-all rounded bg-white/80 px-1 py-0.5 text-[11px]"><?php echo htmlspecialchars($vaultDiagSim['tel_muestra'], ENT_QUOTES, 'UTF-8'); ?></code>
                    · al descifrar coincide: <?php echo $vaultDiagSim['tel_roundtrip_ok'] ? 'si' : 'no'; ?></li>
                <li><strong>Ejemplo domicilio  sellado:</strong> prefijo v1: <?php echo $vaultDiagSim['dir_es_v1'] ? 'si' : 'no'; ?>
                    · muestra: <code class="break-all rounded bg-white/80 px-1 py-0.5 text-[11px]"><?php echo htmlspecialchars($vaultDiagSim['dir_muestra'], ENT_QUOTES, 'UTF-8'); ?></code>
                    · al descifrar coincide: <?php echo $vaultDiagSim['dir_roundtrip_ok'] ? 'si' : 'no'; ?></li>
                <?php if ($idEditar > 0 && is_array($vaultDiagOrdenDb)): ?>
                <li><strong>Esta orden en BD (sin descifrar):</strong>
                    telefono @php $v = app(\App\Services\ExactoVaultService::class); @endphp {{ $v->isSealed($vaultDiagOrdenDb['telefono'] ?? '') ? 'esta cifrado (v1:)' : 'parece texto plano o vacio' }},
                    direccion {{ $v->isSealed($vaultDiagOrdenDb['direccion'] ?? '') ? 'esta cifrada (v1:)' : 'parece texto plano o vacio' }}.
                    <span class="block mt-1 font-mono text-[10px] opacity-90 break-all">tel raw: <?php echo htmlspecialchars(substr((string)($vaultDiagOrdenDb['telefono'] ?? ''), 0, 80), ENT_QUOTES, 'UTF-8'); ?><?php echo strlen((string)($vaultDiagOrdenDb['telefono'] ?? '')) > 80 ? '' : ''; ?></span>
                </li>
                <?php elseif ($idEditar <= 0): ?>
                <li>Para ver como quedo una orden ya guardada, abre esta misma URL con <code class="rounded bg-amber-100 px-1">?id=N&amp;verificar_vault=1</code>.</li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form id="ordenForm" method="POST" action="<?php echo htmlspecialchars($registrarOrdenUrl, ENT_QUOTES, 'UTF-8'); ?>" novalidate
          class="
            space-y-8
          "
        >
            @csrf
            <input type="hidden" name="id_orden_c" id="id_orden_c" value="<?php echo $idEditar > 0 ? (int)$idEditar : ''; ?>">
            <input type="hidden" name="modo_completar" id="modo_completar" value="<?php echo $modoSoloCompletar ? '1' : '0'; ?>">
            {{-- Contenedor para cobro oculto SERSOP01 (en orden nueva la tabla de trabajos no se renderiza). --}}
            <div id="exactoSersop01Campos"></div>

            <?php if ($modoSoloCompletar): ?>
            <div class="mb-6 p-5 rounded-lg border-2 border-blue-600 bg-blue-50 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-left" style="text-transform: uppercase;">
                        <p class="text-base font-bold text-blue-900">Orden ya registrada en la tabla de ordenes</p>
                        <p class="mt-1 text-sm font-semibold text-blue-800">
                            Folio <strong><?php echo htmlspecialchars($cab['folio'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
                            · Cliente <strong><?php echo htmlspecialchars($cab['nombre_cliente'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
                            · Estatus <strong><?php echo htmlspecialchars(mb_strtoupper((string)($cab['estatus'] ?? ''), 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?></strong>
                        </p>
                        <p class="mt-2 text-sm text-blue-700">Los datos del cliente se visualizaran al imprimir el documento.</p>
                        <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center">
                            <button type="button" id="btnEditarDatosCliente" class="inline-flex items-center justify-center gap-2 rounded-lg border-2 px-4 py-2 text-sm font-bold shadow-sm" style="background-color:#ffffff;color:#1e40af;border-color:#2563eb;">
                                <i class="fas fa-user-pen"></i>Editar datos del cliente
                            </button>
                            <button type="button" id="btnReenviarRecepcion" class="inline-flex items-center justify-center gap-2 rounded-lg border-2 px-4 py-2 text-sm font-bold shadow-sm" style="background-color:#059669;color:#ffffff;border-color:#047857;">
                                <i class="fab fa-whatsapp" style="color:#ffffff;"></i>Reenviar WhatsApp/correo
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-blue-700">Usa "Reenviar" si el WhatsApp o el correo no llegaron (numero o correo equivocado). Se envia de nuevo como Recepcion.</p>
                    </div>
                    <div class="w-full sm:w-64">
                        <label for="inputEstatus" class="mb-1 block text-base font-bold text-blue-900">ESTATUS</label>
                        <select name="estatus" id="inputEstatus" class="w-full rounded-lg border-2 border-blue-300 bg-white px-4 py-3 text-base font-bold text-blue-900 shadow-sm focus:border-blue-700 focus:outline-none" style="text-transform: uppercase;">
                            <?php foreach ($estatusOrdenFlujo as $indiceEstatus => $opcionEstatus): ?>
                                <option
                                    value="<?php echo htmlspecialchars($opcionEstatus, ENT_QUOTES, 'UTF-8'); ?>"
                                    style="<?php echo htmlspecialchars($estatusOptionStyles[$opcionEstatus] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo $estatusFormValor === $opcionEstatus ? 'selected' : ''; ?>
                                    <?php echo $indiceEstatus < $estatusFormIndice ? 'disabled' : ''; ?>
                                >
                                    <?php echo htmlspecialchars(mb_strtoupper($opcionEstatus, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- SECCION 1: DATOS DEL CLIENTE -->
            <section
              id="seccionDatosCliente"
              class="
                p-4 sm:pl-6
                bg-blue-50
                border-l-4 border-blue-700 rounded-r-lg
                <?php echo $modoSoloCompletar ? 'hidden' : ''; ?>
              "
            >
                <h2
                  class="
                    flex
                    mb-6
                    text-xl sm:text-2xl font-bold text-blue-900
                    items-center
                  "
                >
                    <i
                      class="
                        mr-3
                        text-blue-700
                        fas fa-user-tie
                      "
                    ></i>DATOS DEL CLIENTE
                </h2>
                
                <div
                  class="
                    grid grid-cols-1
                    gap-6
                    md:grid-cols-2
                  "
                >
                    <div>
                        <label
                          class="
                            block
                            mb-2
                            text-sm font-semibold text-blue-900
                          "
                        >
                            Nombre o Razon Social *
                        </label>
                        <input 
                            type="text" 
                            name="nombreCliente"
                            id="nombreCliente"
                            required
                              class="
                                w-full
                                px-4 py-2
                                border-2 border-blue-300 rounded-lg
                                focus:outline-none focus:border-blue-700 focus:bg-blue-50
                              "
                            
                            placeholder="Nombre completo o nombre de empresa"
                        >
                    </div>

                    <div>
                        <label
                          class="
                            block
                            mb-2
                            text-sm font-semibold text-blue-900
                          "
                        >
                            Orden de Servicio (Folio) *
                        </label>
                        <input 
                            type="text" 
                            name="folio" 
                            value="<?php echo $folioActual; ?>" 
                            readonly
                              class="
                                w-full
                                px-4 py-2
                                text-blue-700
                                bg-blue-100
                                border-2 border-blue-200 rounded-lg
                                cursor-not-allowed
                              "
                            
                        >
                    </div>

                    <div>
                        <label
                          class="
                            block
                            mb-2
                            text-sm font-semibold text-blue-900
                          "
                        >
                            Direccion <span class="font-normal text-blue-700">(opcional)</span>
                        </label>
                        <input 
                            type="text" 
                            name="direccion" 
                              class="
                                w-full
                                px-4 py-2
                                border-2 border-blue-300 rounded-lg
                                focus:outline-none focus:border-blue-700 focus:bg-blue-50
                              "
                            
                            placeholder="Calle y numero"
                        >
                    </div>

                    <div>
                        <label
                          class="
                            block
                            mb-2
                            text-sm font-semibold text-blue-900
                          "
                        >
                            Celular <span class="font-normal text-blue-700">(opcional)</span>
                        </label>
                        <input 
                            type="tel"
                            inputmode="numeric"
                            name="telefono"
                            id="telefono"
                            maxlength="20"
                            title="Opcional. Si lo capturas, escribe 10 digitos (sin +52 ni el 1) para poder enviar el WhatsApp de la orden."
                              class="
                                w-full
                                px-4 py-2
                                border-2 border-blue-300 rounded-lg
                                focus:outline-none focus:border-blue-700 focus:bg-blue-50
                              "
                            autocomplete="tel"
                            placeholder="Opcional - 10 digitos, ej. 6121942057"
                        >
                    </div>


                    <div>
                        <label
                          class="
                            block
                            mb-2
                            text-sm font-semibold text-blue-900
                          "
                        >
                            Correo electronico <span class="font-normal text-blue-700">(opcional)</span>
                        </label>
                        <input 
                            type="email" 
                            name="correo" 
                            id="correo"
                            maxlength="120"
                              class="
                                w-full
                                px-4 py-2
                                border-2 border-blue-300 rounded-lg
                                focus:outline-none focus:border-blue-700 focus:bg-blue-50
                              "
                            
                            placeholder="cliente@ejemplo.com"
                        >
                    </div>

                    <div>
                        <label
                          class="
                            block
                            mb-2
                            text-sm font-semibold text-blue-900
                          "
                        >
                            Poblacion/Ciudad *
                        </label>
                        <input 
                            type="text" 
                            name="poblacion" 
                            required
                            maxlength="80"
                            title="Solo letras, espacios, puntos, apostrofes o guiones (2 a 80 caracteres)"
                              class="
                                w-full
                                px-4 py-2
                                border-2 border-blue-300 rounded-lg
                                focus:outline-none focus:border-blue-700 focus:bg-blue-50
                              "
                            
                            placeholder="Ciudad o municipio"
                        >
                    </div>

                    <div>
                        <label
                          class="
                            block
                            mb-2
                            text-sm font-semibold text-blue-900
                          "
                        >
                            Fecha de Entrada *
                        </label>
                        <input 
                            type="datetime-local" 
                            name="fechaEntrada" 
                            readonly
                              class="
                                w-full
                                px-4 py-2
                                text-blue-700
                                bg-blue-100
                                border-2 border-blue-200 rounded-lg
                                cursor-not-allowed
                              "
                            
                        >
                    </div>

                    <?php if (!$modoSoloCompletar): ?>
                    <input type="hidden" name="estatus" id="inputEstatus" value="<?php echo htmlspecialchars($estatusFormValor, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php endif; ?>
                </div>
            </section>

            <?php if ($modoSoloCompletar): ?>
            <section class="mb-2 rounded-r-lg border-l-4 border-emerald-600 bg-emerald-50 p-4 sm:pl-6">
                <h2 class="flex mb-2 text-lg font-bold text-emerald-900 items-center">
                    <i class="mr-2 text-emerald-600 fas fa-clipboard-check"></i>Datos tecnicos a completar
                </h2>
            </section>
            <?php endif; ?>

            <!-- SECCION 2: DESCRIPCION DE EQUIPOS -->
            <section
              class="
                p-4 sm:pl-6
                bg-blue-50
                border-l-4 border-blue-500 rounded-r-lg
              "
            >
                <h2
                  class="
                    flex
                    mb-6
                    text-xl sm:text-2xl font-bold text-blue-900
                    items-center
                  "
                >
                    <i
                      class="
                        mr-3
                        text-blue-500
                        fas fa-list-ul
                      "
                    ></i><?php echo $modoSoloCompletar ? 'EQUIPOS' : 'DESCRIPCION DE EQUIPOS'; ?>
                </h2>
                
                <div
                  class="
                    overflow-x-auto
                    mb-4 rounded-lg border border-blue-100
                  "
                >
                    <table
                      class="
                        w-full
                        border-collapse text-sm
                      "
                     style="min-width: 980px;">
                        <thead>
                            <tr
                              class="
                                text-white
                                bg-blue-600
                              "
                            >
                                <th
                                  class="
                                    p-3
                                    text-left
                                    border
                                  "
                                >ID</th>
                                <th
                                  class="
                                    p-3
                                    text-left
                                    border
                                  "
                                >MARCA</th>
                                <th
                                  class="
                                    p-3
                                    text-left
                                    border
                                  "
                                >MODELO O DESCRIPCION DEL EQUIPO</th>
                                <th
                                  class="
                                    p-3
                                    text-left
                                    border
                                  "
                                >N.O SERIE</th>
                                <th
                                  class="
                                    p-3
                                    text-left
                                    border
                                  "
                                >DESCRIPCION DE FALLA</th>
                                <th
                                  class="
                                    p-3
                                    text-left
                                    border
                                  "
                                >TIPO DE SERVICIO</th>
                                @php $mostrarAcciones = $idEditar > 0 && in_array($estatusFormValor, ['En proceso', 'Terminado'], true); @endphp
                                @if ($mostrarAcciones)
                                <th class="p-3 text-center border">ESTATUS</th>
                                @endif
<th
                                  class="
                                    p-3
                                    text-left
                                    border
                                  "
>ANADIR</th>
                                   @if ($mostrarAcciones)
                                   <th
                                     class="
                                       p-3
                                       text-center
                                       border
                                     "
                                   >ACCIONES</th>
                                   @endif
                            </tr>
                        </thead>
                        <tbody id="equiposTableBody">
                            <tr
                              class="
                                equipo-row hover:bg-blue-100
                              "
                              data-acciones="0"
                            >
                                <td
                                  class="
                                    p-3
                                    border
                                  "
                                ><span
                                  class="
                                    equipo-numero
                                  "
                                >1</span></td>
                                <td
                                  class="
                                    p-3
                                    border
                                  "
                                ><input type="text"
                                  class="
                                    w-full
                                    px-2 py-1
                                    border border-blue-300
                                    rounded
                                  "
                                 name="equipos[0][marca]" placeholder="Marca"></td>
                                <td
                                  class="
                                    p-3
                                    border
                                  "
                                ><input type="text"
                                  class="
                                    w-full
                                    px-2 py-1
                                    border border-blue-300
                                    rounded
                                  "
                                 name="equipos[0][modelo]" placeholder="Modelo o descripcion"></td>
                                <td
                                  class="
                                    p-3
                                    border
                                  "
                                ><input type="text"
                                  class="
                                    w-full
                                    px-2 py-1
                                    border border-blue-300
                                    rounded
                                  "
                                 name="equipos[0][serie]" placeholder="Serie"></td>
                                <td
                                  class="
                                    p-3
                                    border
                                  "
                                ><input type="text"
                                  class="
                                    w-full
                                    px-2 py-1
                                    border border-blue-300
                                    rounded
                                  "
                                 name="equipos[0][descripcionFalla]" placeholder="Descripcion de falla"></td>
                                <td
                                  class="
                                    p-3
                                    border
                                  "
                                ><select
                                  class="
                                    w-full
                                    px-2 py-1
                                    border border-blue-300
                                    rounded
                                  "
                                 name="equipos[0][tipoServicio]">
                                   <option value="">Seleccionar...</option>
@foreach($tiposServicio ?? [] as $tipoServicio)
                                    <option value="{{ $tipoServicio }}">{{ $tipoServicio }}</option>
                                    @endforeach
                                  </select></td>
@if ($mostrarAcciones)
                                <td class="p-3 text-center border equipo-estatus-cell">
                                    <input type="hidden" name="equipos[0][acciones]" class="equipo-acciones-input" value="0">
                                    <span class="equipo-estatus-badge inline-flex items-center rounded-full bg-slate-400 px-2.5 py-1 text-xs font-bold text-white" style="background-color:#64748b;color:#ffffff;">Pendiente</span>
                                </td>
@endif
<td
                                   class="
                                     p-3
                                     text-center
                                     border
                                   "
                                   >
                                     <button type="button"
                                       class="
                                         text-blue-600 font-bold
                                         hover:text-blue-800 btn-agregar-equipo
                                       "
                                       title="Agregar fila">
                                       <i
                                         class="
                                           fas fa-plus
                                         "
                                       ></i>
                                     </button>
                                   </td>
@if ($mostrarAcciones)
                                     <td
                                       class="
                                         p-3
                                         text-center
                                         border
                                       "
                                     >
                                       <button type="button"
                                         class="
                                           font-bold text-green-600 hover:text-green-800 btn-entrega-equipo-row mr-2
                                         "
                                         title="Terminar y entregar este equipo"
                                         data-id-equipo="1">
                                         <i class="fas fa-truck"></i>
                                       </button>
                                      <button type="button"
                                        class="
                                          font-bold text-red-600 hover:text-red-800 btn-eliminar-equipo
                                        "
                                        title="Eliminar fila">
                                        <i class="fas fa-trash"></i>
                                      </button>
                                    </td>
                                    @endif
                         </tbody>
                    </table>
                </div>
            </section>

            <!-- SECCION 4: CONDICIONES DE ENTREGA DEL EQUIPO -->
            <section
              class="
                p-4 sm:pl-6
                bg-blue-50
                border-l-4 border-blue-500 rounded-r-lg
                
              "
            >
                <h2
                  class="
                    flex
                    mb-6
                    text-xl sm:text-2xl font-bold text-blue-900
                    items-center
                  "
                >
                    <i
                      class="
                        mr-3
                        text-blue-500
                        fas fa-info-circle
                      "
                    ></i>CONDICIONES DE ENTREGA DEL EQUIPO
                </h2>
                
                <div class="space-y-2 text-left text-xs font-normal uppercase italic leading-relaxed text-blue-900 sm:text-sm">
                    @foreach($condicionesEntregaLineas as $lineaCond)
                        <p class="mb-0">&bull; {{ $lineaCond }}</p>
                    @endforeach
                </div>
            </section>

            <!-- SECCION 5: OBSERVACIONES -->
            <section
              class="
                p-4 sm:pl-6
                bg-blue-50
                border-l-4 border-blue-600 rounded-r-lg
                <?php echo $modoSoloCompletar ? 'hidden' : ''; ?>
              "
            >
                <h2
                  class="
                    flex
                    mb-6
                    text-xl sm:text-2xl font-bold text-blue-900
                    items-center
                  "
                >
                    <i
                      class="
                        mr-3
                        text-blue-600
                        fas fa-sticky-note
                      "
                    ></i>OBSERVACIONES
                </h2>
                
                <div
                  class="
                    space-y-3
                  "
                >
                    <div
                      class="
                        flex
                        items-start
                      "
                    >
                        <span
                          class="
                            mr-3
                            font-bold text-blue-700
                          "
                        >1.</span>
                        <input type="text"
                          class="
                            flex-1
                            px-3 py-2
                            border border-blue-300 rounded-lg
                            observacion-input
                          "
                          name="observaciones[]"
                         placeholder="Primera observacion">
                        <button type="button"
                          class="
                            ml-2
                            text-blue-600 font-bold
                            hover:text-blue-800 btn-agregar-obs
                          "
                         title="Agregar observacion">
                            <i
                              class="
                                fas fa-plus
                              "
                            ></i>
                        </button>
                    </div>
                    <div id="observacionesContainer"></div>
                </div>
            </section>

            <!-- SECCION 6: FIRMAS INICIALES (solo alta nueva; oculta al editar) -->
            <section
              id="ordenSeccionFirmasIniciales"
              class="
                p-4 sm:pl-6
                bg-blue-50
                border-l-4 border-blue-700 rounded-r-lg
                <?php echo ($idEditar > 0 || $modoSoloCompletar) ? 'hidden orden-firmas-skip' : ''; ?>
              "
              style="<?php echo ($idEditar > 0 || $modoSoloCompletar) ? 'display:none' : ''; ?>"
            >
                <h2
                  class="
                    flex
                    mb-8
                    text-xl sm:text-2xl font-bold text-blue-900
                    items-center
                  "
                >
                    <i
                      class="
                        mr-3
                        text-blue-700
                        fas fa-pen-fancy
                      "
                    ></i>FIRMAS
                </h2>
                
                <div
                  class="
                    grid grid-cols-1
                    gap-8
                    md:grid-cols-2
                  "
                >
                    <!-- Firma Cliente Entrega -->
                    <div
                      class="
                        text-center
                      "
                    >
                        <h2
                          class="
                            block
                            mb-4
                            text-lg font-bold text-blue-900
                          "
                        >Cliente</h2>
                        <div
                          class="
                            bg-white/95
                            border-2 border-blue-400 rounded-lg
                          "
                         style="min-height: 150px; background-color: rgba(255,255,255,0.96);">
                            <canvas 
                                id="firmaClienteInicial"
                                  class="
                                    w-full h-full
                                    rounded-lg
                                    cursor-crosshair
                                  "
                                
                                style="display: block; background: white;"
                            ></canvas>
                        </div>
                        <button type="button"
                          class="
                            mt-2 px-4 py-2
                            text-white
                            bg-blue-600
                            rounded hover:bg-blue-700
                          "
                         onclick="limpiarFirmaClienteInicial()">
                            <i
                              class="
                                mr-2
                                fas fa-eraser
                              "
                            ></i>Limpiar
                        </button>
                    </div>

                    <!-- Firma Tecnico Recibido -->
                    <div
                      class="
                        text-center
                      "
                    >
                        <label
                          class="
                            block
                            mb-4
                            text-lg font-bold text-blue-900
                          "
                        >Tecnico</label>
                        <div
                          class="
                            bg-white/95
                            border-2 border-blue-400 rounded-lg
                          "
                         style="min-height: 150px; background-color: rgba(255,255,255,0.96);">
                            <canvas 
                                id="firmaTecnicoInicial"
                                  class="
                                    w-full h-full
                                    rounded-lg
                                    cursor-crosshair
                                  "
                                
                                style="display: block; background: white;"
                            ></canvas>
                        </div>
                        <button type="button"
                          class="
                            mt-2 px-4 py-2
                            text-white
                            bg-blue-600
                            rounded hover:bg-blue-700
                          "
                         onclick="limpiarFirmaTecnicoInicial()">
                            <i
                              class="
                                mr-2
                                fas fa-eraser
                              "
                            ></i>Limpiar
                        </button>
                    </div>
                </div>
            </section>

            <?php if (!empty($motivoSalidaTemporal)): ?>
            <section class="rounded-lg p-4 sm:p-6" style="border:2px solid #fdba74;background:#fff7ed;">
                <h2 class="mb-3 text-lg font-bold uppercase" style="color:#7c2d12;">
                    <i class="fas fa-door-open mr-2" style="color:#ea580c;"></i>Salida temporal del equipo
                </h2>
                <?php if (!empty($fechaSalidaTemporal)): ?>
                <p class="mb-2 text-sm" style="color:#9a3412;">
                    Fecha de salida:
                    <strong><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($fechaSalidaTemporal)), ENT_QUOTES, 'UTF-8'); ?></strong>
                    <?php if (!empty($salidaTemporalActiva)): ?>
                        <span class="ml-2 inline-flex rounded-full px-2 py-0.5 text-xs font-bold" style="background:#ea580c;color:#fff;">Activa</span>
                    <?php else: ?>
                        <span class="ml-2 inline-flex rounded-full px-2 py-0.5 text-xs font-bold" style="background:#64748b;color:#fff;">Regresó</span>
                    <?php endif; ?>
                </p>
                <?php endif; ?>
                <div class="rounded-lg p-4 text-sm leading-relaxed whitespace-pre-wrap" style="border:1px solid #fed7aa;background:#fff;color:#1e293b;"><?php echo e($motivoSalidaTemporal); ?></div>
            </section>
            <?php endif; ?>

            <?php
                $estatusEsRecepcion = mb_stripos((string) ($estatusFormValor ?? ''), 'recepc') !== false;
                $ocultarSeccionesTaller = !$modoSoloCompletar && ($idEditar <= 0 || $estatusEsRecepcion);
            ?>

            <?php
            if ($idEditar <= 0 && ! $modoSoloCompletar) {
                $sersop01Aviso = [
                    'clave' => 'SERSOP01',
                    'descripcion' => 'SOPORTE REVISION/VALORACION TECNICA',
                    'precio' => 603.45,
                ];
                if (! empty($serviciosSersopActivos) && is_array($serviciosSersopActivos)) {
                    foreach ($serviciosSersopActivos as $servicioCat) {
                        if (! is_array($servicioCat)) {
                            continue;
                        }
                        if (mb_strtoupper(trim((string) ($servicioCat['clave'] ?? '')), 'UTF-8') === 'SERSOP01') {
                            $sersop01Aviso['descripcion'] = trim((string) ($servicioCat['descripcion'] ?? $sersop01Aviso['descripcion']));
                            $sersop01Aviso['precio'] = (float) ($servicioCat['precio'] ?? 603.45);
                            break;
                        }
                    }
                }
                $sersop01SinIva = number_format((float) $sersop01Aviso['precio'], 2);
                $sersop01ConIva = number_format(round((float) $sersop01Aviso['precio'] * 1.16, 2), 2);
            ?>
            <div id="avisoCobroSersop01Nueva" class="rounded-lg border-2 border-amber-400 bg-amber-50 p-4 text-left shadow-sm">
                <p class="text-base font-bold uppercase text-amber-950">
                    <i class="fas fa-receipt mr-2 text-amber-600"></i>Cobro por defecto en orden nueva
                </p>
                <p class="mt-2 text-sm font-semibold uppercase text-amber-900">
                    Se cobra <strong>SERSOP01</strong>
                    — <?php echo htmlspecialchars($sersop01Aviso['descripcion'], ENT_QUOTES, 'UTF-8'); ?>
                    — <strong>$<?php echo $sersop01SinIva; ?> sin IVA</strong>
                    ($<?php echo $sersop01ConIva; ?> con IVA).
                </p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-amber-900">
                    <li>Al guardar, el sistema preguntará si el cliente ya pagó este cobro.</li>
                    <li><strong>Si no pagó:</strong> no se registra nada de SERSOP01.</li>
                    <li><strong>Si pagó:</strong> pedirá ticket/factura y sí se registra el cobro (sale en trabajos del PDF y en el subtotal).</li>
                </ul>
            </div>
            <?php if ($ocultarSeccionesTaller): ?>
            {{-- En recepción/nueva no hay tabla de trabajos; estos campos permiten registrar SERSOP01 + abono. --}}
            <input type="hidden" name="abono_saldo" id="abonoSaldoAplicado" value="0">
            <input type="hidden" name="abono_saldo_equipos" id="abonoSaldoEquiposJson" value="{}">
            <input type="hidden" name="saldo_pagado_confirmado" id="saldoPagadoConfirmado" value="0">
            <?php endif; ?>
            <?php } ?>

            <?php if (!$ocultarSeccionesTaller): ?>

            <!-- SECCION 7: TRABAJOS REALIZADOS POR EL TECNICO -->
            <section
              class="
                p-4 sm:pl-6
                bg-blue-50
                border-l-4 border-blue-500 rounded-r-lg
              "
            >
                <h2
                  class="
                    flex
                    mb-6
                    text-xl sm:text-2xl font-bold text-blue-900
                    items-center
                  "
                >
                    <i
                      class="
                        mr-3
                        text-blue-500
                        fas fa-tools
                      "
                    ></i><?php echo $modoSoloCompletar ? 'TRABAJOS' : 'TRABAJOS REALIZADOS POR EL TECNICO (TIEMPO TECNICO Y MANO DE OBRA)'; ?>
                </h2>
                
                <div
                  class="
                    overflow-x-auto
                    mb-4 rounded-lg border border-blue-100
                  "
                >
                    <table
                      class="
                        w-full
                        border-collapse text-sm
                      "
                     style="min-width: 940px;">
                        <thead>
                            <tr
                              class="
                                text-white
                                bg-blue-600
                              "
                            >
                                <th
                                  class="
                                    p-3
                                    text-left
                                    border
                                  "
                                >ID</th>
                                <th
                                  class="
                                    p-3
                                    text-left
                                    border
                                  "
                                >CLAVE</th>
                                <th
                                  class="
                                    p-3
                                    text-left
                                    border
                                  "
                                >DESCRIPCION</th>
                                <th
                                  class="
                                    p-3
                                    text-left
                                    border
                                  "
                                >PRECIO SIN IVA</th>
                                <th
                                  class="
                                    p-3
                                    text-left
                                    border
                                  "
                                >TICKET O FACTURA</th>
                                <th
                                  class="
                                    p-3
                                    text-left
                                    border
                                  "
                                >EQUIPO</th>
                                <th
                                  class="
                                    p-3
                                    text-center
                                    border
                                  "
                                >ANADIR</th>
                            </tr>
                        </thead>
                        <tbody id="trabajosTableBody">
                            <tr
                              class="
                                trabajo-row hover:bg-blue-100
                              "
                            >
                                <td
                                  class="
                                    p-3
                                    border
                                  "
                                ><span class="trabajo-numero">1</span></td>
                                <td
                                  class="
                                    p-3
                                    border
                                  "
                                ><select
                                  class="
                                    w-full
                                    px-2 py-1
                                    border border-blue-300
                                    rounded
                                  "
                                 name="trabajos[0][clave]">
                                  <option value="">Clave...</option>
                                  <?php foreach ($serviciosSersopActivos as $servicioSersop): ?>
                                  <?php
                                      $claveServicio = (string)($servicioSersop['clave'] ?? '');
                                      $descripcionServicio = (string)($servicioSersop['descripcion'] ?? '');
                                      $precioServicio = number_format((float)($servicioSersop['precio'] ?? 0), 2, '.', '');
                                      $editableServicio = !empty($servicioSersop['editable']) ? '1' : '0';
                                      $labelServicio = $claveServicio . ' - ' . $descripcionServicio . ' ($' . $precioServicio . ')';
                                  ?>
                                  <option
                                    value="<?php echo htmlspecialchars($claveServicio, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-descripcion="<?php echo htmlspecialchars($descripcionServicio, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-precio="<?php echo htmlspecialchars($precioServicio, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-editable="<?php echo $editableServicio; ?>"
                                  ><?php echo htmlspecialchars($labelServicio, ENT_QUOTES, 'UTF-8'); ?></option>
                                  <?php endforeach; ?>
                                </select></td>
                                <td
                                  class="
                                    p-3
                                    border
                                  "
                                ><input type="text"
                                  class="
                                    w-full
                                    px-2 py-1
                                    border border-blue-300
                                    rounded
                                    bg-gray-100
                                    text-gray-600
                                    cursor-not-allowed
                                  "
                                 name="trabajos[0][descripcion]" placeholder="Descripcion del trabajo" readonly title="Este campo se toma del catálogo SERSOP."></td>
                                <td
                                  class="
                                    p-3
                                    border
                                  "
                                ><div class="exacto-money-field"><span class="exacto-money-prefix">$</span><input type="number" step="0.01"
                                  class="
                                    w-full
                                    px-2 py-1
                                    border border-blue-300
                                    rounded
                                    importe-input
                                    bg-gray-100
                                    text-gray-600
                                    cursor-not-allowed
                                  "
                                 name="trabajos[0][importe]" placeholder="PRECIO SIN IVA" readonly title="PRECIO SIN IVA del catálogo SERSOP."></div></td>
                                <td
                                  class="
                                    p-3
                                    border
                                  "
                                ><input type="text"
                                  class="
                                    w-full
                                    px-2 py-1
                                    border border-blue-300
                                    rounded
                                    ticket-factura-input
                                  "
                                 name="trabajos[0][ticket]" placeholder="Ticket, factura o folio"></td>
                                <td
                                  class="
                                    p-3
                                    border
                                  "
                                ><select
                                  class="
                                    w-full
                                    px-2 py-1
                                    border border-blue-300
                                    rounded
                                    trabajo-equipo-select
                                  "
                                 name="trabajos[0][id_equipo]">
                                  <option value="">-</option>
                                </select></td>
                                <td
                                  class="
                                    p-3
                                    text-center
                                    border
                                  "
                                >
                                    <button type="button"
                                      class="
                                        text-blue-600 font-bold
                                        hover:text-blue-800 btn-agregar-trabajo
                                      "
                                     title="Agregar fila">
                                        <i
                                          class="
                                            fas fa-plus
                                          "
                                        ></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- SECCION 8: COMENTARIOS SOBRE EL TECNICO -->
            <section
              class="
                p-4 sm:pl-6
                bg-blue-50
                border-l-4 border-blue-400 rounded-r-lg
              "
            >
                <h2
                  class="
                    flex
                    mb-6
                    text-xl sm:text-2xl font-bold text-blue-900
                    items-center
                  "
                >
                    <i
                      class="
                        mr-3
                        text-blue-400
                        fas fa-comment
                      "
                    ></i><?php echo $modoSoloCompletar ? 'COMENTARIOS' : 'COMENTARIOS DEL TECNICO'; ?>
                </h2>
                
                <textarea 
                    name="comentariosTecnico" 
                    rows="4"
                      class="
                        w-full
                        px-4 py-3
                        border-2 border-blue-300 rounded-lg
                        focus:outline-none focus:border-blue-700
                      "
                    
                    placeholder="Comentarios del tecnico sobre el equipo..."
                ></textarea>
            </section>

            <!-- SECCION 9: MATERIAL NECESARIO PARA LA REPARACION -->
            <section
              class="
                p-4 sm:pl-6
                bg-blue-50
                border-l-4 border-blue-600 rounded-r-lg
              "
            >
                <h2
                  class="
                    flex
                    mb-6
                    text-xl sm:text-2xl font-bold text-blue-900
                    items-center
                  "
                >
                    <i
                      class="
                        mr-3
                        text-blue-600
                        fas fa-boxes
                      "
                    ></i><?php echo $modoSoloCompletar ? 'MATERIALES' : 'MATERIAL NECESARIO PARA LA REPARACION'; ?>
                </h2>
                
                <div
                  class="
                    overflow-x-auto
                    mb-4 rounded-lg border border-blue-100
                  "
                >
                    <table
                      class="
                        w-full
                        border-collapse text-sm
                      "
                     style="min-width: 980px;">
                        <thead>
                            <tr
                              class="
                                text-white
                                bg-blue-600
                              "
                            >
                                <th
                                  class="
                                    p-3
                                    text-left
                                    border
                                  "
                                >VALE.NO</th>
                                <th
                                  class="
                                    p-3
                                    text-left
                                    border
                                  "
                                >CODIGO</th>
                                <th
                                  class="
                                    p-3
                                    text-left
                                    border
                                  "
                                >CANTIDAD</th>
                                <th
                                  class="
                                    p-3
                                    text-left
                                    border
                                  "
                                >DESCRIPCION</th>
                                <th
                                  class="
                                    p-3
                                    text-left
                                    border
                                  "
                                >PRECIO SIN IVA</th>
                                <th
                                  class="
                                    p-3
                                    text-left
                                    border
                                  "
                                >IMPORTE</th>
                                <th
                                  class="
                                    p-3
                                    text-left
                                    border
                                  "
                                >TICKET O FACTURA</th>
                                <th
                                  class="
                                    p-3
                                    text-left
                                    border
                                  "
                                >EQUIPO</th>
                                <th
                                  class="
                                    p-3
                                    text-center
                                    border
                                  "
                                >ANADIR</th>
                            </tr>
                        </thead>
                        <tbody id="materialesTableBody">
                            <tr
                              class="
                                material-row hover:bg-blue-100
                              "
                            >
                                <td
                                  class="
                                    p-3
                                    border
                                  "
                                ><input type="text"
                                  class="
                                    w-full
                                    px-2 py-1
                                    border border-blue-300
                                    rounded
                                  "
                                 name="materiales[0][vale]" placeholder="Vale"></td>
                                <td
                                  class="
                                    p-3
                                    border
                                  "
                                ><input type="text"
                                  class="
                                    w-full
                                    px-2 py-1
                                    border border-blue-300
                                    rounded
                                  "
                                 name="materiales[0][codigo]" placeholder="Codigo"></td>
                                <td
                                  class="
                                    p-3
                                    border
                                  "
                                ><input type="number" min="0"
                                  class="
                                    w-full
                                    px-2 py-1
                                    border border-blue-300
                                    rounded
                                    cant-input
                                  "
                                 name="materiales[0][cant]" placeholder="Cant"></td>
                                <td
                                  class="
                                    p-3
                                    border
                                  "
                                ><input type="text"
                                  class="
                                    w-full
                                    px-2 py-1
                                    border border-blue-300
                                    rounded
                                  "
                                 name="materiales[0][descripcion]" placeholder="Descripcion"></td>
                                <td
                                  class="
                                    p-3
                                    border
                                  "
                                ><div class="exacto-money-field"><span class="exacto-money-prefix">$</span><input type="number" step="0.01" min="0"
                                  class="
                                    w-full
                                    px-2 py-1
                                    border border-blue-300
                                    rounded
                                    precio-input
                                  "
                                 name="materiales[0][precio]" placeholder="Neto c/IVA" title="Escribe el precio neto (con IVA). Se convierte a sin IVA automáticamente."></div></td>
                                <td
                                  class="
                                    p-3
                                    border
                                  "
                                ><span class="exacto-money-prefix">$</span><span class="importe-calc">0.00</span></td>
                                <td
                                  class="
                                    p-3
                                    border
                                  "
                                ><input type="text"
                                  class="
                                    w-full
                                    px-2 py-1
                                    border border-blue-300
                                    rounded
                                    ticket-factura-input
                                  "
                                 name="materiales[0][ticket]" placeholder="Ticket, factura o folio"></td>
                                <td
                                  class="
                                    p-3
                                    border
                                  "
                                ><select
                                  class="
                                    w-full
                                    px-2 py-1
                                    border border-blue-300
                                    rounded
                                    material-equipo-select
                                  "
                                 name="materiales[0][id_equipo]">
                                  <option value="">-</option>
                                </select></td>
                                <td
                                  class="
                                    p-3
                                    text-center
                                    border
                                  "
                                >
                                    <button type="button"
                                      class="
                                        text-blue-600 font-bold
                                        hover:text-blue-800 btn-agregar-material
                                      "
                                     title="Agregar fila">
                                        <i
                                          class="
                                            fas fa-plus
                                          "
                                        ></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div
                  class="
                    overflow-x-auto
                    mb-4 rounded-lg border border-blue-100
                  "
                >
                    <h3 class="mb-3 text-lg font-bold text-blue-900">ANTICIPOS</h3>
                    <table
                      class="
                        w-full
                        border-collapse text-sm
                      "
                     style="min-width: 720px;">
                        <thead>
                            <tr
                              class="
                                text-white
                                bg-blue-600
                              "
                            >
                                <th class="p-3 text-left border">FOLIO</th>
                                <th class="p-3 text-left border">DESCRIPCION</th>
                                <th class="p-3 text-left border">MONTO SIN IVA</th>
                                <th class="p-3 text-left border">TICKET / FACTURA</th>
                                <th class="p-3 text-left border">EQUIPO</th>
                                <th class="p-3 text-center border">ANADIR</th>
                            </tr>
                        </thead>
                        <tbody id="anticiposTableBody">
                            <tr class="anticipo-row hover:bg-blue-100">
                                <td class="p-3 border">
                                    <input type="text" class="w-full px-2 py-1 border border-blue-300 rounded" name="anticipos[0][folio]" placeholder="Folio pedido">
                                </td>
                                <td class="p-3 border">
                                    <input type="text" class="w-full px-2 py-1 border border-blue-300 rounded" name="anticipos[0][descripcion]" placeholder="Descripcion de refaccion">
                                </td>
                                <td class="p-3 border">
                                    <div class="exacto-money-field"><span class="exacto-money-prefix">$</span><input type="number" step="0.01" class="w-full px-2 py-1 border border-blue-300 rounded anticipo-input" name="anticipos[0][monto]" value="" placeholder="Neto c/IVA" title="Escribe el monto neto (con IVA). Se convierte a sin IVA automáticamente."></div>
                                </td>
                                <td class="p-3 border">
                                    <input type="text" class="w-full px-2 py-1 border border-blue-300 rounded anticipo-ticket-input ticket-factura-input" name="anticipos[0][ticket]" placeholder="Ticket, factura o folio">
                                </td>
                                <td class="p-3 border">
                                    <select class="w-full px-2 py-1 border border-blue-300 rounded anticipo-equipo-select" name="anticipos[0][id_equipo]">
                                        <option value="">-</option>
                                    </select>
                                </td>
                                <td class="p-3 text-center border">
                                    <button type="button" class="text-blue-600 font-bold hover:text-blue-800 btn-agregar-anticipo" title="Agregar anticipo">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div
                  class="
                    mt-4
                    text-right
                  "
                >
                    <strong>SUBTOTAL DE TRABAJOS Y MATERIALES: $<span id="subtotalCombinado">0.00</span></strong><br>
                    <div id="anticiposListaTotales" class="mt-1"></div>
                    <strong>IVA (16%): $<span id="ivaTotal">0.00</span></strong><br>
                    <strong class="block mt-3">TOTAL: $<span id="total">0.00</span></strong>
                    <strong
                        class="block mt-3 rounded-lg px-4 py-3 text-lg shadow-sm"
                        style="background-color: #dc2626; color: #ffffff; display: inline-block;"
                    >SALDO PENDIENTE: $<span id="saldoPendiente">0.00</span></strong>
                    <input type="hidden" name="abono_saldo" id="abonoSaldoAplicado" value="0">
                    <input type="hidden" name="abono_saldo_equipos" id="abonoSaldoEquiposJson" value="{}">
                    <input type="hidden" name="saldo_pagado_confirmado" id="saldoPagadoConfirmado" value="0">
                    <div class="mt-3">
                        <div class="flex flex-col items-end gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
                            <button
                                type="button"
                                id="btnPagarSaldoPendiente"
                                class="inline-flex items-center justify-center rounded-lg bg-green-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                                title="Elige el equipo y liquida solo su saldo"
                            >
                                <i class="fas fa-cash-register mr-2"></i>
                                Liquidar saldo por equipo
                            </button>
                        </div>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <!-- SECCION 10: FIRMAS DE ENTREGA (solo estatus Entregado, al final del formulario) -->
            <section
              id="ordenSeccionFirmasEntrega"
              class="
                p-4 sm:pl-6
                bg-blue-50
                border-l-4 border-blue-700 rounded-r-lg
                <?php
                    $mostrarFirmasEntregaInicial = mb_stripos((string) ($estatusFormValor ?? ''), 'entreg') !== false;
                    echo !empty($firmasDeshabilitadas) ? 'orden-firmas-skip ' : '';
                    echo !$mostrarFirmasEntregaInicial ? 'orden-firmas-skip hidden' : '';
                ?>
              "
              style="<?php echo $mostrarFirmasEntregaInicial ? '' : 'display:none'; ?>"
            >
                <h2
                  class="
                    flex
                    mb-8
                    text-xl sm:text-2xl font-bold text-blue-900
                    items-center
                  "
                >
                    <i
                      class="
                        mr-3
                        text-blue-700
                        fas fa-pen-fancy
                      "
                    ></i><?php echo ($modoSoloCompletar || $idEditar > 0) ? 'FIRMAS DE ENTREGA DEL EQUIPO' : 'FIRMAS'; ?>
                </h2>
                <div
                  class="
                    grid grid-cols-1
                    gap-8
                    md:grid-cols-2
                  "
                >
                    <!-- Firma Cliente -->
                    <div
                      class="
                        text-center
                      "
                    >
                        <h2
                          class="
                            block
                            mb-4
                            text-lg font-bold text-blue-900
                          "
                        ><?php echo ($modoSoloCompletar || $idEditar > 0) ? 'Cliente que recibe' : 'Cliente'; ?></h2>
                        <div
                          class="
                            bg-white/95
                            border-2 border-blue-400 rounded-lg
                          "
                         style="min-height: 150px; background-color: rgba(255,255,255,0.96);">
                            <canvas 
                                id="firmaCliente"
                                  class="
                                    w-full h-full
                                    rounded-lg
                                    cursor-crosshair
                                  "
                                
                                style="display: block; background: white;"
                            ></canvas>
                        </div>
                        <button type="button"
                          class="
                            mt-2 px-4 py-2
                            text-white
                            bg-blue-600
                            rounded hover:bg-blue-700
                          "
                         onclick="limpiarFirmaCliente()">
                            <i
                              class="
                                mr-2
                                fas fa-eraser
                              "
                            ></i>Limpiar
                        </button>
                    </div>

                    <!-- Firma Tecnico -->
                    <div
                      class="
                        text-center
                      "
                    >
                        <label
                          class="
                            block
                            mb-4
                            text-lg font-bold text-blue-900
                          "
                        ><?php echo ($modoSoloCompletar || $idEditar > 0) ? 'Tecnico que entrega' : 'Tecnico'; ?></label>
                        <div
                          class="
                            bg-white/95
                            border-2 border-blue-400 rounded-lg
                          "
                         style="min-height: 150px; background-color: rgba(255,255,255,0.96);">
                            <canvas 
                                id="firmaTecnico"
                                  class="
                                    w-full h-full
                                    rounded-lg
                                    cursor-crosshair
                                  "
                                
                                style="display: block; background: white;"
                            ></canvas>
                        </div>
                        <button type="button"
                          class="
                            mt-2 px-4 py-2
                            text-white
                            bg-blue-600
                            rounded hover:bg-blue-700
                          "
                         onclick="limpiarFirmaTecnico()">
                            <i
                              class="
                                mr-2
                                fas fa-eraser
                              "
                            ></i>Limpiar
                        </button>
                    </div>
                </div>
            </section>

            <!-- BOTONES DE ACCION -->
            <div
              class="
                flex flex-col
                mt-10 pt-8
                border-t-2 border-blue-300
                gap-4 items-center
              "
            >
                <!-- Estado correo / WhatsApp: siempre arriba del botón Guardar -->
                <div
                    id="ordenSubmitStatus"
                    class="mb-0 w-full max-w-2xl hidden rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-center text-sm text-blue-900"
                    role="status"
                    aria-live="polite"
                    aria-atomic="true"
                >
                    <div class="flex flex-col items-center justify-center gap-2">
                        <i id="ordenSubmitStatusIcon" class="fas fa-spinner fa-spin text-xl text-blue-700"></i>
                        <p id="ordenSubmitStatusText" class="w-full text-center font-semibold leading-relaxed">Guardando orden y enviando correo, espere...</p>
                    </div>
                </div>

                <?php if (empty($soloLecturaEntregado)): ?>
                <button type="submit" id="btnGuardarOrden"
                  class="
                    flex w-full justify-center sm:w-auto
                    px-8 py-3
                    text-white font-bold
                    bg-blue-600
                    rounded-lg
                    hover:bg-blue-700 transition items-center
                  "
                >
                    <i
                      class="
                        mr-2
                        fas fa-save
                      "
                    ></i><?php echo $modoSoloCompletar ? 'Guardar datos tecnicos' : 'Guardar Orden de Servicio'; ?>
                </button>
                <?php else: ?>
                <a href="{{ route('ordenes.index') }}" class="inline-flex items-center justify-center rounded-lg border-2 border-blue-600 bg-white px-8 py-3 font-bold text-blue-800 hover:bg-blue-50">
                    <i class="fas fa-arrow-left mr-2"></i>Volver a órdenes
                </a>
                <?php if ($idEditar > 0): ?>
                <a href="{{ url('/pdf/orden/'.(int) $idEditar) }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-8 py-3 font-bold text-white hover:bg-indigo-700">
                    <i class="fas fa-file-pdf mr-2"></i>Ver PDF
                </a>
                <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Botón Liquidar Saldo (nuevo) -->
            <button type="button"
              id="btnLiquidarSaldo"
              class="
                flex w-full justify-center sm:w-auto
                px-8 py-3
                text-white font-bold
                bg-red-600
                rounded-lg
                hover:bg-red-700
                transition
                mt-2
              "
              title="Liquidar saldo por equipo">
                <i class="fas fa-cash-register mr-2"></i>
                Liquidar Saldo por Equipo
            </button>

            <!-- Modal: Entrega por Equipo Confirmation -->
            <div id="modalEntregaEquipo" class="hidden fixed inset-0 z-[10160] items-center justify-center bg-slate-950/80" role="dialog" aria-modal="true" aria-labelledby="modalEntregaEquipoTitle" style="display:none;align-items:center;justify-content:center;padding:1rem;z-index:10160;">
                <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl overflow-hidden">
                    <div class="bg-blue-600 px-5 py-4">
                        <h3 id="modalEntregaEquipoTitle" class="text-center text-lg font-bold text-white flex items-center justify-center gap-2">
                            <i class="fas fa-truck"></i> Entrega por Equipo
                        </h3>
                    </div>
                    <div class="p-5 space-y-4">
                        <p class="text-center text-sm text-slate-600">¿Estás seguro de entregar este equipo individualmente?</p>
                        <p id="entregaEquipoFlujoHint" class="text-center text-xs text-slate-500">
                            Estado del equipo: se actualizar&aacute; al abrir. <strong>Terminado</strong> (uso interno) habilita <strong>Entregado</strong>.
                        </p>
                        <div class="grid grid-cols-2 gap-2">
                            <label id="labelChkTerminado" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2.5 cursor-pointer transition hover:border-blue-400 hover:bg-blue-50">
                                <input type="checkbox" id="chkTerminado" class="checkbox-acceso w-4 h-4 rounded border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                <span class="text-sm font-medium text-blue-900">Marcar como Terminado<br><span class="text-[10px] font-normal text-slate-500">(uso interno)</span></span>
                            </label>
                            <label id="labelChkEntregado" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2.5 cursor-pointer transition hover:border-blue-400 hover:bg-blue-50">
                                <input type="checkbox" id="chkEntregado" class="checkbox-acceso w-4 h-4 rounded border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                <span class="text-sm font-medium text-blue-900">Marcar como Entregado<br><span class="text-[10px] font-normal text-slate-500">(notifica cliente)</span></span>
                            </label>
                        </div>
                        <div id="validacionSaldoPendiente" class="p-3 rounded-lg bg-amber-50 border border-amber-200 text-sm text-amber-800 hidden">
                            <p class="flex items-start gap-2">
                                <i class="fas fa-exclamation-triangle mt-0.5 text-amber-500"></i>
                                <span>Para marcar como Terminado, el saldo pendiente debe estar liquidado.</span>
                            </p>
                            <button type="button" id="btnLiquidarAhora" class="mt-2 text-blue-600 underline hover:text-blue-800 text-xs font-semibold">Liquidar Saldo Ahora</button>
                        </div>
                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-3" id="equipoInfo">
                            <p class="text-xs font-bold uppercase tracking-wide text-blue-700 mb-1"><i class="fas fa-microchip mr-1"></i>Equipo a entregar</p>
                            <table class="w-full text-sm text-slate-700">
                                <tr>
                                    <td class="py-0.5 pr-2 text-slate-500">Marca</td>
                                    <td class="py-0.5 font-semibold text-slate-800" id="equipoInfoMarca">-</td>
                                </tr>
                                <tr>
                                    <td class="py-0.5 pr-2 text-slate-500">Modelo</td>
                                    <td class="py-0.5 font-semibold text-slate-800" id="equipoInfoModelo">-</td>
                                </tr>
                                <tr>
                                    <td class="py-0.5 pr-2 text-slate-500">Serie</td>
                                    <td class="py-0.5 font-semibold text-slate-800" id="equipoInfoSerie">-</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="p-4 border-t border-slate-200 flex justify-end gap-2">
                        <button type="button" id="btnCancelarEntregaEquipo" class="rounded-lg border-2 border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button>
                        <button type="button" id="btnGuardarEntregaEquipo" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 shadow-sm">Guardar Orden de Servicio</button>
                    </div>
                </div>
            </div>

            <!-- Modal: Firmas para Entrega (expandida) -->
            <style>
                #modalFirmasEntrega .exacto-entrega-receptor-selector {
                    display: flex;
                    gap: 0.25rem;
                    padding: 0.25rem;
                    border: 1px solid #e2e8f0;
                    border-radius: 0.5rem;
                    background: #f1f5f9;
                }
                #modalFirmasEntrega .exacto-entrega-receptor-option {
                    position: relative;
                    display: flex;
                    min-height: 2.5rem;
                    flex: 1 1 0%;
                    cursor: pointer;
                    align-items: center;
                    justify-content: center;
                    border-radius: 0.375rem;
                    padding: 0.5rem 0.75rem;
                    color: #475569;
                    font-size: 0.875rem;
                    font-weight: 700;
                    transition: background-color 160ms ease, color 160ms ease, box-shadow 160ms ease;
                }
                #modalFirmasEntrega .exacto-entrega-receptor-option input {
                    position: absolute;
                    width: 1px;
                    height: 1px;
                    opacity: 0;
                }
                #modalFirmasEntrega .exacto-entrega-receptor-option:has(input:checked) {
                    background: #ffffff;
                    color: #1d4ed8;
                    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.14);
                }
                #modalFirmasEntrega .exacto-entrega-receptor-option--tercero:has(input:checked) {
                    color: #b45309;
                }
                #modalFirmasEntrega .exacto-entrega-receptor-option:has(input:focus-visible) {
                    outline: 2px solid #60a5fa;
                    outline-offset: 2px;
                }
                #modalFirmasEntrega .exacto-firma-canvas-wrap {
                    height: clamp(11rem, 29vh, 14rem);
                    min-height: 11rem;
                    background: #ffffff;
                }
                @media (max-width: 767px) {
                    #modalFirmasEntrega .exacto-firma-canvas-wrap {
                        height: 12rem;
                    }
                }
            </style>
            <div id="modalFirmasEntrega" class="hidden fixed inset-0 z-[10170] items-center justify-center bg-slate-950/80" role="dialog" aria-modal="true" aria-labelledby="modalFirmasEntregaTitle" style="display:none;align-items:center;justify-content:center;padding:1rem;z-index:10170;">
                <div class="flex w-full max-w-4xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl" style="width:min(56rem,calc(100vw - 2rem));max-height:90vh;">
                    <div class="flex shrink-0 flex-col gap-3 bg-blue-600 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div>
                            <h3 id="modalFirmasEntregaTitle" class="flex items-center gap-2 text-lg font-bold text-white">
                                <i class="fas fa-file-signature" aria-hidden="true"></i>
                                Firmas de Entrega
                            </h3>
                            <p class="mt-0.5 text-xs text-blue-100 sm:text-sm">Confirma la entrega y registra las firmas correspondientes.</p>
                        </div>
                        <div class="inline-flex max-w-full items-center gap-2 self-start rounded-lg border border-blue-400 px-3 py-2 text-xs text-white sm:self-auto" style="background:rgba(29,78,216,.62);">
                            <span class="shrink-0 font-semibold text-blue-100"><i class="fas fa-microchip mr-1" aria-hidden="true"></i>Equipo:</span>
                            <span class="min-w-0 truncate font-bold">
                                <span id="equipoEntregaMarca">-</span>
                                <span id="equipoEntregaModelo">-</span>
                            </span>
                        </div>
                    </div>
                    <div class="flex-1 space-y-4 overflow-y-auto p-4 sm:p-5">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 md:gap-5">
                                <div>
                                    <p class="mb-2 text-sm font-bold text-slate-800">¿Quién recoge el equipo?</p>
                                    <div class="exacto-entrega-receptor-selector">
                                        <label class="exacto-entrega-receptor-option">
                                            <input type="radio" name="entrega_quien_recibe" id="entregaQuienCliente" value="cliente" checked>
                                            <span><i class="fas fa-user mr-1.5 text-xs" aria-hidden="true"></i>Cliente titular</span>
                                        </label>
                                        <label class="exacto-entrega-receptor-option exacto-entrega-receptor-option--tercero">
                                            <input type="radio" name="entrega_quien_recibe" id="entregaQuienTercero" value="tercero">
                                            <span><i class="fas fa-user-shield mr-1.5 text-xs" aria-hidden="true"></i>Tercero</span>
                                        </label>
                                    </div>
                                </div>
                                <div>
                                    <label for="inputRecibidoClienteEntrega" class="mb-2 block text-sm font-bold text-slate-800">Nombre de quien recibe <span class="text-red-600">*</span></label>
                                    <input type="text" id="inputRecibidoClienteEntrega" maxlength="255"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold uppercase text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                        placeholder="Nombre completo de quien recoge"
                                        autocomplete="name">
                                    <p id="hintRecibidoTercero" class="mt-1.5 hidden text-xs font-medium text-amber-700"><i class="fas fa-circle-info mr-1" aria-hidden="true"></i>Captura el nombre completo del tercero; se confirmará antes de guardar.</p>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <!-- Firma Cliente -->
                            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                                <div class="flex min-h-[3rem] items-center justify-between gap-3 border-b border-slate-200 bg-blue-50 px-4 py-2.5">
                                    <label class="text-sm font-bold text-blue-900"><i class="fas fa-signature mr-1.5 text-blue-600" aria-hidden="true"></i>Firma de quien recibe</label>
                                    <button type="button" id="btnLimpiarFirmaClienteEntrega" class="inline-flex shrink-0 items-center gap-1.5 rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600">
                                        <i class="fas fa-eraser" aria-hidden="true"></i> Limpiar
                                    </button>
                                </div>
                                <div class="exacto-firma-canvas-wrap overflow-hidden">
                                    <canvas id="firmaClienteEntrega" class="w-full h-full rounded-lg cursor-crosshair" style="display: block; background: white;"></canvas>
                                </div>
                            </div>
                            <!-- Firma Tecnico -->
                            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                                <div class="flex min-h-[3rem] items-center justify-between gap-3 border-b border-slate-200 bg-blue-50 px-4 py-2.5">
                                    <label class="text-sm font-bold text-blue-900"><i class="fas fa-user-gear mr-1.5 text-blue-600" aria-hidden="true"></i>Firma del Técnico</label>
                                    <button type="button" id="btnLimpiarFirmaTecnicoEntrega" class="inline-flex shrink-0 items-center gap-1.5 rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600">
                                        <i class="fas fa-eraser" aria-hidden="true"></i> Limpiar
                                    </button>
                                </div>
                                <div class="exacto-firma-canvas-wrap overflow-hidden">
                                    <canvas id="firmaTecnicoEntrega" class="w-full h-full rounded-lg cursor-crosshair" style="display: block; background: white;"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex shrink-0 flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row sm:justify-end sm:px-6 sm:py-4">
                        <button type="button" id="btnCancelarFirmasEntrega" class="min-h-[2.75rem] rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Cancelar</button>
                        <button type="button" id="btnGuardarFirmasEntrega" class="inline-flex min-h-[2.75rem] items-center justify-center gap-2 rounded-lg bg-green-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-green-700">
                            <span>Guardar y Enviar</span>
                            <span class="ml-1 inline-flex items-center gap-1.5 border-l border-green-500 pl-3" aria-label="WhatsApp y correo">
                                <i class="fab fa-whatsapp" aria-hidden="true"></i>
                                <i class="far fa-envelope" aria-hidden="true"></i>
                            </span>
                        </button>
                    </div>
                </div>
            </div>

        </form>
    </div>

    <div id="modalLiquidarSaldo" class="hidden fixed inset-0 z-[20000] bg-slate-950/80" role="dialog" aria-modal="true" aria-labelledby="modalLiquidarSaldoTitle" style="display:none;align-items:center;justify-content:center;padding:1rem;z-index:20000;">
        <div class="w-full max-w-2xl bg-white rounded-2xl shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 id="modalLiquidarSaldoTitle" class="text-center text-xl font-bold text-blue-900">Liquidar Saldo por Equipo</h3>
            </div>
            <div class="p-5 space-y-2 max-h-[60vh] overflow-y-auto">
                <p class="text-sm text-slate-600 mb-4">Selecciona los equipos a liquidar. El pago se aplica a este equipo, el resto de la orden puede seguir con saldo.</p>
                <div id="equiposLiquidarSaldoContainer" class="space-y-2"></div>
            </div>
            <div class="p-5 border-t border-slate-200 flex justify-end gap-3">
                <button type="button" id="btnCancelarLiquidarSaldo" class="rounded-lg border-2 border-slate-300 bg-white px-5 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancelar</button>
                <button type="button" id="btnConfirmarLiquidarSaldo" class="rounded-lg bg-green-600 px-5 py-2 text-sm font-bold text-white hover:bg-green-700">Liquidar seleccionados</button>
            </div>
        </div>
    </div>

    {{-- z-index > nav (9999) y toast WA (10050): si no, "Cambiar de cuenta" tapa el motivo en tableta --}}
    <div id="modalSalidaTemporal" class="hidden fixed inset-0 z-[10100] bg-slate-950/75" role="dialog" aria-modal="true" aria-labelledby="modalSalidaTemporalTitle" style="display:none;align-items:center;justify-content:center;padding:0.5rem;z-index:10100;">
        <div id="cardSalidaTemporal" class="flex w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" style="display:flex;flex-direction:column;width:100%;max-width:48rem;max-height:min(96dvh,96vh);overflow:hidden;background:#fff;">
            <div id="salidaTempHeaderWrap" class="shrink-0 border-b border-orange-200 bg-orange-50 px-3 py-2" style="flex-shrink:0;background:#fff7ed;border-bottom:1px solid #fed7aa;padding:0.5rem 0.75rem;">
                <h3 id="modalSalidaTemporalTitle" class="text-base font-bold text-orange-950" style="margin:0;font-size:1.05rem;font-weight:700;color:#7c2d12;">
                    <i class="fas fa-door-open mr-2 text-orange-600"></i>Salida temporal del equipo
                </h3>
            </div>
            <div id="salidaTempMotivoWrap" class="shrink-0 bg-white px-3 pt-2 pb-2" style="flex-shrink:0;background:#fff;padding:0.5rem 0.75rem 0.4rem;">
                <label for="motivoSalidaTemporalInput" class="mb-1 block text-sm font-bold text-orange-900" style="display:block;margin:0 0 0.35rem;font-size:0.9rem;font-weight:700;color:#7c2d12;">Motivo de salida <span style="color:#dc2626;">*</span></label>
                <textarea id="motivoSalidaTemporalInput" rows="2" maxlength="4000" class="w-full rounded-lg border-2 border-orange-400 px-3 py-2 text-sm focus:border-orange-600 focus:outline-none" style="width:100%;height:3.6rem;min-height:3.6rem;max-height:3.6rem;resize:none;border:2px solid #fb923c;border-radius:0.5rem;padding:0.4rem 0.65rem;box-sizing:border-box;" placeholder="Escribe aquí el motivo de la salida temporal..."></textarea>
            </div>
            <div id="salidaTempFirmasWrap" class="min-h-0 flex-1 overflow-y-auto px-3 pb-2" style="flex:1 1 auto;min-height:0;overflow:auto;-webkit-overflow-scrolling:touch;padding:0.25rem 0.75rem 0.5rem;">
                <div class="grid grid-cols-2 gap-3" style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
                    <div class="text-center">
                        <h4 class="mb-1 text-sm font-bold text-blue-900" style="margin:0 0 0.35rem;font-size:0.9rem;font-weight:700;color:#1e3a8a;">Firma del cliente</h4>
                        <div class="rounded-lg border-2 border-blue-400 bg-white" style="height:88px;border:2px solid #60a5fa;border-radius:0.5rem;background:#fff;">
                            <canvas id="firmaClienteSalidaTemp" class="w-full rounded-lg cursor-crosshair" style="display:block;background:white;width:100%;height:100%;"></canvas>
                        </div>
                        <button type="button" id="btnLimpiarFirmaClienteSalidaTemp" class="mt-1 rounded bg-blue-600 px-3 py-1.5 text-xs text-white hover:bg-blue-700" style="margin-top:0.35rem;background:#2563eb;color:#fff;border:0;border-radius:0.375rem;padding:0.35rem 0.75rem;font-size:0.8rem;cursor:pointer;">
                            <i class="fas fa-eraser mr-1"></i>Limpiar
                        </button>
                    </div>
                    <div class="text-center">
                        <h4 class="mb-1 text-sm font-bold text-blue-900" style="margin:0 0 0.35rem;font-size:0.9rem;font-weight:700;color:#1e3a8a;">Firma del técnico</h4>
                        <div class="rounded-lg border-2 border-blue-400 bg-white" style="height:88px;border:2px solid #60a5fa;border-radius:0.5rem;background:#fff;">
                            <canvas id="firmaTecnicoSalidaTemp" class="w-full rounded-lg cursor-crosshair" style="display:block;background:white;width:100%;height:100%;"></canvas>
                        </div>
                        <button type="button" id="btnLimpiarFirmaTecnicoSalidaTemp" class="mt-1 rounded bg-blue-600 px-3 py-1.5 text-xs text-white hover:bg-blue-700" style="margin-top:0.35rem;background:#2563eb;color:#fff;border:0;border-radius:0.375rem;padding:0.35rem 0.75rem;font-size:0.8rem;cursor:pointer;">
                            <i class="fas fa-eraser mr-1"></i>Limpiar
                        </button>
                    </div>
                </div>
            </div>
            <div id="footerSalidaTemporal" class="shrink-0 flex flex-row flex-wrap items-center justify-end gap-2 border-t border-slate-200 bg-white px-3 py-2" style="flex-shrink:0;display:flex;flex-direction:row;flex-wrap:wrap;justify-content:flex-end;gap:0.5rem;background:#fff;border-top:1px solid #e2e8f0;padding:0.55rem 0.75rem;">
                <button type="button" id="btnCancelarSalidaTemporal" class="rounded-lg border-2 border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancelar</button>
                <button type="button" id="btnConfirmarSalidaTemporal" class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-bold text-white hover:bg-orange-700" style="background:#ea580c;color:#fff;">
                    <i class="fas fa-save mr-1"></i>Guardar
                </button>
            </div>
        </div>
    </div>

    <div id="exactoUiModal" class="hidden fixed inset-0 z-[30000] items-center justify-center bg-slate-950/70 p-4" role="dialog" aria-modal="true" aria-labelledby="exactoUiModalTitle" style="z-index:30000;">
        <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 id="exactoUiModalTitle" class="text-center text-xl font-bold text-blue-900">Aviso</h3>
            </div>
            <div class="exacto-ui-modal-body px-5 py-5 text-center">
                <div id="exactoUiModalIconWrap" class="mb-4 hidden flex justify-center">
                    <span id="exactoUiModalIconCircle" class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
                        <i id="exactoUiModalIcon" class="fas fa-info-circle text-2xl text-slate-600"></i>
                    </span>
                </div>
                <p id="exactoUiModalMessage" class="mx-auto max-w-prose whitespace-pre-line text-center text-sm leading-relaxed text-slate-700"></p>
                <div id="exactoUiModalInputWrap" class="mt-4 hidden text-left">
                    <label id="exactoUiModalInputLabel" for="exactoUiModalInput" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600"></label>
                    <input type="text" id="exactoUiModalInput" class="w-full rounded-lg border-2 border-blue-300 px-3 py-2 text-sm font-semibold uppercase text-slate-800 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-200" autocomplete="off" maxlength="80">
                </div>
            </div>
            <div class="flex flex-col-reverse items-center gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:justify-center">
                <button type="button" id="exactoUiModalCancel" class="hidden rounded-lg border-2 border-slate-300 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    Cancelar
                </button>
                <button type="button" id="exactoUiModalConfirm" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-blue-700">
                    Aceptar
                </button>
            </div>
        </div>
    </div>

    <?php if ($ordenExistenteJson !== null): ?>
    <script type="application/json" id="ordenExistenteJson"><?php echo $ordenExistenteJson; ?></script>
    <?php endif; ?>

    @php
        $ordenServicioJsPath = public_path('legacy/assets/js/orden_servicio.js');
        $ordenServicioJsV = is_file($ordenServicioJsPath) ? (string) filemtime($ordenServicioJsPath) : '1';
        $ordenFirmasJsPath = public_path('legacy/assets/js/orden_firmas_entrega.js');
        $ordenFirmasJsV = is_file($ordenFirmasJsPath) ? (string) filemtime($ordenFirmasJsPath) : '1';
    @endphp
    <script src="{{ asset('legacy/assets/js/orden_firmas_entrega.js') }}?v={{ $ordenFirmasJsV }}"></script>
    <script src="{{ asset('legacy/assets/js/orden_servicio.js') }}?v={{ $ordenServicioJsV }}"></script>

    <script>
        // ===== LÓGICA: Entrega por Equipo =====
        window.exactoEntregaEquipoSeleccionado = null;
        window.exactoEntregaEquipoDbId = null;

        function exactoQuitarResaltadoEquipos() {
            document.querySelectorAll('#equiposTableBody .equipo-row').forEach(f => {
                f.classList.remove('exacto-equipo-seleccionado', 'bg-blue-50', 'ring-2', 'ring-blue-300');
            });
        }

        function exactoFilasEquipos() {
            return Array.from(document.querySelectorAll('#equiposTableBody .equipo-row'));
        }

        /** Número de equipo 1..N (igual que la columna EQUIPO en trabajos/materiales). */
        function exactoResolverNumeroEquipoDesdeBoton(btn) {
            const filas = exactoFilasEquipos();
            if (!filas.length) {
                return 0;
            }
            const fila = btn && btn.closest ? btn.closest('.equipo-row') : null;
            if (fila) {
                const idx = filas.indexOf(fila);
                if (idx >= 0) {
                    return idx + 1;
                }
            }
            const raw = btn && btn.dataset ? Number(btn.dataset.idEquipo) : NaN;
            if (Number.isFinite(raw) && raw >= 1 && raw <= filas.length) {
                return raw;
            }
            // data-id-equipo legado en base 0
            if (Number.isFinite(raw) && raw >= 0 && raw < filas.length) {
                return raw + 1;
            }
            if (filas.length === 1) {
                return 1;
            }
            return 0;
        }

        function exactoResolverIdEquipoDb(numEquipo) {
            const filas = exactoFilasEquipos();
            const fila = filas[numEquipo - 1] || null;
            if (fila) {
                const fromRow = Number(fila.dataset.idEquipoDb || 0);
                if (fromRow > 0) return fromRow;
                const fromBtn = Number(fila.querySelector('.btn-entrega-equipo-row')?.dataset?.idEquipoDb || 0);
                if (fromBtn > 0) return fromBtn;
            }
            let ordenJson = {};
            const jsonEl = document.getElementById('ordenExistenteJson');
            if (jsonEl) {
                try { ordenJson = JSON.parse(jsonEl.textContent || '{}'); } catch (e) { ordenJson = {}; }
            }
            const equipos = Array.isArray(ordenJson.equipos) ? ordenJson.equipos : [];
            const dbId = Number((equipos[numEquipo - 1] || {}).id_equipo || 0);
            return dbId > 0 ? dbId : 0;
        }

        function exactoDatosEquipoPorNumero(numEquipo) {
            const filas = exactoFilasEquipos();
            const fila = filas[numEquipo - 1] || null;
            let marca = '';
            let modelo = '';
            let serie = '';
            if (fila) {
                marca = String(fila.querySelector('[name*="[marca]"]')?.value || '').trim();
                modelo = String(fila.querySelector('[name*="[modelo]"]')?.value || '').trim();
                serie = String(fila.querySelector('[name*="[serie]"]')?.value || '').trim();
            }
            if (!marca && !modelo && !serie) {
                let ordenJson = {};
                const jsonEl = document.getElementById('ordenExistenteJson');
                if (jsonEl) {
                    try { ordenJson = JSON.parse(jsonEl.textContent || '{}'); } catch (e) { ordenJson = {}; }
                }
                const equipos = Array.isArray(ordenJson.equipos) ? ordenJson.equipos : [];
                const equipo = equipos[numEquipo - 1] || {};
                marca = String(equipo.marca || '').trim();
                modelo = String(equipo.modelo || '').trim();
                serie = String(equipo.serie || '').trim();
            }
            return {
                marca: marca || 'Sin marca',
                modelo: modelo || 'Sin modelo',
                serie: serie || '-',
            };
        }

        function exactoResaltarEquipoRow(numEquipo) {
            exactoQuitarResaltadoEquipos();
            const fila = exactoFilasEquipos()[numEquipo - 1];
            if (fila) {
                fila.classList.add('exacto-equipo-seleccionado', 'bg-blue-50', 'ring-2', 'ring-blue-300');
                fila.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        function exactoEstatusOrdenFormulario() {
            const el = document.getElementById('inputEstatus') || document.querySelector('[name="estatus"]');
            const raw = String(el?.value || '').trim();
            if (typeof exactoNormalizarEstatusOrden === 'function') {
                return exactoNormalizarEstatusOrden(raw);
            }
            const k = raw.toLowerCase().replace(/\s+/g, '');
            if (k.includes('entreg')) return 'Entregado';
            if (k.includes('termin')) return 'Terminado';
            if (k.includes('proceso')) return 'En proceso';
            return 'Recepcion';
        }

        /** 0=pendiente, 1=terminado, 2=entregado (tabla ESTATUS / dataset; legado orden global). */
        function exactoAccionesEquipoSeleccionado() {
            const num = Number(window.exactoEntregaEquipoSeleccionado) || 0;
            if (num < 1) return 0;
            const fila = exactoFilasEquipos()[num - 1];
            const fromHidden = Number(fila?.querySelector?.('.equipo-acciones-input')?.value || NaN);
            let acc = Number.isFinite(fromHidden)
                ? fromHidden
                : (Number(fila?.dataset?.acciones || 0) || 0);
            if (acc >= 2) return 2;
            if (acc >= 1) return 1;
            // Legado: órdenes marcadas Terminado/Entregado a nivel global antes del flujo por equipo.
            const est = exactoEstatusOrdenFormulario();
            if (est === 'Entregado') return 2;
            if (est === 'Terminado') return 1;
            return 0;
        }

        function exactoEquipoPuedeEntregarse() {
            if (exactoAccionesEquipoSeleccionado() >= 1) return true;
            const chkTerminado = document.getElementById('chkTerminado');
            return Boolean(chkTerminado && chkTerminado.checked && !chkTerminado.disabled);
        }

        function exactoMarcarAccionesEquipoLocal(numEquipo, acciones) {
            const fila = exactoFilasEquipos()[numEquipo - 1];
            if (!fila) return;
            if (typeof window.exactoPintarEstatusEquipoFila === 'function') {
                window.exactoPintarEstatusEquipoFila(fila, acciones);
            } else {
                const acc = Math.max(0, Number(acciones) || 0);
                fila.dataset.acciones = String(acc);
            }
        }

        function exactoAplicarBloqueoEntregaCheckboxes() {
            const chkTerminado = document.getElementById('chkTerminado');
            const chkEntregado = document.getElementById('chkEntregado');
            const labelTerm = document.getElementById('labelChkTerminado');
            const labelEnt = document.getElementById('labelChkEntregado');
            const hint = document.getElementById('entregaEquipoFlujoHint');
            const acciones = exactoAccionesEquipoSeleccionado();
            const yaTerminadoDb = acciones >= 1;
            const yaEntregado = acciones >= 2;
            const puedeEntregar = yaTerminadoDb || Boolean(chkTerminado && chkTerminado.checked);

            if (chkTerminado) {
                chkTerminado.disabled = yaTerminadoDb;
                if (yaTerminadoDb) chkTerminado.checked = true;
            }
            if (chkEntregado) {
                chkEntregado.disabled = !puedeEntregar || yaEntregado;
                if (yaEntregado) {
                    chkEntregado.checked = true;
                } else if (!puedeEntregar) {
                    chkEntregado.checked = false;
                }
            }

            const lockClass = ['opacity-50', 'cursor-not-allowed', 'bg-slate-100', 'pointer-events-none'];
            if (labelTerm) {
                lockClass.forEach((c) => labelTerm.classList.toggle(c, yaTerminadoDb));
            }
            if (labelEnt) {
                lockClass.forEach((c) => labelEnt.classList.toggle(c, !puedeEntregar || yaEntregado));
            }
            if (hint) {
                if (yaEntregado) {
                    hint.innerHTML = 'Este equipo ya está en <strong>Entregado</strong>. No se cambia el estatus global de la orden.';
                } else if (yaTerminadoDb) {
                    hint.innerHTML = 'Este equipo ya pasó a <strong>Terminado</strong> (uso interno). Ahora marca <strong>Entregado</strong> para firmar y notificar al cliente (OS solo de este equipo).';
                } else if (puedeEntregar) {
                    hint.innerHTML = 'Terminado marcado. Ya puedes pulsar <strong>Entregado</strong> (o Guardar solo Terminado y continuar después).';
                } else {
                    hint.innerHTML = 'Estado actual: <strong>pendiente</strong>. Marca <strong>Terminado</strong> (uso interno). <strong>Entregado</strong> se habilita al marcar Terminado.';
                }
            }
        }

        function exactoAbrirModalEntregaEquipo(numEquipo) {
            const total = exactoFilasEquipos().length;
            if (!numEquipo || numEquipo < 1 || numEquipo > total) {
                exactoShowAlert('Selecciona el equipo a entregar desde el ícono de camión.', {
                    title: 'Entrega por Equipo',
                    icon: 'error',
                });
                return;
            }

            window.exactoEntregaEquipoSeleccionado = numEquipo;
            window.exactoEntregaEquipoDbId = exactoResolverIdEquipoDb(numEquipo);
            exactoResaltarEquipoRow(numEquipo);

            const modal = document.getElementById('modalEntregaEquipo');
            if (!modal) return;
            if (modal.parentNode !== document.body) {
                document.body.appendChild(modal);
            }

            const datos = exactoDatosEquipoPorNumero(numEquipo);
            const elMarca = document.getElementById('equipoInfoMarca');
            const elModelo = document.getElementById('equipoInfoModelo');
            const elSerie = document.getElementById('equipoInfoSerie');
            const elInfo = document.getElementById('equipoInfo');
            if (elMarca) elMarca.textContent = datos.marca;
            if (elModelo) elModelo.textContent = datos.modelo;
            if (elSerie) elSerie.textContent = datos.serie;
            if (elInfo) elInfo.classList.remove('hidden');

            const pendienteEl = document.getElementById('validacionSaldoPendiente');
            if (pendienteEl) pendienteEl.classList.add('hidden');
            exactoAplicarBloqueoEntregaCheckboxes();

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.style.display = 'flex';
            modal.style.alignItems = 'center';
            modal.style.justifyContent = 'center';
            modal.style.padding = '1rem';
            modal.style.zIndex = '20000';
            document.body.style.overflow = 'hidden';
        }

        window.exactoBtnEntregaEquipo = function() {
            const numEquipo = exactoResolverNumeroEquipoDesdeBoton(this);
            if (!numEquipo) {
                exactoShowAlert('Selecciona el equipo a entregar desde el ícono de camión en la fila del equipo.', {
                    title: 'Entrega por Equipo',
                    icon: 'error',
                });
                return;
            }
            exactoAbrirModalEntregaEquipo(numEquipo);
        };

        // Cancelar modal Entrega por Equipo
        document.getElementById('btnCancelarEntregaEquipo')?.addEventListener('click', function() {
            const modal = document.getElementById('modalEntregaEquipo');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
            exactoQuitarResaltadoEquipos();
        });

        // Manejar cambio de checkboxes Terminado/Entregado
        document.getElementById('chkTerminado')?.addEventListener('change', function() {
            const pendienteEl = document.getElementById('validacionSaldoPendiente');
            if (this.disabled) {
                this.checked = exactoAccionesEquipoSeleccionado() >= 1;
                exactoAplicarBloqueoEntregaCheckboxes();
                return;
            }
            if (this.checked) {
                const numEq = Number(window.exactoEntregaEquipoSeleccionado) || 0;
                const saldo = (typeof exactoSaldoPendienteActual === 'function')
                    ? exactoSaldoPendienteActual()
                    : (parseFloat(document.getElementById('saldoPendiente')?.textContent || '0') || 0);
                const saldoEq = (numEq > 0 && typeof exactoCalcularSaldoEquipo === 'function')
                    ? exactoCalcularSaldoEquipo(numEq)
                    : saldo;
                if (saldoEq > 0.009 && saldo > 0.009) {
                    pendienteEl?.classList.remove('hidden');
                    this.checked = false;
                    exactoShowAlert(
                        `Queda un saldo pendiente de $${saldoEq.toFixed(2)} en este equipo. Liquídalo antes de marcar Terminado.`,
                        { title: 'Validación', icon: 'error' }
                    );
                } else {
                    pendienteEl?.classList.add('hidden');
                }
            } else {
                pendienteEl?.classList.add('hidden');
                const chkEntregado = document.getElementById('chkEntregado');
                if (chkEntregado && exactoAccionesEquipoSeleccionado() < 1) {
                    chkEntregado.checked = false;
                }
            }
            // Al marcar Terminado se habilita Entregado de inmediato (sin esperar otro guardado).
            exactoAplicarBloqueoEntregaCheckboxes();
        });

        document.getElementById('chkEntregado')?.addEventListener('change', function() {
            if (this.disabled) {
                this.checked = exactoAccionesEquipoSeleccionado() >= 2;
                return;
            }
            if (this.checked && !exactoEquipoPuedeEntregarse()) {
                this.checked = false;
                exactoShowAlert(
                    'Primero marca Terminado (uso interno). En cuanto lo marques, Entregado se habilita.',
                    { title: 'Flujo de entrega', icon: 'warning' }
                );
                exactoAplicarBloqueoEntregaCheckboxes();
                return;
            }
        });

        // Marcar como Terminado/Entregado y guardar
        document.getElementById('btnGuardarEntregaEquipo')?.addEventListener('click', async function() {
            const btn = this;
            const chkTerminado = document.getElementById('chkTerminado')?.checked;
            const chkEntregado = document.getElementById('chkEntregado')?.checked;
            const idOrden = Number(document.getElementById('id_orden_c')?.value || 0);
            const numEquipo = Number(window.exactoEntregaEquipoSeleccionado) || 0;

            if (idOrden <= 0) {
                await exactoShowAlert('No hay orden activa', {title: 'Error', icon: 'error'});
                return;
            }

            if (numEquipo < 1) {
                await exactoShowAlert('Selecciona el equipo a entregar desde el ícono de camión.', {
                    title: 'Entrega por Equipo',
                    icon: 'error',
                });
                return;
            }

            if (!chkTerminado && !chkEntregado) {
                await exactoShowAlert('Selecciona al menos un estatus (Terminado o Entregado)', {title: 'Error', icon: 'error'});
                return;
            }

            const accionesEq = exactoAccionesEquipoSeleccionado();
            if (chkEntregado && !exactoEquipoPuedeEntregarse()) {
                await exactoShowAlert(
                    'Primero marca Terminado (uso interno). Luego podrás marcar Entregado.',
                    { title: 'Flujo de entrega', icon: 'warning' }
                );
                return;
            }
            if (chkTerminado && accionesEq >= 1 && !chkEntregado) {
                await exactoShowAlert(
                    'Este equipo ya está Terminado. Marca Entregado para firmar y enviar la OS solo de este equipo.',
                    { title: 'Flujo de entrega', icon: 'warning' }
                );
                return;
            }

            // Si es Entregado, primero capturar firmas en el modal de firmas
            if (chkEntregado) {
                abrirModalFirmasEntrega();
                return;
            }

            // Solo Terminado: validar saldo del formulario (misma fuente que SALDO PENDIENTE en pantalla)
            // y guardar. No usar /validar-saldo de BD aquí: el abono liquidado en UI puede no estar guardado aún.
            const saldo = (typeof exactoSaldoPendienteActual === 'function')
                ? exactoSaldoPendienteActual()
                : (parseFloat(document.getElementById('saldoPendiente')?.textContent || '0') || 0);
            if (saldo > 0.009) {
                await exactoShowAlert(
                    `Queda un saldo pendiente de $${saldo.toFixed(2)}. Liquídalo antes de marcar Terminado.`,
                    { title: 'Error', icon: 'error' }
                );
                return;
            }

            btn.disabled = true;
            const textoOriginal = btn.textContent;
            btn.textContent = 'Guardando...';

            try {
                await Promise.resolve(guardarOrdenConEstatus(idOrden, 'Terminado'));
            } catch (err) {
                console.error(err);
                await exactoShowAlert('Error de red al guardar. Revisa tu conexión.', {title: 'Error', icon: 'error'});
            } finally {
                btn.disabled = false;
                btn.textContent = textoOriginal;
            }
        });

        // ===== LÓGICA: Modal de firmas de entrega =====
        function exactoNombreClienteTitular() {
            return String(document.getElementById('nombreCliente')?.value
                || document.querySelector('[name="nombreCliente"]')?.value
                || '').trim();
        }

        function exactoEsEntregaPorTercero() {
            return Boolean(document.getElementById('entregaQuienTercero')?.checked);
        }

        function exactoSincronizarQuienRecibeEntrega() {
            const input = document.getElementById('inputRecibidoClienteEntrega');
            const hint = document.getElementById('hintRecibidoTercero');
            const esTercero = exactoEsEntregaPorTercero();
            if (hint) hint.classList.toggle('hidden', !esTercero);
            if (!input) return;
            if (!esTercero) {
                const titular = exactoNombreClienteTitular();
                if (titular !== '') {
                    input.value = titular.toUpperCase();
                }
                input.readOnly = true;
                input.classList.add('bg-slate-100');
            } else {
                input.readOnly = false;
                input.classList.remove('bg-slate-100');
                if (input.value.trim().toUpperCase() === exactoNombreClienteTitular().toUpperCase()) {
                    input.value = '';
                }
                input.focus();
            }
        }

        function exactoNombreQuienRecibeEntrega() {
            const input = document.getElementById('inputRecibidoClienteEntrega');
            let nombre = String(input?.value || '').trim();
            if (nombre === '' && !exactoEsEntregaPorTercero()) {
                nombre = exactoNombreClienteTitular();
            }
            return nombre.toUpperCase();
        }

        document.getElementById('entregaQuienCliente')?.addEventListener('change', exactoSincronizarQuienRecibeEntrega);
        document.getElementById('entregaQuienTercero')?.addEventListener('change', exactoSincronizarQuienRecibeEntrega);
        document.getElementById('inputRecibidoClienteEntrega')?.addEventListener('input', function () {
            this.value = String(this.value || '').toUpperCase();
        });

        function exactoPrepararFirmasEntregaModal() {
            const ids = ['firmaClienteEntrega', 'firmaTecnicoEntrega'];
            ids.forEach((id) => {
                try {
                    if (typeof window.exactoPrepararCanvasFirmaVisible === 'function') {
                        window.exactoPrepararCanvasFirmaVisible(id);
                    } else if (typeof window.inicializarFirma === 'function') {
                        window.inicializarFirma(id);
                        if (typeof window.pintarFondoBlancoFirma === 'function') {
                            window.pintarFondoBlancoFirma(id);
                        }
                    }
                } catch (err) {
                    console.warn('No se pudo preparar firma de entrega:', id, err);
                }
            });
        }

        function abrirModalFirmasEntrega() {
            // Copiar info del equipo al modal de firmas
            const infoMarca = document.getElementById('equipoInfoMarca');
            const infoModelo = document.getElementById('equipoInfoModelo');
            const eqMarca = document.getElementById('equipoEntregaMarca');
            const eqModelo = document.getElementById('equipoEntregaModelo');
            if (infoMarca && eqMarca) eqMarca.textContent = infoMarca.textContent;
            if (infoModelo && eqModelo) eqModelo.textContent = infoModelo.textContent;

            const radioCliente = document.getElementById('entregaQuienCliente');
            if (radioCliente) radioCliente.checked = true;
            exactoSincronizarQuienRecibeEntrega();

            // Cerrar modal de confirmación y abrir modal de firmas PRIMERO
            // (si se inicializa el canvas oculto, queda negro).
            const modalConf = document.getElementById('modalEntregaEquipo');
            if (modalConf) {
                modalConf.classList.add('hidden');
                modalConf.classList.remove('flex');
                modalConf.style.display = 'none';
            }
            const modalFirmas = document.getElementById('modalFirmasEntrega');
            if (modalFirmas) {
                if (modalFirmas.parentNode !== document.body) {
                    document.body.appendChild(modalFirmas);
                }
                modalFirmas.classList.remove('hidden');
                modalFirmas.classList.add('flex');
                modalFirmas.style.display = 'flex';
                modalFirmas.style.alignItems = 'center';
                modalFirmas.style.justifyContent = 'center';
                modalFirmas.style.padding = '1rem';
                modalFirmas.style.zIndex = '20000';
            }
            document.body.style.overflow = 'hidden';

            requestAnimationFrame(exactoPrepararFirmasEntregaModal);
            setTimeout(exactoPrepararFirmasEntregaModal, 80);
        }

        document.getElementById('btnCancelarFirmasEntrega')?.addEventListener('click', function() {
            const modalFirmas = document.getElementById('modalFirmasEntrega');
            if (modalFirmas) {
                modalFirmas.classList.add('hidden');
                modalFirmas.classList.remove('flex');
                modalFirmas.style.display = 'none';
            }
            document.body.style.overflow = '';
            // Regresar al modal de confirmación
            const modalConf = document.getElementById('modalEntregaEquipo');
            if (modalConf) {
                modalConf.classList.remove('hidden');
                modalConf.classList.add('flex');
                modalConf.style.display = 'flex';
                modalConf.style.alignItems = 'center';
                modalConf.style.justifyContent = 'center';
                modalConf.style.padding = '1rem';
                modalConf.style.zIndex = '10160';
            }
            document.body.style.overflow = 'hidden';
        });

        document.getElementById('btnLimpiarFirmaClienteEntrega')?.addEventListener('click', function() {
            if (typeof window.limpiarFirma === 'function') {
                window.limpiarFirma('firmaClienteEntrega');
            }
        });

        document.getElementById('btnLimpiarFirmaTecnicoEntrega')?.addEventListener('click', function() {
            if (typeof window.limpiarFirma === 'function') {
                window.limpiarFirma('firmaTecnicoEntrega');
            }
        });

        document.getElementById('btnGuardarFirmasEntrega')?.addEventListener('click', async function() {
            const idOrden = Number(document.getElementById('id_orden_c')?.value || 0);
            const numEquipo = Number(window.exactoEntregaEquipoSeleccionado) || 0;
            if (idOrden <= 0) {
                exactoShowAlert('No hay orden activa', {title: 'Error', icon: 'error'});
                return;
            }
            if (numEquipo < 1) {
                exactoShowAlert('Selecciona el equipo a entregar desde el ícono de camión.', {
                    title: 'Entrega por Equipo',
                    icon: 'error',
                });
                return;
            }
            const quienRecibe = exactoNombreQuienRecibeEntrega();
            if (quienRecibe.length < 3) {
                exactoShowAlert(
                    exactoEsEntregaPorTercero()
                        ? 'Escribe el nombre completo del tercero que recoge el equipo.'
                        : 'Indica el nombre de quien recibe el equipo.',
                    { title: 'Quién recibe', icon: 'error' }
                );
                document.getElementById('inputRecibidoClienteEntrega')?.focus();
                return;
            }
            if (exactoEsEntregaPorTercero()) {
                const confirmado = await exactoShowConfirm(
                    `¿Confirmas que ${quienRecibe} recogerá este equipo como tercero?`,
                    {
                        title: 'Confirmar entrega a tercero',
                        confirmText: 'Sí, entregar',
                        cancelText: 'Cancelar',
                        icon: 'warning',
                    }
                );
                if (!confirmado) {
                    document.getElementById('inputRecibidoClienteEntrega')?.focus();
                    return;
                }
            }
            // Guardar la orden con estatus Entregado (incluye firmas del modal)
            guardarOrdenConEstatus(idOrden, 'Entregado');
        });

        function guardarOrdenConEstatus(idOrden, estatus) {
            const form = document.getElementById('ordenForm');
            if (!form) {
                return exactoShowAlert('No se encontró el formulario', {title: 'Error', icon: 'error'});
            }

            const numEquipo = Number(window.exactoEntregaEquipoSeleccionado) || 0;
            if (numEquipo < 1) {
                return exactoShowAlert('Selecciona el equipo a entregar desde el ícono de camión.', {
                    title: 'Entrega por Equipo',
                    icon: 'error',
                });
            }

            if (estatus === 'Entregado') {
                const quienRecibe = exactoNombreQuienRecibeEntrega();
                if (quienRecibe.length < 3) {
                    return exactoShowAlert(
                        exactoEsEntregaPorTercero()
                            ? 'Escribe el nombre completo del tercero que recoge el equipo.'
                            : 'Indica el nombre de quien recibe el equipo.',
                        { title: 'Quién recibe', icon: 'error' }
                    );
                }
            }

            const formData = new FormData(form);
            // Mantener observaciones en texto plano (igual que el guardado normal)
            if (typeof exactoLeerTextosObservaciones === 'function') {
                formData.delete('observaciones[]');
                exactoLeerTextosObservaciones().forEach((texto) => {
                    formData.append('observaciones[]', texto);
                });
            }

            // Sobrescribir el estatus y modo de la entrega por equipo
            formData.set('estatus', estatus);
            formData.set('modo_completar', '0');
            formData.set('entrega_por_equipo', '1');
            formData.set('equipo_indice', String(numEquipo));
            // Preferir id real de BD; si no hay, el servidor acepta el índice 1..N.
            const idEquipoDb = Number(window.exactoEntregaEquipoDbId) || exactoResolverIdEquipoDb(numEquipo) || 0;
            formData.set('id_equipo', String(idEquipoDb > 0 ? idEquipoDb : numEquipo));
            // Persistir acciones del equipo en el payload (Terminado=1, Entregado=2).
            const accionesGuardar = estatus === 'Entregado' ? 2 : (estatus === 'Terminado' ? 1 : 0);
            if (accionesGuardar > 0) {
                formData.set(`equipos[${numEquipo - 1}][acciones]`, String(accionesGuardar));
            }
            if (estatus === 'Entregado') {
                formData.set('recibido_cliente', exactoNombreQuienRecibeEntrega());
                formData.set('entrega_quien_recibe', exactoEsEntregaPorTercero() ? 'tercero' : 'cliente');
            }

            // Firmas del modal de entrega
            const firmaFn = typeof exactoFirmaDataUrlSiHay === 'function'
                ? exactoFirmaDataUrlSiHay
                : (typeof window.exactoFirmaDataUrlSiHay === 'function' ? window.exactoFirmaDataUrlSiHay : null);
            const firmaClienteDataUrl = firmaFn ? firmaFn('firmaClienteEntrega') : '';
            const firmaTecnicoDataUrl = firmaFn ? firmaFn('firmaTecnicoEntrega') : '';
            formData.set('firmaCliente', firmaClienteDataUrl || '');
            formData.set('firmaTecnico', firmaTecnicoDataUrl || '');

            // CSRF en FormData por si el middleware lo exige
            const csrf = (typeof exactoCsrfToken === 'function')
                ? exactoCsrfToken()
                : ((document.querySelector('meta[name="csrf-token"]') || {}).content
                    || window.EXACTO_CSRF_TOKEN
                    || document.querySelector('#ordenForm input[name="_token"]')?.value
                    || '');
            if (csrf && !formData.get('_token')) {
                formData.set('_token', csrf);
            }

            const btnGuardarFirmas = document.getElementById('btnGuardarFirmasEntrega');
            const btnGuardarEntrega = document.getElementById('btnGuardarEntregaEquipo');
            if (btnGuardarFirmas) btnGuardarFirmas.disabled = true;
            if (btnGuardarEntrega) btnGuardarEntrega.disabled = true;

            const urlRegistrar = (typeof exactoUrlApiRegistrar === 'function')
                ? exactoUrlApiRegistrar()
                : ((typeof window.EXACTO_REGISTRAR_ORDEN_URL === 'string' && window.EXACTO_REGISTRAR_ORDEN_URL.trim())
                    ? window.EXACTO_REGISTRAR_ORDEN_URL.trim()
                    : '/api/ordenes/registrar');

            return fetch(urlRegistrar, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf,
                },
                body: formData
            }).then(async (r) => {
                const raw = await r.text();
                let data = null;
                try {
                    data = raw.trim() === '' ? null : JSON.parse(raw);
                } catch (e) {
                    data = null;
                }

                if (!data || typeof data !== 'object') {
                    const mensajeRespuesta = r.status === 419
                        ? 'La sesión venció. Recarga la página e intenta nuevamente.'
                        : (r.status >= 500
                            ? 'El servidor tuvo un error interno al guardar la orden.'
                            : `El servidor devolvió una respuesta no reconocida (HTTP ${r.status}).`);
                    console.error('Entrega por equipo: respuesta cruda del servidor', {
                        status: r.status,
                        body: raw,
                    });
                    await exactoShowAlert(mensajeRespuesta, {
                        title: 'Error',
                        icon: 'error',
                    });
                    return;
                }

                if (!r.ok || !data.success) {
                    await exactoShowAlert(
                        String(data.message || `No se pudo guardar la orden (HTTP ${r.status}).`),
                        {title: 'Error', icon: 'error'}
                    );
                    return;
                }

                if (data.success) {
                    if (typeof exactoPermitirSalidaOrdenForm === 'function') {
                        exactoPermitirSalidaOrdenForm();
                    } else if (typeof window.exactoPermitirSalidaOrdenForm === 'function') {
                        window.exactoPermitirSalidaOrdenForm();
                    }
                    const accResp = Number(data.equipo_acciones);
                    const numEq = Number(window.exactoEntregaEquipoSeleccionado) || 0;
                    const accionesConfirmadas = Number.isFinite(accResp) && accResp > 0
                        ? accResp
                        : accionesGuardar;
                    if (numEq > 0 && accionesConfirmadas > 0) {
                        // Cambiar el color únicamente después de que el servidor confirme el guardado.
                        exactoMarcarAccionesEquipoLocal(numEq, accionesConfirmadas);
                    }
                    const modalFirmas = document.getElementById('modalFirmasEntrega');
                    if (modalFirmas) {
                        modalFirmas.classList.add('hidden');
                        modalFirmas.classList.remove('flex');
                        modalFirmas.style.display = 'none';
                    }
                    const modalConf = document.getElementById('modalEntregaEquipo');
                    if (modalConf) {
                        modalConf.classList.add('hidden');
                        modalConf.classList.remove('flex');
                        modalConf.style.display = 'none';
                    }
                    document.body.style.overflow = '';
                    exactoQuitarResaltadoEquipos();
                    await exactoShowAlert(data.message || 'Orden guardada', {title: 'Éxito', icon: 'success'});
                    window.location.reload();
                }
            })
              .catch(async () => {
                  await exactoShowAlert('Error de red al guardar', {title: 'Error', icon: 'error'});
              })
              .finally(() => {
                  if (btnGuardarFirmas) btnGuardarFirmas.disabled = false;
                  if (btnGuardarEntrega) btnGuardarEntrega.disabled = false;
              });
        }

    </script>

  

    <style>
        @@media print {
            body {
                background: white;
                padding: 0;
            }
            .max-w-7xl {
                box-shadow: none;
            }
            button {
                display: none !important;
            }
        }

        canvas {
            border: 1px solid #ccc;
            background: white;
        }

        /* Evita que la pgina se desplace al firmar con dedo o lpiz en tableta */
        .exacto-firma-pad,
        canvas.exacto-firma-canvas {
            touch-action: none;
            -ms-touch-action: none;
            overscroll-behavior: contain;
            user-select: none;
            -webkit-user-select: none;
        }

        .exacto-firma-pad {
            overflow: hidden;
        }

        canvas.exacto-firma-canvas {
            touch-action: none;
        }

        .orden-firmas-skip {
            display: none !important;
        }

        #exactoUiModal.flex,
        #modalLiquidarSaldo.flex,
        #modalFirmasEntrega.flex,
        #modalEntregaEquipo.flex {
            display: flex !important;
        }

        #exactoUiModalMessage,
        #ordenSubmitStatusText,
        .exacto-ui-modal-body {
            text-align: center;
        }

        /* Sin flechas en inputs numéricos del formulario de orden */
        #ordenForm input[type="number"]::-webkit-outer-spin-button,
        #ordenForm input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        #ordenForm input[type="number"] {
            -moz-appearance: textfield;
            appearance: textfield;
        }

        .exacto-money-field {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            width: 100%;
        }
        .exacto-money-field > .exacto-money-prefix {
            flex-shrink: 0;
            font-weight: 600;
            color: #475569;
        }
        .exacto-money-field > input {
            flex: 1 1 auto;
            min-width: 0;
        }
    </style>
</body>
