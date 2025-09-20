{{-- The Master doesn't talk, he acts. --}}

@php use App\Models\Bag;use Filament\Support\Icons\Heroicon; @endphp

<div class="flex flex-col gap-8" x-data="{ herb: $wire.entangle('herb').live }">
    @if ($this->ingredients->count() === 0)
        <x-filament::section>
            <x-filament::section.heading>Variante ohne Zutaten</x-filament::section.heading>
            <x-filament::section.description>Nichts zu tun hier.</x-filament::section.description>
        </x-filament::section>
    @else
        <x-filament::section class="[&_.fi-section-content]:p-0 overflow-clip">
            <livewire:simple-wizard wire:model.live="herb" :steps="$this->steps"
                                    :completed-steps="$this->completedSteps"/>


            <x-table wire:loading.class="blur-md animate-pulse" wire:target="herb" x-ref="table">
                <x-table.body>
                    @foreach(Bag::whereHerbId($herb)->get()->sortByDesc->redisCurrent as $bag)
                        <x-table.tr
                            wire:key="table-row-{{ $bag->id }}"
                            wire:click="select({{ $herb }}, {{ $bag->id }})"
                            @class([
                                'fi-ta-row cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800',
                                'bg-gray-50 dark:bg-gray-800 border-l-2 border-l-[var(--primary-500)]' => ($bags[$herb] === $bag->id),
                            ])>
                            <x-table.td>
                                <div class="fi-ta-text
                                            grid gap-y-1 px-3 py-4 min-w-max">
                                    <input type="checkbox"
                                           class="fi-checkbox-input"
                                           :value="@js(intval($bags[$herb]) === intval($bag->id))">
                                </div>
                            </x-table.td>
                            <x-table.td>
                                <div class="fi-ta-text grid w-full gap-y-1 px-3 py-4">
                                    <span class="text-sm font-semibold">{{ $bag->herb->name }}</span>
                                    <span
                                        class="text-xs text-gray-500 dark:text-gray-400">{{ $bag->specification }}</span>
                                </div>
                            </x-table.td>
                            <x-table.td>
                                <div class="fi-ta-text grid gap-y-1 px-3 py-4 min-w-max">
                                    <x-filament::badge tooltip="{{ $bag->bestbefore->format('d.m.Y') }}"
                                                       :color="$bag->bestbefore->isPast() ? 'danger' : 'gray'">
                                        <x-filament::icon icon="heroicon-s-calendar" class="w-4 h-4"
                                                          :color="$bag->bestbefore->isPast() ? 'danger' : 'gray'"/>
                                    </x-filament::badge>
                                </div>
                            </x-table.td>
                            <x-table.td>
                                <div class="fi-ta-text
                                            grid gap-y-1 px-3 py-4 min-w-max">
                                    <x-filament::badge icon="heroicon-s-hashtag">
                                        {{ $bag->charge }}
                                    </x-filament::badge>
                                </div>
                            </x-table.td>
                            <x-table.td class="w-full px-3 py-4">
                                <livewire:bag-amount-bar wire:key="bag-amount-bar-{{ $bag->id }}" :$bag class="grow"/>
                            </x-table.td>
                            <x-table.td>
                                {{--{{ $getAction("select-bag-$bag->id") }}--}}
                            </x-table.td>
                            <x-table.td>
                                <x-filament::icon-button
                                    tag="a"
                                    icon="heroicon-s-arrow-top-right-on-square"
                                    color="gray"
                                    :href="route('filament.admin.resources.bags.edit', $bag->id)"/>
                            </x-table.td>
                        </x-table.tr>
                    @endforeach
                </x-table.body>
            </x-table>

            <x-slot:footer>
                <div class="flex items-center justify-between gap-2">
                    <x-filament::button
                        :icon="Heroicon::Backward" @class(['invisible' => $herb === $this->ingredients->first()->herb_id])>
                    </x-filament::button>

                    <x-bag-amount-bar-legend/>

                    <x-filament::button
                        :icon="Heroicon::Forward" @class(['invisible' => $herb === $this->ingredients->last()->herb_id])>
                    </x-filament::button>
                </div>
            </x-slot:footer>
        </x-filament::section>
    @endif
</div>
