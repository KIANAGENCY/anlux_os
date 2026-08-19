<div id="exactoUiModal" class="hidden fixed inset-0 z-[20000] items-center justify-center bg-slate-950/70 p-4" role="dialog" aria-modal="true" aria-labelledby="exactoUiModalTitle" style="z-index:20000;">
    <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 id="exactoUiModalTitle" class="text-center text-xl font-bold text-blue-900">Aviso</h3>
        </div>
        <div class="exacto-ui-modal-body px-5 py-5 text-center">
            <div id="exactoUiModalIconWrap" class="mb-4 hidden flex justify-center">
                <span id="exactoUiModalIconCircle" class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
                    <i id="exactoUiModalIcon" class="fas fa-info-circle text-2xl text-slate-600"></i>
                </span>
            </div>
            <p id="exactoUiModalMessage" class="mx-auto max-w-prose whitespace-pre-line text-center text-sm leading-relaxed text-slate-700"></p>
            <div id="exactoUiModalInputWrap" class="mt-4 hidden text-left">
                <label id="exactoUiModalInputLabel" for="exactoUiModalInput" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600"></label>
                <input type="text" id="exactoUiModalInput" class="w-full rounded-lg border-2 border-blue-300 px-3 py-2 text-sm font-semibold uppercase text-slate-800 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-200" autocomplete="off" maxlength="80">
            </div>
        </div>
        <div class="flex flex-col-reverse items-center gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:justify-center">
            <button type="button" id="exactoUiModalCancel" class="hidden rounded-lg border-2 border-slate-300 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                Cancelar
            </button>
            <button type="button" id="exactoUiModalConfirm" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-blue-700">
                Aceptar
            </button>
        </div>
    </div>
</div>
