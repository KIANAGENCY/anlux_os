// JavaScript del historial de ordenes.
(function () {
    'use strict';

    window.ANLUX_HISTORIAL_READY = false;

    const normalizeStatus = (estatus) => String(estatus || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    const baseUrl = String(window.ANLUX_BASE_URL || '').replace(/\/$/, '');
    const anluxUrl = (path) => {
        const p = path.startsWith('/') ? path : `/${path}`;
        return baseUrl ? `${baseUrl}${p}` : p;
    };

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

    const escaparHtml = (s) => String(s ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

    const urlPdfInline = (id, equipoIndice = 0) => {
        const bust = `${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;
        const eq = Number(equipoIndice) > 0 ? `&eq=${encodeURIComponent(equipoIndice)}` : '';
        return anluxUrl(`/pdf/orden/${encodeURIComponent(id)}?inline=1&refresh_pdf=1&nocache=1${eq}&_=${bust}`);
    };

    async function abrirPdfHistorialSinCache(id, equipoIndice = 0) {
        const url = urlPdfInline(id, equipoIndice);
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
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const raw = await res.blob();
            const pdfBlob = raw.type && raw.type.includes('pdf')
                ? raw
                : new Blob([raw], { type: 'application/pdf' });
            const objUrl = URL.createObjectURL(pdfBlob);
            const ventana = window.open(objUrl, '_blank');
            if (ventana) {
                try { ventana.opener = null; } catch (_) { /* ignore */ }
            } else {
                window.open(url, '_blank');
            }
            setTimeout(() => {
                try { URL.revokeObjectURL(objUrl); } catch (_) { /* ignore */ }
            }, 180000);
        } catch (err) {
            console.warn('PDF historial sin cache falló', err);
            window.open(url, '_blank');
        }
    }

    const ABRIR_PESTANA_MS = 300;
    let lastAbrirPestanaAt = 0;
    /** 'fecha' = más recientes primero; 'estatus' = flujo Recepción → Entregado */
    let sortHistorial = 'fecha';

    const updateSortEstatusUi = () => {
        const iconFecha = document.getElementById('iconSortEstatusFecha');
        const iconFlujo = document.getElementById('iconSortEstatusFlujo');
        const btn = document.getElementById('btnSortEstatus');
        if (!iconFecha || !iconFlujo || !btn) return;
        if (sortHistorial === 'estatus') {
            iconFecha.classList.add('hidden');
            iconFlujo.classList.remove('hidden');
            btn.title = 'Orden: flujo de estatus. Clic para ordenar por fecha de entrada (más recientes primero).';
            btn.setAttribute('aria-pressed', 'true');
        } else {
            iconFecha.classList.remove('hidden');
            iconFlujo.classList.add('hidden');
            btn.title = 'Orden: fecha de entrada. Clic para ordenar por estatus (Recepción → Entregado).';
            btn.setAttribute('aria-pressed', 'false');
        }
    };

    const abrirModal = (id) => {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    const cerrarModal = (id) => {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    const normalizarLista = (data) => {
        if (Array.isArray(data)) return data;
        if (data && typeof data === 'object') return Object.values(data);
        return [];
    };

    const renderOrdenes = (tabla, ordenes) => {
        if (!tabla) return;
        const lista = normalizarLista(ordenes);
        tabla.innerHTML = '';
        if (!lista.length) {
            tabla.innerHTML = '<tr><td class="p-3 border text-center" colspan="5">No se encontraron órdenes</td></tr>';
            return;
        }
        lista.forEach((orden) => {
            const fechaEntrada = orden.fecha_entrada ? String(orden.fecha_entrada).split(' ')[0] : '-';
            const colorClass = statusColor(orden.estatus);
            const labelEstatus = orden.estatus_label || statusLabel(orden.estatus);
            const folioEsc = escaparHtml(orden.folio);
            const clienteEsc = escaparHtml(orden.nombre_cliente);
            const tr = document.createElement('tr');
            tr.className = 'border-b border-blue-100 hover:bg-blue-50';
            tr.innerHTML = `
            <td class="p-3 border font-medium text-blue-900">${folioEsc}</td>
            <td class="p-3 border">${clienteEsc}</td>
            <td class="p-3 border">${fechaEntrada}</td>
            <td class="p-3 border text-center">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-semibold ${colorClass}">
                    ${escaparHtml(labelEstatus)}
                </span>
            </td>
            <td class="p-3 border text-center">
                <div class="flex flex-wrap justify-center gap-2">
                <button type="button" data-pdf-id="${Number(orden.id_orden_c) || 0}" class="btn-abrir-pesta inline-flex items-center rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-bold text-white hover:bg-blue-700" title="Abrir PDF en nueva pesta\u00f1a">
                    <i class="fas fa-external-link-alt mr-1"></i>Abrir pesta\u00f1a
                </button>
                <button type="button" data-entregas-id="${Number(orden.id_orden_c) || 0}" class="btn-equipos-entregados inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-bold text-white" style="background-color:#059669;color:#fff;" aria-expanded="false">
                    <i class="fas fa-box-open mr-1"></i>Equipos entregados
                </button>
                </div>
            </td>
        `;
            tabla.appendChild(tr);
            const detalle = document.createElement('tr');
            detalle.id = `equipos-entregados-${Number(orden.id_orden_c) || 0}`;
            detalle.className = 'hidden bg-slate-50';
            detalle.innerHTML = '<td colspan="5" class="p-4 border border-blue-100"></td>';
            tabla.appendChild(detalle);
        });
    };

    const renderEquiposEntregados = (contenedor, equipos, idOrden) => {
        const lista = normalizarLista(equipos);
        if (!lista.length) {
            contenedor.innerHTML = '<p class="text-sm text-slate-600">Esta orden no tiene equipos marcados como entregados.</p>';
            return;
        }
        contenedor.innerHTML = `
            <div class="grid gap-3 md:grid-cols-2">
                ${lista.map((equipo) => {
                    const tipo = equipo.receptor_tipo === 'tercero' ? 'Tercero' : 'Cliente titular';
                    const fecha = equipo.fecha_entrega
                        ? new Date(String(equipo.fecha_entrega).replace(' ', 'T')).toLocaleString('es-MX')
                        : 'Sin fecha';
                    return `<article class="rounded-lg border border-emerald-200 bg-white p-4 shadow-sm">
                        <div class="mb-2 flex items-start justify-between gap-2">
                            <strong class="text-blue-900">${escaparHtml(`${equipo.marca || ''} ${equipo.modelo || ''}`.trim() || `Equipo ${equipo.indice}`)}</strong>
                            <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800">Entregado</span>
                        </div>
                        <p class="text-sm text-slate-700"><strong>Serie:</strong> ${escaparHtml(equipo.serie || '-')}</p>
                        <p class="text-sm text-slate-700"><strong>Recibió:</strong> ${escaparHtml(equipo.receptor || '-')} (${tipo})</p>
                        <p class="text-sm text-slate-700"><strong>Fecha:</strong> ${escaparHtml(fecha)}</p>
                        <p class="mb-3 text-sm text-slate-700"><strong>Técnico:</strong> ${escaparHtml(equipo.tecnico || '-')}</p>
                        <button type="button" data-pdf-id="${Number(idOrden) || 0}" data-equipo-indice="${Number(equipo.indice) || 0}" class="btn-abrir-pesta rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-bold text-white hover:bg-blue-700">
                            <i class="fas fa-file-pdf mr-1"></i>Ver PDF
                        </button>
                    </article>`;
                }).join('')}
            </div>`;
    };

    async function alternarEquiposEntregados(btn) {
        const id = Number(btn.dataset.entregasId || 0);
        const detalle = document.getElementById(`equipos-entregados-${id}`);
        const contenedor = detalle?.querySelector('td');
        if (!detalle || !contenedor || id <= 0) return;

        const seAbrira = detalle.classList.contains('hidden');
        detalle.classList.toggle('hidden', !seAbrira);
        btn.setAttribute('aria-expanded', seAbrira ? 'true' : 'false');
        if (!seAbrira || detalle.dataset.loaded === '1') return;

        contenedor.innerHTML = '<p class="text-sm text-slate-600">Cargando equipos entregados…</p>';
        try {
            const response = await fetch(anluxUrl(`/api/ordenes/${encodeURIComponent(id)}/equipos-entregados`), {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await response.json();
            if (!response.ok || data?.success !== true) {
                throw new Error(data?.message || `HTTP ${response.status}`);
            }
            renderEquiposEntregados(contenedor, data.data, id);
            detalle.dataset.loaded = '1';
        } catch (err) {
            console.error(err);
            contenedor.innerHTML = '<p class="text-sm text-red-700">No se pudieron cargar los equipos entregados.</p>';
        }
    }

    const showTableMessage = (tabla, message) => {
        if (!tabla) return;
        tabla.innerHTML = `<tr><td class="p-3 border text-center" colspan="5">${escaparHtml(message)}</td></tr>`;
    };

    const cargarOrdenes = async (tabla, searchInput) => {
        if (!tabla) return;
        showTableMessage(tabla, 'Cargando órdenes…');

        const params = new URLSearchParams();
        params.set('search', searchInput ? searchInput.value.trim() : '');
        params.set('startDate', document.getElementById('fechaInicio')?.value || '');
        params.set('endDate', document.getElementById('fechaFin')?.value || '');
        const filtroChecked = document.querySelector('input[name="filtro"]:checked');
        params.set('estatus', filtroChecked ? filtroChecked.value : 'todos');
        params.set('perPage', '50');
        params.set('page', '1');
        params.set('sort', sortHistorial === 'estatus' ? 'estatus' : 'fecha');

        try {
            const response = await fetch(anluxUrl(`/api/ordenes?${params.toString()}`), {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            const contentType = String(response.headers.get('content-type') || '').toLowerCase();
            let data = null;
            try {
                data = await response.json();
            } catch (parseErr) {
                console.error(parseErr);
                showTableMessage(tabla, 'Respuesta inválida del servidor. Recarga con Ctrl+F5.');
                return;
            }

            if (!response.ok || !data || data.success !== true) {
                if (response.status === 401 || response.status === 419 || response.redirected) {
                    window.location.href = anluxUrl('/login');
                    return;
                }
                showTableMessage(tabla, (data && data.message) ? data.message : 'No se pudo cargar el historial.');
                return;
            }

            renderOrdenes(tabla, data.data);
        } catch (err) {
            console.error(err);
            showTableMessage(tabla, 'Error de red al cargar el historial.');
        }
    };

    const init = () => {
        const tabla = document.getElementById('tabla');
        const searchInput = document.getElementById('search');

        if (!tabla) {
            console.error('historial_laravel.js: falta #tabla en la página');
            return;
        }

        const filtroBtn = document.getElementById('filtroBtn');
        const cerrarFiltro = document.getElementById('cerrarFiltro');
        const aplicarFiltro = document.getElementById('aplicarFiltro');
        const buscar = document.getElementById('buscar');

        if (filtroBtn) filtroBtn.addEventListener('click', () => abrirModal('modalFiltro'));
        if (cerrarFiltro) cerrarFiltro.addEventListener('click', () => cerrarModal('modalFiltro'));
        if (aplicarFiltro) {
            aplicarFiltro.addEventListener('click', () => {
                cerrarModal('modalFiltro');
                cargarOrdenes(tabla, searchInput);
            });
        }
        if (buscar) buscar.addEventListener('click', () => cargarOrdenes(tabla, searchInput));
        if (searchInput) {
            searchInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    cargarOrdenes(tabla, searchInput);
                }
            });
        }

        document.getElementById('btnSortEstatus')?.addEventListener('click', () => {
            sortHistorial = sortHistorial === 'estatus' ? 'fecha' : 'estatus';
            updateSortEstatusUi();
            cargarOrdenes(tabla, searchInput);
        });

        tabla.addEventListener('click', (event) => {
            const btnEntregas = event.target.closest('.btn-equipos-entregados');
            if (btnEntregas) {
                event.preventDefault();
                void alternarEquiposEntregados(btnEntregas);
                return;
            }
            const btn = event.target.closest('.btn-abrir-pesta');
            if (!btn) {
                return;
            }
            event.preventDefault();
            const now = Date.now();
            if (now - lastAbrirPestanaAt < ABRIR_PESTANA_MS) {
                return;
            }
            lastAbrirPestanaAt = now;
            const id = Number(btn.dataset.pdfId || 0);
            const equipoIndice = Number(btn.dataset.equipoIndice || 0);
            if (id > 0) {
                void abrirPdfHistorialSinCache(id, equipoIndice);
            }
        });

        window.ANLUX_HISTORIAL_READY = true;
        updateSortEstatusUi();
        cargarOrdenes(tabla, searchInput);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
