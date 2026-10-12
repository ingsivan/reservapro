{{--
    One confirmation dialog per page. Open it with:
    $dispatch('confirm-action', { title, message, confirmLabel, danger, action: () => $wire.method(id) })
--}}
<div x-data="{
        title: '',
        message: '',
        confirmLabel: 'Confirmar',
        danger: true,
        action: null,
        open(detail) {
            Object.assign(this, { confirmLabel: 'Confirmar', danger: true, message: '' }, detail);
            this.$refs.dialog.showModal();
        },
        accept() {
            this.$refs.dialog.close();
            this.action?.();
        },
     }"
     x-on:confirm-action.window="open($event.detail)">
    <dialog x-ref="dialog" aria-labelledby="confirm-title"
            class="m-auto w-[calc(100%-2rem)] max-w-md overflow-hidden rounded-2xl bg-white p-0 text-slate-800 shadow-2xl backdrop:bg-slate-900/50 backdrop:backdrop-blur-sm">
        <div class="flex gap-4 p-6">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-full" :class="danger ? 'bg-rose-100 text-rose-600' : 'bg-teal-100 text-teal-700'">
                <x-icon name="alert" class="size-5" />
            </div>
            <div>
                <h2 id="confirm-title" class="font-semibold text-slate-900" x-text="title"></h2>
                <p class="mt-1 text-sm text-slate-600" x-text="message" x-show="message"></p>
            </div>
        </div>
        <div class="flex flex-col-reverse gap-2 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">
            <button type="button" x-on:click="$refs.dialog.close()" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Volver</button>
            <button type="button" x-on:click="accept()" x-text="confirmLabel"
                    :class="danger ? 'bg-rose-600 hover:bg-rose-500' : 'bg-teal-600 hover:bg-teal-500'"
                    class="rounded-lg px-4 py-2 text-sm font-semibold text-white"></button>
        </div>
    </dialog>
</div>
