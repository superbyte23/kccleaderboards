<x-layouts::app :title="__('Dashboard')">
    @php
        $events = \App\Models\Event::withCount(['teams', 'competitions'])->latest()->get();
        $liveCount = \App\Models\Event::whereDate('event_date', today())->count();
        $upcomingCount = \App\Models\Event::where('event_date', '>', now())->count();
        $totalTeams = \App\Models\Team::count();
        $totalCompetitions = \App\Models\Competition::count();
        $recentEvents = $events->take(4);
    @endphp

    <div class="mx-auto max-w-7xl space-y-6 p-4 pt-5">
        <div class="relative overflow-hidden rounded-2xl border border-line bg-ink p-5 grain md:p-6">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-40 bg-[radial-gradient(70%_120%_at_50%_0%,rgb(229_182_75/0.12),transparent_70%)]"></div>

            <div class="relative">
                <div class="mb-3 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.25em] text-gold-300">
                    <span class="size-1.5 animate-pulse rounded-full bg-gold-400"></span>
                    Rally Dashboard
                </div>
                <h1 class="font-display text-2xl font-extrabold tracking-tight text-white md:text-4xl">
                    Welcome back, {{ auth()->user()->name }}
                </h1>
                <p class="mt-1.5 max-w-lg text-sm leading-relaxed text-zinc-400">
                    Manage events, teams, and competitions — or hop straight into the scoreboard.
                </p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <flux:button :href="route('events')" icon="plus" wire:navigate>Create Event</flux:button>
                    <flux:button :href="route('events')" icon="trophy" variant="subtle" wire:navigate>Manage Events</flux:button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ([
                ['label' => 'Live now', 'value' => $liveCount, 'icon' => 'pulse'],
                ['label' => 'Upcoming', 'value' => $upcomingCount, 'icon' => 'calendar'],
                ['label' => 'Teams', 'value' => $totalTeams, 'icon' => 'users'],
                ['label' => 'Competitions', 'value' => $totalCompetitions, 'icon' => 'trophy'],
            ] as $stat)
                <div class="panel p-4">
                    <div class="font-mono text-2xl font-bold tabular-nums text-gold-300">{{ $stat['value'] }}</div>
                    <div class="mt-1 text-[11px] font-semibold uppercase tracking-widest text-zinc-500">{{ $stat['label'] }}</div>
                </div>
            @endforeach
        </div>

        <div class="grid flex-1 grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="panel overflow-hidden lg:col-span-2">
                <div class="flex items-center justify-between border-b border-line px-4 py-3 md:px-5">
                    <h2 class="font-display text-xl font-bold tracking-tight text-white">Recent events</h2>
                    <flux:button :href="route('events')" variant="subtle" size="sm" icon-trailing="arrow-right" wire:navigate>View all</flux:button>
                </div>

                <div class="divide-y divide-line">
                    @forelse($recentEvents as $event)
                        <a href="{{ route('event-dashboard', $event) }}" wire:navigate
                            class="group flex items-center justify-between gap-3 px-4 py-3 transition-colors hover:bg-ink-soft md:px-5">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-xl border border-line bg-canvas-soft text-gold-300">
                                    <flux:icon name="trophy" class="size-4" />
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-white group-hover:text-gold-300">{{ $event->name }}</p>
                                    <p class="truncate text-xs text-zinc-500">{{ $event->event_date->format('F j, Y') }}</p>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-4">
                                <span class="text-right">
                                    <span class="block font-mono text-sm font-bold tabular-nums text-zinc-300">{{ $event->teams_count }}</span>
                                    <span class="block text-[10px] uppercase tracking-widest text-zinc-500">teams</span>
                                </span>
                                <span class="text-right">
                                    <span class="block font-mono text-sm font-bold tabular-nums text-zinc-300">{{ $event->competitions_count }}</span>
                                    <span class="block text-[10px] uppercase tracking-widest text-zinc-500">events</span>
                                </span>
                            </div>
                        </a>
                    @empty
                        <div class="px-4 py-10 text-center">
                            <p class="text-sm text-zinc-400">No events yet. Create your first one to start the games.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="panel overflow-hidden">
                <div class="border-b border-line px-4 py-3 md:px-5">
                    <h2 class="font-display text-lg font-bold tracking-tight text-white">Leaderboards</h2>
                </div>

                <div class="flex flex-col gap-2 p-4">
                    @forelse($events as $event)
                        <a href="{{ route('leaderboards', $event) }}" wire:navigate
                            class="group flex items-center justify-between rounded-xl border border-line bg-canvas-soft px-4 py-3 transition-colors hover:border-gold-400/40">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-white">{{ $event->name }}</p>
                                <p class="truncate text-[11px] text-zinc-500">{{ $event->event_date->format('M j') }}</p>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4 shrink-0 text-zinc-600 transition-colors group-hover:text-gold-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    @empty
                        <p class="text-center text-sm text-zinc-400">Nothing to show yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-layouts::app>