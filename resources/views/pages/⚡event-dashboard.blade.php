<?php

use Livewire\Component;
use App\Models\Event;

new class extends Component
{
    // attributes and methods for the event dashboard will go here
    public Event $event; 
    public $teams = [];
    public $competitions = [];

    public $showTeamModal = false;
    public $showCompetitionModal = false;

    public function mount(Event $event)
    {
        $this->authorize('view', $event);

        $this->event = $event;
        $this->teams = $event->teams()->get();
        $this->competitions = $event->competitions()->get();
    }

};
?>

<div class="mx-auto max-w-7xl space-y-6 p-4 pt-5">
    <div>
        <flux:button :href="route('events')" variant="subtle" size="sm" wire:navigate>Back to events</flux:button>
    </div>
    <livewire:events.leaderboard :event="$event" />
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <livewire:events.teams :event="$event" />
        <livewire:events.competitions :event="$event" />
    </div>
</div>
