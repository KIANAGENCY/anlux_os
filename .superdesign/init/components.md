# Shared UI Components

The application uses Laravel Blade and Tailwind utility classes rather than a JavaScript component library. The principal shared dialog primitive is below.

## Anlux UI Modal

- Path: `resources/views/partials/anlux-ui-modal.blade.php`
- Purpose: Shared alert, confirmation, and prompt dialog.

```blade
<div id="anluxUiModal" class="hidden fixed inset-0 z-[30000] items-center justify-center bg-slate-950/70 p-4" role="dialog" aria-modal="true" aria-labelledby="anluxUiModalTitle" style="z-index:30000;">
    <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 id="anluxUiModalTitle" class="text-center text-xl font-bold text-blue-900">Aviso</h3>
        </div>
        <div class="anlux-ui-modal-body px-5 py-5 text-center">
            <div id="anluxUiModalIconWrap" class="mb-4 hidden flex justify-center">
                <span id="anluxUiModalIconCircle" class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
                    <i id="anluxUiModalIcon" class="fas fa-info-circle text-2xl text-slate-600"></i>
                </span>
            </div>
            <p id="anluxUiModalMessage" class="mx-auto max-w-prose whitespace-pre-line text-center text-sm leading-relaxed text-slate-700"></p>
            <div id="anluxUiModalInputWrap" class="mt-4 hidden text-left">
                <label id="anluxUiModalInputLabel" for="anluxUiModalInput" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600"></label>
                <input type="text" id="anluxUiModalInput" class="w-full rounded-lg border-2 border-blue-300 px-3 py-2 text-sm font-semibold uppercase text-slate-800 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-200" autocomplete="off" maxlength="80">
            </div>
        </div>
        <div class="flex flex-col-reverse items-center gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:justify-center">
            <button type="button" id="anluxUiModalCancel" class="hidden rounded-lg border-2 border-slate-300 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Cancelar</button>
            <button type="button" id="anluxUiModalConfirm" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-blue-700">Aceptar</button>
        </div>
    </div>
</div>
```

## Delivery Signature Modal

- Path: `resources/views/orders/orden_form.blade.php` lines 1808-1885
- Purpose: Existing target for this design task. It includes recipient selection, recipient name, two signature canvases, selected-equipment summary, cancel action, and submit/notification action.

