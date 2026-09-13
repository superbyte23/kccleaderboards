{{-- Shared ambient background: 45° diamond grid + constellation canvas --}}
<div class="pointer-events-none fixed inset-0 [mask-image:radial-gradient(75%_60%_at_50%_0%,black,transparent)]">
    <div class="animate-grid-pan absolute inset-[-50%] rotate-45 bg-[linear-gradient(rgb(255_255_255/0.03)_1px,transparent_1px),linear-gradient(90deg,rgb(255_255_255/0.03)_1px,transparent_1px)] bg-[size:44px_44px]"></div>
</div>
<canvas id="constellation" class="pointer-events-none fixed inset-0 h-full w-full" aria-hidden="true"></canvas>
