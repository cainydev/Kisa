<div class="grid grid-cols-2 mb-3 overflow-hidden bg-white rounded-md shadow-sm">

    <x-big-button href="{{ route('platform.bottle') }}">
        <x-slot name="icon">
            <x-orchid-icon path="filter" />
        </x-slot>

        <x-slot name="title">
            Abfüllen
        </x-slot>

        Neue Tüten für ein bestehendes Produkt abfüllen
    </x-big-button>

    <x-big-button href="{{ route('platform.deliveries') }}">
        <x-slot name="icon">
            <x-orchid-icon path="basket-loaded" />
        </x-slot>

        <x-slot name="title">
            Einlagern
        </x-slot>
        Eine neue Bestellung ist angekommen und du möchtest sie einlagern. Inklusive Bio-Kontrollformular.
    </x-big-button>

</div>

<div class="grid grid-cols-2 mb-3 bg-white rounded-md shadow-sm">

    <x-big-button href="{{ route('platform.info.dashboard') }}">
        <x-slot name="icon">
            <x-orchid-icon path="monitor" />
        </x-slot>

        <x-slot name="title">
            Dashboard
        </x-slot>
        Statistiken ansehen
    </x-big-button>

    <x-big-button href="{{ route('platform.notifications') }}">
        <x-slot name="icon">
            <x-orchid-icon path="bell" />
        </x-slot>

        <x-slot name="title">
            Benachrichtigungen
        </x-slot>
        Wichtige Benachrichtigungen ansehen, die zum Beispiel bald ablaufende Säcke oder einen fast leeren Bestand
        melden.
    </x-big-button>

</div>
