(function () {
    function forzarMayusculas(input) {
        if (!input) return;
        input.addEventListener('input', function () {
            const start = input.selectionStart;
            const end = input.selectionEnd;
            const upper = String(input.value || '').toLocaleUpperCase('es-MX');
            if (input.value !== upper) {
                input.value = upper;
                if (typeof start === 'number' && typeof end === 'number') {
                    try {
                        input.setSelectionRange(start, end);
                    } catch (e) {
                        // ignore
                    }
                }
            }
        });
    }

    forzarMayusculas(document.getElementById('nombre'));
    forzarMayusculas(document.getElementById('nombre_usuario'));

    const email = document.getElementById('email');
    if (email) {
        email.addEventListener('blur', function () {
            email.value = String(email.value || '').trim().toLowerCase();
        });
    }

    document.getElementById('celular')?.addEventListener('input', function () {
        this.value = String(this.value || '').replace(/\D/g, '').slice(0, 15);
    });

    document.querySelectorAll('.toggle-password').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.target || '');
            const icon = btn.querySelector('i');
            if (!input || !icon) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            icon.classList.toggle('fa-eye', !show);
            icon.classList.toggle('fa-eye-slash', show);
        });
    });
})();
