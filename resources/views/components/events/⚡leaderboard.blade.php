<?php

use Livewire\Component;

use App\Models\Event;
use App\Models\Team;
use App\Models\Result;
use App\Models\Competition;
use Illuminate\Support\Facades\DB; 
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Illuminate\Support\Collection;

new class extends Component
{
    public Event $event;

    // This tells the component to re-render whenever 'refresh-leaderboard' is fired
    #[On('refresh-leaderboard')] // [!code ++]
    public function refresh(): void {} // [!code ++]

    #[Computed]
    public function leaderboard(): Collection
    {
        $rows = Team::query()
            ->where('event_id', $this->event->id)
            ->withCount([
                'results as total_score' => fn($query) => $query->select(\DB::raw('COALESCE(SUM(score), 0)'))
            ])
            ->leftJoin('results', 'teams.id', '=', 'results.team_id')
            ->select(
                'teams.id',
                'teams.name',
                'teams.color',
                'teams.avatar',
                \DB::raw('COALESCE(SUM(results.score), 0) as total_score'),
                \DB::raw('COUNT(DISTINCT results.competition_id) as competitions_participated')
            )
            ->groupBy('teams.id', 'teams.name', 'teams.color', 'teams.avatar')
            ->orderByDesc('total_score')
            ->orderBy('teams.name')
            ->get();

        // Wins = competitions where the team holds (or shares) the top score
        $compIds = Competition::where('event_id', $this->event->id)->pluck('id');
        $wins = DB::table('results as r')
            ->joinSub(
                DB::table('results')
                    ->select('competition_id', DB::raw('MAX(score) as best_score'))
                    ->whereIn('competition_id', $compIds)
                    ->groupBy('competition_id'),
                'b',
                fn($join) => $join
                    ->on('r.competition_id', '=', 'b.competition_id')
                    ->on('r.score', '=', 'b.best_score')
            )
            ->whereIn('r.competition_id', $compIds)
            ->groupBy('r.team_id')
            ->pluck(DB::raw('COUNT(*)'), 'r.team_id');

        return $rows->each(fn($team) => $team->wins = (int) ($wins[$team->id] ?? 0));
    }

    #[Computed]
    public function eventInfo(): array
    {
        return [
            'name' => $this->event->name,
            'description' => $this->event->description,
            'date' => $this->event->event_date->format("F d, Y"),
            'totalCompetitions' => $this->event->competitions()->count(),
            'totalTeams' => $this->event->teams()->count(),
        ];
    }

    public function mount(Event $event): void
    {
        $this->event = $event;
    }
 
}
?> 
<div class="grid grid-cols-1 gap-6">
    <!-- Event Header -->
    <div class="relative overflow-hidden rounded-2xl border border-line bg-ink p-5 grain md:p-7">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-40 bg-[radial-gradient(70%_130%_at_50%_0%,rgb(229_182_75/0.12),transparent_70%)]"></div>
        <div class="relative">
            <div class="mb-2 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.25em] text-gold-300">
                <span class="size-1.5 rounded-full bg-gold-400"></span>
                Live standings
            </div>
            <h1 class="font-display text-2xl font-extrabold tracking-tight text-white md:text-4xl">{{ $this->eventInfo()['name'] }}</h1>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-zinc-400">{{ $this->eventInfo()['description'] }}</p>

            <div class="mt-6 hidden gap-3 md:grid md:grid-cols-3">
                <div class="rounded-xl border border-line bg-canvas-soft px-4 py-3">
                    <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Date</div>
                    <div class="mt-1 font-mono text-sm font-bold tabular-nums text-gold-300">{{ $this->eventInfo()['date'] }}</div>
                </div>
                <div class="rounded-xl border border-line bg-canvas-soft px-4 py-3">
                    <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Competitions</div>
                    <div class="mt-1 font-mono text-sm font-bold tabular-nums text-gold-300">{{ $this->eventInfo()['totalCompetitions'] }}</div>
                </div>
                <div class="rounded-xl border border-line bg-canvas-soft px-4 py-3">
                    <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Teams</div>
                    <div class="mt-1 font-mono text-sm font-bold tabular-nums text-gold-300">{{ $this->eventInfo()['totalTeams'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaderboard Table -->
    <div>
        <div class="mb-3 flex items-center justify-between gap-3">
            <h2 class="font-display text-xl font-bold tracking-tight text-white">Standings</h2>
        </div>

        @if($this->leaderboard->isNotEmpty())
            @php
                $standingsCount = $this->leaderboard->count();
                $standingsMobileCols = (int) ceil($standingsCount / 2);
                $standingsClass = 'standings-strip-' . $this->event->id;
            @endphp
            <style>
                .{{ $standingsClass }} { grid-template-columns: repeat({{ $standingsMobileCols }}, minmax(0, 1fr)); }
                @media (min-width: 1024px) {
                    .{{ $standingsClass }} { grid-template-columns: repeat({{ $standingsCount }}, minmax(0, 1fr)); }
                }
            </style>
            <div class="{{ $standingsClass }} grid gap-2">
                @foreach($this->leaderboard as $index => $team)
                    @php
                        $bg = $team->avatar
                            ? asset('storage/' . $team->avatar)
                            : 'https://ui-avatars.com/api/?name=' . urlencode($team->name) . '&background=16181d&color=ecc65c';
                    @endphp
                    <div wire:key="team-{{ $team->id }}" class="group relative flex min-w-0 flex-col items-center justify-center gap-1 overflow-hidden rounded-xl border border-line px-3 py-6 text-center transition-all duration-300 hover:z-20 hover:scale-105 hover:border-gold-400/50 hover:shadow-[0_12px_32px_rgb(0_0_0/0.5)]">
                        <div class="absolute inset-0 transition-transform duration-500 group-hover:scale-110" style="background-image: url('{{ $bg }}'); background-size: cover; background-position: center top;"></div>
                        <div class="absolute inset-0 opacity-30 transition-opacity duration-300 group-hover:opacity-40" style="background-color: {{ $team->color ?? '#52525b' }}"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/70 to-black/50 transition-opacity duration-300 group-hover:opacity-60"></div>
                        <div class="relative flex w-full min-w-0 flex-col items-center gap-1.5">
                            <span class="rounded-full bg-black/50 px-2.5 py-0.5 text-center font-mono text-[10px] font-bold uppercase tracking-widest text-gold-300 ring-1 ring-white/20">Rank #{{ $index + 1 }}</span>
                            <p class="w-full truncate text-sm font-bold text-white drop-shadow">{{ $team->name }}</p>
                            <p class="flex items-center justify-center gap-1.5 font-mono text-2xl font-bold tabular-nums text-gold-300 drop-shadow">
                                <x-tabler-icon name="star-filled" class="size-4" />
                                {{ $team->total_score }}
                            </p>
                            <div class="flex flex-wrap items-center justify-center gap-x-2 gap-y-0.5 text-[11px] tabular-nums">
                                <span class="text-zinc-300"><span class="font-mono font-bold text-white">{{ $team->wins }}</span> wins</span>
                                <span class="text-zinc-600">•</span>
                                <span class="text-zinc-400"><span class="font-mono font-bold text-zinc-200">{{ $team->competitions_participated }}</span> played</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="panel px-4 py-12 text-center">
                <p class="text-sm text-zinc-400">No teams found for this event yet.</p>
            </div>
        @endif
    </div>
</div> 