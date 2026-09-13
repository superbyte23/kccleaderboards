<?php

use Livewire\Component;
use App\Models\Event;
use App\Models\Team;
use App\Models\Competition;
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
    public function topFour(): Collection
    {
        return $this->leaderboard()->take(4);
    }

    #[Computed]
    public function countdown(): ?array
    {
        $target = $this->event->event_date->copy()->endOfDay();
        if ($target->isPast()) {
            return null;
        }
        $diff = now()->diff($target);
        return ['d' => $diff->days, 'h' => $diff->h, 'm' => $diff->i, 's' => $diff->s];
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
                    'results'  => $sorted->filter(fn ($r) => $r->team)->values(),
                ];
            });
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

<div class="w-full" wire:poll.6000ms>
  {{-- ─── Header ─────────────────────────────────────────────────────── --}}
  <header>
    <div class="min-w-0">
      <div class="mb-2 flex flex-wrap items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.2em] text-gold-300 sm:text-[11px] sm:tracking-[0.25em]">
        <span class="h-px w-6 bg-gold-400/60 sm:w-8"></span>
        <span class="truncate">Live standings · {{ $event->event_date->format('F j, Y') }}</span>
      </div>
      <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
        <h1 class="font-display text-2xl font-extrabold tracking-tight text-white sm:text-3xl md:text-5xl">{{ $event->name }}</h1>
        <span class="inline-flex shrink-0 items-center gap-2 rounded-full bg-green-500/10 px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest text-green-400 ring-1 ring-green-500/30">
          <span class="size-1.5 animate-pulse rounded-full bg-green-400"></span> Live
        </span>
      </div>
      <p class="mt-2 max-w-xl text-sm leading-relaxed text-zinc-400">{{ $event->description }}</p>
    </div>
  </header>

  {{-- ─── Row 1: Top 4 podium ────────────────────────────────────────── --}}
  @if ($this->topFour->isNotEmpty())
    @php
      $first = $this->topFour->get(0);
      $second = $this->topFour->get(1);
      $third = $this->topFour->get(2);
      $fourth = $this->topFour->get(3);
    @endphp

    <div id="podium" class="relative mt-6 scroll-mt-28 overflow-x-clip md:mt-8">
      {{-- stage floor light --}}
      <div class="pointer-events-none absolute -bottom-4 left-1/2 h-24 w-[110%] -translate-x-1/2 rounded-[100%] bg-gold-400/10 blur-2xl"></div>

      <div class="relative grid grid-cols-3 items-end gap-2 sm:grid-cols-4 sm:gap-3 md:gap-4">
        {{-- 2nd place --}}
        @if ($second)
          <div class="order-2 row-start-2 flex flex-col items-center sm:row-start-auto">
            <div class="animate-float [animation-delay:0.9s]">
              <div class="relative">
                <img src="{{ $second->avatar ? asset('storage/' . $second->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($second->name) }}" class="size-16 shrink-0 rounded-2xl border border-zinc-400/70 object-cover shadow-[0_0_24px_rgb(203_213_225/0.3)] sm:size-20 md:size-24" alt="Team avatar for {{ $second->name }}">
                <span class="absolute -right-2 -top-2 flex size-6 items-center justify-center rounded-full bg-ink shadow-lg ring-1 ring-zinc-400/50 sm:size-8">
                  <flux:icon name="trophy" variant="solid" class="size-3.5 text-zinc-300 sm:size-5" />
                </span>
              </div>
            </div>
            <p class="mt-3 w-full truncate text-center font-display text-base font-bold text-white sm:text-xl md:text-2xl">{{ $second->name }}</p>
            <div class="flex items-baseline justify-center gap-1.5">
              <span class="font-mono text-xl font-bold tabular-nums text-zinc-100 sm:text-3xl md:text-4xl">{{ $second->total_score }}</span>
              <span class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500 sm:text-xs">pts</span>
            </div>
            <p class="hidden text-xs text-zinc-500 sm:block">{{ $second->competitions_participated }} competition{{ $second->competitions_participated !== 1 ? 's' : '' }}</p>
            <div class="animate-shadow-pulse [animation-delay:0.9s] relative z-10 mt-2 h-2.5 w-16 rounded-[100%] bg-black/70 blur-[6px] sm:w-24"></div>
            <div class="-mt-5 w-full drop-shadow-[0_0_18px_rgb(203_213_225/0.12)] sm:-mt-10">
              <div class="h-4 bg-gradient-to-b from-zinc-400/30 to-zinc-400/5 [clip-path:polygon(14%_0,86%_0,100%_100%,0_100%)] sm:h-5"></div>
              <div class="flex h-14 items-center justify-center border-x border-b border-zinc-400/30 bg-gradient-to-b from-zinc-400/10 to-ink sm:h-24">
                <span class="font-mono text-xs font-bold uppercase tracking-[0.2em] text-zinc-300 sm:text-sm">Rank 2</span>
              </div>
            </div>
          </div>
        @endif

        {{-- 1st place --}}
        @if ($first)
          <div class="order-1 col-start-2 flex flex-col items-center sm:col-span-1 sm:col-start-auto">
            <div class="animate-float">
              <div class="relative">
                <img src="{{ $first->avatar ? asset('storage/' . $first->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($first->name) }}" class="size-20 shrink-0 rounded-2xl border-2 border-gold-400/60 object-cover shadow-[0_0_36px_rgb(229_182_75/0.4)] sm:size-24 md:size-28" alt="Team avatar for {{ $first->name }}">
                <span class="absolute -right-2 -top-2 flex size-7 items-center justify-center rounded-full bg-ink shadow-lg ring-1 ring-gold-400/60 sm:size-9">
                  <flux:icon name="trophy" variant="solid" class="size-4 text-gold-400 drop-shadow-[0_0_6px_rgb(229_182_75/0.8)] sm:size-5" />
                </span>
              </div>
            </div>
            <p class="mt-3 w-full truncate text-center font-display text-lg font-bold text-white sm:text-2xl md:text-3xl">{{ $first->name }}</p>
            <div class="flex items-baseline justify-center gap-1.5">
              <span class="font-mono text-2xl font-bold tabular-nums text-gold-300 sm:text-4xl md:text-5xl">{{ $first->total_score }}</span>
              <span class="text-[10px] font-semibold uppercase tracking-widest text-gold-300/60 sm:text-xs">pts</span>
            </div>
            <p class="hidden text-xs text-zinc-400 sm:block">{{ $first->competitions_participated }} competition{{ $first->competitions_participated !== 1 ? 's' : '' }}</p>
            <div class="animate-shadow-pulse relative z-10 mt-2 h-2.5 w-20 rounded-[100%] bg-black/70 blur-[6px] sm:w-28"></div>
            <div class="-mt-5 w-full drop-shadow-[0_0_20px_rgb(229_182_75/0.25)] sm:-mt-10">
              <div class="h-5 bg-gradient-to-b from-gold-400/50 to-gold-400/5 [clip-path:polygon(14%_0,86%_0,100%_100%,0_100%)] sm:h-6"></div>
              <div class="flex h-16 items-center justify-center border-x border-b border-gold-400/40 bg-gradient-to-b from-gold-400/15 to-ink sm:h-28">
                <span class="font-mono text-xs font-bold uppercase tracking-[0.2em] text-gold-300 sm:text-sm">Rank 1</span>
              </div>
            </div>
          </div>
        @endif

        {{-- 3rd place --}}
        @if ($third)
          <div class="order-3 row-start-2 flex flex-col items-center sm:row-start-auto">
            <div class="animate-float [animation-delay:1.8s]">
              <div class="relative">
                <img src="{{ $third->avatar ? asset('storage/' . $third->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($third->name) }}" class="size-16 shrink-0 rounded-2xl border border-amber-700/70 object-cover shadow-[0_0_24px_rgb(180_120_60/0.3)] sm:size-20 md:size-24" alt="Team avatar for {{ $third->name }}">
                <span class="absolute -right-2 -top-2 flex size-6 items-center justify-center rounded-full bg-ink shadow-lg ring-1 ring-amber-600/50 sm:size-8">
                  <flux:icon name="trophy" variant="solid" class="size-3.5 text-amber-600 sm:size-5" />
                </span>
              </div>
            </div>
            <p class="mt-3 w-full truncate text-center font-display text-base font-bold text-white sm:text-xl md:text-2xl">{{ $third->name }}</p>
            <div class="flex items-baseline justify-center gap-1.5">
              <span class="font-mono text-xl font-bold tabular-nums text-amber-500 sm:text-3xl md:text-4xl">{{ $third->total_score }}</span>
              <span class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500 sm:text-xs">pts</span>
            </div>
            <p class="hidden text-xs text-zinc-500 sm:block">{{ $third->competitions_participated }} competition{{ $third->competitions_participated !== 1 ? 's' : '' }}</p>
            <div class="animate-shadow-pulse [animation-delay:1.8s] relative z-10 mt-2 h-2.5 w-16 rounded-[100%] bg-black/70 blur-[6px] sm:w-24"></div>
            <div class="-mt-5 w-full drop-shadow-[0_0_18px_rgb(217_119_6/0.15)] sm:-mt-10">
              <div class="h-4 bg-gradient-to-b from-amber-600/30 to-amber-600/5 [clip-path:polygon(14%_0,86%_0,100%_100%,0_100%)] sm:h-5"></div>
              <div class="flex h-12 items-center justify-center border-x border-b border-amber-700/40 bg-gradient-to-b from-amber-600/10 to-ink sm:h-20">
                <span class="font-mono text-xs font-bold uppercase tracking-[0.2em] text-amber-500 sm:text-sm">Rank 3</span>
              </div>
            </div>
          </div>
        @endif

        {{-- 4th place --}}
        @if ($fourth)
          <div class="order-4 row-start-2 flex flex-col items-center sm:row-start-auto">
            <div class="animate-float [animation-delay:2.7s]">
              <div class="relative">
                <img src="{{ $fourth->avatar ? asset('storage/' . $fourth->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($fourth->name) }}" class="size-16 shrink-0 rounded-2xl border border-purple-500/60 object-cover shadow-[0_0_24px_rgb(168_85_247/0.35)] sm:size-20 md:size-24" alt="Team avatar for {{ $fourth->name }}">
                <span class="absolute -right-2 -top-2 flex size-6 items-center justify-center rounded-full bg-ink shadow-lg ring-1 ring-purple-500/50 sm:size-8">
                  <flux:icon name="trophy" variant="solid" class="size-3.5 text-purple-400 sm:size-5" />
                </span>
              </div>
            </div>
            <p class="mt-3 w-full truncate text-center font-display text-base font-bold text-white sm:text-xl md:text-2xl">{{ $fourth->name }}</p>
            <div class="flex items-baseline justify-center gap-1.5">
              <span class="font-mono text-xl font-bold tabular-nums text-purple-300 sm:text-3xl md:text-4xl">{{ $fourth->total_score }}</span>
              <span class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500 sm:text-xs">pts</span>
            </div>
            <p class="hidden text-xs text-zinc-500 sm:block">{{ $fourth->competitions_participated }} competition{{ $fourth->competitions_participated !== 1 ? 's' : '' }}</p>
            <div class="animate-shadow-pulse [animation-delay:2.7s] relative z-10 mt-2 h-2.5 w-16 rounded-[100%] bg-black/70 blur-[6px] sm:w-24"></div>
            <div class="-mt-5 w-full drop-shadow-[0_0_18px_rgb(168_85_247/0.18)] sm:-mt-10">
              <div class="h-3 bg-gradient-to-b from-purple-500/30 to-purple-500/5 [clip-path:polygon(14%_0,86%_0,100%_100%,0_100%)] sm:h-4"></div>
              <div class="flex h-8 items-center justify-center border-x border-b border-purple-500/30 bg-gradient-to-b from-purple-500/10 to-ink sm:h-14">
                <span class="font-mono text-xs font-bold uppercase tracking-[0.2em] text-purple-300 sm:text-sm">Rank 4</span>
              </div>
            </div>
          </div>
        @endif
      </div>
    </div>

    {{-- ─── Countdown ──────────────────────────────────────────────── --}}
    <div class="mt-5 flex items-center justify-center gap-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">
      <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
      @if ($this->countdown)
        <span>Ends in&nbsp;<span class="font-mono tabular-nums text-zinc-300">{{ str_pad($this->countdown['d'], 2, '0', STR_PAD_LEFT) }}d {{ str_pad($this->countdown['h'], 2, '0', STR_PAD_LEFT) }}h {{ str_pad($this->countdown['m'], 2, '0', STR_PAD_LEFT) }}m {{ str_pad($this->countdown['s'], 2, '0', STR_PAD_LEFT) }}s</span></span>
      @else
        <span>Final results</span>
      @endif
    </div>

    {{-- ─── Status pill ────────────────────────────────────────────── --}}
    <div class="mt-3 flex justify-center">
      <div class="inline-flex flex-wrap items-center justify-center gap-x-3 gap-y-1 rounded-full border border-line bg-canvas-soft px-4 py-1.5 text-[11px] text-zinc-400">
        <span><span class="font-mono font-bold tabular-nums text-gold-300">{{ number_format($this->leaderboard->sum('total_score')) }}</span> pts scored</span>
        <span class="text-zinc-700">•</span>
        <span>{{ $this->leaderboard->count() }} teams</span>
        <span class="text-zinc-700">•</span>
        <span>{{ $this->gameSummary->count() }} competitions</span>
      </div>
    </div>
  @endif

  {{-- ─── Row 2: Official ranking ────────────────────────────────────── --}}
  <div id="ranking" class="mt-8 scroll-mt-28 md:mt-10">
    <div class="mb-3 flex items-center justify-between px-1">
      <h3 class="text-[11px] font-bold uppercase tracking-[0.2em] text-zinc-400">Official ranking</h3>
      <div class="display-rule ml-4 flex-1"></div>
    </div>

    <div class="space-y-2">
      <div class="flex items-center gap-3 px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-zinc-600 sm:gap-4 sm:px-4">
        <span class="w-7 shrink-0 text-center sm:w-8">Plc</span>
        <div class="flex min-w-0 flex-1 items-center gap-3">
          <span class="size-8 shrink-0"></span>
          <span>Team</span>
        </div>
        <span class="shrink-0">Pts</span>
      </div>

      @php
        $rowRankText = fn ($i) => match (true) {
            $i === 0 => 'text-gold-300',
            $i === 1 => 'text-zinc-300',
            $i === 2 => 'text-amber-500',
            $i === 3 => 'text-purple-400',
            default => 'text-zinc-600',
        };
        $rowCard = fn ($i) => match (true) {
            $i === 0 => 'border-gold-400/40 bg-gold-400/10',
            $i === 1 => 'border-zinc-400/30 bg-zinc-300/10',
            $i === 2 => 'border-amber-600/40 bg-amber-600/10',
            $i === 3 => 'border-purple-500/40 bg-purple-500/10',
            default => 'border-line bg-canvas-soft',
        };
      @endphp
      @forelse ($this->leaderboard as $index => $team)
        <div class="flex items-center gap-3 rounded-xl border px-3 py-2.5 transition hover:brightness-125 sm:gap-4 sm:px-4 sm:py-3 {{ $rowCard($index) }}">
          <span class="w-7 shrink-0 text-center font-mono text-sm font-bold tabular-nums sm:w-8 sm:text-base {{ $rowRankText($index) }}">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
          <div class="flex min-w-0 flex-1 items-center gap-3">
            <img src="{{ $team->avatar ? asset('storage/' . $team->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($team->name) }}" class="size-8 shrink-0 rounded-lg object-cover" alt="Team avatar for {{ $team->name }}">
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold text-white">{{ $team->name }}</p>
              <p class="truncate text-[10px] uppercase tracking-widest text-zinc-500">{{ $team->represents }}</p>
            </div>
          </div>
          <div class="flex shrink-0 items-center gap-2.5">
            <span class="size-2 rounded-full" style="background-color: {{ $team->color }}"></span>
            <span class="font-mono text-sm font-bold tabular-nums text-gold-300">{{ number_format($team->total_score) }}</span>
          </div>
        </div>
      @empty
        <div class="panel p-12 text-center">
          <p class="text-sm text-zinc-400">No teams registered for this event yet.</p>
        </div>
      @endforelse
    </div>
  </div>

  {{-- ─── Row 3: Game summary (inline) ───────────────────────────────── --}}
  <div id="summary" class="mt-8 scroll-mt-28 md:mt-10" x-data="{
    open: false,
    q: '',
    cat: '',
    comps: @js($this->gameSummary->map(fn ($c) => ['n' => $c->name, 'c' => $c->category])->values()->all()),
    selected: { name: '', category: '', results: [] },
    match(n, c) {
      const t = this.q.trim().toLowerCase();
      return (t === '' || n.toLowerCase().includes(t)) && (this.cat === '' || c === this.cat);
    },
    rankText(n) {
      return n === 1 ? 'text-gold-300' : n === 2 ? 'text-zinc-300' : n === 3 ? 'text-amber-500' : n === 4 ? 'text-purple-400' : 'text-zinc-600';
    },
  }">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2 px-1">
      <h3 class="text-[11px] font-bold uppercase tracking-[0.2em] text-zinc-400">Game summary</h3>
      <div class="flex flex-wrap items-center gap-2">
        <div class="w-44 sm:w-52">
          <flux:input type="search" x-model="q" placeholder="Filter games…" icon="magnifying-glass" clearable />
        </div>
        <div class="w-40 sm:w-48">
          <flux:select x-model="cat">
            <option value="">All categories</option>
            @foreach ($this->gameSummary->map(fn ($c) => $c->category)->unique()->values() as $category)
              <option value="{{ $category }}">{{ $category }}</option>
            @endforeach
          </flux:select>
        </div>
        <span class="rounded-full bg-canvas-soft px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-500 ring-1 ring-line">{{ $this->gameSummary->count() }} competition{{ $this->gameSummary->count() !== 1 ? 's' : '' }}</span>
      </div>
    </div>

    @if ($this->gameSummary->isEmpty())
      <div class="rounded-2xl border border-line bg-canvas-soft py-12 text-center">
        <p class="text-sm text-zinc-400">No competition results recorded yet.</p>
      </div>
    @else
      @php
        $ranked = $this->leaderboard->values();
        $teamTone = function ($num) {
            return match (true) {
                $num === 1 => ['ring' => 'ring-1 ring-gold-400/60', 'text' => 'text-gold-300'],
                $num === 2 => ['ring' => 'ring-1 ring-zinc-400/50', 'text' => 'text-zinc-300'],
                $num === 3 => ['ring' => 'ring-1 ring-amber-600/60', 'text' => 'text-amber-500'],
                $num === 4 => ['ring' => 'ring-1 ring-purple-500/60', 'text' => 'text-purple-400'],
                default => ['ring' => 'ring-1 ring-zinc-600/40', 'text' => 'text-zinc-500'],
            };
        };
      @endphp

      <div class="overflow-hidden rounded-2xl border border-line bg-canvas-soft">
        <div class="overflow-x-auto">
          <div class="grid min-w-[36rem]" style="grid-template-columns: 10.5rem repeat({{ $ranked->count() }}, minmax(3.5rem, 1fr));">
            {{-- Corner + team headers (rank order) --}}
            <div class="sticky left-0 z-10 border-b border-line bg-canvas-soft px-4 py-3">
              <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">Competition</span>
            </div>
            @foreach ($ranked as $i => $team)
              @php $tone = $teamTone($i + 1); @endphp
              <div class="border-b border-l border-line bg-canvas-soft px-2 py-3 text-center">
                <p class="font-mono text-[9px] font-bold uppercase tracking-widest {{ $tone['text'] }}">Rank {{ $i + 1 }}</p>
                <img src="{{ $team->avatar ? asset('storage/' . $team->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($team->name) }}" class="mx-auto mt-1 size-7 rounded-lg object-cover {{ $tone['ring'] }}" alt="Team avatar for {{ $team->name }}">
                <p class="mt-1 truncate text-[10px] font-semibold text-zinc-200">{{ $team->name }}</p>
              </div>
            @endforeach

            {{-- Rows: one per competition --}}
            @foreach ($this->gameSummary as $comp)
              @php
                $byTeam = $comp->results->keyBy(fn ($r) => $r->team->id);
                $best = $comp->results->max('score');
                $detail = [
                    'name'     => $comp->name,
                    'category' => $comp->category,
                    'results'  => $comp->results->map(fn ($r) => [
                        'name'   => $r->team->name,
                        'avatar' => $r->team->avatar ? asset('storage/' . $r->team->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($r->team->name),
                        'score'  => (int) $r->score,
                        'best'   => (int) $r->score === $best,
                    ])->values()->all(),
                ];
              @endphp
              <button type="button" @click="selected = @js($detail); open = true" x-show="match(@js($comp->name), @js($comp->category))" class="sticky left-0 z-10 flex w-full items-center justify-between gap-2 border-t border-line bg-canvas-soft px-4 py-2.5 text-left">
                <span class="min-w-0">
                  <span class="block truncate text-sm font-semibold text-white">{{ $comp->name }}</span>
                  <span class="block truncate text-[10px] uppercase tracking-widest text-zinc-500">{{ $comp->category }}</span>
                </span>
                <flux:icon name="chevron-down" variant="mini" class="size-4 shrink-0 text-zinc-600 md:hidden" />
              </button>
              @foreach ($ranked as $i => $team)
                @php $result = $byTeam->get($team->id); @endphp
                <div x-show="match(@js($comp->name), @js($comp->category))" class="border-t border-l border-line px-2 py-2 text-center">
                  @if ($result)
                    <span class="font-mono text-xs font-bold tabular-nums {{ $result->score === $best ? 'text-gold-300' : 'text-zinc-400' }}">{{ number_format($result->score) }}</span>
                  @else
                    <span class="font-mono text-xs text-zinc-700">—</span>
                  @endif
                </div>
              @endforeach
            @endforeach
          </div>
        </div>
      </div>

      <div x-show="q.trim() !== '' || cat !== '' ? ! comps.some((r) => match(r.n, r.c)) : false" x-cloak class="rounded-2xl border border-line bg-canvas-soft py-10 text-center">
        <p class="text-sm text-zinc-400">No games match the current filters.</p>
        <button type="button" @click="q = ''; cat = ''" class="mt-2 text-xs font-semibold text-gold-300 hover:text-gold-200">Clear filters</button>
      </div>

      {{-- ─── Mobile: competition detail offcanvas ─────────────────────── --}}
      <div class="fixed inset-0 z-50 md:hidden" x-show="open" x-cloak>
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="open = false" x-show="open" x-transition.opacity></div>
        <div
          class="absolute inset-x-0 bottom-0 flex max-h-[82vh] w-full flex-col rounded-t-3xl border-t border-line bg-ink shadow-2xl"
          x-show="open"
          x-cloak
          x-transition:enter="transition ease-out duration-300"
          x-transition:enter-start="translate-y-full"
          x-transition:enter-end="translate-y-0"
          x-transition:leave="transition ease-in duration-200"
          x-transition:leave-start="translate-y-0"
          x-transition:leave-end="translate-y-full"
        >
          <div class="mx-auto mt-2.5 h-1 w-10 shrink-0 rounded-full bg-zinc-700"></div>
          <div class="flex items-start justify-between gap-3 border-b border-line px-5 py-4">
            <div class="min-w-0">
              <p class="font-display text-xl font-bold leading-tight text-white" x-text="selected.name"></p>
              <span class="mt-1.5 inline-block rounded-full bg-gold-400/10 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-widest text-gold-300 ring-1 ring-gold-400/20" x-text="selected.category"></span>
            </div>
            <button type="button" @click="open = false" class="-mr-1.5 flex size-8 shrink-0 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-ink hover:text-white" aria-label="Close details">
              <flux:icon name="x-mark" variant="solid" class="size-5" />
            </button>
          </div>

          <div class="min-h-0 flex-1 overflow-y-auto px-3 py-3">
            <template x-for="(r, i) in selected.results" :key="i">
              <div class="flex items-center gap-3 rounded-lg px-2.5 py-2.5">
                <span class="w-5 shrink-0 font-mono text-xs font-bold tabular-nums" :class="rankText(i + 1)" x-text="i + 1"></span>
                <img :src="r.avatar" class="size-8 shrink-0 rounded-lg object-cover" :alt="'Team avatar for ' + r.name" />
                <p class="min-w-0 flex-1 truncate text-sm font-medium text-zinc-100" x-text="r.name"></p>
                <span class="shrink-0 font-mono text-sm font-bold tabular-nums" :class="r.best ? 'text-gold-300' : 'text-zinc-400'" x-text="r.score.toLocaleString()"></span>
              </div>
            </template>
          </div>
        </div>
      </div>
    @endif
  </div>
</div>
