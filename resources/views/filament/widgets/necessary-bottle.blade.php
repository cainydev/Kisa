@php use App\Models\Variant;use Illuminate\Support\Carbon; @endphp
<x-filament-widgets::widget>
    <x-filament::section>
        <x-filament::section.heading>
            Benötigte Abfüllungen aus Bestellungen
        </x-filament::section.heading>
        @if ($this->messages->isNotEmpty())
            <div>
                <x-filament::icon icon="heroicon-o-exclamation-triangle"/>
                @foreach($this->messages as $message)
                    <p class="text-red-700">{{ $message }}</p>
                @endforeach
            </div>
        @endif
        <div class="flex flex-wrap gap-4 items-stretch pt-3 mb-4">
            <x-filament::input.wrapper class="h-9" prefix="Bestellungen von">
                <x-filament::input.select wire:model.live.debounce="maxDate">
                    <option value="{{ now()->startOfDay()->toIso8601String() }}">Heute</option>
                    <option value="{{ now()->subDays(3)->startOfDay()->toIso8601String() }}">Letzte 3 Tage</option>
                    <option value="{{ now()->subDays(7)->startOfDay()->toIso8601String() }}">Letzte 7 Tage</option>
                    <option value="{{ now()->subDays(14)->startOfDay()->toIso8601String() }}">Letzte 14 Tage
                    </option>
                    <option value="{{ now()->subDays(30)->startOfDay()->toIso8601String() }}">Letzte 30 Tage
                    </option>
                </x-filament::input.select>
            </x-filament::input.wrapper>
            <x-filament::input.wrapper class="h-9" prefix="Vorsorge bei Größen bis">
                <x-filament::input.select wire:model.live.debounce="extrapolateMaxSize">
                    @foreach($sizes as $size)
                        <option value="{{ $size }}">{{ $size }}g</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
            <x-filament::input.wrapper class="h-9" prefix="Vorsorge Monate">
                <x-filament::input type="number" min="1" max="12" wire:model.live.debounce="extrapolateMonths"/>
            </x-filament::input.wrapper>
        </div>
        <x-filament::section class="[&_.fi-section-content]:p-0 overflow-clip">
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
                </tr>
                </thead>

                <tbody
                    class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5 relative overflow-hidden before:transition-all"
                    wire:loading.class="before:absolute before:inset-0 dark:before:bg-gray-900 before:bg-white">
                @foreach($positions as $variant_id => $count)
                    @php $variant = Variant::find($variant_id) @endphp
                    <x-filament-tables::row class="px-6"
                                            wire:key="position-{{ $variant_id }}">
                        <x-filament-tables::cell>
                            <div class="px-3 py-4">
                                {{ $count }}
                            </div>
                        </x-filament-tables::cell>
                        <x-filament-tables::cell>
                            <div class="px-3 py-4">
                                {{$variant->product->name}}
                            </div>
                        </x-filament-tables::cell>
                        <x-filament-tables::cell>
                            <div class="px-3 py-4">
                                {{$variant->size}}g
                            </div>
                        </x-filament-tables::cell>
                        <x-filament-tables::cell>
                            <div class="px-3 py-4">
                                {{$variant->stock}}
                            </div>
                        </x-filament-tables::cell>
                    </x-filament-tables::row>
                @endforeach
                </tbody>
            </table>
        </x-filament::section>
    </x-filament::section>
</x-filament-widgets::widget>
