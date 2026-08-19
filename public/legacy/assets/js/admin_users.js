(function () {
    const modal = document.getElementById('modalEditarUsuario');
    const form = document.getElementById('formEditarUsuario');
    const inputNombre = document.getElementById('modalUsuarioNombre');
    const inputUsername = document.getElementById('modalUsuarioUsername');
    const inputEmail = document.getElementById('modalUsuarioEmail');
    const inputPassword = document.getElementById('modalUsuarioPassword');
    const inputPasswordConfirm = document.getElementById('modalUsuarioPasswordConfirm');

    if (!modal || !form || !inputNombre || !inputEmail || !inputPassword || !inputPasswordConfirm) {
        return;
    }

    const abrir = (button) => {
        inputNombre.value = String(button.dataset.nombre || '');
        inputEmail.value = String(button.dataset.email || '');
        if (inputUsername) {
            inputUsername.value = String(button.dataset.usuario || '');
        }
        form.action = String(button.dataset.updateUrl || '');
        inputPassword.value = '';
        inputPasswordConfirm.value = '';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        if (inputUsername) {
            inputUsername.focus();
        } else {
            inputPassword.focus();
        }
    };

    const cerrar = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('.btn-editar-usuario');
        if (trigger) {
            abrir(trigger);
            return;
        }
        if (event.target === modal || event.target.closest('#cerrarModalEditarUsuario')) {
            cerrar();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            cerrar();
        }
    });

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    document.querySelectorAll('.btn-eliminar-usuario').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const url = btn.dataset.deleteUrl || '';
            const nombre = String(btn.dataset.nombre || '').trim();
            if (!url) return;
            const msg = nombre !== ''
                ? '¿Eliminar la cuenta de «' + nombre + '»? Esta acción no se puede deshacer.'
                : '¿Eliminar esta cuenta? Esta acción no se puede deshacer.';
            if (!window.confirm(msg)) return;
            btn.disabled = true;
            try {
                const res = await fetch(url, {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    window.alert(data.message || 'No se pudo eliminar la cuenta.');
                    return;
                }
                const row = btn.closest('tr');
                if (row) row.remove();
                const tbody = document.querySelector('table tbody');
                if (tbody && tbody.querySelectorAll('tr').length === 0) {
                    const cols = tbody.closest('table')?.querySelectorAll('thead th').length || 8;
                    tbody.innerHTML = '<tr><td colspan="' + cols + '" class="border p-4 text-center text-slate-600">No hay usuarios registrados.</td></tr>';
                }
            } catch (e) {
                window.alert('Error de red al eliminar la cuenta.');
            } finally {
                btn.disabled = false;
            }
        });
    });

    document.querySelectorAll('.btn-toggle-activo').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const url = btn.dataset.url || '';
            if (!url) return;
            const nuevoActivo = btn.dataset.activo !== '1';
            btn.disabled = true;
            try {
                const res = await fetch(url, {
                    method: 'PATCH',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ activo: nuevoActivo }),
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    window.alert(data.message || 'No se pudo actualizar el estado.');
                    return;
                }
                const activo = Boolean(data.activo);
                btn.dataset.activo = activo ? '1' : '0';
                btn.setAttribute('aria-pressed', activo ? 'true' : 'false');
                btn.title = activo ? 'Activo (clic para desactivar)' : 'Inactivo (clic para activar)';
                btn.classList.toggle('bg-emerald-500', activo);
                btn.classList.toggle('bg-red-500', !activo);
                const icon = btn.querySelector('i');
                if (icon) {
                    icon.className = 'fas ' + (activo ? 'fa-check' : 'fa-times');
                }
            } catch (e) {
                window.alert('Error de red al cambiar el estado.');
            } finally {
                btn.disabled = false;
            }
        });
    });
})();
