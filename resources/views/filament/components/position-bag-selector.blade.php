{{-- Stop trying to control. --}}

<x-filament-tables::table>
    @php
        $allActiveBags = $getHerb()->bags;
        $selectedBag = $getHerb()->bags()->withTrashed()->find($getState());
        if($selectedBag !== null) $allActiveBags->push($selectedBag);
    @endphp
    @forelse($allActiveBags->unique('id')->sortByDesc('bestbefore') as $bag)
        <x-filament-tables::row wire:key="{{ $bag->id }}">
            <x-filament-tables::cell>
                <div class="fi-ta-text grid w-full gap-y-1 px-3 py-4">
                    <span class="text-sm font-semibold">{{ $getHerb()->name }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $bag->specification }}</span>
                </div>
            </x-filament-tables::cell>
            <x-filament-tables::cell>
                <div class="fi-ta-text grid gap-y-1 px-3 py-4 min-w-max">
                    <x-filament::badge tooltip="{{ $bag->bestbefore->format('d.m.Y') }}"
                                       :color="$bag->bestbefore->isPast() ? 'danger' : 'gray'">
                        <x-filament::icon icon="heroicon-s-calendar" class="w-4 h-4"
                                          :color="$bag->bestbefore->isPast() ? 'danger' : 'gray'"/>
                    </x-filament::badge>
                </div>
            </x-filament-tables::cell>
            <x-filament-tables::cell>
                <div class="fi-ta-text grid gap-y-1 px-3 py-4 min-w-max">
                    <x-filament::badge icon="heroicon-s-hashtag">
                        {{ $bag->charge }}
                    </x-filament::badge>
                </div>
            </x-filament-tables::cell>
            <x-filament-tables::cell class="w-full px-3 py-4">
                <livewire:bag-amount-bar wire:key="bag-amount-bar-{{ $bag->id }}" :$bag class="grow"/>
            </x-filament-tables::cell>
            <x-filament-tables::actions.cell class="">
                {{ $getAction("select-bag-$bag->id") }}
            </x-filament-tables::actions.cell>
            <x-filament-tables::actions.cell class="">
                <x-filament::icon-button
                    tag="a"
                    icon="heroicon-s-arrow-top-right-on-square"
                    color="gray"
                    :href="route('filament.admin.resources.bags.edit', $bag->id)"/>
            </x-filament-tables::actions.cell>
        </x-filament-tables::row>
    @empty
        <x-filament-tables::row>
            <x-filament-tables::cell class="pt-4">
                <section
                    class="fi-section rounded-xl w-full grow bg-white shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 fi-collapsed">
                    <div class="fi-section-header flex gap-3 px-6 py-4">
                        <x-filament::icon icon="heroicon-s-exclamation-triangle" class="w-7 h-7" color="orange"/>
                        <div class="grid flex-1 gap-y-1">
                            <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                                Keine passenden Säcke mit {{ $getHerb()->name }} gefunden
                            </h3>
                            <p class="fi-section-header-description text-sm text-gray-500 dark:text-gray-400">
                                Erstelle eine neue Lieferung, um Säcke hinzuzufügen.
                            </p>
                        </div>
                    </div>
                </section>
            </x-filament-tables::cell>
        </x-filament-tables::row>
    @endforelse
</x-filament-tables::table>
