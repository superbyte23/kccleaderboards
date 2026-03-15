<?php

use Livewire\Component;
use App\Models\Event;
use App\Models\Team;
use App\Models\Competition;
use Livewire\Attributes\Computed;
use Illuminate\Support\Collection;

new #[Layout('layouts.guest')] class extends Component {
    #[Locked]
    public $event;
    public string $timeframe = 'daily';
    public ?string $selectedTeamId = null;

    #[Computed]
    public function leaderboard(): Collection
    {
        return Team::query()
            ->where('teams.event_id', $this->event->id)
            ->leftJoin('results', 'teams.id', '=', 'results.team_id')
            ->leftJoin('competitions', function ($join) {
                $join->on('results.competition_id', '=', 'competitions.id')
                    ->whereNull('competitions.deleted_at');
            })
            ->select(
                'teams.id',
                'teams.name',
                'teams.color',
                'teams.avatar',
                'teams.represents',
                \DB::raw('COALESCE(SUM(CASE WHEN competitions.id IS NOT NULL THEN results.score ELSE 0 END), 0) as total_score'),
                \DB::raw('COUNT(DISTINCT CASE WHEN competitions.id IS NOT NULL THEN results.competition_id END) as competitions_participated')
            )
            ->groupBy('teams.id', 'teams.name', 'teams.color', 'teams.avatar', 'teams.represents')
            ->orderByDesc('total_score')
            ->orderBy('teams.name')
            ->get();
    }

    #[Computed]
    public function teamStats(): ?object
    {
        if (!$this->selectedTeamId) {
            return null;
        }

        $results = \App\Models\Result::query()
            ->where('team_id', $this->selectedTeamId)
            ->whereHas('competition', fn($q) => $q
                ->where('event_id', $this->event->id)
                ->whereNull('deleted_at')
            )
            ->with(['competition' => fn($q) => $q->whereNull('deleted_at')])
            ->get();

        $team         = \App\Models\Team::find($this->selectedTeamId);
        $total        = $results->sum('score');
        $competitions = $results->count();
        $avg          = $competitions > 0 ? round($total / $competitions) : 0;
        $highest      = $results->max('score') ?? 0;
        $lowest       = $results->min('score') ?? 0;

        $history = $results
            ->sortByDesc('score')
            ->map(function ($result) {
                $placement = \App\Models\Result::where('competition_id', $result->competition_id)
                    ->where('score', '>', $result->score)
                    ->count() + 1;

                return (object) [
                    'competition' => $result->competition->name,
                    'category'    => $result->competition->category,
                    'score'       => $result->score,
                    'placement'   => $placement,
                ];
            })
            ->values();

        $gold    = $history->where('placement', 1)->count();
        $silver  = $history->where('placement', 2)->count();
        $bronze  = $history->where('placement', 3)->count();
        $fourth  = $history->where('placement', 4)->count();
        $winRate = $competitions > 0 ? round(($gold / $competitions) * 100) : 0;

        return (object) compact(
            'team', 'total', 'competitions', 'avg', 'highest', 'lowest',
            'gold', 'silver', 'bronze', 'fourth', 'winRate', 'history'
        );
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
            ->whereNull('competitions.deleted_at')
            ->with([
                'results' => function ($q) {
                    $q->with('team')->orderByDesc('score');
                },
            ])
            ->get()
            ->map(function ($competition) {
                $sorted = $competition->results->sortByDesc('score')->values();
                return (object) [
                    'id'           => $competition->id,
                    'name'         => $competition->name,
                    'category'     => $competition->category,
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

    public function viewStats(string $teamId): void
    {
        $this->selectedTeamId = $teamId;
        unset($this->teamStats);
        $this->modal('team-stats')->show();
    }

    public function mount(Event $event): void
    {
        $this->event = Event::findOrFail($event->id);
    }
};
?>

<div class="flex flex-col lg:flex-row gap-8 items-start justify-center" wire:poll.60000ms>
    <style>

.pulse {  
}

.pulse::before {
    content: ""; 
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: #e74c3c;
    animation: ripple 1.8s infinite;
}

@keyframes ripple {
    0% {
        transform: scale(1);
        opacity: 0.8;
    }

    100% {
        transform: scale(2);
        opacity: 0;
    }
}

</style>
    <div class="w-full max-w-6xl p-5"> 
        <header class="flex md:flex-row justify-between items-start md:items-center gap-4">
            <div class="flex justify-between items-center gap-3">
                <div>
                    <h1 class="text-2xl mb-2 font-bold text-[#e5b64b]">{{ $event->name }}</h1>
                    <h1 class="text-xs font-bold">{{ $event->description }}</h1>
                </div>
            </div>
            <div class="flex items-center gap-3"> 
                <div wire:loading.delay>
                    <flux:icon.loading /> 
                </div>
                <flux:button variant="subtle" square x-data x-on:click="$flux.dark = ! $flux.dark">
                  <flux:icon.sun class="dark:hidden" />
                  <flux:icon.moon class="hidden dark:block" />
                </flux:button> 
                <flux:badge color="lime" classs="gap-2">
                Live
                <span class="relative ms-2 flex size-3">
                  <span class="absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                  <span class="relative inline-flex size-3 pulse rounded-full bg-red-500"></span>
                </span>
                </flux:badge>
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
                        <div
                            class="relative bg-[#3d3d3d] rounded-l-3xl rounded-r-full p-3 flex items-center gap-6 group hover:translate-x-2 transition-transform cursor-pointer">
                            <div class="absolute -top-2 -left-4 text-4xl">🥈</div>
                            <img src="{{ $second->avatar ? asset('uploads/' . $second->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($second->name) }}"
                                class="w-20 h-20 rounded-full bg-[#555]" alt="avatar">
                            <div>
                                <div class="flex items-baseline gap-2">
                                    <span
                                        class="text-5xl font-bold tracking-tighter text-white/90">{{ $second->total_score }}</span>
                                    <span class="text-sm text-gray-400 font-semibold">Points</span>
                                </div>
                                <p class="text-xl text-gray-400 font-mono mb-1">{{ $second->name }}</p>
                                <p class="text-xs font-bold text-gray-300">
                                    competition{{ $second->competitions_participated !== 1 ? 's' : '' }}
                                    <span class="mx-1 opacity-30">|</span> {{ $second->competitions_participated }}
                                </p>
                            </div>
                        </div>
                    @endif

                    @if ($first)
                        <div
                            class="relative bg-[#6b6141] rounded-l-3xl rounded-r-full p-6 flex items-center gap-6 group hover:translate-x-2 transition-transform cursor-pointer">
                            <div class="absolute -top-2 -left-4 text-5xl">🥇</div>
                            <img src="{{ $first->avatar ? asset('uploads/' . $first->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($first->name) }}"
                                class="w-24 h-24 rounded-full bg-[#837651]" alt="avatar">
                            <div>
                                <div class="flex items-baseline gap-2">
                                    <span
                                        class="text-5xl font-bold tracking-tighter text-white/90">{{ $first->total_score }}</span>
                                    <span class="text-sm text-gray-400 font-semibold">Points</span>
                                </div>
                                <p class="text-xl text-gray-400 font-mono mb-1">{{ $first->name }}</p>
                                <p class="text-xs font-bold text-gray-300">
                                    competition{{ $first->competitions_participated !== 1 ? 's' : '' }}
                                    <span class="mx-1 opacity-30">|</span> {{ $first->competitions_participated }}
                                </p>
                            </div>
                        </div>
                    @endif

                    @if ($third)
                        <div
                            class="relative bg-[#4a3a3a] rounded-l-3xl rounded-r-full p-3 flex items-center gap-6 group hover:translate-x-2 transition-transform cursor-pointer">
                            <div class="absolute -top-2 -left-4 text-3xl">🥉</div>
                            <img src="{{ $third->avatar ? asset('uploads/' . $third->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($third->name) }}"
                                class="w-20 h-20 rounded-full bg-[#555]" alt="avatar">
                            <div>
                                <div class="flex items-baseline gap-2">
                                    <span
                                        class="text-5xl font-bold tracking-tighter text-white/90">{{ $third->total_score }}</span>
                                    <span class="text-sm text-gray-400 font-semibold">Points</span>
                                </div>
                                <p class="text-xl text-gray-400 font-mono mb-1">{{ $third->name }}</p>
                                <p class="text-xs font-bold text-gray-300">
                                    competition{{ $third->competitions_participated !== 1 ? 's' : '' }}
                                    <span class="mx-1 opacity-30">|</span> {{ $third->competitions_participated }}
                                </p>
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
                    <div class="flex items-center p-3 gap-4 group rounded-xl border border-gray-100"
                        wire:key="{{ $team->id }}">
                        <flux:badge>{{ Number::ordinal($index + 1) }}</flux:badge>
                        <div class="flex-1 flex justify-between items-center transition-colors">
                            <div class="flex items-center gap-3">
                                <img src="{{ $team->avatar ? asset('uploads/' . $team->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($team->name) }}"
                                    class="w-8 h-8 rounded-lg" alt="user">
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold" style="color: {{ $team->color }}">{{ $team->name }}</span>
                                    <span class="text-[10px] uppercase">{{ $team->represents }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-3"> 
                                <div class="text-[#e5b64b] font-mono font-bold">
                                    {{ number_format($team->total_score) }} <span
                                        class="text-[10px] opacity-60">PTS</span></div>
                                <flux:button size="xs" variant="filled" icon="presentation-chart-line"
                                    wire:click="viewStats('{{ $team->id }}')" wire:loading.attr="disabled"
                                    wire:click.target="viewStats('{{ $team->id }}')">Stats</flux:button>
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
                    <p class="text-sm">No competition results recorded yet.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border-separate" style="border-spacing: 0 6px;">
                        <thead>
                            <tr>
                                <th class="text-left text-[10px] font-bold uppercase tracking-w12est pb-2 pl-3">
                                    Competition</th>
                                <th class="text-left text-[10px] font-bold uppercase tracking-w12est pb-2 pl-3">Category
                                </th>
                                <th
                                    class="text-center text-[10px] font-bold uppercase tracking-widest text-yellow-500 pb-2">
                                    1st</th>
                                <th
                                    class="text-center text-[10px] font-bold uppercase tracking-widest text-gray-400 pb-2">
                                    2nd</th>
                                <th
                                    class="text-center text-[10px] font-bold uppercase tracking-widest text-amber-700 pb-2">
                                    3rd</th>
                                <th class="text-center text-[10px] font-bold uppercase tracking-w12est pb-2">4th</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->gameSummary as $comp)
                                <tr class="group hover:bg-gray-200 hover:text-[#000]">
                                    <td
                                        class="transition-colors rounded-l-xl px-4 py-3 font-semibold">
                                        {{ $comp->name }}
                                    </td>
                                    <td class="transition-colors text-center px-3 py-3">
                                        <span
                                            class="text-[10px] font-bold uppercase tracking-wider bg-white/5 px-2 py-1 rounded-full">
                                            {{ $comp->category }}
                                        </span>
                                    </td>

                                    {{-- 1st --}}
                                    <td class="transition-colors px-3 py-3 text-center">
                                        @if ($comp->first)
                                            <div class="flex flex-col items-center gap-1">
                                                <img src="{{ $comp->first->avatar ? asset('uploads/' . $comp->first->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($comp->first->name) }}"
                                                    class="w-7 h-7 rounded-full ring-2 ring-yellow-500/60"
                                                    alt="{{ $comp->first->name }}">
                                                <span
                                                    class="text-[11px] font-bold text-yellow-400 leading-tight">{{ $comp->first->name }}</span>
                                                <span
                                                    class="text-[12px] font-mono">{{ number_format($comp->first_score) }}</span>
                                            </div>
                                        @else
                                            <span class="text-gray-700 text-xs">—</span>
                                        @endif
                                    </td>

                                    {{-- 2nd --}}
                                    <td class="transition-colors px-3 py-3 text-center">
                                        @if ($comp->second)
                                            <div class="flex flex-col items-center gap-1">
                                                <img src="{{ $comp->second->avatar ? asset('uploads/' . $comp->second->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($comp->second->name) }}"
                                                    class="w-7 h-7 rounded-full ring-2 ring-gray-400/50"
                                                    alt="{{ $comp->second->name }}">
                                                <span
                                                    class="text-[11px] font-bold text-gray-400 leading-tight">{{ $comp->second->name }}</span>
                                                <span
                                                    class="text-[12px] font-mono">{{ number_format($comp->second_score) }}</span>
                                            </div>
                                        @else
                                            <span class="text-gray-700 text-xs">—</span>
                                        @endif
                                    </td>

                                    {{-- 3rd --}}
                                    <td class="transition-colors px-3 py-3 text-center">
                                        @if ($comp->third)
                                            <div class="flex flex-col items-center gap-1">
                                                <img src="{{ $comp->third->avatar ? asset('uploads/' . $comp->third->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($comp->third->name) }}"
                                                    class="w-7 h-7 rounded-full ring-2 ring-amber-700/50"
                                                    alt="{{ $comp->third->name }}">
                                                <span
                                                    class="text-[11px] font-bold text-amber-600 leading-tight">{{ $comp->third->name }}</span>
                                                <span
                                                    class="text-[12px] font-mono">{{ number_format($comp->third_score) }}</span>
                                            </div>
                                        @else
                                            <span class="text-gray-700 text-xs">—</span>
                                        @endif
                                    </td>

                                    {{-- 4th --}}
                                    <td class="transition-colors rounded-r-xl px-3 py-3 text-center">
                                        @if ($comp->fourth)
                                            <div class="flex flex-col items-center gap-1">
                                                <img src="{{ $comp->fourth->avatar ? asset('uploads/' . $comp->fourth->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($comp->fourth->name) }}"
                                                    class="w-7 h-7 rounded-full ring-2 ring-gray-700/50"
                                                    alt="{{ $comp->fourth->name }}">
                                                <span
                                                    class="text-[11px] font-bold leading-tight">{{ $comp->fourth->name }}</span>
                                                <span
                                                    class="text-[12px] font-mono">{{ number_format($comp->fourth_score) }}</span>
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
                    {{ $this->gameSummary->count() }} competition{{ $this->gameSummary->count() !== 1 ? 's' : '' }}
                    total
                </span>
                <flux:modal.close>
                    <flux:button variant="primary" size="sm">Close</flux:button>
                </flux:modal.close>
            </div>

        </div>
    </flux:modal>

    {{-- ─── Team Stats Modal ────────────────────────────────────────────── --}}
    <flux:modal name="team-stats" class="w-full max-w-lg">
        @if ($this->teamStats)
            @php $s = $this->teamStats; @endphp

            {{-- Header --}}
            <div class="flex items-center gap-4 pb-5 border-b border-zinc-200 dark:border-zinc-700">
                <img src="{{ $s->team->avatar ? asset('uploads/' . $s->team->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($s->team->name) }}"
                    class="w-12 h-12 rounded-full" alt="{{ $s->team->name }}">
                <div class="flex-1 min-w-0">
                    <div class="text-lg font-bold truncate">{{ $s->team->name }}</div>
                    @if ($s->team->represents)
                        <div class="text-xs opacity-50 mt-0.5">{{ $s->team->represents }}</div>
                    @endif
                </div> 
            </div>

            {{-- Body --}}
            <div class="pt-5 space-y-5">

                {{-- Performance Overview --}}
                <p class="text-xs uppercase tracking-widest opacity-40 font-semibold">Performance Overview</p>
                <div class="grid grid-cols-4 gap-2">
                    @foreach ([
                        ['Total Points',  number_format($s->total),  true],
                        ['Competitions',  $s->competitions,           false],
                        ['Avg Score',     number_format($s->avg),     false],
                        ['Highest',       number_format($s->highest), false],
                    ] as [$label, $value, $accent])
                        <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-3 text-center">
                            <div class="text-lg font-bold font-mono {{ $accent ? 'text-[#e5b64b]' : '' }}">
                                {{ $value }}
                            </div>
                            <div class="text-[10px] opacity-40 uppercase tracking-wide mt-1">{{ $label }}</div>
                        </div>
                    @endforeach
                </div>

                {{-- Placement Distribution --}}
                <p class="text-xs uppercase tracking-widest opacity-40 font-semibold">Placement Distribution</p>
                <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-4 grid grid-cols-4 gap-2 text-center">
                    <div>
                        <div class="text-2xl font-bold font-mono text-yellow-400">{{ $s->gold }}</div>
                        <div class="text-[10px] opacity-50 mt-1">🥇 1st</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold font-mono text-zinc-400">{{ $s->silver }}</div>
                        <div class="text-[10px] opacity-50 mt-1">🥈 2nd</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold font-mono text-amber-600">{{ $s->bronze }}</div>
                        <div class="text-[10px] opacity-50 mt-1">🥉 3rd</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold font-mono opacity-40">{{ $s->fourth }}</div>
                        <div class="text-[10px] opacity-50 mt-1">4th</div>
                    </div>
                </div>

                <div class="h-px bg-zinc-200 dark:bg-zinc-700"></div>

                {{-- Win Rate --}}
                <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-4 space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm opacity-60">Win Rate</span>
                        <span class="text-lg font-bold font-mono text-[#e5b64b]">{{ $s->winRate }}%</span>
                    </div>
                    <div class="w-full h-1.5 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                        <div class="h-full bg-[#e5b64b] rounded-full transition-all" style="width: {{ $s->winRate }}%"></div>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex items-center gap-1.5 text-xs opacity-50">
                            <div class="w-1.5 h-1.5 rounded-full bg-yellow-400"></div>
                            {{ $s->gold }} Wins
                        </div>
                        <div class="flex items-center gap-1.5 text-xs opacity-50">
                            <div class="w-1.5 h-1.5 rounded-full bg-zinc-400"></div>
                            {{ $s->competitions - $s->gold }} Non-wins
                        </div>
                        <div class="flex items-center gap-1.5 text-xs opacity-50">
                            <div class="w-1.5 h-1.5 rounded-full bg-zinc-600"></div>
                            {{ $s->competitions }} Total
                        </div>
                    </div>
                </div>

                <div class="h-px bg-zinc-200 dark:bg-zinc-700"></div>

                {{-- Competition History --}}
                <p class="text-xs uppercase tracking-widest opacity-40 font-semibold">Competition History</p>
                <div class="space-y-1.5">
                    @forelse ($s->history as $item)
                        @php
                            $placementLabel = match ($item->placement) {
                                1 => '1st',
                                2 => '2nd',
                                3 => '3rd',
                                default => $item->placement . 'th',
                            };
                            $borderColor = match ($item->placement) {
                                1 => 'border-yellow-500',
                                2 => 'border-zinc-400',
                                3 => 'border-amber-700',
                                default => 'border-zinc-700',
                            };
                            $badgeClass = match ($item->placement) {
                                1 => 'text-yellow-400 bg-yellow-900/30 border-yellow-700/40',
                                2 => 'text-zinc-300 bg-zinc-500/10 border-zinc-500/20',
                                3 => 'text-amber-600 bg-amber-900/20 border-amber-700/30',
                                default => 'text-zinc-500 bg-zinc-500/10 border-zinc-500/10',
                            };
                        @endphp
                        <div class="flex items-center gap-3 px-3 py-2.5 rounded-lg border-l-2 {{ $borderColor }} hover:bg-zinc-500/10 transition-colors">
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-semibold truncate">{{ $item->competition }}</div>
                                @if ($item->category)
                                    <div class="text-[10px] opacity-40 uppercase tracking-wide mt-0.5">{{ $item->category }}</div>
                                @endif
                            </div>
                            <div class="text-sm font-bold text-[#e5b64b] font-mono min-w-[56px] text-right shrink-0">
                                {{ number_format($item->score) }} pts
                            </div>
                            <span class="text-xs font-bold px-2 py-0.5 rounded border min-w-[34px] text-center {{ $badgeClass }}">
                                {{ $placementLabel }}
                            </span>
                        </div>
                    @empty
                        <p class="text-sm opacity-40 text-center py-4">No competitions yet.</p>
                    @endforelse
                </div>

            </div>

            {{-- Footer --}}
            <div class="flex justify-end pt-4 border-t border-zinc-200 dark:border-zinc-700 mt-4">
                <flux:modal.close>
                    <flux:button variant="primary" size="sm">Close</flux:button>
                </flux:modal.close>
            </div>

        @endif
    </flux:modal>
</div>
