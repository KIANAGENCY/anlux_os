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

    const urlPdfInline = (id) => anluxUrl(`/pdf/orden/${encodeURIComponent(id)}?inline=1&refresh_pdf=1&_=${Date.now()}`);

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
            const pdfUrl = escaparHtml(urlPdfInline(orden.id_orden_c));
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
                <a href="${pdfUrl}" target="_blank" rel="noopener noreferrer" class="btn-abrir-pesta inline-flex items-center rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-bold text-white hover:bg-blue-700" title="Abrir PDF en nueva pesta\u00f1a">
                    <i class="fas fa-external-link-alt mr-1"></i>Abrir pesta\u00f1a
                </a>
            </td>
        `;
            tabla.appendChild(tr);
        });
    };

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
            const link = event.target.closest('.btn-abrir-pesta');
            if (!link) {
                return;
            }
            const now = Date.now();
            if (now - lastAbrirPestanaAt < ABRIR_PESTANA_MS) {
                event.preventDefault();
                return;
            }
            lastAbrirPestanaAt = now;
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
