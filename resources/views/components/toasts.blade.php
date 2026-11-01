@props(['position' => 'top-right'])

<div
    class="pointer-events-none fixed inset-x-0 top-4 z-[70] flex flex-col items-center gap-2 px-4 sm:items-end sm:pr-6"
    role="status"
    aria-live="polite"
>
    <template x-for="toast in $store.toasts.items" :key="toast.id">
        <div class="toast animate-toast-in max-w-sm" x-transition.opacity>
            <div
                class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full"
                :class="toast.type === 'error' ? 'bg-red-50 text-danger' : (toast.type === 'info' ? 'bg-slate-100 text-muted' : 'bg-green-50 text-success')"
            >
                <svg x-show="toast.type === 'error'" class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                <svg x-show="toast.type === 'info'" class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                <svg x-show="toast.type !== 'error' && toast.type !== 'info'" class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
            </div>
            <p class="flex-1 pt-0.5 text-sm font-medium text-ink" x-text="toast.message"></p>
            <button type="button" class="rounded-md p-1 text-slate-400 transition hover:bg-slate-100 hover:text-ink" @click="$store.toasts.dismiss(toast.id)" aria-label="Dismiss notification">
                <x-icon name="x" class="h-3.5 w-3.5" />
            </button>
        </div>
    </template>
</div>

@if (session('success') || session('error') || session('info'))
    <div
        x-data
        x-init="
            @if (session('success')) window.toast(@js(session('success'))); @endif
            @if (session('error')) window.toast(@js(session('error')), 'error'); @endif
            @if (session('info')) window.toast(@js(session('info')), 'info'); @endif
        "
    ></div>
@endif
