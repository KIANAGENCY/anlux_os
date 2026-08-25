(function () {
    'use strict';

    function exactoUiDialog(config) {
        const modal = document.getElementById('exactoUiModal');
        const titleEl = document.getElementById('exactoUiModalTitle');
        const iconWrapEl = document.getElementById('exactoUiModalIconWrap');
        const iconCircleEl = document.getElementById('exactoUiModalIconCircle');
        const iconEl = document.getElementById('exactoUiModalIcon');
        const messageEl = document.getElementById('exactoUiModalMessage');
        const confirmBtn = document.getElementById('exactoUiModalConfirm');
        const cancelBtn = document.getElementById('exactoUiModalCancel');
        if (!modal || !titleEl || !messageEl || !confirmBtn || !cancelBtn || !iconWrapEl || !iconCircleEl || !iconEl) {
            if (config.showCancel) {
                return Promise.resolve(window.confirm(config.message || '¿Deseas continuar?'));
            }
            window.alert(config.message || 'Aviso');
            return Promise.resolve(true);
        }

        return new Promise((resolve) => {
            const previousOverflow = document.body.style.overflow;
            titleEl.textContent = config.title || (config.showCancel ? 'Confirmar' : 'Aviso');
            messageEl.textContent = String(config.message || '');
            confirmBtn.textContent = config.confirmText || 'Aceptar';
            cancelBtn.textContent = config.cancelText || 'Cancelar';
            cancelBtn.classList.toggle('hidden', !config.showCancel);
            iconWrapEl.classList.toggle('hidden', !config.icon);
            iconWrapEl.classList.toggle('flex', Boolean(config.icon));
            iconCircleEl.className = 'flex h-14 w-14 items-center justify-center rounded-full bg-slate-100';
            iconEl.className = 'fas fa-info-circle text-2xl text-slate-600';
            if (config.icon === 'success') {
                iconCircleEl.classList.add('bg-emerald-100');
                iconEl.className = 'fas fa-check text-2xl text-emerald-600';
            } else if (config.icon === 'error') {
                iconCircleEl.classList.add('bg-red-100');
                iconEl.className = 'fas fa-times text-2xl text-red-600';
            } else if (config.icon === 'warning') {
                iconCircleEl.classList.add('bg-amber-100');
                iconEl.className = 'fas fa-exclamation text-2xl text-amber-600';
            }
            // Encima de otros modales de la orden (liquidar/entrega = 20000).
            if (modal.parentNode !== document.body) {
                document.body.appendChild(modal);
            }
            modal.style.zIndex = '30000';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';

            const cleanup = (value) => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = previousOverflow;
                confirmBtn.removeEventListener('click', onConfirm);
                cancelBtn.removeEventListener('click', onCancel);
                modal.removeEventListener('click', onBackdrop);
                document.removeEventListener('keydown', onKeydown);
                resolve(value);
            };
            const onConfirm = () => cleanup(true);
            const onCancel = () => cleanup(false);
            const onBackdrop = (event) => {
                if (event.target === modal) {
                    cleanup(config.showCancel ? false : true);
                }
            };
            const onKeydown = (event) => {
                if (event.key === 'Escape') {
                    cleanup(config.showCancel ? false : true);
                }
            };

            confirmBtn.addEventListener('click', onConfirm);
            cancelBtn.addEventListener('click', onCancel);
            modal.addEventListener('click', onBackdrop);
            document.addEventListener('keydown', onKeydown);
            confirmBtn.focus();
        });
    }

    window.exactoShowAlert = function (message, options) {
        options = options || {};
        return exactoUiDialog({
            title: options.title || 'Aviso',
            message: message,
            confirmText: options.confirmText || 'Aceptar',
            icon: options.icon,
            showCancel: false,
        });
    };

    window.exactoShowConfirm = function (message, options) {
        options = options || {};
        return exactoUiDialog({
            title: options.title || 'Confirmar',
            message: message,
            confirmText: options.confirmText || 'Continuar',
            cancelText: options.cancelText || 'Cancelar',
            icon: options.icon,
            showCancel: true,
        });
    };
})();
