{{-- To attain knowledge, add things every day; To attain wisdom, subtract things every day. --}}

<div class="grow" x-data="{
        total: $wire.entangle('total', true),
        free: $wire.entangle('free', true),
        used:  $wire.entangle('used', true),
        trashed: $wire.entangle('trashed', true),

        toPercentage(value){
            return (value / this.total) * 100 + '%';
        }
    }">
    <div class="flex items-stretch gap-1 w-full">
        <div class="flex items-stretch grow overflow-hidden transition-all min-w-max basis-0"
             x-bind:style="{flexBasis: toPercentage(trashed)}">
            <x-filament::badge color="danger"
                               class="transition-all rounded-l-md rounded-r-sm min-w-max overflow-hidden grow">
                <span class="flex items-center relative">
                    <x-filament::icon icon="heroicon-s-trash" class="w-4 h-4"/>
                    <span class="transition-all overflow-hidden"
                          x-transition
                          x-show="trashed > 0"
                          x-text="trashed > 0 ? '  ' + Math.round(trashed) + 'g' : ''"></span>
                </span>
            </x-filament::badge>
        </div>
        <div class="flex items-stretch grow overflow-hidden transition-all min-w-max basis-0"
             x-bind:style="{flexBasis: toPercentage(used)}">
            <x-filament::badge color="warning"
                               class="transition-all rounded-sm min-w-max overflow-hidden grow">
                <span class="flex items-center relative">
                    <x-filament::icon icon="heroicon-s-x-mark" class="w-4 h-4"/>
                    <span class="transition-all overflow-hidden"
                          x-transition
                          x-show="used > 0"
                          x-text="used > 0 ? '  ' + Math.round(used) + 'g' : ''"></span>
                </span>
            </x-filament::badge>
        </div>
        <div class="flex items-stretch grow overflow-hidden transition-all min-w-max basis-0"
             x-bind:style="{flexBasis: toPercentage(free)}">
            <x-filament::badge color="success"
                               class="transition-all rounded-l-sm rounded-r-md min-w-max overflow-hidden grow">
                <span class="flex items-center">
                    <x-filament::icon icon="heroicon-s-check" class="w-4 h-4"/>
                    <span class="transition-all overflow-hidden"
                          x-transition
                          x-show="free > 0"
                          x-text="free > 0 ? '  ' + Math.round(free) + 'g' : ''"></span>
                </span>
            </x-filament::badge>
        </div>
    </div>
</div>
