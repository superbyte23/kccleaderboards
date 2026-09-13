<?php

use App\Actions\OptimizeAvatar;
use App\Livewire\Concerns\HasSileoToasts;
use App\Models\Event;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
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
        $this->authorize('view', $event);

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
        $this->authorize('update', $this->event);

        $this->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:7',
            'represents' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|max:5120', // 5MB safety net; client compresses first
        ], [
            'avatar.max' => 'That image is still too large after compression (limit 5 MB). Please pick a smaller photo.',
            'avatar.image' => 'The avatar must be a JPG, PNG, WebP or GIF image.',
        ]);

        $data = [
            'name' => $this->name,
            'color' => $this->color,
            'represents' => $this->represents,
        ];

        // Handle Avatar Upload
        if ($this->avatar) {
            $data['avatar'] = OptimizeAvatar::run($this->avatar);
        }

        if ($this->isEditingTeam) {
            $team = $this->event->teams()->find($this->isEditingTeam);

            if ($team) {

                // delete old avatar ONLY if new one uploaded
                if ($this->avatar && $team->avatar) {
                    OptimizeAvatar::delete($team->avatar);
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
        $this->authorize('update', $this->event);

        if (! $this->isDeletingTeam) {
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
    <section>
        <div class="sticky top-0 z-20 -mx-1 mb-3 flex items-center justify-between gap-3 border-b border-line bg-canvas px-1 py-2">
            <h2 class="font-display text-xl font-bold tracking-tight text-white">Participating teams</h2>
            <flux:button wire:click="openTeamModal" icon="plus" size="sm" variant="primary">Add team</flux:button>
        </div>

        <div class="space-y-2">
            @forelse ($teams as $team)
                <div wire:key="team-{{ $team->id }}" class="rounded-xl border border-line bg-canvas-soft p-3">
                    <div class="flex items-center gap-3">
                        <flux:avatar src="{{ $team->avatar ? asset('storage/' . $team->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($team->name) }}" size="sm" class="shrink-0" />
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="truncate font-semibold text-white">{{ $team->name }}</p>
                                <span class="size-2 shrink-0 rounded-full" style="background-color: {{ $team->color }}"></span>
                            </div>
                            <p class="truncate text-xs text-zinc-500">{{ $team->represents }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            <flux:button
                                wire:click="editTeam('{{ $team->id }}')"
                                wire:loading.attr="disabled"
                                wire:target="editTeam"
                                variant="ghost"
                                size="sm"
                                square
                            >
                                <x-tabler-icon name="pencil-cog" class="size-4 text-zinc-300" />
                            </flux:button>
                            <flux:button
                                wire:click="confirmDeleteTeam('{{ $team->id }}')"
                                wire:loading.attr="disabled"
                                wire:target="confirmDeleteTeam"
                                variant="ghost"
                                size="sm"
                                square
                            >
                                <x-tabler-icon name="trash-x" class="size-4 text-red-400" />
                            </flux:button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-12 text-center text-sm text-zinc-400">No teams yet. Add your first team.</div>
            @endforelse
        </div>
    </section>

    <flux:modal wire:model.self="showTeamModal" flyout position="bottom" class="modal-sheet">
        <form wire:submit="saveTeam">
            <div class="space-y-6">
                <flux:heading size="lg" class="font-display font-bold tracking-tight text-white">{{ $isEditingTeam ? "Edit team" : "Add team" }}</flux:heading>

                <flux:input label="Name" wire:model="name" placeholder="Team name" />

                <flux:input label="Represents" wire:model="represents" placeholder="e.g. College of Arts" />

                <flux:input label="Color" wire:model="color" type="color" />

                <flux:field>
                    <flux:label>Team avatar</flux:label>

                    <div x-data="avatarCrop()">
                        <input type="file" accept="image/*" x-ref="file" class="block w-full cursor-pointer rounded-xl border border-line bg-canvas-soft text-sm text-zinc-500 file:mr-4 file:border-0 file:bg-gold-400 file:px-4 file:py-2 file:text-sm file:font-bold file:text-gold-950 hover:file:bg-gold-300" />

                        @error('avatar') <span class="text-xs text-red-400">{{ $message }}</span> @enderror

                        <div wire:loading wire:target="avatar" class="mt-1 text-xs text-gold-300">
                            Uploading to server...
                        </div>

                        <template x-if="loaded">
                            <div class="mt-2 space-y-2">
                                <div class="relative select-none touch-none" x-ref="frame">
                                    <img :src="previewUrl" :style="{ width: displayW + 'px', height: displayH + 'px' }" class="rounded-xl" draggable="false" />

                                    <div :style="boxStyle()" class="absolute top-0 left-0 cursor-move rounded-lg ring-2 ring-gold-400" style="box-shadow: 0 0 0 9999px rgba(0,0,0,0.5)" @pointerdown="startMove($event, 'move')">
                                        <div class="absolute -bottom-1.5 -right-1.5 size-4 cursor-nwse-resize rounded-sm border-2 border-gold-400 bg-gold-950" @pointerdown.stop="startMove($event, 'resize')"></div>
                                    </div>
                                </div>

                                <div class="flex gap-2">
                                    <flux:button @click="reset()" variant="subtle" size="sm">Cancel</flux:button>
                                    <flux:button @click="applyCrop()" variant="primary" size="sm">
                                        <template x-if="busy"><span class="animate-pulse">Cropping…</span></template>
                                        <template x-if="!busy"><span>Crop &amp; upload</span></template>
                                    </flux:button>
                                </div>
                            </div>
                        </template>

                        <template x-if="!loaded && uploading">
                            <div class="mt-2 text-xs text-gold-300 animate-pulse">Compressing & uploading…</div>
                        </template>

                        <template x-if="!loaded && !uploading">
                            <div class="mt-2">
                                @if ($isEditingTeam && ($currentTeam = $teams->find($isEditingTeam)) && $currentTeam->avatar)
                                    <img src="{{ asset('storage/' . $currentTeam->avatar) }}" class="size-16 rounded-xl border border-line object-cover">
                                @endif
                            </div>
                        </template>
                    </div>
                </flux:field>

                <div class="flex justify-end gap-2">
                    <flux:button wire:click="closeTeamModal" variant="subtle">
                        <x-tabler-icon name="x" class="size-4" />
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        <x-tabler-icon name="device-floppy" class="size-4" />
                        Save changes
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showDeleteConfirm" flyout position="bottom" class="modal-sheet">
        <form wire:submit.prevent="deleteTeam">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg" class="font-display font-bold tracking-tight text-white">Delete team</flux:heading>
                    <flux:text class="mt-2">
                        Are you sure you want to delete this team? This action cannot be undone.
                    </flux:text>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button wire:click="$set('showDeleteConfirm', false)" variant="ghost">Cancel</flux:button>
                    <flux:button type="submit" variant="danger">Delete team</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>