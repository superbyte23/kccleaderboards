@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <h2 class="font-display text-2xl font-bold tracking-tight text-white sm:text-3xl">{{ $title }}</h2>
    <p class="mt-2 text-sm leading-relaxed text-zinc-400">{{ $description }}</p>
</div>
