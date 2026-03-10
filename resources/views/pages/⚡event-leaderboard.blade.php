<?php

use Livewire\Component;
use App\Models\Event;
use App\Models\Team;
use App\Models\Competition;
use Livewire\Attributes\Computed;
use Illuminate\Support\Collection;

new #[Layout('layouts.guest')] 
class extends Component {
    #[Locked]
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

    #[Computed]
    public function gameSummary(): Collection
    {
        return Competition::query()
            ->where('competitions.event_id', $this->event->id)
            ->with(['results' => function ($q) {
                $q->with('team')->orderByDesc('score');
            }])
            ->get()
            ->map(function ($competition) {
                $sorted = $competition->results->sortByDesc('score')->values();
                return (object) [
                    'id'       => $competition->id,
                    'name'     => $competition->name,
                    'category' => $competition->category,
                    'first'        => $sorted->get(0)?->team,
                    'second'       => $sorted->get(1)?->team,
                    'third'        => $sorted->get(2)?->team,
                    'fourth'       => $sorted->get(3)?->team,
                    'first_score'  => $sorted->get(0)?->score,
                    'second_score' => $sorted->get(1)?->score,
                    'third_score'  => $sorted->get(2)?->score,
                    'fourth_score' => $sorted->get(3)?->score,
                ];
            });
    }

    public function setTimeframe(string $timeframe): void
    {
        $this->timeframe = $timeframe;
    }

    public function openSummary(): void
    {
        unset($this->gameSummary);
        $this->modal('game-summary')->show();
        $this->dispatch('open-modal', name: 'game-summary');
    }


    public function mount(Event $event): void
    {
        $this->event = Event::findOrFail($event->id);
    }
};
?>

<div class="flex flex-col lg:flex-row gap-8 items-start justify-center" wire:poll.6000ms>
  <div class="w-full max-w-4xl p-5 shadow-xl">
    
    <header class="flex md:flex-row justify-between items-start md:items-center gap-4">
      <div class="flex justify-between items-center gap-3">
        <div> 
            <h1 class="text-xl font-bold text-[#e5b64b]">{{ $event->name }}</h1>
            <h1 class="text-xs font-bold">{{ $event->description }}</h1>
        </div>
      </div>
      <div class="flex items-center gap-3">  
          <flux:button 
    variant="subtle" 
    square 
    x-data 
    @click="
        let isDark = document.documentElement.classList.contains('dark');
        let newTheme = isDark ? 'light' : 'dark';
        
        // 1. Update the DOM immediately
        if (newTheme === 'dark') {
            document.documentElement.classList.add('dark');
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.classList.remove('dark');
            document.documentElement.setAttribute('data-theme', 'light');
        }

        // 2. Sync all storage keys so it persists on refresh
        localStorage.setItem('flux.appearance', newTheme);
        localStorage.setItem('theme', newTheme);
        localStorage.setItem('appearance', newTheme);
        localStorage.setItem('mary-theme', newTheme);

        // 3. Dispatch an event if other components need to know
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: newTheme }));
    "
>
    <flux:icon.sun class="dark:hidden" />
    <flux:icon.moon class="hidden dark:block" />
</flux:button> 
          <flux:badge color="red">Live</flux:badge>
        </div>  
    </header>
    
    
    <flux:button class="my-4" icon="trophy" wire:click.="openSummary">Game Summary</flux:button> 

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
              <img src="{{ $second->avatar ? asset('uploads/' . $second->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($second->name) }}" class="w-20 h-20 rounded-full bg-[#555]" alt="avatar"> 
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
              <img src="{{ $first->avatar ? asset('uploads/' . $first->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($first->name) }}" class="w-24 h-24 rounded-full bg-[#837651]" alt="avatar">
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
              <img src="{{ $third->avatar ? asset('uploads/' . $third->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($third->name) }}" class="w-20 h-20 rounded-full bg-[#555]" alt="avatar"> 
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
            <h3 class="text-sm font-bold uppercase tracking-widest">Official Ranking</h3>
            <div class="h-px flex-1 bg-white/5 ml-4"></div>
        </div>

        @forelse ($this->leaderboard as $index => $team)
            <div class="flex items-center gap-4 group">
              <span class="font-mono font-bold w-6 text-sm">{{ $index + 1 }}</span>
              <div class="flex-1 rounded-xl p-3 flex justify-between items-center border border-white/5 transition-colors">
                <div class="flex items-center gap-3">
                  <img src="{{ $team->avatar ? asset('uploads/' . $team->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($team->name) }}" class="w-8 h-8 rounded-lg" alt="user">
                  <div class="flex flex-col">
                    <span class="text-sm font-bold">{{ $team->name }}</span>
                    <span class="text-[10px] uppercase">{{ $team->represents }}</span>
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
                <p class="text-gray">No data available for this event yet.</p>
            </div>
        @endforelse
      </div> 
    </div>
  </div>


  {{-- ─── Game Summary Modal ────────────────────────────────────────────── --}}
  <flux:modal name="game-summary" class="w-full max-w-3xl">
    <div class="flex flex-col gap-6">

      {{-- Modal Header --}}
      <div class="flex items-center gap-3">
        <span class="text-5xl">🏆</span>
        <div>
          <flux:heading size="lg" class="text-[#e5b64b]">Game Summary</flux:heading>
          <flux:subheading>Winners per competition — {{ $event->name }}</flux:subheading>
        </div>
      </div>

      {{-- Table --}}
      @if ($this->gameSummary->isEmpty())
        <div class="text-center py-16">
          <p class= text-sm">No competition results recorded yet.</p>
        </div>
      @else
        <div class="overflow-x-auto">
          <table class="w-full text-sm border-separate" style="border-spacing: 0 6px;">
            <thead>
              <tr>
                <th class="text-left text-[10px] font-bold uppercase tracking-w12est pb-2 pl-3">Competition</th>
                <th class="text-left text-[10px] font-bold uppercase tracking-w12est pb-2 pl-3">Category</th>
                <th class="text-center text-[10px] font-bold uppercase tracking-widest text-yellow-500 pb-2">1st</th>
                <th class="text-center text-[10px] font-bold uppercase tracking-widest text-gray-400 pb-2">2nd</th>
                <th class="text-center text-[10px] font-bold uppercase tracking-widest text-amber-700 pb-2">3rd</th>
                <th class="text-center text-[10px] font-bold uppercase tracking-w12est pb-2">4th</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($this->gameSummary as $comp)
                <tr class="group hover:bg-gray-200 hover:text-[#000]">
                  <td class="transition-colors rounded-l-xl px-4 py-3 font-semibold whitespace-nowrap">
                    {{ $comp->name }}
                  </td>
                  <td class="transition-colors px-3 py-3">
                    <span class="text-[10px] font-bold uppercase tracking-wider bg-white/5 px-2 py-1 rounded-full">
                      {{ $comp->category }}
                    </span>
                  </td>

                  {{-- 1st --}}
                  <td class="transition-colors px-3 py-3 text-center">
                    @if ($comp->first)
                      <div class="flex flex-col items-center gap-1">
                        <img src="{{ $comp->first->avatar ? asset('uploads/' . $comp->first->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($comp->first->name) }}" class="w-7 h-7 rounded-full ring-2 ring-yellow-500/60" alt="{{ $comp->first->name }}">
                        <span class="text-[11px] font-bold text-yellow-400 leading-tight">{{ $comp->first->name }}</span>
                        <span class="text-[12px] font-mono">{{ number_format($comp->first_score) }}</span>
                      </div>
                    @else
                      <span class="text-gray-700 text-xs">—</span>
                    @endif
                  </td>

                  {{-- 2nd --}}
                  <td class="transition-colors px-3 py-3 text-center">
                    @if ($comp->second)
                      <div class="flex flex-col items-center gap-1">
                        <img src="{{ $comp->second->avatar ? asset('uploads/' . $comp->second->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($comp->second->name) }}" class="w-7 h-7 rounded-full ring-2 ring-gray-400/50" alt="{{ $comp->second->name }}">
                        <span class="text-[11px] font-bold text-gray-400 leading-tight">{{ $comp->second->name }}</span>
                        <span class="text-[12px] font-mono">{{ number_format($comp->second_score) }}</span>
                      </div>
                    @else
                      <span class="text-gray-700 text-xs">—</span>
                    @endif
                  </td>

                  {{-- 3rd --}}
                  <td class="transition-colors px-3 py-3 text-center">
                    @if ($comp->third)
                      <div class="flex flex-col items-center gap-1">
                        <img src="{{ $comp->third->avatar ? asset('uploads/' . $comp->third->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($comp->third->name) }}" class="w-7 h-7 rounded-full ring-2 ring-amber-700/50" alt="{{ $comp->third->name }}">
                        <span class="text-[11px] font-bold text-amber-600 leading-tight">{{ $comp->third->name }}</span>
                        <span class="text-[12px] font-mono">{{ number_format($comp->third_score) }}</span>
                      </div>
                    @else
                      <span class="text-gray-700 text-xs">—</span>
                    @endif
                  </td>

                  {{-- 4th --}}
                  <td class="transition-colors rounded-r-xl px-3 py-3 text-center">
                    @if ($comp->fourth)
                      <div class="flex flex-col items-center gap-1">
                        <img src="{{ $comp->fourth->avatar ? asset('uploads/' . $comp->fourth->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($comp->fourth->name) }}" class="w-7 h-7 rounded-full ring-2 ring-gray-700/50" alt="{{ $comp->fourth->name }}">
                        <span class="text-[11px] font-bold leading-tight">{{ $comp->fourth->name }}</span>
                        <span class="text-[12px] font-mono">{{ number_format($comp->fourth_score) }}</span>
                      </div>
                    @else
                      <span class="text-gray-700 text-xs">—</span>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif

      {{-- Footer --}}
      <div class="flex justify-between items-center pt-2 border-t border-white/5">
        <span class="te12-xs">
          {{ $this->gameSummary->count() }} competition{{ $this->gameSummary->count() !== 1 ? 's' : '' }} total
        </span>
        <flux:modal.close>
          <flux:button variant="primary" size="sm">Close</flux:button>
        </flux:modal.close>
      </div>

    </div>
  </flux:modal>

  <script>
    const theme = localStorage.getItem('flux.appearance') || 'light';
    if (theme === 'dark') {
        document.documentElement.classList.add('dark');
        document.documentElement.setAttribute('data-theme', 'dark');
    }
</script>

</div>