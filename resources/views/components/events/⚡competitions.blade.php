<?php

use Livewire\Component;
use App\Models\Event;
use App\Models\Competition;
use App\Livewire\Concerns\HasSileoToasts;

new class extends Component {
    use HasSileoToasts;
    public Event $event;
    public $competitions = [];

    // fields for competition creation/editing
    public $name = '';
    public $category = '';

    public $showCompModal = false;
    public $isEditingComp = null;
    public $isDeletingComp = null;

    public $showDeleteConfirm = false;

    public function mount(Event $event)
    {
        $this->authorize('view', $event);

        $this->event = $event;
        $this->getEventComps();
        $this->dispatch('refresh-leaderboard');
    }

    public function getEventComps()
    {
        $this->competitions = $this->event
            ->competitions()
            ->with(['results.team']) // Eager load to avoid N+1 issues
            ->latest()
            ->get()
            ->map(function ($comp) {
                // Find the result with the highest score for this specific competition
                $winnerResult = $comp->results()->orderByDesc('score')->first();
                $comp->winner_name = $winnerResult ? $winnerResult->team->name : 'No winner yet';
                $comp->winner_color = $winnerResult ? $winnerResult->team->color : null;
                return $comp;
            });
        $this->dispatch('refresh-leaderboard');
    }

    public function openCompModal()
    {
        $this->showCompModal = true;
    }

    public function closeCompModal()
    {
        $this->resetForm();
        $this->showCompModal = false;
    }

    public function resetForm()
    {
        $this->name = '';
        $this->category = '';
        $this->isEditingComp = null;
    }

    public function saveComp()
    {
        $this->authorize('update', $this->event);

        $this->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string',
        ]);

        if ($this->isEditingComp) {
            $comp = $this->event->competitions()->find($this->isEditingComp);
            if ($comp) {
                $comp->update([
                    'name' => $this->name,
                    'category' => $this->category,
                ]);
                $this->getEventComps();
                $this->closeCompModal();
            }
            $this->toastSuccess('Success!', 'Competition updated.');
            return;
        }

        $comp = $this->event->competitions()->create([
            'event_id' => $this->event->id,
            'name' => $this->name,
            'category' => $this->category,
        ]);

        if ($comp) {
            $this->resetForm();
            $this->getEventComps();
            $this->closeCompModal();
            $this->toastSuccess('Success!', 'Competition created successfully.');
        }
    }

    public function editComp($id)
    {
        $comp = $this->event->competitions()->find($id);
        if (!$comp) {
            $this->toastError('Error!', 'Competition not found');
            return;
        }
        $this->name = $comp->name;
        $this->category = $comp->category;
        $this->isEditingComp = $id;
        $this->showCompModal = true;
    }

    public function confirmDeleteComp($id)
    {
        $this->isDeletingComp = $id;
        $this->showDeleteConfirm = true;
    }

    public function deleteComp()
    {
        $this->authorize('update', $this->event);

        if (!$this->isDeletingComp) {
            $this->toastError('Error!', 'No comp selected');
            return;
        }
        $comp = $this->event->competitions()->find($this->isDeletingComp);
        if ($comp) {
            $comp->delete();
            $this->getEventComps();
            $this->showDeleteConfirm = false;
            $this->isDeletingComp = null;
            $this->toastSuccess('Success!', 'Competition removed.');
        }
    }
};
?>

<div>
    <section>
        <div class="sticky top-0 z-20 -mx-1 mb-3 flex items-center justify-between gap-3 border-b border-line bg-canvas px-1 py-2">
            <h2 class="font-display text-xl font-bold tracking-tight text-white">Competitions</h2>
            <flux:button wire:click="openCompModal" wire:loading.attr="disabled" wire:target="openCompModal" icon="plus" size="sm" variant="primary">Add competition</flux:button>
        </div>

        <div class="space-y-2">
            @forelse ($competitions as $comp)
                <div wire:key="comp-{{ $comp->id }}" class="rounded-xl border border-line bg-canvas-soft p-3">
                    <div class="flex items-center justify-between gap-3">
                        <span class="rounded-full bg-gold-400/10 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-widest text-gold-300 ring-1 ring-gold-400/20">{{ $comp->category }}</span>
                        <div class="flex shrink-0 items-center gap-1">
                            <flux:button
                                :href="route('competition-dashboard', $comp)"
                                variant="subtle"
                                size="sm"
                                square
                                wire:navigate
                            >
                                <x-tabler-icon name="scan-eye" class="size-4 text-gold-300" />
                            </flux:button>
                            <flux:button
                                wire:click="editComp('{{ $comp->id }}')"
                                wire:loading.attr="disabled"
                                wire:target="editComp"
                                variant="ghost"
                                size="sm"
                                square
                            >
                                <x-tabler-icon name="pencil-cog" class="size-4 text-zinc-300" />
                            </flux:button>
                            <flux:button
                                wire:click="confirmDeleteComp('{{ $comp->id }}')"
                                wire:loading.attr="disabled"
                                wire:target="confirmDeleteComp"
                                variant="ghost"
                                size="sm"
                                square
                            >
                                <x-tabler-icon name="trash-x" class="size-4 text-red-400" />
                            </flux:button>
                        </div>
                    </div>
                    <p class="mt-2 truncate font-semibold text-white">{{ $comp->name }}</p>
                    <div class="mt-2 flex items-center gap-2 pt-1">
                        <p class="shrink-0 text-[10px] font-bold uppercase tracking-widest text-zinc-500">Winner</p>
                        @if ($comp->winner_name !== 'No winner yet')
                            <p class="min-w-0 flex-1 truncate text-right text-sm font-semibold text-white">{{ $comp->winner_name }}</p>
                        @else
                            <div class="flex-1 text-right">
                                <flux:badge size="sm" variant="subtle">Pending</flux:badge>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-12 text-center text-sm text-zinc-400">No competitions yet. Add your first one.</div>
            @endforelse
        </div>
    </section>

    <flux:modal wire:model.self="showCompModal" flyout position="bottom" class="modal-sheet">
        <form wire:submit="saveComp">
            <div class="space-y-6">
                <flux:heading size="lg" class="font-display font-bold tracking-tight text-white">{{ $isEditingComp ? 'Edit competition' : 'Add competition' }}</flux:heading>
                <flux:input label="Name" wire:model="name" />
                <flux:select label="Category" wire:model="category">
                    <option value="">Select a category</option>
                    <option value="Sports">Sports</option>
                    <option value="Cultural">Cultural</option>
                    <option value="Academic">Academic</option>
                    <option value="Creative Arts">Creative Arts</option>
                    <option value="Science and Tech">Science and Tech</option>
                </flux:select>
                <div class="flex justify-end gap-2">
                    <flux:spacer />
                    <flux:button type="button" wire:click="closeCompModal" variant="subtle">
                        <x-tabler-icon name="x" class="size-4" />
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        <x-tabler-icon name="device-floppy" class="size-4" />
                        Save
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model.self="showDeleteConfirm" flyout position="bottom" class="modal-sheet">
        <form wire:submit.prevent="deleteComp">
            <div class="space-y-6">
                <flux:heading size="lg" class="font-display font-bold tracking-tight text-white">Delete competition</flux:heading>
                <flux:text>You're about to delete this competition. This action cannot be reversed.</flux:text>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="subtle">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="danger">Delete</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>