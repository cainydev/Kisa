@php
    use Filament\Actions\Action;
    use Filament\Tables\Actions\HeaderActionsPosition;
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-filament::section.heading>Nächste Abfüllungen</x-filament::section.heading>
        <div class="flex flex-wrap gap-4 items-stretch pt-3 mb-4">
            <x-filament::input.wrapper class="h-9" prefix="Maximum size">
                <x-filament::input.select wire:model.live="maxSize">
                    @foreach($sizes as $size)
                        <option value="{{ $size }}">{{ $size }}g</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
            <x-filament::input.wrappcacher class="h-9" prefix="Max. Positionen">
                <x-filament::input type="number" min="1" max="10" wire:model.live="maxPositions"/>
            </x-filament::input.wrappcacher>
            <x-filament::input.wrapper class="h-9" prefix="Vorsorge Monate">
                <x-filament::input type="number" min="1" max="12" wire:model.live="coverMonths"/>
            </x-filament::input.wrapper>
            <x-filament::input.wrapper class="h-9" prefix="Group same recipes" :inline-prefix="true">
                <x-filament::input.checkbox wire:model.live="groupSimilar" class="mx-3 mt-2"/>
            </x-filament::input.wrapper>
        </div>
        <div class="flex flex-col gap-3">
            @foreach($groups as $key => $group)
                <x-filament::section heading="Vorschlag {{$loop->iteration}}"
                                     :compact="true"
                                     :collapsible="true"
                                     :collapsed="!$loop->first"
                                     :header-actions="[
                                        Action::make('create-' . $loop->index)
                                            ->label('Erstellen')
                                            ->button()
                                            ->action(fn () => $this->createGroup($loop->index))

                                        ]">
                    <ul>
                        @foreach($group as $position)
                            <li>{{$position->count}}
                                x {{$position->variant->product->name}} {{ $position->variant->size }}
                                , {{ $position->variant->average_monthly_sales }}</li>
                        @endforeach
                    </ul>
                </x-filament::section>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
