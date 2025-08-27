{{-- The Master doesn't talk, he acts. --}}

@php use Filament\Support\Icons\Heroicon; @endphp

<div class="flex flex-col gap-8">
    @if ($this->ingredients->count() === 0)
        <x-filament::section>
            <x-filament::section.heading>Variante ohne Zutaten</x-filament::section.heading>
            <x-filament::section.description>Nichts zu tun hier.</x-filament::section.description>
        </x-filament::section>
    @else
        <x-filament::section class="[&_.fi-section-content]:p-0">
            <x-filament::tabs class="w-full" x-data="{}" contained="true">
                @foreach($this->ingredients as $ingredient)
                    @php($herb_id = $ingredient->herb->id)
                    @php($selected = array_key_exists($herb_id, $bags) && !is_null($bags[$herb_id]))
                    <x-filament::tabs.item
                        wire:key="tab-{{ $herb_id }}"
                        wire:click.prevent="$set('herb', {{ $herb_id }})"
                        :active="$herb == $herb_id"
                        :badge="$this->amounts[$herb_id] . 'g'"
                        :badge-color="$selected ? 'primary' : 'gray'"
                        :icon="$selected ? Heroicon::Check : null"
                    >
                        {{ $ingredient->herb->name }}
                    </x-filament::tabs.item>
                @endforeach
            </x-filament::tabs>

            <div wire:loading.remove wire:target="herb" class="[&_.fi-ta-ctn]:rounded-none [&_.fi-ta-ctn]:ring-0">
                {{ $this->form }}
            </div>

            <x-filament::section wire:loading.flex
                                 wire:target="herb"
                                 class="ring-0 rounded-none animate-pulse justify-center items-center">
                <div class="h-[5.25rem] flex items-center">
                    <x-filament::loading-indicator class="w-8 h-8"/>
                </div>
            </x-filament::section>

            <x-slot:footer>
                <x-bag-amount-bar-legend/>
            </x-slot:footer>
        </x-filament::section>
    @endif
</div>
