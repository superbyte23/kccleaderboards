<?php 
use App\Livewire\Concerns\HasSileoToasts;
use Livewire\Component;

new class extends Component
{
    use HasSileoToasts;
    public function showSuccess(): void
    {
        $this->toastSuccess('Changes saved!', 'Your profile has been updated.');
    }

    public function showError(): void
    {
        $this->toastError('Upload failed', 'File exceeds the 10 MB limit.');
    }

    public function showWarning(): void
    {
        $this->toastWarning('Session expiring', 'You will be logged out in 5 minutes.');
    }

    public function showInfo(): void
    {
        $this->toastInfo('Update available', 'Refresh to get v2.4.');
    }

    // ── Action toast (Undo) ────────────────────────────────────────────────

    public function deleteItem(): void
    {
        // ... delete logic ...

        $this->toastAction(
            type:         'info',
            title:        'Item deleted',
            description:  'The record has been removed.',
            actionLabel:  'Undo',
            actionEvent:  'undo-delete',
            actionParams: [42],
        );
    }

    #[\Livewire\Attributes\On('undo-delete')]
    public function undoDelete(int $id): void
    {
        // ... restore logic ...
        $this->toastSuccess('Restored!', "Item #{$id} has been restored.");
    }

    // ── Promise toast ──────────────────────────────────────────────────────

    public function startSave(): void
    {
        $this->toastPromise(
            promiseEvent: 'run-save',
            loadingMsg:      'Saving your data…',
            successMsg:      'All changes saved!',
            errorMsg:        'Save failed. Please retry.',
        );
    }

    // Step 2: JS dispatches 'run-save' → Livewire calls this method
    #[\Livewire\Attributes\On('run-save')]
    public function runSave(): void
    {
        try {
            // ... your actual save logic ...
            $this->resolveToastPromise('run-save');
        } catch (\Throwable) {
            $this->rejectToastPromise('run-save');
        }
    }

    // ── Custom position ────────────────────────────────────────────────────

    public function showBottomCenter(): void
    {
        $this->dispatch('sileo',
            type:     'success',
            title:    'Bottom center!',
            position: 'bottom-center',
        );
    }
};
?>

<div class="p-10 space-y-6">
    <h1 class="text-2xl font-bold">Sileo + Livewire 4 Demo</h1>

    {{-- Basic types --}}
    <div class="flex flex-wrap gap-3">
        <button wire:click="showSuccess"
            class="px-4 py-2 rounded-lg bg-emerald-700 hover:bg-emerald-600 text-sm font-semibold text-white">
            Success
        </button>
        <button wire:click="showError"
            class="px-4 py-2 rounded-lg bg-rose-700 hover:bg-rose-600 text-sm font-semibold text-white">
            Error
        </button>
        <button wire:click="showWarning"
            class="px-4 py-2 rounded-lg bg-amber-700 hover:bg-amber-600 text-sm font-semibold text-white">
            Warning
        </button>
        <button wire:click="showInfo"
            class="px-4 py-2 rounded-lg bg-sky-700 hover:bg-sky-600 text-sm font-semibold text-white">
            Info
        </button>
    </div>

    {{-- Action & Promise --}}
    <div class="flex flex-wrap gap-3">
        <button wire:click="deleteItem"
            class="px-4 py-2 rounded-lg bg-zinc-700 hover:bg-zinc-600 text-sm font-semibold text-white">
            Action (Undo)
        </button>
        <button wire:click="startSave"
            class="px-4 py-2 rounded-lg bg-violet-700 hover:bg-violet-600 text-sm font-semibold text-white"
            data-loading:class="opacity-50 cursor-wait">
            Promise Toast
        </button>
        <button wire:click="showBottomCenter"
            class="px-4 py-2 rounded-lg bg-zinc-700 hover:bg-zinc-600 text-sm font-semibold text-white">
            Custom Position
        </button>
    </div>
</div>