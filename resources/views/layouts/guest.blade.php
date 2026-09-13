<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0a0b0d">
        <meta name="description" content="Rally — live leaderboards, team standings, and results for your events.">

        <title>{{ config('app.name', 'Rally') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=archivo:400,500,600,700,800,900&family=barlow-condensed:500,600,700,800&family=space-mono:400,700&display=swap" rel="stylesheet" />

        <script>
            window.localStorage.setItem('flux.appearance', 'dark')
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
        @fluxAppearance
    </head>
    <body class="min-h-screen bg-canvas font-sans text-zinc-100">
        <div class="relative min-h-screen overflow-hidden grain">
            {{-- Shared arena ambience (game-website FX for all guest pages) --}}
            <div class="pointer-events-none fixed inset-x-0 top-0 h-[480px] bg-[radial-gradient(60%_100%_at_50%_0%,rgb(229_182_75/0.10),transparent_70%)]"></div>
            @include('partials.ambient')
            <div class="animate-drift pointer-events-none absolute -left-32 top-40 size-96 rounded-full bg-gold-400/10 blur-3xl"></div>
            <div class="animate-drift pointer-events-none absolute -right-32 top-[480px] size-[28rem] rounded-full bg-purple-500/10 blur-3xl [animation-duration:20s]"></div>
            <span class="animate-float pointer-events-none absolute left-[8%] top-[300px] hidden size-1.5 rounded-full bg-gold-400/60 sm:block"></span>
            <span class="animate-float pointer-events-none absolute right-[10%] top-[220px] hidden size-1 rounded-full bg-zinc-400/50 [animation-delay:1.4s] sm:block"></span>
            <span class="animate-float pointer-events-none absolute left-[14%] top-[560px] hidden size-1 rounded-full bg-purple-400/50 [animation-delay:2.6s] lg:block"></span>
            <span class="animate-float pointer-events-none absolute right-[16%] top-[640px] hidden size-1.5 rounded-full bg-gold-300/40 [animation-delay:0.7s] lg:block"></span>

            <nav class="relative z-10 mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-4 sm:px-6 sm:py-6 md:px-12">
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
                    @auth
                        <flux:button :href="route('dashboard')" wire:navigate size="sm" icon-trailing="arrow-right">Dashboard</flux:button>
                    @else
                        <flux:button :href="route('login')" wire:navigate variant="subtle" size="sm">Log in</flux:button>
                        @if (Route::has('register'))
                            <flux:button :href="route('register')" wire:navigate size="sm">Join in</flux:button>
                        @endif
                    @endauth
                </div>
            </nav>

            <div class="relative z-10 mx-auto max-w-6xl px-4 pb-16 pt-8 sm:px-6 sm:pb-20 sm:pt-12 md:px-12">
                {{ $slot }}
            </div>

            @include('partials.footer')
        </div>

        @livewireScripts
        @fluxScripts
    </body>
</html>
