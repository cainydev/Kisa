@props(['positions', 'herb'])

<div>
    <livewire:available-bags :wire:key="'available-bags-' . $positions->pluck('id')->implode('-')"
                             :$positions :$herb/>
</div>
