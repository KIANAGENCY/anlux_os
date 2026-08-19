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
<th
                                  class="
                                    p-3
                                    text-left
                                    border
                                  "
>ANADIR</th>
                                   @php $mostrarAcciones = $idEditar > 0 && in_array($estatusFormValor, ['En proceso', 'Terminado'], true); @endphp
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
                                         data-id-equipo="0">
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

            {{-- Botón Entrega por equipo: solo en editor de orden y estatus "En proceso" o "Terminado" --}}
            <?php if ($idEditar > 0 && in_array($estatusFormValor, ['En proceso', 'Terminado'], true)): ?>
            <div class="mt-4 p-3 border rounded bg-green-50 text-sm">
                <button type="button"
                    class="btn-entrega-equipo text-green-600 font-bold hover:text-green-800 block w-full text-left mb-2"
                    title="Entrega por equipo">
                    <i class="fas fa-truck mr-1"></i>Entrega por equipo
                </button>
                <p class="text-xs text-green-700">Solo visible cuando la orden está en <strong>En proceso</strong>.</p>
            </div>
            <?php endif; ?>

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
                    <input type="hidden" name="saldo_pagado_confirmado" id="saldoPagadoConfirmado" value="0">
                    <div class="mt-3">
                        <div class="flex flex-col items-end gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
                            <button
                                type="button"
                                id="btnPagarSaldoPendiente"
                                class="inline-flex items-center justify-center rounded-lg bg-green-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                                title="Liquida todo el saldo pendiente"
                            >
                                <i class="fas fa-cash-register mr-2"></i>
                                Pagar saldo pendiente
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

            <!-- Modal: Liquidar Saldo - Seleccionar equipos -->
            <div id="modalLiquidarSaldo" class="hidden fixed inset-0 z-[10150] bg-slate-950/80" role="dialog" aria-modal="true" aria-labelledby="modalLiquidarSaldoTitle" style="display:none;align-items:center;justify-content:center;padding:1rem;z-index:10150;">
                <div class="w-full max-w-2xl bg-white rounded-2xl shadow-2xl max-h-[90vh] overflow-y-auto">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h3 id="modalLiquidarSaldoTitle" class="text-center text-xl font-bold text-blue-900">Liquidar Saldo por Equipo</h3>
                    </div>
                    <div class="p-5 space-y-2 max-h-[60vh] overflow-y-auto">
                        <p class="text-sm text-slate-600 mb-4">Selecciona los equipos a liquidar:</p>
                        <div id="equiposLiquidarSaldoContainer" class="space-y-1">
                            <!-- Equipo rows will be populated by JS -->
                        </div>
                    </div>
                    <div class="p-5 border-t border-slate-200 flex justify-end gap-3">
                        <button type="button" id="btnCancelarLiquidarSaldo" class="rounded-lg border-2 border-slate-300 bg-white px-5 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancelar</button>
                        <button type="button" id="btnConfirmarLiquidarSaldo" class="rounded-lg bg-red-600 px-5 py-2 text-sm font-bold text-white hover:bg-red-700">Liquidar Seleccionados</button>
                    </div>
                </div>
            </div>

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
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2.5 cursor-pointer transition hover:border-blue-400 hover:bg-blue-50">
                                <input type="checkbox" id="chkTerminado" class="checkbox-acceso w-4 h-4 rounded border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                <span class="text-sm font-medium text-blue-900">Marcar como Terminado</span>
                            </label>
                            <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2.5 cursor-pointer transition hover:border-blue-400 hover:bg-blue-50">
                                <input type="checkbox" id="chkEntregado" class="checkbox-acceso w-4 h-4 rounded border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                <span class="text-sm font-medium text-blue-900">Marcar como Entregado</span>
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
            <div id="modalFirmasEntrega" class="hidden fixed inset-0 z-[10170] items-center justify-center bg-slate-950/80" role="dialog" aria-modal="true" aria-labelledby="modalFirmasEntregaTitle" style="display:none;align-items:center;justify-content:center;padding:1rem;z-index:10170;">
                <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl overflow-hidden">
                    <div class="bg-blue-600 px-5 py-3">
                        <h3 id="modalFirmasEntregaTitle" class="text-center text-lg font-bold text-white flex items-center justify-center gap-2">
                            <i class="fas fa-file-signature"></i> Firmas de Entrega
                        </h3>
                    </div>
                    <div class="p-4 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <!-- Firma Cliente -->
                            <div>
                                <label class="block text-sm font-bold text-blue-900">Firma del Cliente</label>
                                <div class="bg-white border-2 border-blue-300 rounded-lg overflow-hidden" style="height: 170px;">
                                    <canvas id="firmaClienteEntrega" class="w-full h-full rounded-lg cursor-crosshair" style="display: block; background: white;"></canvas>
                                </div>
                                <button type="button" id="btnLimpiarFirmaClienteEntrega" class="mt-2 inline-flex items-center gap-1 rounded-lg border border-red-300 bg-red-50 px-3 py-1.5 text-xs font-bold text-red-600 hover:bg-red-100">
                                    <i class="fas fa-eraser"></i> Limpiar firma
                                </button>
                            </div>
                            <!-- Firma Tecnico -->
                            <div>
                                <label class="block text-sm font-bold text-blue-900">Firma del Tecnico</label>
                                <div class="bg-white border-2 border-blue-300 rounded-lg overflow-hidden" style="height: 170px;">
                                    <canvas id="firmaTecnicoEntrega" class="w-full h-full rounded-lg cursor-crosshair" style="display: block; background: white;"></canvas>
                                </div>
                                <button type="button" id="btnLimpiarFirmaTecnicoEntrega" class="mt-2 inline-flex items-center gap-1 rounded-lg border border-red-300 bg-red-50 px-3 py-1.5 text-xs font-bold text-red-600 hover:bg-red-100">
                                    <i class="fas fa-eraser"></i> Limpiar firma
                                </button>
                            </div>
                        </div>
                        <div class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm">
                            <span class="font-bold text-blue-700"><i class="fas fa-microchip mr-1"></i>Equipo:</span>
                            <span id="equipoEntregaMarca" class="font-semibold text-slate-800">-</span>
                            <span id="equipoEntregaModelo" class="font-semibold text-slate-800">-</span>
                        </div>
                    </div>
                    <div class="p-4 border-t border-slate-200 flex justify-end gap-2">
                        <button type="button" id="btnCancelarFirmasEntrega" class="rounded-lg border-2 border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button>
                        <button type="button" id="btnGuardarFirmasEntrega" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700 shadow-sm">Guardar y Enviar (WhatsApp/Correo)</button>
                    </div>
                </div>
            </div>

        </form>
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

    <div id="exactoUiModal" class="hidden fixed inset-0 z-[10110] items-center justify-center bg-slate-950/70 p-4" role="dialog" aria-modal="true" aria-labelledby="exactoUiModalTitle" style="z-index:10110;">
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
        function exactoQuitarResaltadoEquipos() {
            document.querySelectorAll('#equiposTableBody .equipo-row').forEach(f => {
                f.classList.remove('exacto-equipo-seleccionado', 'bg-blue-50', 'ring-2', 'ring-blue-300');
            });
        }

        function exactoResaltarEquipoRow(idEquipo) {
            exactoQuitarResaltadoEquipos();
            const filas = document.querySelectorAll('#equiposTableBody .equipo-row');
            const fila = filas[idEquipo];
            if (fila) {
                fila.classList.add('exacto-equipo-seleccionado', 'bg-blue-50', 'ring-2', 'ring-blue-300');
                fila.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        window.exactoBtnEntregaEquipo = function() {
            const idEquipo = Number(this.dataset.idEquipo ?? 0);
            if (Number.isNaN(idEquipo)) return;
            // Guardar el equipo seleccionado para el guardado de la entrega
            window.exactoEntregaEquipoSeleccionado = idEquipo;

            // Resaltar la fila del equipo que se va a entregar
            exactoResaltarEquipoRow(idEquipo);

            // Mostrar modal de confirmación
            const modal = document.getElementById('modalEntregaEquipo');
            if (!modal) return;

            // Obtener datos del equipo: desde la fila de la tabla o del JSON embebido
            let marca = '';
            let modelo = '';
            let serie = '';
            const fila = this.closest ? this.closest('.equipo-row') : null;
            if (fila) {
                const inpMarca = fila.querySelector('[name*="[marca]"]');
                const inpModelo = fila.querySelector('[name*="[modelo]"]');
                const inpSerie = fila.querySelector('[name*="[serie]"]');
                marca = inpMarca ? String(inpMarca.value || '').trim() : '';
                modelo = inpModelo ? String(inpModelo.value || '').trim() : '';
                serie = inpSerie ? String(inpSerie.value || '').trim() : '';
            }
            if (!marca && !modelo && !serie) {
                const ordenJson = window.EXISTENTE_ORDEN_JSON ?? {};
                const equipos = Array.isArray(ordenJson.equipos) ? ordenJson.equipos : [];
                const equipo = equipos[idEquipo] || {};
                marca = String(equipo.marca || '').trim();
                modelo = String(equipo.modelo || '').trim();
                serie = String(equipo.serie || '').trim();
            }
            marca = marca || '<?php echo htmlspecialchars((string)($cab['marca'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>';
            modelo = modelo || '<?php echo htmlspecialchars((string)($cab['modelo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>';

            document.getElementById('equipoInfoMarca').textContent = marca;
            document.getElementById('equipoInfoModelo').textContent = modelo;
            document.getElementById('equipoInfoSerie').textContent = serie || '-';
            document.getElementById('equipoInfo').classList.remove('hidden');

            // Cargar firmas existentes si las hay
            const firmaCExistente = '<?php echo htmlspecialchars($cab['firma_c_e'] ?? '', ENT_QUOTES, 'UTF-8'); ?>';
            const firmaTExistente = '<?php echo htmlspecialchars($cab['firma_t_r'] ?? '', ENT_QUOTES, 'UTF-8'); ?>';

            // Si ya hay firmas, mostrarlas en los canvas (simple placeholder)
            if (firmaCExistente) {
                const ctxC = document.getElementById('firmaClienteEntrega')?.getContext('2d');
                if (ctxC) {
                    ctxC.clearRect(0, 0, ctxC.canvas.width, ctxC.canvas.height);
                }
            }
            if (firmaTExistente) {
                const ctxT = document.getElementById('firmaTecnicoEntrega')?.getContext('2d');
                if (ctxT) {
                    ctxT.clearRect(0, 0, ctxT.canvas.width, ctxT.canvas.height);
                }
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.style.display = 'flex';
            modal.style.alignItems = 'center';
            modal.style.justifyContent = 'center';
            modal.style.padding = '1rem';
            modal.style.zIndex = '10160';
            document.body.style.overflow = 'hidden';
        };

        // Agregar event listeners a los botones "Entrega por Equipo"
        document.addEventListener('DOMContentLoaded', function() {
            const btnsEntrega = document.querySelectorAll('.btn-entrega-equipo');
            btnsEntrega.forEach(btn => {
                btn.addEventListener('click', window.exactoBtnEntregaEquipo);
            });
        });

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
            if (this.checked) {
                // Validar saldo pendiente - consultar al servidor
                const idOrden = Number(document.getElementById('id_orden_c')?.value || 0);
                if (idOrden) {
                    fetch('/api/ordenes/validar-saldo', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': exactoCsrfToken()
                        },
                        body: JSON.stringify({id_orden: idOrden})
                    }).then(r => r.json())
                      .then(data => {
                          if (data && data.valid) {
                              pendienteEl.classList.add('hidden');
                          } else {
                              pendienteEl.classList.remove('hidden');
                              this.checked = false;
                              exactoShowAlert((data && data.message) || 'Saldo pendiente no liquidado', {title: 'Validación', icon: 'error'});
                          }
                      });
                }
            } else {
                pendienteEl.classList.add('hidden');
            }
        });

        // Marcar como Entregado y guardar
        document.getElementById('btnGuardarEntregaEquipo')?.addEventListener('click', function() {
            const chkTerminado = document.getElementById('chkTerminado').checked;
            const chkEntregado = document.getElementById('chkEntregado').checked;
            const idOrden = Number(document.getElementById('id_orden_c')?.value || 0);

            if (idOrden <= 0) {
                exactoShowAlert('No hay orden activa', {title: 'Error', icon: 'error'});
                return;
            }

            if (!chkTerminado && !chkEntregado) {
                exactoShowAlert('Selecciona al menos un estatus (Terminado o Entregado)', {title: 'Error', icon: 'error'});
                return;
            }

            // Si es Entregado, primero capturar firmas en el modal de firmas
            if (chkEntregado) {
                abrirModalFirmasEntrega();
                return;
            }

            // Solo Terminado: validar saldo y guardar
            fetch('/api/ordenes/validar-saldo', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': exactoCsrfToken()
                },
                body: JSON.stringify({id_orden: idOrden})
            }).then(r => r.json())
              .then(data => {
                  if (!data.valid) {
                      exactoShowAlert(data.message || 'Saldo pendiente no liquidado', {title: 'Error', icon: 'error'});
                      return;
                  }
                  guardarOrdenConEstatus(idOrden, 'Terminado');
              });
        });

        // ===== LÓGICA: Modal de firmas de entrega =====
        function abrirModalFirmasEntrega() {
            // Copiar info del equipo al modal de firmas
            const infoMarca = document.getElementById('equipoInfoMarca');
            const infoModelo = document.getElementById('equipoInfoModelo');
            const eqMarca = document.getElementById('equipoEntregaMarca');
            const eqModelo = document.getElementById('equipoEntregaModelo');
            if (infoMarca && eqMarca) eqMarca.textContent = infoMarca.textContent;
            if (infoModelo && eqModelo) eqModelo.textContent = infoModelo.textContent;

            // Inicializar lienzos de firma si aún no están
            if (typeof inicializarFirma === 'function') {
                inicializarFirma('firmaClienteEntrega');
                inicializarFirma('firmaTecnicoEntrega');
            }

            // Cerrar modal de confirmación y abrir modal de firmas
            const modalConf = document.getElementById('modalEntregaEquipo');
            if (modalConf) {
                modalConf.classList.add('hidden');
                modalConf.classList.remove('flex');
                modalConf.style.display = 'none';
            }
            const modalFirmas = document.getElementById('modalFirmasEntrega');
            if (modalFirmas) {
                modalFirmas.classList.remove('hidden');
                modalFirmas.classList.add('flex');
                modalFirmas.style.display = 'flex';
                modalFirmas.style.alignItems = 'center';
                modalFirmas.style.justifyContent = 'center';
                modalFirmas.style.padding = '1rem';
                modalFirmas.style.zIndex = '10170';
            }
            document.body.style.overflow = 'hidden';
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
            if (typeof limpiarFirma === 'function') {
                limpiarFirma('firmaClienteEntrega');
            } else if (typeof canvasContexts !== 'undefined' && canvasContexts['firmaClienteEntrega']) {
                const canvas = document.getElementById('firmaClienteEntrega');
                canvasContexts['firmaClienteEntrega'].clearRect(0, 0, canvas.width, canvas.height);
                if (typeof pintarFondoBlancoFirma === 'function') {
                    pintarFondoBlancoFirma('firmaClienteEntrega');
                }
            }
        });

        document.getElementById('btnLimpiarFirmaTecnicoEntrega')?.addEventListener('click', function() {
            if (typeof limpiarFirma === 'function') {
                limpiarFirma('firmaTecnicoEntrega');
            } else if (typeof canvasContexts !== 'undefined' && canvasContexts['firmaTecnicoEntrega']) {
                const canvas = document.getElementById('firmaTecnicoEntrega');
                canvasContexts['firmaTecnicoEntrega'].clearRect(0, 0, canvas.width, canvas.height);
                if (typeof pintarFondoBlancoFirma === 'function') {
                    pintarFondoBlancoFirma('firmaTecnicoEntrega');
                }
            }
        });

        document.getElementById('btnGuardarFirmasEntrega')?.addEventListener('click', function() {
            const idOrden = Number(document.getElementById('id_orden_c')?.value || 0);
            if (idOrden <= 0) {
                exactoShowAlert('No hay orden activa', {title: 'Error', icon: 'error'});
                return;
            }
            // Guardar la orden con estatus Entregado (incluye firmas del modal)
            guardarOrdenConEstatus(idOrden, 'Entregado');
        });

        function guardarOrdenConEstatus(idOrden, estatus) {
            const form = document.getElementById('ordenForm');
            if (!form) {
                exactoShowAlert('No se encontró el formulario', {title: 'Error', icon: 'error'});
                return;
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
            formData.set('id_equipo', String(window.exactoEntregaEquipoSeleccionado || 0));

            // Firmas del modal de entrega
            const firmaClienteDataUrl = exactoFirmaDataUrlSiHay('firmaClienteEntrega');
            const firmaTecnicoDataUrl = exactoFirmaDataUrlSiHay('firmaTecnicoEntrega');
            formData.set('firmaCliente', firmaClienteDataUrl || '');
            formData.set('firmaTecnico', firmaTecnicoDataUrl || '');

            fetch('/api/ordenes/registrar', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            }).then(r => r.json())
              .then(data => {
                  if (data.success) {
                      exactoShowAlert(data.message || 'Orden guardada', {title: 'Éxito', icon: 'success'});
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
                      // Recargar o actualizar vista
                      window.location.reload();
                  } else {
                      exactoShowAlert(data.message || 'Error al guardar', {title: 'Error', icon: 'error'});
                  }
              })
              .catch(() => {
                  exactoShowAlert('Error de red al guardar', {title: 'Error', icon: 'error'});
              });
        }

        // ===== LÓGICA: Liquidar Saldo por Equipo =====
        window.exactoBtnLiquidarSaldo = function() {
            const idOrden = Number(document.getElementById('id_orden_c')?.value || 0);
            if (!idOrden) {
                exactoShowAlert('No hay orden activa', {title: 'Error', icon: 'error'});
                return;
            }

            // Cargar lista de equipos desde el JSON o consultar BD
            const modal = document.getElementById('modalLiquidarSaldo');
            if (!modal) return;

            // Obtener equipos de la orden actual
            const ordenJson = window.EXISTENTE_ORDEN_JSON ?? {};
            const equipos = ordenJson.equipos || [];

            const container = document.getElementById('equiposLiquidarSaldoContainer');
            container.innerHTML = '';

            if (!equipos || equipos.length === 0) {
                container.innerHTML = '<p class="text-slate-500">No hay equipos registrados en esta orden.</p>';
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.style.display = 'flex';
                return;
            }

            equipos.forEach((eq, idx) => {
                const row = document.createElement('div');
                row.className = 'p-3 border rounded bg-blue-50';
                row.innerHTML = `
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" class="checkbox-acceso w-4 h-4 rounded border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-300" name="equipo_liquidar[]" value="${eq.id_equipo || idx}">
                        <span class="text-sm text-blue-900 flex-1">${eq.marca || 'Sin marca'} - ${eq.modelo || 'Sin modelo'}</span>
                    </label>
                `;
                container.appendChild(row);
            });

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.style.display = 'flex';
            modal.style.alignItems = 'center';
            modal.style.justifyContent = 'center';
            modal.style.padding = '1rem';
            modal.style.zIndex = '10150';
            document.body.style.overflow = 'hidden';
        };

        document.getElementById('btnCancelarLiquidarSaldo')?.addEventListener('click', function() {
            const modal = document.getElementById('modalLiquidarSaldo');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
        });

        document.getElementById('btnConfirmarLiquidarSaldo')?.addEventListener('click', function() {
            const idOrden = Number(document.getElementById('id_orden_c')?.value || 0);
            const checkboxes = document.querySelectorAll('input[name="equipo_liquidar[]"]:checked');
            const idsEquipo = Array.from(checkboxes).map(c => Number(c.value));

            if (idsEquipo.length === 0) {
                exactoShowAlert('Selecciona al menos un equipo', {title: 'Error', icon: 'error'});
                return;
            }

            // Llamar al backend para liquidar saldo de los equipos seleccionados
            fetch('/api/ordenes/liquidar-saldo-equipo', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': exactoCsrfToken()
                },
                body: JSON.stringify({id_orden: idOrden, ids_equipo: idsEquipo})
            }).then(r => r.json())
              .then(data => {
                  if (data.success) {
                      exactoShowAlert(data.message || 'Saldo liquidado', {title: 'Éxito', icon: 'success'});
                      modalLiquidarSaldo.classList.add('hidden');
                      modalLiquidarSaldo.classList.remove('flex');
                      modalLiquidarSaldo.style.display = 'none';
                      document.body.style.overflow = '';
                      window.location.reload();
                  } else {
                      exactoShowAlert(data.message || 'Error al liquidar', {title: 'Error', icon: 'error'});
                  }
              });
        });
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

        #exactoUiModal.flex {
            display: flex;
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
