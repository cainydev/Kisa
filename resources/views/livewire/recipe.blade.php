{{-- The Master doesn't talk, he acts. --}}

<div>
    @if ($this->ingredients->count() === 0)
        <x-filament::section>
            <x-filament::section.heading>Variante ohne Zutaten</x-filament::section.heading>
            <x-filament::section.description>Nichts zu tun hier.</x-filament::section.description>
        </x-filament::section>
    @else
        {{ $this->form }}
    @endif
</div>
