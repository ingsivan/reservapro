@props(['title'])

<x-layouts::app :title="$title">
    <div class="mx-auto max-w-sm">
        <h1 class="text-center text-2xl font-bold text-slate-900">{{ $title }}</h1>
        <div class="mt-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            {{ $slot }}
        </div>
        @isset($footer)
            <p class="mt-4 text-center text-sm text-slate-600">{{ $footer }}</p>
        @endisset
    </div>
</x-layouts::app>
