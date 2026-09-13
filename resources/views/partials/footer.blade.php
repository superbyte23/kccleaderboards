<footer class="relative border-t border-line px-4 py-6 sm:px-6 md:px-12">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-center gap-x-5 gap-y-2 text-center text-xs text-zinc-500 sm:justify-between sm:text-left">
        <a href="{{ route('home') }}" class="flex items-center gap-2" wire:navigate>
            <span class="flex size-6 shrink-0 items-center justify-center rounded-lg bg-accent text-accent-foreground shadow-gold">
                <x-app-logo-icon class="size-3.5 fill-current" />
            </span>
            <span class="font-display text-sm font-bold tracking-tight text-white">Rally</span>
        </a>

        <nav class="flex flex-wrap items-center justify-center gap-x-6 gap-y-1 sm:gap-x-8">
            <a href="{{ route('home') }}#events" wire:navigate class="transition-colors hover:text-gold-300">Explore events</a>
            @auth
                <a href="{{ route('dashboard') }}" wire:navigate class="transition-colors hover:text-gold-300">Dashboard</a>
            @else
                <a href="{{ route('login') }}" wire:navigate class="transition-colors hover:text-gold-300">Log in</a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" wire:navigate class="transition-colors hover:text-gold-300">Join in</a>
                @endif
            @endauth
        </nav>

        <span>© {{ date('Y') }} Rally. All rights reserved.</span>
    </div>
</footer>
