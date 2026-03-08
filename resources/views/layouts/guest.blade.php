<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
 
        @livewireStyles
        @fluxAppearance
    </head>
    <body class="min-h-screen bg-[#121212] text-white font-sans p-6 md:p-12">
        {{ $slot }} 
        @livewireScripts
        @fluxScripts

        <footer class="mt-20 border-t border-gray-800 flex flex-wrap gap-3 justify-center opacity-50 text-sm">
            <div class="flex text-center items-center gap-2"><span>•</span>Kabankalan Catholic College Inc.</div>
            <div class="flex text-center items-center gap-2"><span>•</span> KCC College Department - Supreme Student Affairs Organization</div>
            <div class="flex text-center items-center gap-2"><span>•</span> Superbyte Team</div>
        </footer>
    </body>
</html>