<?php

use App\Models\Event;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate; 
use App\Livewire\Concerns\HasSileoToasts;

new class extends Component {
    use HasSileoToasts;
    use WithPagination;

    #[Validate('required|string|min:3|max:255')]
    public $name = '';

    #[Validate('required|string|min:10')]
    public $description = '';

    #[Validate('required|date')]
    public $event_date = '';

    public $search = '';
    public $editingId = null;
    public $showModal = false;
    public $showDeleteConfirm = false;
    public $deleteId = null;

    public function render()
    {
        return $this->view([
            'events' => Event::when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10),
        ]);
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEditModal($id)
    {
        $event = Event::find($id);
        if ($event) {
            $this->editingId = $id;
            $this->name = $event->name;
            $this->description = $event->description;
            $this->event_date = $event->event_date->format('Y-m-d');
            $this->showModal = true;
        }
    }

    public function save()
    {
        $this->validate();

        if ($this->editingId) {
            $event = Event::find($this->editingId);
            $event->update([
                'name' => $this->name,
                'description' => $this->description,
                'event_date' => $this->event_date,
            ]);
            $this->toastSuccess('Success!', 'Event updated successfully!');
        } else {
            Event::create([
                'name' => $this->name,
                'description' => $this->description,
                'event_date' => $this->event_date,
            ]);
            $this->toastSuccess('Success!', 'Event created successfully!');
        }

        $this->resetForm();
        $this->showModal = false;
    }

    public function confirmDelete($id)
    {
        $this->deleteId = $id;
        $this->showDeleteConfirm = true;
    }

    public function delete()
    {
        $event = Event::findOrFail($this->deleteId);
        // dd($event);
        if ($event) {
            $event->delete();
            $this->toastSuccess('Success!', 'Event deleted successfully!');
            $this->showDeleteConfirm = false;
            $this->deleteId = null;
        }
    }

    public function closeModal()
    {
        $this->resetForm();
        $this->showModal = false;
    }

    private function resetForm()
    {
        $this->name = '';
        $this->description = '';
        $this->event_date = '';
        $this->editingId = null;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }
}; ?>

<div class="mx-auto max-w-7xl space-y-6 p-4 pt-5">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-extrabold tracking-tight text-white md:text-4xl">Events management</h1>
        </div>
        <flux:button icon="plus" wire:click="openCreateModal" variant="primary">Create event</flux:button>
    </div>

    <div class="w-full">
        <flux:input
            wire:model.live="search"
            type="search"
            placeholder="Search events..."
            icon="magnifying-glass"
        />
    </div>

    <div class="space-y-3">
        @forelse($events as $event)
            <div wire:key="event-{{ $event->id }}" class="panel p-4">
                <div class="flex items-center gap-3">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl border border-line bg-canvas-soft text-gold-300">
                        <flux:icon name="trophy" class="size-4.5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold text-white">{{ $event->name }}</p>
                        <p class="truncate text-xs text-zinc-500">{{ Str::limit($event->description, 50) }}</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between gap-3 border-t border-line pt-3">
                    <span class="font-mono text-xs tabular-nums text-zinc-400">{{ $event->event_date->format('M j, Y') }}</span>
                    <div class="flex shrink-0 items-center gap-1">
                        <flux:button
                            href="/event-dashboard/{{ $event->id }}"
                            wire:navigate
                            variant="subtle"
                            size="sm"
                            square
                        >
                            <x-tabler-icon name="scan-eye" class="size-4 text-gold-300" />
                        </flux:button>
                        <flux:button
                            wire:click="openEditModal('{{ $event->id }}')"
                            wire:loading.attr="disabled"
                            wire:target="openEditModal"
                            variant="ghost"
                            size="sm"
                            square
                        >
                            <x-tabler-icon name="pencil-cog" class="size-4 text-zinc-300" />
                        </flux:button>
                        <flux:button
                            wire:click="confirmDelete('{{ $event->id }}')"
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
            <div class="panel px-4 py-16 text-center text-zinc-400">No events found.</div>
        @endforelse
    </div>

    @if($events->count())
        <div class="flex justify-center">
            {{ $events->links() }}
        </div>
    @endif

    <flux:modal name="eventModal" wire:model="showModal" flyout position="bottom" class="modal-sheet">
        <div class="space-y-4">
            <h2 class="font-display text-2xl font-bold tracking-tight text-white">
                {{ $editingId ? 'Edit event' : 'Create new event' }}
            </h2>

            <div class="space-y-4">
                <div>
                    <flux:input
                        wire:model="name"
                        label="Event name"
                        placeholder="Enter event name"
                        type="text"
                    />
                    @error('name')
                        <span class="text-sm text-red-400">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <flux:textarea
                        wire:model="description"
                        label="Description"
                        placeholder="Enter event description"
                    />
                    @error('description')
                        <span class="text-sm text-red-400">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <flux:input
                        wire:model="event_date"
                        label="Event date"
                        type="date"
                    />
                    @error('event_date')
                        <span class="text-sm text-red-400">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-line pt-4">
                <flux:button wire:click="closeModal" variant="subtle">
                    <x-tabler-icon name="x" class="size-4" />
                    Cancel
                </flux:button>
                <flux:button wire:click="save" variant="primary">
                    <x-tabler-icon name="device-floppy" class="size-4" />
                    {{ $editingId ? 'Update' : 'Create' }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="deleteConfirm" wire:model="showDeleteConfirm" flyout position="bottom" class="modal-sheet">
        <div class="space-y-4">
            <h2 class="font-display text-2xl font-bold tracking-tight text-white">Confirm delete</h2>
            <p class="text-zinc-400">
                Are you sure you want to delete this event? This action cannot be undone.
            </p>

            <div class="flex justify-end gap-3 border-t border-line pt-4">
                <flux:button
                    wire:click="$set('showDeleteConfirm', false)"
                    variant="subtle"
                >
                    Cancel
                </flux:button>
                <flux:button
                    wire:click="delete"
                    variant="danger"
                >
                    Delete
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>