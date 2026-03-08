<?php

use Livewire\Component;

new class extends Component
{
    public string $position = 'top-right';

    public function mount(string $position = 'top-right'): void
    {
        $this->position = $position;
    } 
};
?>

<div>
    <div id="sileo-portal" data-position="{{ $position }}"></div>

    @viteReactRefresh
    @vite('resources/js/sileo-bridge.js')
</div>