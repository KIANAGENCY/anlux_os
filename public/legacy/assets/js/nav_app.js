// JavaScript de navegacion compartida.

(function () {
    var btn = document.getElementById('navBtnCerrarSesion');
    var modal = document.getElementById('modalCerrarSesion');
    var cancel = document.getElementById('modalCerrarSesionCancelar');
    if (!btn || !modal || !cancel) return;
    function abrir() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }
    function cerrar() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }
    function permitirSalidaSinAviso() {
        try {
            if (typeof window.exactoPermitirSalidaOrdenForm === 'function') {
                window.exactoPermitirSalidaOrdenForm();
            }
        } catch (e) {
            // ignore
        }
    }
    btn.addEventListener('click', abrir);
    cancel.addEventListener('click', cerrar);
    modal.addEventListener('click', function (e) {
        if (e.target === modal) cerrar();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) cerrar();
    });
    var logoutForm = modal.querySelector('form[action*="logout"]');
    if (logoutForm) {
        logoutForm.addEventListener('submit', function () {
            permitirSalidaSinAviso();
        });
    }
})();
