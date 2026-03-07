<?php

use Livewire\Component;

use App\Models\Event;
use App\Models\Team;
use App\Models\Result; 
use Livewire\Attributes\Computed;
use Illuminate\Support\Collection;

new class extends Component
{
    public Event $event;

    #[Computed]
    public function leaderboard(): Collection
    {
        return Team::query()
            ->where('event_id', $this->event->id)
            ->withCount([
                'results as total_score' => fn($query) => $query->select(\DB::raw('COALESCE(SUM(score), 0)'))
            ])
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
<div>
    <!-- Event Header -->
    <div class="mb-8">
        <flux:card>
            <h1 class="text-4xl font-bold mb-2">{{ $this->eventInfo()['name'] }}</h1>
            <p class="mb-4">{{ $this->eventInfo()['description'] }}</p>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                <flux:card>
                    <flux:text class="text-base">Date</flux:text>
                    <flux:badge size="lg"><p class= font-semibold">{{ $this->eventInfo()['date'] }}</p></flux:badge>
                </flux:card>
                <flux:card>
                    <flux:text class="text-base">Competitions</flux:text>
                    <p class= font-semibold">{{ $this->eventInfo()['totalCompetitions'] }}</p>
                </flux:card>
                <flux:card>
                    <flux:text class="text-base">Teams</flux:text>
                    <p class= font-semibold">{{ $this->eventInfo()['totalTeams'] }}</p>
                </flux:card>
            </div>
        </flux:card>
    </div>

    <!-- Leaderboard Table -->
    <flux:card>
        @if($this->leaderboard->isNotEmpty())
            <div class="overflow-x-auto">
                <flux:table class="w-full">
                    <flux:table.columns>
                        <flux:table.column>#</flux:table.column>
                        <flux:table.column>Team</flux:table.column>
                        <flux:table.column class="text-center">Competitions Participated</flux:table.column>
                        <flux:table.column class="text-right">Total Score</flux:table.column> 
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($this->leaderboard as $index => $team)
                            <flux:table.row wire:key="team-{{ $team->id }}">
                                <flux:table.cell>
                                    <div class="flex items-center gap-3">
                                        @if($index === 0)
                                            <span class="text-2xl">🥇</span>
                                        @elseif($index === 1)
                                            <span class="text-2xl">🥈</span>
                                        @elseif($index === 2)
                                            <span class="text-2xl">🥉</span>
                                        @else
                                            <span class="font-semibold">{{ $index + 1 }}</span>
                                        @endif
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex items-center gap-3">
                                        @if($team->color)
                                            <div class="w-4 h-4 rounded-full" style="background-color: {{ $team->color }}"></div>
                                        @else
                                            <div class="w-4 h-4 rounded-full bg-slate-500"></div>
                                        @endif
                                        <span class="font-semibold">{{ $team->name }}</span>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    {{ $team->competitions_participated }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    <span class="text-2xl font-bold text-blue-400">{{ $team->total_score }}</span>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @else
            <div class="px-6 py-12 text-center">
                <p class="">No teams found for this event.</p>
            </div>
        @endif
    </flux:card>
</div> 