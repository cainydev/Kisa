@php
    /** @var App\Models\Bag $bag */
    $bag = $getRecord();

    $total = $bag->size;
    $free = $bag->getCurrentWithTrashed();
    $freePercentage = ($free / $total) * 100;
    $trashed = $bag->trashed;
    $trashedPercentage = ($trashed / $total) * 100;
    $used = $bag->size - $free - $trashed;
    $usedPercentage = ($used / $total) * 100;
@endphp

<div {{ $getExtraAttributeBag() }} class="grow">
    <div class="flex items-stretch gap-0.5 w-full">
        @if($total > 0)
            <div class="flex items-stretch grow overflow-hidden transition-all min-w-max basis-0"
                @style(["flex-basis: $trashedPercentage%"])>
                <x-filament::badge color="danger"
                                   class="transition-all rounded-l-md rounded-r-xs min-w-max overflow-hidden grow">
                    <span class="flex items-center relative">
                        <x-filament::icon icon="heroicon-s-trash" class="w-4 h-4"/>
                        @if($trashed > 0)
                            <span class="transition-all overflow-hidden"> {{ round($trashed) }}g</span>
                        @endif
                    </span>
                </x-filament::badge>
            </div>
            <div class="flex items-stretch grow overflow-hidden transition-all min-w-max basis-0"
                @style(["flex-basis: $usedPercentage%"])>
                <x-filament::badge color="warning"
                                   class="transition-all rounded-xs min-w-max overflow-hidden grow">
                    <span class="flex items-center relative">
                        <x-filament::icon icon="heroicon-s-x-mark" class="w-4 h-4"/>
                        @if($used > 0)
                            <span class="transition-all overflow-hidden"> {{ round($used) }}g</span>
                        @endif
                    </span>
                </x-filament::badge>
            </div>
            <div class="flex items-stretch grow overflow-hidden transition-all min-w-max basis-0"
                @style(["flex-basis: $freePercentage%"])>
                <x-filament::badge color="success"
                                   class="transition-all rounded-l-xs rounded-r-md min-w-max overflow-hidden grow">
                    <span class="flex items-center">
                        <x-filament::icon icon="heroicon-s-check" class="w-4 h-4"/>
                        @if($free > 0)
                            <span class="transition-all overflow-hidden"> {{ round($free) }}g</span>
                        @endif
                    </span>
                </x-filament::badge>
            </div>
        @else
            Empty
        @endif
    </div>
</div>
