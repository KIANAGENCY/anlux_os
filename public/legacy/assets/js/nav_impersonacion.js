(function () {

    'use strict';



    const csrf = window.ANLUX_CSRF_TOKEN || '';

    const baseUrl = String(window.ANLUX_BASE_URL || '').replace(/\/$/, '');

    const anluxUrl = (path) => `${baseUrl}${path.startsWith('/') ? path : `/${path}`}`;

    const STORAGE_TOKEN_KEY = 'anlux_impersonacion_token';



    const isImpersonating = Boolean(window.ANLUX_IS_IMPERSONATING);

    const userId = Number(window.ANLUX_USER_ID || 0);



    let pollRequesterToken = null;

    let pollRequesterTimer = null;

    let pollInboxTimer = null;

    let accountsRefreshTimer = null;

    let applyingSession = false;

    const handledRequestIds = new Set();



    function getAccountSelect() {

        return document.getElementById('navSelectCuenta') || document.getElementById('navSelectTecnico');

    }



    async function fetchJson(url, options) {

        const res = await fetch(url, Object.assign({

            credentials: 'same-origin',

            headers: {

                Accept: 'application/json',

                'X-Requested-With': 'XMLHttpRequest',

                'X-CSRF-TOKEN': csrf,

            },

        }, options || {}));

        const data = await res.json().catch(() => ({}));

        return { res, data };

    }



    function ensureLoadingOverlay() {

        let el = document.getElementById('anluxImpersonationLoading');

        if (el) {

            return el;

        }



        el = document.createElement('div');

        el.id = 'anluxImpersonationLoading';

        el.className = 'hidden fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/75 p-4';

        el.setAttribute('role', 'alertdialog');

        el.setAttribute('aria-live', 'polite');

        el.innerHTML = `

            <div class="w-full max-w-xs rounded-2xl bg-white px-6 py-7 text-center shadow-2xl">

                <div class="anlux-imperson-spinner mx-auto mb-3 h-12 w-12 rounded-full border-4 border-blue-200 border-t-blue-600" aria-hidden="true"></div>

                <p id="anluxImpersonationLoadingMsg" class="text-sm font-semibold text-blue-900"></p>

            </div>

        `;



        const style = document.createElement('style');

        style.textContent = `

            @keyframes anlux-imperson-spin { to { transform: rotate(360deg); } }

            .anlux-imperson-spinner { animation: anlux-imperson-spin 0.85s linear infinite; }

        `;

        document.head.appendChild(style);

        document.body.appendChild(el);



        return el;

    }



    function showLoading(message) {

        const el = ensureLoadingOverlay();

        const msg = el.querySelector('#anluxImpersonationLoadingMsg');

        if (msg) {

            msg.textContent = message || 'Procesando…';

        }

        el.classList.remove('hidden');

        el.classList.add('flex');

        document.body.style.overflow = 'hidden';

    }



    function updateLoadingMessage(message) {

        const msg = document.getElementById('anluxImpersonationLoadingMsg');

        if (msg) {

            msg.textContent = message || '';

        }

    }



    function hideLoading() {

        const el = document.getElementById('anluxImpersonationLoading');

        if (!el) {

            return;

        }

        el.classList.add('hidden');

        el.classList.remove('flex');

        document.body.style.overflow = '';

    }



    function persistToken(token) {

        if (token) {

            try {

                sessionStorage.setItem(STORAGE_TOKEN_KEY, token);

            } catch (e) {

                /* ignore */

            }

        }

    }



    function clearPersistedToken() {

        try {

            sessionStorage.removeItem(STORAGE_TOKEN_KEY);

        } catch (e) {

            /* ignore */

        }

    }



    async function stopRequesterPoll(cancelledByUser) {

        const tokenToCancel = pollRequesterToken;



        if (pollRequesterTimer) {

            clearInterval(pollRequesterTimer);

            pollRequesterTimer = null;

        }

        pollRequesterToken = null;

        applyingSession = false;

        clearPersistedToken();

        hideLoading();



        if (tokenToCancel && cancelledByUser) {

            try {

                await fetchJson(anluxUrl('/api/impersonacion/cancelar'), {

                    method: 'POST',

                    headers: { 'Content-Type': 'application/json' },

                    body: JSON.stringify({ token: tokenToCancel }),

                });

            } catch (e) {

                /* ignore */

            }

        }



        if (cancelledByUser) {

            const select = getAccountSelect();

            if (select) {

                select.value = select.dataset.lastValue || '';

            }

            loadAccountsSelect();

        }

    }



    function startRequesterPoll() {

        stopRequesterPoll(false);

        if (!pollRequesterToken) {

            return;

        }

        persistToken(pollRequesterToken);

        pollRequesterTimer = setInterval(pollRequesterStatus, 1200);

        pollRequesterStatus();

    }



    function statusPrefix(status) {

        if (status === 'online') return '● ';

        if (status === 'in_use') return '◐ ';

        if (status === 'offline') return '○ ';

        if (status === 'inactive') return '✕ ';

        if (status === 'self') return '→ ';

        return '';

    }



    function optionClass(status) {

        if (status === 'online') return 'anlux-opt-online';

        if (status === 'in_use') return 'anlux-opt-in_use';

        if (status === 'offline') return 'anlux-opt-offline';

        if (status === 'inactive') return 'anlux-opt-inactive';

        if (status === 'self') return 'anlux-opt-self';

        return '';

    }



    async function applySessionAndReload(token) {

        applyingSession = true;

        updateLoadingMessage('Entrando…');

        const apply = await fetchJson(anluxUrl('/api/impersonacion/aplicar'), {

            method: 'POST',

            headers: { 'Content-Type': 'application/json' },

            body: JSON.stringify({ token }),

        });



        if (apply.res.ok && apply.data.success) {

            clearPersistedToken();

            window.location.reload();

            return true;

        }



        applyingSession = false;

        return false;

    }



    async function pollRequesterStatus() {

        if (!pollRequesterToken || applyingSession) {

            return;

        }



        const { res, data } = await fetchJson(

            anluxUrl(`/api/impersonacion/estado/${encodeURIComponent(pollRequesterToken)}`)

        );



        if (!res.ok || !data.success) {

            if (data.status === 'invalid' || res.status === 404) {

                stopRequesterPoll(true);

                if (typeof window.anluxShowAlert === 'function') {

                    await window.anluxShowAlert(

                        data.message || 'La solicitud ya no es válida.',

                        { icon: 'warning', title: 'Solicitud expirada' }

                    );

                }

            }

            return;

        }



        if (data.status === 'pending') {

            updateLoadingMessage('Entrando…');

            const ok = await applySessionAndReload(pollRequesterToken);

            if (!ok) {

                stopRequesterPoll(true);

            }

            return;

        }



        if (data.status === 'approved' && data.can_apply) {

            const ok = await applySessionAndReload(pollRequesterToken);

            if (!ok) {

                stopRequesterPoll(true);

                if (typeof window.anluxShowAlert === 'function') {

                    await window.anluxShowAlert(

                        'No se pudo entrar a la cuenta.',

                        { icon: 'error', title: 'Error al acceder' }

                    );

                }

            }

            return;

        }



        if (data.status === 'denied' || data.status === 'expired') {

            stopRequesterPoll(true);

            const select = getAccountSelect();

            if (select) {

                select.value = select.dataset.lastValue || '';

            }

            if (typeof window.anluxShowAlert === 'function') {

                await window.anluxShowAlert(data.message || 'Acceso no autorizado.', { icon: 'warning' });

            }

        }

    }



    async function onAccountSelectChange(event) {

        const select = event.target;

        const previous = select.dataset.lastValue || '';

        const targetId = Number(select.value || 0);

        if (!targetId) {

            return;

        }

        const opt = select.options[select.selectedIndex];

        // El cambio de cuenta es instantáneo: no se espera confirmación del titular.
        showLoading('Entrando…');

        select.disabled = true;

        if (opt && opt.disabled) {

            select.disabled = false;

            select.value = previous;

            hideLoading();

            if (typeof window.anluxShowAlert === 'function') {

                await window.anluxShowAlert(

                    opt.dataset.hint || 'Esta cuenta no está disponible para cambio.',

                    { icon: 'warning', title: 'Cuenta no disponible' }

                );

            }

            return;

        }



        const { res, data } = await fetchJson(anluxUrl('/api/impersonacion/solicitar'), {

            method: 'POST',

            headers: { 'Content-Type': 'application/json' },

            body: JSON.stringify({ target_id: targetId }),

        });



        select.disabled = false;



        if (!res.ok || !data.success) {

            stopRequesterPoll(true);

            select.value = previous;

            if (typeof window.anluxShowAlert === 'function') {

                await window.anluxShowAlert(data.message || 'No se pudo solicitar el acceso.', { icon: 'error' });

            }

            return;

        }



        pollRequesterToken = data.token;

        select.dataset.lastValue = String(targetId);

        persistToken(pollRequesterToken);



        if (data.instant_apply || data.can_apply) {

            updateLoadingMessage('Entrando…');

            const ok = await applySessionAndReload(pollRequesterToken);

            if (!ok) {

                stopRequesterPoll(true);

                select.value = previous;

                if (typeof window.anluxShowAlert === 'function') {

                    await window.anluxShowAlert('No se pudo entrar a la cuenta.', { icon: 'error' });

                }

            }

            return;

        }



        updateLoadingMessage('Entrando…');

        const okFallback = await applySessionAndReload(pollRequesterToken);

        if (!okFallback) {

            stopRequesterPoll(true);

            select.value = previous;

            if (typeof window.anluxShowAlert === 'function') {

                await window.anluxShowAlert('No se pudo entrar a la cuenta.', { icon: 'error' });

            }

        }

    }



    async function pollTargetInbox() {

        if (isImpersonating) {

            return;

        }

        const { res, data } = await fetchJson(anluxUrl('/api/impersonacion/pendientes'));

        if (!res.ok || !data.success || !Array.isArray(data.data)) {

            return;

        }



        for (const item of data.data) {

            const id = Number(item.id || 0);

            if (!id || handledRequestIds.has(id)) {

                continue;

            }

            handledRequestIds.add(id);



            const quien = item.requester_nombre || 'Un usuario';

            const mensaje = `${quien} quiere acceder a tu cuenta.\n\n¿Permites el acceso?`;



            let approve = false;

            if (typeof window.anluxShowConfirm === 'function') {

                approve = await window.anluxShowConfirm(mensaje, {

                    title: 'Acceso a tu cuenta',

                    confirmText: 'Aceptar',

                    cancelText: 'Rechazar',

                    icon: 'warning',

                });

            } else {

                approve = window.confirm(mensaje);

            }



            await fetchJson(anluxUrl('/api/impersonacion/responder'), {

                method: 'POST',

                headers: { 'Content-Type': 'application/json' },

                body: JSON.stringify({ request_id: id, approve: approve }),

            });

        }

    }



    async function loadAccountsSelect() {

        const select = getAccountSelect();

        if (!select) {

            return;

        }



        const { res, data } = await fetchJson(anluxUrl('/api/impersonacion/cuentas'));

        const current = Number(select.dataset.currentId || userId || 0);

        const previous = select.value || select.dataset.lastValue || '';



        if (!res.ok || !data.success) {

            if (select.options.length <= 1 && typeof window.anluxShowAlert === 'function') {

                const msg = data.message

                    || (res.status === 500 ? 'Error del servidor al cargar cuentas.' : 'No se pudieron cargar las cuentas.');

                await window.anluxShowAlert(msg, { icon: 'warning', title: 'Selector de cuentas' });

            }

            return;

        }



        const list = Array.isArray(data.data) ? data.data : [];

        if (list.length === 0) {

            return;

        }



        select.innerHTML = '<option value="">— Cambiar de cuenta —</option>';



        list.forEach((t) => {

            const opt = document.createElement('option');

            const status = String(t.status || 'offline');

            const nombre = t.nombre || `Técnico #${t.id}`;

            const label = t.status_label || '';

            opt.value = String(t.id);

            opt.textContent = `${statusPrefix(status)}${nombre}${label ? ` — ${label}` : ''}`;

            opt.className = optionClass(status);

            opt.dataset.status = status;

            opt.disabled = !t.selectable;

            if (!t.selectable) {

                opt.dataset.hint =

                    status === 'inactive'

                        ? 'La cuenta está desactivada.'

                        : status === 'in_use'

                          ? 'Acceso directo disponible; vuelve a intentar.'

                        : status === 'self'

                          ? 'Ya estás en esta cuenta.'

                          : status === 'online'

                            ? 'En línea: acceso directo.'

                            : status === 'offline'

                              ? 'Sin conexión: entrarás de inmediato.'

                              : 'No disponible.';

            }

            if (Number(t.id) === current && status === 'self') {

                opt.selected = true;

                select.dataset.lastValue = String(t.id);

            } else if (previous && String(t.id) === String(previous)) {

                opt.selected = true;

            }

            select.appendChild(opt);

        });

    }



    async function exitImpersonation() {

        showLoading('Volviendo a tu cuenta…');

        const { res, data } = await fetchJson(anluxUrl('/api/impersonacion/salir'), { method: 'POST' });

        if (res.ok && data.success) {

            clearPersistedToken();

            window.location.reload();

            return;

        }

        hideLoading();

        if (typeof window.anluxShowAlert === 'function') {

            await window.anluxShowAlert(data.message || 'No se pudo volver a tu cuenta.', { icon: 'error' });

        }

    }



    async function resumePendingAccessIfAny() {

        if (isImpersonating) {

            return;

        }

        let saved = '';

        try {

            saved = sessionStorage.getItem(STORAGE_TOKEN_KEY) || '';

        } catch (e) {

            saved = '';

        }

        if (!saved) {

            return;

        }

        pollRequesterToken = saved;

        showLoading('Entrando…');

        const ok = await applySessionAndReload(saved);

        if (!ok) {

            stopRequesterPoll(true);

        }

    }



    function init() {

        const btnSalir = document.getElementById('navBtnSalirImpersonacion');

        if (btnSalir) {

            btnSalir.addEventListener('click', exitImpersonation);

        }



        const select = getAccountSelect();

        if (!isImpersonating && select) {

            loadAccountsSelect();

            select.addEventListener('change', onAccountSelectChange);

            resumePendingAccessIfAny();



            accountsRefreshTimer = setInterval(() => {

                if (!pollRequesterToken && !applyingSession) {

                    loadAccountsSelect();

                }

            }, 4000);

        }



        if (!isImpersonating) {

            const pollMs = 3000;

            pollInboxTimer = setInterval(pollTargetInbox, pollMs);

            pollTargetInbox();

            document.addEventListener('visibilitychange', () => {

                if (document.visibilityState === 'visible') {

                    pollTargetInbox();

                    if (!pollRequesterToken) {

                        loadAccountsSelect();

                    }

                    if (pollRequesterToken) {

                        pollRequesterStatus();

                    }

                }

            });

            window.addEventListener('focus', () => {

                pollTargetInbox();

                if (!pollRequesterToken) {

                    loadAccountsSelect();

                }

                if (pollRequesterToken) {

                    pollRequesterStatus();

                }

            });

        }

    }



    if (document.readyState === 'loading') {

        document.addEventListener('DOMContentLoaded', init);

    } else {

        init();

    }

})();
