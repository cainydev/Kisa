<x-filament-panels::page>
    @if($bottle->positions->count() === 0)
        <div>
            <p>Keine Positionen hinzugefügt</p>
        </div>
    @else
        <x-filament::tabs class="w-full" x-data="{}">
            @if($grouped)
                @foreach($this->groups as $key => $positions)
                    <x-filament::tabs.item
                        wire:key="group-{{ Js::from($key) }}"
                        wire:click="$set('activeGroupedTab', {{ Js::from($key) }})"
                        alpine-active="{{ Js::from($activeGroupedTab == $key) }}"
                        :badge="count($positions) > 1 ? count($positions) : null"
                        badge-color="info">
                        {{ $positions->first()->variant->product->name }}
                    </x-filament::tabs.item>
                @endforeach
            @else
                @foreach($bottle->positions as $position)
                    <x-filament::tabs.item
                        wire:key="single-{{ $position->id }}"
                        wire:click="$set('activeTab', {{ $position->id }})"
                        alpine-active="{{ Js::from($activeTab == $position->id) }}"
                        :badge="$position->variant->size . 'g'">
                        {{ $position->count }} × {{ $position->variant->product->name }}
                    </x-filament::tabs.item>
                @endforeach
            @endif
        </x-filament::tabs>
    @endif

    @if($this->positions !== null)
        @php($ids = $this->positions->pluck('id')->implode('-'))
        <div wire:loading.remove wire:target="grouped, activeGroupedTab, activeTab">
            <livewire:bottle-position-list :positions="$this->positions"
                                           wire:key="list-{{ $ids }}"/>
        </div>

        <div wire:loading.remove wire:target="grouped, activeGroupedTab, activeTab">
            <livewire:recipe :positions="$this->positions"
                             wire:key="recipe-{{ $ids }}"/>
        </div>

        <div wire:loading.flex wire:target="grouped, activeGroupedTab, activeTab"
             class="hidden flex-col gap-8">
            <x-filament::loading-section height="117px"/>
            <x-filament::loading-section height="300px"/>
        </div>
    @endif
</x-filament-panels::page>
