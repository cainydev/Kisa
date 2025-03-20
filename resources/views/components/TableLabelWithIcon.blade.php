@props(['icon', 'label'])

<div class="flex items-center gap-0.5">
    <x-filament::icon :$icon class="w-7 h-7"/>
    <span>{{ $label }}</span>
</div>
