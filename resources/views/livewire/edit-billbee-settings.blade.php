{{-- The Master doesn't talk, he acts. --}}

<div>
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

        <x-filament::loading-indicator wire:loading="test" class="h-12 w-12 mx-auto"/>

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
</div>
