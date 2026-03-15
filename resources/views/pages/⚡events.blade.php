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
            $this->toastSuccess('notify', 'Event updated successfully!');
        } else {
            Event::create([
                'name' => $this->name,
                'description' => $this->description,
                'event_date' => $this->event_date,
            ]);
            $this->toastSuccess('notify', 'Event created successfully!');
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
            $this->toastSuccess('notify', 'Event deleted successfully!');
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

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold">Events Management</h1>
        <flux:button icon="plus" wire:click="openCreateModal" variant="primary">Create Event</flux:button>
    </div>

    <!-- Search Bar -->
    <div class="w-full">
        <flux:input
            wire:model.live="search"
            type="search"
            placeholder="Search events..."
            icon="magnifying-glass"
        />
    </div>

    <!-- Events Table -->
    <flux:card>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">Name</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">Description</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">Date</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($events as $event)
                        <tr wire:key="event-{{ $event->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $event->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ Str::limit($event->description, 50) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $event->event_date->format('F m, Y') }}</td>
                            <td class="px-6 py-4 text-sm space-x-2 flex">
                                <flux:button
                                    href="/event-dashboard/{{ $event->id }}"
                                    wire:navigate
                                    variant="subtle"
                                    size="sm"
                                    icon="eye"
                                > 
                                    View Dashboard
                                </flux:button>
                                <flux:button
                                    wire:click="openEditModal('{{ $event->id }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="openEditModal"
                                    variant="subtle"
                                    size="sm"
                                    icon="pencil"
                                > 
                                    Edit
                                </flux:button>
                                <flux:button
                                    wire:click="confirmDelete('{{ $event->id }}')"
                                    variant="subtle"
                                    size="sm"
                                    class="text-red-600 dark:text-red-400"
                                    icon="trash"
                                > 
                                    Delete
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                No events found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($events->count())
            <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-4">
                {{ $events->links() }}
            </div>
        @endif
    </flux:card>

    <!-- Create/Edit Modal -->
    <flux:modal name="eventModal" wire:model="showModal" class="md:w-96">
        <div class="space-y-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                {{ $editingId ? 'Edit Event' : 'Create New Event' }}
            </h2>

            <div class="space-y-4">
                <div>
                    <flux:input
                        wire:model="name"
                        label="Event Name"
                        placeholder="Enter event name"
                        type="text"
                    />
                    @error('name')
                        <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <flux:textarea
                        wire:model="description"
                        label="Description"
                        placeholder="Enter event description"
                    />
                    @error('description')
                        <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <flux:input
                        wire:model="event_date"
                        label="Event Date"
                        type="date"
                    />
                    @error('event_date')
                        <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="flex gap-3 justify-end border-t border-gray-200 dark:border-gray-700 pt-4">
                <flux:button wire:click="closeModal" variant="subtle">
                    Cancel
                </flux:button>
                <flux:button wire:click="save" variant="primary">
                    {{ $editingId ? 'Update' : 'Create' }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal name="deleteConfirm" wire:model="showDeleteConfirm" class="md:w-96">
        <div class="space-y-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Confirm Delete</h2>
            <p class="text-gray-600 dark:text-gray-400">
                Are you sure you want to delete this event? This action cannot be undone.
            </p>

            <div class="flex gap-3 justify-end border-t border-gray-200 dark:border-gray-700 pt-4">
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