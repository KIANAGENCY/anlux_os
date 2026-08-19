// JavaScript del listado de ordenes.

        const csrfToken = window.EXACTO_CSRF_TOKEN || '';
        let currentPage = 1;
        let totalPages = 1;

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

        const tabla = document.getElementById('tabla');
        const searchInput = document.getElementById('search');
        const perPageSelect = document.getElementById('perPage');
        const paginationInfo = document.getElementById('paginationInfo');
        const pageNumber = document.getElementById('pageNumber');
        const prevPage = document.getElementById('prevPage');
        const nextPage = document.getElementById('nextPage');

        let pdfOrdenesIdActual = 0;
        const urlPdfInlineOrdenes = (id) => `actions/generar_orden_pdf.php?id=${encodeURIComponent(id)}&inline=1`;

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

        function abrirPdfModalOrdenes(id, folio) {
            pdfOrdenesIdActual = Number(id) || 0;
            const url = urlPdfInlineOrdenes(id);
            // Solo pestaña nueva. Con "noopener" en features, open() suele devolver null
            // aunque sí abra la pestaña; no usar location.href (cargaría el PDF dos veces).
            const ventana = window.open(url, '_blank');
            if (ventana) {
                try { ventana.opener = null; } catch (_) { /* ignore */ }
            }
        }

        function cerrarPdfModalOrdenes() {
            document.getElementById('modalPdf').classList.add('hidden');
            document.getElementById('modalPdf').classList.remove('flex');
            document.getElementById('iframePdfOrdenes').src = 'about:blank';
            document.body.style.overflow = '';
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
                tabla.innerHTML = '<tr><td class="p-3 border text-center" colspan="9">No se encontraron &oacute;rdenes</td></tr>';
                return;
            }
            ordenes.forEach((orden) => {
                const fechaEntrada = orden.fecha_entrada ? orden.fecha_entrada.split(' ')[0] : '-';
                const fechaTerminada = orden.fecha_terminada ? orden.fecha_terminada.split(' ')[0] : '-';
                const fechaSalida = orden.fecha_salida ? orden.fecha_salida.split(' ')[0] : '-';
                const colorClass = statusColor(orden.estatus);
                tabla.innerHTML += `
                    <tr class="hover:bg-blue-100">
                        <td class="p-3 border">${orden.folio}</td>
                        <td class="p-3 border">${orden.nombre_cliente}</td>
                        <td class="p-3 border">${fechaEntrada}</td>
                        <td class="p-3 border">${fechaTerminada}</td>
                        <td class="p-3 border">${fechaSalida}</td>
                        <td class="p-3 border text-sm text-blue-900">${escHtmlAttr(primerTecnicoOrden(orden) || '\u2014')}</td>
                        <td class="p-3 border text-sm text-slate-700 align-top whitespace-normal min-w-[14rem] w-56 leading-relaxed">${involucradosHtmlOrden(orden)}</td>
                        <td class="p-3 border text-center">
                            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-semibold ${colorClass}">
                                <span class="w-2 h-2 rounded-full bg-white/80"></span>
                                ${statusLabel(orden.estatus)}
                            </span>
                        </td>
                        <td class="p-3 border text-center">
                            <div class="flex flex-wrap items-center justify-center gap-2">
                            <button type="button" class="btn-ver-orden inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-600 hover:bg-slate-100 hover:text-slate-900" title="Ver / completar orden" data-orden-id="${orden.id_orden_c}">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn-editar-estatus inline-flex h-8 w-8 items-center justify-center rounded-full text-blue-600 hover:bg-blue-100 hover:text-blue-800" title="Editar estatus" data-edit-id="${orden.id_orden_c}" data-edit-estatus="${escHtmlAttr(orden.estatus)}" data-edit-folio="${escHtmlAttr(orden.folio || '')}" data-edit-firmas="${Number(orden.firmas_entrega_ok) === 1 ? '1' : '0'}" data-edit-firmas-recepcion="${Number(orden.firmas_recepcion_ok) === 1 ? '1' : '0'}">
                                <i class="fas fa-edit"></i>
                            </button>
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

        const cargarOrdenes = async () => {
            const params = new URLSearchParams();
            params.set('search', searchInput.value.trim());
            params.set('startDate', document.getElementById('fechaInicio').value);
            params.set('endDate', document.getElementById('fechaFin').value);
            params.set('estatus', document.querySelector('input[name="filtro"]:checked').value);
            params.set('page', String(currentPage));
            params.set('perPage', perPageSelect.value || '10');

            const response = await fetch(`actions/ordenes_api.php?${params.toString()}`);
            const data = await response.json();

            if (!data.success) {
                tabla.innerHTML = `<tr><td class="p-3 border text-center" colspan="9">${data.message}</td></tr>`;
                return;
            }

            renderOrdenes(data.data);
            renderPagination(data.pagination);
        };

        tabla.addEventListener('click', (e) => {
            const ver = e.target.closest('.btn-ver-orden');
            if (ver && ver.dataset.ordenId) {
                window.location.href = 'orden_servicio.php?id=' + encodeURIComponent(ver.dataset.ordenId) + '&ref=ordenes';
                return;
            }
            const editBtn = e.target.closest('.btn-editar-estatus');
            if (editBtn && editBtn.dataset.editId) {
                abrirEdit(editBtn.dataset.editId, editBtn.dataset.editEstatus || '', editBtn.dataset.editFolio || '', editBtn.dataset.editFirmas || '0', editBtn.dataset.editFirmasRecepcion || '0');
                return;
            }
            const pdfBtn = e.target.closest('.btn-pdf-inline');
            if (pdfBtn && pdfBtn.dataset.pdfId) {
                abrirPdfModalOrdenes(pdfBtn.dataset.pdfId, pdfBtn.dataset.pdfFolio || '');
                return;
            }
            const descBtn = e.target.closest('.btn-pdf-descargar');
            if (descBtn && descBtn.dataset.descargaId) {
                window.open('actions/generar_orden_pdf.php?id=' + encodeURIComponent(descBtn.dataset.descargaId), '_blank');
            }
        });

        const esEstatusEntregado = (estatus) => normalizeStatus(estatus).includes('entregado');

        const abrirEdit = (id, estatus, folio, firmasEntregaOk, firmasRecepcionOk) => {
            document.getElementById('editId').value = id;
            document.getElementById('editFolio').value = folio != null ? String(folio) : '';
            document.getElementById('editFirmasEntregaOk').value = firmasEntregaOk === '1' || firmasEntregaOk === 1 ? '1' : '0';
            document.getElementById('editFirmasRecepcionOk').value = firmasRecepcionOk === '1' || firmasRecepcionOk === 1 ? '1' : '0';
            const entregado = esEstatusEntregado(estatus);
            document.getElementById('editActualEsEntregado').value = entregado ? '1' : '0';
            const radioValue = statusToRadio(estatus);
            document.getElementById('editEstatusOrigen').value = radioValue;
            const firmasRecOk = document.getElementById('editFirmasRecepcionOk').value === '1';
            document.querySelectorAll('input[name="editEstatus"]').forEach((input) => {
                input.checked = input.value === radioValue;
                let dis = (input.value === 'verde') || (entregado && input.value !== 'verde');
                if (!firmasRecOk && (input.value === 'naranja' || input.value === 'amarillo') && input.value !== radioValue) {
                    dis = true;
                }
                input.disabled = dis;
            });
            abrirModal('modalEdit');
        };

        const guardarEdit = async () => {
            const id = document.getElementById('editId').value;
            const estatus = document.querySelector('input[name="editEstatus"]:checked').value;
            if (document.getElementById('editActualEsEntregado').value === '1' && estatus !== 'verde') {
                alert('Esta orden ya esta entregada. No se puede cambiar el estatus a Recepcion, En proceso ni Terminado.');
                return;
            }
            if (estatus === 'verde' && document.getElementById('editFirmasEntregaOk').value !== '1') {
                alert('No se puede marcar como Entregado: faltan la firma de recibido del cliente y/o la firma de entrega del tecnico en la orden. Abre la orden (icono del ojo), completa las firmas de cierre y guarda antes de cambiar el estatus aqui.');
                return;
            }
            const origenRadio = document.getElementById('editEstatusOrigen').value;
            if ((estatus === 'naranja' || estatus === 'amarillo') && document.getElementById('editFirmasRecepcionOk').value !== '1' && estatus !== origenRadio) {
                alert('No se puede poner en En proceso ni en Terminado sin las firmas de FIRMAS DE RECIBIDO DEL EQUIPO (entrega del cliente y recepcion del tecnico). Abre la orden de servicio, completa esa seccion y guarda.');
                return;
            }
            const formData = new FormData();
            formData.append('action', 'updateStatus');
            formData.append('id', id);
            formData.append('estatus', estatus);
            formData.append('csrf_token', csrfToken);

            const response = await fetch('actions/ordenes_api.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (!data.success) {
                alert(data.message || 'No se pudo actualizar el estatus');
                return;
            }

            cerrarModal('modalEdit');
            cargarOrdenes();
        };

        const descargarOrdenActual = () => {
            const id = document.getElementById('editId').value;
            if (!id) return;
            window.open(`actions/generar_orden_pdf.php?id=${id}`, '_blank');
        };

        document.getElementById('filtroBtn').onclick = () => abrirModal('modalFiltro');
        document.getElementById('cerrarFiltro').onclick = () => cerrarModal('modalFiltro');
        document.getElementById('aplicarFiltro').onclick = () => {
            cerrarModal('modalFiltro');
            currentPage = 1;
            cargarOrdenes();
        };
        document.getElementById('buscar').onclick = () => {
            currentPage = 1;
            cargarOrdenes();
        };
        document.getElementById('cancelarEdit').onclick = () => cerrarModal('modalEdit');
        document.getElementById('guardarEdit').onclick = guardarEdit;
        perPageSelect.addEventListener('change', () => {
            currentPage = 1;
            cargarOrdenes();
        });
        prevPage.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage -= 1;
                cargarOrdenes();
            }
        });
        nextPage.addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage += 1;
                cargarOrdenes();
            }
        });
        document.getElementById('modalPdfCerrarOrdenes').addEventListener('click', cerrarPdfModalOrdenes);
        document.getElementById('modalPdfNuevaPestanaOrdenes').addEventListener('click', () => {
            if (pdfOrdenesIdActual) window.open(urlPdfInlineOrdenes(pdfOrdenesIdActual), '_blank', 'noopener');
        });
        document.getElementById('modalPdf').addEventListener('click', (e) => {
            if (e.target.id === 'modalPdf') cerrarPdfModalOrdenes();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !document.getElementById('modalPdf').classList.contains('hidden')) cerrarPdfModalOrdenes();
        });
        searchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                currentPage = 1;
                cargarOrdenes();
            }
        });

        window.addEventListener('DOMContentLoaded', cargarOrdenes);
