@props([
    'rank' => 1,
    'class' => 'size-6',
])

@php
    $medals = [
        1 => ['fill' => '#e5b64b', 'text' => '#221606'],
        2 => ['fill' => '#aab0bb', 'text' => '#16181d'],
        3 => ['fill' => '#c07a48', 'text' => '#221606'],
    ];
    $fill = $medals[$rank]['fill'] ?? '#3a3f4b';
    $text = $medals[$rank]['text'] ?? '#e5e7eb';
@endphp

<svg viewBox="0 0 24 24" class="{{ $class }}" aria-hidden="true">
    <circle cx="12" cy="12" r="11" fill="{{ $fill }}" />
    <circle cx="12" cy="12" r="10.25" fill="none" stroke="rgba(255,255,255,0.35)" stroke-width="1" />
    <text x="12" y="16.5" text-anchor="middle" font-family="'Space Mono', monospace" font-size="11" font-weight="700" fill="{{ $text }}">{{ $rank }}</text>
</svg>