<?php

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

new class extends Component
{
    public $users;

    public $userId;
    public $name;
    public $email;
    public $password;

    public $showModal = false;
    public $isEdit = false;

    public $deleteId;

    protected function rules()
    {
        return [
            'name' => 'required|min:3',
            'email' => 'required|email',
            'password' => $this->isEdit ? 'nullable|min:6' : 'required|min:6'
        ];
    }

    public function mount()
    {
        $this->getUsers();
    }

    public function getUsers()
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

            $data = [
                'name' => $this->name,
                'email' => $this->email,
            ];

            if ($this->password) {
                $data['password'] = Hash::make($this->password);
            }

            $user->update($data);

        } else {

            User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password)
            ]);

        }

        $this->getUsers();
        $this->resetForm();
    }

    public function confirmDelete($id)
    {
        $this->deleteId = $id;
        $this->modal('delete-user')->show();
    }

    public function deleteUser()
    {
        User::findOrFail($this->deleteId)->delete();

        $this->deleteId = null;

        $this->getUsers();

        $this->modal('delete-user')->close();
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

        <flux:heading size="lg">
            Users
        </flux:heading>

        <flux:button wire:click="create" variant="primary">
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

            <flux:table.row wire:key="user-{{ $user->id }}">

                <flux:table.cell>
                    {{ $user->name }}
                </flux:table.cell>

                <flux:table.cell>
                    {{ $user->email }}
                </flux:table.cell>

                <flux:table.cell class="space-x-2">

                    <flux:button
                        size="sm"
                        wire:click="edit({{ $user->id }})"
                        wire.target="edit({{ $user->id }})"
                        wire:loading.attr="disabled"
                    >
                        Edit
                    </flux:button>

 

                        <flux:button
                            size="sm"
                            variant="danger"
                            wire:click="confirmDelete({{ $user->id }})"
                            wire.target="confirmDelete({{ $user->id }})"
                            wire:loading.attr="disabled"
                        >
                            Delete
                        </flux:button> 

                </flux:table.cell>

            </flux:table.row>

            @endforeach

        </flux:table.rows>

    </flux:table>

</flux:card>



{{-- Create / Edit Modal --}}

<flux:modal wire:model="showModal" class="md:w-96">

    <div class="space-y-6">

        <flux:heading size="lg">
            {{ $isEdit ? 'Edit User' : 'Create User' }}
        </flux:heading>

        <flux:text>
            {{ $isEdit ? 'Update user information' : 'Add new user' }}
        </flux:text>


        <flux:field>
            <flux:label>Name</flux:label>
            <flux:input wire:model="name"/>
            <flux:error name="name"/>
        </flux:field>


        <flux:field>
            <flux:label>Email</flux:label>
            <flux:input type="email" wire:model="email"/>
            <flux:error name="email"/>
        </flux:field>


        @if(!$isEdit)

        <flux:field>
            <flux:label>Password</flux:label>
            <flux:input type="password" wire:model="password"/>
            <flux:error name="password"/>
        </flux:field>

        @endif


        <div class="flex justify-end gap-2">

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

    </div>

</flux:modal>



{{-- Delete Confirmation Modal --}}

<flux:modal name="delete-user" class="min-w-[22rem]">

    <div class="space-y-6">

        <div>
            <flux:heading size="lg">
                Delete User?
            </flux:heading>

            <flux:text class="mt-2">
                You're about to delete this user.<br>
                This action cannot be reversed.
            </flux:text>
        </div>

        <div class="flex gap-2">

            <flux:spacer />

            <flux:modal.close>
                <flux:button variant="ghost">
                    Cancel
                </flux:button>
            </flux:modal.close>

            <flux:button
                variant="danger"
                wire:click="deleteUser"
            >
                Delete User
            </flux:button>

        </div>

    </div>

</flux:modal>

</div>