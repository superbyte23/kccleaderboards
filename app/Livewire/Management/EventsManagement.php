<?php

namespace App\Livewire\Management;

use App\Models\Event;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Validate;

class EventsManagement extends Component
{
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
        $events = Event::when(! auth()->user()->isAdmin(), function ($query) {
            $query->where('user_id', auth()->id());
        })
        ->when($this->search, function ($query) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
        })
        ->orderBy('created_at', 'desc')
        ->paginate(10);

        return view('livewire.management.events-management', [
            'events' => $events,
        ]);
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEditModal($id)
    {
        $event = Event::findOrFail($id);
        abort_unless(auth()->user()->can('update', $event), 403);

        $this->editingId = $id;
        $this->name = $event->name;
        $this->description = $event->description;
        $this->event_date = $event->event_date;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        if ($this->editingId) {
            $event = Event::findOrFail($this->editingId);
            abort_unless(auth()->user()->can('update', $event), 403);

            $event->update([
                'name' => $this->name,
                'description' => $this->description,
                'event_date' => $this->event_date,
            ]);
            $this->dispatch('notify', message: 'Event updated successfully!', type: 'success');
        } else {
            Event::create([
                'user_id' => auth()->id(),
                'name' => $this->name,
                'description' => $this->description,
                'event_date' => $this->event_date,
            ]);
            $this->dispatch('notify', message: 'Event created successfully!', type: 'success');
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
        if ($this->deleteId) {
            $event = Event::findOrFail($this->deleteId);
            abort_unless(auth()->user()->can('delete', $event), 403);

            $event->delete();
            $this->dispatch('notify', message: 'Event deleted successfully!', type: 'success');
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
}
?>
