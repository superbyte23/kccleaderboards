<?php

use Livewire\Component;
use App\Models\Competition;
use App\Models\Result;

new class extends Component
{
    public Competition $competition;
    public array $scores = [];
    public array $teamsForInput = [];
    public bool $showScoreModal = false;

    public function mount(Competition $competition)
    {
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
        $this->showScoreModal = !$this->showScoreModal;
    }

    public function getWinner()
    {
        return reset($this->scores);
    }
};
?>
<div>
    {{-- Header --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <flux:heading size="xl">{{ $competition->name }}</flux:heading>
            <flux:subheading>Competition Leaderboard</flux:subheading>
        </div>
        <flux:button
            href="{{ route('event-dashboard', $competition->event) }}"
            variant="ghost"
            icon="arrow-left"
            wire:navigate
        >
            Back to Event
        </flux:button>
    </div>

    {{-- Actions --}}
    <div class="mb-6">
        <flux:button
            wire:click="toggleScoreModal"
            variant="primary"
            icon="pencil-square"
        >
            Edit Scores
        </flux:button>
    </div>

    {{-- Current Leader --}}
    @if($winner = $this->getWinner())
        <div class="mb-6 rounded-xl p-5 flex items-center gap-4 border-l-4"
            style="border-color: {{ $winner['team_color'] }}; background-color: {{ $winner['team_color'] }}18;">
            <span class="text-4xl">🏆</span>
            <div>
                <div class="text-xs uppercase tracking-widest opacity-60 font-semibold mb-0.5">Current Leader</div>
                <div class="text-2xl font-bold" style="color: {{ $winner['team_color'] }}">
                    {{ $winner['team_name'] }}
                </div>
                <div class="text-sm font-mono font-bold opacity-70">
                    {{ number_format($winner['score']) }} pts
                </div>
            </div>
        </div>
    @endif

    {{-- Leaderboard --}}
    @if(!empty($scores))
        <flux:card>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Rank</flux:table.column>
                    <flux:table.column>Team</flux:table.column>
                    <flux:table.column class="text-right">Score</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($scores as $index => $scoreData)
                        <flux:table.row wire:key="score-{{ $scoreData['team_id'] }}">
                            <flux:table.cell>
                                @if($loop->first)
                                    <span class="text-lg">🥇</span>
                                @elseif($loop->iteration === 2)
                                    <span class="text-lg">🥈</span>
                                @elseif($loop->iteration === 3)
                                    <span class="text-lg">🥉</span>
                                @else
                                    <flux:badge variant="subtle">{{ $loop->iteration }}</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    <div class="w-3 h-3 rounded-full shrink-0"
                                        style="background-color: {{ $scoreData['team_color'] ?? '#64748b' }}">
                                    </div>
                                    <span class="font-semibold">{{ $scoreData['team_name'] }}</span>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell class="text-right">
                                <span class="text-xl font-bold font-mono text-[#e5b64b]">
                                    {{ number_format($scoreData['score']) }}
                                </span>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @else
        <flux:card class="text-center py-12">
            <flux:heading>No scores yet</flux:heading>
            <flux:subheading class="mt-1">No teams registered for this competition yet.</flux:subheading>
        </flux:card>
    @endif

    {{-- Score Input Modal --}}
    <flux:modal wire:model="showScoreModal" :dismissible="false" flyout>
        <div class="space-y-6">

            {{-- Modal Header --}}
            <div>
                <flux:heading size="lg">Enter Scores</flux:heading>
                <flux:subheading>Update scores for all teams in {{ $competition->name }}</flux:subheading>
            </div>

            {{-- Score Inputs --}}
            <div class="space-y-3">
                @foreach($teamsForInput as $teamData)
                    <div class="flex items-center gap-4 p-3 rounded-xl bg-zinc-100 dark:bg-zinc-800">
                        <div class="w-3 h-3 rounded-full shrink-0"
                            style="background-color: {{ $teamData['team_color'] ?? '#64748b' }}">
                        </div>
                        <span class="font-semibold flex-1 text-sm">{{ $teamData['team_name'] }}</span>
                        <div class="w-32">
                            <flux:input
                                type="number"
                                wire:change="updateScore('{{ $teamData['team_id'] }}', $event.target.value)"
                                value="{{ $teamData['score'] }}"
                                min="0"
                                size="sm"
                                input:class="text-center font-bold font-mono"
                            />
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Footer --}}
            <div class="flex justify-end pt-2">
                <flux:button wire:click="toggleScoreModal" variant="primary">Done</flux:button>
            </div>

        </div>
    </flux:modal>
</div>