(function () {
    const tbody = document.getElementById('catalogoSersopBody');
    const btnAgregar = document.getElementById('btnAgregarServicioSersop');
    function renumerar() {
        tbody.querySelectorAll('.catalogo-sersop-row').forEach((fila, index) => {
            fila.querySelectorAll('input').forEach((input) => {
                const name = input.getAttribute('name');
                if (name) input.setAttribute('name', name.replace(/servicios\[\d+\]/, 'servicios[' + index + ']'));
            });
        });
    }
    function agregarFila() {
        const index = tbody.querySelectorAll('.catalogo-sersop-row').length;
        const fila = document.createElement('tr');
        fila.className = 'catalogo-sersop-row hover:bg-blue-50';
        fila.innerHTML = '<td class="border p-3"><input type="text" name="servicios[' + index + '][clave]" class="w-full rounded border border-blue-300 px-2 py-1 font-semibold uppercase text-blue-900" placeholder="SERSOP17"></td><td class="border p-3"><input type="text" name="servicios[' + index + '][descripcion]" class="w-full rounded border border-blue-300 px-2 py-1" placeholder="DESCRIPCION DEL SERVICIO"></td><td class="border p-3"><input type="number" step="0.01" min="0" name="servicios[' + index + '][precio]" value="0.00" class="w-full rounded border border-blue-300 px-2 py-1" title="PRECIO SIN IVA" placeholder="0.00"></td><td class="border p-3 text-center"><input type="checkbox" name="servicios[' + index + '][editable]" class="h-5 w-5"></td><td class="border p-3 text-center"><input type="checkbox" name="servicios[' + index + '][activo]" checked class="h-5 w-5"></td><td class="border p-3 text-center"><button type="button" class="btn-quitar-servicio-sersop font-bold text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button></td>';
        tbody.appendChild(fila);
    }
    btnAgregar?.addEventListener('click', agregarFila);
    tbody?.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-quitar-servicio-sersop');
        if (!btn) return;
        if (tbody.querySelectorAll('.catalogo-sersop-row').length <= 1) {
            alert('Debe quedar al menos una clave en el catálogo.');
            return;
        }
        btn.closest('.catalogo-sersop-row').remove();
        renumerar();
    });
    tbody?.addEventListener('input', function (e) {
        if (e.target && e.target.name && e.target.name.includes('[clave]')) {
            e.target.value = String(e.target.value || '').toUpperCase().replace(/\s+/g, '');
        }
    });
})();
