@php
    use Filament\Actions\Action;
    use Filament\Tables\Actions\HeaderActionsPosition;
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-filament::section.heading>Empfohlene Abfüllungen</x-filament::section.heading>
        <div class="flex flex-wrap gap-4 items-stretch pt-3 mb-4">
            <x-filament::input.wrapper class="h-9" prefix="Maximum size">
                <x-filament::input.select wire:model.live.debounce="maxSize">
                    @foreach($sizes as $size)
                        <option value="{{ $size }}">{{ $size }}g</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
            <x-filament::input.wrapper class="h-9" prefix="Max Positionen">
                <x-filament::input type="number" min="1" max="10" wire:model.live.debounce="maxPositions"/>
            </x-filament::input.wrapper>
            <x-filament::input.wrapper class="h-9" prefix="Min pro Position">
                <x-filament::input type="number" min="1" max="100" wire:model.live.debounce="minItems"/>
            </x-filament::input.wrapper>
            <x-filament::input.wrapper class="h-9" prefix="Vorsorge Monate">
                <x-filament::input type="number" min="1" max="12" wire:model.live.debounce="coverMonths"/>
            </x-filament::input.wrapper>
            <x-filament::input.wrapper class="h-9" prefix="Group same recipes" :inline-prefix="true">
                <x-filament::input.checkbox wire:model.live.debounce="groupSimilar" class="mx-3 mt-2"/>
            </x-filament::input.wrapper>
        </div>
        <div class="flex flex-col gap-3">
            @foreach($groups as $key => $group)
                <x-filament::section
                    class="[&_.fi-section-content]:p-0 overflow-clip"
                    heading="Vorschlag {{$loop->iteration}}"
                    :compact="true"
                    :collapsible="true"
                    :collapsed="!$loop->first"
                    :header-actions="[Action::make('create-' . $loop->index)->label('Erstellen')->action(fn () => $this->createGroup($loop->index))]">
                    <table
                        class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                        <thead class="divide-y divide-gray-200 dark:divide-white/5">
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <x-filament-tables::header-cell>
                                Anzahl
                            </x-filament-tables::header-cell>
                            <x-filament-tables::header-cell>
                                Produkt
                            </x-filament-tables::header-cell>
                            <x-filament-tables::header-cell>
                                Variante
                            </x-filament-tables::header-cell>
                            <x-filament-tables::header-cell>
                                <span class="flex items-center gap-1">
                                    Bestand
                                    <x-billbee class="w-7 h-7 inline-block"/>
                                </span>
                            </x-filament-tables::header-cell>
                            <x-filament-tables::header-cell>
                                Aufgebraucht
                            </x-filament-tables::header-cell>
                            <x-filament-tables::header-cell>
                                Nächster Sale
                            </x-filament-tables::header-cell>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                        @foreach($group as $position)
                            <x-filament-tables::row class="px-6">
                                <x-filament-tables::cell>
                                    <div class="px-3 py-4">
                                        {{$position->count}}
                                    </div>
                                </x-filament-tables::cell>
                                <x-filament-tables::cell>
                                    <div class="px-3 py-4">
                                        {{$position->variant->product->name}}
                                    </div>
                                </x-filament-tables::cell>
                                <x-filament-tables::cell>
                                    <div class="px-3 py-4">
                                        {{$position->variant->size}}g
                                    </div>
                                </x-filament-tables::cell>
                                <x-filament-tables::cell>
                                    <div class="px-3 py-4">
                                        {{$position->variant->stock}}
                                    </div>
                                </x-filament-tables::cell>
                                <x-filament-tables::cell>
                                    <div class="px-3 py-4">
                                        @php
                                            $date = \Carbon\Carbon::parse($position->variant->depleted_date);
                                            if ($date < now()) $date = null;
                                        @endphp
                                        {{$date?->diffForHumans() ?: "Jetzt"}}
                                    </div>
                                </x-filament-tables::cell>
                                <x-filament-tables::cell>
                                    <div class="px-3 py-4">
                                        @php
                                            $date = \Carbon\Carbon::parse($position->variant->next_sale);
                                            if ($date < now()) $date = null;
                                        @endphp
                                        {{$date?->diffForHumans() ?: "Jetzt"}}
                                    </div>
                                </x-filament-tables::cell>
                            </x-filament-tables::row>
                        @endforeach
                        </tbody>
                    </table>
                </x-filament::section>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
