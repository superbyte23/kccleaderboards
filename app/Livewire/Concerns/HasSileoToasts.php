<?php

namespace App\Livewire\Concerns;

/**
 * HasSileoToasts — Livewire 4 edition
 *
 * Add to any Livewire component for clean toast methods.
 * Dispatches browser events consumed by ⚡sileo-toaster.blade.php.
 *
 * Usage:
 *   class MyComponent extends Component
 *   {
 *       use HasSileoToasts;
 *
 *       public function save(): void
 *       {
 *           $this->toastSuccess('Saved!');
 *           $this->toastAction('info', 'Deleted', 'Row removed.', 'Undo', 'undo-delete');
 *           $this->toastPromise('do-save', loading: 'Saving…');
 *       }
 *   }
 */
trait HasSileoToasts
{
    // ── Core ───────────────────────────────────────────────────────────────

    protected function toast(
        string  $type,
        string  $title,
        string  $description = '',
        int     $duration = 4000,
        ?string $position = null,
        ?array  $action = null,
    ): void {
        // Livewire 4: dispatch() fires a native browser CustomEvent on window
        $this->dispatch('sileo',
            type:        $type,
            title:       $title,
            description: $description,
            duration:    $duration,
            position:    $position,
            action:      $action,
        );
    }

    // ── Convenience ────────────────────────────────────────────────────────

    protected function toastSuccess(string $title, string $desc = '', int $duration = 4000): void
    {
        $this->toast('success', $title, $desc, $duration);
    }

    protected function toastError(string $title, string $desc = '', int $duration = 4000): void
    {
        $this->toast('error', $title, $desc, $duration);
    }

    protected function toastWarning(string $title, string $desc = '', int $duration = 4000): void
    {
        $this->toast('warning', $title, $desc, $duration);
    }

    protected function toastInfo(string $title, string $desc = '', int $duration = 4000): void
    {
        $this->toast('info', $title, $desc, $duration);
    }

    // ── Action toast ───────────────────────────────────────────────────────

    protected function toastAction(
        string $type,
        string $title,
        string $description,
        string $actionLabel,
        string $actionEvent,
        array  $actionParams = [],
        int    $duration = 6000,
    ): void {
        $this->toast($type, $title, $description, $duration, null, [
            'label'  => $actionLabel,
            'event'  => $actionEvent,
            'params' => $actionParams,
        ]);
    }

    // ── Promise toast ──────────────────────────────────────────────────────

    /**
     * Shows a loading toast then resolves/rejects when your handler
     * calls resolveToastPromise() or rejectToastPromise().
     */
    protected function toastPromise(
        string $promiseEvent,
        string $loadingMsg = 'Loading…',
        string $successMsg = 'Done!',
        string $errorMsg = 'Something went wrong.',
    ): void {
        $this->dispatch('sileo.promise', ...[
            'event'   => $promiseEvent,
            'loading' => $loadingMsg,
            'success' => $successMsg,
            'error'   => $errorMsg ,
        ]);
    }

    protected function resolveToastPromise(string $promiseEvent): void
    {
        $this->dispatch("sileo.resolve.{$promiseEvent}");
    }

    protected function rejectToastPromise(string $promiseEvent): void
    {
        $this->dispatch("sileo.reject.{$promiseEvent}");
    }
}