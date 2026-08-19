// JavaScript del listado de ordenes.

        const csrfToken = window.EXACTO_CSRF_TOKEN || '';
        const baseUrl = String(window.EXACTO_BASE_URL || '').replace(/\/$/, '');
        const exactoUrl = (path) => `${baseUrl}${path.startsWith('/') ? path : `/${path}`}`;
        let currentPage = 1;
        let totalPages = 1;
        /** 'fecha' = m&aacute;s recientes primero; 'estatus' = Recepci&oacute;n &rarr; En proceso &rarr; Terminado &rarr; Entregado, luego fecha */
        let sortOrdenes = 'fecha';

        const normalizeStatus = (estatus) => String(estatus || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

        const statusLabel = (estatus) => {
            const normalized = normalizeStatus(estatus);
            if (normalized.includes('recepcion')) return 'Recepci\u00f3n';
            if (normalized.includes('proceso')) return 'En proceso';
            if (normalized.includes('terminado')) return 'Terminado';
            if (normalized.includes('entregado')) return 'Entregado';
            return estatus || 'Desconocido';
        };

        const statusColor = (estatus) => {
            const normalized = normalizeStatus(estatus);
            if (normalized.includes('recepcion')) return 'bg-red-500 text-white';
            if (normalized.includes('proceso')) return 'bg-orange-500 text-white';
            if (normalized.includes('terminado')) return 'bg-yellow-500 text-black';
            if (normalized.includes('entregado')) return 'bg-green-500 text-white';
            return 'bg-gray-400 text-white';
        };

        const statusToRadio = (estatus) => {
            const normalized = normalizeStatus(estatus);
            if (normalized.includes('recepcion')) return 'rojo';
            if (normalized.includes('proceso')) return 'naranja';
            if (normalized.includes('terminado')) return 'amarillo';
            if (normalized.includes('entregado')) return 'verde';
            return 'rojo';
        };

        const updateSortEstatusUi = () => {
            const iconFecha = document.getElementById('iconSortEstatusFecha');
            const iconFlujo = document.getElementById('iconSortEstatusFlujo');
            const btn = document.getElementById('btnSortEstatus');
            if (!iconFecha || !iconFlujo || !btn) return;
            if (sortOrdenes === 'estatus') {
                iconFecha.classList.add('hidden');
                iconFlujo.classList.remove('hidden');
                btn.title = 'Orden: flujo de estatus. Clic para ordenar por fecha de entrada (m\u00e1s recientes primero).';
                btn.setAttribute('aria-pressed', 'true');
            } else {
                iconFecha.classList.remove('hidden');
                iconFlujo.classList.add('hidden');
                btn.title = 'Orden: fecha de entrada. Clic para ordenar por estatus (Recepci\u00f3n \u2192 Entregado).';
                btn.setAttribute('aria-pressed', 'false');
            }
        };

        const tabla = document.getElementById('tabla');
        const searchInput = document.getElementById('search');
        const perPageSelect = document.getElementById('perPage');
        const paginationInfo = document.getElementById('paginationInfo');
        const pageNumber = document.getElementById('pageNumber');
        const prevPage = document.getElementById('prevPage');
        const nextPage = document.getElementById('nextPage');

        let pdfOrdenesIdActual = 0;
        const urlPdfOrden = (id, inline) => {
            const bust = `${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;
            const q = inline ? 'inline=1&refresh_pdf=1' : 'refresh_pdf=1';
            return exactoUrl(`/pdf/orden/${encodeURIComponent(id)}?${q}&nocache=1&_=${bust}`);
        };
        const urlPdfInlineOrdenes = (id) => urlPdfOrden(id, true);

        /** Escapa texto para usarlo dentro de comillas dobles en atributos HTML (data-*). */
        function escHtmlAttr(val) {
            return String(val ?? '')
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;');
        }

        function escHtml(val) {
            return String(val ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        /**
         * Abre el PDF sin cache del navegador: fetch no-store + blob URL.
         * Así Firefox/Chrome no reutilizan un PDF viejo al regenerar layout.
         */
        async function abrirPdfOrdenSinCache(id, inline = true) {
            const url = urlPdfOrden(id, inline);
            try {
                const res = await fetch(url, {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: {
                        Accept: 'application/pdf',
                        'Cache-Control': 'no-cache',
                        Pragma: 'no-cache',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (!res.ok) {
                    throw new Error('HTTP ' + res.status);
                }
                const raw = await res.blob();
                const pdfBlob = raw.type && raw.type.includes('pdf')
                    ? raw
                    : new Blob([raw], { type: 'application/pdf' });
                const objUrl = URL.createObjectURL(pdfBlob);
                const ventana = window.open(objUrl, '_blank');
                if (ventana) {
                    try { ventana.opener = null; } catch (_) { /* ignore */ }
                } else {
                    // Popup bloqueado: descarga/abre por URL con bust.
                    window.open(url, '_blank');
                }
                setTimeout(() => {
                    try { URL.revokeObjectURL(objUrl); } catch (_) { /* ignore */ }
                }, 180000);
            } catch (err) {
                console.warn('PDF sin cache falló; abriendo URL directa', err);
                window.open(url, '_blank');
            }
        }

        function abrirPdfModalOrdenes(id, folio) {
            pdfOrdenesIdActual = Number(id) || 0;
            void abrirPdfOrdenSinCache(id, true);
        }

        function cerrarPdfModalOrdenes() {
            document.getElementById('modalPdf').classList.add('hidden');
            document.getElementById('modalPdf').classList.remove('flex');
            document.getElementById('iframePdfOrdenes').src = 'about:blank';
            document.body.style.overflow = '';
            document.body.classList.remove('exacto-pdf-open');
            pdfOrdenesIdActual = 0;
        }

        function verPdfInlineDesdeEdit() {
            const id = document.getElementById('editId').value;
            const folio = document.getElementById('editFolio').value;
            if (!id) return;
            cerrarModal('modalEdit');
            abrirPdfModalOrdenes(id, folio);
        }

        const abrirModal = (id) => {
            const modal = document.getElementById(id);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };
        const cerrarModal = (id) => {
            const modal = document.getElementById(id);
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };

        function mostrarAvisoOrdenes(message, title = 'Aviso') {
            const modal = document.getElementById('ordenesUiModal');
            const titleEl = document.getElementById('ordenesUiModalTitle');
            const messageEl = document.getElementById('ordenesUiModalMessage');
            const confirmBtn = document.getElementById('ordenesUiModalConfirm');
            if (!modal || !titleEl || !messageEl || !confirmBtn) {
                window.alert(message);
                return Promise.resolve();
            }

            return new Promise((resolve) => {
                const previousOverflow = document.body.style.overflow;
                titleEl.textContent = title;
                messageEl.textContent = String(message || '');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';

                const close = () => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    document.body.style.overflow = previousOverflow;
                    confirmBtn.removeEventListener('click', onConfirm);
                    modal.removeEventListener('click', onBackdrop);
                    document.removeEventListener('keydown', onKeydown);
                    resolve();
                };
                const onConfirm = () => close();
                const onBackdrop = (event) => {
                    if (event.target === modal) {
                        close();
                    }
                };
                const onKeydown = (event) => {
                    if (event.key === 'Escape') {
                        close();
                    }
                };

                confirmBtn.addEventListener('click', onConfirm);
                modal.addEventListener('click', onBackdrop);
                document.addEventListener('keydown', onKeydown);
                confirmBtn.focus();
            });
        }

        const partesLogOrden = (orden) => {
            const logRaw = String(orden.tecnicos_log ?? orden.TECNICOS_LOG ?? '').trim();
            if (!logRaw) return [];
            return logRaw.split(' \u00b7 ').map((p) => p.trim()).filter(Boolean);
        };

        const sinDupesConsecutivos = (arr) => {
            const out = [];
            for (const x of arr) {
                if (!x) continue;
                if (out.length === 0 || out[out.length - 1] !== x) out.push(x);
            }
            return out;
        };

        /** Tecnico que abrio en taller (cabecera) o, si no hay dato, el primero registrado en el log. */
        const primerTecnicoOrden = (orden) => {
            const tr = String(orden.tecnico_recibido || '').trim();
            if (tr) return tr;
            const p = sinDupesConsecutivos(partesLogOrden(orden));
            return p[0] || '';
        };

        /** Lista única en orden: recepción + log + entrega. */
        const secuenciaInvolucradosOrden = (orden) => {
            const desdeApi = String(orden.involucrados_display ?? '').trim();
            if (desdeApi) {
                return desdeApi
                    .split(/\r?\n| → | \u2192 /)
                    .map((linea) => linea.replace(/^\d+\.\s*/, '').trim())
                    .filter(Boolean);
            }
            const nombres = [];
            const push = (nombre) => {
                const n = String(nombre || '').trim();
                if (!n) return;
                if (nombres.some((ya) => ya.toLowerCase() === n.toLowerCase())) return;
                nombres.push(n);
            };
            push(orden.tecnico_recibido);
            sinDupesConsecutivos(partesLogOrden(orden)).forEach(push);
            push(orden.entregado_por_tecnico);
            return nombres;
        };

        /** HTML numerado 1. / 2. en varias líneas para la columna Involucrados. */
        const involucradosHtmlOrden = (orden) => {
            const secuencia = secuenciaInvolucradosOrden(orden);
            if (!secuencia.length) {
                return '\u2014';
            }
            return secuencia
                .map((nombre, idx) => `${idx + 1}. ${escHtml(nombre)}`)
                .join('<br>');
        };

        const renderOrdenes = (ordenes) => {
            tabla.innerHTML = '';
            if (!ordenes.length) {
                tabla.innerHTML = '<tr><td class="p-3 border text-center" colspan="10">No se encontraron &oacute;rdenes</td></tr>';
                return;
            }
            ordenes.forEach((orden) => {
                const lockActivo = Boolean(orden.edit_lock_active) && !Boolean(orden.edit_lock_is_mine);
                const lockNombre = String(orden.edit_lock_nombre || '').trim();
                const ordenEntregada = normalizeStatus(orden.estatus).includes('entregado');
                const salidaActiva = Number(orden.salida_temporal_activa) === 1;
                const fechaSalidaTemp = orden.fecha_salida_temporal ? String(orden.fecha_salida_temporal).split(' ')[0] : '';
                const huboSalidaTemp = salidaActiva || Boolean(fechaSalidaTemp);
                const rowClass = lockActivo
                    ? 'bg-red-100 hover:bg-red-200 ring-1 ring-inset ring-red-300'
                    : (salidaActiva
                        ? 'bg-orange-50 hover:bg-orange-100 cursor-pointer'
                        : 'hover:bg-blue-100 cursor-pointer');
                const fechaEntrada = orden.fecha_entrada ? orden.fecha_entrada.split(' ')[0] : '-';
                const fechaTerminada = orden.fecha_terminada
                    ? String(orden.fecha_terminada).replace('T', ' ').slice(0, 16)
                    : '-';
                const fechaSalida = orden.fecha_salida ? orden.fecha_salida.split(' ')[0] : '-';
                const salidaTempHtml = salidaActiva
                    ? `<span class="inline-flex flex-col items-center gap-0.5" title="Equipo fuera del taller (salida temporal)">
                            <span class="inline-flex items-center rounded-full bg-orange-600 px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-wide text-white">Se dio salida</span>
                            ${fechaSalidaTemp ? `<span class="text-[11px] font-semibold text-orange-800">${escHtml(fechaSalidaTemp)}</span>` : ''}
                       </span>`
                    : (fechaSalidaTemp
                        ? `<span class="inline-flex flex-col items-center gap-0.5" title="Hubo salida temporal y el equipo ya regresó">
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-900">Salida (regresó)</span>
                                <span class="text-[11px] text-slate-600">${escHtml(fechaSalidaTemp)}</span>
                           </span>`
                        : '—');
                const avisoSalidaEstatus = salidaActiva
                    ? `<div class="mt-1"><span class="inline-flex items-center rounded-md bg-orange-600 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-white">Se dio salida</span></div>`
                    : (huboSalidaTemp
                        ? `<div class="mt-1"><span class="inline-flex items-center rounded-md bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-900">Hubo salida temp.</span></div>`
                        : '');
                const colorClass = statusColor(orden.estatus);
                const tituloLock = lockActivo
                    ? `En edición por ${lockNombre || 'otro usuario'}`
                    : (ordenEntregada ? 'Ver orden (solo lectura)' : 'Ver / completar orden');
                const botonEditarOrden = ordenEntregada
                    ? `<button type="button" class="btn-ver-orden inline-flex h-8 w-8 items-center justify-center rounded-full text-emerald-700 hover:bg-emerald-100" title="${escHtmlAttr(tituloLock)}" data-orden-id="${orden.id_orden_c}">
                                <i class="fas fa-eye"></i>
                            </button>`
                    : (lockActivo
                        ? `<button type="button" class="btn-ver-orden-bloqueada inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-full text-red-400 opacity-80" title="${escHtmlAttr(tituloLock)}" data-orden-id="${orden.id_orden_c}" data-lock-nombre="${escHtmlAttr(lockNombre)}" disabled aria-disabled="true">
                                <i class="fas fa-pencil-alt"></i>
                            </button>`
                        : `<button type="button" class="btn-ver-orden inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-600 hover:bg-slate-100 hover:text-slate-900" title="${escHtmlAttr(tituloLock)}" data-orden-id="${orden.id_orden_c}">
                                <i class="fas fa-pencil-alt"></i>
                            </button>`);
                const botonEditarEstatus = ordenEntregada
                    ? `<button type="button" class="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-full text-slate-300 opacity-60" title="Orden entregada: no se puede editar estatus" disabled aria-disabled="true">
                                <i class="fas fa-check"></i>
                            </button>`
                    : `<button type="button" class="btn-editar-estatus inline-flex h-8 w-8 items-center justify-center rounded-full text-blue-600 hover:bg-blue-100 hover:text-blue-800" title="Editar estatus" data-edit-id="${orden.id_orden_c}" data-edit-estatus="${escHtmlAttr(orden.estatus)}" data-edit-folio="${escHtmlAttr(orden.folio || '')}" data-edit-firmas-recepcion="${Number(orden.firmas_recepcion_ok) === 1 ? '1' : '0'}">
                                <i class="fas fa-check"></i>
                            </button>`;
                tabla.innerHTML += `
                    <tr class="${rowClass}" data-orden-id="${orden.id_orden_c}" data-lock-activo="${lockActivo ? '1' : '0'}" data-lock-nombre="${escHtmlAttr(lockNombre)}">
                        <td class="p-3 border">${orden.folio}</td>
                        <td class="p-3 border">${orden.nombre_cliente}</td>
                        <td class="p-3 border">${fechaEntrada}</td>
                        <td class="p-3 border">${fechaTerminada}</td>
                        <td class="p-3 border">${fechaSalida}</td>
                        <td class="p-3 border text-center">${salidaTempHtml}</td>
                        <td class="p-3 border text-sm text-blue-900 min-w-[12rem]">${escHtmlAttr(primerTecnicoOrden(orden) || '\u2014')}</td>
                        <td class="p-3 border text-sm text-slate-700 align-top whitespace-normal min-w-[16rem] leading-relaxed">${involucradosHtmlOrden(orden)}</td>
                        <td class="p-3 border text-center">
                            <div class="inline-flex flex-col items-center">
                                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-semibold ${colorClass}">
                                    <span class="w-2 h-2 rounded-full bg-white/80"></span>
                                    ${escHtml(orden.estatus_label || statusLabel(orden.estatus))}
                                </span>
                                ${avisoSalidaEstatus}
                            </div>
                        </td>
                        <td class="p-3 border text-center">
                            <div class="flex flex-wrap items-center justify-center gap-2">
                            ${botonEditarOrden}
                            ${botonEditarEstatus}
                            <button type="button" class="btn-pdf-inline inline-flex h-8 w-8 items-center justify-center rounded-full text-indigo-600 hover:bg-indigo-100 hover:text-indigo-800" title="Ver PDF en pantalla (sin descargar)" data-pdf-id="${orden.id_orden_c}" data-pdf-folio="${escHtmlAttr(orden.folio || '')}">
                                <i class="fas fa-file-pdf"></i>
                            </button>
                            <button type="button" class="btn-pdf-descargar inline-flex h-8 w-8 items-center justify-center rounded-full text-green-600 hover:bg-green-100 hover:text-green-800" title="Descargar PDF" data-descarga-id="${orden.id_orden_c}">
                                <i class="fas fa-download"></i>
                            </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
        };

        const renderPagination = (pagination) => {
            const total = Number(pagination?.total || 0);
            currentPage = Number(pagination?.page || 1);
            totalPages = Number(pagination?.totalPages || 1);
            const perPage = Number(pagination?.perPage || perPageSelect.value || 10);
            const from = total === 0 ? 0 : ((currentPage - 1) * perPage) + 1;
            const to = Math.min(currentPage * perPage, total);

            paginationInfo.textContent = total === 0
                ? 'No hay ordenes para mostrar'
                : `Mostrando ${from}-${to} de ${total} ordenes`;
            pageNumber.textContent = `${currentPage} / ${totalPages}`;
            prevPage.disabled = currentPage <= 1;
            nextPage.disabled = currentPage >= totalPages;
        };

        const showTableMessage = (message, footer = '') => {
            tabla.innerHTML = `<tr><td class="p-3 border text-center" colspan="10">${message}</td></tr>`;
            if (footer) paginationInfo.textContent = footer;
            pageNumber.textContent = `${currentPage} / ${totalPages}`;
            prevPage.disabled = true;
            nextPage.disabled = true;
        };

        const cargarOrdenes = async () => {
            const params = new URLSearchParams();
            params.set('search', searchInput.value.trim());
            params.set('startDate', document.getElementById('fechaInicio').value);
            params.set('endDate', document.getElementById('fechaFin').value);
            params.set('estatus', document.querySelector('input[name="filtro"]:checked').value);
            params.set('page', String(currentPage));
            params.set('perPage', perPageSelect.value || '10');
            params.set('sort', sortOrdenes === 'estatus' ? 'estatus' : 'fecha');
            paginationInfo.textContent = 'Cargando órdenes...';

            const apiUrl = exactoUrl(`/api/ordenes?${params.toString()}`);
            try {
                const response = await fetch(apiUrl, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const contentType = String(response.headers.get('content-type') || '').toLowerCase();
                if (!response.ok || !contentType.includes('application/json')) {
                    if (response.status === 401 || response.status === 419 || response.redirected) {
                        window.location.href = exactoUrl('/login');
                        return;
                    }
                    showTableMessage('No se pudo cargar el listado. Recarga la p&aacute;gina.', 'Error al cargar órdenes');
                    return;
                }
                const data = await response.json();

                if (!response.ok || !data || data.success !== true) {
                    const msg = (data && data.message) || (response.status === 500
                        ? 'Error del servidor al cargar órdenes.'
                        : 'No se pudo cargar el listado.');
                    showTableMessage(msg, 'Error al cargar órdenes');
                    return;
                }

                const lista = Array.isArray(data.data) ? data.data : Object.values(data.data || {});
                renderOrdenes(lista);
                renderPagination(data.pagination);
                updateSortEstatusUi();
            } catch (err) {
                showTableMessage('Error de red al cargar &oacute;rdenes.', 'Error de red');
                console.error(err);
            }
        };

        tabla.addEventListener('click', async (e) => {
            const filaBloqueada = e.target.closest('tr[data-lock-activo="1"]');
            if (filaBloqueada && !e.target.closest('.btn-pdf-inline') && !e.target.closest('.btn-pdf-descargar') && !e.target.closest('.btn-editar-estatus')) {
                const nombre = filaBloqueada.getAttribute('data-lock-nombre') || 'otro usuario';
                const msg = `Esta orden está en edición por ${nombre}. Espera a que termine.`;
                if (typeof window.exactoShowAlert === 'function') {
                    await window.exactoShowAlert(msg, { title: 'Orden en uso', icon: 'warning' });
                } else {
                    window.alert(msg);
                }
                return;
            }
            const ver = e.target.closest('.btn-ver-orden');
            if (ver && ver.dataset.ordenId) {
                window.location.href = exactoUrl('/orden_servicio/' + encodeURIComponent(ver.dataset.ordenId) + '?ref=ordenes');
                return;
            }
            const editBtn = e.target.closest('.btn-editar-estatus');
            if (editBtn && editBtn.dataset.editId) {
                abrirEdit(editBtn.dataset.editId, editBtn.dataset.editEstatus || '', editBtn.dataset.editFolio || '', editBtn.dataset.editFirmasRecepcion || '0');
                return;
            }
            const pdfBtn = e.target.closest('.btn-pdf-inline');
            if (pdfBtn && pdfBtn.dataset.pdfId) {
                abrirPdfModalOrdenes(pdfBtn.dataset.pdfId, pdfBtn.dataset.pdfFolio || '');
                return;
            }
            const descBtn = e.target.closest('.btn-pdf-descargar');
            if (descBtn && descBtn.dataset.descargaId) {
                void abrirPdfOrdenSinCache(descBtn.dataset.descargaId, false);
                return;
            }
            // Click en cualquier parte de la fila (no en un boton/enlace) abre la edicion.
            // Solo para ordenes editables: la fila tiene .btn-ver-orden cuando no esta
            // bloqueada ni entregada.
            const fila = e.target.closest('tr[data-orden-id]');
            if (fila && fila.dataset.lockActivo !== '1' && !e.target.closest('button') && !e.target.closest('a')) {
                const id = fila.dataset.ordenId;
                if (id && fila.querySelector('.btn-ver-orden')) {
                    window.location.href = exactoUrl('/orden_servicio/' + encodeURIComponent(id) + '?ref=ordenes');
                }
            }
        });

        const esEstatusEntregado = (estatus) => normalizeStatus(estatus).includes('entregado');

        const abrirEdit = (id, estatus, folio, firmasRecepcionOk) => {
            document.getElementById('editId').value = id;
            document.getElementById('editFolio').value = folio != null ? String(folio) : '';
            document.getElementById('editFirmasRecepcionOk').value = firmasRecepcionOk === '1' || firmasRecepcionOk === 1 ? '1' : '0';
            const radioValue = statusToRadio(estatus);
            document.getElementById('editEstatusOrigen').value = radioValue;
            const firmasRecOk = document.getElementById('editFirmasRecepcionOk').value === '1';
            document.querySelectorAll('input[name="editEstatus"]').forEach((input) => {
                input.checked = input.value === radioValue;
                let dis = false;
                if (!firmasRecOk && (input.value === 'naranja' || input.value === 'amarillo') && input.value !== radioValue) {
                    dis = true;
                }
                input.disabled = dis;
            });
            abrirModal('modalEdit');
        };

        const guardarEdit = async () => {
            const id = document.getElementById('editId').value;
            const seleccionado = document.querySelector('input[name="editEstatus"]:checked');
            if (!seleccionado) {
                await mostrarAvisoOrdenes('Selecciona un estatus antes de guardar.', 'Validación requerida');
                return;
            }
            const estatus = seleccionado.value;
            const origenRadio = document.getElementById('editEstatusOrigen').value;
            if ((estatus === 'naranja' || estatus === 'amarillo') && document.getElementById('editFirmasRecepcionOk').value !== '1' && estatus !== origenRadio) {
                await mostrarAvisoOrdenes('No se puede poner en En proceso ni en Terminado sin las firmas de Cliente y Técnico. Abre la orden de servicio, completa esa sección y guarda.', 'Validación requerida');
                return;
            }
            const formData = new FormData();
            formData.append('id', id);
            formData.append('estatus', estatus);
            if (csrfToken) {
                formData.append('_token', csrfToken);
            }

            const response = await fetch(exactoUrl('/api/ordenes/estatus'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });
            const data = await response.json();

            if (!data.success) {
                await mostrarAvisoOrdenes(data.message || 'No se pudo actualizar el estatus', 'No se pudo guardar');
                return;
            }

            cerrarModal('modalEdit');
            cargarOrdenes();
        };

        const descargarOrdenActual = () => {
            const id = document.getElementById('editId').value;
            if (!id) return;
            window.open(urlPdfOrden(id, false), '_blank');
        };

        const bindOrdenesUi = () => {
            const filtroBtn = document.getElementById('filtroBtn');
            const cerrarFiltro = document.getElementById('cerrarFiltro');
            const aplicarFiltro = document.getElementById('aplicarFiltro');
            const buscarBtn = document.getElementById('buscar');
            const cancelarEdit = document.getElementById('cancelarEdit');
            const guardarEditBtn = document.getElementById('guardarEdit');
            const modalPdf = document.getElementById('modalPdf');
            const modalPdfCerrar = document.getElementById('modalPdfCerrarOrdenes');
            const modalPdfNuevaPestana = document.getElementById('modalPdfNuevaPestanaOrdenes');

            if (!tabla || !searchInput || !perPageSelect || !paginationInfo) {
                console.error('ordenes_laravel.js: faltan elementos de la tabla en la página');
                return;
            }

            if (filtroBtn) filtroBtn.addEventListener('click', () => abrirModal('modalFiltro'));
            if (cerrarFiltro) cerrarFiltro.addEventListener('click', () => cerrarModal('modalFiltro'));
            if (aplicarFiltro) {
                aplicarFiltro.addEventListener('click', () => {
                    cerrarModal('modalFiltro');
                    currentPage = 1;
                    cargarOrdenes();
                });
            }
            if (buscarBtn) {
                buscarBtn.addEventListener('click', () => {
                    currentPage = 1;
                    cargarOrdenes();
                });
            }
            if (cancelarEdit) cancelarEdit.addEventListener('click', () => cerrarModal('modalEdit'));
            if (guardarEditBtn) guardarEditBtn.addEventListener('click', guardarEdit);
            perPageSelect.addEventListener('change', () => {
                currentPage = 1;
                cargarOrdenes();
            });
            if (prevPage) {
                prevPage.addEventListener('click', () => {
                    if (currentPage > 1) {
                        currentPage -= 1;
                        cargarOrdenes();
                    }
                });
            }
            if (nextPage) {
                nextPage.addEventListener('click', () => {
                    if (currentPage < totalPages) {
                        currentPage += 1;
                        cargarOrdenes();
                    }
                });
            }
            if (modalPdfCerrar) modalPdfCerrar.addEventListener('click', cerrarPdfModalOrdenes);
            if (modalPdfNuevaPestana) {
                modalPdfNuevaPestana.addEventListener('click', () => {
                    if (pdfOrdenesIdActual) void abrirPdfOrdenSinCache(pdfOrdenesIdActual, true);
                });
            }
            if (modalPdf) {
                modalPdf.addEventListener('click', (e) => {
                    if (e.target.id === 'modalPdf') cerrarPdfModalOrdenes();
                });
            }
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && modalPdf && !modalPdf.classList.contains('hidden')) {
                    cerrarPdfModalOrdenes();
                }
            });
            searchInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    currentPage = 1;
                    cargarOrdenes();
                }
            });

            document.getElementById('btnSortEstatus')?.addEventListener('click', () => {
                sortOrdenes = sortOrdenes === 'estatus' ? 'fecha' : 'estatus';
                currentPage = 1;
                cargarOrdenes();
            });

            updateSortEstatusUi();
            cargarOrdenes();
            setInterval(() => {
                if (!document.hidden) {
                    cargarOrdenes();
                }
            }, 30000);
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bindOrdenesUi);
        } else {
            bindOrdenesUi();
        }
