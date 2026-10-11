@props(['message', 'error' => false])

@if ($message)
    <div {{ $attributes->class([
        'rounded-lg border px-4 py-3 text-sm',
        'border-rose-200 bg-rose-50 text-rose-800' => $error,
        'border-emerald-200 bg-emerald-50 text-emerald-800' => ! $error,
    ]) }} role="{{ $error ? 'alert' : 'status' }}" wire:key="notice-{{ md5($message) }}">
        {{ $message }}
    </div>
@endif
