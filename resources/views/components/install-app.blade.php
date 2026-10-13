@props(['compact' => false])

{{--
    "Instalar la app" button. On Chrome, Edge and Android it opens the browser's install prompt
    (captured in resources/js/app.js); on iPhone and iPad, where Safari has no prompt, it explains
    how to add ReservaPro to the home screen. Hidden once the app is installed.
--}}
<div x-data="{
        canInstall: !! window.deferredInstallPrompt,
        isIos: /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1),
        hint: false,
        installed: window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true,
        async install() {
            const prompt = window.deferredInstallPrompt;

            if (! prompt) {
                this.hint = ! this.hint;

                return;
            }

            prompt.prompt();
            const { outcome } = await prompt.userChoice;
            window.deferredInstallPrompt = null;
            this.canInstall = false;

            if (outcome === 'accepted') {
                this.installed = true;
            }
        },
     }"
     x-on:app-installable.window="canInstall = true"
     x-on:appinstalled.window="installed = true"
     x-on:click.outside="hint = false"
     x-show="! installed && (canInstall || isIos)" x-cloak
     {{ $attributes->merge(['class' => 'relative']) }}>
    @if ($compact)
        <button type="button" x-on:click="install" x-bind:aria-expanded="hint" class="rounded-md p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900" aria-label="Instalar la app" title="Instalar la app">
            <x-icon name="download" class="size-5" />
        </button>
    @else
        <button type="button" x-on:click="install"
                class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-teal-700 hover:bg-teal-50">
            <x-icon name="download" class="size-5" />
            Instalar la app
        </button>
    @endif
    <p x-show="hint" x-transition.opacity role="status"
       @class([
           'z-40 rounded-lg bg-slate-900 px-3 py-2 text-xs leading-relaxed text-white shadow-lg',
           'absolute top-full right-0 mt-2 w-60' => $compact,
           'mx-3 mt-1' => ! $compact,
       ])>
        En Safari, toca <strong>Compartir</strong> y luego <strong>Agregar a inicio</strong>.
    </p>
</div>
