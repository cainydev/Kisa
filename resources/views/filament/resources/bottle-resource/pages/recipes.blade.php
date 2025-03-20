<x-filament-panels::page>
    @if($record->positions->count() === 0)
        <div>
            <p>Keine Positionen hinzugefügt</p>
        </div>
    @else
        @php($groups = $record->positions->groupBy('variant.product_id'))
        <x-filament::tabs class="w-full" x-data="{
            activeTab: $wire.entangle('activeTab').live,
            activeGroupedTab: $wire.entangle('activeGroupedTab').live
        }">
            @if($grouped)
                @foreach($groups as $product_id => $groupPositions)
                    <x-filament::tabs.item
                        wire:key="group-{{ $product_id }}"
                        @click="activeGroupedTab = {{ $product_id }}"
                        alpine-active="activeGroupedTab === {{ $product_id }}"
                        :badge="count($groupPositions) > 1 ? count($groupPositions) : null"
                        badge-color="info">
                        {{ \App\Models\Product::find($product_id)->name }}
                    </x-filament::tabs.item>
                @endforeach
            @else
                @foreach($record->positions as $position)
                    <x-filament::tabs.item
                        wire:key="single-{{ $position->id }}"
                        @click="activeTab = {{ $position->id }}"
                        alpine-active="activeTab === {{ $position->id }}"
                        :badge="$position->variant->size . 'g'">
                        {{ $position->count }} × {{ $position->variant->product->name }}
                    </x-filament::tabs.item>
                @endforeach
            @endif
        </x-filament::tabs>
    @endif

    <div wire:loading.class="[&_*_.fi-ta-row]:blur-sm">
        <livewire:bottle-position-list :$positions/>
    </div>
</x-filament-panels::page>
