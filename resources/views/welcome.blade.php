<x-layouts::guest>
    <div class="min-h-screen  font-sans p-6 md:p-12">
      <nav class="flex justify-between items-center mb-16">
        <div class="flex items-center gap-3">
          <h1 class="text-xl font-bold tracking-tight">KCC Inc. <span class="text-yellow-500">INTRAMURALS2026</span></h1>
        </div>
        <flux:button variant="subtle" square x-data x-on:click="$flux.dark = ! $flux.dark">
          <flux:icon.sun class="dark:hidden" />
          <flux:icon.moon class="hidden dark:block" />
        </flux:button> 
      </nav>
      <header class="max-w-4xl mb-12">
        <h2 class="text-4xl md:text-6xl font-extrabold mb-4 leading-tight">
          A Synodal Journey Together <br /> <span class="text-gray-500">in Love and Faith</span>
        </h2>
        {{-- 
        <p class="text-gray-400 text-lg">Select an active tournament below to view the global rankings and your current standing.</p>
        --}}
      </header>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($events as $event)
        <div
          class="group relative rounded-[2rem] p-1 border border-transparent hover:border-yellow-500/50 transition-all cursor-pointer">
          <div class="rounded-[1.8rem] p-6">
            <div class="flex justify-between items-start mb-8">
              <span
                class="bg-yellow-500/10 text-yellow-500 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-widest">
              {{ $event->event_date > now() ? 'Upcoming' : 'Live Now' }}
              </span>
              <span class="text-3xl">🏆</span>
            </div>
            <h3 class="text-2xl font-bold mb-2">{{ $event->name }}</h3>
            <p class="text-gray-400 text-sm mb-6">{{ $event->description }}</p>
            {{-- Dynamically inject the Event UUID into the URL --}}
            <flux:button wire:navigate href="/leaderboards/{{ $event->id }}" variant="outline"
              class="w-full group-hover:bg-yellow-500 group-hover:text-black py-4 rounded-2xl font-bold transition-colors flex justify-center items-center gap-2">
              View Leaderboard
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13 7l5 5m0 0l-5 5m5-5H6" />
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
    </div>
  </x-layouts::guest>