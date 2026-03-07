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

<div class="">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-4xl font-bold text-gray-900 mb-2">{{ $competition->name }}</h1>
            <p class="text-gray-600">Competition Leaderboard</p>
        </div>

        <flux:button
            href="{{ route('event-dashboard', $competition->event) }}"
            variant="outline"
            wire:navigate
        >
            Back to Event Dashboard
        </flux:button>
    </div>
    {{-- Edit Mode Toggle --}}
    <div class="mb-6">
        <flux:button
            wire:click="toggleScoreModal"
            variant="primary"
        >
            Edit Scores
        </flux:button>
    </div>

    {{-- Winner Badge --}}
    @if($winner = $this->getWinner())
        <div class="mb-8 p-6 rounded-lg shadow-lg" style="background: linear-gradient(135deg, {{ $winner['team_color'] }} 0%, {{ $winner['team_color'] }}dd 100%); border: 3px solid {{ $winner['team_color'] }};">
            <div class="text-center">
                <p class="text-white font-semibold mb-1 drop-shadow-lg">🏆 CURRENT LEADER 🏆</p>
                <p class="text-3xl font-bold text-white drop-shadow-lg">{{ $winner['team_name'] }}</p>
                <p class="text-2xl font-bold text-white drop-shadow-lg">Score: {{ $winner['score'] }}</p>
            </div>
        </div>
    @endif

    {{-- Leaderboard Table --}}
    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-100 border-b-2 border-gray-300">
                    <th class="px-6 py-4 text-left font-semibold text-gray-900">Rank</th>
                    <th class="px-6 py-4 text-left font-semibold text-gray-900">Team</th>
                    <th class="px-6 py-4 text-center font-semibold text-gray-900">Score</th>
                    {{-- @if($editMode)
                        <th class="px-6 py-4 text-center font-semibold text-gray-900">Action</th>
                    @endif --}}
                </tr>
            </thead>
            <tbody>
                @foreach($scores as $index => $scoreData)
                    <tr class="border-b border-gray-200 hover:bg-gray-50 transition-colors
                        @if($loop->first) bg-yellow-50 @endif">
                        <td class="px-6 py-4">
                            <span class="text-lg font-bold text-gray-700">
                                @if($loop->first)
                                    🥇 1st
                                @elseif($loop->iteration === 2)
                                    🥈 2nd
                                @elseif($loop->iteration === 3)
                                    🥉 3rd
                                @else
                                    {{ $loop->iteration }}
                                @endif
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="flex items-center">
                                <span
                                    class="w-4 h-4 rounded-full mr-3 border-2 border-gray-400"
                                    style="background-color: {{ $scoreData['team_color'] ?? '#999' }}"
                                ></span>
                                <span class="font-semibold text-gray-900">{{ $scoreData['team_name'] }}</span>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-2xl font-bold text-gray-900">{{ $scoreData['score'] }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Empty State --}}
    @if(empty($scores))
        <div class="text-center py-12 bg-gray-50 rounded-lg">
            <p class="text-gray-600 text-lg">No teams registered for this competition yet.</p>
        </div>
    @endif

    {{-- Score Input Modal --}}
    <flux:modal wire:model="showScoreModal" :dismissible="false" flyout>
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Enter Scores</flux:heading>
                <flux:subheading>Update scores for all teams in {{ $competition->name }}</flux:subheading>
            </div>

            <div class="flex flex-col gap-4">
                @foreach($teamsForInput as $teamData)
                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="flex items-center justify-between mb-3">
                            <span class="flex items-center">
                                <span
                                    class="w-4 h-4 rounded-full mr-3 border-2 border-gray-400"
                                    style="background-color: {{ $teamData['team_color'] ?? '#999' }}"
                                ></span>
                                <span class="font-semibold text-gray-900">{{ $teamData['team_name'] }}</span>
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-gray-700 font-medium text-sm">Score:</label>
                            <input
                                type="number"
                                wire:change="updateScore('{{ $teamData['team_id'] }}', $event.target.value)"
                                value="{{ $teamData['score'] }}"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded text-center font-bold text-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                min="0"
                            />
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end">
                <flux:spacer />
                <flux:button wire:click="toggleScoreModal" variant="ghost">Done</flux:button>
            </div>
        </div>
    </flux:modal>
</div>