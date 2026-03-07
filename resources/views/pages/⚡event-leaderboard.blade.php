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
                \DB::raw('COALESCE(SUM(results.score), 0) as total_score'),
                \DB::raw('COUNT(DISTINCT results.competition_id) as competitions_participated')
            )
            ->groupBy('teams.id', 'teams.name', 'teams.color')
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
              
              <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Felix" class="w-20 h-20 rounded-full bg-[#555]" alt="avatar"> 
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
              
              <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Aneka" class="w-24 h-24 rounded-full bg-[#837651]" alt="avatar">
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
              
              <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Felix" class="w-20 h-20 rounded-full bg-[#555]" alt="avatar"> 
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
        <h3 class="text-xl font-medium mb-6 text-gray-200 text-center">Official Ranking</h3>
        @if ($this->leaderboard->isNotEmpty())
          @foreach ($this->leaderboard as $index => $team)
            <div class="flex items-center gap-3">
              <span class="text-gray-500 font-bold w-6 text-center">{{ Number::ordinal($index + 1) }}</span>
              <div class="flex-1 bg-[#222] rounded-2xl p-3 flex justify-between items-center border border-white/5">
                <div class="flex items-center gap-3">
                  <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Leo" class="w-10 h-10 rounded-xl bg-blue-400" alt="user">
                  <div class="flex items-center gap-2 px-3 py-1 rounded-lg border border-white/10" style="background-color: {{ $team->color }}">
                    <span class="text-[10px] font-mono text-white-400">{{ $team->name }}</span>
                  </div>
                  {{-- <div class="flex items-center gap-2 bg-[#1a1a1a] px-3 py-1 rounded-lg border border-white/10">
                    <span class="text-[10px] font-mono text-gray-400">Bx4fWW...pUMDrc</span>
                  </div> --}}
                </div>
                <div class="text-green-500 font-bold text-xs">{{  $team->total_score }} PTS</div>
              </div>
            </div>
          @endforeach
        @else
            @if ($this->leaderboard->isEmpty())
                <div class="text-center py-16">
                    <p class="text-slate-400 text-xl">No teams found for this event.</p>
                </div>
            @endif
        @endif 
      </div> 
    </div>
  </div>
</div>