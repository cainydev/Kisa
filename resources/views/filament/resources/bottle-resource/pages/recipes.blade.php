@php($positions = $bottle->positions)
@php($groups = $bottle->positions->groupBy('variant.product_id'))

<x-filament-panels::page x-data="{
    activeTab: 'tab-{{ $grouped ? $groups->keys()[0] : $positions->first()->id }}',
}">
    <x-filament::tabs class="w-full" :contained="false">
        @if($grouped)
            @foreach($groups as $product_id => $groupPositions)
                <x-filament::tabs.item
                    wire:key="group-{{ $product_id }}"
                    alpine-active="activeTab === 'tab-{{  $product_id }}'"
                    :badge="count($groupPositions) > 1 ? count($groupPositions) : null"
                    badge-color="info"
                    x-on:click="activeTab = 'tab-{{  $product_id }}'">
                    {{ \App\Models\Product::find($product_id)->name }}
                </x-filament::tabs.item>
            @endforeach
        @else
            @foreach($positions as $position)
                <x-filament::tabs.item
                    wire:key="single-{{ $position->id }}"
                    alpine-active="activeTab === 'tab-{{ $position->id }}'"
                    :badge="$position->variant->size . 'g'"
                    x-on:click="activeTab = 'tab-{{ $position->id }}'">
                    {{ $position->count }} × {{ $position->variant->product->name }}
                </x-filament::tabs.item>
            @endforeach
        @endif
    </x-filament::tabs>

    @php($groupsOrSingles = $grouped ? $groups : $positions->mapWithKeys(fn($pos) => [$pos->id => [$pos]]))

    @foreach($groupsOrSingles as $groupId => $groupedPositions)
        <section x-show="activeTab === 'tab-{{ $groupId }}'"
                 wire:key="tab-{{ $groupId }}" class="flex flex-col gap-8">
            <div class="flex flex-col md:flex-row gap-8">
                {{-- Bottle Position List --}}
                <livewire:bottle-position-list wire:key="list-{{ $groupId }}"
                                               :pos="$groupedPositions"/>

                {{-- Charge --}}
                <x-filament::section class="w-full">
                    <x-slot name="heading">Charge</x-slot>
                    @if(count($groupedPositions) > 1)
                        <x-slot:description>
                            Die Charge wird automatisch auf alle Positionen angewandt.
                        </x-slot:description>
                    @endif

                    <div class="flex items-center gap-2 w-fit">
                        <x-filament::input.wrapper prefix-icon="heroicon-s-hashtag"
                                                   class="grow">
                            <x-filament::input label="Charge"/>
                        </x-filament::input.wrapper>
                        <x-filament::button color="gray">Generieren</x-filament::button>
                        <x-filament::button color="primary">Speichern</x-filament::button>
                    </div>
                </x-filament::section>
            </div>
            <x-filament::section class="col-span-full">
                Recipe
            </x-filament::section>
        </section>
    @endforeach
</x-filament-panels::page>
