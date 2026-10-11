@props(['name', 'label', 'type' => 'text', 'hint' => null])

<div>
    <label for="{{ $name }}" class="text-sm font-medium text-slate-700">{{ $label }}</label>
    @if ($type === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" rows="3"
                  {{ $attributes->class('mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/30 focus:outline-none') }}>{{ $attributes->has('wire:model') ? '' : old($name) }}</textarea>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
               @if ($type !== 'password' && ! $attributes->has('wire:model') && ! $attributes->has('value')) value="{{ old($name) }}" @endif
               {{ $attributes->class('mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/30 focus:outline-none') }} />
    @endif
    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
    @enderror
</div>
