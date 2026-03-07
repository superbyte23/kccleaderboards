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
        $events = Event::when($this->search, function ($query) {
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
        $event = Event::find($id);
        if ($event) {
            $this->editingId = $id;
            $this->name = $event->name;
            $this->description = $event->description;
            $this->event_date = $event->event_date;
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
            $this->dispatch('notify', message: 'Event updated successfully!', type: 'success');
        } else {
            Event::create([
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
            Event::destroy($this->deleteId);
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
