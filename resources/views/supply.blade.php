<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-white">
            {{ __('Einlagern') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <x-panel.wrapper>
            <x-panel class="bg-white !col-span-full">
                @livewire('delivery')
            </x-panel>
        </x-panel.wrapper>
    </div>
</x-app-layout>
