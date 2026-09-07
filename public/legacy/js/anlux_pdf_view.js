/**
 * Vista PDF: en tableta/móvil Chrome no muestra PDF dentro de iframe ("contenido bloqueado").
 * En esos dispositivos abrimos el PDF en pestaña nueva.
 */
(function (global) {
    function anluxPdfPreferirNuevaPestana() {
        const ua = String(global.navigator?.userAgent || '');

        if (/Android/i.test(ua)) {
            return true;
        }

        if (/iPhone|iPod/i.test(ua)) {
            return true;
        }

        if (global.matchMedia) {
            const tactil = global.matchMedia('(pointer: coarse)').matches;
            const sinHover = global.matchMedia('(hover: none)').matches;
            const pantallaCompacta = global.matchMedia('(max-width: 1024px)').matches;
            if (tactil && sinHover && pantallaCompacta) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param {number|string} id
     * @param {string} folio
     * @param {{ urlBuilder: (id: number|string) => string, onIframe: (id: number|string, folio: string, url: string) => void, onPopupBlocked?: (url: string, folio: string) => void }} options
     */
    function anluxAbrirPdf(id, folio, options) {
        const url = options.urlBuilder(id);

        if (anluxPdfPreferirNuevaPestana()) {
            const ventana = global.open(url, '_blank', 'noopener,noreferrer');
            if (!ventana && typeof options.onPopupBlocked === 'function') {
                options.onPopupBlocked(url, folio);
            }

            return 'tab';
        }

        options.onIframe(id, folio, url);

        return 'iframe';
    }

    global.anluxPdfView = {
        preferirNuevaPestana: anluxPdfPreferirNuevaPestana,
        abrir: anluxAbrirPdf,
    };
})(window);
