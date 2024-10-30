{{-- The Master doesn't talk, he acts. --}}

<x-filament::section>
    <x-slot name="heading">
        Billbee Einstellungen
    </x-slot>

    <x-slot name="description">
        @if($this->form->getState()['enabled'])
        Enthält alle Verbindungsdetails um mit der Billbee-API zu kommunizieren.
        @else
        Aktiviere die Schnittstelle um deine App mit der Billbee-API zu verbinden.
        @endif
    </x-slot>

    <x-slot name="headerEnd">
        @if($this->form->getState()['enabled'] && !$this->testSuccess)
            <x-filament::button color="info" x-on:click="$dispatch('open-modal', { id: 'connection-test-modal' })" wire:click="test">
                Verbindung testen
            </x-filament::button>
        @endif
        @if(!$this->form->getState()['enabled'] || $this->testSuccess)
            <x-filament::button wire:click="save">
                Speichern
            </x-filament::button>
            @endif
    </x-slot>

    {{ $this->form }}

    <x-filament::modal id="connection-test-modal">
        <x-slot name="heading">
            <p wire:loading="test">Teste Verbindung...</p>
            <p wire:loading.remove="test">
                @if($this->testSuccess)
                    Verbindungsaufbau erfolgreich
                @else
                    Verbindungsaufbau gescheitert
                @endif
            </p>
        </x-slot>

        <x-filament::loading-indicator wire:loading="test" class="h-12 w-12 mx-auto" />

        <div wire:loading.remove="test" class="flex gap-3">
            @if($this->testSuccess)
                <x-filament::icon icon="heroicon-o-check-circle" class="shrink-0 w-12 h-12 text-success-500"/>
                <p>Die Einstellungen können jetzt gespeichert werden.</p>
            @else
                <x-filament::icon icon="heroicon-o-x-circle" class="shrink-0 w-12 h-12 text-danger-500"/>
                <p>Die Einstellungen können nur gespeichert werden, wenn der Verbindungsaufbau erfolgreich war.</p>
                @endif
        </div>
    </x-filament::modal>
</x-filament::section>
