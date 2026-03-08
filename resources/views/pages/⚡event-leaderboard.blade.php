<?php

use Livewire\Component;
use App\Models\Event;
use App\Models\Team;
use Livewire\Attributes\Computed;
use Illuminate\Support\Collection;

new #[Layout('layouts.guest')] class extends Component {
    public $event;
    public string $timeframe = 'daily';

    #[Computed]
    public function leaderboard(): Collection
    {
        return Team::query()
            ->where('event_id', $this->event->id)
            ->leftJoin('results', 'teams.id', '=', 'results.team_id')
            ->select(
                'teams.id',
                'teams.name',
                'teams.color',
                'teams.avatar',
                'teams.represents',
                \DB::raw('COALESCE(SUM(results.score), 0) as total_score'),
                \DB::raw('COUNT(DISTINCT results.competition_id) as competitions_participated')
            )
            ->groupBy('teams.id', 'teams.name', 'teams.color', 'teams.avatar', 'teams.represents')
            ->orderByDesc('total_score')
            ->orderBy('teams.name')
            ->get();
    }

    #[Computed]
    public function topThree(): Collection
    {
        return $this->leaderboard()->take(3);
    }

    #[Computed]
    public function remainingTeams(): Collection
    {
        return $this->leaderboard()->slice(3);
    }

    public function setTimeframe(string $timeframe): void
    {
        $this->timeframe = $timeframe;
    }

    public function mount(Event $event): void
    {
        $this->event = Event::findOrFail($event->id);
    }
};
?>

<div class="flex flex-col lg:flex-row gap-8 items-start justify-center">
  <div class="w-full max-w-4xl bg-[#121212] rounded-[2.5rem] shadow-2xl">
    
    <header class="flex md:flex-row justify-between items-start md:items-center mb-12 gap-4">
      <div class="flex justify-between items-center gap-3">
        {{-- <div class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center border border-yellow-500/50">
           <img src="https://api.dicebear.com/7.x/bottts/svg?seed=Lucky" class="w-6 h-6" alt="logo">
        </div> --}}
        <div> 
            <h1 class="text-xl font-bold text-[#e5b64b]">{{ $event->name }}</h1>
            <h1 class="text-xs font-bold text-[#fafafa]">{{ $event->description }}</h1>
        </div>
        <flux:badge color="lime" te>Live</flux:badge> 
      </div>
      <div class="flex items-center gap-3"> 
          <flux:button 
              variant="primary" 
              wire:navigate 
              href="/" 
              icon="arrow-left"
          >
              <span class="hidden sm:inline">Back to Homepage</span>
          </flux:button>
        </div>  
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
      <div class="space-y-4">
        @if ($this->topThree->isNotEmpty())
          @php
            $first = $this->topThree->get(0);
            $second = $this->topThree->get(1);
            $third = $this->topThree->get(2);
          @endphp

          @if ($second)
            <div class="relative bg-[#3d3d3d] rounded-l-3xl rounded-r-full p-3 flex items-center gap-6 group hover:translate-x-2 transition-transform cursor-pointer">
              <div class="absolute -top-2 -left-4 text-4xl">🥈</div>
              
              <img src="{{ $second->avatar ? asset('storage/' . $second->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($second->name) }}" class="w-20 h-20 rounded-full bg-[#555]" alt="avatar"> 
              <div>
                <div class="flex items-baseline gap-2">
                  <span class="text-5xl font-bold tracking-tighter text-white/90">{{ $second->total_score }}</span>
                  <span class="text-sm text-gray-400 font-semibold">Points</span>
                </div>
                <p class="text-xl text-gray-400 font-mono mb-1">{{ $second->name }}</p>
                <p class="text-xs font-bold text-gray-300">competition{{ $second->competitions_participated !== 1 ? 's' : '' }} 
                  <span class="mx-1 opacity-30">|</span> {{ $second->competitions_participated }}</p>
              </div>
            </div>
          @endif

          @if ($first) 
            <div class="relative bg-[#6b6141] rounded-l-3xl rounded-r-full p-6 flex items-center gap-6 group hover:translate-x-2 transition-transform cursor-pointer">
              <div class="absolute -top-2 -left-4 text-5xl">🥇</div>
              
              <img src="{{ $first->avatar ? asset('storage/' . $first->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($first->name) }}" class="w-24 h-24 rounded-full bg-[#837651]" alt="avatar">
              <div>
                <div class="flex items-baseline gap-2">
                  <span class="text-5xl font-bold tracking-tighter text-white/90">{{ $first->total_score }}</span>
                  <span class="text-sm text-gray-400 font-semibold">Points</span>
                </div>
                <p class="text-xl text-gray-400 font-mono mb-1">{{ $first->name }}</p>
                <p class="text-xs font-bold text-gray-300">competition{{ $first->competitions_participated !== 1 ? 's' : '' }} 
                  <span class="mx-1 opacity-30">|</span> {{ $first->competitions_participated }}</p>
              </div>
            </div>
          @endif

          @if ($third) 
            <div class="relative bg-[#4a3a3a] rounded-l-3xl rounded-r-full p-3 flex items-center gap-6 group hover:translate-x-2 transition-transform cursor-pointer">
              <div class="absolute -top-2 -left-4 text-3xl">🥉</div>
              
              <img src="{{ $third->avatar ? asset('storage/' . $third->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($third->name) }}" class="w-20 h-20 rounded-full bg-[#555]" alt="avatar"> 
              <div>
                <div class="flex items-baseline gap-2">
                  <span class="text-5xl font-bold tracking-tighter text-white/90">{{ $third->total_score }}</span>
                  <span class="text-sm text-gray-400 font-semibold">Points</span>
                </div>
                <p class="text-xl text-gray-400 font-mono mb-1">{{ $third->name }}</p>
                <p class="text-xs font-bold text-gray-300">competition{{ $third->competitions_participated !== 1 ? 's' : '' }} 
                  <span class="mx-1 opacity-30">|</span> {{ $third->competitions_participated }}</p>
              </div>
            </div>
          @endif
        @endif 
      </div>

      <div class="space-y-3">
        <div class="flex items-center justify-between mb-4 px-2">
            <h3 class="text-sm font-bold uppercase tracking-widest text-gray-500">Official Ranking</h3>
            <div class="h-px flex-1 bg-white/5 ml-4"></div>
        </div>

        @forelse ($this->leaderboard as $index => $team)
            <div class="flex items-center gap-4 group">
              <span class="text-gray-600 font-mono font-bold w-6 text-sm">{{ $index + 1 }}</span>
              <div class="flex-1 bg-[#1a1a1a] hover:bg-[#222] rounded-xl p-3 flex justify-between items-center border border-white/5 transition-colors">
                <div class="flex items-center gap-3">
                  <img src="{{ $team->avatar ? asset('storage/' . $team->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($team->name) }}" class="w-8 h-8 rounded-lg" alt="user">
                  <div class="flex flex-col">
                    <span class="text-sm font-bold text-gray-200">{{ $team->name }}</span>
                    <span class="text-[10px] text-gray-500 uppercase">{{ $team->represents }}</span>
                  </div>
                </div>
                
                <div class="flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full" style="background-color: {{ $team->color }}"></div>
                    <div class="text-[#e5b64b] font-mono font-bold text-sm">{{ number_format($team->total_score) }} <span class="text-[10px] opacity-60">PTS</span></div>
                </div>
              </div>
            </div>
        @empty
            <div class="text-center py-12">
                <p class="text-gray-500">No data available for this event yet.</p>
            </div>
        @endforelse
      </div> 
    </div>
  </div>
</div>