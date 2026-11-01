<div
    x-cloak
    x-show="$store.confirm.open"
    class="modal-backdrop"
    @keydown.escape.window="$store.confirm.open && $store.confirm.cancel()"
    @keydown.enter.window="$store.confirm.open && ($event.preventDefault(), $store.confirm.confirm())"
>
    <div class="modal-panel max-w-md p-6" role="dialog" aria-modal="true" aria-labelledby="confirm-title" @click.outside="$store.confirm.cancel()">
        <div class="flex items-start gap-4">
            <div
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full"
                :class="$store.confirm.tone === 'danger' ? 'bg-red-50 text-danger' : 'bg-orange-soft text-orange-dark'"
            >
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
            </div>
            <div class="min-w-0 flex-1">
                <h2 id="confirm-title" class="font-display text-lg font-semibold text-ink" x-text="$store.confirm.title"></h2>
                <p class="mt-1.5 text-sm leading-relaxed text-muted" x-text="$store.confirm.message"></p>
            </div>
        </div>
        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" class="btn btn-secondary" @click="$store.confirm.cancel()" x-text="$store.confirm.cancelLabel"></button>
            <button
                type="button"
                class="btn"
                :class="$store.confirm.tone === 'danger' ? 'btn-danger' : 'btn-primary'"
                @click="$store.confirm.confirm()"
                x-ref="confirmButton"
                x-effect="$store.confirm.open && $nextTick(() => $refs.confirmButton.focus())"
                x-text="$store.confirm.confirmLabel"
            ></button>
        </div>
    </div>
</div>
