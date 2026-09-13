<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-canvas text-zinc-100 antialiased">
        <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
            <div class="relative hidden h-full flex-col overflow-hidden p-10 text-white lg:flex">
                <div class="absolute inset-0 bg-[linear-gradient(160deg,#16181d_0%,#101215_55%,#0c0d10_100%)]"></div>
                <div class="pointer-events-none absolute inset-x-0 top-0 h-1/2 bg-[radial-gradient(70%_100%_at_50%_0%,rgb(229_182_75/0.14),transparent_70%)]"></div>

                <a href="{{ route('home') }}" class="relative z-20 flex items-center gap-3" wire:navigate>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-accent text-accent-foreground shadow-gold">
                        <x-app-logo-icon class="size-6 fill-current" />
                    </span>
                    <span class="leading-tight">
                        <span class="block font-display text-lg font-bold tracking-tight">Rally</span>
                        <span class="block text-[9px] font-semibold uppercase tracking-[0.2em] text-gold-300">Live Leaderboards</span>
                    </span>
                </a>

                @php
                    [$message, $author] = str(Illuminate\Foundation\Inspiring::quotes()->random())->explode('-');
                @endphp

                <div class="relative z-20 mt-auto">
                    <div class="mb-6 h-px w-24 bg-gold-400/50"></div>
                    <blockquote class="space-y-2">
                        <flux:heading size="lg" class="font-sans font-medium text-zinc-200">&ldquo;{{ trim($message) }}&rdquo;</flux:heading>
                        <footer><flux:heading class="text-gold-300">{{ trim($author) }}</flux:heading></footer>
                    </blockquote>
                </div>
            </div>

            <div class="w-full lg:p-8">
                <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                    <a href="{{ route('home') }}" class="z-20 flex flex-col items-center gap-2 font-medium lg:hidden" wire:navigate>
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-accent text-accent-foreground shadow-gold">
                            <x-app-logo-icon class="size-5 fill-current" />
                        </span>
                        <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                    </a>
                    {{ $slot }}
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>