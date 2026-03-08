<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>


<div
    x-data="{
        toasts: [],
        colors: {
            success: 'bg-green-600',
            error: 'bg-red-600',
            warning: 'bg-yellow-500 text-black',
            info: 'bg-blue-600',
        },

        addToast(payload) {
            const id = Date.now() + Math.random();

            this.toasts.push({
                id,
                message: payload.message ?? '',
                type: payload.type ?? 'success',
                show: true,
            });

            setTimeout(() => this.hideToast(id), 3000);
        },

        hideToast(id) {
            const toast = this.toasts.find(t => t.id === id);
            if (!toast) return;

            toast.show = false;

            // remove AFTER animation finishes
            setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }, 250);
        }
    }"
    x-on:toast.window="
        const payload = Array.isArray($event.detail)
            ? $event.detail[0]
            : $event.detail;

        addToast(payload);
    "
    class="fixed top-5 right-5 z-50 space-y-2"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="toast.show"
            x-transition:enter="transform transition ease-out duration-300"
            x-transition:enter-start="translate-x-full opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transform transition ease-in duration-200"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="translate-x-full opacity-0"
            class="max-w-xs w-full border border-line-2 text-sm rounded-lg shadow-lg px-4 py-2 text-white"
            :class="colors[toast.type]"
        >
            <span x-text="toast.message"></span>
        </div>
    </template>
</div>