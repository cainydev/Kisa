@php($positions = $bottle->positions)
@php($groups = $bottle->positions->groupBy('variant.product_id'))

@if($positions->count() === 0)
    <x-filament-panels::page class="flex items-center">
        <div>
            <p>Keine Positionen hinzugefügt</p>
        </div>
    </x-filament-panels::page>
@else

    <x-filament-panels::page class="" x-data="{
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
                {{-- Bottle Position List --}}
                <livewire:bottle-position-list wire:key="list-{{ $groupId }}"
                                               :pos="$groupedPositions"/>

                @php($ingredientsRequired = $groupedPositions[0]->variant->product->recipeIngredients->sortByDesc('percentage'))
                @php($ingredients = $groupedPositions[0]->ingredients)

                <div x-data="{ activeTab: $persist(1) }">
                    <section
                        class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 col-span-full">
                        <div class="fi-section-content-ctn">
                            <div class="fi-section-content p-2 flex items-center gap-2">
                                @foreach($ingredientsRequired as $ing)
                                    @php($finished = $ingredients->pluck('herb_id')->contains($ing->herb_id))
                                    @php($first = $loop->first ? 'rounded-l-lg' : '')
                                    @php($last = $loop->last ? 'rounded-r-lg' : '')
                                    <x-filament::button
                                        x-on:click="activeTab = {{ $loop->iteration }}"
                                        x-on:keydown.left="activeTab = Math.max(1, activeTab - 1)"
                                        x-on:keydown.right="activeTab = Math.min({{ $loop->count }}, activeTab + 1)"
                                        color="gray"
                                        x-bind:class="activeTab === {{ $loop->iteration }} ? '{{ $finished ? '!bg-green-800' : '!bg-stone-900'}} shadow-inner scale-90' : '{{ $finished ? '!bg-green-300' : ''}}'"
                                        class="rounded-sm h-11 min-w-fit grow {{ $first }} {{ $last }}"
                                        style="flex-basis: {{ $ing->percentage }}%">
                                        @if($ingredients->pluck('herb_id')->contains($ing->herb_id))
                                            <x-filament::icon icon="heroicon-o-check-circle" class="fill-primary-500"/>
                                        @else
                                            <p class="pr-1 text-xl pl-1.5 font-semibold">{{ $loop->iteration }}</p>
                                        @endif
                                    </x-filament::button>
                                @endforeach
                            </div>
                        </div>
                    </section>
                    @foreach($ingredientsRequired as $ing)
                        <livewire:available-bags :wire:key="$ing->id"
                                                 :positions="$groupedPositions"
                                                 :herb="$ing->herb"></livewire:available-bags>
                    @endforeach
                </div>
            </section>
        @endforeach
    </x-filament-panels::page>
@endif
