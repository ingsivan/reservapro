{{-- Toast messages: session flashes on page load plus `toast` events dispatched by Livewire components. --}}
@php
    $initialToasts = array_values(array_filter([
        session('status') ? ['message' => session('status'), 'type' => 'success'] : null,
        session('error') ? ['message' => session('error'), 'type' => 'error'] : null,
    ]));
@endphp

<div x-data="{
        toasts: [],
        add(toast) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, type: 'success', ...toast });
            setTimeout(() => this.remove(id), toast.type === 'error' ? 8000 : 4500);
        },
        remove(id) {
            this.toasts = this.toasts.filter((toast) => toast.id !== id);
        },
     }"
     x-init="@js($initialToasts).forEach((toast) => add(toast))"
     x-on:toast.window="add($event.detail)"
     class="pointer-events-none fixed inset-x-0 bottom-4 z-50 flex flex-col items-center gap-2 px-4 sm:inset-x-auto sm:right-6 sm:bottom-6 sm:items-end"
     aria-live="polite">
    <template x-for="toast in toasts" :key="toast.id">
        <div x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-2 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
             x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             :role="toast.type === 'error' ? 'alert' : 'status'"
             class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl bg-slate-900 px-4 py-3 text-sm text-white shadow-lg shadow-slate-900/20">
            <span x-show="toast.type !== 'error'" class="mt-0.5 text-emerald-400"><x-icon name="check-circle" class="size-5" /></span>
            <span x-show="toast.type === 'error'" class="mt-0.5 text-rose-400"><x-icon name="alert" class="size-5" /></span>
            <p class="flex-1 leading-5" x-text="toast.message"></p>
            <button type="button" x-on:click="remove(toast.id)" class="-mr-1 rounded p-0.5 text-slate-400 hover:text-white" aria-label="Cerrar aviso">
                <x-icon name="x" class="size-4" />
            </button>
        </div>
    </template>
</div>
