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
        $this->event = $event;
        $this->teams = $event->teams()->get();
        $this->competitions = $event->competitions()->get();
    }

};
?>

<div>
    <div class="space-y-6"> 
        <livewire:events.leaderboard :event="$event" /> 
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8"> 
            <livewire:events.teams :event="$event" />
            <livewire:events.competitions :event="$event" /> 
        </div> 
    </div> 
</div>