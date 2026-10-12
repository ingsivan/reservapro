@props(['business'])

{{-- The business logo, or its initials on a dark square when there is no logo. --}}
@if ($logoUrl = $business->logoUrl())
    <img src="{{ $logoUrl }}" alt="Logo de {{ $business->name }}" {{ $attributes->class('bg-white object-cover') }}>
@else
    <span aria-hidden="true" {{ $attributes->class('flex items-center justify-center bg-slate-900 font-bold text-white') }}>{{ $business->initials() }}</span>
@endif
