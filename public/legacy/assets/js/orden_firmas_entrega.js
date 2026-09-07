(function () {
    function anluxSyncFirmasEntregaInline() {
        var estatusEl = document.getElementById('inputEstatus');
        var seccionEntrega = document.getElementById('ordenSeccionFirmasEntrega');
        var seccionIniciales = document.getElementById('ordenSeccionFirmasIniciales');
        var idOrden = document.getElementById('id_orden_c');
        if (!estatusEl || !seccionEntrega) return;

        var valor = String(estatusEl.value || '').toLowerCase();
        var esEntregado = valor.indexOf('entreg') !== -1;
        var esEdicion = idOrden && String(idOrden.value || '').trim() !== '';

        seccionEntrega.classList.toggle('hidden', !esEntregado);
        seccionEntrega.classList.toggle('orden-firmas-skip', !esEntregado);
        seccionEntrega.style.display = esEntregado ? 'block' : 'none';

        if (seccionIniciales) {
            var ocultarIniciales = esEdicion || esEntregado;
            seccionIniciales.classList.toggle('hidden', ocultarIniciales);
            seccionIniciales.classList.toggle('orden-firmas-skip', ocultarIniciales);
            seccionIniciales.style.display = ocultarIniciales ? 'none' : 'block';
        }

        if (esEntregado) {
            var btn = document.getElementById('btnGuardarOrden');
            var form = document.getElementById('ordenForm');
            if (btn && form && btn.parentElement && btn.parentElement.parentElement === form) {
                form.insertBefore(seccionEntrega, btn.parentElement);
            }
        }
    }

    document.addEventListener('DOMContentLoaded', anluxSyncFirmasEntregaInline);
    document.addEventListener('change', function (e) {
        if (e.target && e.target.id === 'inputEstatus') {
            anluxSyncFirmasEntregaInline();
        }
    });
    window.anluxSyncFirmasEntregaInline = anluxSyncFirmasEntregaInline;
})();
