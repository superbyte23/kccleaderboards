<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        @livewireStyles
    </head>
    <body class="min-h-screen bg-canvas font-sans text-zinc-100">
        <div class="relative flex min-h-screen flex-col overflow-hidden grain">
            {{-- Shared arena ambience (same FX as the guest shell) --}}
            <div class="pointer-events-none fixed inset-x-0 top-0 h-[480px] bg-[radial-gradient(60%_100%_at_50%_0%,rgb(229_182_75/0.10),transparent_70%)]"></div>
            @include('partials.ambient')
            <div class="animate-drift pointer-events-none absolute -left-32 top-40 size-96 rounded-full bg-gold-400/10 blur-3xl"></div>
            <div class="animate-drift pointer-events-none absolute -right-32 top-[480px] size-[28rem] rounded-full bg-purple-500/10 blur-3xl [animation-duration:20s]"></div>
            <span class="animate-float pointer-events-none absolute left-[8%] top-[300px] hidden size-1.5 rounded-full bg-gold-400/60 sm:block"></span>
            <span class="animate-float pointer-events-none absolute right-[10%] top-[220px] hidden size-1 rounded-full bg-zinc-400/50 [animation-delay:1.4s] sm:block"></span>
            <span class="animate-float pointer-events-none absolute left-[14%] top-[560px] hidden size-1 rounded-full bg-purple-400/50 [animation-delay:2.6s] lg:block"></span>
            <span class="animate-float pointer-events-none absolute right-[16%] top-[640px] hidden size-1.5 rounded-full bg-gold-300/40 [animation-delay:0.7s] lg:block"></span>

            <nav class="relative z-10 mx-auto flex w-full max-w-6xl items-center justify-between gap-3 px-4 py-4 sm:px-6 sm:py-6 md:px-12">
                <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2.5 sm:gap-3" wire:navigate>
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent text-accent-foreground shadow-gold sm:size-10">
                        <x-app-logo-icon class="size-5 fill-current sm:size-6" />
                    </span>
                    <span class="leading-tight">
                        <span class="block truncate font-display text-base font-bold tracking-tight sm:text-lg">Rally</span>
                        <span class="block text-[9px] font-semibold uppercase tracking-[0.2em] text-gold-300 sm:text-[10px]">Live Leaderboards</span>
                    </span>
                </a>

                <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                    @if (request()->routeIs('register'))
                        <flux:button :href="route('login')" wire:navigate variant="subtle" size="sm">Log in</flux:button>
                    @elseif (Route::has('register'))
                        <flux:button :href="route('register')" wire:navigate size="sm">Join in</flux:button>
                    @endif
                </div>
            </nav>

            <main class="relative z-10 mx-auto flex w-full max-w-md flex-1 flex-col justify-center px-4 py-10 sm:px-6">
                <div class="animate-rise relative">
                    <div class="pointer-events-none absolute -inset-6 rounded-[2rem] bg-gold-400/10 blur-2xl"></div>
                    <div class="relative overflow-hidden rounded-2xl border border-line bg-ink shadow-2xl">
                        <div class="h-1 bg-gradient-to-r from-transparent via-gold-400/70 to-transparent"></div>
                        <div class="flex justify-center pt-6">
                            <span class="flex size-11 items-center justify-center rounded-xl bg-accent text-accent-foreground shadow-gold ring-1 ring-gold-400/40">
                                <x-app-logo-icon class="size-6 fill-current" />
                            </span>
                        </div>
                        <div class="px-6 py-6 sm:px-8">{{ $slot }}</div>
                    </div>
                    <p class="mt-4 text-center font-mono text-[10px] uppercase tracking-[0.25em] text-zinc-600">Rally · Secure Access</p>
                </div>
            </main>

            @include('partials.footer')
        </div>

        @livewireScripts
        @fluxScripts
    </body>
</html>
