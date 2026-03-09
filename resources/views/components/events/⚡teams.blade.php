<?php

use Livewire\Component;
use App\Models\Event;
use Livewire\WithFileUploads;
use App\Livewire\Concerns\HasSileoToasts;

new class extends Component {
    use HasSileoToasts;
    use WithFileUploads;
    public Event $event;
    public $teams = [];

    // fields for team creation/editing
    public $name = '';
    public $color = '';
    public $avatar = '';
    public $represents = null;
    public $showTeamModal = false;
    public $isEditingTeam = null;
    public $isDeletingTeam = null;

    public $showDeleteConfirm = false;

    public function mount(Event $event)
    {
        $this->event = $event;
        $this->getEventTeams(); // Load teams when the component mounts
        
    }

    public function getEventTeams()
    {
        $this->teams = $this->event->teams()->get();
        $this->dispatch('refresh-leaderboard');
    }

    public function openTeamModal()
    {
        $this->showTeamModal = true;
    }

    public function closeTeamModal()
    {
        $this->resetForm();
        $this->showTeamModal = false;
    }

    public function resetForm()
    {
        // reset form fields if needed
        $this->name = '';
        $this->color = '';
        $this->represents = '';
        $this->avatar = null;
        $this->isEditingTeam = null;
    }

    public function saveTeam()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:7',
            'represents' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|max:2048', // Max 2MB
        ]);

        $data = [
            'name' => $this->name,
            'color' => $this->color,
            'represents' => $this->represents,
        ];

        // Handle Avatar Upload
        if ($this->avatar) {
            $data['avatar'] = $this->avatar->store('avatars', 'public');
        }

        if ($this->isEditingTeam) {
            $team = $this->event->teams()->find($this->isEditingTeam);

            if ($team) {

                // delete old avatar ONLY if new one uploaded
                if ($this->avatar && $team->avatar) {
                    Storage::disk('public')->delete($team->avatar);
                }

                $team->update($data);
                $this->getEventTeams();
                $this->closeTeamModal();
            }

        } else {
            $this->event->teams()->create($data);
            $this->getEventTeams();
            $this->closeTeamModal();
        }

        $this->toastSuccess('Success!', $this->isEditingTeam ? 'Team updated Successfully.' : 'Team created Successfully.');
    }
    
    public function editTeam($teamId)
    {
        $team = $this->event->teams()->find($teamId);

        if (! $team) {
            $this->toastError('Error!', 'Team not found.'); 
        }

        $this->name = $team->name;
        $this->color = $team->color;
        $this->represents = $team->represents;
        $this->isEditingTeam = $teamId;
        $this->showTeamModal = true;
    }

    public function confirmDeleteTeam($teamId)
    {
        $this->isDeletingTeam = $teamId;
        $this->showDeleteConfirm = true;
    }

    public function deleteTeam()
    {
        if (!$this->isDeletingTeam) {
            $this->toastError('Error!', 'No team selected for deletion.'); 
            return;
        }

        $team = $this->event->teams()->find($this->isDeletingTeam);

        if (! $team) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Team not found.',
            ]);
            return;
        }

        $team->delete();
        $this->getEventTeams(); // Refresh the list of teams
        $this->showDeleteConfirm = false; // Close the confirmation modal
        $this->isDeletingTeam = null; // Reset the editing team

        $this->toastSuccess('Success!', 'Team deleted successfully.');
    }
};
?>

<div>

    {{-- 2. List of Teams (Houses) --}}
    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <flux:heading size="xl">Participating Teams</flux:heading>

            <flux:button wire:click="openTeamModal" icon="plus">Add Team</flux:button>

        </div>

        <flux:card>
            <flux:table>
                <flux:table.columns> 
                    <flux:table.column>Avatar</flux:table.column>
                    <flux:table.column>Team Name</flux:table.column>
                    {{-- <flux:table.column>Global ID</flux:table.column> --}}
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($teams as $team)
                        <flux:table.row wire:key="team-{{ $team->id }}"> 
                            <flux:table.cell><flux:avatar src="{{ $team->avatar ? asset('storage/' . $team->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($team->name) }}" /></flux:table.cell>
                            <flux:table.cell font="medium">
                                <flux:badge style="background-color: {{ $team->color }};" >{{ $team->name }}</flux:badge>
                            </flux:table.cell>
                            {{-- <flux:table.cell>{{ $team->id }}</flux:table.cell> --}}
                            <flux:table.cell>
                                <flux:button 
                                    wire:click="editTeam('{{ $team->id }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="editTeam"
                                    variant="ghost"
                                    icon="pencil-square"></flux:button> 
                                <flux:button icon="trash" 
                                    wire:click="confirmDeleteTeam('{{ $team->id }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="confirmDeleteTeam"
                                    variant="ghost"
                                    ></flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>

            </flux:table>
        </flux:card>
    </section> 
    <flux:modal wire:model.self="showTeamModal" class="md:w-96">
        <form wire:submit="saveTeam">
            <div class="space-y-6">
                <flux:heading size="lg">{{ $isEditingTeam ? "Edit Team" : "Add Team" }}</flux:heading>
                
                <flux:input label="Name" wire:model="name" placeholder="Team name" />
                
                <flux:input label="Represents" wire:model="represents" placeholder="e.g. University of Science" />

                <flux:input label="Color" wire:model="color" type="color" />

                <flux:field>
                    <flux:label>Team Avatar</flux:label>
                    <input type="file" wire:model="avatar" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                    @error('avatar') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror

                    <div wire:loading wire:target="avatar" class="text-xs text-blue-500 mt-1">
                        Uploading to server...
                    </div>
                    
                    {{-- Preview Section --}}
                    <div class="mt-2">
                        @if ($avatar && !is_string($avatar))
                            {{-- New file selected --}}
                            <img src="{{ $avatar->temporaryUrl() }}" class="w-16 h-16 rounded-full border">
                        @elseif ($isEditingTeam && ($currentTeam = $teams->find($isEditingTeam)) && $currentTeam->avatar)
                            {{-- Existing file from database --}}
                            <img src="{{ asset('storage/' . $currentTeam->avatar) }}" class="w-16 h-16 rounded-full border">
                        @endif
                    </div>
                </flux:field>

                <div class="flex space-x-2 justify-end">
                    <flux:button wire:click="closeTeamModal">Cancel</flux:button>
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
    <flux:modal wire:model="showDeleteConfirm" class="min-w-[22rem]">
        <form wire:submit.prevent="deleteTeam">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Delete Team</flux:heading>
                    <flux:text class="mt-2">
                        Are you sure you want to delete this team? This action cannot be undone.
                    </flux:text>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    {{-- This button closes the modal using Flux's internal logic --}}
                    <flux:button wire:click="$set('showDeleteConfirm', false)">Cancel</flux:button>
                    <flux:button type="submit" variant="danger">Delete Team</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
