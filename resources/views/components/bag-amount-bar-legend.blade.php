<div class="flex items-center justify-center gap-3 py-2">
    <div class="hidden md:flex items-stretch gap-1">
        <div class="min-w-max">
            <x-filament::badge color="gray" icon="heroicon-s-calendar">
                MHD
            </x-filament::badge>
        </div>
    </div>
    <div class="hidden md:flex items-stretch gap-1">
        <div class="min-w-max">
            <x-filament::badge icon="heroicon-s-hashtag">
                Charge
            </x-filament::badge>
        </div>
    </div>
    <div class="flex items-stretch gap-1">
        <div class="min-w-max">
            <x-filament::badge icon="heroicon-s-trash" color="danger"
                               class="rounded-sm rounded-l-md min-w-max">
                Ausschuss
            </x-filament::badge>
        </div>
        <div class="min-w-max">
            <x-filament::badge icon="heroicon-s-x-mark" color="warning"
                               class="rounded-sm min-w-max">
                Verbraucht
            </x-filament::badge>
        </div>
        <div class="min-w-max">
            <x-filament::badge icon="heroicon-s-check" color="success"
                               class="rounded-sm rounded-r-md min-w-max">
                Übrig
            </x-filament::badge>
        </div>
    </div>
</div>
