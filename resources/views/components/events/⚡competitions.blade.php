<?php

use Livewire\Component;
use App\Models\Event;
use App\Models\Competition;

new class extends Component
{
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
        $this->event = $event;
        $this->getEventComps();
    }

    public function getEventComps()
    {
        $this->competitions = $this->event->competitions()->get();
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
            $this->dispatch('toast', ['type'=>'success','message'=>'Competition updated.']);
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
        }
        $this->dispatch('toast', ['type'=>'success','message'=>'Competition created.']);
    }

    public function editComp($id)
    {
        $comp = $this->event->competitions()->find($id);
        if (! $comp) {
            $this->dispatch('toast',['type'=>'error','message'=>'Not found']);
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
        if (! $this->isDeletingComp) {
            $this->dispatch('toast',['type'=>'error','message'=>'No comp selected']);
            return;
        }
        $comp = $this->event->competitions()->find($this->isDeletingComp);
        if ($comp) {
            $comp->delete();
            $this->getEventComps();
            $this->showDeleteConfirm = false;
            $this->isDeletingComp = null;
            $this->dispatch('toast',['type'=>'success','message'=>'Competition removed']);
        }
    }
};
?>

<div>
    <section class="space-y-4">
        <div class="flex items:center justify-between">
            <flux:heading size="xl">Competitions</flux:heading>
            <flux:button wire:click="openCompModal" icon="plus">Add Competition</flux:button>
        </div>

        <flux:card>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Activity</flux:table.column>
                    <flux:table.column>Category</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($competitions as $comp)
                        <flux:table.row wire:key="comp-{{ $comp->id }}">
                            <flux:table.cell font="medium">{{ $comp->name }}</flux:table.cell>
                            <flux:table.cell>{{ $comp->category }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:button :href="route('competition-dashboard', $comp)" icon="eye" variant="ghost" wire:navigate></flux:button>
                                <flux:button wire:click="editComp('{{ $comp->id }}')" icon="pencil-square" variant="ghost"></flux:button>
                                <flux:button wire:click="confirmDeleteComp('{{ $comp->id }}')" icon="trash" variant="ghost"></flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </section>
    

    <flux:modal wire:model.self="showCompModal" class="md:w-96">
        <form wire:submit="saveComp">
            <div class="space-y-6">
                <flux:input label="Name" wire:model="name" />
                <flux:select label="Category" wire:model="category">
                    <option value="">Select a category</option>
                    <option value="Sports">Sports</option>
                    <option value="Cultural">Cultural</option>
                    <option value="Academic">Academic</option>
                    <option value="Creative Arts">Creative Arts</option>
                    <option value="Science and Tech">Science and Tech</option>
                </flux:select>
                <div class="flex gap-2 justify-end">
                    <flux:spacer />
                    <flux:button type="button" wire:click="closeCompModal" variant="subtle">Cancel</flux:button>
                    <flux:button type="submit" variant="primary">Save</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model.self="showDeleteConfirm" class="min-w-[22rem]">
        <form wire:submit.prevent="deleteComp">
            <div class="space-y-6">
                <flux:heading size="lg">Delete Competition</flux:heading>
                <flux:text>You;&rsquo;re about to delete this competition. This action cannot be reversed.</flux:text>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button>Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="danger">Delete</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>