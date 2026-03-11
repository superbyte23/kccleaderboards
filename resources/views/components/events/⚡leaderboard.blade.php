<?php

use Livewire\Component;
use App\Models\Event;
use App\Models\Team;
use App\Models\Result;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Illuminate\Support\Collection;

new class extends Component
{
    public Event $event;

    #[On('refresh-leaderboard')]
    public function refresh(): void {}

    #[Computed]
    public function leaderboard(): Collection
    {
        return Team::query()
            ->where('teams.event_id', $this->event->id)
            ->leftJoin('results', 'teams.id', '=', 'results.team_id')
            ->leftJoin('competitions', function ($join) {
                $join->on('results.competition_id', '=', 'competitions.id')
                    ->whereNull('competitions.deleted_at'); // ✅ exclude soft-deleted competitions
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
    public function eventInfo(): array
    {
        return [
            'name'              => $this->event->name,
            'description'       => $this->event->description,
            'date'              => $this->event->event_date->format('F d, Y'),
            'totalCompetitions' => $this->event->competitions()->count(),
            'totalTeams'        => $this->event->teams()->count(),
        ];
    }

    public function mount(Event $event): void
    {
        $this->event = $event;
    }
};
?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

    {{-- Event Header --}}
    <div class="h-auto">
        <flux:card>
            <h1 class="text-4xl font-bold mb-2">{{ $this->eventInfo['name'] }}</h1>
            <p class="mb-4">{{ $this->eventInfo['description'] }}</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                <flux:card>
                    <flux:text class="text-base">Date</flux:text>
                    <p class="font-semibold">{{ $this->eventInfo['date'] }}</p>
                </flux:card>
                <flux:card>
                    <flux:text class="text-base">Competitions</flux:text>
                    <p class="font-semibold">{{ $this->eventInfo['totalCompetitions'] }}</p>
                </flux:card>
                <flux:card>
                    <flux:text class="text-base">Teams</flux:text>
                    <p class="font-semibold">{{ $this->eventInfo['totalTeams'] }}</p>
                </flux:card>
            </div>
        </flux:card>
    </div>

    {{-- Leaderboard Table --}}
    <flux:card class="h-auto">
        @if ($this->leaderboard->isNotEmpty())
            <div class="overflow-x-auto">
                <flux:table class="w-full">
                    <flux:table.columns>
                        <flux:table.column>#</flux:table.column>
                        <flux:table.column>Team</flux:table.column>
                        <flux:table.column class="text-center">Competitions</flux:table.column>
                        <flux:table.column class="text-right">Total Score</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->leaderboard as $index => $team)
                            <flux:table.row wire:key="team-{{ $team->id }}">
                                <flux:table.cell>
                                    @if ($index === 0)
                                        <span class="text-2xl">🥇</span>
                                    @elseif ($index === 1)
                                        <span class="text-2xl">🥈</span>
                                    @elseif ($index === 2)
                                        <span class="text-2xl">🥉</span>
                                    @else
                                        <span class="font-semibold">{{ $index + 1 }}</span>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex items-center gap-3">
                                        <div class="w-4 h-4 rounded-full"
                                            style="background-color: {{ $team->color ?? '#64748b' }}"></div>
                                        <span class="font-semibold">{{ $team->name }}</span>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell class="text-center">
                                    {{ $team->competitions_participated }}
                                </flux:table.cell>
                                <flux:table.cell class="text-right">
                                    <span class="text-2xl font-bold text-blue-400">
                                        {{ number_format($team->total_score) }}
                                    </span>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @else
            <div class="px-6 py-12 text-center">
                <p>No teams found for this event.</p>
            </div>
        @endif
    </flux:card>

</div>