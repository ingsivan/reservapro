@props(['staffMember'])

{{-- Initials on the staff member's color, to tell people apart in the agenda. --}}
<span aria-hidden="true" {{ $attributes->class('flex shrink-0 items-center justify-center rounded-full font-semibold '.$staffMember->avatarClasses()) }}>{{ $staffMember->initials() }}</span>
