<?php

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Livewire\Concerns\HasSileoToasts;

new class extends Component
{
    use HasSileoToasts;
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

            $this->toastSuccess('Success!', 'User updated successfully.');

        } else {

            User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password)
            ]);

            $this->toastSuccess('Success!', 'User created successfully.');

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

        $this->toastSuccess('Success!', 'User deleted successfully.');

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

<div class="mx-auto max-w-7xl space-y-6 p-4 pt-5">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-extrabold tracking-tight text-white md:text-4xl">Users</h1>
        </div>
        <flux:button wire:click="create" variant="primary" icon="plus">Add user</flux:button>
    </div>

    <div class="space-y-3">
        @forelse($users as $user)
            <div wire:key="user-{{ $user->id }}" class="panel p-4">
                <div class="flex items-center gap-3">
                    <flux:avatar :name="$user->name" :initials="$user->initials()" size="sm" class="shrink-0" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold text-white">{{ $user->name }}</p>
                        <p class="truncate text-xs text-zinc-500">{{ $user->email }}</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between gap-3 border-t border-line pt-3">
                    <span class="font-mono text-xs tabular-nums text-zinc-400">Joined {{ $user->created_at->format('M j, Y') }}</span>
                    <div class="flex shrink-0 items-center gap-1">
                        <flux:button
                            size="sm"
                            variant="subtle"
                            wire:click="edit({{ $user->id }})"
                            wire:target="edit({{ $user->id }})"
                            wire:loading.attr="disabled"
                            square
                        >
                            <x-tabler-icon name="pencil-cog" class="size-4 text-zinc-300" />
                        </flux:button>
                        <flux:button
                            size="sm"
                            variant="ghost"
                            wire:click="confirmDelete({{ $user->id }})"
                            wire:target="confirmDelete({{ $user->id }})"
                            wire:loading.attr="disabled"
                            square
                        >
                            <x-tabler-icon name="trash-x" class="size-4 text-red-400" />
                        </flux:button>
                    </div>
                </div>
            </div>
        @empty
            <div class="panel px-4 py-16 text-center text-zinc-400">No users found.</div>
        @endforelse
    </div>

    {{-- Create / Edit Modal --}}
    <flux:modal wire:model="showModal" flyout position="bottom" class="modal-sheet">
        <div class="space-y-6">
            <flux:heading size="lg" class="font-display font-bold tracking-tight text-white">
                {{ $isEdit ? 'Edit user' : 'Create user' }}
            </flux:heading>

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
                <flux:button variant="ghost" wire:click="$set('showModal', false)">
                    <x-tabler-icon name="x" class="size-4" />
                    Cancel
                </flux:button>
                <flux:button variant="primary" wire:click="save">
                    <x-tabler-icon name="device-floppy" class="size-4" />
                    Save
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Delete Confirmation Modal --}}
    <flux:modal name="delete-user" flyout position="bottom" class="modal-sheet">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg" class="font-display font-bold tracking-tight text-white">
                    Delete user?
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
                <flux:button variant="danger" wire:click="deleteUser">
                    Delete user
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>