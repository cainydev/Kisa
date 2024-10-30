{{-- A good traveler has no fixed plans and is not intent upon arriving. --}}

<x-filament::section>
    <x-slot name="heading">
        Benutzer Einstellungen
    </x-slot>

    <x-slot name="description">
        Konfiguriere dein Profil um besser erkennbar für andere zu sein.
    </x-slot>

    <x-slot name="headerEnd">
        <x-filament::button wire:click="save">
            Speichern
        </x-filament::button>
    </x-slot>

    {{ $this->form }}
</x-filament::section>
