<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ __('Welcome') }} - {{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body>
        <script src="https://cdn.tailwindcss.com"></script>

<div class="min-h-screen bg-[#121212] text-white font-sans p-6 md:p-12">
  <nav class="flex justify-between items-center mb-16">
    <div class="flex items-center gap-3">
      {{-- <div class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center border border-yellow-500/50">
        <img src="https://api.dicebear.com/7.x/bottts/svg?seed=Lucky" class="w-6 h-6" alt="logo">
      </div> --}}
      <h1 class="text-xl font-bold tracking-tight">KCC Inc. <span class="text-yellow-500">INTRAMURALS 2026</span></h1>
    </div> 
  </nav>

  <header class="max-w-4xl mb-12">
    <h2 class="text-4xl md:text-6xl font-extrabold mb-4 leading-tight">
      A Synodal Journey Together <br/> <span class="text-gray-500">in Love and Faith</span>
    </h2>
    {{-- <p class="text-gray-400 text-lg">Select an active tournament below to view the global rankings and your current standing.</p> --}}
  </header>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($events as $event)
        <div class="group relative bg-[#1f1f1f] rounded-[2rem] p-1 border border-transparent hover:border-yellow-500/50 transition-all cursor-pointer">
          <div class="bg-gradient-to-br from-[#2a2a2a] to-[#1a1a1a] rounded-[1.8rem] p-6">
            <div class="flex justify-between items-start mb-8">
              <span class="bg-yellow-500/10 text-yellow-500 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-widest">
                {{ $event->event_date > now() ? 'Upcoming' : 'Live Now' }}
              </span>
              <span class="text-3xl">🏆</span>
            </div>
            
            <h3 class="text-2xl font-bold mb-2">{{ $event->name }}</h3>
            <p class="text-gray-400 text-sm mb-6">{{ $event->description }}</p>
            
            {{-- Dynamically inject the Event UUID into the URL --}}
            <flux:button wire:navigate href="/leaderboards/{{ $event->id }}" variant="outline" class="w-full group-hover:bg-yellow-500 group-hover:text-black py-4 rounded-2xl font-bold transition-colors flex justify-center items-center gap-2">
              View Leaderboard
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
              </svg>
            </flux:button>
          </div>
        </div>
    @empty
        <div class="col-span-full text-center py-10 text-gray-500">
            No events found.
        </div>
    @endforelse
</div>

  <footer class="mt-20 pt-8 border-t border-gray-800 flex flex-wrap gap-3 justify-center opacity-50 text-sm">
    <div class="flex text-center items-center gap-2"><span>•</span>Kabankalan Catholic College Inc.</div>
    <div class="flex text-center items-center gap-2"><span>•</span> KCC College Department - Supreme Student Affairs Organization</div>
    <div class="flex text-center items-center gap-2"><span>•</span> Superbyte Team</div>
  </footer>
</div>
    @fluxScripts 
    </body>
</html>
