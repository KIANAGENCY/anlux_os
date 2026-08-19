// JavaScript del historial de ordenes.

const normalizeStatus = (estatus) => String(estatus || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

        const statusLabel = (estatus) => {
            const normalized = normalizeStatus(estatus);
            if (normalized.includes('recepcion')) return 'Recepción';
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

        const tabla = document.getElementById('tabla');
        const searchInput = document.getElementById('search');
        const modalPdf = document.getElementById('modalPdf');
        const iframePdf = document.getElementById('iframePdf');
        const modalPdfFolio = document.getElementById('modalPdfFolio');
        let pdfIdActual = 0;

        const urlPdfInline = (id) => `actions/generar_orden_pdf.php?id=${encodeURIComponent(id)}&inline=1`;

        const abrirModalPdf = (id, folio) => {
            pdfIdActual = Number(id) || 0;
            modalPdfFolio.textContent = folio || ('Orden #' + id);
            iframePdf.src = urlPdfInline(id);
            modalPdf.classList.remove('hidden');
            modalPdf.classList.add('flex');
            document.body.style.overflow = 'hidden';
        };

        const cerrarModalPdf = () => {
            modalPdf.classList.add('hidden');
            modalPdf.classList.remove('flex');
            iframePdf.src = 'about:blank';
            document.body.style.overflow = '';
            pdfIdActual = 0;
        };

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

        const escaparHtml = (s) => String(s ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');

        const renderOrdenes = (ordenes) => {
            tabla.innerHTML = '';
            if (!ordenes.length) {
                tabla.innerHTML = '<tr><td class="p-3 border text-center" colspan="5">No se encontraron órdenes</td></tr>';
                return;
            }
            ordenes.forEach((orden) => {
                const fechaEntrada = orden.fecha_entrada ? orden.fecha_entrada.split(' ')[0] : '-';
                const colorClass = statusColor(orden.estatus);
                const folioEsc = escaparHtml(orden.folio);
                const clienteEsc = escaparHtml(orden.nombre_cliente);
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-blue-100 cursor-pointer border-b border-blue-100';
                tr.dataset.orderId = String(orden.id_orden_c);
                tr.dataset.folio = String(orden.folio || '');
                tr.innerHTML = `
                    <td class="p-3 border font-medium text-blue-900">${folioEsc}</td>
                    <td class="p-3 border">${clienteEsc}</td>
                    <td class="p-3 border">${fechaEntrada}</td>
                    <td class="p-3 border text-center">
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-semibold ${colorClass}">
                            ${statusLabel(orden.estatus)}
                        </span>
                    </td>
                    <td class="p-3 border text-center">
                        <div class="flex flex-wrap items-center justify-center gap-2">
                        <button type="button" class="btn-ver-pdf rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-bold text-white hover:bg-blue-700" title="Ver PDF aquí">
                            <i class="fas fa-eye"></i> PDF
                        </button>
                        <a href="orden_servicio.php?id=${orden.id_orden_c}&ref=ordenes" class="link-orden inline-flex items-center rounded-lg border-2 border-blue-300 bg-white px-3 py-1.5 text-sm font-semibold text-blue-800 hover:bg-blue-50" onclick="event.stopPropagation()">
                            <i class="fas fa-external-link-alt mr-1"></i>Orden
                        </a>
                        </div>
                    </td>
                `;
                tr.addEventListener('click', (e) => {
                    if (e.target.closest('.link-orden')) return;
                    if (e.target.closest('.btn-ver-pdf')) {
                        abrirModalPdf(orden.id_orden_c, orden.folio);
                        return;
                    }
                    abrirModalPdf(orden.id_orden_c, orden.folio);
                });
                tabla.appendChild(tr);
            });
        };

        const cargarOrdenes = async () => {
            const params = new URLSearchParams();
            params.set('search', searchInput.value.trim());
            params.set('startDate', document.getElementById('fechaInicio').value);
            params.set('endDate', document.getElementById('fechaFin').value);
            params.set('estatus', document.querySelector('input[name="filtro"]:checked').value);

            const response = await fetch(`actions/ordenes_api.php?${params.toString()}`);
            const data = await response.json();

            if (!data.success) {
                tabla.innerHTML = `<tr><td class="p-3 border text-center" colspan="5">${escaparHtml(data.message)}</td></tr>`;
                return;
            }

            renderOrdenes(data.data);
        };

        document.getElementById('modalPdfCerrar').addEventListener('click', cerrarModalPdf);
        document.getElementById('modalPdfNuevaPestana').addEventListener('click', () => {
            if (pdfIdActual) window.open(urlPdfInline(pdfIdActual), '_blank', 'noopener');
        });
        modalPdf.addEventListener('click', (e) => {
            if (e.target === modalPdf) cerrarModalPdf();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modalPdf.classList.contains('hidden')) cerrarModalPdf();
        });

        document.getElementById('filtroBtn').onclick = () => abrirModal('modalFiltro');
        document.getElementById('cerrarFiltro').onclick = () => cerrarModal('modalFiltro');
        document.getElementById('aplicarFiltro').onclick = () => {
            cerrarModal('modalFiltro');
            cargarOrdenes();
        };
        document.getElementById('buscar').onclick = () => cargarOrdenes();
        searchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                cargarOrdenes();
            }
        });

        window.addEventListener('DOMContentLoaded', cargarOrdenes);
