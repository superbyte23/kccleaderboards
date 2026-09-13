<?php

use Livewire\Component;
use App\Models\Competition;
use App\Models\Result;
use App\Livewire\Concerns\HasSileoToasts;

new class extends Component
{
    use HasSileoToasts;
    public Competition $competition;
    public array $scores = [];
    public array $teamsForInput = [];
    public bool $showScoreModal = false;

    public function mount(Competition $competition)
    {
        $this->authorize('view', $competition);

        $this->competition = $competition;
        $this->loadScores();
    }

    public function loadScores()
    {
        $this->scores = [];
        $this->teamsForInput = [];
        $teams = $this->competition->event->teams;

        foreach ($teams as $team) {
            $result = Result::where('team_id', $team->id)
                ->where('competition_id', $this->competition->id)
                ->first();
            
            $teamData = [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'team_color' => $team->color,
                'team_avatar' => $team->avatar,
                'score' => $result?->score ?? 0,
                'result_id' => $result?->id,
            ];

            $this->scores[$team->id] = $teamData;
            $this->teamsForInput[$team->id] = $teamData;
        }

        // Sort scores by score descending for leaderboard display
        uasort($this->scores, fn($a, $b) => $b['score'] <=> $a['score']);
    }

    public function updateScore($teamId, $newScore)
    {
        $this->authorize('update', $this->competition);

        $newScore = (int)$newScore;

        $result = Result::where('team_id', $teamId)
            ->where('competition_id', $this->competition->id)
            ->first();

        if ($result) {
            $result->update(['score' => $newScore]);
        } else {
            Result::create([
                'team_id' => $teamId,
                'competition_id' => $this->competition->id,
                'score' => $newScore,
            ]);
        }

        $this->loadScores();
        $this->dispatch('scoreUpdated');
    }

    public function toggleScoreModal()
    {
        if ($this->showScoreModal) {
            $this->toastSuccess('Scores saved!', 'All scores are up to date.');
        }

        $this->showScoreModal = !$this->showScoreModal;
    }
};
?>

<div class="mx-auto max-w-7xl space-y-6 p-4 pt-5">
    {{-- Header --}}
    <div>
        <flux:button
            icon="chevron-left"
            href="{{ route('event-dashboard', $competition->event) }}"
            variant="subtle"
            size="sm"
            wire:navigate
        >
            Back to event
        </flux:button>
        <h1 class="mt-2 font-display text-2xl font-extrabold tracking-tight text-white md:text-4xl">{{ $competition->name }}</h1>
        <p class="mt-1 text-sm text-zinc-400">Competition leaderboard</p>
    </div>

    {{-- Standings --}}
    <div>
        <div class="mb-3 flex items-center justify-between gap-3">
            <h2 class="font-display text-xl font-bold tracking-tight text-white">Standings</h2>
            @if(!empty($scores))
                <span class="font-mono text-xs tabular-nums text-zinc-500">{{ count($scores) }} teams</span>
            @endif
        </div>

        @if(!empty($scores))
            @php
                $stripCount = count($scores);
                $stripMobileCols = (int) ceil($stripCount / 2);
                $stripClass = 'cstrip-' . $competition->id;
            @endphp
            <style>
                .{{ $stripClass }} { grid-template-columns: repeat({{ $stripMobileCols }}, minmax(0, 1fr)); }
                @media (min-width: 1024px) {
                    .{{ $stripClass }} { grid-template-columns: repeat({{ $stripCount }}, minmax(0, 1fr)); }
                }
            </style>
            <div class="{{ $stripClass }} grid gap-2">
                @foreach($scores as $scoreData)
                    @php
                        $stripBg = $scoreData['team_avatar']
                            ? asset('storage/' . $scoreData['team_avatar'])
                            : 'https://ui-avatars.com/api/?name=' . urlencode($scoreData['team_name']) . '&background=16181d&color=ecc65c';
                        $isLeader = $loop->first;
                    @endphp
                    <div wire:key="score-{{ $scoreData['team_id'] }}" class="relative flex min-w-0 flex-col items-center justify-center gap-1 overflow-hidden rounded-xl border px-2 py-4 text-center {{ $isLeader ? 'border-gold-400/60 shadow-[0_0_24px_rgb(229_182_75/0.25)]' : 'border-line' }}" style="background-image: url('{{ $stripBg }}'); background-size: cover; background-position: center top;">
                        <div class="absolute inset-0 opacity-30" style="background-color: {{ $scoreData['team_color'] ?? '#52525b' }}"></div>
                        <div class="absolute inset-0 bg-black/70 bg-gradient-to-t from-black via-black/85 to-black/65"></div>
                        <div class="relative flex w-full min-w-0 flex-col items-center gap-1">
                            <span class="rounded-full bg-black/50 px-2.5 py-0.5 text-center font-mono text-[10px] font-bold uppercase tracking-widest {{ $isLeader ? 'text-gold-300' : 'text-zinc-400' }} ring-1 ring-white/20">Rank #{{ $loop->iteration }}</span>
                            <p class="w-full truncate text-xs font-semibold text-white drop-shadow">{{ $scoreData['team_name'] }}</p>
                            <p class="flex items-center justify-center gap-1 font-mono text-lg font-bold tabular-nums text-gold-300 drop-shadow">
                                <x-tabler-icon name="star-filled" class="size-3.5" />
                                {{ $scoreData['score'] }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="panel px-4 py-12 text-center">
                <p class="text-sm text-zinc-400">No teams registered for this competition yet.</p>
            </div>
        @endif
    </div>

    <div class="flex justify-center">
        <flux:button wire:click="toggleScoreModal" variant="primary" class="w-full sm:w-auto">
            <x-tabler-icon name="pencil-cog" class="size-4 -mt-px" />
            Edit Scores
        </flux:button>
    </div>

    {{-- Score Input Modal --}}
    <flux:modal wire:model="showScoreModal" :dismissible="false" flyout position="bottom" class="modal-sheet">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg" class="font-display font-bold tracking-tight text-white">Enter scores</flux:heading>
                <flux:subheading>Update scores for all teams in {{ $competition->name }}</flux:subheading>
            </div>

            <div class="overflow-hidden rounded-xl border border-line">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-line bg-canvas-soft">
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-widest text-zinc-500">Team</th>
                            <th class="w-28 px-4 py-2.5 text-right text-[10px] font-bold uppercase tracking-widest text-zinc-500">Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line bg-ink">
                        @foreach($teamsForInput as $teamData)
                            <tr wire:key="input-{{ $teamData['team_id'] }}">
                                <td class="px-4 py-2.5">
                                    <div class="flex min-w-0 items-center gap-2.5">
                                        <img src="{{ $teamData['team_avatar'] ? asset('storage/' . $teamData['team_avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($teamData['team_name']) }}" class="size-8 shrink-0 rounded-lg object-cover" alt="" />
                                        <span class="truncate text-sm font-semibold text-white">{{ $teamData['team_name'] }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-2.5">
                                    <input
                                        type="number"
                                        wire:change="updateScore('{{ $teamData['team_id'] }}', $event.target.value)"
                                        value="{{ $teamData['score'] }}"
                                        class="h-10 w-full rounded-lg border border-line bg-canvas-soft px-3 text-right font-mono text-base font-bold tabular-nums text-white focus:border-gold-400 focus:outline-none"
                                        min="0"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end">
                <flux:spacer />
                <flux:button wire:click="toggleScoreModal" variant="ghost">Done</flux:button>
            </div>
        </div>
    </flux:modal>
</div>