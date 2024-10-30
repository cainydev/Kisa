<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div class="bg-red-500" wire:poll x-data="{ state: $wire.$entangle('{{ $getStatePath() }}') }">

    </div>
</x-dynamic-component>
