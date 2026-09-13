<x-layouts::guest>
    {{-- ─── Hero: the Rally platform ─────────────────────────────── --}}
    <header>
        <div class="grid items-start gap-10 lg:grid-cols-2 lg:gap-12">
            <div class="min-w-0">
                <div class="animate-rise mb-2 flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.2em] text-gold-300 sm:text-[11px] sm:tracking-[0.25em]">
                    <span class="h-px w-6 bg-gold-400/60 sm:w-8"></span>
                    <span class="truncate">Live scoring platform</span>
                </div>
                <h1 class="animate-rise font-display text-2xl font-extrabold tracking-tight text-white sm:text-3xl md:text-5xl" style="animation-delay: 80ms">
                    Live scoreboards<br>
                    <span class="text-zinc-500">for your</span>
                    <span class="text-gold-300">intramurals.</span>
                </h1>
                <p class="animate-rise mt-2 max-w-xl text-sm leading-relaxed text-zinc-400" style="animation-delay: 160ms">
                    Rally turns every game into a living scoreboard — live rankings, podium finishes, and every result in one place. Pick an event below to follow the standings.
                </p>
                <div class="animate-rise mt-6 flex flex-wrap items-center gap-3 sm:mt-7" style="animation-delay: 240ms">
                    <a href="#events" class="btn-shine inline-flex items-center gap-2 rounded-xl bg-gold-400 px-5 py-2.5 text-sm font-bold text-gold-950 transition-colors hover:bg-gold-300">
                        Explore events
                        <svg xmlns="http://www.w3.org/2000/svg" class="relative z-[2] size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m0 0l-6-6m6 6l6-6"/></svg>
                    </a>
                    @guest
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl border border-line bg-canvas-soft px-5 py-2.5 text-sm font-bold text-zinc-200 transition-colors hover:border-gold-400/40 hover:text-white">
                                Join in
                            </a>
                        @endif
                    @endguest
                </div>
                <div class="animate-rise mt-5 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-[11px] font-semibold uppercase tracking-[0.15em] text-zinc-500" style="animation-delay: 320ms">
                    <span class="flex items-center gap-1.5"><flux:icon name="bolt" variant="mini" class="size-3.5 text-gold-400" /> Instant results</span>
                    <span class="flex items-center gap-1.5"><flux:icon name="trophy" variant="mini" class="size-3.5 text-gold-400" /> Podium finishes</span>
                    <span class="hidden items-center gap-1.5 sm:flex"><flux:icon name="table-cells" variant="mini" class="size-3.5 text-gold-400" /> Game summary</span>
                </div>
            </div>

            {{-- Mock product preview (static — not event data) --}}
            <div class="animate-rise relative mx-auto w-full max-w-md lg:max-w-none" style="animation-delay: 300ms" aria-hidden="true">
                <div class="pointer-events-none absolute -inset-4 rounded-[2rem] bg-gold-400/10 blur-2xl"></div>
                <div class="animate-float relative overflow-hidden rounded-2xl border border-line bg-ink shadow-[0_24px_80px_-24px_rgb(0_0_0/0.8)]">
                    <div class="flex items-center justify-between border-b border-line px-4 py-3 sm:px-5">
                        <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Official Ranking</span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest text-green-400 ring-1 ring-green-500/30">
                            <span class="size-1.5 animate-pulse rounded-full bg-green-400"></span> Live
                        </span>
                    </div>
                    <div class="space-y-2 p-3 sm:p-4">
                        @php
                            $mock = [
                                ['n' => 1, 'name' => 'Team Gold', 'pts' => 240, 'tone' => 'text-gold-300', 'row' => 'border-gold-400/40 bg-gold-400/10', 'avatar' => 'https://ui-avatars.com/api/?name=TG&background=e5b64b&color=0a0b0d&bold=true'],
                                ['n' => 2, 'name' => 'Team Crimson', 'pts' => 210, 'tone' => 'text-zinc-200', 'row' => 'border-zinc-400/30 bg-zinc-400/10', 'avatar' => 'https://ui-avatars.com/api/?name=TC&background=71717a&color=0a0b0d&bold=true'],
                                ['n' => 3, 'name' => 'Team Indigo', 'pts' => 180, 'tone' => 'text-amber-500', 'row' => 'border-amber-700/40 bg-amber-600/10', 'avatar' => 'https://ui-avatars.com/api/?name=TI&background=b45309&color=0a0b0d&bold=true'],
                                ['n' => 4, 'name' => 'Team Emerald', 'pts' => 150, 'tone' => 'text-purple-300', 'row' => 'border-purple-500/30 bg-purple-500/10', 'avatar' => 'https://ui-avatars.com/api/?name=TE&background=a855f7&color=0a0b0d&bold=true'],
                            ];
                        @endphp
                        @foreach ($mock as $row)
                            <div class="flex items-center gap-2.5 rounded-xl border px-2.5 py-2 sm:gap-3 sm:px-3 {{ $row['row'] }}">
                                <span class="w-5 shrink-0 text-center font-mono text-xs font-bold tabular-nums {{ $row['tone'] }}">{{ $row['n'] }}</span>
                                <img src="{{ $row['avatar'] }}" class="size-7 shrink-0 rounded-lg object-cover" alt="" loading="lazy">
                                <span class="min-w-0 flex-1 truncate text-xs font-bold text-white sm:text-sm">{{ $row['name'] }}</span>
                                <span class="shrink-0 font-mono text-sm font-bold tabular-nums {{ $row['tone'] }} sm:text-base">{{ $row['pts'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex items-center justify-between border-t border-line bg-canvas-soft px-4 py-2.5 sm:px-5">
                        <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-zinc-500">4 teams · live</span>
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold text-gold-300">
                            View full leaderboard
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- ─── Vibe ticker ──────────────────────────────────────────── --}}
    <div class="mt-8 overflow-hidden rounded-2xl border border-line bg-ink/60 md:mt-10">
        <div class="animate-marquee flex w-max hover:[animation-play-state:paused]">
            @foreach ([0, 1] as $half)
                <div @if ($half === 1) aria-hidden="true" @endif class="flex shrink-0 items-center">
                    @foreach (['Live Scores', 'Podium Finishes', 'Game Summary', 'Rankings', 'Intramurals', 'House Colors'] as $word)
                        <span class="flex items-center gap-2 whitespace-nowrap px-5 py-2.5 font-mono text-[11px] font-bold uppercase tracking-[0.2em] text-zinc-300">
                            {{ $word }}
                            <flux:icon name="bolt" variant="mini" class="ml-3 size-3 shrink-0 text-gold-400/60" />
                        </span>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    {{-- ─── Features: how it plays ───────────────────────────────── --}}
    <section class="mt-8 md:mt-10">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2 px-1">
            <h3 class="text-[11px] font-bold uppercase tracking-[0.2em] text-zinc-400">How it plays</h3>
            <span class="rounded-full bg-canvas-soft px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-500 ring-1 ring-line">The platform</span>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div class="group rounded-2xl border border-line bg-canvas-soft p-5 transition-all duration-300 hover:-translate-y-1 hover:border-gold-400/40 hover:shadow-gold sm:p-6">
                <div class="mb-4 flex size-10 items-center justify-center rounded-xl bg-gold-400/10 ring-1 ring-gold-400/30">
                    <flux:icon name="bolt" variant="solid" class="size-5 text-gold-300" />
                </div>
                <h4 class="mb-1.5 font-display text-xl font-bold tracking-tight text-white sm:text-2xl">Live leaderboards</h4>
                <p class="text-sm leading-relaxed text-zinc-400">Team standings update the moment a result lands. Every point, every climb, in real time.</p>
            </div>
            <div class="group rounded-2xl border border-line bg-canvas-soft p-5 transition-all duration-300 hover:-translate-y-1 hover:border-gold-400/40 hover:shadow-gold sm:p-6">
                <div class="mb-4 flex size-10 items-center justify-center rounded-xl bg-gold-400/10 ring-1 ring-gold-400/30">
                    <flux:icon name="trophy" variant="solid" class="size-5 text-gold-300" />
                </div>
                <h4 class="mb-1.5 font-display text-xl font-bold tracking-tight text-white sm:text-2xl">Champion podium</h4>
                <p class="text-sm leading-relaxed text-zinc-400">Top finishers get the ceremony treatment — gold, silver, bronze, and the spotlight.</p>
            </div>
            <div class="group rounded-2xl border border-line bg-canvas-soft p-5 transition-all duration-300 hover:-translate-y-1 hover:border-gold-400/40 hover:shadow-gold sm:p-6 sm:col-span-2 lg:col-span-1">
                <div class="mb-4 flex size-10 items-center justify-center rounded-xl bg-gold-400/10 ring-1 ring-gold-400/30">
                    <flux:icon name="table-cells" variant="solid" class="size-5 text-gold-300" />
                </div>
                <h4 class="mb-1.5 font-display text-xl font-bold tracking-tight text-white sm:text-2xl">Game summary</h4>
                <p class="text-sm leading-relaxed text-zinc-400">Every competition × team result in one sheet — searchable and filterable by category.</p>
            </div>
        </div>

        @if ($totals['events'] > 0)
            <div class="mt-3 flex justify-center">
                <div class="inline-flex flex-wrap items-center justify-center gap-x-3 gap-y-1 rounded-full border border-line bg-canvas-soft px-4 py-1.5 text-[11px] text-zinc-400">
                    <span><span class="font-mono font-bold tabular-nums text-gold-300">{{ $totals['events'] }}</span> event{{ $totals['events'] !== 1 ? 's' : '' }} hosted</span>
                    <span class="text-zinc-700">•</span>
                    <span><span class="font-mono font-bold tabular-nums text-gold-300">{{ $totals['teams'] }}</span> teams</span>
                    <span class="text-zinc-700">•</span>
                    <span><span class="font-mono font-bold tabular-nums text-gold-300">{{ $totals['competitions'] }}</span> competitions</span>
                </div>
            </div>
        @endif
    </section>

    {{-- ─── Events on the platform ───────────────────────────────── --}}
    <main id="events" class="mt-8 scroll-mt-28 md:mt-10">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2 px-1">
            <h3 class="text-[11px] font-bold uppercase tracking-[0.2em] text-zinc-400">Events on the platform</h3>
            <span class="rounded-full bg-canvas-soft px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-500 ring-1 ring-line">{{ $events->count() }} event{{ $events->count() !== 1 ? 's' : '' }}</span>
        </div>

        <div class="space-y-3">
            @forelse ($events as $event)
                @php
                    $cardTone = $event->event_date->isToday()
                        ? 'border-gold-400/40 bg-gold-400/[0.06]'
                        : 'border-line bg-canvas-soft';
                @endphp
                <a href="/leaderboards/{{ $event->id }}" wire:navigate style="animation-delay: {{ min($loop->index * 80, 400) }}ms"
                    class="animate-rise group block overflow-hidden rounded-2xl border p-4 transition-all duration-300 hover:-translate-y-1 hover:shadow-gold sm:p-6 {{ $cardTone }}">
                    <div class="flex flex-col gap-5 sm:gap-6 md:flex-row md:items-start md:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="mb-3 flex flex-wrap items-center gap-2 sm:mb-4 sm:gap-3">
                                <span class="font-mono text-[11px] font-bold tabular-nums text-zinc-700">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                @if ($event->event_date->isToday())
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-green-500/10 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-green-400 ring-1 ring-green-500/30">
                                        <span class="size-1.5 animate-pulse rounded-full bg-green-400"></span> Live Now
                                    </span>
                                @elseif ($event->event_date->isFuture())
                                    <span class="rounded-full bg-gold-400/10 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-gold-300 ring-1 ring-gold-400/30">Upcoming</span>
                                @else
                                    <span class="rounded-full bg-zinc-500/10 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-zinc-400 ring-1 ring-zinc-500/20">Concluded</span>
                                @endif
                                <span class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0-2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/></svg>
                                    {{ $event->event_date->format('M j, Y') }}
                                </span>
                            </div>

                            <h3 class="mb-2 font-display text-2xl font-bold tracking-tight text-white sm:text-3xl md:text-4xl">{{ $event->name }}</h3>
                            <p class="mb-5 line-clamp-2 max-w-xl text-sm leading-relaxed text-zinc-400 sm:mb-6">{{ $event->description }}</p>
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center gap-4 sm:gap-6 md:flex-col md:items-end md:gap-4">
                            <div class="flex items-center gap-5 sm:gap-6 md:justify-end">
                                <div class="text-center">
                                    <div class="font-mono text-xl font-bold tabular-nums text-gold-300 sm:text-2xl">{{ $event->teams_count }}</div>
                                    <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Teams</div>
                                </div>
                                <div class="text-center">
                                    <div class="font-mono text-xl font-bold tabular-nums text-gold-300 sm:text-2xl">{{ $event->competitions_count }}</div>
                                    <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Events</div>
                                </div>
                            </div>

                            <span class="btn-shine inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gold-400 px-4 py-2.5 text-sm font-bold text-gold-950 transition-colors group-hover:bg-gold-300 sm:w-auto">
                                View Leaderboard
                                <svg xmlns="http://www.w3.org/2000/svg" class="relative z-[2] size-4 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            </span>
                        </div>
                    </div>
                </a>
            @empty
                <div class="rounded-2xl border border-line bg-canvas-soft p-16 text-center">
                    <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-xl bg-gold-400/10 ring-1 ring-gold-400/20">
                        <flux:icon name="trophy" variant="solid" class="size-6 text-gold-400" />
                    </div>
                    <h3 class="mb-2 text-xl font-bold text-white">No events yet</h3>
                    <p class="text-sm text-zinc-400">The arena is still warming up. Check back soon for the first event.</p>
                </div>
            @endforelse
        </div>
    </main>
</x-layouts::guest>
