<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main>
        {{ $slot }}

    <livewire:toaster />
    </flux:main>
</x-layouts::app.sidebar>
