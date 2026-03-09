<?php

use Livewire\Component;
use App\Models\User;

new class extends Component
{
     public $users;

    public $userId;
    public $name;
    public $email;
    public $password;

    public $showModal = false;
    public $isEdit = false;

    protected function rules()
    {
        return [
            'name' => 'required|min:3',
            'email' => 'required|email',
        ];
    }

    public function boot()
    {
        $this->users = User::latest()->get(); 
    }

    public function create()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);

        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;

        $this->isEdit = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        if ($this->isEdit) {

            $user = User::findOrFail($this->userId);

            $user->update([
                'name' => $this->name,
                'email' => $this->email,
            ]);

        } else {

            User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => $this->password
            ]);

        }

        $this->resetForm();
    }

    public function delete($id)
    {
        User::findOrFail($id)->delete();
    }

    public function resetForm()
    {
        $this->reset([
            'userId',
            'name',
            'email',
            'password',
            'showModal',
            'isEdit'
        ]);
    }
};
?>

<div>

    <flux:card>

        <div class="flex justify-between mb-4">

            <h2 class="text-xl font-bold">
                Users
            </h2>

            <flux:button
                wire:click="create"
                variant="primary"
            >
                Add User
            </flux:button>

        </div>


        <flux:table>

            <flux:table.columns>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Email</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>

                @foreach($users as $user)

                    <flux:table.row>

                        <flux:table.cell>
                            {{ $user->name }}
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $user->email }}
                        </flux:table.cell>

                        <flux:table.cell>

                            <flux:button
                                size="sm"
                                wire:click="edit({{ $user->id }})"
                            >
                                Edit
                            </flux:button>

                            <flux:button
                                size="sm"
                                variant="danger"
                                wire:click="delete({{ $user->id }})"
                            >
                                Delete
                            </flux:button>

                        </flux:table.cell>

                    </flux:table.row>

                @endforeach

            </flux:table.rows>

        </flux:table>

    </flux:card>



    <flux:modal wire:model="showModal">

        <flux:card class="w-[400px]">

            <h3 class="text-lg font-bold mb-4">
                {{ $isEdit ? 'Edit User' : 'Create User' }}
            </h3>

            <flux:input
                label="Name"
                wire:model="name"
            />

            <flux:input
                label="Email"
                type="email"
                wire:model="email"
            />

            @if(!$isEdit)

            <flux:input
                label="Password"
                type="password"
                wire:model="password"
            />

            @endif


            <div class="flex justify-end gap-2 mt-4">

                <flux:button
                    variant="ghost"
                    wire:click="$set('showModal', false)"
                >
                    Cancel
                </flux:button>

                <flux:button
                    variant="primary"
                    wire:click="save"
                >
                    Save
                </flux:button>

            </div>

        </flux:card>

    </flux:modal>

</div>