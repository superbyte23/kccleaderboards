@props(['name' => 'scan-eye'])

@php
$paths = [
    'scan-eye' => '
        <path d="M7 12q 5 -7 10 0" />
        <path d="M7 12q 5 7 10 0" />
        <path d="M12 12h-.01" />
        <path d="M3 7v-2a2 2 0 0 1 2 -2h2" />
        <path d="M3 17v2a2 2 0 0 0 2 2h2" />
        <path d="M17 3h2a2 2 0 0 1 2 2v2" />
        <path d="M17 21h2a2 2 0 0 0 2 -2v-2" />',
    'pencil-cog' => '
        <path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" />
        <path d="M13.5 6.5l4 4" />
        <path d="M17.001 19a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
        <path d="M19.001 15.5v1.5" />
        <path d="M19.001 21v1.5" />
        <path d="M22.032 17.25l-1.299 .75" />
        <path d="M17.27 20l-1.3 .75" />
        <path d="M15.97 17.25l1.3 .75" />
        <path d="M20.733 20l1.3 .75" />',
    'trash-x' => '
        <path d="M4 7h16" />
        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
        <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
        <path d="M10 12l4 4m0 -4l-4 4" />',
    'star-filled' => '
        <path d="M8.243 7.34l-6.38 .925l-.113 .023a1 1 0 0 0 -.44 1.684l4.622 4.499l-1.09 6.355l-.013 .11a1 1 0 0 0 1.464 .944l5.706 -3l5.693 3l.1 .046a1 1 0 0 0 1.352 -1.1l-1.091 -6.355l4.624 -4.5l.078 -.085a1 1 0 0 0 -.633 -1.62l-6.38 -.926l-2.852 -5.78a1 1 0 0 0 -1.794 0l-2.853 5.78z" />',
    'x' => '
        <path d="M18 6l-12 12" />
        <path d="M6 6l12 12" />',
    'device-floppy' => '
        <path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2" />
        <path d="M10 14a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
        <path d="M14 4l0 4l-6 0l0 -4" />',
];
$filled = $name === 'star-filled';
@endphp

<svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->merge(['class' => 'inline-block size-4 shrink-0 align-middle']) }} viewBox="0 0 24 24" fill="{{ $filled ? 'currentColor' : 'none' }}" stroke="{{ $filled ? 'none' : 'currentColor' }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths[$name] ?? $paths['scan-eye'] !!}</svg>
